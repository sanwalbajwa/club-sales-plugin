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
		add_action('wp_ajax_cs_restore_sale', array(__CLASS__, 'handle_restore_sale'));
        add_action('wp_ajax_cs_permanently_delete_sale', array(__CLASS__, 'handle_permanently_delete_sale'));
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
    // You might want to add a specific way to capture customer email during order creation
    $order['customer_email'] = $order['email'] ?? ''; // Assuming 'email' column exists in cs_sales table

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
    // Add error reporting
    error_log('Product Search AJAX Called');
    
    // Ensure nonce verification is early
    $nonce_verified = check_ajax_referer('cs-ajax-nonce', 'nonce', false);
    
    if (!$nonce_verified) {
        error_log('Nonce verification failed');
        wp_send_json_error('Nonce verification failed');
        return;
    }
    
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    error_log('Search Term: ' . $search_term);
    
    try {
        // Use a faster method to search products if possible
        $results = CS_Sales::search_products($search_term);
        error_log('Products Found: ' . count($results));
        
        // Prune unnecessary data to reduce payload BUT KEEP VENDOR INFO
        $modified_results = array_map(function($product) {
            // Only include essential data
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'sku' => $product['sku'] ?? 'N/A',
                'total_price' => CS_Price_Calculator::get_club_sales_price($product['id']),
                'image' => $product['image'] ?? '',
                'permalink' => $product['permalink'] ?? '',
                // ADD VENDOR INFO HERE
                'vendor_id' => $product['vendor_id'] ?? 0,
                'vendor_name' => $product['vendor_name'] ?? 'N/A',
                'store_name' => $product['store_name'] ?? 'N/A'
            ];
        }, $results);
        
        wp_send_json_success([
            'count' => count($modified_results),
            'term' => $search_term,
            'data' => $modified_results
        ]);
    } catch (Exception $e) {
        error_log('Search Failed: ' . $e->getMessage());
        wp_send_json_error('Search failed');
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
        'email' => sanitize_email($_POST['email'] ?? ''), // Add email
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
    try {
        check_ajax_referer('cs-ajax-nonce', 'nonce');

        // Debug: Log what we're receiving
        error_log('=== ADD SALE DEBUG ===');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('Email received: ' . ($_POST['email'] ?? 'NO EMAIL'));

        // Get the raw products JSON string from the POST data
        $products_json_string = isset($_POST['products']) ? wp_unslash($_POST['products']) : '[]';

        // Decode the JSON string into a PHP array to validate it
        $products_array = json_decode($products_json_string, true);

        // Check if the JSON was valid
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid products data format.');
            return;
        }

        // Re-encode the validated PHP array into a clean JSON string
        $clean_products_for_db = wp_json_encode($products_array);
        
        // Get and validate email
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        error_log('Sanitized email: ' . $email);
        
        // Prepare sale data with ALL fields including email
        $sale_data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => $email, // Make sure email is included here
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'sale_amount' => floatval($_POST['total_amount']),
            'sale_date' => sanitize_text_field($_POST['sale_date']),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'products' => $clean_products_for_db
        );
        
        error_log('Sale data being sent to add_sale: ' . print_r($sale_data, true));
        
        // Pass the complete sale data to add_sale
        $result = CS_Sales::add_sale($sale_data);
        
        if ($result) {
            // Update the stats last updated timestamp
            $current_user_id = get_current_user_id();
            update_user_meta($current_user_id, 'cs_stats_last_updated', time());
            
            wp_send_json_success(array(
                'message' => 'Sale added successfully',
                'sale_id' => $result,
                'stats_updated' => true
            ));
        } else {
            global $wpdb;
            error_log('Database error: ' . $wpdb->last_error);
            wp_send_json_error('Could not add sale. Database error: ' . $wpdb->last_error);
        }
    } catch (Exception $e) {
        error_log('Exception in handle_add_sale: ' . $e->getMessage());
        wp_send_json_error('Error: ' . $e->getMessage());
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

    // Add user information and calculate profits for each sale
    if (!empty($sales)) {
        foreach ($sales as &$sale) {
            $user = get_userdata($sale->user_id);
            $sale->user_name = $user ? $user->display_name : 'Unknown';
            $sale->is_child = $user ? in_array('club_child_user', $user->roles) : false;
            
            // Mark if the sale is deleted
            $sale->is_deleted = !is_null($sale->deleted_at);
            
            // Calculate customer_pays and profit
            if (isset($sale->customer_pays) && $sale->customer_pays > 0) {
                $customer_pays = floatval($sale->customer_pays);
            } else {
                // Fallback calculation if customer_pays not stored
                $customer_pays = 0;
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
                            $rrp = 0;
                            if (function_exists('get_field')) {
                                $rrp = floatval(get_field('rrp', $product_id));
                            }
                            if ($rrp == 0) {
                                $wc_product = wc_get_product($product_id);
                                if ($wc_product) {
                                    $rrp = floatval($wc_product->get_regular_price());
                                }
                            }
                            $customer_pays += ($rrp * $quantity);
                        }
                    }
                }
                
                // Update the record with calculated customer_pays
                if ($customer_pays > 0 && !$sale->is_deleted) {
                    $wpdb->update(
                        $wpdb->prefix . 'cs_sales',
                        array('customer_pays' => $customer_pays),
                        array('id' => $sale->id),
                        array('%f'),
                        array('%d')
                    );
                }
            }
            
            $sale->customer_pays = $customer_pays;
            $sale->profit = $customer_pays - floatval($sale->sale_amount);
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
}
