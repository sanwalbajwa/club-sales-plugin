<?php
class CS_Klarna {
    private static function get_api_credentials() {
        $merchant_id = '20d65877-6280-4d5e-8999-5e3e543695d3';
        $shared_secret = 'klarna_live_api_V3k0KTkzKnpRVFdZZyM_ekEvVFRnSUtEZlJBIUI0SWIsMjBkNjU4NzctNjI4MC00ZDVlLTg5OTktNWUzZTU0MzY5NWQzLDEsdmU0cDMvQkgySjRTTHZpdklkdjE2UUtaQnpYdmtvWmlRMk16T25MM1FETT0';
        $test_mode = false;
        
        return array(
            'merchant_id' => $merchant_id,
            'shared_secret' => $shared_secret,
            'test_mode' => $test_mode
        );
    }
    
    public static function create_order($sale_ids) {
        global $wpdb;
        
        if (empty($sale_ids)) {
            throw new Exception('No sales provided');
        }
        
        // Get the sales data
        $sale_ids_str = implode(',', array_map('intval', $sale_ids));
        $sales = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cs_sales WHERE id IN ($sale_ids_str)");
        
        if (empty($sales)) {
            throw new Exception('No sales found');
        }
        
        try {
            // Clear the cart first
            if (function_exists('WC')) {
                WC()->cart->empty_cart();
            }
            
            // Add products to cart and set customer data
            $checkout_url = self::add_products_to_cart_and_checkout($sales);
            
            if ($checkout_url) {
                // Update the original sales with processing status
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}cs_sales SET status = 'processing' WHERE id IN ($sale_ids_str)"
                ));
                
