<?php
/**
 * Add login and registration form to Club Sales Dashboard
 */

function cs_modified_dashboard() {
    // If user is not logged in, show login/registration form
    if (!is_user_logged_in()) {
        ob_start();
        cs_login_registration_form();
        return ob_get_clean();
    }
    
    // Get current user
    $current_user = wp_get_current_user();
    // Check if user has store_vendor role - restrict them from dashboard
    if (in_array('wcfm_vendor', $current_user->roles)) {
        ob_start();
        cs_vendor_restricted_message();
        return ob_get_clean();
    }
    
    // Call the original dashboard function for authorized users
    return CS_Shortcodes::dashboard();
}

/**
 * Display restricted access message for store vendors
 */
function cs_vendor_restricted_message() {
    echo '<div class="cs-auth-container" style="max-width: 600px; margin: 40px auto; padding: 20px;">';
    echo '<div class="cs-auth-box" style="background: #ffffff; padding: 32px; border-radius: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); text-align: center;">';
    echo '<div class="cs-form-icon" style="width: 60px; height: 60px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">';
    echo '<svg class="cs-form-icon-svg" style="width: 30px; height: 30px;" fill="white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
    echo '</div>';
    echo '<h2 style="color: #ef4444; margin-bottom: 16px; font-size: 22px;">' . __('Access Restricted', 'club-sales') . '</h2>';
    echo '<p style="color: #6b7280; font-size: 16px; margin-bottom: 20px;">' . __('Store vendors cannot access the seller dashboard. Please complete the registration process first.', 'club-sales') . '</p>';
    echo '<p style="color: #6b7280; font-size: 14px; margin-bottom: 24px;">' . __('If you need to register as a seller, please fill out the registration form.', 'club-sales') . '</p>';
    
    // Add registration form or link
    echo '<div style="margin-top: 20px;">';
    echo '<a href="' . wp_logout_url(get_permalink()) . '" class="cs-submit-btn" style="display: inline-block; background: #00b853; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500;">' . __('Logout and Register', 'club-sales') . '</a>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
}

