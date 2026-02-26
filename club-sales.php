<?php
/*
Plugin Name: Club Sales 11
Plugin URI: https://yourwebsite.com/club-sales
Description: A comprehensive sales tracking system for clubs and schools with Klarna integration and child user management.
Version: 2.0.0
Author: Aftab
Author URI: https://yourwebsite.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: club-sales
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}
 
// Define plugin constants
define('CS_VERSION', '2.0.0');
define('CS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Plugin activation hook
register_activation_hook(__FILE__, 'cs_activate_plugin');

function cs_check_dependencies() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Club Sales requires WooCommerce to be installed and activated.', 'club-sales'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    
    return true;
}

function cs_activate_plugin() {
    // Create necessary database tables
    cs_create_tables();
    
    // Run database upgrade to add missing columns
    cs_upgrade_database();
    
    // Add default options
    add_option('cs_settings', array(
        'currency' => 'SEK',
        'date_format' => 'Y-m-d',
        'klarna_merchant_id' => '',
        'klarna_shared_secret' => '',
        'klarna_test_mode' => 'yes'
    ));

    // Create custom role for child users
    add_role(
        'club_child_user',
        'Club Child User',
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => false,
        )
    );
    
    // Add default options for child users
    add_option('cs_child_user_settings', array(
        'managing_roles' => array('administrator'),
        'allow_child_registration' => 'no',
        'delete_users_on_removal' => 'no',
        'default_child_products' => array()
    ));

    // Clear permalinks
    flush_rewrite_rules();
}

// Function to upgrade database schema
function cs_upgrade_database() {
    global $wpdb;
    
    // Check if customer_pays column exists
    $column = $wpdb->get_results($wpdb->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = %s 
        AND TABLE_NAME = %s 
        AND COLUMN_NAME = 'customer_pays'",
        DB_NAME,
        $wpdb->prefix . 'cs_sales'
    ));
    
    if (empty($column)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}cs_sales ADD COLUMN customer_pays decimal(10,2) DEFAULT 0.00 AFTER sale_amount");
    }
    
    // Check if profit column exists
    $column = $wpdb->get_results($wpdb->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = %s 
        AND TABLE_NAME = %s 
        AND COLUMN_NAME = 'profit'",
        DB_NAME,
        $wpdb->prefix . 'cs_sales'
    ));
    
    if (empty($column)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}cs_sales ADD COLUMN profit decimal(10,2) DEFAULT 0.00 AFTER customer_pays");
    }
    
    // Check if deleted_at column exists
    $column = $wpdb->get_results($wpdb->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = %s 
        AND TABLE_NAME = %s 
        AND COLUMN_NAME = 'deleted_at'",
        DB_NAME,
        $wpdb->prefix . 'cs_sales'
    ));
    
    if (empty($column)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}cs_sales ADD COLUMN deleted_at datetime DEFAULT NULL AFTER created_at");
    }
    
    // Fix swapped order values (customer_pays and sale_amount were backwards in old data)
    // Check if we need to run the swap fix by looking for orders with negative profit or customer_pays < sale_amount
    $swapped_check = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}cs_sales 
        WHERE (customer_pays > 0 AND sale_amount > 0 AND customer_pays < sale_amount)
        OR (profit < 0 AND customer_pays > 0 AND sale_amount > 0)"
    );
    
    if ($swapped_check > 0) {
        error_log('🔧 Club Sales: Found ' . $swapped_check . ' orders with swapped values. Fixing automatically...');
        
        // Get all problematic orders
        $swapped_orders = $wpdb->get_results(
            "SELECT id, customer_pays, sale_amount FROM {$wpdb->prefix}cs_sales 
            WHERE (customer_pays > 0 AND sale_amount > 0 AND customer_pays < sale_amount)
            OR (profit < 0 AND customer_pays > 0 AND sale_amount > 0)"
        );
        
        foreach ($swapped_orders as $order) {
            // Swap the values
            $new_customer_pays = floatval($order->sale_amount);
            $new_sale_amount = floatval($order->customer_pays);
            $new_profit = $new_customer_pays - $new_sale_amount;
            
            $wpdb->update(
                $wpdb->prefix . 'cs_sales',
                array(
                    'customer_pays' => $new_customer_pays,
                    'sale_amount' => $new_sale_amount,
                    'profit' => $new_profit
                ),
                array('id' => $order->id),
                array('%f', '%f', '%f'),
                array('%d')
            );
            
            error_log('✅ Fixed Order #' . $order->id . ': Customer pays ' . $new_customer_pays . ' SEK, Club pays ' . $new_sale_amount . ' SEK');
        }
        
        error_log('✅ Club Sales: Fixed ' . count($swapped_orders) . ' swapped orders.');
    }
}

// Create database tables
function cs_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = array();
    
    // Sales table
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cs_sales (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        customer_name varchar(255) NOT NULL,
        phone varchar(50) DEFAULT NULL,
		email varchar(255) DEFAULT NULL,
        address text DEFAULT NULL,
        sale_amount decimal(10,2) NOT NULL,
        sale_date date NOT NULL,
        notes text DEFAULT NULL,
        products text DEFAULT NULL,
        klarna_order_id varchar(255) DEFAULT NULL,
        status varchar(50) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    // Opportunities table
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cs_opportunities (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        status varchar(50) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    // Sales History table for charts
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cs_sales_history (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        month varchar(7) NOT NULL,
        total_sales decimal(10,2) NOT NULL,
        total_opportunities int NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY month_user (month, user_id)
    ) $charset_collate;";
    $sql[] = "ALTER TABLE {$wpdb->prefix}cs_sales 
		ADD COLUMN email varchar(255) DEFAULT NULL 
		AFTER phone;";
    
    // Group table for organizing teams/clubs
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cs_groups (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        leader_user_id bigint(20) DEFAULT NULL,
        description text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Group members table
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cs_group_members (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        group_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        role varchar(50) DEFAULT 'member',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY group_user (group_id, user_id),
        KEY group_id (group_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    // Child-Parent relationship table
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cs_child_parent (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        child_id bigint(20) NOT NULL,
        parent_id bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY child_id (child_id),
        KEY parent_id (parent_id)
    ) $charset_collate;";
    
    // Child-Product assignment table
    $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cs_child_products (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        child_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        assigned_by bigint(20) NOT NULL,
        assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY child_product (child_id, product_id),
        KEY child_id (child_id),
        KEY product_id (product_id)
    ) $charset_collate;";
	
	// In the cs_create_tables function in club-sales.php
    $sql[] = "ALTER TABLE {$wpdb->prefix}cs_child_products 
    ADD COLUMN margin DECIMAL(5,2) DEFAULT NULL,
    ADD COLUMN vat_rate DECIMAL(5,2) DEFAULT NULL,
    ADD COLUMN total_price DECIMAL(10,2) DEFAULT NULL";
	
	$sql[] = "ALTER TABLE {$wpdb->prefix}cs_child_products 
    ADD COLUMN base_price DECIMAL(10,2) DEFAULT NULL,
    ADD COLUMN margin DECIMAL(5,2) DEFAULT NULL,
    ADD COLUMN margin_amount DECIMAL(10,2) DEFAULT NULL,
    ADD COLUMN vat_rate DECIMAL(5,2) DEFAULT NULL,
    ADD COLUMN vat_amount DECIMAL(10,2) DEFAULT NULL,
    ADD COLUMN total_price DECIMAL(10,2) DEFAULT NULL";
	
	// Teams table for team/class management
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cs_teams (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    name varchar(255) NOT NULL,
    swish_number varchar(20) DEFAULT NULL,
    color varchar(7) DEFAULT '#10b981',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id)
	) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach($sql as $query) {
        dbDelta($query);
    }
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'cs_deactivate_plugin');

function cs_deactivate_plugin() {
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('cs_daily_sales_calculation');
    flush_rewrite_rules();
}

// Include required files for the main Club Sales functionality
require_once CS_PLUGIN_DIR . 'includes/class-cs-sales.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-shortcodes.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-ajax.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-klarna.php';

// Include required files for the Child Users functionality
require_once CS_PLUGIN_DIR . 'includes/class-cs-child-manager.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-child-shortcodes.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-child-ajax.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-child-admin.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-auth.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-registration-settings.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-user-admin.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-price-calculator.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-order-confirmation.php';
require_once CS_PLUGIN_DIR . 'includes/class-cs-sales-chart.php';

// Check and upgrade database if needed on every load
add_action('plugins_loaded', 'cs_check_db_upgrade');

function cs_check_db_upgrade() {
    $db_version = get_option('cs_db_version', '1.0');
    
    // Only run upgrade if we haven't done it yet
    if (version_compare($db_version, '2.1', '<')) {
        cs_upgrade_database();
        update_option('cs_db_version', '2.1');
    }
}

CS_Ajax::init();


// Initialize the plugin
class ClubSalesPlugin {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->setup_hooks();
    }
    
    private function setup_hooks() {
        // Init hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Setup daily cron for sales calculations
        if (!wp_next_scheduled('cs_daily_sales_calculation')) {
            wp_schedule_event(time(), 'daily', 'cs_daily_sales_calculation');
        }
        add_action('cs_daily_sales_calculation', array($this, 'calculate_daily_sales'));
        
        // Add filter to modify the dashboard tabs based on user role
        // NOTE: Child user tab filtering is handled in CS_Child_Shortcodes::init() with priority 5
        add_filter('cs_dashboard_tabs', array($this, 'modify_dashboard_tabs'), 10, 1);
        
        // Filter club sales ajax endpoints
        add_filter('cs_product_search_results', array($this, 'filter_child_products'), 10, 2);
		add_action('wp_ajax_refresh_sales_material', array($this, 'handle_refresh_sales_material'));
		add_action('wp_ajax_nopriv_refresh_sales_material', array($this, 'handle_refresh_sales_material'));
    }
    
    public function init() {
        // Initialize shortcodes
        CS_Shortcodes::init();
        // Initialize Ajax
        CS_Ajax::init();
        // Initialize Child-related shortcodes
        CS_Child_Shortcodes::init();
        // Initialize Child-related Ajax
        CS_Child_Ajax::init();
        // Initialize Admin for child users
        CS_Child_Admin::init();
        
        // Load text domain for translations
        load_plugin_textdomain('club-sales', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
	public function enqueue_scripts() {
		// First register and enqueue jQuery
		wp_enqueue_script('jquery');

		// For debugging, add version timestamp to break cache
		$version = time();

		// Register and enqueue styles
		$styles_url = plugin_dir_url(__FILE__) . 'assets/css/cs-styles.css';
		wp_enqueue_style('cs-styles', $styles_url, array(), filemtime(plugin_dir_path(__FILE__) . 'assets/css/cs-styles.css'));
		wp_enqueue_style('dashicons');

        // Register and enqueue scripts
        wp_register_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array('jquery'), '3.7.0', true);
        wp_register_script('qrcode-js', CS_PLUGIN_URL . 'assets/js/qrcode.min.js', array(), $version, true);
        wp_register_script('cs-scripts', CS_PLUGIN_URL . 'assets/js/cs-scripts.js', array('jquery', 'chart-js', 'qrcode-js'), $version, true);

        // Register and enqueue child users scripts
        wp_register_script('cs-child-scripts', CS_PLUGIN_URL . 'assets/js/cs-child-scripts.js', array('jquery', 'cs-scripts'), $version, true);

        wp_enqueue_script('chart-js');
        wp_enqueue_script('qrcode-js');
        wp_enqueue_script('cs-scripts');
        wp_enqueue_script('cs-child-scripts');

		// Localize script for main plugin
		wp_localize_script('cs-scripts', 'csAjax', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('cs-ajax-nonce'),
			'pluginUrl' => CS_PLUGIN_URL,
			'currency' => get_option('cs_settings')['currency'] ?? 'SEK',
			'siteUrl' => get_site_url(),
			'isChildUser' => in_array('club_child_user', wp_get_current_user()->roles) // Add child user flag
		));

		// Localize script for child users
		wp_localize_script('cs-child-scripts', 'csChildAjax', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('cs-child-ajax-nonce')
		));
	}
    
    public function calculate_daily_sales() {
        global $wpdb;
        
        $today = date('Y-m');
        
        // Calculate monthly totals for each user
        $query = $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}cs_sales_history (user_id, month, total_sales, total_opportunities)
            SELECT 
                user_id,
                DATE_FORMAT(sale_date, '%Y-%m') as month,
                SUM(sale_amount) as total_sales,
                COUNT(*) as total_opportunities
            FROM {$wpdb->prefix}cs_sales
            WHERE DATE_FORMAT(sale_date, '%Y-%m') = %s
            GROUP BY user_id
            ON DUPLICATE KEY UPDATE
                total_sales = VALUES(total_sales),
                total_opportunities = VALUES(total_opportunities)",
            $today
        );
        
        $wpdb->query($query);
    }
    
    // Modify dashboard tabs based on user role
    // In the ClubSalesPlugin class
public function modify_dashboard_tabs($tabs) {
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    // IMPORTANT: Child user filtering happens FIRST in CS_Child_Shortcodes (priority 5)
    // This function only handles parent users with managing capabilities
    
    // Check if user can manage child users
    $managing_roles = get_option('cs_child_user_settings', array('managing_roles' => array('administrator')))['managing_roles'];
    $can_manage_children = false;
    
    foreach ($user_roles as $role) {
        if (in_array($role, $managing_roles)) {
            $can_manage_children = true;
            break;
        }
    }
    
    if ($can_manage_children) {
        // Add the manage children tab as the second tab
        $admin_tabs = array();
        $i = 0;
        
        foreach ($tabs as $key => $tab) {
            // Skip the assign-products tab since we're assigning from the main products tab now
            if ($key === 'assign-products') {
                continue;
            }
            
            $admin_tabs[$key] = $tab;
            
            // After the first tab, add the manage children tab
            if ($i === 0) {
                $admin_tabs['manage-children'] = array(
                    'icon' => 'dashicons-groups',
					'title' => __('Manage Children', 'club-sales'),
                    'content_callback' => array('CS_Child_Shortcodes', 'manage_children_tab')
                );
                
                // Add new Sales Material tab
                $admin_tabs['sales-material'] = array(
                    'icon' => 'dashicons-media-document',
                    'title' => __('Sales Material', 'club-sales'),
                    'content_callback' => array('CS_Child_Shortcodes', 'sales_material_tab')
                );
            }
            
            $i++;
        }
        
        return $admin_tabs;
    }
    
    return $tabs;
}
    
    // Filter products for child users
    public function filter_child_products($products, $user_id) {
        // Check if user is a child user
        $user = get_user_by('id', $user_id);
        if (!$user || !in_array('club_child_user', $user->roles)) {
            return $products;
        }
        
        // Get assigned products for this child
        $assigned_products = CS_Child_Manager::get_child_products($user_id);
        
        if (empty($assigned_products)) {
            return array();
        }
        
        // Filter products to only include assigned ones
        $assigned_product_ids = array_column($assigned_products, 'product_id');
        $filtered_products = array_filter($products, function($product) use ($assigned_product_ids) {
            return in_array($product['id'], $assigned_product_ids);
        });
        
        return array_values($filtered_products);
    }
	public function handle_refresh_sales_material() {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cs-child-ajax-nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    // Get product ID and child ID from request
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
    
    // Start output buffering to capture the rendered content
    ob_start();
    
    try {
        // Call the sales material tab function with the specific product
        CS_Child_Shortcodes::sales_material_tab($product_id, $child_id);
        
        // Get the buffered content
        $content = ob_get_clean();
        
        // Send successful response with the rendered content
        wp_send_json_success([
            'content' => $content,
            'product_id' => $product_id,
            'child_id' => $child_id
        ]);
        
    } catch (Exception $e) {
        // Clean the buffer in case of error
        ob_end_clean();
        
        // Log the error
        error_log('Error refreshing sales material: ' . $e->getMessage());
        
        // Send error response
        wp_send_json_error('Failed to refresh sales material: ' . $e->getMessage());
    }
}
	
}

// Modify your plugin initialization
function ClubSales() {
    if (cs_check_dependencies()) {
        return ClubSalesPlugin::get_instance();
    }
    return null;
}

// Start the plugin
add_action('plugins_loaded', 'ClubSales');
function cs_enqueue_geoapify_scripts() {
    // Ensure jQuery is enqueued first
    wp_enqueue_script('jquery');

    // Enqueue Geoapify library
    wp_enqueue_script(
        'geoapify-autocomplete', 
        'https://unpkg.com/@geoapify/geocoder-autocomplete@1/dist/index.min.js', 
        array('jquery'), 
        '1.0.0', 
        true
    );

    // Enqueue Geoapify CSS
    wp_enqueue_style(
        'geoapify-autocomplete-styles', 
        'https://unpkg.com/@geoapify/geocoder-autocomplete@1/styles/minimal.css', 
        array(), 
        '1.0.0'
    );

    // Localize script with configuration
    wp_localize_script('cs-scripts', 'GeoapifyConfig', array(
        'apiKey' => 'b05843f512a34157a2aa6215419b259d',
        'country' => 'SE',
        'limit' => 5,
        'debug' => true // Add debug flag
    ));
}
add_action('wp_enqueue_scripts', 'cs_enqueue_geoapify_scripts', 20); // Increased priority
/**
 * Register AJAX handler for sales material refresh
 */
