<?php
class CS_Ajax {
	public static function init() {
		add_action('wp_ajax_cs_add_sale', array(__CLASS__, 'handle_add_sale'));
		add_action('wp_ajax_cs_get_stats', array(__CLASS__, 'handle_get_stats'));
		add_action('wp_ajax_cs_search_products', array(__CLASS__, 'handle_product_search'));
		add_action('wp_ajax_cs_process_klarna', array(__CLASS__, 'handle_klarna_checkout'));
		add_action('wp_ajax_cs_get_sales', array(__CLASS__, 'handle_get_sales'));
		add_action('wp_ajax_cs_get_order_details', array(__CLASS__, 'handle_get_order_details'));
		add_action('wp_ajax_cs_delete_sale', array(__CLASS__, 'handle_delete_sale'));
		add_action('wp_ajax_cs_update_order', array(__CLASS__, 'handle_update_order'));
		add_action('wp_ajax_cs_recalculate_total_profit', array(__CLASS__, 'handle_recalculate_total_profit'));
		add_action('wp_ajax_cs_reset_order_status', array(__CLASS__, 'handle_reset_order_status'));
		// New: Add auto-update handler
		add_action('wp_ajax_cs_check_stats_update', array(__CLASS__, 'handle_check_stats_update'));
		add_action('wp_ajax_cs_mark_order_delivered', array(__CLASS__, 'handle_mark_order_delivered'));
		add_action('wp_ajax_cs_complete_order', array(__CLASS__, 'handle_complete_order'));
		add_action('wp_ajax_cs_restore_sale', array(__CLASS__, 'handle_restore_sale'));
        add_action('wp_ajax_cs_permanently_delete_sale', array(__CLASS__, 'handle_permanently_delete_sale'));
		add_action('wp_ajax_cs_get_product_categories', array(__CLASS__, 'get_product_categories'));
		add_action('wp_ajax_nopriv_cs_get_product_categories', array(__CLASS__, 'get_product_categories'));
		
		// Settings page handlers
		add_action('wp_ajax_cs_change_password', array(__CLASS__, 'handle_change_password'));
		add_action('wp_ajax_cs_update_profile', array(__CLASS__, 'handle_update_profile'));
		add_action('wp_ajax_cs_update_organization', array(__CLASS__, 'handle_update_organization'));
		
		// Sales material handlers
		add_action('wp_ajax_cs_save_selected_products', array(__CLASS__, 'handle_save_selected_products'));
		add_action('wp_ajax_cs_get_selected_products', array(__CLASS__, 'handle_get_selected_products'));
		add_action('wp_ajax_cs_get_preview_material', array(__CLASS__, 'handle_get_preview_material'));
		add_action('wp_ajax_cs_download_material', array(__CLASS__, 'handle_download_material'));
		
		// Kafeteria handlers
		add_action('wp_ajax_cs_get_kafeteria_products', array(__CLASS__, 'handle_get_kafeteria_products'));
		add_action('wp_ajax_cs_get_kafeteria_categories', array(__CLASS__, 'handle_get_kafeteria_categories'));
		add_action('wp_ajax_cs_kafeteria_checkout', array(__CLASS__, 'handle_kafeteria_checkout'));
		
		// Diagnostic handler
		add_action('wp_ajax_cs_check_swish_number', array(__CLASS__, 'handle_check_swish_number'));
		
		// Package overview handler
		add_action('wp_ajax_cs_get_package_overview', array(__CLASS__, 'handle_get_package_overview'));
	}
    
	// New: Handle stats update checking
	public static function handle_check_stats_update() {
		check_ajax_referer('cs-ajax-nonce', 'nonce');
		
		$user_id = get_current_user_id();
		
		// Check if there have been any recent changes
		global $wpdb;
		
		// Get the timestamp of the last sale/order change for this user and their children
		$user_ids = array($user_id);
		
		// Include children if this is a parent user
		if (CS_Child_Manager::can_manage_children($user_id)) {
			$children = CS_Child_Manager::get_parent_children($user_id);
			foreach ($children as $child) {
				$user_ids[] = $child['id'];
			}
		}
		
		$user_ids_str = implode(',', array_map('intval', $user_ids));
		
		// Check for recent changes (within the last 30 seconds)
		$recent_changes = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}cs_sales 
			WHERE user_id IN ($user_ids_str) 
			AND (created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND) OR sale_date > DATE_SUB(CURDATE(), INTERVAL 1 DAY))"
		);
		
		// Check if we have a stored "last_updated" timestamp for this user
		$last_updated = get_user_meta($user_id, 'cs_stats_last_updated', true);
		$current_time = time();
		
		// Consider update needed if:
		// 1. No last updated timestamp exists
		// 2. There are recent changes
		// 3. More than 60 seconds have passed since last update
		$needs_update = empty($last_updated) || 
						$recent_changes > 0 || 
						($current_time - $last_updated) > 60;
		
		wp_send_json_success(array(
			'needs_update' => $needs_update,
			'recent_changes' => intval($recent_changes),
			'last_updated' => $last_updated,
			'current_time' => $current_time
		));
	}
	
	// Add this new handler function
public static function handle_get_order_details() {
    check_ajax_referer('cs-ajax-nonce', 'nonce');
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
        return;
    }
    
    global $wpdb;
    
    // Get the order with email field included
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, u.display_name AS seller_name, u.user_login AS seller_username 
        FROM {$wpdb->prefix}cs_sales s
        LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
        WHERE s.id = %d",
        $order_id
    ), ARRAY_A);

    if (!$order) {
        wp_send_json_error('Order not found');
        return;
    }

    // Add email to the order data if available
    $order['customer_email'] = $order['email'] ?? '';

    // Check if the current user has permission to view this order
    $current_user_id = get_current_user_id();
    $parent_children = CS_Child_Manager::get_parent_children($current_user_id);
    $child_parent = CS_Child_Manager::get_child_parent($order['user_id']);

    // Allow viewing if:
    // 1. The order belongs to the current user, OR
    // 2. The current user is the parent of the user who made the order
    if ($order['user_id'] != $current_user_id) {
        $can_view = false;
        
        // Check if current user is a parent of the order's user
        if ($child_parent && $child_parent['id'] == $current_user_id) {
            $can_view = true;
        }
        
        // Check if current user can manage children
        if (!$can_view && CS_Child_Manager::can_manage_children()) {
            $can_view = true;
        }
        
        if (!$can_view) {
            wp_send_json_error('You do not have permission to view this order');
            return;
        }
    }

    // Attempt to parse products, with multiple fallback methods
    $products = array();
    if (!empty($order['products'])) {
        // Try parsing as JSON
        $parsed_products = json_decode($order['products'], true);
        
        if (is_array($parsed_products)) {
            $products = $parsed_products;
        } else {
            // If JSON parsing fails, log the original products string for debugging
            error_log('Failed to parse products JSON: ' . $order['products']);
        }
    }

    // Add products to the order data
    $order['products'] = $products;

    // Determine if the seller is a child user
    $is_child_user = false;
    $user = get_userdata($order['user_id']);
    if ($user && in_array('club_child_user', $user->roles)) {
        $is_child_user = true;
    }

    // Add seller information to the order data
    $order['is_child_seller'] = $is_child_user;
    $order['seller_display_name'] = $order['seller_name'] ?: $order['seller_username'];

    wp_send_json_success($order);
}
	
	public static function handle_recalculate_total_profit() {
        // Check nonce for security
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        // Ensure only admins can recalculate
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to recalculate profit.');
            return;
        }
        
        // Recalculate total profit
        $total_profit = CS_Sales::refresh_total_profit();
        
        // Send success response
        wp_send_json_success(array(
            'total_profit' => $total_profit,
            'formatted_profit' => number_format($total_profit, 2) . ' SEK'
        ));
    }
