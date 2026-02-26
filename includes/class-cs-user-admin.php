<?php
/**
 * Add custom user profile fields to WordPress admin
 */

// Add fields to user profile page
function cs_add_custom_user_fields($user) {
    // Get user data
    $ssn = get_user_meta($user->ID, 'cs_ssn', true);
    $school = get_user_meta($user->ID, 'cs_school', true);
    $team = get_user_meta($user->ID, 'cs_team', true);
    $activity_type = get_user_meta($user->ID, 'cs_activity_type', true);
    ?>
    <h3>Club Sales Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="cs_ssn">Social Security Number</label></th>
            <td>
                <input type="text" name="cs_ssn" id="cs_ssn" value="<?php echo esc_attr($ssn); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="cs_school">School/Association</label></th>
            <td>
                <input type="text" name="cs_school" id="cs_school" value="<?php echo esc_attr($school); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="cs_team">Team/Class</label></th>
            <td>
                <input type="text" name="cs_team" id="cs_team" value="<?php echo esc_attr($team); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="cs_activity_type">Activity Type</label></th>
            <td>
                <input type="text" name="cs_activity_type" id="cs_activity_type" value="<?php echo esc_attr($activity_type); ?>" class="regular-text" />
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'cs_add_custom_user_fields');
add_action('edit_user_profile', 'cs_add_custom_user_fields');

// Save custom user profile fields
function cs_save_custom_user_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    // Update the user meta
    if (isset($_POST['cs_ssn'])) {
        update_user_meta($user_id, 'cs_ssn', sanitize_text_field($_POST['cs_ssn']));
    }
    
    if (isset($_POST['cs_school'])) {
        update_user_meta($user_id, 'cs_school', sanitize_text_field($_POST['cs_school']));
    }
    
    if (isset($_POST['cs_team'])) {
        update_user_meta($user_id, 'cs_team', sanitize_text_field($_POST['cs_team']));
    }
    
    if (isset($_POST['cs_activity_type'])) {
        update_user_meta($user_id, 'cs_activity_type', sanitize_text_field($_POST['cs_activity_type']));
    }
}
add_action('personal_options_update', 'cs_save_custom_user_fields');
add_action('edit_user_profile_update', 'cs_save_custom_user_fields');

// Add custom columns to users list table
function cs_add_user_columns($columns) {
    $columns['cs_school'] = 'School/Association';
    $columns['cs_team'] = 'Team/Class';
    $columns['cs_activity_type'] = 'Activity Type';
    
    return $columns;
}
add_filter('manage_users_columns', 'cs_add_user_columns');

// Display custom column data in users list table
function cs_show_user_columns_data($value, $column_name, $user_id) {
    switch ($column_name) {
        case 'cs_school':
            return get_user_meta($user_id, 'cs_school', true);
        case 'cs_team':
            return get_user_meta($user_id, 'cs_team', true);
        case 'cs_activity_type':
            return get_user_meta($user_id, 'cs_activity_type', true);
        default:
            return $value;
    }
}
add_filter('manage_users_custom_column', 'cs_show_user_columns_data', 10, 3);

// Add custom fields to the users table filters
function cs_add_user_filter_dropdown() {
    // Only show on the users.php page
    $screen = get_current_screen();
    if ($screen->id != 'users') {
        return;
    }
    
    // Get all unique activity types
    global $wpdb;
    $activity_types = $wpdb->get_col(
        "SELECT DISTINCT meta_value FROM {$wpdb->usermeta} 
        WHERE meta_key = 'cs_activity_type' AND meta_value != '' 
        ORDER BY meta_value ASC"
    );
    
    // Create dropdown
    if (!empty($activity_types)) {
        $current_activity = isset($_GET['cs_activity_filter']) ? $_GET['cs_activity_filter'] : '';
        ?>
        <label class="screen-reader-text" for="cs_activity_filter">Filter by activity type</label>
        <select name="cs_activity_filter" id="cs_activity_filter">
            <option value="">All Activity Types</option>
            <?php foreach ($activity_types as $activity) : ?>
                <option value="<?php echo esc_attr($activity); ?>" <?php selected($current_activity, $activity); ?>>
                    <?php echo esc_html($activity); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    // Get all unique schools
    $schools = $wpdb->get_col(
        "SELECT DISTINCT meta_value FROM {$wpdb->usermeta} 
        WHERE meta_key = 'cs_school' AND meta_value != '' 
        ORDER BY meta_value ASC"
    );
    
    // Create dropdown
    if (!empty($schools)) {
        $current_school = isset($_GET['cs_school_filter']) ? $_GET['cs_school_filter'] : '';
        ?>
        <label class="screen-reader-text" for="cs_school_filter">Filter by school</label>
        <select name="cs_school_filter" id="cs_school_filter">
            <option value="">All Schools/Associations</option>
            <?php foreach ($schools as $school) : ?>
                <option value="<?php echo esc_attr($school); ?>" <?php selected($current_school, $school); ?>>
                    <?php echo esc_html($school); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}
add_action('restrict_manage_users', 'cs_add_user_filter_dropdown');

// Filter users by custom fields
function cs_filter_users_by_custom_fields($query) {
    global $pagenow;
    
    // Only run on the users.php page
    if ($pagenow != 'users.php') {
        return;
    }
    
    // Filter by activity type
    if (isset($_GET['cs_activity_filter']) && !empty($_GET['cs_activity_filter'])) {
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key' => 'cs_activity_type',
                'value' => sanitize_text_field($_GET['cs_activity_filter']),
                'compare' => '='
            )
        );
        
        // Add existing meta query if it exists
        if (isset($query->query_vars['meta_query'])) {
            $meta_query[] = $query->query_vars['meta_query'];
        }
        
        $query->query_vars['meta_query'] = $meta_query;
    }
    
    // Filter by school
    if (isset($_GET['cs_school_filter']) && !empty($_GET['cs_school_filter'])) {
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key' => 'cs_school',
                'value' => sanitize_text_field($_GET['cs_school_filter']),
                'compare' => '='
            )
        );
        
        // Add existing meta query if it exists
        if (isset($query->query_vars['meta_query'])) {
            $meta_query[] = $query->query_vars['meta_query'];
        }
        
        $query->query_vars['meta_query'] = $meta_query;
    }
}
add_action('pre_get_users', 'cs_filter_users_by_custom_fields');