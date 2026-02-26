<?php
/**
 * Add registration role settings to Club Sales Plugin
 */

// Add settings section for registration role
function cs_add_registration_settings() {
    // Register new setting
    register_setting('cs_settings', 'cs_registration_role');
    
    // Add settings section
    add_settings_section(
        'cs_registration_section',
        __('Registration Settings', 'club-sales'),
        'cs_registration_section_callback',
        'club-sales-settings'
    );
    
    // Add settings field
    add_settings_field(
        'cs_registration_role',
        __('Default Registration Role', 'club-sales'),
        'cs_registration_role_callback',
        'club-sales-settings',
        'cs_registration_section'
    );
}
add_action('admin_init', 'cs_add_registration_settings');

// Section callback
function cs_registration_section_callback() {
    echo '<p>' . __('Configure settings for user registration through the Club Sales plugin.', 'club-sales') . '</p>';
}

// Field callback
function cs_registration_role_callback() {
    $roles = get_editable_roles();
    $current_role = get_option('cs_registration_role', 'subscriber');
    
    echo '<select name="cs_registration_role">';
    foreach ($roles as $role_key => $role) {
        // Skip administrator role for security
        if ($role_key === 'administrator') {
            continue;
        }
        echo '<option value="' . esc_attr($role_key) . '" ' . selected($current_role, $role_key, false) . '>' . esc_html($role['name']) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . __('Select the default role that will be assigned to users who register through the Club Sales registration form.', 'club-sales') . '</p>';
}

// Add settings page to admin menu
function cs_add_settings_page() {
    add_menu_page(
        __('Club Sales Settings', 'club-sales'),
        __('Club Sales Role', 'club-sales-role'),
        'manage_options',
        'club-sales-settings',
        'cs_render_settings_page',
        'dashicons-cart',
        30
    );
}
add_action('admin_menu', 'cs_add_settings_page');

// Render settings page
function cs_render_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Show settings form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('cs_settings');
            do_settings_sections('club-sales-settings');
            submit_button(__('Save Settings', 'club-sales'));
            ?>
        </form>
    </div>
    <?php
}

// Now modify the registration handler to use the selected role
function cs_modify_registration_handler() {
    // Update the user role setting in the registration handler
    // This function just defines the modification - we'll apply it with the filter below
    
    // Get the registration function
    $function_string = file_get_contents(__FILE__);
    
    // Find the section where the role is set
    $pattern = '/\$user->set_role\(\'subscriber\'\); \/\/ Default WP role/';
    $replacement = '$user->set_role(get_option(\'cs_registration_role\', \'subscriber\')); // Use role from settings';
    
    // Replace the hard-coded role with our dynamic setting
    $modified_function = preg_replace($pattern, $replacement, $function_string);
    
    return $modified_function;
}

// Hook into the class-cs-auth.php file to modify the registration handler
function cs_update_registration_handler() {
    // Get the file path
    $auth_file = plugin_dir_path(__FILE__) . 'class-cs-auth.php';
    
    // Check if file exists and is writable
    if (file_exists($auth_file) && is_writable($auth_file)) {
        $file_contents = file_get_contents($auth_file);
        
        // Find the role setting line and replace it
        $pattern = '/\$user->set_role\(\'subscriber\'\); \/\/ Default WP role/';
        $replacement = '$user->set_role(get_option(\'cs_registration_role\', \'subscriber\')); // Use role from settings';
        
        $modified_contents = preg_replace($pattern, $replacement, $file_contents);
        
        // Only write to the file if a change was made
        if ($modified_contents !== $file_contents) {
            file_put_contents($auth_file, $modified_contents);
        }
    }
}
add_action('admin_init', 'cs_update_registration_handler', 20);

// Alternative approach - directly modify the user registration function
function cs_modify_user_role($user_id) {
    // This hook runs immediately after a user is registered
    // We'll use it to override the default role assignment
    
    // Only modify users registered through our form
    // We can check a transient that we set during registration
    $is_cs_registration = get_transient('cs_user_registration_' . $user_id);
    
    if ($is_cs_registration) {
        // Get the selected role from options
        $role = get_option('cs_registration_role', 'subscriber');
        
        // Update the user's role
        $user = new WP_User($user_id);
        $user->set_role($role);
        
        // Clean up the transient
        delete_transient('cs_user_registration_' . $user_id);
    }
}
add_action('user_register', 'cs_modify_user_role', 20); // Priority 20 to run after default role assignment