                return $checkout_url;
            } else {
                throw new Exception('Failed to prepare checkout');
            }
            
        } catch (Exception $e) {
            error_log("Order creation error: " . $e->getMessage());
            throw new Exception('Order creation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Add products to cart and return checkout URL
     */
    private static function add_products_to_cart_and_checkout($sales) {
        if (!function_exists('WC')) {
            throw new Exception('WooCommerce is not active');
        }
        
        error_log("=== ADDING PRODUCTS TO CART (CHILD ORDER FIX) ===");
        
        $customer_data = null;
        $products_added = 0;
        
        // Process each sale
        foreach ($sales as $sale) {
            error_log("Processing sale ID: " . $sale->id . " from user ID: " . $sale->user_id);
            
            // Get customer data from first sale
            if (!$customer_data) {
                $customer_data = array(
                    'customer_name' => $sale->customer_name,
                    'email' => $sale->email,
                    'phone' => $sale->phone,
                    'address' => $sale->address
                );
                error_log("Customer data: " . print_r($customer_data, true));
            }
            
            // Parse products from sale
            $products_json = $sale->products;
            
            // Handle escaped JSON
            if (strpos($products_json, '\"') !== false) {
                $products_json = stripslashes($products_json);
                error_log("Removed slashes from JSON");
            }
            
            error_log("Products JSON: " . $products_json);
            
            $products = json_decode($products_json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON parse error for sale ID " . $sale->id . ": " . json_last_error_msg());
                
                // Try to create a fallback product from the sale amount
                $fallback_added = self::add_fallback_product_to_cart($sale);
                if ($fallback_added) {
                    $products_added++;
                }
                continue;
            }
            
            error_log("Parsed products: " . print_r($products, true));
            
            // Add products to cart
            if (!empty($products) && is_array($products)) {
                foreach ($products as $product_data) {
                    $product_id = isset($product_data['id']) ? intval($product_data['id']) : 0;
                    $quantity = isset($product_data['quantity']) ? intval($product_data['quantity']) : 1;
                    
                    // Handle different price fields that might be present
                    $price = 0;
                    if (isset($product_data['price'])) {
                        $price = floatval($product_data['price']);
                    } elseif (isset($product_data['total_price'])) {
                        $price = floatval($product_data['total_price']);
                    } elseif (isset($product_data['sale_price'])) {
                        $price = floatval($product_data['sale_price']);
                    }
                    
                    $product_name = isset($product_data['name']) ? $product_data['name'] : 'Unknown Product';
                    
                    error_log("Product details: ID={$product_id}, Name={$product_name}, Qty={$quantity}, Price={$price}");
                    
                    // Validate product data
                    if ($product_id <= 0) {
                        error_log("Invalid product ID: {$product_id}, creating generic product");
                        
                        // Create a generic product with the name and price
                        $generic_added = self::add_generic_product_to_cart($product_name, $price, $quantity);
                        if ($generic_added) {
                            $products_added++;
                        }
                        continue;
                    }
                    
                    if ($quantity <= 0) {
                        error_log("Invalid quantity: {$quantity}, setting to 1");
                        $quantity = 1;
                    }
                    
                    if ($price <= 0) {
                        error_log("Invalid price: {$price}, trying to get from WooCommerce product");
                        
                        // Try to get price from WooCommerce product
                        $wc_product = wc_get_product($product_id);
                        if ($wc_product) {
                            $price = $wc_product->get_price();
                            error_log("Got price from WooCommerce: {$price}");
                        }
                        
                        if ($price <= 0) {
                            error_log("Still no valid price, skipping product");
                            continue;
                        }
                    }
                    
                    // Try to add to cart
                    $cart_added = self::add_single_product_to_cart($product_id, $product_name, $price, $quantity);
                    if ($cart_added) {
                        $products_added++;
                    }
                }
            } else {
                error_log("No valid products array found, creating fallback from sale amount");
                
                // Create fallback product from sale amount
                $fallback_added = self::add_fallback_product_to_cart($sale);
                if ($fallback_added) {
                    $products_added++;
                }
            }
        }
        
        error_log("Total products added to cart: " . $products_added);
        
        if ($products_added === 0) {
            // Last resort: create a generic "Club Sales Order" product
            error_log("No products added, creating last resort product");
            
            $total_amount = array_sum(array_column($sales, 'sale_amount'));
            $generic_added = self::add_generic_product_to_cart('Club Sales Order', $total_amount, 1);
            
            if ($generic_added) {
                $products_added = 1;
            } else {
                throw new Exception('No products could be added to cart');
            }
        }
        
        // Store customer data in session for checkout
        if ($customer_data) {
            self::store_customer_data_in_session($customer_data);
        }
        
        // Return checkout URL
        return wc_get_checkout_url();
    }
	
	private static function add_fallback_product_to_cart($sale) {
        $product_name = "Sale #{$sale->id} - {$sale->customer_name}";
        $price = floatval($sale->sale_amount);
        $quantity = 1;
        
        error_log("Creating fallback product from sale: {$product_name}, Amount: {$price}");
        
        return self::add_generic_product_to_cart($product_name, $price, $quantity);
    }
    private static function add_single_product_to_cart($product_id, $product_name, $price, $quantity) {
        try {
            // Check if WooCommerce product exists
            $wc_product = wc_get_product($product_id);
            
            if ($wc_product && $wc_product->exists() && $wc_product->is_purchasable()) {
                error_log("Adding existing WooCommerce product {$product_id}");
                
                // Add existing WooCommerce product to cart
                $cart_item_data = array(
                    'club_sales_price' => $price,
                    'club_sales_original' => true,
                    'club_sales_name' => $product_name
                );
                
                $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);
                
                if ($cart_item_key) {
                    error_log("Successfully added WooCommerce product {$product_id} to cart");
                    return true;
                } else {
                    error_log("Failed to add WooCommerce product {$product_id} to cart");
                }
            } else {
                error_log("WooCommerce product {$product_id} not found or not purchasable");
            }
            
            // If we can't add the existing product, create a temporary one
            return self::add_generic_product_to_cart($product_name, $price, $quantity);
            
        } catch (Exception $e) {
            error_log("Error adding product to cart: " . $e->getMessage());
            return self::add_generic_product_to_cart($product_name, $price, $quantity);
        }
    }
	
	private static function add_generic_product_to_cart($product_name, $price, $quantity) {
        try {
            error_log("Creating generic product: {$product_name}, Price: {$price}, Qty: {$quantity}");
            
            // Create temporary product
            $temp_product_id = self::create_temporary_product($product_name, $price);
            
            if ($temp_product_id) {
                $cart_item_data = array(
                    'club_sales_temp_product' => true,
                    'club_sales_original_name' => $product_name,
                    'club_sales_price' => $price
                );
                
                $cart_item_key = WC()->cart->add_to_cart($temp_product_id, $quantity, 0, array(), $cart_item_data);
                
                if ($cart_item_key) {
                    error_log("Successfully added temporary product {$temp_product_id} to cart");
                    return true;
                } else {
                    error_log("Failed to add temporary product {$temp_product_id} to cart");
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error creating generic product: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Create a temporary simple product for checkout
     */
    private static function create_temporary_product($name, $price) {
        try {
            $product = new WC_Product_Simple();
            $product->set_name($name);
            $product->set_status('private'); // Make it private
            $product->set_regular_price($price);
            $product->set_price($price);
            $product->set_manage_stock(false);
            $product->set_stock_status('instock');
            $product->set_virtual(true); // Virtual to avoid shipping
            $product->set_downloadable(false);
            $product->set_sold_individually(false);
            
            // Add meta to identify as club sales temp product
            $product->add_meta_data('_club_sales_temp_product', 'yes', true);
            $product->add_meta_data('_club_sales_created', time(), true);
            
            $product_id = $product->save();
            
            if ($product_id) {
                error_log("Created temporary product: {$product_id} - {$name} - {$price}");
                return $product_id;
            } else {
                error_log("Failed to save temporary product");
            }
            
        } catch (Exception $e) {
            error_log("Exception creating temporary product: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Store customer data in WooCommerce session
     */
    private static function store_customer_data_in_session($customer_data) {
        if (!WC()->session) {
            return;
        }
        
        // Parse address
        $address_parts = self::parse_address($customer_data['address']);
        $name_parts = explode(' ', trim($customer_data['customer_name']), 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        // Store customer data in session
        WC()->session->set('club_sales_customer_data', array(
            'billing_first_name' => $first_name,
            'billing_last_name' => $last_name,
            'billing_email' => $customer_data['email'],
            'billing_phone' => $customer_data['phone'],
            'billing_address_1' => $address_parts['address_1'],
            'billing_address_2' => $address_parts['address_2'],
            'billing_city' => $address_parts['city'],
            'billing_postcode' => $address_parts['postcode'],
            'billing_country' => 'SE',
            'shipping_first_name' => $first_name,
            'shipping_last_name' => $last_name,
            'shipping_address_1' => $address_parts['address_1'],
            'shipping_address_2' => $address_parts['address_2'],
            'shipping_city' => $address_parts['city'],
            'shipping_postcode' => $address_parts['postcode'],
            'shipping_country' => 'SE'
        ));
        
        error_log("Stored customer data in session");
    }
    
    /**
     * Parse address string into components
     */
    private static function parse_address($address_string) {
        $lines = explode("\n", trim($address_string));
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);
        
        $parsed = array(
            'address_1' => '',
            'address_2' => '',
            'city' => '',
            'postcode' => ''
        );
        
        if (count($lines) >= 1) {
            $parsed['address_1'] = $lines[0];
        }
        
        if (count($lines) >= 2) {
            $last_line = end($lines);
            
            if (preg_match('/(\d{3}\s?\d{2})\s+(.+)/', $last_line, $matches)) {
                $parsed['postcode'] = str_replace(' ', '', $matches[1]);
                $parsed['city'] = $matches[2];
            } else {
                $parsed['city'] = $last_line;
            }
            
            if (count($lines) > 2) {
                $middle_lines = array_slice($lines, 1, -1);
                $parsed['address_2'] = implode(', ', $middle_lines);
            }
        }
        
        return $parsed;
    }
}
