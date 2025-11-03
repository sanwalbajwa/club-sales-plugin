<?php
/**
 * Add login and registration form to Club Sales Dashboard
 */

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
        wp_add_inline_script('cs-child-scripts', 
            'window.CS_Child_Manager = window.CS_Child_Manager || {}; 
             window.CS_Child_Manager.is_child_user = true;', 
            'before'
        );
    }
}
add_action('wp_enqueue_scripts', 'cs_set_child_user_global');

function cs_vendor_registration_redirect() {
    if (is_page('vendor-registeration') && current_user_is_wcfm_vendor()) {
        wp_redirect('https://klubbforsaljning.se/sv/store-manager/');
        exit;
    }
}
add_action('template_redirect', 'cs_vendor_registration_redirect');

function current_user_is_wcfm_vendor() {
    $current_user = wp_get_current_user();
    return in_array('wcfm_vendor', $current_user->roles);
}

// Remove the original shortcode and add the modified one
function cs_replace_dashboard_shortcode() {
    remove_shortcode('club_sales_dashboard');
    add_shortcode('club_sales_dashboard', 'cs_modified_dashboard');
}
add_action('init', 'cs_replace_dashboard_shortcode', 20);

// Login and registration form HTML
function cs_login_registration_form() {
    $login_error = isset($_GET['login_error']) ? urldecode($_GET['login_error']) : '';
    $register_error = isset($_GET['register_error']) ? urldecode($_GET['register_error']) : '';
    $register_success = isset($_GET['register_success']) ? urldecode($_GET['register_success']) : '';
    
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
            'Friluftsfrämjandet',
            'Fäktning',
            'Golf',
            'Gymnastik',
            'Handboll',
            'Hästsport',
            'Innebandy',
            'Ishockey/Ringette'
        )
    );
    
    // Add styles matching the reference design
    echo '<style>
    /* Background and container */
    body {
        background: linear-gradient(135deg, #f9fafb 0%, #ffffff 50%, #f3f4f6 100%) !important;
        min-height: 100vh !important;
        position: relative !important;
        overflow-x: hidden !important;
    }
    
    /* Background decorative elements */
    body::before {
        content: "" !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background-image: radial-gradient(circle, #00a73d 1px, transparent 1px) !important;
        background-size: 30px 30px !important;
        opacity: 0.03 !important;
        z-index: 0 !important;
        pointer-events: none !important;
    }
    
    /* Floating circles */
    .cs-auth-container::before {
        content: "" !important;
        position: fixed !important;
        top: 5rem !important;
        left: 2.5rem !important;
        width: 8rem !important;
        height: 8rem !important;
        background: linear-gradient(135deg, #00a73d, #00c94a) !important;
        opacity: 0.2 !important;
        filter: blur(2rem) !important;
        border-radius: 50% !important;
        z-index: 0 !important;
        animation: float 6s ease-in-out infinite !important;
    }
    
    .cs-auth-container::after {
        content: "" !important;
        position: fixed !important;
        top: 10rem !important;
        right: 5rem !important;
        width: 10rem !important;
        height: 10rem !important;
        background: linear-gradient(135deg, #00c94a, #00a73d) !important;
        opacity: 0.15 !important;
        filter: blur(3rem) !important;
        border-radius: 50% !important;
        z-index: 0 !important;
        animation: float-delayed 7s ease-in-out infinite !important;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }
    
    @keyframes float-delayed {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-30px); }
    }
    
    /* Header */
    .cs-auth-header {
        padding-top: 30px;
        text-align: center !important;
        margin-bottom: 2rem !important;
        position: relative !important;
        z-index: 0 !important;
    }
    
    .cs-logo-icon {
        width: 64px !important;
        height: 64px !important;
        margin: 0 auto 0.75rem !important;
        background: linear-gradient(135deg, #00a73d 0%, #00c94a 100%) !important;
        border-radius: 1rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 10px 25px rgba(0, 167, 61, 0.3) !important;
    }
    
    .cs-logo-icon .dashicons {
        font-size: 32px !important;
        color: white !important;
        width: 32px !important;
        height: 32px !important;
    }
    
    .cs-main-title {
        color: #00a73d !important;
        font-size: 18px !important;
        font-weight: 400 !important;
        margin: 0 0 0.5rem 0 !important;
    }
    
    .cs-main-subtitle {
        color: #6b7280 !important;
        font-size: 15px !important;
        margin: 0 !important;
    }
    
    /* Container */
    .cs-auth-container {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)) !important;
        gap: 1.5rem !important;
        max-width: 1200px !important;
        margin: 40px auto !important;
        padding: 0 1rem !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
        position: relative !important;
        z-index: 0 !important;
    }
    
    /* Form boxes */
    .cs-auth-box {
        background: rgba(255, 255, 255, 0.7) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        padding: 2rem !important;
        border-radius: 1.5rem !important;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        transition: all 0.3s ease !important;
        position: relative !important;
        z-index: 10 !important;
    }
    
    .cs-auth-box:hover {
        box-shadow: 0 20px 60px rgba(0, 167, 61, 0.15) !important;
    }
//     .cs-auth-box-1 {
// 		padding: 0rem 2rem 2rem 2rem;
// 	}
    /* Form header */
    .cs-form-header {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        margin-bottom: 1.5rem !important;
    }
    
    .cs-form-icon {
        width: 40px !important;
        height: 40px !important;
        background: linear-gradient(135deg, #00a73d, #00c94a) !important;
        border-radius: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 4px 12px rgba(0, 167, 61, 0.3) !important;
    }
    
    .cs-form-icon .dashicons {
        font-size: 20px !important;
        color: white !important;
        width: 20px !important;
        height: 20px !important;
    }
    
    .cs-auth-title {
        font-size: 18px !important;
        margin: 0 !important;
        color: #00a73d !important;
        font-weight: 500 !important;
    }
    
    /* Form elements */
    .cs-auth-form .form-row {
        margin-bottom: 1.25rem !important;
    }
    
    .cs-auth-form .input-label {
        display: block !important;
        margin-bottom: 0.5rem !important;
        font-weight: 500 !important;
        color: #374151 !important;
        font-size: 14px !important;
    }
    
    .cs-input-wrapper {
        position: relative !important;
    }
    
    .cs-input-icon {
        position: absolute !important;
        left: 12px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: #9ca3af !important;
        font-size: 20px !important;
        width: 20px !important;
        height: 20px !important;
        pointer-events: none !important;
    }
    
    .cs-auth-form input[type="text"],
    .cs-auth-form input[type="email"],
    .cs-auth-form input[type="password"],
    .cs-auth-form select {
        width: 100% !important;
        padding: 12px 16px 12px 44px !important;
        border: 2px solid #e5e7eb !important;
        border-radius: 0.75rem !important;
        font-size: 14px !important;
        color: #374151 !important;
        background: white !important;
        transition: all 0.3s ease !important;
    }
    
    .cs-auth-form input:focus,
    .cs-auth-form select:focus {
        outline: none !important;
        border-color: #00a73d !important;
        box-shadow: 0 0 0 4px rgba(0, 167, 61, 0.1) !important;
    }
    
    .cs-auth-form input:focus ~ .cs-input-icon {
        color: #00a73d !important;
    }
    
    /* Checkbox */
    .cs-checkbox-wrapper {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        margin-bottom: 1.5rem !important;
    }
    
    .cs-checkbox-wrapper input[type="checkbox"] {
        width: 18px !important;
        height: 18px !important;
        cursor: pointer !important;
        accent-color: #00a73d !important;
        margin: 0 !important;
    }
    
    .cs-checkbox-wrapper label {
        font-size: 14px !important;
        color: #374151 !important;
        margin: 0 !important;
        cursor: pointer !important;
    }
    
    /* Buttons */
    .cs-auth-button {
        width: 100% !important;
        background: linear-gradient(90deg, #00a73d 0%, #00c94a 100%) !important;
        color: white !important;
        border: none !important;
        padding: 1rem 1.5rem !important;
        border-radius: 9999px !important;
        cursor: pointer !important;
        font-size: 15px !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 4px 12px rgba(0, 167, 61, 0.3) !important;
        margin-top: 0.5rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
    }
    
    .cs-auth-button:hover {
        transform: scale(1.02) !important;
        box-shadow: 0 6px 16px rgba(0, 167, 61, 0.4) !important;
        background: linear-gradient(90deg, #008f36 0%, #00b343 100%) !important;
    }
    
    .cs-auth-button .dashicons {
        font-size: 20px !important;
        width: 20px !important;
        height: 20px !important;
    }
    
    /* Messages */
    .cs-auth-message {
        padding: 12px 16px !important;
        margin: 0 0 1.25rem 0 !important;
        border-radius: 0.75rem !important;
        font-size: 14px !important;
    }
    
    .cs-auth-error {
        background-color: #fef2f2 !important;
        border-left: 4px solid #ef4444 !important;
        color: #dc2626 !important;
    }
    
    .cs-auth-success {
        background-color: #f0fdf4 !important;
        border-left: 4px solid #10b981 !important;
        color: #059669 !important;
    }
    
    /* Links */
    .cs-auth-box a {
        color: #ef4444 !important;
        text-decoration: none !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        transition: color 0.3s ease !important;
    }
    
    .cs-auth-box a:hover {
        color: #dc2626 !important;
        text-decoration: underline !important;
    }
    
    /* Section titles */
    .cs-section-title {
        font-size: 16px !important;
        margin: 1.5rem 0 1rem !important;
        padding-bottom: 0.5rem !important;
        border-bottom: 1px solid #e5e7eb !important;
        color: #00a73d !important;
        font-weight: 600 !important;
    }
    
    /* Two column layout for name fields */
    .cs-form-row-50 {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 1rem !important;
        margin-bottom: 1.25rem !important;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .cs-auth-container {
            grid-template-columns: 1fr !important;
            max-width: 550px !important;
        }
    }
    
    @media (max-width: 768px) {
        .cs-auth-container {
            margin: 1.5rem 1rem !important;
        }
        
        .cs-auth-box {
            padding: 1.75rem 1.5rem !important;
        }
        
        .cs-form-row-50 {
            grid-template-columns: 1fr !important;
        }
        
        .cs-main-title {
            font-size: 22px !important;
        }
        
        .cs-main-subtitle {
            font-size: 14px !important;
        }
    }
    </style>';
    
    // Header with logo
    echo '<div class="cs-auth-header">';
    echo '<div class="cs-logo-icon"><span class="dashicons dashicons-groups"></span></div>';
    echo '<h1 class="cs-main-title">Välkommen</h1>';
    echo '<p class="cs-main-subtitle">Logga in eller skapa ett nytt konto</p>';
    echo '</div>';
    
    // Container for both login and registration forms
    echo '<div class="cs-auth-container">';
    
    // Login form
    echo '<div class="cs-auth-box-1">';
	echo '<div class="cs-auth-box cs-login-box">';
    echo '<div class="cs-form-header">';
    echo '<div class="cs-form-icon"><span class="dashicons dashicons-lock"></span></div>';
    echo '<h2 class="cs-auth-title">' . __('Logga In', 'club-sales') . '</h2>';
    echo '</div>';
    
    if (!empty($login_error)) {
        echo '<div class="cs-auth-message cs-auth-error">' . esc_html($login_error) . '</div>';
    }
    
    echo '<form class="cs-auth-form" action="' . esc_url(site_url('wp-login.php', 'login_post')) . '" method="post">';
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Användarnamn eller mailadress', 'club-sales') . '</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<span class="dashicons dashicons-email cs-input-icon"></span>';
    echo '<input type="text" id="cs-login-username" name="log" required>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Lösenord', 'club-sales') . '</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<span class="dashicons dashicons-lock cs-input-icon"></span>';
    echo '<input type="password" id="cs-login-password" name="pwd" required>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="cs-checkbox-wrapper">';
    echo '<input type="checkbox" name="remember" id="remember-me">';
    echo '<label style="color:#374151 !important" for="remember-me">' . __('Kom ihåg mig', 'club-sales') . '</label>';
    echo '</div>';
    
    echo '<input type="hidden" name="redirect_to" value="' . esc_url($_SERVER['REQUEST_URI']) . '">';
    echo '<button type="submit" class="cs-auth-button">';
    echo '<span>' . __('Logga In', 'club-sales') . '</span>';
    echo '<span class="dashicons dashicons-arrow-right-alt2"></span>';
    echo '</button>';
    echo '</form>';
    
    echo '<p style="text-align: center; margin-top: 1rem;"><a href="' . esc_url(site_url('wp-login.php?action=lostpassword')) . '">' . __('Glömt ditt lösenord?', 'club-sales') . '</a></p>';
    echo '</div>';
	echo '</div>';
    
    // Registration form
    echo '<div class="cs-auth-box cs-register-box">';
    echo '<div class="cs-form-header">';
    echo '<div class="cs-form-icon"><span class="dashicons dashicons-groups"></span></div>';
    echo '<h2 class="cs-auth-title">' . __('Registrera dig', 'club-sales') . '</h2>';
    echo '</div>';
    
    if (!empty($register_error)) {
        echo '<div class="cs-auth-message cs-auth-error">' . esc_html($register_error) . '</div>';
    }
    
    if (!empty($register_success)) {
        echo '<div class="cs-auth-message cs-auth-success">' . esc_html($register_success) . '</div>';
    }
    
    echo '<form class="cs-auth-form" action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
    echo '<input type="hidden" name="action" value="cs_register_user">';
    echo wp_nonce_field('cs_register_nonce', 'cs_register_nonce', true, false);
    
    // Personal Section
    echo '<h3 class="cs-section-title">' . __('Personuppgifter till ansvarig', 'club-sales') . '</h3>';
    
    // First name and Last name
    echo '<div class="cs-form-row-50">';
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Förnamn', 'club-sales') . '</div>';
    echo '<input type="text" id="cs-register-firstname" name="firstname" required>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Efternamn', 'club-sales') . '</div>';
    echo '<input type="text" id="cs-register-lastname" name="lastname" required>';
    echo '</div>';
    echo '</div>';
    
    // Social Security Number
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Personnummer', 'club-sales') . '</div>';
	echo '<div class="cs-input-wrapper">';
	echo '<span class="dashicons dashicons-lock cs-input-icon"></span>';
    echo '<input type="text" id="cs-register-ssn" name="ssn" class="ssn-input" placeholder="YYYYMMDD-XXXX" required pattern="[0-9]{8}-[0-9]{4}">';
	echo '</div>';
    echo '</div>';
    
    // Mobile number
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Mobilnummer', 'club-sales') . '</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<span class="dashicons dashicons-phone cs-input-icon"></span>';
    echo '<input type="text" id="cs-register-phone" name="phone" required>';
    echo '</div>';
    echo '</div>';
    
    // Email
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Email', 'club-sales') . '</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<span class="dashicons dashicons-email cs-input-icon"></span>';
    echo '<input type="email" id="cs-register-email" name="email" required>';
    echo '</div>';
    echo '</div>';
    
    // Password fields
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Lösenord', 'club-sales') . '</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<span class="dashicons dashicons-lock cs-input-icon"></span>';
    echo '<input type="password" id="cs-register-password" name="password" required>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Bekräfta Lösenord', 'club-sales') . '</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<span class="dashicons dashicons-lock cs-input-icon"></span>';
    echo '<input type="password" id="cs-register-confirm-password" name="confirm_password" required>';
    echo '</div>';
    echo '</div>';
    
    // Group Section
    echo '<h3 class="cs-section-title">' . __('Gruppuppgifter', 'club-sales') . '</h3>';
    
    // School/Association
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Förening, lag eller klass', 'club-sales') . '</div>';
    echo '<input type="text" id="cs-register-school" name="school" required>';
    echo '</div>';
    
    // Team/Class
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Lag eller klass', 'club-sales') . '</div>';
    echo '<input type="text" id="cs-register-team" name="team" required>';
    echo '</div>';
    
    // Activity Type Dropdown
    echo '<div class="form-row">';
    echo '<div class="input-label">' . __('Aktivitetstyp', 'club-sales') . '</div>';
    echo '<select id="cs-register-activity-type" name="activity_type" required>';
    echo '<option value="">' . __('Välj aktivitetstyp...', 'club-sales') . '</option>';
    
    echo '<optgroup label="Sports">';
    foreach ($activity_categories['sports'] as $activity) {
        echo '<option value="' . esc_attr($activity) . '">' . esc_html($activity) . '</option>';
    }
    echo '</optgroup>';
    
    echo '<optgroup label="Activities">';
    foreach ($activity_categories['activities'] as $activity) {
        echo '<option value="' . esc_attr($activity) . '">' . esc_html($activity) . '</option>';
    }
    echo '</optgroup>';
    
    echo '</select>';
    echo '</div>';
    
    echo '<input type="hidden" id="cs-register-username" name="username">';
    echo '<input type="hidden" name="redirect_to" value="' . esc_url($_SERVER['REQUEST_URI']) . '">';
    
    echo '<button type="submit" class="cs-auth-button">';
    echo '<span>' . __('Registrera dig', 'club-sales') . '</span>';
    echo '<span class="dashicons dashicons-arrow-right-alt2"></span>';
    echo '</button>';
    echo '</form>';
    echo '</div>';
    
    echo '</div>'; // End container
    
    // JavaScript for form handling
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Auto-generate username from email
        const emailField = document.getElementById("cs-register-email");
        const usernameField = document.getElementById("cs-register-username");
        
        if (emailField && usernameField) {
            emailField.addEventListener("change", function() {
                let email = this.value.trim();
                if (email) {
                    let username = email.split("@")[0];
                    username = username.replace(/[^a-z0-9_]/gi, "");
                    username = username + Math.floor(Math.random() * 1000);
                    usernameField.value = username;
                }
            });
        }
        
        // Format Social Security Number
        const ssnField = document.getElementById("cs-register-ssn");
        
        if (ssnField) {
            ssnField.addEventListener("input", function(e) {
                let value = e.target.value.replace(/[^0-9]/g, "");
                
                if (value.length > 8) {
                    value = value.substring(0, 8) + "-" + value.substring(8, 12);
                }
                
                e.target.value = value;
            });
        }
        
        // Password confirmation validation
        const passwordField = document.getElementById("cs-register-password");
        const confirmPasswordField = document.getElementById("cs-register-confirm-password");
        
        if (confirmPasswordField) {
            confirmPasswordField.addEventListener("blur", function() {
                if (passwordField.value !== confirmPasswordField.value && confirmPasswordField.value) {
                    confirmPasswordField.style.borderColor = "#ef4444";
                    alert("Lösenorden matchar inte");
                } else if (passwordField.value === confirmPasswordField.value && confirmPasswordField.value) {
                    confirmPasswordField.style.borderColor = "#00a73d";
                }
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