function cs_register_ajax_handlers() {
    add_action('wp_ajax_refresh_sales_material', 'cs_refresh_sales_material');
}
add_action('init', 'cs_register_ajax_handlers');

function cs_add_calculated_price_to_vendor_dashboard() {
    // Check if we're in the admin area or WCFM dashboard
    if (is_admin() || (function_exists('wcfm_is_vendor') && wcfm_is_vendor())) {
        // Add price display above short description
        add_action('after_wcfm_products_manage_general', 'cs_display_price_above_short_description', 9);
        
        // Add JavaScript to update the price display
        add_action('wp_footer', 'cs_add_vendor_price_update_script');
        add_action('admin_footer', 'cs_add_vendor_price_update_script');
    }
}
add_action('init', 'cs_add_calculated_price_to_vendor_dashboard');

/**
 * Get the current VAT rate for products
 * 
 * This function tries to get the VAT rate from your system configuration
 * 
 * @return float VAT rate as a percentage
 */
function cs_get_current_vat_rate($product_id = null) {
    // First, try to get VAT rate from product meta
    $vat_rate = null;
    
    if ($product_id) {
        // Try to get VAT rate from product meta
        $vat_rate = get_post_meta($product_id, '_cs_vat_rate', true);
        
        // If no VAT rate from meta, try ACF
        if (empty($vat_rate) && function_exists('get_field')) {
            $vat_rate = get_field('vat', $product_id);
        }
    }
    
    // If no product-specific VAT rate, get from plugin settings
    if (empty($vat_rate)) {
        $settings = get_option('cs_settings', []);
        $vat_rate = $settings['vat_rate'] ?? 25.0; // Default to Swedish standard VAT
    }
    return floatval($vat_rate);
}

