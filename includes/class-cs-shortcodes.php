<?php
function cs_debug_check_child_products() {
    global $wpdb;
    $child_id = isset($_GET['debug_child_id']) ? intval($_GET['debug_child_id']) : 0;
    
    if (!$child_id) {
        return "No child ID specified. Add ?debug_child_id=XX to the URL.";
    }
    
    // Check if the cs_child_products table exists
    $table_name = $wpdb->prefix . 'cs_child_products';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    
    if (!$table_exists) {
        return "ERROR: Table {$table_name} does not exist!";
    }
    
    // Clear any query cache
    $wpdb->flush();
    $timestamp = time(); // Force cache busting
    
    // Get the records directly from the database
    $records = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cs_child_products WHERE child_id = %d /* cache bust: {$timestamp} */",
        $child_id
    ));
    
    // Return the results as HTML
    $output = "<div style='background:#fff;padding:20px;margin:20px;border:1px solid #ddd;'>";
    $output .= "<h3>Database Debug: Child Products for Child ID: {$child_id}</h3>";
    $output .= "<p>SQL: " . $wpdb->last_query . "</p>";
    $output .= "<p>Found " . count($records) . " records</p>";
    
    if (count($records) > 0) {
        $output .= "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        $output .= "<tr><th>ID</th><th>Child ID</th><th>Product ID</th><th>Assigned By</th><th>Assigned At</th></tr>";
        
        foreach ($records as $record) {
            $output .= "<tr>";
            $output .= "<td>{$record->id}</td>";
            $output .= "<td>{$record->child_id}</td>";
            $output .= "<td>{$record->product_id}</td>";
            $output .= "<td>{$record->assigned_by}</td>";
            $output .= "<td>{$record->created_at}</td>";
            $output .= "</tr>";
        }
        
        $output .= "</table>";
    } else {
        $output .= "<p>No records found in the database.</p>";
    }
    
    // Show product information if available
    if (count($records) > 0 && function_exists('wc_get_product')) {
        $output .= "<h4>Product Information:</h4>";
        $output .= "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        $output .= "<tr><th>Product ID</th><th>Name</th><th>SKU</th><th>Price</th></tr>";
        
        foreach ($records as $record) {
            $product = wc_get_product($record->product_id);
            if ($product) {
                $output .= "<tr>";
                $output .= "<td>{$record->product_id}</td>";
                $output .= "<td>" . esc_html($product->get_name()) . "</td>";
                $output .= "<td>" . esc_html($product->get_sku()) . "</td>";
                $output .= "<td>" . wc_price($product->get_price()) . "</td>";
                $output .= "</tr>";
            } else {
                $output .= "<tr><td>{$record->product_id}</td><td colspan='3'>Product not found in WooCommerce</td></tr>";
            }
        }
        
        $output .= "</table>";
    }
    
    // Show all records in the table (limited to 20)
    $all_records = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY id DESC LIMIT 20");
    $output .= "<h4>Recent Records in Table (Last 20):</h4>";
    $output .= "<p>Found " . count($all_records) . " total records</p>";
    
    if (count($all_records) > 0) {
        $output .= "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        $output .= "<tr><th>ID</th><th>Child ID</th><th>Product ID</th><th>Assigned By</th><th>Assigned At</th></tr>";
        
        foreach ($all_records as $record) {
            $highlight = ($record->child_id == $child_id) ? "background-color:#e6f7e6;" : "";
            $output .= "<tr style='{$highlight}'>";
            $output .= "<td>{$record->id}</td>";
            $output .= "<td>{$record->child_id}</td>";
            $output .= "<td>{$record->product_id}</td>";
            $output .= "<td>{$record->assigned_by}</td>";
            $output .= "<td>{$record->created_at}</td>";
            $output .= "</tr>";
        }
        
        $output .= "</table>";
    } else {
        $output .= "<p>No records found in the entire table.</p>";
    }
    
    // Show the most recent errors
    $errors = $wpdb->last_error ? $wpdb->last_error : "No errors reported by wpdb.";
    $output .= "<h4>Recent Database Errors:</h4>";
    $output .= "<pre>{$errors}</pre>";
    
    $output .= "</div>";
    
    return $output;
}

// Add a shortcode for easy debugging
add_shortcode('cs_debug_child_products', 'cs_debug_check_child_products');

// Or display it in admin using an action
add_action('admin_notices', function() {
    if (isset($_GET['debug_child_products']) && current_user_can('manage_options')) {
        echo cs_debug_check_child_products();
    }
});


class CS_Shortcodes {
    public static function init() {
        add_shortcode('club_sales_dashboard', array(__CLASS__, 'dashboard'));
		add_shortcode('club_sales_price', array(__CLASS__, 'cs_customer_price_display_shortcode'));
    }
    
    public static function products_tab_content() {
        include CS_PLUGIN_DIR . 'templates/tabs/products.php';
    }
    
    public static function sales_material_tab_content() {
        include CS_PLUGIN_DIR . 'templates/tabs/sales-material.php';
    }
    public static function add_order_tab_content() {
        include CS_PLUGIN_DIR . 'templates/tabs/add-order.php';
    }
    
    public static function orders_tab_content() {
        include CS_PLUGIN_DIR . 'templates/tabs/orders.php';
    }
    
    public static function stats_tab_content() {
        include CS_PLUGIN_DIR . 'templates/tabs/settings.php';
    }
    