public static function handle_product_search() {
    check_ajax_referer('cs-ajax-nonce', 'nonce');
    
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    
    if (is_array($category)) {
        $category = array_map('sanitize_text_field', $category);
    } else {
        $category = sanitize_text_field($category);
    }
    
    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $is_child = in_array('club_child_user', $current_user->roles);
    
    error_log('🔍 Product Search - User ID: ' . $user_id . ', Is Child: ' . ($is_child ? 'YES' : 'NO'));
    
    try {
        if ($is_child) {
            // Child users: get only assigned products
            error_log('👶 Child user - fetching assigned products for user ID: ' . $user_id);
            $assigned_products = CS_Child_Manager::get_child_products($user_id);
            
            error_log('👶 CS_Child_Manager returned ' . count($assigned_products) . ' products');
            error_log('👶 Assigned products data: ' . print_r($assigned_products, true));
            
            // Convert to same format as search results
            $results = array();
            foreach ($assigned_products as $product_data) {
                // Note: get_child_products returns 'product_id', not 'id'
                $product_id = isset($product_data['product_id']) ? $product_data['product_id'] : $product_data['id'];
                error_log('👶 Processing product ID: ' . $product_id);
                
                $product = wc_get_product($product_id);
                if ($product) {
                    // Get product image
                    $image_id = $product->get_image_id();
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : wc_placeholder_img_src();
                    
                    // Get RRP from ACF or regular price
                    $rrp = function_exists('get_field') ? get_field('rrp', $product_id) : $product->get_regular_price();
                    if (empty($rrp)) {
                        $rrp = $product->get_regular_price();
                    }
                    
                    // Get supplier/store name
                    $supplier = function_exists('get_field') ? get_field('butik', $product_id) : '';
                    if (empty($supplier)) {
                        $supplier = get_post_meta($product_id, '_store_name', true);
                    }
                    
                    $results[] = array(
                        'id' => $product_id,
                        'name' => $product_data['name'] ?? $product->get_name(),
                        'sku' => $product_data['sku'] ?? $product->get_sku() ?? 'N/A',
                        'price' => $product_data['base_price'] ?? $product->get_regular_price(),
                        'rrp' => $rrp,
                        'total_price' => $product_data['total_price'] ?? $product->get_regular_price(),
                        'image' => $product_data['image'] ?? $image_url,
                        'permalink' => get_permalink($product_id),
                        'vendor_id' => 0,
                        'vendor_name' => $supplier ?: 'N/A',
                        'store_name' => $supplier ?: 'N/A',
                        'can_be_combined' => false // Default for child products
                    );
                    error_log('👶 Added product to results: ' . $product->get_name());
                } else {
                    error_log('❌ WC Product not found for ID: ' . $product_id);
                }
            }
            
            error_log('✅ Returning ' . count($results) . ' assigned products for child user');
        } else {
            // Parent users: search all products
            $results = CS_Sales::search_products($search_term, $category);
            error_log('✅ Returning ' . count($results) . ' products for parent user');
        }
        
        $modified_results = array_map(function($product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'sku' => $product['sku'] ?? 'N/A',
                'price' => $product['price'] ?? 0,
                'rrp' => $product['rrp'] ?? 0,
                'total_price' => $product['total_price'] ?? CS_Price_Calculator::get_club_sales_price($product['id']),
                'image' => $product['image'] ?? '',
                'permalink' => $product['permalink'] ?? '',
                'vendor_id' => $product['vendor_id'] ?? 0,
                'vendor_name' => $product['vendor_name'] ?? 'N/A',
                'store_name' => $product['store_name'] ?? 'N/A',
                'can_be_combined' => $product['can_be_combined'] ?? false
            ];
        }, $results);
        
        wp_send_json_success([
            'count' => count($modified_results),
            'term' => $search_term,
            'data' => $modified_results,
            'products' => $modified_results // Add 'products' key for consistency
        ]);
    } catch (Exception $e) {
        error_log('❌ Product search error: ' . $e->getMessage());
        wp_send_json_error('Search failed: ' . $e->getMessage());
    }
}
    public static function handle_update_order() {
    check_ajax_referer('cs-ajax-nonce', 'nonce');
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
        return;
    }
    
    // Validate date
    $sale_date = isset($_POST['sale_date']) ? sanitize_text_field($_POST['sale_date']) : '';
    if (!empty($sale_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sale_date)) {
        wp_send_json_error('Invalid date format');
        return;
    }
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $allowed_statuses = ['pending', 'ordered_from_supplier', 'completed'];
    if (!empty($status) && !in_array($status, $allowed_statuses)) {
        wp_send_json_error('Invalid status');
        return;
    }
    // Include email in update data
    $update_data = array(
        'customer_name' => sanitize_text_field($_POST['customer_name']),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone']),
        'address' => sanitize_textarea_field($_POST['address']),
        'sale_date' => $sale_date,
        'notes' => sanitize_textarea_field($_POST['notes'])
    );
    if (!empty($status)) {
        $update_data['status'] = $status;
    }
    
    global $wpdb;
    
    // Verify the current user has permission to edit this order
    $current_user_id = get_current_user_id();
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cs_sales WHERE id = %d",
        $order_id
    ));
    
    // Permission check
    $can_edit = false;
    
    // User can edit their own orders
    if ($order->user_id == $current_user_id) {
        $can_edit = true;
    }
    
    // Check if current user is a parent of the order's user
    $child_parent = CS_Child_Manager::get_child_parent($order->user_id);
    if ($child_parent && $child_parent['id'] == $current_user_id) {
        $can_edit = true;
    }
    
    // Check if user can manage children
    if (!$can_edit && CS_Child_Manager::can_manage_children()) {
        $can_edit = true;
    }
    
    // Admins can always edit
    if (!$can_edit && current_user_can('manage_options')) {
        $can_edit = true;
    }
    
    if (!$can_edit) {
        wp_send_json_error('You do not have permission to edit this order');
        return;
    }
    
     error_log('Order Update Data: ' . print_r($update_data, true));
    
    // Perform the update
    $result = $wpdb->update(
        $wpdb->prefix . 'cs_sales', 
        $update_data, 
        array('id' => $order_id),
        array('%s', '%s', '%s', '%s', '%s', '%s'),
        array('%d')
    );
    
    // Add more robust error logging
    if ($result === false) {
        error_log('Order Update Failed: ' . $wpdb->last_error);
        wp_send_json_error('Failed to update order: ' . $wpdb->last_error);
        return;
    }
    
    // Update the stats last updated timestamp
    update_user_meta($current_user_id, 'cs_stats_last_updated', time());
    
    // Retrieve the updated order for response
    $updated_order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cs_sales WHERE id = %d",
        $order_id
    ), ARRAY_A);
    
    wp_send_json_success(array(
        'message' => 'Order updated successfully',
        'order' => $updated_order,
        'stats_updated' => true
    ));
}
	
	
public static function handle_add_sale() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');

        $customer_name = sanitize_text_field($_POST['customer_name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $address = sanitize_textarea_field($_POST['address']);
        $total_amount = floatval($_POST['total_amount']);
        $sale_date = sanitize_text_field($_POST['sale_date']);
        $notes = sanitize_textarea_field($_POST['notes']);
        $products_json = stripslashes($_POST['products']);
        $products = json_decode($products_json, true);

        // ✅ FIX: Check for customer_pays FIRST, then calculate if needed
        if (isset($_POST['customer_pays']) && floatval($_POST['customer_pays']) > 0) {
            $customer_pays = floatval($_POST['customer_pays']);
        } else {
            // Fallback: Calculate customer_pays from products RRP
            $customer_pays = 0;
            if (is_array($products)) {
                foreach ($products as $product) {
                    $quantity = isset($product['quantity']) ? intval($product['quantity']) : 1;
                    
                    // ✅ Use RRP if available (sent from JavaScript)
                    if (isset($product['rrp']) && floatval($product['rrp']) > 0) {
                        $rrp_price = floatval($product['rrp']);
                    } else {
                        // Fallback: Get RRP from product ACF field
                        $product_id = isset($product['id']) ? intval($product['id']) : 0;
                        $rrp_price = 0;
                        
                        if ($product_id > 0) {
                            if (function_exists('get_field')) {
                                $rrp_price = floatval(get_field('rrp', $product_id));
                            }
                            
                            // If still no RRP, use base price * 2 as fallback
                            if (empty($rrp_price)) {
                                $wc_product = wc_get_product($product_id);
                                if ($wc_product) {
                                    $base_price = floatval($wc_product->get_regular_price());
                                    $rrp_price = $base_price * 2;
                                }
                            }
                        }
                    }
                    
                    $customer_pays += ($rrp_price * $quantity);
                }
            }
        }

        global $wpdb;
        $user_id = get_current_user_id();

        // TEMPORARY DEBUG LOGGING
        error_log('==========================================');
        error_log('🔍 ADD SALE - Received from Frontend:');
        error_log('  POST[total_amount]: ' . $_POST['total_amount']);
        error_log('  POST[customer_pays]: ' . $_POST['customer_pays']);
        error_log('🔍 Variables before INSERT:');
        error_log('  $total_amount: ' . $total_amount);
        error_log('  $customer_pays: ' . $customer_pays);
        error_log('  Products JSON: ' . substr($products_json, 0, 200) . '...');
        error_log('==========================================');

        $result = $wpdb->insert(
            $wpdb->prefix . 'cs_sales',
            array(
                'user_id' => $user_id,
                'customer_name' => $customer_name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'products' => $products_json,
                'sale_amount' => $total_amount,
                'customer_pays' => $customer_pays,
                'sale_date' => $sale_date,
                'notes' => $notes,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            $order_id = $wpdb->insert_id;
            
            // TEMPORARY DEBUG LOGGING
            error_log('✅ Order inserted - ID: ' . $order_id);
            error_log('📊 Verifying what was saved to database...');
            
            // Get the order details for email
            $order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cs_sales WHERE id = %d",
                $order_id
            ), ARRAY_A);
            
            // TEMPORARY DEBUG LOGGING
            error_log('📊 Order from database:');
            error_log('  sale_amount (NI betalar): ' . $order['sale_amount']);
            error_log('  customer_pays (Kunden betalar): ' . $order['customer_pays']);
            error_log('  Profit: ' . ($order['customer_pays'] - $order['sale_amount']));
            error_log('==========================================');
            
            // Trigger email sending
            if ($order) {
                do_action('cs_order_details_viewed', $order);
            }
            
            // Update user's stats last updated timestamp
            update_user_meta($user_id, 'cs_stats_last_updated', time());
            
            wp_send_json_success(array(
                'message' => 'Sale added successfully!',
                'order_id' => $order_id,
                'customer_pays' => $customer_pays,
                'profit' => $customer_pays - $total_amount
            ));
        } else {
            error_log('Database error in handle_add_sale: ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }

    public static function handle_delete_sale() {
    // Check nonce for security
    check_ajax_referer('cs-ajax-nonce', 'nonce');
    
    $sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
    
    if (!$sale_id) {
        wp_send_json_error('Invalid sale ID');
        return;
    }
    
    global $wpdb;
    
    // First, verify the current user has permission to delete this sale
    $sale = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id, deleted_at FROM {$wpdb->prefix}cs_sales WHERE id = %d",
        $sale_id
    ));
    
    // Check if sale exists
    if (!$sale) {
        wp_send_json_error('Sale not found');
        return;
    }
    
    // Check if already deleted
    if ($sale->deleted_at) {
        wp_send_json_error('Sale is already deleted');
        return;
    }
    
    // Permission check (same as before)
    $current_user_id = get_current_user_id();
    $can_delete = false;
    
    if ($sale->user_id == $current_user_id) {
        $can_delete = true;
    }
    
    if (!$can_delete && CS_Child_Manager::can_manage_children()) {
        $children = CS_Child_Manager::get_parent_children($current_user_id);
        $child_ids = wp_list_pluck($children, 'id');
        
        if (in_array($sale->user_id, $child_ids)) {
            $can_delete = true;
        }
    }
    
    if (!$can_delete && current_user_can('manage_options')) {
        $can_delete = true;
    }
    
    if (!$can_delete) {
        wp_send_json_error('You do not have permission to delete this sale');
        return;
    }
    
    // Soft delete - set deleted_at timestamp
    $result = $wpdb->update(
        $wpdb->prefix . 'cs_sales',
        array('deleted_at' => current_time('mysql')),
        array('id' => $sale_id),
        array('%s'),
        array('%d')
    );
    
    if ($result !== false) {
        update_user_meta($current_user_id, 'cs_stats_last_updated', time());
        
        wp_send_json_success(array(
            'message' => 'Sale deleted successfully',
            'sale_id' => $sale_id,
            'stats_updated' => true
        ));
    } else {
        wp_send_json_error('Could not delete sale');
    }
}

public static function handle_restore_sale() {
    check_ajax_referer('cs-ajax-nonce', 'nonce');
    
    $sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
    
    if (!$sale_id) {
        wp_send_json_error('Invalid sale ID');
        return;
    }
    
    global $wpdb;
    
    // Get sale details
    $sale = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id, deleted_at FROM {$wpdb->prefix}cs_sales WHERE id = %d",
        $sale_id
    ));
    
    if (!$sale || !$sale->deleted_at) {
        wp_send_json_error('Sale not found or not deleted');
        return;
    }
    
    // Permission check (same logic as delete)
    $current_user_id = get_current_user_id();
    $can_restore = false;
    
    if ($sale->user_id == $current_user_id) {
        $can_restore = true;
    }
    
    if (!$can_restore && CS_Child_Manager::can_manage_children()) {
        $children = CS_Child_Manager::get_parent_children($current_user_id);
        $child_ids = wp_list_pluck($children, 'id');
        
        if (in_array($sale->user_id, $child_ids)) {
            $can_restore = true;
        }
    }
    
    if (!$can_restore && current_user_can('manage_options')) {
        $can_restore = true;
    }
    
    if (!$can_restore) {
        wp_send_json_error('You do not have permission to restore this sale');
        return;
    }
    
    // Restore - clear deleted_at timestamp
    $result = $wpdb->update(
        $wpdb->prefix . 'cs_sales',
        array('deleted_at' => null),
        array('id' => $sale_id),
        array('%s'),
        array('%d')
    );
    
    if ($result !== false) {
        update_user_meta($current_user_id, 'cs_stats_last_updated', time());
        
        wp_send_json_success(array(
            'message' => 'Sale restored successfully',
            'sale_id' => $sale_id,
            'stats_updated' => true
        ));
    } else {
        wp_send_json_error('Could not restore sale');
    }
}