/**
 * Display calculated price above short description field
 */
function cs_display_price_above_short_description() {
    global $product_id;
    
    // Get the product regular price if product exists
    $regular_price = 0;
    if ($product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $regular_price = floatval($product->get_regular_price());
        }
    }
    
    // Get the correct VAT rate from your system
    $vat_rate = cs_get_current_vat_rate($product_id);
    
    // Get the margin rate from settings
    $margin_rate = floatval(get_option('club_sales_global_margin', 12));
    
    // Calculate margin amount
    $margin_amount = $regular_price * ($margin_rate / 100);
    
    // Price with margin
    $price_with_margin = $regular_price + $margin_amount;
    
    // Calculate VAT amount
    $vat_amount = $price_with_margin * ($vat_rate / 100);
    
    // Calculate total price
    $total_price = $price_with_margin + $vat_amount;
    
    // Always apply Swedish rounding
    $rounded_total_price = CS_Price_Calculator::round_price_to_nearest_nine($total_price);
    
    // Log detailed calculation for debugging
    error_log('Vendor Dashboard Pricing Calculation:');
    error_log('Base Price: ' . $regular_price);
    error_log('Margin Rate: ' . $margin_rate . '%');
    error_log('Margin Amount: ' . $margin_amount);
    error_log('Price with Margin: ' . $price_with_margin);
    error_log('VAT Rate: ' . $vat_rate . '%');
    error_log('VAT Amount: ' . $vat_amount);
    error_log('Total Price: ' . $total_price);
    error_log('Rounded Total Price: ' . $rounded_total_price);
    
    // Output debug information
    $currency = get_woocommerce_currency_symbol();
    
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Find the Short Description field
            const $shortDescField = $('.excerpt_title, .excerpt.wcfm_title, p.excerpt');
            
            if ($shortDescField.length) {
                // Create the price display element - using plain text instead of translation function
                const priceHtml = `
                    <div class="cs-calculated-price-above-desc" id="cs_above_short_desc">
                        <span>Customer pays: </span>
                        <span class="cs-price-value"><?php echo number_format($rounded_total_price, 2) . ' SEK'?></span>
                    </div>
                `;
                
                // Insert before the short description field
                $shortDescField.before(priceHtml);
                
                // Add the required styles
                $('head').append(`
                    <style>
                        .cs-calculated-price-above-desc {
                            margin: 15px 0;
                            padding: 12px 15px;
                            background-color: #000;
                            color: #fff;
                            border-radius: 4px;
                            display: inline-block;
                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                        }
                        
                        .cs-calculated-price-above-desc span {
                            font-weight: 700;
                            font-size: 16px;
                        }
                    </style>
                `);
            }
        });
    </script>
    <?php
}
	
/**
 * Add JavaScript to update the price display when regular price changes
 */