    public static function dashboard() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access this feature.', 'club-sales') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $stats = CS_Sales::get_user_stats($user_id);
        
        wp_enqueue_script('cs-scripts');
        wp_enqueue_style('cs-styles');
        wp_enqueue_script('cs-child-scripts');
        wp_enqueue_style('cs-child-styles');
        
        // Define the default tabs
        $tabs = array(
            'assign-product' => array(  // Changed from 'products'
                'icon' => 'dashicons-store',
                'title' => __('Assign Product', 'club-sales'),  // Changed from 'Products'
                'content_callback' => array(__CLASS__, 'products_tab_content')
            ),
            'add-order' => array(
                'icon' => 'dashicons-cart',
                'title' => __('Add Order', 'club-sales'),
                'content_callback' => array(__CLASS__, 'add_order_tab_content')
            ),
            'orders' => array(
                'icon' => 'dashicons-list-view',
                'title' => __('Orders', 'club-sales'),
                'content_callback' => array(__CLASS__, 'orders_tab_content')
            ),
            'kafeteria' => array(
                'icon' => 'dashicons-coffee',
                'title' => __('Kafeteria', 'club-sales'),
                'content_callback' => array(__CLASS__, 'kafeteria_tab_content')
            ),
            'stats' => array(
                'icon' => 'dashicons-chart-bar',
                'title' => __('Settings', 'club-sales'),
                'content_callback' => array(__CLASS__, 'stats_tab_content')
            )
        );
        
        // Allow plugins to modify the tabs
        $tabs = apply_filters('cs_dashboard_tabs', $tabs);
        
        ob_start();
        
        // Include the dashboard template with tabs passed as a parameter
        include CS_PLUGIN_DIR . 'templates/dashboard_dynamic.php';
        
        return ob_get_clean();
    }public static function cs_customer_price_display_shortcode() {
    // Get the current product
    global $product;
    
    if (!$product) {
        $product = wc_get_product(get_the_ID());
        
        if (!$product) {
            return '<p>Product not found</p>';
        }
    }
    
    // Get RRP using ACF get_field()
    $rrp = function_exists('get_field') ? get_field('rrp', $product->get_id()) : null;
    
    // If no RRP found, fallback to regular price
    if (empty($rrp)) {
        $rrp = $product->get_regular_price();
    }
    
    // Get base price for calculation (regular price)
    $base_price = $product->get_regular_price();
    
    // Get margin rate - use global setting
    $margin_rate = floatval(get_option('club_sales_global_margin', 12));
    
    // Try to get custom VAT rate
    $vat_rate = null;
    
    // First, try to get VAT rate from product meta
    $vat_rate = get_post_meta($product->get_id(), '_cs_vat_rate', true);
    
    // If no custom VAT rate from meta, try ACF
    if (empty($vat_rate) && function_exists('get_field')) {
        $vat_rate = get_field('vat', $product->get_id());
    }
    
    // If still no VAT rate, use default
    if (empty($vat_rate)) {
        $vat_rate = 25.0; // Swedish standard VAT rate
    }
    
    // Calculate customer pays price
    $margin_amount = $base_price * ($margin_rate / 100);
    $price_with_margin = $base_price + $margin_amount;
    $vat_amount = $price_with_margin * ($vat_rate / 100);
    $total_price = $price_with_margin + $vat_amount;
    
    // Always apply Swedish rounding
    $rounded_total_price = CS_Price_Calculator::round_price_to_nearest_nine($total_price);
    
    // Format prices
    $formatted_rrp = wc_price($rrp);
    $formatted_total_price = wc_price($rounded_total_price);
    $currency = get_option('cs_settings')['currency'] ?? 'SEK';
    
    // Build HTML output with enhanced styling
    $output = '<div class="cs-price-display-container">';
    
    $output .= '<div class="cs-price-grid">';
    
    // RRP Section
    $output .= '<div class="cs-rrp-section">';
    $output .= '<div class="cs-price-label">RRP</div>';
    $output .= '<div class="cs-price-value cs-rrp-value">' . $formatted_rrp . '</div>';
    $output .= '</div>';
    
    // Customer Pays Section
    $output .= '<div class="cs-customer-section">';
    $output .= '<div class="cs-price-label">' . __( 'Customer Pays', 'club_sales' ) . '</div>';
    $output .= '<div class="cs-price-value cs-customer-value">' . $formatted_total_price . '</div>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    // Add custom styles
    $output .= '<style>
    .cs-price-display-container {
        max-width: 100%;
        margin: 20px 0;
        font-family: "Helvetica Neue", Arial, sans-serif;
    }
    
    .cs-price-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .cs-rrp-section,
    .cs-customer-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 10px;
        border-radius: 6px;
    }
    
    .cs-rrp-section {
        background-color: #f0f0f0;
    }
    
    .cs-customer-section {
        background-color: #e6f3e6;
    }
    
    .cs-price-label {
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .cs-price-value {
        font-size: 24px;
        font-weight: 700;
    }
    
    .cs-rrp-value {
        color: #888;
    }
    
    .cs-customer-value {
        color: #4CAF50;
    }
    </style>';
    
    $output .= '</div>'; // Close container
    
    return $output;
}

    /**
     * Kafeteria Tab Content
     */
    public static function kafeteria_tab_content() {
        include CS_PLUGIN_DIR . 'templates/tabs/kafeteria.php';
    }
	
}