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
    
    private static function get_api_url() {
        $credentials = self::get_api_credentials();
        
        if ($credentials['test_mode']) {
            return 'https://api.playground.klarna.com/';
        } else {
            return 'https://api.klarna.com/';
        }
    }
    
    public static function create_order($sale_ids) {
        global $wpdb;
        
        if (empty($sale_ids)) {
            throw new Exception('No sales provided');
        }
        
        $sale_ids_str = implode(',', array_map('intval', $sale_ids));
        $sales = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cs_sales WHERE id IN ($sale_ids_str)");
        
        if (empty($sales)) {
            throw new Exception('No sales found');
        }
        
        $credentials = self::get_api_credentials();
        
        if (empty($credentials['merchant_id']) || empty($credentials['shared_secret'])) {
            throw new Exception('Klarna API credentials not configured');
        }
        
        // Create a cart from the sales
        $order_lines = array();
        $total_amount = 0;
        
        foreach ($sales as $sale) {
            $products = json_decode($sale->products, true);
            
            if (!empty($products)) {
                foreach ($products as $product) {
                    $quantity = isset($product['quantity']) ? intval($product['quantity']) : 1;
                    $unit_price = floatval($product['price']);
                    $line_total = $unit_price * $quantity;
                    
                    $order_lines[] = array(
                        'name' => $product['name'],
                        'quantity' => $quantity,
                        'unit_price' => intval($unit_price * 100), // Klarna uses amounts in cents
                        'tax_rate' => 2500, // 25% tax rate (Sweden)
                        'total_amount' => intval($line_total * 100),
                        'total_tax_amount' => intval($line_total * 0.2 * 100) // 20% of price is tax
                    );
                    
                    $total_amount += $line_total;
                }
            } else {
                // If no products, just add the total as a single line
                $order_lines[] = array(
                    'name' => 'Sale #' . $sale->id,
                    'quantity' => 1,
                    'unit_price' => intval($sale->sale_amount * 100),
                    'tax_rate' => 2500,
                    'total_amount' => intval($sale->sale_amount * 100),
                    'total_tax_amount' => intval($sale->sale_amount * 0.2 * 100)
                );
                
                $total_amount += $sale->sale_amount;
            }
        }
        
        // Create Klarna order
        $order_data = array(
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-SE',
            'order_amount' => intval($total_amount * 100),
            'order_tax_amount' => intval($total_amount * 0.2 * 100),
            'order_lines' => $order_lines,
            'merchant_urls' => array(
                'terms' => site_url('/terms/'),
                'checkout' => site_url('/checkout/'),
                'confirmation' => site_url('/confirmation/'),
                'push' => site_url('/wc-api/klarna_push/'),
            )
        );
        
        // Call Klarna API
        $api_url = self::get_api_url() . 'checkout/v3/orders';
        
        error_log("=== KLARNA API CALL ===");
        error_log("URL: " . $api_url);
        error_log("Order data: " . json_encode($order_data, JSON_PRETTY_PRINT));
        
        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($credentials['merchant_id'] . ':' . $credentials['shared_secret'])
            ),
            'body' => json_encode($order_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log("Klarna API connection error: " . $response->get_error_message());
            throw new Exception('Klarna API connection error: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $body = json_decode($response_body, true);
        
        error_log("=== KLARNA API RESPONSE ===");
        error_log("Response code: " . $response_code);
        error_log("Response body: " . $response_body);
        error_log("Parsed body: " . print_r($body, true));
        
        if ($response_code !== 200 && $response_code !== 201) {
            $error_message = 'Klarna API error (Code: ' . $response_code . ')';
            
            if (isset($body['error_code'])) {
                $error_message .= ' - Error Code: ' . $body['error_code'];
            }
            if (isset($body['error_message'])) {
                $error_message .= ' - Message: ' . $body['error_message'];
            }
            if (isset($body['error_messages'])) {
                $error_message .= ' - Details: ' . implode(', ', $body['error_messages']);
            }
            
            error_log("Klarna API error: " . $error_message);
            throw new Exception($error_message);
        }
        
        // Enhanced URL extraction logic
        $checkout_url = self::extract_checkout_url($body);
        
        if (empty($checkout_url)) {
            error_log("=== KLARNA RESPONSE ANALYSIS ===");
            error_log("Available fields: " . implode(', ', array_keys($body)));
            error_log("Full response structure: " . print_r($body, true));
            
            // Try to create a custom checkout URL if we have an order ID
            if (isset($body['order_id'])) {
                $checkout_url = self::construct_checkout_url($body['order_id']);
                error_log("Constructed checkout URL: " . $checkout_url);
            }
            
            if (empty($checkout_url)) {
                throw new Exception('Could not extract checkout URL from Klarna response. Available fields: ' . implode(', ', array_keys($body)));
            }
        }
        
        // Update sales with Klarna order ID
        if (!empty($body['order_id'])) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}cs_sales SET klarna_order_id = %s WHERE id IN ($sale_ids_str)",
                $body['order_id']
            ));
            error_log("Updated sales with Klarna order ID: " . $body['order_id']);
        }
        
        error_log("=== FINAL RESULT ===");
        error_log("Checkout URL: " . $checkout_url);
        error_log("URL validation: " . (filter_var($checkout_url, FILTER_VALIDATE_URL) ? 'VALID' : 'INVALID'));
        
        return $checkout_url;
    }
    
    /**
     * Extract checkout URL from Klarna response
     */
    private static function extract_checkout_url($body) {
        $checkout_url = '';
        
        // Try different possible URL fields
        $url_fields = array(
            'checkout_url',
            'gui.snippet',
            'gui.checkout_url', 
            'html_snippet',
            'checkout.html_snippet',
            'checkout_snippet',
            'snippet'
        );
        
        foreach ($url_fields as $field) {
            if (strpos($field, '.') !== false) {
                // Handle nested fields like 'gui.snippet'
                $parts = explode('.', $field);
                $current = $body;
                
                foreach ($parts as $part) {
                    if (isset($current[$part])) {
                        $current = $current[$part];
                    } else {
                        $current = null;
                        break;
                    }
                }
                
                if (!empty($current)) {
                    $checkout_url = $current;
                    error_log("Found checkout URL in field: " . $field);
                    break;
                }
            } else {
                // Handle direct fields
                if (!empty($body[$field])) {
                    $checkout_url = $body[$field];
                    error_log("Found checkout URL in field: " . $field);
                    break;
                }
            }
        }
        
        // If we got HTML snippet, we need to extract the actual URL or handle it differently
        if (!empty($checkout_url) && strpos($checkout_url, '<') !== false) {
            // This is HTML snippet, extract URL from it
            $extracted_url = self::extract_url_from_html($checkout_url);
            if (!empty($extracted_url)) {
                $checkout_url = $extracted_url;
            } else {
                // Create a data URL for the HTML snippet
                $checkout_url = 'data:text/html;charset=utf-8,' . urlencode($checkout_url);
            }
        }
        
        return $checkout_url;
    }
    
    /**
     * Extract URL from HTML snippet
     */
    private static function extract_url_from_html($html) {
        // Try to find checkout URL in the HTML
        if (preg_match('/https?:\/\/[^"\s<>]+klarna[^"\s<>]*/', $html, $matches)) {
            return $matches[0];
        }
        
        // Try to find any klarna domain URL
        if (preg_match('/https?:\/\/[^"\s<>]*klarna\.com[^"\s<>]*/', $html, $matches)) {
            return $matches[0];
        }
        
        return '';
    }
    
    /**
     * Construct checkout URL from order ID
     */
    private static function construct_checkout_url($order_id) {
        $credentials = self::get_api_credentials();
        
        if ($credentials['test_mode']) {
            return 'https://checkout.testdrive.klarna.com/checkout/' . $order_id;
        } else {
            return 'https://checkout.klarna.com/checkout/' . $order_id;
        }
    }
    
    public static function get_order($order_id) {
        $credentials = self::get_api_credentials();
        
        if (empty($credentials['merchant_id']) || empty($credentials['shared_secret'])) {
            throw new Exception('Klarna API credentials not configured');
        }
        
        $api_url = self::get_api_url() . 'checkout/v3/orders/' . $order_id;
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($credentials['merchant_id'] . ':' . $credentials['shared_secret'])
            )
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    // Add this method to make the credentials publicly accessible for debugging
    public static function get_api_credentials_debug() {
        return self::get_api_credentials();
    }
}