function cs_add_vendor_price_update_script() {
    // Get the global margin rate and VAT rate from your system
    $margin_rate = floatval(get_option('club_sales_global_margin', 12));
    $vat_rate = cs_get_current_vat_rate();
    $currency = get_woocommerce_currency_symbol();
    
    ?>
   <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Calculate price function (matching CS Price Calculator method exactly)
        function calculatePrice(basePrice, customVatRate = null) {
            // Ensure base price is a valid number
            basePrice = parseFloat(basePrice) || 0;
            
            // Get margin and VAT rates
            const margin = <?php echo $margin_rate; ?>;
            const vat = customVatRate !== null ? parseFloat(customVatRate) : <?php echo $vat_rate; ?>;
            
            // Step 1: Calculate margin amount
            const marginAmount = basePrice * (margin / 100);
            
            // Step 2: Price with margin
            const priceWithMargin = basePrice + marginAmount;
            
            // Step 3: Calculate VAT amount ON THE PRICE WITH MARGIN
            const vatAmount = priceWithMargin * (vat / 100);
            
            // Step 4: Calculate total price (price with margin + VAT)
            const totalPrice = priceWithMargin + vatAmount;
            
            // Swedish rounding logic (matches CS_Price_Calculator method)
            function swedishRound(price) {
                // Round up to the nearest whole number
                let rounded = Math.ceil(price);
    
    // Find the last digit
				let lastDigit = rounded % 10;

				// Rounding logic to nearest 9
				if (lastDigit <= 1) {
					// For 0-1: Round to 9 of the CURRENT 10s group
					rounded = Math.floor(rounded / 10) * 10 - 1;
				} else if (lastDigit >= 2 && lastDigit <= 8) {
					// For 2-8: Round to 9 of the CURRENT 10s group
					rounded = Math.floor(rounded / 10) * 10 + 9;
				}
				// For 9: Keep as is

				return rounded;
            }
            
            // Detailed logging for debugging
            console.log('Price Calculation Breakdown:', {
                basePrice: basePrice.toFixed(2),
                marginRate: margin + '%',
                marginAmount: marginAmount.toFixed(2),
                priceWithMargin: priceWithMargin.toFixed(2),
                vatRate: vat + '%',
                vatAmount: vatAmount.toFixed(2),
                totalPrice: totalPrice.toFixed(2)
            });
            
            // Apply Swedish rounding and return
            const roundedPrice = swedishRound(totalPrice);
            
            console.log('Final Rounded Price:', roundedPrice);
            
            return roundedPrice;
        }
        
        // Update the price when regular price changes
        $(document).on('input change keyup', '#regular_price, input[name="regular_price"], #_regular_price', function() {
            const basePrice = $(this).val();
            
            // Get VAT rate from the VAT field if it exists, otherwise use default
            let vatRate = <?php echo $vat_rate; ?>;
            const $vatField = $('#d04a4a2c8b7ac4a76c18da1a927e7aea, .cs-vat-field-container input');
            if ($vatField.length) {
                vatRate = parseFloat($vatField.val()) || vatRate;
            }
            
            // Calculate total price
            const totalPrice = calculatePrice(basePrice, vatRate);
            
            // Update the display if it exists
            if ($('#cs_above_short_desc').length) {
                $('#cs_above_short_desc .cs-price-value').text(totalPrice.toFixed(2) + ' SEK');
            }
        });
        
        // Trigger initial calculation on page load
        setTimeout(function() {
            const $priceInput = $('#regular_price, input[name="regular_price"], #_regular_price').first();
            
            if ($priceInput.length) {
                $priceInput.trigger('change');
            }
        }, 500);
        
        // Function to move the VAT and RRP fields above customer pays display
       function moveFields() {
    // Find the VAT and RRP field inputs
    const $vatInputField = $('#d04a4a2c8b7ac4a76c18da1a927e7aea');
    const $rrpInputField = $('#f0ed791c0fc0a2f5b856a13c88f2882c');
    const $deliveryTimeField = $('#7c7f9f48d29519e8cb47ac606a431a65');
    
    // Find the customer pays display
    const $customerPaysDisplay = $('#cs_above_short_desc');
    
    // Only proceed if necessary elements exist
    if ($customerPaysDisplay.length) {
        console.log("Found customer pays display");
        
        // Create container for the relocated fields
        const $fieldsContainer = $('<div class="cs-relocated-fields-container"></div>');
        
        // First add RRP field if it exists
        if ($rrpInputField.length) {
            console.log("Found RRP field");
            
            // Create RRP container
            const $rrpContainer = $('<div class="cs-rrp-field-container"></div>');
            
            // Create RRP label
            const $rrpLabel = $('<div class="cs-field-label"><strong>Retail Price</strong></div>');
            
            // Clone the RRP input
            const $rrpInputClone = $rrpInputField.clone();
            
            // Add elements to container
            $rrpContainer.append($rrpLabel);
            $rrpContainer.append($rrpInputClone);
            
            // Add RRP container to fields container
            $fieldsContainer.append($rrpContainer);
            
            // Sync the cloned input with the original
            $rrpInputClone.on('input change', function() {
                $rrpInputField.val($(this).val()).trigger('change');
            });
            
            // Hide original RRP field
            $rrpInputField.closest('.wcfm-field-group, .wcfm-field-row').hide();
        }
        
        // Add Delivery Time field if it exists
        if ($deliveryTimeField.length) {
            console.log("Found Delivery Time field");
            
            // Create Delivery Time container
            const $deliveryTimeContainer = $('<div class="cs-delivery-time-container"></div>');
            
            // Create Delivery Time label
            const $deliveryTimeLabel = $('<div class="cs-field-label"><strong>Delivery Time</strong></div>');
            
            // Clone the Delivery Time input
            const $deliveryTimeInputClone = $deliveryTimeField.clone();
            
            // Add date picker functionality to the cloned input
            $deliveryTimeInputClone.removeClass('hasDatepicker').datepicker({
                dateFormat: 'F j, Y',
                altField: $deliveryTimeField,
                altFormat: 'yy-mm-dd'
            });
            
            // Add elements to container
            $deliveryTimeContainer.append($deliveryTimeLabel);
            $deliveryTimeContainer.append($deliveryTimeInputClone);
            
            // Add Delivery Time container to fields container
            $fieldsContainer.append($deliveryTimeContainer);
            
            // Sync the cloned input with the original
            $deliveryTimeInputClone.on('change', function() {
                $deliveryTimeField.val($(this).val()).trigger('change');
            });
            
            // Hide original Delivery Time field
            $deliveryTimeField.closest('.wcfm-field-group, .wcfm-field-row').hide();
        }
                
                // Then add VAT field if it exists
                if ($vatInputField.length) {
                    console.log("Found VAT field");
                    
                    // Create VAT container
                    const $vatContainer = $('<div class="cs-vat-field-container"></div>');
                    
                    // Create VAT label
                    const $vatLabel = $('<div class="cs-field-label"><strong>VAT Rate</strong></div>');
                    
                    // Clone the VAT input
                    const $vatInputClone = $vatInputField.clone();
                    
                    // Add elements to container
                    $vatContainer.append($vatLabel);
                    $vatContainer.append($vatInputClone);
                    
                    // Add VAT container to fields container
                    $fieldsContainer.append($vatContainer);
                    
                    // Sync the cloned input with the original
                    $vatInputClone.on('input change', function() {
                        const newValue = $(this).val();
                        $vatInputField.val(newValue).trigger('change');
                        
                        // Update the price when VAT changes
                        const basePrice = $('#regular_price, input[name="regular_price"], #_regular_price').val();
                        const totalPrice = calculatePrice(basePrice, newValue);
                        
                        // Update the display
                        $('#cs_above_short_desc .cs-price-value').text(totalPrice.toFixed(2) + ' SEK');
                    });
                    
                    // Hide original VAT field
                    $vatInputField.closest('.wcfm-field-group, .wcfm-field-row').hide();
                }
                
                // Insert the fields container before the customer pays display
                $customerPaysDisplay.before($fieldsContainer);
                
                // Add custom styles
                $('head').append(`
                    <style>
                        .cs-relocated-fields-container {
                    margin-bottom: 20px;
                    width: 100%;
                }
                
                .cs-rrp-field-container,
                .cs-vat-field-container,
                .cs-delivery-time-container {
                    margin-bottom: 15px;
                    padding: 5px 0;
                    display: flex;
                    gap: 10px;
                }
                
                .cs-field-label {
                    margin-bottom: 5px;
                    font-size: 14px;
                }
                
                .cs-rrp-field-container input,
                .cs-vat-field-container input,
                .cs-delivery-time-container input {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    background-color: #f7f7f7;
                }
                    </style>
                `);
                
                console.log("Successfully moved fields above Customer pays display");
            } else {
                console.log("Could not find customer pays display, trying again in 500ms");
                setTimeout(moveFields, 500);
            }
        }
        
        // Initialize price calculation and move fields when the page loads
        setTimeout(function() {
            // Move fields first
            moveFields();
            
            // Then initialize price calculation
            const $priceInput = $('#regular_price, input[name="regular_price"], #_regular_price').first();
            
            if ($priceInput.length) {
                console.log("Found price input with value: " + $priceInput.val());
                
                // Trigger change event to calculate initial price
                $priceInput.trigger('change');
            } else {
                console.log("Price input not found");
            }
        }, 1000);
    });
</script>
    <?php
}
function cs_customer_price_display_shortcode() {
    // Get the current product
    global $product;
    
    if (!$product) {
        $product = wc_get_product(get_the_ID());
        
        if (!$product) {
            return '<p>Product not found</p>';
        }
    }
    
    // Get base price
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
    
    // Ensure margin rate and VAT rate are floats
    $margin_rate = floatval($margin_rate);
    $vat_rate = floatval($vat_rate);
    
    // Calculate margin amount
    $margin_amount = $base_price * ($margin_rate / 100);
    
    // Price with margin
    $price_with_margin = $base_price + $margin_amount;
    
    // Calculate VAT amount
    $vat_amount = $price_with_margin * ($vat_rate / 100);
    
    // Calculate total price
    $total_price = $price_with_margin + $vat_amount;
    
    // Always apply Swedish rounding
    $rounded_total_price = CS_Price_Calculator::round_price_to_nearest_nine($total_price);
    
    // Log detailed calculation for debugging
    error_log('Base Price: ' . $base_price);
    error_log('Margin Rate: ' . $margin_rate . '%');
    error_log('Margin Amount: ' . $margin_amount);
    error_log('Price with Margin: ' . $price_with_margin);
    error_log('VAT Rate: ' . $vat_rate . '%');
    error_log('VAT Amount: ' . $vat_amount);
    error_log('Total Price: ' . $total_price);
    error_log('Rounded Total Price: ' . $rounded_total_price);
    
    // Format prices
    $formatted_regular_price = wc_price($base_price);
    $formatted_total_price = wc_price($rounded_total_price);
    
    // Build HTML output
    $output = '<div class="cs-price-display-container">';
    $output .= '<div class="cs-regular-price-row">';
    $output .= '<span class="cs-price-label">Price:</span> ';
    $output .= '<span class="cs-regular-price">' . $formatted_regular_price . '</span>';
    $output .= '</div>';
    $output .= '<div class="cs-customer-price-row">';
    $output .= '<span class="cs-customer-price-label">Customer pays:</span> ';
    $output .= '<span class="cs-customer-price">' . $formatted_total_price . '</span>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Add custom styles (same as before)
    $output .= '<style>
        .cs-price-display-container {
            margin: 15px 0;
            padding: 10px;
            font-family: inherit;
        }
        .cs-regular-price-row, .cs-customer-price-row {
            margin-bottom: 8px;
        }
        .cs-price-label, .cs-customer-price-label {
            font-weight: 500;
        }
        .cs-customer-price-label, .cs-price-label {
            font-weight: 700;
			font-size: 20px;
            color: rgb(149, 142, 9);
        }
        .cs-customer-price, .cs-regular-price {
            font-weight: 700;
            font-size: 20px;
            color: rgb(149, 142, 9);
        }
    </style>';
    
    return $output;
}
add_shortcode('club_sales_price', 'cs_customer_price_display_shortcode');

