<?php
/**
 * Add login and registration form to Club Sales Dashboard
 */

// Modify the dashboard shortcode to show login/registration when not logged in
function cs_modified_dashboard() {
    if (!is_user_logged_in()) {
        ob_start();
        cs_login_registration_form();
        return ob_get_clean();
    }
    
    // Call the original dashboard function
    return CS_Shortcodes::dashboard();
}
function cs_set_child_user_global() {
    $current_user = wp_get_current_user();
    
    // Check if the current user is a child user
    if (in_array('club_child_user', $current_user->roles)) {
        // Add a script to set the global variable
        wp_add_inline_script('cs-child-scripts', 
            'window.CS_Child_Manager = window.CS_Child_Manager || {}; 
             window.CS_Child_Manager.is_child_user = true;', 
            'before'
        );
    }
}
add_action('wp_enqueue_scripts', 'cs_set_child_user_global');

function cs_vendor_registration_redirect() {
    // Check if we're on the English vendor registration page
    if (is_page('vendor-registeration') && current_user_is_wcfm_vendor()) {
        // Redirect to the Swedish store manager page
        wp_redirect('https://klubbforsaljning.se/sv/store-manager/');
        exit;
    }
}
add_action('template_redirect', 'cs_vendor_registration_redirect');

function current_user_is_wcfm_vendor() {
    // Check if the current user has the WCFM vendor role
    $current_user = wp_get_current_user();
    return in_array('wcfm_vendor', $current_user->roles);
}

// Remove the original shortcode and add the modified one
function cs_replace_dashboard_shortcode() {
    remove_shortcode('club_sales_dashboard');
    add_shortcode('club_sales_dashboard', 'cs_modified_dashboard');
}
add_action('init', 'cs_replace_dashboard_shortcode', 20); // Priority 20 to run after the original shortcode is registered