public static function handle_permanently_delete_sale() {
    check_ajax_referer('cs-ajax-nonce', 'nonce');
    
    $sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
    
    if (!$sale_id) {
        wp_send_json_error('Invalid sale ID');
        return;
    }
    
    global $wpdb;
    
    // Get sale details
    $sale = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id, deleted_at FROM {$wpdb->prefix}cs_sales WHERE id = %d",
        $sale_id
    ));
    
    if (!$sale) {
        wp_send_json_error('Sale not found');
        return;
    }
    
    // Only allow permanent deletion of already soft-deleted items
    if (!$sale->deleted_at) {
        wp_send_json_error('Sale must be deleted before it can be permanently deleted');
        return;
    }
    
    // Permission check (same logic as delete)
    $current_user_id = get_current_user_id();
    $can_delete = false;
    
    if ($sale->user_id == $current_user_id) {
        $can_delete = true;
    }
    
    if (!$can_delete && CS_Child_Manager::can_manage_children()) {
        $children = CS_Child_Manager::get_parent_children($current_user_id);
        $child_ids = wp_list_pluck($children, 'id');
        
        if (in_array($sale->user_id, $child_ids)) {
            $can_delete = true;
        }
    }
    
    if (!$can_delete && current_user_can('manage_options')) {
        $can_delete = true;
    }
    
    if (!$can_delete) {
        wp_send_json_error('You do not have permission to permanently delete this sale');
        return;
    }
    
    // Permanently delete the record
    $result = $wpdb->delete(
        $wpdb->prefix . 'cs_sales',
        array('id' => $sale_id),
        array('%d')
    );
    
    if ($result !== false) {
        update_user_meta($current_user_id, 'cs_stats_last_updated', time());
        
        wp_send_json_success(array(
            'message' => 'Sale permanently deleted',
            'sale_id' => $sale_id,
            'stats_updated' => true
        ));
    } else {
        wp_send_json_error('Could not permanently delete sale');
    }
}

    public static function handle_get_stats() {
    check_ajax_referer('cs-ajax-nonce', 'nonce');
    
    $user_id = get_current_user_id();
    
    // Get updated stats with real profit calculation
    $stats = CS_Sales::get_user_stats($user_id);
    
    // Update the last updated timestamp
    update_user_meta($user_id, 'cs_stats_last_updated', time());
    
    // Add debug information
    $stats['debug'] = array(
        'user_id' => $user_id,
        'calculation_method' => 'total_price - rrp',
        'includes_children' => CS_Child_Manager::can_manage_children($user_id),
        'last_updated' => time()
    );
    
    wp_send_json_success($stats);
}
    
