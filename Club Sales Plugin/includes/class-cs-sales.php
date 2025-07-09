<?php
class CS_Sales {
    public static function add_sale($data) {
    global $wpdb;
    
    $sale_data = array(
        'user_id' => get_current_user_id(),
        'customer_name' => sanitize_text_field($data['customer_name']),
        'phone' => sanitize_text_field($data['phone'] ?? ''),
        'email' => $data['email'] ?? '',
        'address' => sanitize_textarea_field($data['address'] ?? ''),
        'sale_amount' => floatval($data['sale_amount']),
        'sale_date' => sanitize_text_field($data['sale_date']),
        'notes' => sanitize_textarea_field($data['notes'] ?? ''),
        'products' => wp_unslash($data['products']), // Use wp_unslash instead of sanitize_text_field
        'status' => 'pending'
    );
    
    // Insert the sale record
    $sale_inserted = $wpdb->insert($wpdb->prefix . 'cs_sales', $sale_data);
    do_action('cs_order_details_viewed', $sale_data + ['id' => $wpdb->insert_id]);

    // If sale was inserted successfully, add an opportunity record
    if ($sale_inserted) {
        $opportunity_data = array(
            'user_id' => get_current_user_id(),
            'status' => 'completed'
        );
        $wpdb->insert($wpdb->prefix . 'cs_opportunities', $opportunity_data);
        if (!empty($sale_data['email'])) {
            // Add sale data to the hook
            do_action('cs_after_sale_added', $sale_data);
        }
        return $wpdb->insert_id;
    }
    
    return false;
}
    
    public static function get_sales($user_id = null, $status = null) {
        global $wpdb;
        
        $user_id = $user_id ?: get_current_user_id();
        
        $sql = "SELECT * FROM {$wpdb->prefix}cs_sales WHERE user_id = %d";
        $params = array($user_id);
        
        if ($status) {
            $sql .= " AND status = %s";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY sale_date DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    public static function get_group_sales($group_id) {
        global $wpdb;
        
        $sql = "SELECT s.* FROM {$wpdb->prefix}cs_sales s 
                JOIN {$wpdb->prefix}cs_group_members gm ON s.user_id = gm.user_id 
                WHERE gm.group_id = %d 
                ORDER BY s.sale_date DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $group_id));
    }
    
    public static function search_products($search_term) {
        // Add debugging
        error_log('CS_Sales::search_products called with term: ' . $search_term);
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            error_log('WooCommerce is not active');
            return array();
        }

        global $wpdb;

        // Combine queries to exclude out-of-stock products
        $query = $wpdb->prepare(
            "SELECT DISTINCT p.ID, p.post_title 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_stock_status ON p.ID = pm_stock_status.post_id 
                AND pm_stock_status.meta_key = '_stock_status'
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish'
            AND (pm_stock_status.meta_value IS NULL OR pm_stock_status.meta_value != 'outofstock')"
        );

        // Execute the query to get product IDs
        $product_ids = $wpdb->get_col($query);

        // If no products found, return empty array
        if (empty($product_ids)) {
            return array();
        }

        // Prepare for WooCommerce product retrieval
        $results = array();

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);

            if ($product) {
                // Use the club sales pricing calculation
                $club_sales_price = CS_Price_Calculator::get_club_sales_price($product_id);

                // Get the product permalink and handle potential errors
                try {
                    $permalink = $product->get_permalink();
                    // If permalink is empty or invalid, generate a fallback URL
                    if (empty($permalink) || $permalink === '#') {
                        $permalink = add_query_arg('product_id', $product_id, home_url('/product'));
                    }
                } catch (Exception $e) {
                    // Fallback to a generic URL if get_permalink() fails
                    $permalink = add_query_arg('product_id', $product_id, home_url('/product'));
                    error_log('Error getting permalink for product ' . $product_id . ': ' . $e->getMessage());
                }

                $results[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'sku' => $product->get_sku(),
                    'price' => wc_get_price_to_display($product),
                    'total_price' => $club_sales_price,
                    'regular_price' => $product->get_regular_price(),
                    'stock' => $product->get_stock_quantity(),
                    'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                    'permalink' => $permalink // Add the permalink explicitly
                );
            }
        }
        
        error_log('Returning ' . count($results) . ' products');
        return $results;
    }
    
    /**
     * Calculate total profit based on Total Price - RRP for all products in orders
     * 
     * @param int|null $user_id User ID to calculate profit for
     * @return float Total profit amount
     */
    /**
 * Calculate total profit based on Total Price - RRP for all products in orders
 * This version handles escaped JSON strings properly
 * 
 * @param int|null $user_id User ID to calculate profit for
 * @return float Total profit amount
 */