/**
 * AJAX handler to refresh sales material content
 */
function cs_refresh_sales_material() {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cs_refresh_nonce')) {
        wp_send_json_error('Invalid security token');
        die();
    }
    
    // Get product ID if available
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    // Get child ID if available (for parent users)
    $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
    
    // Buffer the output
    ob_start();
    
    // Call the sales material tab function
    CS_Sales_Material::sales_material_tab($product_id, $child_id);
    
    // Get the buffered content
    $content = ob_get_clean();
    
    // Send the response
    wp_send_json_success([
        'content' => $content
    ]);
    
    die();
}
add_filter('woocommerce_get_permalink', function($permalink, $product) {
    // If permalink is empty or invalid, generate a custom one
    if (empty($permalink) || $permalink === '#') {
        return add_query_arg('product_id', $product->get_id(), home_url('/product'));
    }
    return $permalink;
}, 10, 2);
add_shortcode('cs_rounding_test', function() {
    $results = CS_Price_Calculator::detailed_rounding_test();
    
    $output = '<table border="1" style="width:100%;">';
    $output .= '<tr><th>Input</th><th>Rounded</th><th>Expected</th><th>Status</th></tr>';
    
    foreach ($results as $result) {
        $output .= sprintf(
            '<tr style="background-color:%s;">
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
            </tr>',
            $result['status'] === 'PASS' ? '#e6ffe6' : '#ffe6e6',
            $result['input'],
            $result['rounded'],
            $result['expected'],
            $result['status']
        );
    }
    
    $output .= '</table>';
    
    return $output;
});
function cs_list_child_users_shortcode() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please log in to view child users.</p>';
    }
    
    global $wpdb;
    $current_user_id = get_current_user_id();
    
    // Check if current user can manage children
    if (!CS_Child_Manager::can_manage_children()) {
        return '<p>You do not have permission to view child users.</p>';
    }
    
    // Fetch child users
    $children = CS_Child_Manager::get_parent_children($current_user_id);
    
    // Start output
    $output = '<div class="cs-child-users-list">';
    $output .= '<h2>My Child Users</h2>';
    
    if (empty($children)) {
        $output .= '<p>You have not added any child users yet.</p>';
    } else {
        $output .= '<table class="cs-table">';
        $output .= '<thead><tr>
            <th>Name</th>
            <th>Email</th>
            <th>Registered</th>
            <th>Actions</th>
        </tr></thead>';
        $output .= '<tbody>';
        
        foreach ($children as $child) {
            $output .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>
                        <button class="cs-remove-child" data-child-id="%d">Remove</button>
                    </td>
                </tr>',
                esc_html($child['name']),
                esc_html($child['email']),
                date_i18n(get_option('date_format'), strtotime($child['registered'])),
                $child['id']
            );
        }
        
        $output .= '</tbody></table>';
    }
    
    $output .= '</div>';
    
    // Add some basic JavaScript for removing children
    $output .= '
    <script>
    jQuery(document).ready(function($) {
        $(".cs-remove-child").on("click", function() {
            var childId = $(this).data("child-id");
            if (confirm("Are you sure you want to remove this child user?")) {
                $.ajax({
                    url: "' . admin_url('admin-ajax.php') . '",
                    type: "POST",
                    data: {
                        action: "cciu_remove_child",
                        nonce: "' . wp_create_nonce('cs-child-ajax-nonce') . '",
                        child_id: childId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("Error: " + response.data);
                        }
                    }
                });
            }
        });
    });
    </script>';
    
    return $output;
}
add_shortcode('cs_list_child_users', 'cs_list_child_users_shortcode');

// Diagnostic shortcode to verify child-parent relationships
function cs_diagnose_child_relationships() {
    if (!current_user_can('manage_options')) {
        return 'Administrator access only.';
    }
    
    global $wpdb;
    $output = '<h2>Child-Parent Relationship Diagnostic</h2>';
    
    // Get all child-parent relationships
    $relationships = $wpdb->get_results(
        "SELECT cp.*, 
                child_user.user_login AS child_username, 
                child_user.display_name AS child_display_name,
                parent_user.user_login AS parent_username, 
                parent_user.display_name AS parent_display_name
        FROM {$wpdb->prefix}cs_child_parent cp
        LEFT JOIN {$wpdb->users} child_user ON cp.child_id = child_user.ID
        LEFT JOIN {$wpdb->users} parent_user ON cp.parent_id = parent_user.ID
        ORDER BY cp.created_at DESC"
    );
    
    $output .= '<table border="1" style="width:100%;">';
    $output .= '<tr>
        <th>Relationship ID</th>
        <th>Child ID</th>
        <th>Child Username</th>
        <th>Child Display Name</th>
        <th>Parent ID</th>
        <th>Parent Username</th>
        <th>Parent Display Name</th>
        <th>Created At</th>
    </tr>';
    
    foreach ($relationships as $rel) {
        $output .= sprintf(
            '<tr>
                <td>%d</td>
                <td>%d</td>
                <td>%s</td>
                <td>%s</td>
                <td>%d</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
            </tr>',
            $rel->id,
            $rel->child_id,
            esc_html($rel->child_username),
            esc_html($rel->child_display_name),
            $rel->parent_id,
            esc_html($rel->parent_username),
            esc_html($rel->parent_display_name),
            $rel->created_at
        );
    }
    $output .= '</table>';
    
    return $output;
}
add_shortcode('cs_diagnose_child_relationships', 'cs_diagnose_child_relationships');