function cs_set_child_user_global() {
    $current_user = wp_get_current_user();
    
    // Check if the current user is a child user
    if (in_array('club_child_user', $current_user->roles)) {
        // Add to cs-scripts (loads first) so it's available immediately
        wp_add_inline_script('cs-scripts', 
            'window.CS_Child_Manager = window.CS_Child_Manager || {}; 
             window.CS_Child_Manager.is_child_user = true;', 
            'before'
        );
        
        // Also add to cs-child-scripts for backward compatibility
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
    
    // Add styles matching the Figma design exactly
    echo '<style>
    /* Reset and base styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
   /* Main Body Background */
    body {
        position: relative;
        min-height: 100vh !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
        overflow-x: hidden;
    }
    
    /* Background Pattern */
    body::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: radial-gradient(circle, #00a73d 1px, transparent 1px);
        background-size: 30px 30px;
        opacity: 0.03;
        z-index: 0;
    }
    
    /* Floating Circles */
    body::after {
        content: "";
        position: absolute;
        top: 5rem;
        left: 2.5rem;
        width: 8rem;
        height: 8rem;
        background: linear-gradient(135deg, #00a73d, #00c94a);
        opacity: 0.2;
        filter: blur(2rem);
        border-radius: 50%;
        z-index: 0;
        animation: float 6s ease-in-out infinite;
    }
    
    /* Floating animation */
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }
    
    /* Header section */
    .cs-auth-header {
        text-align: center !important;
        padding: 40px 20px 30px !important;
        max-width: 100% !important;
        margin: 0 auto !important;
    }
    
    .cs-logo-icon {
        width: 60px !important;
        height: 60px !important;
        margin: 0 auto 16px !important;
        background: #00b853 !important;
        border-radius: 12px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 4px 12px rgba(0, 184, 83, 0.25) !important;
    }
    
    .cs-logo-svg {
        width: 32px !important;
        height: 32px !important;
		fill: transparent !important;
    }
    
    .cs-main-title {
        color: #00b853 !important;
        font-size: 22px !important;
        font-weight: 500 !important;
        margin: 0 0 8px 0 !important;
        letter-spacing: -0.02em !important;
    }
    
    .cs-main-subtitle {
        color: #6b7280 !important;
        font-size: 15px !important;
        font-weight: 400 !important;
        margin: 0 !important;
    }
    
    /* Container */
    .cs-auth-container {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 400px), 1fr)) !important;
        gap: 24px !important;
        max-width: 1100px !important;
        margin: 0 auto !important;
        padding: 0 20px 40px !important;
    }
    
    /* Form boxes */
    .cs-auth-box {
        background: #ffffff !important;
        padding: 32px !important;
        border-radius: 16px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
    }
    
    /* Form header */
    .cs-form-header {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        margin-bottom: 24px !important;
    }
    
    .cs-form-icon {
        width: 40px !important;
        height: 40px !important;
        background: #00b853 !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
    }
    
    .cs-form-icon-svg {
        width: 20px !important;
        height: 20px !important;
		fill: transparent !important;
    }
    
    .cs-auth-title {
        font-size: 18px !important;
        margin: 0 !important;
        color: #00b853 !important;
        font-weight: 500 !important;
        letter-spacing: -0.01em !important;
    }
    
    /* Form elements */
    .cs-auth-form .form-row {
        margin-bottom: 16px !important;
    }
    
    .cs-auth-form .input-label {
        display: block !important;
        margin-bottom: 8px !important;
        font-weight: 500 !important;
        color: #374151 !important;
        font-size: 17px !important;
    }
    
    .cs-input-wrapper {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
    }
    
    .cs-input-icon {
        position: absolute !important;
        left: 14px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        width: 20px !important;
        height: 20px !important;
        pointer-events: none !important;
        z-index: 1 !important;
		fill: transparent !important;
    }
    
    .cs-input-icon path {
        fill: transparent !important;
    }
    
    .cs-auth-form input[type="text"],
    .cs-auth-form input[type="email"],
    .cs-auth-form input[type="password"],
    .cs-auth-form select {
        width: 100% !important;
        padding: 12px 14px 12px 44px !important;
        border: 1.5px solid #e5e7eb !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        color: #374151 !important;
        background: #ffffff !important;
        transition: all 0.2s ease !important;
        font-family: inherit !important;
    }
    
    .cs-auth-form input::placeholder {
        color: #9ca3af !important;
    }
    
    .cs-auth-form input:focus,
    .cs-auth-form select:focus {
        outline: none !important;
        border-color: #00b853 !important;
        box-shadow: 0 0 0 3px rgba(0, 184, 83, 0.1) !important;
    }
    
    .cs-auth-form input:focus + .cs-input-icon path,
    .cs-auth-form select:focus + .cs-input-icon path {
        fill: transparent !important;
    }
    
    /* Checkbox */
    .cs-checkbox-wrapper {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin-bottom: 20px !important;
    }
    
    .cs-checkbox-wrapper input[type="checkbox"] {
        width: 18px !important;
        height: 18px !important;
        cursor: pointer !important;
        accent-color: #00b853 !important;
        margin: 0 !important;
        padding: 0 !important;
        border-radius: 4px !important;
    }
    
    /* Buttons */
    .cs-auth-button {
        width: 100% !important;
        background: #00b853 !important;
        color: white !important;
        border: none !important;
        padding: 20px 24px !important;
        border-radius: 9999px !important;
        cursor: pointer !important;
        font-size: 15px !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 2px 8px rgba(0, 184, 83, 0.25) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        font-family: inherit !important;
    }
    
    .cs-auth-button:hover {
        background: #00a84c !important;
        box-shadow: 0 4px 12px rgba(0, 184, 83, 0.35) !important;
        transform: translateY(-1px) !important;
    }
    
    .cs-auth-button:active {
        transform: translateY(0) !important;
    }
    
    .cs-button-icon {
        width: 18px !important;
        height: 18px !important;
    }
    
    /* Messages */
    .cs-auth-message {
        padding: 12px 16px !important;
        margin: 0 0 20px 0 !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        line-height: 1.5 !important;
    }
    
    .cs-auth-error {
        background-color: #fef2f2 !important;
        border-left: 3px solid #ef4444 !important;
        color: #dc2626 !important;
    }
    
    .cs-auth-success {
        background-color: #f0fdf4 !important;
        border-left: 3px solid #10b981 !important;
        color: #059669 !important;
    }
    
    /* Links */
    .cs-auth-box a {
        color: #ef4444 !important;
        text-decoration: none !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        transition: color 0.2s ease !important;
    }
    
    .cs-auth-box a:hover {
        color: #dc2626 !important;
        text-decoration: underline !important;
    }
    
    /* Section titles */
    .cs-section-header {
        display: flex !important;
		flex-direction: row;
        align-items: center !important;
		justify-content: flex-start;
        gap: 8px !important;
        margin: 24px 0 16px !important;
        padding-bottom: 8px !important;
        border-bottom: 1px solid #e5e7eb !important;
    }
    
    .cs-section-icon {
        width: 18px !important;
        height: 18px !important;
        flex-shrink: 0 !important;
    }
    
    .cs-section-icon path {
        fill: #00b853 !important;
    }
    
    .cs-section-title {
        font-size: 15px !important;
        margin: 0 !important;
        color: #00b853 !important;
        font-weight: 500 !important;
    }
    
	.cs-section-header h3.cs-section-title
	 {
		color: #00b853 !important;
	}
	
    /* Two column layout for name fields */
    .cs-form-row-50 {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
        margin-bottom: 16px !important;
    }
    .elementor-261 .elementor-element.elementor-element-e0c7930 label,