// Login and registration form HTML
function cs_login_registration_form() {
    // Get any error messages if they exist
    $login_error = isset($_GET['login_error']) ? urldecode($_GET['login_error']) : '';
    $register_error = isset($_GET['register_error']) ? urldecode($_GET['register_error']) : '';
    $register_success = isset($_GET['register_success']) ? urldecode($_GET['register_success']) : '';
    
    // Get available activity types for dropdown (these can be configured in the admin)
    $activity_categories = array(
        'sports' => array(
            'Kampsport/Brottning/Boxning',
            'Kennelklubb/Hundsport',
            'Konståkning/Skridsko/Curling',
            'Kör/Musik',
            'Motorsport',
            'Orientering',
            'Racketsport',
            'Scouting',
            'Simning/Vattensport',
            'Skidor',
            'Skolklass',
            'Skytte',
            'Volleyboll',
            'Övrigt'
        ),
        'activities' => array(
            'Alpint',
            'Amerikansk Fotboll/Rugby',
            'Bandy',
            'Basket',
            'Bobol/Baseball/Softball',
            'Bowling',
            'Cykling',
            'Dans',
            'Flersektionsförening',
            'Fotboll',
            'Friidrott/Löpning',
            'Friluftsträmjandet',
            'Fäktning',
            'Golf',
            'Gymnastik',
            'Handboll',
            'Hästsport',
            'Innebandy',
            'Ishockey/Ringette'
        )
    );
    
    // Add necessary styles
    echo '<style>
    .cs-auth-container {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        max-width: 1200px;
        margin: 40px auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    
    .cs-auth-box {
        flex: 1;
        min-width: 300px;
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .cs-auth-title {
        font-size: 24px;
        margin-bottom: 20px;
        color: #4CAF50;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .cs-auth-form .form-row {
        margin-bottom: 15px;
    }
    
    .cs-auth-form .input-label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }
    
    .cs-auth-form input[type="text"],
    .cs-auth-form input[type="email"],
    .cs-auth-form input[type="password"],
    .cs-auth-form select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        color: #333;
    }
    
    .cs-auth-form input[type="text"]:focus,
    .cs-auth-form input[type="email"]:focus,
    .cs-auth-form input[type="password"]:focus,
    .cs-auth-form select:focus {
        border-color: #4CAF50;
        outline: none;
    }
    
    .cs-auth-button {
        display: inline-block;
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
        transition: background-color 0.2s;
        text-decoration: none;
        margin-top: 10px;
    }
    
    .cs-auth-button:hover {
        background-color: #45a049;
    }
    
    .cs-auth-message {
        padding: 10px 15px;
        margin: 15px 0;
        border-radius: 4px;
    }
    
    .cs-auth-error {
        background-color: #fff2f0;
        border-left: 4px solid #f44336;
        color: #721c24;
    }
    
    .cs-auth-success {
        background-color: #f0f9eb;
        border-left: 4px solid #4CAF50;
        color: #155724;
    }
    
    .cs-section-title {
        font-size: 18px;
        margin: 25px 0 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
        color: #4CAF50;
    }
    
    .cs-form-row-50 {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .cs-form-row-50 > div {
        flex: 1;
        min-width: 200px;
    }
    
    .cs-dropdown {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .cs-dropdown .cs-dropdown-item {
        padding: 8px 12px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .cs-dropdown .cs-dropdown-item:hover {
        background-color: #f5f5f5;
    }
    
    .cs-dropdown .cs-dropdown-item.selected {
        background-color: #e3f2fd;
    }
    
    /* Secondary dropdown container */
    .cs-activities-dropdown {
        display: none;
        max-height: 300px;
        overflow-y: auto;
    }
    
    /* For Social Security Number formatting */
    .ssn-input {
        letter-spacing: 1px;
        font-family: monospace;
    }
    
    @media (max-width: 768px) {
        .cs-auth-container {
            flex-direction: column;
        }
    }
    </style>';
    
    // Container for both login and registration forms
    echo '<div class="cs-auth-container">';
    
    // Login form
    echo '<div class="cs-auth-box cs-login-box">';
    echo '<h2 class="cs-auth-title">Login</h2>';
    
    if (!empty($login_error)) {
        echo '<div class="cs-auth-message cs-auth-error">' . esc_html($login_error) . '</div>';
    }
    
    echo '<form class="cs-auth-form" action="' . esc_url(site_url('wp-login.php', 'login_post')) . '" method="post">';
    echo '<div class="form-row">';
    echo '<div class="input-label">Username or Email</div>';
    echo '<input type="text" id="cs-login-username" name="log" required>';
    echo '</div>';
    echo '<div class="form-row">';
    echo '<div class="input-label">Password</div>';
    echo '<input type="password" id="cs-login-password" name="pwd" required>';
    echo '</div>';
    echo '<input type="hidden" name="redirect_to" value="' . esc_url($_SERVER['REQUEST_URI']) . '">';
    echo '<button type="submit" class="cs-auth-button">Login</button>';
    echo '</form>';
    echo '<p><a href="' . esc_url(site_url('wp-login.php?action=lostpassword')) . '">Forgot your password?</a></p>';
    echo '</div>';
    
    // Registration form
    echo '<div class="cs-auth-box cs-register-box">';
    echo '<h2 class="cs-auth-title">Register</h2>';
    
    if (!empty($register_error)) {
        echo '<div class="cs-auth-message cs-auth-error">' . esc_html($register_error) . '</div>';
    }
    
    if (!empty($register_success)) {
        echo '<div class="cs-auth-message cs-auth-success">' . esc_html($register_success) . '</div>';
    }
    
    // Custom registration form with additional fields
    echo '<form class="cs-auth-form" action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
    echo '<input type="hidden" name="action" value="cs_register_user">';
    echo wp_nonce_field('cs_register_nonce', 'cs_register_nonce', true, false);
    
    // Personal Section
    echo '<h3 class="cs-section-title">Personal Section</h3>';
    
    // First name and Last name (side by side)
    echo '<div class="cs-form-row-50">';
    echo '<div class="form-row">';
    echo '<div class="input-label">First name</div>';
    echo '<input type="text" id="cs-register-firstname" name="firstname" required>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="input-label">Last name</div>';
    echo '<input type="text" id="cs-register-lastname" name="lastname" required>';
    echo '</div>';
    echo '</div>'; // End of cs-form-row-50
    
    // Social Security Number
    echo '<div class="form-row">';
    echo '<div class="input-label">Social Security Number</div>';
    echo '<input type="text" id="cs-register-ssn" name="ssn" class="ssn-input" placeholder="YYYYMMDD-XXXX" required pattern="[0-9]{8}-[0-9]{4}">';
    echo '</div>';
    
    // Email
    echo '<div class="form-row">';
    echo '<div class="input-label">Email</div>';
    echo '<input type="email" id="cs-register-email" name="email" required>';
    echo '</div>';
    
    // Username and password (automatically generated, invisible to user)
    echo '<input type="hidden" id="cs-register-username" name="username">';
    
    // Password fields
    echo '<div class="form-row">';
    echo '<div class="input-label">Password</div>';
    echo '<input type="password" id="cs-register-password" name="password" required>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="input-label">Confirm Password</div>';
    echo '<input type="password" id="cs-register-confirm-password" name="confirm_password" required>';
    echo '</div>';
    
    // Group Section
    echo '<h3 class="cs-section-title">Group Section</h3>';
    
    // School/Association
    echo '<div class="form-row">';
    echo '<div class="input-label">School/Association</div>';
    echo '<input type="text" id="cs-register-school" name="school" required>';
    echo '</div>';
    
    // Team/Class
    echo '<div class="form-row">';
    echo '<div class="input-label">Team/Class</div>';
    echo '<input type="text" id="cs-register-team" name="team" required>';
    echo '</div>';
    
    // Activity Type Dropdown
    echo '<div class="form-row">';
    echo '<div class="input-label">We are a:</div>';
    echo '<select id="cs-register-activity-type" name="activity_type" required>';
    echo '<option value="">Select activity type...</option>';
    
    // Sports Category
    echo '<optgroup label="Sports">';
    foreach ($activity_categories['sports'] as $activity) {
        echo '<option value="' . esc_attr($activity) . '">' . esc_html($activity) . '</option>';
    }
    echo '</optgroup>';
    
    // Activities Category
    echo '<optgroup label="Activities">';
    foreach ($activity_categories['activities'] as $activity) {
        echo '<option value="' . esc_attr($activity) . '">' . esc_html($activity) . '</option>';
    }
    echo '</optgroup>';
    
    echo '</select>';
    echo '</div>';
    
    echo '<input type="hidden" name="redirect_to" value="' . esc_url($_SERVER['REQUEST_URI']) . '">';
    echo '<button type="submit" class="cs-auth-button">Register</button>';
    echo '</form>';
    echo '</div>'; // End of registration box
    
    echo '</div>'; // End of container
    
    // Add JavaScript for form handling
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Auto-generate username from email
        const emailField = document.getElementById("cs-register-email");
        const usernameField = document.getElementById("cs-register-username");
        
        if (emailField && usernameField) {
            emailField.addEventListener("change", function() {
                // Take the part before @ and use it as username
                let email = this.value.trim();
                if (email) {
                    let username = email.split("@")[0];
                    // Clean up the username - only allow alphanumeric and underscore
                    username = username.replace(/[^a-z0-9_]/gi, "");
                    // Add a random number to make it more unique
                    username = username + Math.floor(Math.random() * 1000);
                    usernameField.value = username;
                }
            });
        }
        
        // Format Social Security Number
        const ssnField = document.getElementById("cs-register-ssn");
        
        if (ssnField) {
            ssnField.addEventListener("input", function(e) {
                let value = e.target.value.replace(/[^0-9]/g, ""); // Remove non-digits
                
                if (value.length > 8) {
                    // Format as YYYYMMDD-XXXX
                    value = value.substring(0, 8) + "-" + value.substring(8, 12);
                }
                
                e.target.value = value;
            });
        }
    });
    </script>';
}

// Handle user registration
function cs_handle_user_registration() {
    // Check if this is a registration request
    if (isset($_POST['action']) && $_POST['action'] === 'cs_register_user') {
        // Verify nonce
        if (!isset($_POST['cs_register_nonce']) || !wp_verify_nonce($_POST['cs_register_nonce'], 'cs_register_nonce')) {
            $error = 'Security verification failed. Please try again.';
            wp_redirect(add_query_arg('register_error', urlencode($error), $_POST['redirect_to']));
            exit;
        }
        
        // Get form data
        $firstname = sanitize_text_field($_POST['firstname']);
        $lastname = sanitize_text_field($_POST['lastname']);
        $ssn = sanitize_text_field($_POST['ssn']);
        $email = sanitize_email($_POST['email']);
        $school = sanitize_text_field($_POST['school']);
        $team = sanitize_text_field($_POST['team']);
        $activity_type = sanitize_text_field($_POST['activity_type']);
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $redirect_to = $_POST['redirect_to'];
        
        // Basic validation
        if (empty($firstname) || empty($lastname) || empty($ssn) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
            wp_redirect(add_query_arg('register_error', urlencode($error), $redirect_to));
            exit;
        }
        
        // Validate SSN format (YYYYMMDD-XXXX)
        if (!preg_match('/^\d{8}-\d{4}$/', $ssn)) {
            $error = 'Social Security Number must be in the format YYYYMMDD-XXXX.';
            wp_redirect(add_query_arg('register_error', urlencode($error), $redirect_to));
            exit;
        }
        
        // Ensure username is not empty (auto-generated from email)
        if (empty($username)) {
            $username = substr($email, 0, strpos($email, '@')) . rand(100, 999);
        }
        
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
            wp_redirect(add_query_arg('register_error', urlencode($error), $redirect_to));
            exit;
        }
        
        if (username_exists($username)) {
            $error = 'Username already exists. Please use a different email.';
            wp_redirect(add_query_arg('register_error', urlencode($error), $redirect_to));
            exit;
        }
        
        if (email_exists($email)) {
            $error = 'Email already registered. Please use a different email or login.';
            wp_redirect(add_query_arg('register_error', urlencode($error), $redirect_to));
            exit;
        }
        
        // Create the user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            $error = $user_id->get_error_message();
            wp_redirect(add_query_arg('register_error', urlencode($error), $redirect_to));
            exit;
        }
        
        // Set the role based on admin settings
        $role = get_option('cs_registration_role', 'subscriber');
        $user = new WP_User($user_id);
        $user->set_role($role);
        
        // Save additional user meta
        update_user_meta($user_id, 'first_name', $firstname);
        update_user_meta($user_id, 'last_name', $lastname);
        update_user_meta($user_id, 'cs_ssn', $ssn);
        update_user_meta($user_id, 'cs_school', $school);
        update_user_meta($user_id, 'cs_team', $team);
        update_user_meta($user_id, 'cs_activity_type', $activity_type);
        
        // SEND ADMIN NOTIFICATION DIRECTLY HERE
        $admin_email = get_option('admin_email');
        
        // Prepare email details
        $subject = "New {$role} Registration - Club Sales";
        
        // HTML Email Body
        $message = sprintf(
            '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">' .
            '<div style="background-color: #4CAF50; color: white; padding: 15px; text-align: center;">' .
            '<h2>New User Registration</h2>' .
            '</div>' .
            '<div style="padding: 20px; background-color: #f9f9f9;">' .
            '<p><strong>User Details:</strong></p>' .
            '<ul>' .
            '<li><strong>Name:</strong> %s %s</li>' .
            '<li><strong>Email:</strong> %s</li>' .
            '<li><strong>Role:</strong> %s</li>' .
            '<li><strong>School/Association:</strong> %s</li>' .
            '<li><strong>Team/Class:</strong> %s</li>' .
            '<li><strong>Activity Type:</strong> %s</li>' .
            '</ul>' .
            '</div>' .
            '<div style="background-color: #4CAF50; color: white; padding: 10px; text-align: center;">' .
            '<p>View user details in the WordPress admin panel</p>' .
            '</div>' .
            '</body></html>',
            esc_html($firstname),
            esc_html($lastname),
            esc_html($email),
            esc_html($role),
            esc_html($school),
            esc_html($team),
            esc_html($activity_type)
        );

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            "From: Club Sales Registration <{$admin_email}>"
        );

        // Send email
        $email_sent = wp_mail($admin_email, $subject, $message, $headers);

        // Log email sending result
        if ($email_sent) {
            error_log("Registration notification email sent to admin for {$role} user {$user_id}");
        } else {
            error_log("Failed to send registration notification email for {$role} user {$user_id}");
        }
        
        // Send notification to admin
        wp_new_user_notification($user_id, null, 'admin');
        
        // Success message
        $success = 'Registration successful! You can now login.';
        wp_redirect(add_query_arg('register_success', urlencode($success), $redirect_to));
        exit;
    }
}
add_action('admin_post_nopriv_cs_register_user', 'cs_handle_user_registration');
add_action('admin_post_cs_register_user', 'cs_handle_user_registration');

// Function to check login errors and redirect with custom message
function cs_login_failed_redirect($username) {
    // Get the referrer URL (where the login form was submitted from)
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : home_url();
    
    // Don't redirect if on wp-login.php
    if (strstr($referrer, 'wp-login.php')) {
        return;
    }
    
    // Add error message
    $error = 'Invalid username or password. Please try again.';
    wp_redirect(add_query_arg('login_error', urlencode($error), $referrer));
    exit;
}
add_action('wp_login_failed', 'cs_login_failed_redirect');

// Redirect after logout
function cs_redirect_after_logout() {
    // Get the page with the club_sales_dashboard shortcode
    // This is a simplistic approach - in a real implementation, 
    // you might want to store the page ID in an option
    global $wpdb;
    $page = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_content LIKE %s AND post_type = 'page' AND post_status = 'publish' LIMIT 1",
            '%[club_sales_dashboard]%'
        )
    );
    
    if ($page) {
        $redirect_url = get_permalink($page);
    } else {
        $redirect_url = home_url();
    }
    
    wp_redirect($redirect_url);
    exit;
}
add_action('wp_logout', 'cs_redirect_after_logout');