//Sales Materials on Product Page
function cs_product_sales_material_downloads_shortcode() {
    // Check if we're on a single product page
    if (!is_singular('product')) {
        return '';
    }
    
    // Get current product ID
    $product_id = get_the_ID();
    
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return '';
    }
    
    // Prepare download items
    $download_items = [];
    
    // Sales Pitches
    $sales_pitches = get_field('sales_pitches', $product_id);
    if ($sales_pitches) {
        $download_items[] = [
            'label' => 'Sales Pitches',
            'url' => is_array($sales_pitches) ? $sales_pitches['url'] : wp_get_attachment_url($sales_pitches),
            'type' => 'sales_pitches'
        ];
    }
    
    // Product Image
    $product_image = get_field('product_image', $product_id);
    if ($product_image) {
        $download_items[] = [
            'label' => 'Product Image',
            'url' => is_array($product_image) ? $product_image['url'] : wp_get_attachment_url($product_image),
            'type' => 'product_image'
        ];
    }
    
    // Social Media Content
    $social_media_content = get_field('social_media_content', $product_id);
    if ($social_media_content) {
        $download_items[] = [
            'label' => 'Social Media Content',
            'url' => is_array($social_media_content) ? $social_media_content['url'] : wp_get_attachment_url($social_media_content),
            'type' => 'social_media_content'
        ];
    }
    
    // If no download items, return empty
    if (empty($download_items)) {
        return '';
    }
    
    // Start output buffering
    ob_start();
    ?>
    <div class="cs-sales-material-downloads">
        <div class="cs-download-buttons">
            <?php foreach ($download_items as $index => $item): ?>
                <a href="#" 
                   class="cs-download-btn" 
                   data-url="<?php echo esc_url($item['url']); ?>" 
                   data-type="<?php echo esc_attr($item['type']); ?>">
                    <?php echo esc_html($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <style>
    .cs-sales-material-downloads {
        margin-top: 20px;
    }
    .cs-download-buttons {
        display: flex;
        gap: 10px;
    }
    .cs-download-btn {
        background-color: #1a7a19;
        color: white !important;
        padding: 10px 20px;
        text-decoration: none !important;
        border-radius: 25px;
        font-weight: bold;
        transition: background-color 0.3s ease;
		width: 30%;
    }
    .cs-download-btn:hover {
        background-color: #145c13;
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.cs-download-btn').on('click', function(e) {
            e.preventDefault();
            
            const downloadUrl = $(this).data('url');
            const downloadType = $(this).data('type');
            
            // Extract filename from the URL
            const filename = extractFilenameFromUrl(downloadUrl);
            
            // Create a temporary anchor element
            const link = document.createElement('a');
            link.href = downloadUrl;
            
            // Use the original filename
            link.download = filename;
            
            // Append to body, click, and remove
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Function to extract filename from URL
        function extractFilenameFromUrl(url) {
            // Remove query parameters and fragment identifier
            const cleanUrl = url.split(/[?#]/)[0];
            
            // Split the URL and get the last part
            const parts = cleanUrl.split('/');
            return decodeURIComponent(parts[parts.length - 1]);
        }
    });
    </script>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('cs_product_downloads', 'cs_product_sales_material_downloads_shortcode');
if (!wp_next_scheduled('cs_daily_profit_refresh')) {
    wp_schedule_event(time(), 'daily', 'cs_daily_profit_refresh');
}
add_action('cs_daily_profit_refresh', array('CS_Sales', 'refresh_total_profit'));

if (!wp_next_scheduled('cs_daily_profit_refresh')) {
    wp_schedule_event(time(), 'daily', 'cs_daily_profit_refresh');
}

// Hook the profit refresh function
add_action('cs_daily_profit_refresh', array('CS_Sales', 'refresh_total_profit'));



function cs_verify_profit_calculation() {
    if (!is_user_logged_in()) {
        return 'Please log in to view profit calculation.';
    }
    
    global $wpdb;
    $user_id = get_current_user_id();
    
    $output = '<div style="background:#fff;padding:20px;border:1px solid #ddd;color:#000;">';
    $output .= '<h2>Profit Calculation Verification</h2>';
    $output .= '<p><strong>Calculation Method:</strong> (Sale Price × Quantity) - RRP</p>';
    
    // Get all sales for current user
    $sales = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cs_sales WHERE user_id = %d ORDER BY id DESC",
        $user_id
    ));
    
    $output .= '<p><strong>Total Sales Found:</strong> ' . count($sales) . '</p>';
    
    $grand_total_profit = 0;
    
    foreach ($sales as $sale) {
        $output .= '<div style="border:1px solid #ccc;padding:10px;margin:10px 0;">';
        $output .= '<h3>Sale ID: ' . $sale->id . ' - ' . esc_html($sale->customer_name) . '</h3>';
        
        // Parse products
        $products_json = $sale->products;
        if (strpos($products_json, '\"') !== false) {
            $products_json = stripslashes($products_json);
        }
        
        $products = json_decode($products_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $output .= '<p style="color:red;">JSON Error: ' . json_last_error_msg() . '</p>';
            continue;
        }
        
        $sale_profit = 0;
        
        if (is_array($products)) {
            $output .= '<table border="1" style="width:100%;margin:10px 0;">';
            $output .= '<tr><th>Product</th><th>Sale Price</th><th>Qty</th><th>Revenue</th><th>RRP (Cost)</th><th>Profit</th></tr>';
            
            foreach ($products as $product) {
                $product_id = isset($product['id']) ? $product['id'] : 0;
                $sale_price = isset($product['price']) ? floatval($product['price']) : 0;
                $quantity = isset($product['quantity']) ? intval($product['quantity']) : 1;
                
                // Get RRP
                $rrp = 0;
                if (function_exists('get_field')) {
                    $rrp = floatval(get_field('rrp', $product_id));
                }
                
                if ($rrp == 0 && $product_id > 0) {
                    $wc_product = wc_get_product($product_id);
                    if ($wc_product) {
                        $rrp = floatval($wc_product->get_regular_price());
                    }
                }
                
                // NEW CALCULATION
                $total_revenue = $sale_price * $quantity;
                $profit = $total_revenue - $rrp;
                $sale_profit += $profit;
                
                $output .= sprintf(
                    '<tr>
                        <td>%s (ID: %d)</td>
                        <td>%s SEK</td>
                        <td>%d</td>
                        <td>%s SEK</td>
                        <td>%s SEK</td>
                        <td style="color:%s;font-weight:bold;">%s SEK</td>
                    </tr>',
                    esc_html($product['name'] ?? 'Unknown'),
                    $product_id,
                    number_format($sale_price, 2),
                    $quantity,
                    number_format($total_revenue, 2),
                    number_format($rrp, 2),
                    $profit >= 0 ? 'green' : 'red',
                    number_format($profit, 2)
                );
                
                // Add calculation breakdown
                $output .= sprintf(
                    '<tr><td colspan="6" style="background:#f5f5f5;font-size:12px;padding:5px;">
                        Calculation: (%s × %d) - %s = %s SEK
                    </td></tr>',
                    number_format($sale_price, 2),
                    $quantity,
                    number_format($rrp, 2),
                    number_format($profit, 2)
                );
            }
            
            $output .= '</table>';
        }
        
        $output .= '<p><strong>Sale Total Profit:</strong> <span style="color:green;font-size:16px;">' . number_format($sale_profit, 2) . ' SEK</span></p>';
        $grand_total_profit += $sale_profit;
        
        $output .= '</div>';
    }
    
    $output .= '<h2 style="color:#4CAF50;">Grand Total Profit: ' . number_format($grand_total_profit, 2) . ' SEK</h2>';
    
    // Now test the actual functions
    $output .= '<h3>Function Test Results:</h3>';
    $calculated_profit = CS_Sales::calculate_total_profit($user_id);
    $stats = CS_Sales::get_user_stats($user_id);
    
    $output .= '<p><strong>CS_Sales::calculate_total_profit():</strong> ' . number_format($calculated_profit, 2) . ' SEK</p>';
    $output .= '<p><strong>CS_Sales::get_user_stats() profit:</strong> ' . number_format($stats['total_profit'], 2) . ' SEK</p>';
    
    if (abs($grand_total_profit - $calculated_profit) > 0.01) {
        $output .= '<p style="color:red;"><strong>WARNING:</strong> Manual calculation differs from function calculation!</p>';
    } else {
        $output .= '<p style="color:green;"><strong>SUCCESS:</strong> Calculations match!</p>';
    }
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode('cs_verify_profit', 'cs_verify_profit_calculation');

function cs_price_rounding_test_shortcode() {
    $test_cases = [
        ['input' => 120, 'expected' => 119],
        ['input' => 145, 'expected' => 149],
        ['input' => 190, 'expected' => 189],
        ['input' => 135, 'expected' => 139],
        ['input' => 199, 'expected' => 199],
        ['input' => 100, 'expected' => 99],
        ['input' => 102, 'expected' => 109],
        ['input' => 110, 'expected' => 109],
        ['input' => 115, 'expected' => 119],
        ['input' => 180, 'expected' => 189],
        ['input' => 188, 'expected' => 189],
        ['input' => 201, 'expected' => 209],
        ['input' => 250, 'expected' => 249]
    ];
    
    ob_start();
    
    echo '<table border="1" style="width:100%;">';
    echo '<tr><th>Input</th><th>Rounded</th><th>Expected</th><th>Status</th><th>Calculation Steps</th></tr>';
    
    $total_tests = 0;
    $passed_tests = 0;
    
    foreach ($test_cases as $case) {
        $total_tests++;
        $input = $case['input'];
        $expected = $case['expected'];
        
        $rounded = CS_Price_Calculator::round_price_to_nearest_nine($input);
        $status = $rounded === $expected ? 'PASS' : 'FAIL';
        
        if ($status === 'PASS') {
            $passed_tests++;
        }
        
        // Calculate steps for debugging
        $steps = [];
        $last_digit = $input % 10;
        
        if ($last_digit <= 1) {
            $steps[] = "0-1 range: floor({$input}/10) * 10 - 1";
            $steps[] = "Calculation: " . (floor($input / 10) * 10 - 1);
        } elseif ($last_digit >= 2 && $last_digit <= 8) {
            $steps[] = "2-8 range: floor({$input}/10) * 10 + 9";
            $steps[] = "Calculation: " . (floor($input / 10) * 10 + 9);
        } else {
            $steps[] = "9 range: Keep as is";
        }
        
        echo sprintf(
            '<tr style="background-color:%s;">
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td style="color:%s;">%s</td>
                <td>%s</td>
            </tr>',
            $status === 'PASS' ? '#e6ffe6' : '#ffe6e6',
            $input,
            $rounded,
            $expected,
            $status === 'PASS' ? 'green' : 'red',
            $status,
            implode('<br>', $steps)
        );
    }
    
    $pass_percentage = round(($passed_tests / $total_tests) * 100, 2);
    
    echo '</table>';
    
    echo sprintf(
        '<div style="margin-top:20px; text-align:center;">
            <strong>Test Summary:</strong><br>
            Total Tests: %d<br>
            Passed Tests: %d<br>
            Pass Percentage: %s%%
        </div>',
        $total_tests,
        $passed_tests,
        $pass_percentage
    );
    
    return ob_get_clean();
}
add_shortcode('cs_price_rounding_test', 'cs_price_rounding_test_shortcode');

// Hook to modify cart item prices
add_action('woocommerce_before_calculate_totals', 'cs_modify_cart_item_prices');

function cs_modify_cart_item_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['club_sales_price'])) {
            $cart_item['data']->set_price($cart_item['club_sales_price']);
        }
    }
}