public static function calculate_total_profit($user_id = null) {
    global $wpdb;
    
    // If no user ID provided, use current user
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    error_log("=== PROFIT CALCULATION START for user ID: " . $user_id . " ===");
    
    // Get all sales for the user (both pending and completed)
    $sales = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cs_sales WHERE user_id = %d",
        $user_id
    ));
    
    error_log("Found " . count($sales) . " sales for user " . $user_id);
    
    $total_profit = 0;
    $sale_count = 0;
    
    foreach ($sales as $sale) {
        $sale_count++;
        error_log("Processing sale #" . $sale_count . " (ID: " . $sale->id . ")");
        
        // Handle escaped JSON strings
        $products_json = $sale->products;
        
        // Remove escape slashes if present
        if (strpos($products_json, '\"') !== false) {
            $products_json = stripslashes($products_json);
            error_log("Removed slashes from JSON for sale ID: " . $sale->id);
        }
        
        // Parse the products JSON
        $products = json_decode($products_json, true);
        
        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON parse error for sale ID " . $sale->id . ": " . json_last_error_msg());
            error_log("Raw JSON: " . $products_json);
            continue;
        }
        
        error_log("Products in sale: " . count($products));
        
        $sale_profit = 0;
        
        if (!empty($products) && is_array($products)) {
            foreach ($products as $product_data) {
                // Get the product ID
                $product_id = isset($product_data['id']) ? intval($product_data['id']) : 0;
                
                if ($product_id > 0) {
                    // Get the sale price (total price from the order)
                    $sale_price = isset($product_data['price']) ? floatval($product_data['price']) : 0;
                    
                    // Get the quantity
                    $quantity = isset($product_data['quantity']) ? intval($product_data['quantity']) : 1;
                    
                    // Get RRP - try multiple methods
                    $rrp = 0;
                    $rrp_source = 'not found';
                    
                    // Method 1: Try ACF field 'rrp'
                    if (function_exists('get_field')) {
                        $acf_rrp = get_field('rrp', $product_id);
                        if (!empty($acf_rrp) && is_numeric($acf_rrp)) {
                            $rrp = floatval($acf_rrp);
                            $rrp_source = 'ACF rrp field';
                        }
                    }
                    
                    // Method 2: If no RRP from ACF, try WooCommerce regular price
                    if ($rrp == 0) {
                        $wc_product = wc_get_product($product_id);
                        if ($wc_product) {
                            $regular_price = $wc_product->get_regular_price();
                            if (!empty($regular_price)) {
                                $rrp = floatval($regular_price);
                                $rrp_source = 'WooCommerce regular price';
                            }
                        }
                    }
                    
                    // NEW CALCULATION: (Sale Price × Quantity) - RRP
                    // This treats RRP as a total cost, not per-unit
                    $product_profit = ($rrp - $sale_price) * $quantity;
                    
                    // Add to sale profit
                    $sale_profit += $product_profit;
                    
                    // Debug logging with new calculation
                    error_log("  Product ID: {$product_id}");
                    error_log("  Sale Price: {$sale_price} SEK");
                    error_log("  Quantity: {$quantity}");
                    error_log("  Total Revenue: ({$sale_price} × {$quantity}) = {$total_revenue} SEK");
                    error_log("  RRP (Cost): {$rrp} SEK (source: {$rrp_source})");
                    error_log("  Product Profit: {$total_revenue} - {$rrp} = {$product_profit} SEK");
                } else {
                    error_log("  Invalid product ID: " . $product_id);
                }
            }
        } else {
            error_log("  No products found in sale");
        }
        
        error_log("Sale #{$sale_count} profit: {$sale_profit} SEK");
        $total_profit += $sale_profit;
    }
    
    // Also include profits from child users if this is a parent user
    if (CS_Child_Manager::can_manage_children($user_id)) {
        $children = CS_Child_Manager::get_parent_children($user_id);
        
        error_log("User can manage children. Found " . count($children) . " children");
        
        foreach ($children as $child) {
            $child_profit = self::calculate_total_profit($child['id']);
            $total_profit += $child_profit;
            error_log("Child ID: {$child['id']}, Child Profit: {$child_profit} SEK");
        }
    }
    
    error_log("=== TOTAL CALCULATED PROFIT: " . $total_profit . " SEK ===");
    
    return $total_profit;
}

    /**
     * Get user statistics including real profit calculation
     * 
     * @param int $user_id User ID
     * @return array Statistics array
     */
   public static function get_user_stats($user_id) {
    global $wpdb;
    
    error_log("=== GET USER STATS START for user ID: " . $user_id . " ===");
    
    $stats = array(
        'total_profit' => 0,
        'opportunities' => 0,
        'completed_sales' => 0
    );
    
    // Calculate real profit instead of just summing sale amounts
    $total_profit = self::calculate_total_profit($user_id);
    
    // Get opportunities count (total number of sales)
    $opportunities = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}cs_sales WHERE user_id = %d",
        $user_id
    ));
    
    // Get completed sales count
    $completed_sales = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}cs_sales WHERE user_id = %d AND status = 'completed'",
        $user_id
    ));
    
    // Include child users' stats if this is a parent
    if (CS_Child_Manager::can_manage_children($user_id)) {
        $children = CS_Child_Manager::get_parent_children($user_id);
        
        foreach ($children as $child) {
            // Add child's opportunities
            $child_opportunities = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cs_sales WHERE user_id = %d",
                $child['id']
            ));
            $opportunities += $child_opportunities;
            
            // Add child's completed sales
            $child_completed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cs_sales WHERE user_id = %d AND status = 'completed'",
                $child['id']
            ));
            $completed_sales += $child_completed;
        }
    }
    
    $stats['total_profit'] = $total_profit;
    $stats['opportunities'] = $opportunities ?: 0;
    $stats['completed_sales'] = $completed_sales ?: 0;
    
    error_log("=== FINAL USER STATS ===");
    error_log("Total Profit: " . $stats['total_profit'] . " SEK");
    error_log("Opportunities: " . $stats['opportunities']);
    error_log("Completed Sales: " . $stats['completed_sales']);
    
    return $stats;
}

    /**
     * Refresh/recalculate total profit
     * 
     * @return float Total profit
     */
    public static function refresh_total_profit() {
        $user_id = get_current_user_id();
        return self::calculate_total_profit($user_id);
    }
    
    public static function get_group_stats($group_id) {
        global $wpdb;
        
        $stats = array(
            'total_profit' => 0,
            'opportunities' => 0,
            'member_count' => 0,
            'completed_sales' => 0
        );
        
        // Get group members
        $members = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}cs_group_members WHERE group_id = %d",
            $group_id
        ));
        
        if (!empty($members)) {
            // Calculate profit for each member
            foreach ($members as $member_id) {
                $member_profit = self::calculate_total_profit($member_id);
                $stats['total_profit'] += $member_profit;
            }
            
            $member_list = implode(',', array_map('intval', $members));
            
            // Get opportunities count
            $opportunities = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cs_opportunities WHERE user_id IN ($member_list)"
            );
            
            // Get completed sales count
            $completed_sales = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cs_sales WHERE user_id IN ($member_list) AND status = 'completed'"
            );
            
            $stats['opportunities'] = $opportunities ?: 0;
            $stats['completed_sales'] = $completed_sales ?: 0;
        }
        
        $stats['member_count'] = count($members);
        
        return $stats;
    }
}