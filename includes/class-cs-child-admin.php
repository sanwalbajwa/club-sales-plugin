<?php
/**
 * Admin functionality for the Club Child Users
 */
class CS_Child_Admin {
    /**
     * Initialize admin hooks
     */
    public static function init() {
        // Add admin menu
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array(__CLASS__, 'register_settings'));
		add_action('admin_menu', array(__CLASS__, 'add_global_margin_settings'));
    }
    
    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('Club Child Users', 'club-sales'),
            __('Club Child Users', 'club-sales'),
            'manage_options',
            'club-child-users',
            array(__CLASS__, 'admin_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'club-child-users',
            __('Settings', 'club-sales'),
            __('Settings', 'club-sales'),
            'manage_options',
            'club-child-users-settings',
            array(__CLASS__, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('cs_child_user_settings', 'cs_child_user_settings');
        
        add_settings_section(
            'cs_child_section_general',
            __('General Settings', 'club-sales'),
            array(__CLASS__, 'section_general_callback'),
            'club-child-users-settings'
        );
        
        add_settings_field(
            'cs_child_field_managing_roles',
            __('Roles that can manage child users', 'club-sales'),
            array(__CLASS__, 'field_managing_roles_callback'),
            'club-child-users-settings',
            'cs_child_section_general'
        );
        
        add_settings_field(
            'cs_child_field_delete_users',
            __('Delete users on removal', 'club-sales'),
            array(__CLASS__, 'field_delete_users_callback'),
            'club-child-users-settings',
            'cs_child_section_general'
        );
        
        add_settings_field(
            'cs_child_field_allow_registration',
            __('Allow child registration', 'club-sales'),
            array(__CLASS__, 'field_allow_registration_callback'),
            'club-child-users-settings',
            'cs_child_section_general'
        );
    }
    
    /**
     * General section callback
     */
    public static function section_general_callback() {
        echo '<p>' . __('Configure general settings for the Club Child Users Integration.', 'club-sales') . '</p>';
    }
    
    /**
     * Managing roles field callback
     */
    public static function field_managing_roles_callback() {
        $settings = get_option('cs_child_user_settings', array('managing_roles' => array('administrator')));
        $managing_roles = $settings['managing_roles'];
        
        $roles = wp_roles()->get_names();
        ?>
        <fieldset>
            <?php foreach ($roles as $role_key => $role_name) : ?>
                <label>
                    <input type="checkbox" name="cs_child_user_settings[managing_roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $managing_roles)); ?>>
                    <?php echo esc_html($role_name); ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>
        <p class="description"><?php _e('Select which user roles can manage child users.', 'club-sales'); ?></p>
        <?php
    }
    
    /**
     * Delete users field callback
     */
    public static function field_delete_users_callback() {
        $settings = get_option('cs_child_user_settings', array('delete_users_on_removal' => 'no'));
        $delete_users = isset($settings['delete_users_on_removal']) ? $settings['delete_users_on_removal'] : 'no';
        ?>
        <fieldset>
            <label>
                <input type="radio" name="cs_child_user_settings[delete_users_on_removal]" value="yes" <?php checked($delete_users, 'yes'); ?>>
                <?php _e('Yes', 'club-sales'); ?>
            </label><br>
            <label>
                <input type="radio" name="cs_child_user_settings[delete_users_on_removal]" value="no" <?php checked($delete_users, 'no'); ?>>
                <?php _e('No', 'club-sales'); ?>
            </label>
        </fieldset>
        <p class="description"><?php _e('If set to "Yes", child users will be completely deleted when removed. If "No", they will still exist but without a parent relationship.', 'club-sales'); ?></p>
        <?php
    }
    
    /**
     * Allow registration field callback
     */
    public static function field_allow_registration_callback() {
        $settings = get_option('cs_child_user_settings', array('allow_child_registration' => 'no'));
        $allow_registration = isset($settings['allow_child_registration']) ? $settings['allow_child_registration'] : 'no';
        ?>
        <fieldset>
            <label>
                <input type="radio" name="cs_child_user_settings[allow_child_registration]" value="yes" <?php checked($allow_registration, 'yes'); ?>>
                <?php _e('Yes', 'club-sales'); ?>
            </label><br>
            <label>
                <input type="radio" name="cs_child_user_settings[allow_child_registration]" value="no" <?php checked($allow_registration, 'no'); ?>>
                <?php _e('No', 'club-sales'); ?>
            </label>
        </fieldset>
        <p class="description"><?php _e('If set to "Yes", child users can register themselves. If "No", only club admins can add child users.', 'club-sales'); ?></p>
        <?php
    }
    
    /**
     * Admin page
     */
    public static function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=club-child-users" class="nav-tab nav-tab-active"><?php _e('Dashboard', 'club-sales'); ?></a>
                <a href="?page=club-child-users-settings" class="nav-tab"><?php _e('Settings', 'club-sales'); ?></a>
            </h2>
            
            <div class="card">
                <h2><?php _e('Club Child Users Overview', 'club-sales'); ?></h2>
                <p><?php _e('This plugin allows club admins to add and manage child users and assign products to them.', 'club-sales'); ?></p>
                
                <h3><?php _e('Key Features', 'club-sales'); ?></h3>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php _e('Add child users to club admin accounts', 'club-sales'); ?></li>
                    <li><?php _e('Assign specific products to each child user', 'club-sales'); ?></li>
                    <li><?php _e('Child users can only see and sell their assigned products', 'club-sales'); ?></li>
                    <li><?php _e('Track sales by both club admins and their children', 'club-sales'); ?></li>
                </ul>
            </div>
            
            <div class="card">
                <h2><?php _e('Usage Instructions', 'club-sales'); ?></h2>
                <ol style="list-style-type: decimal; margin-left: 20px;">
                    <li><?php _e('Configure which roles can manage child users in the Settings tab', 'club-sales'); ?></li>
                    <li><?php _e('Add the [club_sales_dashboard] shortcode to a page', 'club-sales'); ?></li>
                    <li><?php _e('Club admins will see a "Manage Children" tab where they can add child users', 'club-sales'); ?></li>
                    <li><?php _e('Use the "Assign Products" tab to assign specific products to each child', 'club-sales'); ?></li>
                    <li><?php _e('Child users will only see their assigned products when they log in', 'club-sales'); ?></li>
                </ol>
                
                <h3><?php _e('Alternative Shortcodes', 'club-sales'); ?></h3>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><code>[cs_manage_children]</code> - <?php _e('Display the child management interface', 'club-sales'); ?></li>
                    <li><code>[cs_assign_products]</code> - <?php _e('Display the product assignment interface', 'club-sales'); ?></li>
                    <li><code>[cs_child_products]</code> - <?php _e('Display assigned products (for child users)', 'club-sales'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=club-child-users" class="nav-tab"><?php _e('Dashboard', 'club-sales'); ?></a>
                <a href="?page=club-child-users-settings" class="nav-tab nav-tab-active"><?php _e('Settings', 'club-sales'); ?></a>
            </h2>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('cs_child_user_settings');
                do_settings_sections('club-child-users-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
	public static function add_global_margin_settings() {
    add_menu_page(
        __('Global Product Settings', 'club-sales'),
        __('Product Settings', 'club-sales'),
        'manage_options',
        'club-sales-global-settings',
        array(__CLASS__, 'render_global_margin_settings_page'),
        'dashicons-admin-generic',
        30
    );
}

public static function render_global_margin_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save settings if form is submitted
    if (isset($_POST['club_sales_global_margin_nonce']) && 
        wp_verify_nonce($_POST['club_sales_global_margin_nonce'], 'club_sales_global_margin_action')) {
        
        // Sanitize and save margin
        $global_margin = isset($_POST['global_margin']) ? 
            floatval($_POST['global_margin']) : 0;
        
        update_option('club_sales_global_margin', $global_margin);
        self::recalculate_all_product_prices();
        // Add success message
        add_settings_error(
            'club_sales_global_margin', 
            'margin_updated', 
            __('Global margin updated successfully.', 'club-sales'), 
            'updated'
        );
    }
    
    // Get current global margin
    $current_margin = get_option('club_sales_global_margin', 12);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors(); ?>
        
        <form method="post">
            <?php wp_nonce_field('club_sales_global_margin_action', 'club_sales_global_margin_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="global_margin"><?php _e('Global Margin (%)', 'club-sales'); ?></label>
                    </th>
                    <td>
                        <input 
                            type="number" 
                            id="global_margin" 
                            name="global_margin" 
                            value="<?php echo esc_attr($current_margin); ?>" 
                            step="0.01" 
                            min="0" 
                            max="100"
                        />
                        <p class="description">
                            <?php _e('Set a default margin percentage that will be applied to all products.', 'club-sales'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save Global Margin', 'club-sales')); ?>
        </form>
    </div>
    <?php
}
	public static function recalculate_all_product_prices() {
    // Get all published products
    $products = wc_get_products(array(
        'status' => 'publish',
        'limit' => -1
    ));
    
    // Recalculate prices for each product
    foreach ($products as $product) {
        CS_Price_Calculator::auto_calculate_product_prices($product->get_id());
    }
}
}