// Hook to pre-fill checkout fields
// add_filter('woocommerce_checkout_get_value', 'cs_prefill_checkout_fields', 10, 2);

function cs_prefill_checkout_fields($value, $input) {
	
	return $value;
	
//     if (!WC()->session) {
//         return $value;
//     }
    
//     $customer_data = WC()->session->get('club_sales_customer_data');
    
//     if ($customer_data && isset($customer_data[$input])) {
//         return $customer_data[$input];
//     }
    
//     return $value;
}

// Clean up temporary products after order completion
add_action('woocommerce_order_status_completed', 'cs_cleanup_temp_products');
add_action('woocommerce_order_status_processing', 'cs_cleanup_temp_products');

function cs_cleanup_temp_products($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $product = wc_get_product($product_id);
        
        if ($product && $product->get_meta('_club_sales_temp_product') === 'yes') {
            // Delete the temporary product
            wp_delete_post($product_id, true);
            error_log("Cleaned up temporary product: " . $product_id);
        }
    }
}
// Hook to force empty checkout fields
add_filter('woocommerce_checkout_get_value', 'cs_force_empty_checkout_fields', 10, 2);

function cs_force_empty_checkout_fields($value, $input) {
    // List of fields to force empty
    $fields_to_empty = array(
        'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone',
        'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode',
        'shipping_first_name', 'shipping_last_name', 'shipping_address_1', 
        'shipping_address_2', 'shipping_city', 'shipping_postcode'
    );
    
    // If this is a field we want to keep empty, return empty string
    if (in_array($input, $fields_to_empty)) {
        return '';
    }
    
    // For other fields, return the original value
    return $value;
}

// Hook into WooCommerce product tax class filter
add_filter('woocommerce_product_tax_class', 'cs_auto_assign_tax_class_by_vat', 10, 2);

/**
 * Automatically assign tax class based on product's VAT rate
 */
function cs_auto_assign_tax_class_by_vat($tax_class, $product) {
    $product_id = $product->get_id();
    $vat_rate = null;
    
    if (function_exists('get_field')) {
        $vat_rate = get_field('vat', $product_id);
    }
    
    // Clean the VAT rate value (handles values like " 6 " from vendor form)
    if ($vat_rate) {
        $vat_rate = trim(str_replace(' ', '', $vat_rate));
    }

    $vat_to_tax_class_map = array(
        '6'  => '6',
        '12' => '12',
        '25' => '25'
    );
    
    if ($vat_rate && array_key_exists($vat_rate, $vat_to_tax_class_map)) {
        return $vat_to_tax_class_map[$vat_rate];
    }
    
    return $tax_class; // Fallback to original
}

/**
 * Add JavaScript for real-time tax class updates
 */
add_action('wp_footer', 'cs_add_vat_tax_realtime_script');
add_action('admin_footer', 'cs_add_vat_tax_realtime_script');

function cs_add_vat_tax_realtime_script() {
    // Only load on relevant admin/vendor pages
    if (!is_admin() && !function_exists('wcfm_is_vendor')) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {

        // This map is correct based on your previous screenshot
        const vatToTaxClass = {
            '6': '6',
            '12': '12',
            '25': '25'
        };

        // Function to update the tax class dropdown
        function updateTaxClassDisplay(vatRate) {
            // Clean the value just in case (removes spaces and non-numeric chars)
            const cleanVatRate = String(vatRate).replace(/\D/g, '');

            if (vatToTaxClass[cleanVatRate]) {
                const taxClassSlug = vatToTaxClass[cleanVatRate];
                $('#_tax_class').val(taxClassSlug);
                console.log(`✅ Tax class updated to: ${taxClassSlug}`);
            }
        }

        // Use a robust selector that works on both admin and vendor pages.
        // It listens for changes on the VAT dropdown.
        $(document).on('change', 'select[data-name="vat"]', function() {
            const selectedValue = $(this).val();
            updateTaxClassDisplay(selectedValue);
        });

        // Also check the value when the page first loads
        setTimeout(function() {
            const $vatSelect = $('select[data-name="vat"]');
            if ($vatSelect.length > 0) {
                const existingValue = $vatSelect.val();
                if (existingValue) {
                    updateTaxClassDisplay(existingValue);
                }
            }
        }, 1000); // Wait 1 sec for page to build

    });
    </script>
    <?php
}

// Main sync function - consolidated from multiple duplicates
add_action('acf/save_post', 'cs_sync_tax_class_with_vat', 20);
add_action('wcfm_product_manage_after_process', 'cs_sync_tax_class_with_vat_wcfm', 10, 2);

/**
 * Main function to sync tax class with VAT rate (ACF save)
 */
function cs_sync_tax_class_with_vat($post_id) {
    // Only process for products
    if (get_post_type($post_id) !== 'product') {
        return;
    }
    
    cs_update_tax_class_from_vat_rate($post_id);
}

/**
 * WCFM wrapper function
 */
function cs_sync_tax_class_with_vat_wcfm($product_id, $wcfm_products_manage_form_data) {
    // Only process during product saves, not during settings saves
    if (wp_doing_ajax() && isset($_POST['action'])) {
        $action = $_POST['action'];
        // Skip if it's a settings save action
        if (strpos($action, 'settings') !== false || strpos($action, 'billing') !== false) {
            return;
        }
    }
    
    cs_update_tax_class_from_vat_rate($product_id);
}

/**
 * Core function to update tax class based on VAT rate
 */
function cs_update_tax_class_from_vat_rate($product_id) {
    if (function_exists('get_field')) {
        $vat_rate = get_field('vat', $product_id);
        
        if ($vat_rate) {
            // Clean the VAT rate value
            $vat_rate = trim(str_replace(' ', '', $vat_rate));
            
            // Map VAT rates to tax classes
            $vat_to_tax_class_map = array(
                '6'  => '6',
                '12' => '12',
                '25' => '25'
            );
            
            // Update the tax class if we have a mapping
            if (array_key_exists($vat_rate, $vat_to_tax_class_map)) {
                $tax_class = $vat_to_tax_class_map[$vat_rate];
                
                // Get current tax class
                $current_tax_class = get_post_meta($product_id, '_tax_class', true);
                
                // Only update if different
                if ($current_tax_class !== $tax_class) {
                    update_post_meta($product_id, '_tax_class', $tax_class);
                    error_log("Updated product {$product_id} tax class to '{$tax_class}' based on VAT rate {$vat_rate}%");
                    
                    // Clear WooCommerce caches
                    if (function_exists('wc_delete_product_transients')) {
                        wc_delete_product_transients($product_id);
                    }
                }
            }
        }
    }
}

// Bidirectional sync: Update VAT field when tax class changes in admin
add_action('updated_post_meta', 'cs_sync_vat_from_tax_class_change', 10, 4);

/**
 * Sync VAT field when tax class is changed in WooCommerce admin
 */