form.cs-auth-form label,
.cs-checkbox-wrapper label {
    font-size: 14px !important;
    color: #6b7280 !important;
    margin: 0 !important;
    cursor: pointer !important;
    font-weight: 400 !important;
}

    /* Responsive */
    @media (max-width: 1024px) {
        .cs-auth-container {
            grid-template-columns: 1fr !important;
            max-width: 500px !important;
        }
    }
    
    @media (max-width: 768px) {
        .cs-auth-container {
            grid-template-columns: 1fr !important;
            padding: 0 16px 40px !important;
            max-width: 100% !important;
        }
        
        .cs-auth-box {
            padding: 24px 16px !important;
        }
        
        .cs-form-row-50 {
            grid-template-columns: 1fr !important;
        }
    }
    
    @media (max-width: 640px) {
        .cs-auth-container {
            padding: 0 16px 40px !important;
        }
        
        .cs-auth-box {
            padding: 24px 20px !important;
        }
        
        .cs-form-row-50 {
            grid-template-columns: 1fr !important;
        }
        
        .cs-main-title {
            font-size: 20px !important;
        }
        
        .cs-main-subtitle {
            font-size: 14px !important;
        }
    }
    </style>';
    
    // Header with logo
    echo '<div class="cs-auth-header">';
    echo '<div class="cs-logo-icon">';
    echo '<svg class="cs-logo-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M8.5 11C10.7091 11 12.5 9.20914 12.5 7C12.5 4.79086 10.7091 3 8.5 3C6.29086 3 4.5 4.79086 4.5 7C4.5 9.20914 6.29086 11 8.5 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M17 11L19 13L23 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '</div>';
    echo '<h1 class="cs-main-title">Välkommen</h1>';
    echo '<p class="cs-main-subtitle">Logga in eller skapa ett nytt konto</p>';
    echo '</div>';
    
    // Container for both login and registration forms
    echo '<div class="cs-auth-container">';
    
    // Login form
    echo '<div>';
    echo '<div class="cs-auth-box cs-login-box">';
    echo '<div class="cs-form-header">';
    echo '<div class="cs-form-icon">';
    echo '<svg class="cs-form-icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M19 11H5M12 4L5 11L12 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '</div>';
    echo '<h2 class="cs-auth-title">Logga In</h2>';
    echo '</div>';
    
    if (!empty($login_error)) {
        echo '<div class="cs-auth-message cs-auth-error">' . esc_html($login_error) . '</div>';
    }
    
    echo '<form class="cs-auth-form" action="' . esc_url(site_url('wp-login.php', 'login_post')) . '" method="post">';
    echo '<div class="form-row">';
    echo '<div class="input-label">Användarnamn eller mailadress</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<svg class="cs-input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M22 6L12 13L2 6" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '<input type="text" id="cs-login-username" name="log" required>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="input-label">Lösenord</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<svg class="cs-input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '<input type="password" id="cs-login-password" name="pwd" required>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="cs-checkbox-wrapper">';
    echo '<input type="checkbox" name="rememberme" id="remember-me">';
    echo '<label for="remember-me">Kom ihåg mig</label>';
    echo '</div>';
    
    echo '<input type="hidden" name="redirect_to" value="' . esc_url($_SERVER['REQUEST_URI']) . '">';
    echo '<button type="submit" class="cs-auth-button">';
    echo '<span>Logga In</span>';
    echo '<svg class="cs-button-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '</button>';
    echo '</form>';
    
    echo '<p style="text-align: center; margin-top: 16px;"><a href="' . esc_url(site_url('/reset-password')) . '">Glömt lösenord?</a></p>';
    echo '</div>';
    echo '</div>';
    
    // Registration form
    echo '<div class="cs-auth-box cs-register-box">';
    echo '<div class="cs-form-header">';
    echo '<div class="cs-form-icon">';
    echo '<svg class="cs-form-icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M8.5 11C10.7091 11 12.5 9.20914 12.5 7C12.5 4.79086 10.7091 3 8.5 3C6.29086 3 4.5 4.79086 4.5 7C4.5 9.20914 6.29086 11 8.5 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M20 8V14M17 11H23" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '</div>';
    echo '<h2 class="cs-auth-title">Registrera dig</h2>';
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
    echo '<div class="cs-section-header">';
    echo '<svg class="cs-section-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" fill="#00b853"/>';
    echo '<circle cx="12" cy="7" r="4" fill="#00b853"/>';
    echo '</svg>';
    echo '<h3 class="cs-section-title">Personuppgifter till ansvarig</h3>';
    echo '</div>';
    
    // First name and Last name
    echo '<div class="cs-form-row-50">';
    echo '<div class="form-row">';
    echo '<div class="input-label">Förnamn</div>';
    echo '<input type="text" id="cs-register-firstname" name="firstname" required>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="input-label">Efternamn</div>';
    echo '<input type="text" id="cs-register-lastname" name="lastname" required>';
    echo '</div>';
    echo '</div>';
    
    // Social Security Number
    echo '<div class="form-row">';
    echo '<div class="input-label">Personnummer</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<svg class="cs-input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '<input type="text" id="cs-register-ssn" name="ssn" class="ssn-input" placeholder="YYYYMMDD-XXXX" required pattern="[0-9]{8}-[0-9]{4}">';
    echo '</div>';
    echo '</div>';
    
    // Mobile number
    echo '<div class="form-row">';
    echo '<div class="input-label">Mobilnummer</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<svg class="cs-input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M22 16.92V19.92C22.0011 20.1985 21.9441 20.4742 21.8325 20.7293C21.7209 20.9845 21.5573 21.2136 21.3521 21.4019C21.1468 21.5901 20.9046 21.7335 20.6407 21.8227C20.3769 21.9119 20.0974 21.9451 19.82 21.92C16.7428 21.5856 13.787 20.5341 11.19 18.85C8.77382 17.3147 6.72533 15.2662 5.18999 12.85C3.49997 10.2412 2.44824 7.27099 2.11999 4.17997C2.095 3.90344 2.12787 3.62474 2.21649 3.3616C2.30512 3.09846 2.44756 2.85666 2.63476 2.6516C2.82196 2.44653 3.0498 2.28268 3.30379 2.17052C3.55777 2.05836 3.83233 2.00026 4.10999 1.99997H7.10999C7.5953 1.9952 8.06579 2.16705 8.43376 2.48351C8.80173 2.79996 9.04207 3.23942 9.10999 3.71997C9.23662 4.68004 9.47144 5.6227 9.80999 6.52997C9.94454 6.8879 9.97366 7.27689 9.8939 7.65086C9.81415 8.02482 9.62886 8.36809 9.35999 8.63998L8.08999 9.90997C9.51355 12.4135 11.5864 14.4864 14.09 15.91L15.36 14.64C15.6319 14.3711 15.9751 14.1858 16.3491 14.1061C16.7231 14.0263 17.1121 14.0555 17.47 14.19C18.3773 14.5286 19.3199 14.7634 20.28 14.89C20.7658 14.9585 21.2094 15.2032 21.5265 15.5775C21.8437 15.9518 22.0122 16.4296 22 16.92Z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '<input type="text" id="cs-register-phone" name="phone" required>';
    echo '</div>';
    echo '</div>';
    
    // Email
    echo '<div class="form-row">';
    echo '<div class="input-label">Email</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<svg class="cs-input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M22 6L12 13L2 6" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '<input type="email" id="cs-register-email" name="email" required>';
    echo '</div>';
    echo '</div>';
    
    // Password fields
    echo '<div class="form-row">';
    echo '<div class="input-label">Lösenord</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<svg class="cs-input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '<input type="password" id="cs-register-password" name="password" required>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="form-row">';
    echo '<div class="input-label">Bekräfta Lösenord</div>';
    echo '<div class="cs-input-wrapper">';
    echo '<svg class="cs-input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '<path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '<input type="password" id="cs-register-confirm-password" name="confirm_password" required>';
    echo '</div>';
    echo '</div>';
    
    // Group Section
    echo '<div class="cs-section-header">';
    echo '<svg class="cs-section-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" fill="#00b853"/>';
    echo '<circle cx="9" cy="7" r="4" fill="#00b853"/>';
    echo '<path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" fill="#00b853"/>';
    echo '<path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" fill="#00b853"/>';
    echo '</svg>';
    echo '<h3 class="cs-section-title">Gruppuppgifter</h3>';
    echo '</div>';
    
    // School/Association
    echo '<div class="form-row">';
    echo '<div class="input-label">Förening, lag eller klass</div>';
    echo '<input type="text" id="cs-register-school" name="school" required>';
    echo '</div>';
    
    // Team/Class
//     echo '<div class="form-row">';
//     echo '<div class="input-label">Lag eller klass</div>';
//     echo '<input type="text" id="cs-register-team" name="team" required>';
//     echo '</div>';
    
    // Activity Type Dropdown
    echo '<div class="form-row">';
    echo '<div class="input-label">Aktivitetstyp</div>';
    echo '<select id="cs-register-activity-type" name="activity_type" required>';
    echo '<option value="">Välj aktivitetstyp...</option>';
    
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
    echo '<span>Registrera dig</span>';
    echo '<svg class="cs-button-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
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
                    confirmPasswordField.style.borderColor = "#00b853";
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