public static function handle_klarna_checkout() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        // If no sale IDs are provided, fetch all pending sales
        if (!isset($_POST['sale_ids']) || empty($_POST['sale_ids'])) {
            global $wpdb;
            $pending_sales = $wpdb->get_col(
                "SELECT id FROM {$wpdb->prefix}cs_sales WHERE status = 'pending'"
            );
            
            if (empty($pending_sales)) {
                wp_send_json_error('No pending sales found');
                return;
            }
            
            $sale_ids = $pending_sales;
        } else {
            $sale_ids = array_map('intval', (array) $_POST['sale_ids']);
        }
        
        // Log for debugging
        error_log("Processing checkout for sale IDs: " . implode(', ', $sale_ids));
        
        try {
            // Create WooCommerce order
            $checkout_url = CS_Klarna::create_order($sale_ids);
            
            // Enhanced logging
            error_log("WooCommerce checkout URL created: " . $checkout_url);
            
            // Validate the checkout URL
            if (empty($checkout_url)) {
                error_log("Empty checkout URL received");
                wp_send_json_error('Empty checkout URL received');
                return;
            }
            
            // Check if it's a valid URL
            if (!filter_var($checkout_url, FILTER_VALIDATE_URL)) {
                error_log("Invalid checkout URL format: " . $checkout_url);
                wp_send_json_error('Invalid checkout URL format: ' . $checkout_url);
                return;
            }
            
            // IMPORTANT: Orders remain as 'pending' - no status change until WooCommerce completion
            error_log("Orders remain 'pending' status until checkout completion");
            
            // Update stats timestamp for current user
            $current_user_id = get_current_user_id();
            update_user_meta($current_user_id, 'cs_stats_last_updated', time());
            
            // Enhanced response
            $response_data = array(
                'redirect_url' => $checkout_url,
                'processed_count' => count($sale_ids),
                'stats_updated' => true,
                'sale_ids' => $sale_ids,
                'timestamp' => time(),
                'checkout_type' => 'woocommerce',
                'status_info' => 'Orders remain pending until checkout completion'
            );
            
            error_log("Sending success response: " . json_encode($response_data));
            
            wp_send_json_success($response_data);
            
        } catch (Exception $e) {
            error_log("Checkout error: " . $e->getMessage());
            wp_send_json_error('Checkout error: ' . $e->getMessage());
        }
    }

    // Remove the reset order status handler since we no longer need it
    // Orders will only be 'pending' or 'completed' - no 'processing' status to reset from
    
    // You can remove this entire function or keep it disabled
    public static function handle_reset_order_status() {
        wp_send_json_error('Reset functionality is not needed. Orders remain pending until completed.');
    }