function cs_sync_vat_from_tax_class_change($meta_id, $post_id, $meta_key, $meta_value) {
    // Only process for products and tax class changes
    if (get_post_type($post_id) !== 'product' || $meta_key !== '_tax_class') {
        return;
    }
    
    // Skip during AJAX requests (like WCFM settings save)
    if (wp_doing_ajax() && (!isset($_POST['action']) || strpos($_POST['action'], 'wcfm') !== false)) {
        return;
    }
    
    // Prevent infinite loops
    static $processing = false;
    if ($processing) return;
    $processing = true;
    
    // Map tax classes back to VAT rates
    $tax_class_to_vat_map = array(
        '6'  => '6',
        '12' => '12', 
        '25' => '25',
        ''   => '25' // Default standard to 25%
    );
    
    if (array_key_exists($meta_value, $tax_class_to_vat_map)) {
        $new_vat_rate = $tax_class_to_vat_map[$meta_value];
        
        // Get current VAT from ACF
        $current_vat = '';
        if (function_exists('get_field')) {
            $current_vat = get_field('vat', $post_id);
        }
        
        // Only update if different
        if ($current_vat !== $new_vat_rate) {
            // Update ACF VAT field
            if (function_exists('update_field')) {
                update_field('vat', $new_vat_rate, $post_id);
                error_log("Updated ACF VAT field to '{$new_vat_rate}' based on tax class '{$meta_value}' for product {$post_id}");
            }
        }
    }
    
    $processing = false;
}

/**
 * Update all VAT-related fields for consistency
 */
function cs_update_all_vat_fields($product_id, $vat_rate) {
    $clean_vat = floatval(trim(str_replace(' ', '', $vat_rate)));
    
    // Update custom VAT meta fields
    update_post_meta($product_id, '_cs_vat_rate', $clean_vat);
    
    // Calculate VAT amount based on product price
    $product = wc_get_product($product_id);
    if ($product) {
        $base_price = $product->get_regular_price();
        if ($base_price) {
            // Calculate VAT amount
            $margin_rate = floatval(get_option('club_sales_global_margin', 12));
            $margin_amount = $base_price * ($margin_rate / 100);
            $price_with_margin = $base_price + $margin_amount;
            $vat_amount = $price_with_margin * ($clean_vat / 100);
            
            // Update VAT amount meta
            update_post_meta($product_id, '_cs_vat_amount', $vat_amount);
        }
    }
}
function cs_debug_rrp_shortcode($atts) {
    $atts = shortcode_atts(array(
        'product_id' => 0,
    ), $atts);
    
    $product_id = intval($atts['product_id']);
    
    if (!$product_id) {
        return '<p>Please provide product_id parameter: [cs_debug_rrp product_id="123"]</p>';
    }
    
    $current_user = wp_get_current_user();
    $is_child = in_array('club_child_user', $current_user->roles);
    $user_type = $is_child ? 'CHILD' : 'ADMIN';
    
    ob_start();
    ?>
    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin: 20px 0; color: #000;">
        <h3>RRP Debug for Product <?php echo $product_id; ?> - User Type: <?php echo $user_type; ?></h3>
        
        <?php
        global $wpdb;
        
        // Test 1: Check if product exists
        $product_exists = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, post_title, post_status FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'product'", 
            $product_id
        ));
        
        echo "<h4>Test 1: Product Exists Check</h4>";
        if ($product_exists) {
            echo "<p style='color: green;'>✓ Product exists: {$product_exists->post_title} (Status: {$product_exists->post_status})</p>";
        } else {
            echo "<p style='color: red;'>✗ Product does not exist or is not a product type</p>";
            echo "</div>";
            return ob_get_clean();
        }
        
        // Test 2: Direct DB query for RRP
        echo "<h4>Test 2: Direct Database Query</h4>";
        $db_rrp = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'rrp'", 
            $product_id
        ));
        echo "<p><strong>SQL:</strong> " . $wpdb->last_query . "</p>";
        echo "<p><strong>Result:</strong> " . var_export($db_rrp, true) . "</p>";
        
        if ($db_rrp) {
            echo "<p style='color: green;'>✓ RRP found in database: {$db_rrp}</p>";
        } else {
            echo "<p style='color: red;'>✗ No RRP found in database</p>";
        }
        
        // Test 3: All meta for this product
        echo "<h4>Test 3: All Meta Data for Product</h4>";
        $all_meta = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d ORDER BY meta_key", 
            $product_id
        ));
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Meta Key</th><th>Meta Value</th></tr>";
        foreach ($all_meta as $meta) {
            $highlight = (strpos($meta->meta_key, 'rrp') !== false) ? 'background-color: yellow;' : '';
            echo "<tr style='{$highlight}'><td>{$meta->meta_key}</td><td>{$meta->meta_value}</td></tr>";
        }
        echo "</table>";
        
        // Test 4: WordPress get_post_meta
        echo "<h4>Test 4: WordPress get_post_meta()</h4>";
        $wp_meta = get_post_meta($product_id, 'rrp', true);
        echo "<p><strong>get_post_meta({$product_id}, 'rrp', true):</strong> " . var_export($wp_meta, true) . "</p>";
        
        if ($wp_meta) {
            echo "<p style='color: green;'>✓ RRP found via get_post_meta: {$wp_meta}</p>";
        } else {
            echo "<p style='color: red;'>✗ No RRP found via get_post_meta</p>";
        }
        
        // Test 5: ACF get_field
        echo "<h4>Test 5: ACF get_field()</h4>";
        if (function_exists('get_field')) {
            $acf_rrp = get_field('rrp', $product_id);
            echo "<p><strong>get_field('rrp', {$product_id}):</strong> " . var_export($acf_rrp, true) . "</p>";
            
            if ($acf_rrp) {
                echo "<p style='color: green;'>✓ RRP found via ACF: {$acf_rrp}</p>";
            } else {
                echo "<p style='color: red;'>✗ No RRP found via ACF</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ ACF get_field function not available</p>";
        }
        
        // Test 6: WooCommerce Product
        echo "<h4>Test 6: WooCommerce Product Data</h4>";
        $wc_product = wc_get_product($product_id);
        if ($wc_product) {
            $regular_price = $wc_product->get_regular_price();
            $sale_price = $wc_product->get_sale_price();
            echo "<p><strong>Regular Price:</strong> {$regular_price}</p>";
            echo "<p><strong>Sale Price:</strong> {$sale_price}</p>";
            echo "<p><strong>Display Price:</strong> " . $wc_product->get_price() . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Could not load WooCommerce product</p>";
        }
        
        // Test 7: Child Product Assignment Check
        if ($is_child) {
            echo "<h4>Test 7: Child Product Assignment Check</h4>";
            $assigned = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cs_child_products WHERE child_id = %d AND product_id = %d",
                $current_user->ID,
                $product_id
            ));
            
            if ($assigned) {
                echo "<p style='color: green;'>✓ Product is assigned to this child user</p>";
                echo "<p><strong>Assignment details:</strong> " . print_r($assigned, true) . "</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Product is NOT assigned to this child user</p>";
            }
        }
        
        // Test 8: Recent Sales Check
        echo "<h4>Test 8: Recent Sales with This Product</h4>";
        $recent_sales = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_id, customer_name, sale_amount, customer_pays, products 
             FROM {$wpdb->prefix}cs_sales 
             WHERE products LIKE %s 
             ORDER BY id DESC LIMIT 5",
            '%"id":' . $product_id . '%'
        ));
        
        if ($recent_sales) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Sale ID</th><th>User ID</th><th>Customer</th><th>Sale Amount</th><th>Customer Pays</th></tr>";
            foreach ($recent_sales as $sale) {
                $highlight = ($sale->customer_pays == 0) ? 'background-color: #ffcccc;' : '';
                echo "<tr style='{$highlight}'>
                    <td>{$sale->id}</td>
                    <td>{$sale->user_id}</td>
                    <td>{$sale->customer_name}</td>
                    <td>{$sale->sale_amount}</td>
                    <td>{$sale->customer_pays}</td>
                </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No recent sales found with this product</p>";
        }
        
        ?>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('cs_debug_rrp', 'cs_debug_rrp_shortcode');

// Get product description via AJAX
add_action('wp_ajax_cs_get_product_description', 'cs_get_product_description');
add_action('wp_ajax_nopriv_cs_get_product_description', 'cs_get_product_description');

function cs_get_product_description() {
    // Verify nonce - using the correct nonce name
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cs-ajax-nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $product_id = intval($_POST['product_id']);
    $product = wc_get_product($product_id);
    
    if (!$product) {
        wp_send_json_error('Product not found');
        return;
    }
    
    $description = $product->get_description();
    
    if (empty($description)) {
        $description = $product->get_short_description();
    }
    
    if (empty($description)) {
        $description = '<p>Ingen beskrivning tillgänglig för denna produkt.</p>';
    }
    
    wp_send_json_success([
        'description' => $description
    ]);
}