public static function handle_get_sales() {
    check_ajax_referer('cs-ajax-nonce', 'nonce');

    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;
    $user_filter = isset($_POST['user_filter']) ? sanitize_text_field($_POST['user_filter']) : null;
    $show_deleted = isset($_POST['show_deleted']) ? sanitize_text_field($_POST['show_deleted']) : 'no';
    $current_user_id = get_current_user_id();

    global $wpdb;

    // Base query depending on whether we're showing deleted or active orders
    if ($show_deleted === 'yes') {
        // Show only deleted orders
        $sql = "SELECT s.* FROM {$wpdb->prefix}cs_sales s WHERE s.deleted_at IS NOT NULL";
    } else {
        // Show only active (non-deleted) orders
        $sql = "SELECT s.* FROM {$wpdb->prefix}cs_sales s WHERE s.deleted_at IS NULL";
    }
    
    $params = array();

    // Apply status filter only for active orders (deleted orders don't need status filtering)
    if ($status && $show_deleted !== 'yes') {
        $sql .= " AND s.status = %s";
        $params[] = $status;
    }

    // Apply user filter logic
    if ($user_filter === 'my') {
        $sql .= " AND s.user_id = %d";
        $params[] = $current_user_id;
    } else if ($user_filter === 'children') {
        $children = CS_Child_Manager::get_parent_children($current_user_id);
        if (!empty($children)) {
            $child_ids = array_column($children, 'id');
            $placeholders = implode(',', array_fill(0, count($child_ids), '%d'));
            $sql .= " AND s.user_id IN ($placeholders)";
            $params = array_merge($params, $child_ids);
        } else {
            wp_send_json_success(array('sales' => array()));
            return;
        }
    } else {
        // Default: show user's orders and their children's orders
        if (CS_Child_Manager::is_child_user()) {
            $sql .= " AND s.user_id = %d";
            $params[] = $current_user_id;
        } else {
            $children = CS_Child_Manager::get_parent_children($current_user_id);
            $user_ids = array($current_user_id);
            if (!empty($children)) {
                foreach ($children as $child) {
                    $user_ids[] = $child['id'];
                }
            }
            $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
            $sql .= " AND s.user_id IN ($placeholders)";
            $params = array_merge($params, $user_ids);
        }
    }

    // Order by date, newest first
    $sql .= " ORDER BY s.created_at DESC, s.sale_date DESC";
    
    if (!empty($params)) {
        $query = $wpdb->prepare($sql, $params);
    } else {
        $query = $sql;
    }
    
    $sales = $wpdb->get_results($query);

    // Add user information and use stored values for each sale
    if (!empty($sales)) {
        foreach ($sales as &$sale) {
            $user = get_userdata($sale->user_id);
            $sale->user_name = $user ? $user->display_name : 'Unknown';
            $sale->is_child = $user ? in_array('club_child_user', $user->roles) : false;
            $sale->is_deleted = !is_null($sale->deleted_at);
            
            // ALWAYS fetch CURRENT swish number (not historical stored value)
            // Get team information for child users
            $sale->team_name = '';
            $current_swish = '';
            
            if ($sale->is_child) {
                $team_id = get_user_meta($sale->user_id, 'assigned_team', true);
                error_log("🔍 Order #{$sale->id}: Child user {$sale->user_id}, assigned_team ID: " . ($team_id ? $team_id : 'NONE'));
                
                if ($team_id) {
                    $team = $wpdb->get_row($wpdb->prepare(
                        "SELECT name, swish_number FROM {$wpdb->prefix}cs_teams WHERE id = %d",
                        $team_id
                    ));
                    if ($team) {
                        $sale->team_name = $team->name;
                        $current_swish = $team->swish_number ? trim($team->swish_number) : '';
                        error_log("📱 Order #{$sale->id}: Child user - Team '{$team->name}' (ID: {$team_id}) swish: " . ($current_swish ? $current_swish : 'EMPTY'));
                    } else {
                        error_log("⚠️ Order #{$sale->id}: Team ID {$team_id} not found in database!");
                    }
                } else {
                    error_log("⚠️ Order #{$sale->id}: Child user has NO assigned_team in user_meta!");
                }
            }
            
            // If no swish from team, get current personal swish from user meta
            if (empty($current_swish)) {
                $current_swish = get_user_meta($sale->user_id, 'swish_number', true);
                $current_swish = $current_swish ? trim($current_swish) : '';
                error_log("📱 Order #{$sale->id}: User {$sale->user_id} - using personal swish: " . ($current_swish ? $current_swish : 'EMPTY'));
            }
            
            // Override stored swish with current swish number
            $sale->swish_number = $current_swish;
            error_log("✅ Order #{$sale->id}: FINAL swish_number being sent to frontend: '" . ($sale->swish_number ? $sale->swish_number : 'EMPTY/NULL') . "'");

            // Use stored values if present, fallback to calculation only if missing
            $sale->customer_pays = isset($sale->customer_pays) && $sale->customer_pays > 0 ? floatval($sale->customer_pays) : 0;
            $sale->base_amount = isset($sale->sale_amount) && $sale->sale_amount > 0 ? floatval($sale->sale_amount) : 0;

            // If missing, recalculate from products
            if ($sale->customer_pays == 0 || $sale->base_amount == 0) {
                $products_json = $sale->products;
                if (strpos($products_json, '\"') !== false) {
                    $products_json = stripslashes($products_json);
                }
                $products = json_decode($products_json, true);
                if (is_array($products)) {
                    foreach ($products as $product) {
                        $product_id = isset($product['id']) ? intval($product['id']) : 0;
                        $quantity = isset($product['quantity']) ? intval($product['quantity']) : 1;
                        if ($product_id > 0) {
                            $wc_product = wc_get_product($product_id);
                            if ($wc_product) {
                                $base_price = floatval($wc_product->get_regular_price());
                                $sale->base_amount += ($base_price * $quantity);
                                
                                // Get RRP for customer payment (not club_sales_price!)
                                $rrp = 0;
                                if (function_exists('get_field')) {
                                    $rrp = floatval(get_field('rrp', $product_id));
                                }
                                // If RRP not set, calculate as base_price * 2
                                if (empty($rrp) && !empty($base_price)) {
                                    $rrp = $base_price * 2;
                                }
                                $sale->customer_pays += ($rrp * $quantity);
                            }
                        }
                    }
                }
            }
            $sale->profit = $sale->customer_pays - $sale->base_amount;
        }
    }

    wp_send_json_success(array('sales' => $sales));
}

public static function handle_mark_order_delivered() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }
        
        global $wpdb;
        
        // First, verify the order exists and get its details
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cs_sales WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            wp_send_json_error('Order not found');
            return;
        }
        
        // Check if the current user has permission to mark this order as delivered
        $current_user_id = get_current_user_id();
        $can_mark_delivered = false;
        
        // User can mark their own orders as delivered
        if ($order->user_id == $current_user_id) {
            $can_mark_delivered = true;
        }
        
        // Check if current user is a parent of the order's user
        $child_parent = CS_Child_Manager::get_child_parent($order->user_id);
        if ($child_parent && $child_parent['id'] == $current_user_id) {
            $can_mark_delivered = true;
        }
        
        // Check if user can manage children
        if (!$can_mark_delivered && CS_Child_Manager::can_manage_children()) {
            $can_mark_delivered = true;
        }
        
        // Admins can always mark orders as delivered
        if (!$can_mark_delivered && current_user_can('manage_options')) {
            $can_mark_delivered = true;
        }
        
        if (!$can_mark_delivered) {
            wp_send_json_error('You do not have permission to mark this order as delivered');
            return;
        }
        
        // Check if order is in the correct status to be marked as delivered
        if ($order->status !== 'ordered_from_supplier') {
            wp_send_json_error('Order must be "Ordered from Supplier" status to mark as delivered');
            return;
        }
        
        // Update the order status to completed
        $result = $wpdb->update(
            $wpdb->prefix . 'cs_sales',
            array('status' => 'completed'),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update order status: ' . $wpdb->last_error);
            return;
        }
        
        // Log the status change
        error_log("Order ID {$order_id} marked as delivered (completed) by user ID {$current_user_id}");
        
        // Update the stats last updated timestamp
        update_user_meta($current_user_id, 'cs_stats_last_updated', time());
        
        // Get the updated order for response
        $updated_order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cs_sales WHERE id = %d",
            $order_id
        ), ARRAY_A);
        
        wp_send_json_success(array(
            'message' => 'Order marked as delivered to customer successfully',
            'order' => $updated_order,
            'stats_updated' => true,
            'old_status' => $order->status,
            'new_status' => 'completed'
        ));
    }

    public static function handle_complete_order() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }
        
        global $wpdb;
        
        // Get the order
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cs_sales WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            wp_send_json_error('Order not found');
            return;
        }
        
        // Check permissions
        $current_user_id = get_current_user_id();
        $can_complete = false;
        
        // User can complete their own orders
        if ($order->user_id == $current_user_id) {
            $can_complete = true;
        }
        
        // Check if current user is a parent of the order's user
        $child_parent = CS_Child_Manager::get_child_parent($order->user_id);
        if ($child_parent && $child_parent['id'] == $current_user_id) {
            $can_complete = true;
        }
        
        // Check if user can manage children
        if (!$can_complete && CS_Child_Manager::can_manage_children()) {
            $can_complete = true;
        }
        
        // Admins can always complete orders
        if (!$can_complete && current_user_can('manage_options')) {
            $can_complete = true;
        }
        
        if (!$can_complete) {
            wp_send_json_error('You do not have permission to complete this order');
            return;
        }
        
        // Check if order is in ordered_from_supplier status
        if ($order->status !== 'ordered_from_supplier') {
            wp_send_json_error('Order must be in "Ordered from Supplier" status to be completed');
            return;
        }
        
        // Update the order status to completed
        $result = $wpdb->update(
            $wpdb->prefix . 'cs_sales',
            array('status' => 'completed'),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update order status: ' . $wpdb->last_error);
            return;
        }
        
        // Log the status change
        error_log("Order ID {$order_id} completed by user ID {$current_user_id}");
        
        // Update the stats last updated timestamp
        update_user_meta($current_user_id, 'cs_stats_last_updated', time());
        
        wp_send_json_success(array(
            'message' => 'Order marked as completed successfully',
            'order_id' => $order_id,
            'new_status' => 'completed'
        ));
    }

/**
     * Get product categories for filter
     */
    public static function get_product_categories() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        // Get WooCommerce product categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (is_wp_error($categories)) {
            wp_send_json_error('Failed to fetch categories');
            return;
        }
        
        // Get kafeteria category to exclude it and its children
        $kafeteria_term = get_term_by('slug', 'kafeteria', 'product_cat');
        $exclude_ids = array();
        
        if ($kafeteria_term) {
            $exclude_ids[] = $kafeteria_term->term_id;
            
            // Get all child categories of kafeteria
            $kafeteria_children = get_term_children($kafeteria_term->term_id, 'product_cat');
            if (!is_wp_error($kafeteria_children) && !empty($kafeteria_children)) {
                $exclude_ids = array_merge($exclude_ids, $kafeteria_children);
            }
        }
        
        $category_data = array();
        foreach ($categories as $category) {
            // Skip kafeteria and its children
            if (in_array($category->term_id, $exclude_ids)) {
                continue;
            }
            
            $category_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count
            );
        }
        
        wp_send_json_success($category_data);
    }
    
    // Handle password change
    public static function handle_change_password() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Användaren är inte inloggad', 'club-sales')));
            return;
        }
        
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        
        if (empty($current_password) || empty($new_password)) {
            wp_send_json_error(array('message' => __('Alla fält är obligatoriska', 'club-sales')));
            return;
        }
        
        // Verify current password
        $user = get_user_by('id', $user_id);
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            wp_send_json_error(array('message' => __('Nuvarande lösenord är felaktigt', 'club-sales')));
            return;
        }
        
        // Validate new password length
        if (strlen($new_password) < 8) {
            wp_send_json_error(array('message' => __('Lösenordet måste vara minst 8 tecken långt', 'club-sales')));
            return;
        }
        
        // Update password
        wp_set_password($new_password, $user_id);
        
        wp_send_json_success(array('message' => __('Lösenordet har ändrats', 'club-sales')));
    }
    
    // Handle profile update
    public static function handle_update_profile() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Användaren är inte inloggad', 'club-sales')));
            return;
        }
        
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        
        // Validate email
        if (!empty($email) && !is_email($email)) {
            wp_send_json_error(array('message' => __('Ogiltig e-postadress', 'club-sales')));
            return;
        }
        
        // Check if email is already in use by another user
        if (!empty($email)) {
            $email_exists = email_exists($email);
            if ($email_exists && $email_exists != $user_id) {
                wp_send_json_error(array('message' => __('E-postadressen används redan av en annan användare', 'club-sales')));
                return;
            }
        }
        
        // Update user data
        $user_data = array(
            'ID' => $user_id,
        );
        
        if (!empty($email)) {
            $user_data['user_email'] = $email;
        }
        
        $result = wp_update_user($user_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'billing_phone', $phone);
        
        wp_send_json_success(array('message' => __('Profilen har uppdaterats', 'club-sales')));
    }
    
    // Handle organization update
    public static function handle_update_organization() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Användaren är inte inloggad', 'club-sales')));
            return;
        }
        
        $organization_name = isset($_POST['organization_name']) ? sanitize_text_field($_POST['organization_name']) : '';
        $organization_number = isset($_POST['organization_number']) ? sanitize_text_field($_POST['organization_number']) : '';
        $swish_number = isset($_POST['swish_number']) ? sanitize_text_field($_POST['swish_number']) : '';
        
        // Log the update
        error_log("🔄 User $user_id updating swish number to: $swish_number");
        
        // Update organization meta
        update_user_meta($user_id, 'organization_name', $organization_name);
        update_user_meta($user_id, 'organization_number', $organization_number);
        update_user_meta($user_id, 'swish_number', $swish_number);
        
        // Verify it was saved
        $saved_swish = get_user_meta($user_id, 'swish_number', true);
        error_log("✅ Swish number saved in database as: $saved_swish");
        
        // Update stats timestamp to trigger refresh
        update_user_meta($user_id, 'cs_stats_last_updated', time());
        
        wp_send_json_success(array(
            'message' => __('Organisationsinformationen har uppdaterats', 'club-sales'),
            'stats_updated' => true,
            'swish_updated' => true,
            'new_swish' => $saved_swish
        ));
    }
    
    // Sales Material Handlers
    
    /**
     * Save selected products to database
     */
    public static function handle_save_selected_products() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Användaren är inte inloggad', 'club-sales')));
            return;
        }
        
        // Get product IDs from request
        $product_ids_json = isset($_POST['product_ids']) ? $_POST['product_ids'] : '[]';
        $product_ids = json_decode(stripslashes($product_ids_json), true);
        
        if (!is_array($product_ids)) {
            $product_ids = array();
        }
        
        // Save to user meta
        error_log('💾 [handle_save_selected_products] Saving product_ids: ' . print_r($product_ids, true) . ' for user ' . $user_id);
        update_user_meta($user_id, 'cs_selected_products', $product_ids);
        $saved = get_user_meta($user_id, 'cs_selected_products', true);
        error_log('💾 [handle_save_selected_products] Saved value in cs_selected_products: ' . print_r($saved, true));
        error_log('💾 Saved ' . count($product_ids) . ' products to database for user ' . $user_id);
        
        wp_send_json_success(array(
            'message' => __('Produkter sparade', 'club-sales'),
            'count' => count($product_ids),
            'products' => $product_ids
        ));
    }
    
    /**
     * Get selected products from database
     */
    public static function handle_get_selected_products() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Användaren är inte inloggad', 'club-sales')));
            return;
        }
        
        error_log('📦 Sales Material - User ID: ' . $user_id);
        
        $products = array();
        
        // Check if user is a child user
        $current_user = wp_get_current_user();
        $is_child = in_array('club_child_user', $current_user->roles);
        
        if ($is_child) {
            // Child users see their assigned products
            error_log('📦 Child user detected, fetching assigned products');
            $assigned_products = CS_Child_Manager::get_child_products($user_id);
            $selected_products = array();
            foreach ($assigned_products as $product_data) {
                $product_id = isset($product_data['product_id']) ? $product_data['product_id'] : $product_data['id'];
                $selected_products[] = $product_id;
            }
            error_log('📦 Child user has ' . count($selected_products) . ' assigned products');
        } else {
            // Parent users see their selected products
            $selected_products = get_user_meta($user_id, 'cs_selected_products', true);
            error_log('📦 [handle_get_selected_products] Raw cs_selected_products: ' . print_r($selected_products, true));
            // Always ensure $selected_products is an array
            if (!is_array($selected_products)) {
                $maybe_array = json_decode($selected_products, true);
                error_log('📦 [handle_get_selected_products] Decoded as array: ' . print_r($maybe_array, true));
                if (is_array($maybe_array)) {
                    $selected_products = $maybe_array;
                } else {
                    $selected_products = array();
                }
            }
        }
        
        error_log('📦 Sales Material - Found ' . count($selected_products) . ' products');
        foreach ($selected_products as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $product_data = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'image' => wp_get_attachment_url($product->get_image_id())
                );
                // Add ACF fields for sales materials if available
                if (function_exists('get_field')) {
                    $sales_pitches = get_field('sales_pitches', $product_id);
                    $product_image = get_field('product_image', $product_id);
                    $social_media_content = get_field('social_media_content', $product_id);
                    // Handle both array and direct ID returns from ACF
                    if ($sales_pitches) {
                        if (is_array($sales_pitches) && isset($sales_pitches['url'])) {
                            $product_data['sales_pitches'] = $sales_pitches['url'];
                        } elseif (is_numeric($sales_pitches)) {
                            $product_data['sales_pitches'] = wp_get_attachment_url($sales_pitches);
                        } else {
                            $product_data['sales_pitches'] = $sales_pitches;
                        }
                    }
                    
                    if ($product_image) {
                        if (is_array($product_image) && isset($product_image['url'])) {
                            $product_data['product_image'] = $product_image['url'];
                        } elseif (is_numeric($product_image)) {
                            $product_data['product_image'] = wp_get_attachment_url($product_image);
                        } else {
                            $product_data['product_image'] = $product_image;
                        }
                    }
                    
                    if ($social_media_content) {
                        if (is_array($social_media_content) && isset($social_media_content['url'])) {
                            $product_data['social_media_content'] = $social_media_content['url'];
                        } elseif (is_numeric($social_media_content)) {
                            $product_data['social_media_content'] = wp_get_attachment_url($social_media_content);
                        } else {
                            $product_data['social_media_content'] = $social_media_content;
                        }
                    }
                    
                    error_log('📦 Product ' . $product_id . ' materials: ' . json_encode(array(
                        'sales_pitches' => isset($product_data['sales_pitches']) ? 'YES' : 'NO',
                        'product_image' => isset($product_data['product_image']) ? 'YES' : 'NO',
                        'social_media_content' => isset($product_data['social_media_content']) ? 'YES' : 'NO'
                    )));
                }
                
                $products[] = $product_data;
            }
        }
        
        error_log('📦 Sales Material - Returning ' . count($products) . ' products');
        
        wp_send_json_success(array('products' => $products));
    }
    
    public static function handle_get_preview_material() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Användaren är inte inloggad', 'club-sales')));
            return;
        }
        
        $material_type = isset($_POST['material_type']) ? sanitize_text_field($_POST['material_type']) : 'products';
        
        // Generate preview data based on material type
        $preview_data = array();
        
        if ($material_type === 'products') {
            // Get product material preview
            $selected_products = get_user_meta($user_id, 'cs_selected_products', true);
            $product_count = is_array($selected_products) ? count($selected_products) : 0;
            
            $preview_data = array(
                'title' => __('Valda produkter', 'club-sales'),
                'description' => sprintf(__('Detta material innehåller information om %d valda produkter för er försäljning.', 'club-sales'), $product_count),
                'type' => __('Produktmaterial', 'club-sales'),
                'format' => 'PDF',
                'size' => '2.4 MB',
                'status' => __('Tillgänglig', 'club-sales'),
                'preview_image' => '' // No preview image for now
            );
        } else if ($material_type === 'campaign') {
            // Get campaign images preview
            $preview_data = array(
                'title' => __('Kampanjbilder', 'club-sales'),
                'description' => __('Professionella bilder för sociala medier och marknadsföring av er kampanj.', 'club-sales'),
                'type' => __('Kampanjmaterial', 'club-sales'),
                'format' => 'ZIP (JPG/PNG)',
                'size' => '5.8 MB',
                'status' => __('Tillgänglig', 'club-sales'),
                'preview_image' => '' // No preview image for now
            );
        }
        
        wp_send_json_success($preview_data);
    }
    
    public static function handle_download_material() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Användaren är inte inloggad', 'club-sales')));
            return;
        }
        
        $material_type = isset($_POST['material_type']) ? sanitize_text_field($_POST['material_type']) : 'products';
        
        // Generate PDF or material file
        if ($material_type === 'products') {
            // Use only cs_selected_products for the current user
            $selected_products = get_user_meta($user_id, 'cs_selected_products', true);
            if (!is_array($selected_products)) {
                $maybe_array = json_decode($selected_products, true);
                if (is_array($maybe_array)) {
                    $selected_products = $maybe_array;
                } else {
                    $selected_products = array();
                }
            }
            if (empty($selected_products)) {
                wp_send_json_error(array('message' => __('Inga produkter valda', 'club-sales')));
                return;
            }
            // Generate PDF with product information
            // For now, return a placeholder URL
            $download_url = self::generate_product_pdf($user_id, $selected_products);
            wp_send_json_success(array(
                'download_url' => $download_url,
                'filename' => 'produktmaterial-' . date('Y-m-d') . '.pdf'
            ));
        } else if ($material_type === 'campaign') {
            // Get campaign images
            $campaign_images = get_user_meta($user_id, 'cs_campaign_images', true);
            
            if (empty($campaign_images)) {
                wp_send_json_error(array('message' => __('Inga kampanjbilder tillgängliga', 'club-sales')));
                return;
            }
            
            // Create ZIP file with images
            $download_url = self::generate_campaign_zip($user_id, $campaign_images);
            
            wp_send_json_success(array(
                'download_url' => $download_url,
                'filename' => 'kampanjbilder-' . date('Y-m-d') . '.zip'
            ));
        }
        
        wp_send_json_error(array('message' => __('Kunde inte generera material', 'club-sales')));
    }
    
    // Helper function to generate product PDF
    private static function generate_product_pdf($user_id, $product_ids) {
        // This is a placeholder - implement actual PDF generation
        // You might want to use a library like TCPDF or mPDF
        
        // For now, return a sample PDF URL
        return plugins_url('assets/sample-product-material.pdf', dirname(__FILE__));
    }
    
    // Helper function to generate campaign images ZIP
    private static function generate_campaign_zip($user_id, $images) {
        // This is a placeholder - implement actual ZIP generation
        
        // For now, return a sample ZIP URL
        return plugins_url('assets/sample-campaign-images.zip', dirname(__FILE__));
    }
    
    /**
     * Handle Get Kafeteria Products
     */
    public static function handle_get_kafeteria_products() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $category_id = isset($_POST['category_id']) ? sanitize_text_field($_POST['category_id']) : 'all';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'popularity';
        
        // Set up query args
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        // Add category filter - only show products from Kafeteria parent category
        $tax_query = array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => 'kafeteria',
                'operator' => 'IN',
                'include_children' => true,
            )
        );
        
        // If specific category selected, add it to the query
        if ($category_id !== 'all') {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => intval($category_id),
            );
        }
        
        $args['tax_query'] = $tax_query;
        
        // Add search query
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // Add sorting
        switch ($sort) {
            case 'price-asc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order'] = 'ASC';
                break;
            case 'price-desc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_price';
                $args['order'] = 'DESC';
                break;
            case 'name-asc':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            default:
                $args['orderby'] = 'menu_order title';
                $args['order'] = 'ASC';
        }
        
        $query = new WP_Query($args);
        $products = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                
                if (!$product) {
                    continue;
                }
                
                // Get product data
                $image_id = $product->get_image_id();
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : wc_placeholder_img_src();
                
                // Get RRP from ACF or meta
                $rrp = function_exists('get_field') ? get_field('rrp', $product->get_id()) : get_post_meta($product->get_id(), 'rrp', true);
                if (empty($rrp)) {
                    $rrp = $product->get_regular_price();
                }
                
                // Get supplier/store name
                $supplier = function_exists('get_field') ? get_field('butik', $product->get_id()) : '';
                if (empty($supplier)) {
                    $supplier = get_post_meta($product->get_id(), '_store_name', true);
                }
                
                // Get product categories
                $terms = get_the_terms($product->get_id(), 'product_cat');
                $categories = array();
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        if ($term->slug !== 'kafeteria') {
                            $categories[] = array(
                                'id' => $term->term_id,
                                'name' => $term->name,
                                'slug' => $term->slug,
                            );
                        }
                    }
                }
                
                $products[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'sku' => $product->get_sku(),
                    'price' => floatval($product->get_price()),
                    'rrp' => floatval($rrp),
                    'image' => $image_url,
                    'supplier' => $supplier,
                    'categories' => $categories,
                    'rating' => floatval($product->get_average_rating()),
                    'rating_count' => intval($product->get_rating_count()),
                    'description' => wp_strip_all_tags($product->get_short_description()),
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'products' => $products,
            'total' => count($products),
        ));
    }
    
    /**
     * Handle Get Kafeteria Categories
     */
    public static function handle_get_kafeteria_categories() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        // Get the Kafeteria parent category
        $parent_term = get_term_by('slug', 'kafeteria', 'product_cat');
        
        if (!$parent_term) {
            wp_send_json_error(array('message' => __('Kafeteria kategori hittades inte', 'club-sales')));
            return;
        }
        
        // Get child categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'parent' => $parent_term->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
        
        if (is_wp_error($categories)) {
            wp_send_json_error(array('message' => __('Kunde inte hämta kategorier', 'club-sales')));
            return;
        }
        
        $category_data = array();
        
        // Define category icons mapping
        $category_icons = array(
            'kaffe-te' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>',
            'bakverk-brod' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 1 0 0-20z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
            'drycker' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 2v6a3 3 0 1 0 6 0V2"></path><path d="M12 8v13"></path><path d="M8 21h8"></path></svg>',
            'forbrukningsmaterial' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>',
            'frukt-gront' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0 1 9 14l-9 6-9-6a10 10 0 0 1 9-14z"></path></svg>',
            'glass-dessert' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8"></path></svg>',
            'matvaror' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>',
            'snacks-godis' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"></circle><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83"></path></svg>',
        );
        
        foreach ($categories as $category) {
            $icon = isset($category_icons[$category->slug]) ? $category_icons[$category->slug] : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>';
            
            $category_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'icon' => $icon,
            );
        }
        
        wp_send_json_success(array(
            'categories' => $category_data,
        ));
    }
    
    /**
     * Diagnostic: Check what swish number is saved for current user
     */
    public static function handle_check_swish_number() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Användaren är inte inloggad', 'club-sales')));
            return;
        }
        
        $swish_number = get_user_meta($user_id, 'swish_number', true);
        $org_name = get_user_meta($user_id, 'organization_name', true);
        $org_number = get_user_meta($user_id, 'organization_number', true);
        
        wp_send_json_success(array(
            'user_id' => $user_id,
            'swish_number' => $swish_number,
            'organization_name' => $org_name,
            'organization_number' => $org_number,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Handle Kafeteria Checkout - Add cart items to WooCommerce cart and redirect
     */
    public static function handle_kafeteria_checkout() {
        check_ajax_referer('cs-ajax-nonce', 'nonce');
        
        $cart_items = isset($_POST['cart_items']) ? json_decode(stripslashes($_POST['cart_items']), true) : array();
        
        if (empty($cart_items)) {
            wp_send_json_error(array('message' => __('Varukorgen är tom', 'club-sales')));
            return;
        }
        
        // Make sure WooCommerce is available
        if (!function_exists('WC')) {
            wp_send_json_error(array('message' => __('WooCommerce är inte tillgängligt', 'club-sales')));
            return;
        }
        
        // Clear WooCommerce cart first
        WC()->cart->empty_cart();
        
        // Add each item to WooCommerce cart
        $added_count = 0;
        foreach ($cart_items as $item) {
            $product_id = isset($item['id']) ? intval($item['id']) : 0;
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
            
            if ($product_id > 0 && $quantity > 0) {
                // Verify product exists and is purchasable
                $product = wc_get_product($product_id);
                if ($product && $product->is_purchasable()) {
                    WC()->cart->add_to_cart($product_id, $quantity);
                    $added_count++;
                }
            }
        }
        
        if ($added_count === 0) {
            wp_send_json_error(array('message' => __('Kunde inte lägga till produkter i varukorgen', 'club-sales')));
            return;
        }
        
        // Return checkout URL
        $checkout_url = wc_get_checkout_url();
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d produkter har lagts till i varukorgen', 'club-sales'), $added_count),
            'checkout_url' => $checkout_url,
            'items_added' => $added_count
        ));
    }
    
    /**
     * Get package overview for pending orders
     */
    public static function handle_get_package_overview() {
        try {
            check_ajax_referer('cs-ajax-nonce', 'nonce');
            
            $user_id = get_current_user_id();
            
            // Check if ACF is available
            if (!function_exists('get_field')) {
                wp_send_json_error(array('message' => 'ACF not available'));
                return;
            }
            
            // Get user's children IDs
            $children = CS_Child_Manager::get_parent_children($user_id);
            $children_ids = array();
            foreach ($children as $child) {
                $children_ids[] = $child['id'];
            }
            $all_user_ids = array_merge(array($user_id), $children_ids);
            
            // Get pending orders
            global $wpdb;
            $table_name = $wpdb->prefix . 'cs_sales';
            
            // Build query safely without spread operator
            $user_ids_escaped = array_map('intval', $all_user_ids);
            $user_ids_string = implode(',', $user_ids_escaped);
            
            $query = "SELECT * FROM $table_name WHERE user_id IN ($user_ids_string) AND status = 'pending' AND is_deleted = 0";
            $orders = $wpdb->get_results($query);
            
            // Aggregate products across all pending orders
            $product_totals = array();
            
            foreach ($orders as $order) {
                $order_data = maybe_unserialize($order->order_data);
                
                if (is_array($order_data) && !empty($order_data['products'])) {
                    foreach ($order_data['products'] as $product) {
                        if (!isset($product['product_id']) || !isset($product['quantity'])) {
                            continue;
                        }
                        
                        $product_id = $product['product_id'];
                        $quantity = $product['quantity'];
                        
                        if (!isset($product_totals[$product_id])) {
                            $product_totals[$product_id] = 0;
                        }
                        
                        $product_totals[$product_id] += $quantity;
                    }
                }
            }
            
            // Filter products that have full_box_only = true and calculate packaging
            $packages = array();
            
            foreach ($product_totals as $product_id => $total_quantity) {
                $full_box_only = get_field('full_box_only', $product_id);
                $box_size = get_field('box_size', $product_id);
                
                // Only include products with full_box_only enabled and valid box_size
                if ($full_box_only && !empty($box_size) && $box_size > 0) {
                    $product = wc_get_product($product_id);
                    
                    if ($product) {
                        $boxes_needed = ceil($total_quantity / $box_size);
                        $total_packaging = $boxes_needed * $box_size;
                        $extra = $total_packaging - $total_quantity;
                        
                        $packages[] = array(
                            'product_id' => $product_id,
                            'product_name' => $product->get_name(),
                            'sold' => $total_quantity,
                            'box_size' => $box_size,
                            'boxes_needed' => $boxes_needed,
                            'packaging' => $total_packaging,
                            'extra' => $extra
                        );
                    }
                }
            }
            
            wp_send_json_success(array(
                'packages' => $packages,
                'total_products' => count($packages)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error: ' . $e->getMessage()
            ));
        }
    }
}