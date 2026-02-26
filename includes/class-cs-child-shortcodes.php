<?php
/**
 * Shortcodes for the Club Child Users
 */
class CS_Child_Shortcodes {
    /**
     * Initialize shortcodes
     */
    public static function init() {
        // Register shortcodes
        add_shortcode('cs_manage_children', array(__CLASS__, 'manage_children_shortcode'));
        add_shortcode('cs_assign_products', array(__CLASS__, 'assign_products_shortcode'));
        add_shortcode('cs_child_products', array(__CLASS__, 'child_products_shortcode'));
		add_shortcode('cs_sales_material', array(__CLASS__, 'sales_material_shortcode'));
		// Priority 5 to run BEFORE the main plugin's filter (priority 10) - ensures child users get filtered first
		add_filter('cs_dashboard_tabs', array(__CLASS__, 'modify_dashboard_tabs'), 5, 1);
        
        // Add action for notices
        add_action('cs_before_content', function() {
            // This will be filled by action hooks if needed
        });
        
        // Check for AJAX requests in assign products tab
        add_action('template_redirect', array(__CLASS__, 'handle_ajax_requests'));
    }
   
/**
 * Sales material shortcode
 * 
 * @return string Shortcode output
 */
public static function sales_material_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>' . __('Please log in to view sales materials.', 'club-sales') . '</p>';
    }
    
    wp_enqueue_script('cs-child-scripts');
    wp_enqueue_style('cs-child-styles');
    
    ob_start();
    self::sales_material_tab();
    return ob_get_clean();
}
public static function team_management_tab_content() {
    include CS_PLUGIN_DIR . 'templates/tabs/team-management.php';
}
public static function modify_dashboard_tabs($tabs) {
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    error_log('🔍 Dashboard Tabs Filter - User ID: ' . $current_user->ID . ', Roles: ' . implode(', ', $user_roles));
    
    // Check if current user is a child user
    if (in_array('club_child_user', $user_roles)) {
        error_log('✅ Child user detected! Filtering to 3 tabs only');
        
        // Child users ONLY see: Sales Material, Add Order, Orders (Beställningar)
        $child_tabs = array();
        
        // 1. Sales Material (Säljmaterial) - First tab, default active
        $child_tabs['sales-material'] = array(
            'icon' => 'dashicons-media-document',
            'title' => __('Säljmaterial', 'club-sales'),
            'content_callback' => array('CS_Child_Shortcodes', 'sales_material_tab')
        );
        
        // 2. Add Order (Lägg till order)
        if (isset($tabs['add-order'])) {
            $child_tabs['add-order'] = $tabs['add-order'];
        }
        
        // 3. Orders (Beställningar)
        $child_tabs['orders'] = array(
            'icon' => 'dashicons-list-view',
            'title' => __('Beställningar', 'club-sales'),
            'content_callback' => array('CS_Child_Shortcodes', 'child_orders_tab')
        );
        
        error_log('✅ Returning ' . count($child_tabs) . ' tabs for child user');
        return $child_tabs;
    }
    
    error_log('⚪ Not a child user, passing through tabs');
    // If not a child user, return original tabs
    return $tabs;
}

/**
 * Sales material tab content
 */
public static function sales_material_tab($specific_product_id = 0, $override_child_id = 0) {
    include CS_PLUGIN_DIR . 'templates/tabs/sales-material.php';
}
	 
    /**
     * Handle AJAX requests for assign products tab
     */
    public static function handle_ajax_requests() {
        // Check if this is an AJAX request
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            // We only want to output the content, not the full page
            add_filter('template_include', function($template) {
                return '';
            });
            
            // Disable admin bar for this request
            add_filter('show_admin_bar', '__return_false');
            
            // Output only the relevant content
            if (isset($_GET['child_id'])) {
                self::assign_products_tab();
                exit;
            }
        }
    }
    
    /**
     * Manage children shortcode
     * 
     * @return string Shortcode output
     */
    public static function manage_children_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to manage child users.', 'club-sales') . '</p>';
        }
        
        if (!CS_Child_Manager::can_manage_children()) {
            return '<p>' . __('You do not have permission to manage child users.', 'club-sales') . '</p>';
        }
        
        wp_enqueue_script('cs-child-scripts');
        wp_enqueue_style('cs-child-styles');
        
        ob_start();
        self::manage_children_tab();
        return ob_get_clean();
    }
    
    /**
     * Assign products shortcode
     * 
     * @return string Shortcode output
     */
    public static function assign_products_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to assign products to child users.', 'club-sales') . '</p>';
        }
        
        if (!CS_Child_Manager::can_manage_children()) {
            return '<p>' . __('You do not have permission to assign products to child users.', 'club-sales') . '</p>';
        }
        
        wp_enqueue_script('cs-child-scripts');
        wp_enqueue_style('cs-child-styles');
        
        ob_start();
        self::assign_products_tab();
        return ob_get_clean();
    }
    
    /**
     * Child products shortcode
     * 
     * @return string Shortcode output
     */
    public static function child_products_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your assigned products.', 'club-sales') . '</p>';
        }
        
        wp_enqueue_script('cs-child-scripts');
        wp_enqueue_style('cs-child-styles');
        
        ob_start();
        self::assigned_products_tab();
        return ob_get_clean();
    }
    /**
 * Child Orders Tab Content
 */
public static function child_orders_tab() {
    ?>
    <div class="cs-order-parent">
    <div class="cs-order-section-header">
        <div class="cs-header-left">
            <h2><?php _e('Dina ordrar', 'club-sales'); ?></h2>
        </div>
        
        <div class="cs-header-right">
            <div class="cs-filter-container">
                <select id="order-status-filter">
                    <option value=""><?php _e('Alla ordrar', 'club-sales'); ?></option>
                    <option value="pending"><?php _e('Väntande', 'club-sales'); ?></option>
                    <option value="ordered_from_supplier"><?php _e('Beställd från leverantör', 'club-sales'); ?></option>
                    <option value="completed"><?php _e('Slutförd', 'club-sales'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <!-- Orders Statistics Cards -->
    <div class="cs-orders-stats-container">
        <div class="cs-orders-stat-card">
            <div class="cs-stat-header">
                <div class="cs-stat-label"><?php _e('Totalt antal ordrar', 'club-sales'); ?></div>
                <div class="cs-stat-icon cs-stat-icon-orders">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="cs-stat-value" id="cs-orders-total-count">
                <span class="cs-loading-spinner"></span>
            </div>
        </div>

        <div class="cs-orders-stat-card">
            <div class="cs-stat-header">
                <div class="cs-stat-label"><?php _e('Total försäljning', 'club-sales'); ?></div>
                <div class="cs-stat-icon cs-stat-icon-sales">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
            </div>
            <div class="cs-stat-value" id="cs-orders-total-sales">
                <span class="cs-loading-spinner"></span>
            </div>
        </div>

        <div class="cs-orders-stat-card cs-stat-card-highlight">
            <div class="cs-stat-header">
                <div class="cs-stat-label"><?php _e('Total vinst', 'club-sales'); ?></div>
                <div class="cs-stat-icon cs-stat-icon-profit">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
            </div>
            <div class="cs-stat-value" id="cs-orders-total-profit">
                <span class="cs-loading-spinner"></span>
            </div>
        </div>
    </div>

    <div class="cs-orders-container">
        <table class="cs-orders-table">
            <thead>
                <tr>
                    <th><?php _e('Order #', 'club-sales'); ?></th>
                    <th><?php _e('Datum', 'club-sales'); ?></th>
                    <th><?php _e('Kundnamn', 'club-sales'); ?></th>
                    <th><?php _e('Säljare', 'club-sales'); ?></th>
                    <th><?php _e('Kunden betalar', 'club-sales'); ?></th>
                    <th><?php _e('NI betalar', 'club-sales'); ?></th>
                    <th><?php _e('Vinst', 'club-sales'); ?></th>  
                    <th><?php _e('Status', 'club-sales'); ?></th>
                    <th><?php _e('Alternativ', 'club-sales'); ?></th>
                </tr>
            </thead>
            <tbody id="orders-list">
                <tr>
                    <td colspan="9" class="cs-loading"><?php _e('Laddar ordrar...', 'club-sales'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>
    
    <?php include_once(plugin_dir_path(__FILE__) . '../templates/swish-qr-modal.html'); ?>
    <?php
}
    /**
     * Manage children tab content
     */
public static function manage_children_tab() {
    $user_id = get_current_user_id();
    $children = CS_Child_Manager::get_parent_children($user_id);
    
    // Get user's teams/classes
    global $wpdb;
    $teams = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cs_teams WHERE user_id = %d ORDER BY created_at DESC",
        $user_id
    ));
    ?>
<div class="cs-section">
    <div class="cs-form-container">
        <!-- NEW: Product-style Header -->
        <div class="cs-product-header">    
            <div class="cs-product-subheader">
                <!-- Info Banner -->
                <div class="cs-info-banner">
                    <div class="cs-info-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <div class="cs-info-text">
                        <strong><?php _e('Tips:', 'club-sales'); ?></strong> 
                        <?php _e('Skapa lag/klasser för att organisera dina säljare. Lägg sedan till säljare och tilldela dem till rätt lag.', 'club-sales'); ?>
                    </div>
                </div>
                
                <!-- Catalog Header with Icon -->
                <div class="cs-catalog-header">
                    <div class="cs-catalog-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="cs-catalog-info">
                        <h2><?php _e('Hantera Lag & Säljare', 'club-sales'); ?></h2>
                        <p><?php _e('Organisera dina säljare i lag och klasser', 'club-sales'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- KEEP EXISTING: Teams Section (slightly modified) -->
        <div class="cs-teams-header">
            <div class="cs-teams-header-left">
                <div class="cs-teams-header-info">
                    <h3 class="cs-subsection-title"><?php _e('Lag & Klasser', 'club-sales'); ?></h3>
                    <p class="cs-subsection-subtitle"><?php echo count($teams) > 0 
                        ? sprintf(_n('%d lag/klass skapad', '%d lag/klasser skapade', count($teams), 'club-sales'), count($teams))
                        : __('Inga lag/klasser skapade ännu', 'club-sales'); 
                    ?></p>
                </div>
            </div>
            <button class="cs-add-team-btn" id="cs-open-add-team-modal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <?php _e('Nytt Lag/Klass', 'club-sales'); ?>
            </button>
        </div>


            <!-- Teams List or Empty State -->
            <?php if (empty($teams)) : ?>
                <div class="cs-teams-empty-state">
                    <div class="cs-empty-section-content">
                        <h3><?php _e('Redo att sätta igång?', 'club-sales'); ?></h3>
                        <p><?php _e('Skapa ditt första lag/klass för att komma igång!', 'club-sales'); ?></p>
                    </div>
                </div>
            <?php else : ?>
                <div class="cs-teams-grid" id="cs-teams-grid">
                    <?php foreach ($teams as $team) : 
                        // Get seller count for this team
                        $seller_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}cs_child_parent cp 
                             INNER JOIN {$wpdb->prefix}usermeta um ON cp.child_id = um.user_id 
                             WHERE cp.parent_id = %d AND um.meta_key = 'assigned_team' AND um.meta_value = %d",
                            $user_id,
                            $team->id
                        ));
                    ?>
                        <div class="cs-team-card" style="border : 2px solid <?php echo esc_attr($team->color); ?>" data-team-id="<?php echo esc_attr($team->id); ?>">
                            <div class="cs-teams-card-icon" style="background-color: <?php echo esc_attr($team->color); ?>">
                                <!-- Flag Icon -->
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                    <line x1="4" y1="22" x2="4" y2="15"></line>
                                </svg>
                            </div>
                            <div class="cs-team-card-content">
                                <h3><?php echo esc_html($team->name); ?></h3>
                                <p class="cs-team-sellers">
                                    <?php echo sprintf(_n('%d säljare', '%d säljare', $seller_count, 'club-sales'), $seller_count); ?>
                                </p>
                                <p class="cs-team-swish">Swish: <?php echo esc_html($team->swish_number); ?></p>
                            </div>
                            
                            <!-- Settings Dropdown Button -->
                            <div class="cs-team-settings">
                                <button class="cs-team-settings-btn" data-team-id="<?php echo esc_attr($team->id); ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" 
     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="12" cy="12" r="3"></circle>
  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .66.39 1.26 1 1.51H21a2 2 0 0 1 0 4h-.09c-.7 0-1.31.39-1.51 1z"/>
</svg>

                                </button>
                                <div class="cs-team-dropdown" data-team-id="<?php echo esc_attr($team->id); ?>">
                                    <button class="cs-edit-team-btn" data-team-id="<?php echo esc_attr($team->id); ?>" 
                                            data-team-name="<?php echo esc_attr($team->name); ?>"
                                            data-team-swish="<?php echo esc_attr($team->swish_number); ?>"
                                            data-team-color="<?php echo esc_attr($team->color); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        Redigera
                                    </button>
                                    <button class="cs-delete-team-btn" data-team-id="<?php echo esc_attr($team->id); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        Radera
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- EXISTING ADD SELLER SECTION WITH TEAM DROPDOWN -->
    <div class="cs-section">
        <div class="cs-form-container">
            <div class="cs-section-header">
                <div class="cs-section-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="cs-section-header-text">
                    <h2><?php _e('Lägg till säljare', 'club-sales'); ?></h2>
                    <p><?php _e('Skapa nya säljarkonton för er kampanj', 'club-sales'); ?></p>
                </div>
            </div>
            <form id="cs-add-child-form" class="cs-form">
                <?php wp_nonce_field('cciu_add_child', 'cciu_nonce'); ?>
                
                <div class="cs-form-row">
                    <div class="cs-form-group">
                        <label for="child_first_name"><?php _e('Förnamn', 'club-sales'); ?> <span class="required">*</span></label>
                        <input type="text" id="child_first_name" name="child_first_name" required>
                    </div>
                    
                    <div class="cs-form-group">
                        <label for="child_last_name"><?php _e('Efternamn', 'club-sales'); ?> <span class="required">*</span></label>
                        <input type="text" id="child_last_name" name="child_last_name" required>
                    </div>
                </div>
                
                <div class="cs-form-group">
                    <label for="child_email"><?php _e('Email Adress', 'club-sales'); ?> <span class="required">*</span></label>
                    <input type="email" id="child_email" name="child_email" required>
                </div>
                
                <div class="cs-form-group">
                    <label for="child_password"><?php _e('Lösenord', 'club-sales'); ?> <span class="required">*</span></label>
                    <div class="cs-password-container">
                        <input type="password" id="child_password" name="child_password" required>
                    </div>
                </div>
                
                <!-- NEW: Team Selection Dropdown -->
                <?php if (!empty($teams)) : ?>
                <div class="cs-form-group">
                    <label for="child_team"><?php _e('Välj Lag/Klass', 'club-sales'); ?></label>
                    <select id="child_team" name="child_team" class="cs-team-select">
						<option value="">Välj lag/klass...</option>
						<?php foreach ($teams as $team) : ?>
							<option value="<?php echo esc_attr($team->id); ?>">
								<?php echo esc_html($team->name); ?>
							</option>
						<?php endforeach; ?>
					</select>
                </div>
				 <div class="cs-password-suggestions">
                            <button type="button" id="generate-password" class="cs-generate-password-btn">
                                <?php _e('Generera Starkt Lösenord', 'club-sales'); ?>
                            </button>
                            <div id="password-suggestions-list" class="cs-password-suggestions-list"></div>
                        </div>
                <?php endif; ?>
                
                <div class="cs-form-actions">
                    <button type="submit" class="cs-submit-btn"><?php _e('Lägg Till Säljare', 'club-sales'); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- EXISTING MANAGE SELLERS SECTION -->
    <div class="cs-section">
        <div class="cs-form-container">
            <div class="cs-section-header cs-section-header-with-filter">
                <div class="cs-section-header-left">
                    <div class="cs-section-header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="cs-section-header-text">
                        <h2><?php _e('Administrera säljare', 'club-sales'); ?></h2>
                        <p id="cs-sellers-count-text"><?php printf(__('Visar %d av %d säljare', 'club-sales'), count($children), count($children)); ?></p>
                    </div>
                </div>
                
                <!-- Team Filter Dropdown -->
                <div class="cs-child-team-filter-container">
                    <label for="cs-team-filter" class="cs-child-filter-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        <?php _e('Filtrera:', 'club-sales'); ?>
                    </label>
                    <select id="cs-team-filter" class="cs-child-team-filter-select">
                        <option value=""><?php _e('Alla lag/klasser', 'club-sales'); ?></option>
                        <?php foreach ($teams as $team) : ?>
                            <option value="<?php echo esc_attr($team->id); ?>" data-color="<?php echo esc_attr($team->color); ?>">
                                <?php echo esc_html($team->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="cs-children-list">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Namn', 'club-sales'); ?></th>
                            <th><?php _e('Mejladress', 'club-sales'); ?></th>
                            <th><?php _e('Lag/Klass', 'club-sales'); ?></th>
                            <th><?php _e('Registrerad', 'club-sales'); ?></th>
                            <th><?php _e('Alternativ', 'club-sales'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="children-list">
                        <?php if (empty($children)): ?>
                            <tr>
                                <td colspan="5" class="cs-no-data"><?php _e('Inga säljare hittades.', 'club-sales'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($children as $child): 
                                $assigned_team_id = get_user_meta($child['id'], 'assigned_team', true);
                                $team_name = 'Inget lag';
                                $team_color = '#cccccc'; // Default gray for no team
                                if ($assigned_team_id) {
                                    $team = $wpdb->get_row($wpdb->prepare(
                                        "SELECT name, color FROM {$wpdb->prefix}cs_teams WHERE id = %d",
                                        $assigned_team_id
                                    ));
                                    if ($team) {
                                        $team_name = $team->name;
                                        $team_color = $team->color;
                                    }
                                }
                            ?>
                                <tr data-team-id="<?php echo esc_attr($assigned_team_id); ?>" data-child-id="<?php echo esc_attr($child['id']); ?>">
                                    <td><?php echo esc_html($child['name']); ?></td>
                                    <td class="td-email"><?php echo esc_html($child['email']); ?></td>
                                    <td>
                                        <div class="cs-team-badge">
                                            <span class="cs-team-color-dot" style="background-color: <?php echo esc_attr($team_color); ?>"></span>
                                            <?php echo esc_html($team_name); ?>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html(date('F d, Y', strtotime($child['registered']))); ?></td>
                                    <td class="cs-action-buttons">
                                        <button class="cs-edit-btn cs-action cs-edit-child" 
                                                data-child-id="<?php echo esc_attr($child['id']); ?>"
                                                data-child-name="<?php echo esc_attr($child['name']); ?>"
                                                data-child-email="<?php echo esc_attr($child['email']); ?>"
                                                data-child-team="<?php echo esc_attr($assigned_team_id); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            <?php _e('Redigera', 'club-sales'); ?>
                                        </button>
                                        <button class="cs-delete-btn cs-action cs-remove-child" data-child-id="<?php echo esc_attr($child['id']); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            <?php _e('Radera', 'club-sales'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Team Modal -->
    <div class="cs-modal" id="cs-add-team-modal" style="display: none;">
        <div class="cs-modal-overlay"></div>
        <div class="cs-modal-content">
            <div class="cs-modal-header">
                <div class="cs-modal-header-left">
                    <div class="cs-teams-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                            <line x1="4" y1="22" x2="4" y2="15"></line>
                        </svg>
                    </div>
                    <div>
                        <h3 id="cs-modal-title"><?php _e('Hantera Lag & Klasser', 'club-sales'); ?></h3>
                        <p id="cs-modal-team-count"><?php _e('Skapa ett nytt lag/klass', 'club-sales'); ?></p>
                    </div>
                </div>
                <button class="cs-modal-close" id="cs-close-add-team-modal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <form id="cs-add-team-form" class="cs-team-form">
                <?php wp_nonce_field('cs_add_team', 'cs_team_nonce'); ?>
                <input type="hidden" id="team_id" name="team_id" value="">
                
                <div class="cs-form-group">
                    <label for="team_name"><?php _e('Lag/Klass namn', 'club-sales'); ?> <span class="required">*</span></label>
                    <input type="text" id="team_name" name="team_name" 
                           placeholder="<?php _e('T.ex. Lag A, Klass 5B, Team Blå', 'club-sales'); ?>" required>
                </div>
                
                <div class="cs-form-group">
                    <label for="team_swish"><?php _e('Swish-nummer (Föreningens/Lagets)', 'club-sales'); ?></label>
                    <input type="text" id="team_swish" name="team_swish" 
                           placeholder="<?php _e('1234567890', 'club-sales'); ?>">
                    <p class="cs-form-help">
                        <?php _e('Föreningsnummer (10 siffror) eller mobilnummer (valfritt)', 'club-sales'); ?>
                    </p>
                </div>
                
                <div class="cs-form-group">
                    <label><?php _e('Välj Färg', 'club-sales'); ?></label>
                    <div class="cs-color-picker">
                        <label class="cs-color-option">
                            <input type="radio" name="team_color" value="#00B050" checked>
                            <span class="cs-color-swatch" style="background-color: #00B050;"></span>
                        </label>
                        <label class="cs-color-option">
                            <input type="radio" name="team_color" value="#3b82f6">
                            <span class="cs-color-swatch" style="background-color: #3b82f6;"></span>
                        </label>
                        <label class="cs-color-option">
                            <input type="radio" name="team_color" value="#ef4444">
                            <span class="cs-color-swatch" style="background-color: #ef4444;"></span>
                        </label>
                        <label class="cs-color-option">
                            <input type="radio" name="team_color" value="#8b5cf6">
                            <span class="cs-color-swatch" style="background-color: #8b5cf6;"></span>
                        </label>
                        <label class="cs-color-option">
                            <input type="radio" name="team_color" value="#f97316">
                            <span class="cs-color-swatch" style="background-color: #f97316;"></span>
                        </label>
                        <label class="cs-color-option">
                            <input type="radio" name="team_color" value="#ec4899">
                            <span class="cs-color-swatch" style="background-color: #ec4899;"></span>
                        </label>
                    </div>
                </div>
                
                <div class="cs-modal-actions">
                    <button type="submit" class="cs-btn-primary" id="cs-submit-team-btn">
                        <?php _e('Skapa Lag/Klass', 'club-sales'); ?>
                    </button>
                    <button type="button" class="cs-btn-secondary" id="cs-cancel-add-team">
                        <?php _e('Avbryt', 'club-sales'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Seller Modal -->
    <div class="cs-modal" id="cs-edit-child-modal" style="display: none;">
        <div class="cs-modal-overlay"></div>
        <div class="cs-modal-content">
            <div class="cs-modal-header">
                <div class="cs-modal-header-left">
                    <div class="cs-section-header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div>
                        <h3><?php _e('Redigera Säljare', 'club-sales'); ?></h3>
                        <p><?php _e('Uppdatera säljarens information', 'club-sales'); ?></p>
                    </div>
                </div>
                <button class="cs-modal-close" id="cs-close-edit-child-modal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <form id="cs-edit-child-form" class="cs-team-form">
                <?php wp_nonce_field('cs_edit_child', 'cs_edit_child_nonce'); ?>
                <input type="hidden" id="edit_child_id" name="child_id" value="">
                
                <div class="cs-form-group">
                    <label for="edit_child_name"><?php _e('Namn', 'club-sales'); ?> <span class="required">*</span></label>
                    <input type="text" id="edit_child_name" name="child_name" required>
                </div>
                
                <div class="cs-form-group">
                    <label for="edit_child_email"><?php _e('Email', 'club-sales'); ?> <span class="required">*</span></label>
                    <input type="email" id="edit_child_email" name="child_email" required>
                </div>
                
                <?php if (!empty($teams)) : ?>
                <div class="cs-form-group">
                    <label for="edit_child_team"><?php _e('Lag/Klass', 'club-sales'); ?></label>
                    <select id="edit_child_team" name="child_team" class="cs-team-select">
                        <option value="">Inget lag</option>
                        <?php foreach ($teams as $team) : ?>
                            <option value="<?php echo esc_attr($team->id); ?>">
                                <?php echo esc_html($team->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="cs-modal-actions">
                    <button type="submit" class="cs-btn-primary">
                        <?php _e('Spara Ändringar', 'club-sales'); ?>
                    </button>
                    <button type="button" class="cs-btn-secondary" id="cs-cancel-edit-child">
                        <?php _e('Avbryt', 'club-sales'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
}
    /**
     * Assign products tab content
     */
    public static function assign_products_tab() {
        $children = CS_Child_Manager::get_parent_children(get_current_user_id());
        $selected_child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;
        
        // Get WooCommerce products
        $products = [];
        if (function_exists('wc_get_products')) {
            $args = array(
                'limit' => 100,
                'status' => 'publish',
            );
            
            // If search term is provided
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $args['s'] = sanitize_text_field($_GET['search']);
            }
            
            $wc_products = wc_get_products($args);
            
            foreach ($wc_products as $product) {
                $products[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'sku' => $product->get_sku(),
                    'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')
                );
            }
        }
        
        // Get assigned products if a child is selected
        $assigned_products = [];
        if ($selected_child_id) {
            $assigned_products = CS_Child_Manager::get_child_products($selected_child_id);
            // Create a lookup array of assigned product IDs
            $assigned_product_ids = array_column($assigned_products, 'product_id');
        }
        
        // For AJAX requests, only output the child products section
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            if ($selected_child_id) {
                ?>
                <div class="cs-child-products">
                    <div class="cs-section-header">
                        <h3><?php _e('Currently Assigned Products', 'club-sales'); ?></h3>
                    </div>
                    
                    <div class="cs-assigned-products">
                        <?php if (empty($assigned_products)): ?>
                            <p class="cs-empty"><?php _e('No products assigned to this child user.', 'club-sales'); ?></p>
                        <?php else: ?>
                            <?php foreach ($assigned_products as $product): 
                                if (empty($product['name'])) continue; // Skip products without details
                            ?>
                                <div class="cs-assigned-product">
                                    <div class="cs-product-info">
                                        <span class="cs-product-name"><?php echo esc_html($product['name']); ?></span>
                                        <span class="cs-product-meta"><?php _e('SKU:', 'club-sales'); ?> <?php echo esc_html($product['sku'] ?? 'N/A'); ?></span>
                                        <span class="cs-product-price"><?php echo esc_html($product['price'] ?? '0'); ?> <?php echo esc_html(get_option('cs_settings')['currency'] ?? 'SEK'); ?></span>
                                    </div>
                                    <form method="post" class="unassign-form">
                                        <?php wp_nonce_field('unassign_product', 'cs_unassign_nonce'); ?>
                                        <input type="hidden" name="action" value="cs_unassign_product">
                                        <input type="hidden" name="child_id" value="<?php echo esc_attr($selected_child_id); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo esc_attr($product['product_id']); ?>">
                                        <button type="submit" class="cs-unassign-product">
                                            <span class="dashicons dashicons-no-alt"></span> <?php _e('Unassign', 'club-sales'); ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cs-section-header">
                        <h3><?php _e('Assign New Products', 'club-sales'); ?></h3>
                        <form method="get" class="cs-search-container">
                            <input type="hidden" name="ajax" value="1">
                            <input type="hidden" name="child_id" value="<?php echo esc_attr($selected_child_id); ?>">
                            <input type="text" name="search" id="product-search" placeholder="<?php _e('Search for products...', 'club-sales'); ?>" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
                            <button type="submit" id="search-button">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                        </form>
                    </div>
                    
                    <div class="cs-products-grid">
                        <?php if (empty($products)): ?>
                            <p class="cs-empty"><?php _e('No products found.', 'club-sales'); ?></p>
                        <?php else: ?>
                            <?php foreach ($products as $product): 
                                $is_assigned = !empty($assigned_product_ids) && in_array($product['id'], $assigned_product_ids);
                            ?>
                                <div class="cs-product-card <?php echo $is_assigned ? 'assigned' : ''; ?>">
                                    <div class="cs-product-image">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?>">
                                        <?php else: ?>
                                            <span class="dashicons dashicons-format-image"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cs-product-info">
                                        <div class="cs-product-name"><?php echo esc_html($product['name']); ?></div>
                                        <div class="cs-product-sku"><?php _e('SKU:', 'club-sales'); ?> <?php echo esc_html($product['sku'] ?? 'N/A'); ?></div>
                                        <div class="cs-product-price"><?php echo esc_html($product['price'] ?? '0'); ?> <?php echo esc_html(get_option('cs_settings')['currency'] ?? 'SEK'); ?></div>
                                    </div>
                                    <?php if ($is_assigned): ?>
                                        <form method="post" class="unassign-form">
                                            <?php wp_nonce_field('unassign_product', 'cs_unassign_nonce'); ?>
                                            <input type="hidden" name="action" value="cs_unassign_product">
                                            <input type="hidden" name="child_id" value="<?php echo esc_attr($selected_child_id); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo esc_attr($product['id']); ?>">
                                            <button type="submit" class="cs-assign-btn assigned">
                                                <?php _e('Unassign', 'club-sales'); ?>
                                            </button>
                                        </form>
									<?php else: ?>
									<div class="cs-assign-buttons">
										<form method="post" class="assign-form">
											<?php wp_nonce_field('assign_product', 'cs_assign_nonce'); ?>
											<input type="hidden" name="action" value="cs_assign_product">
											<input type="hidden" name="child_id" value="<?php echo esc_attr($selected_child_id); ?>">
											<input type="hidden" name="product_id" value="<?php echo esc_attr($product['id']); ?>">
											<button type="submit" class="cs-assign-btn">
												<?php _e('Assign', 'club-sales'); ?>
											</button>
										</form>

										<form method="post" class="assign-all-form">
											<?php wp_nonce_field('assign_product', 'cs_assign_nonce'); ?>
											<input type="hidden" name="action" value="cs_assign_product">
											<input type="hidden" name="child_id" value="all">
											<input type="hidden" name="product_id" value="<?php echo esc_attr($product['id']); ?>">
											<button type="submit" class="cs-assign-all-btn">
												<?php _e('Assign to All Children', 'club-sales'); ?>
											</button>
										</form>
									</div>
									<?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
            return;
        }
        
        // Regular full page output
        ?>
        <div class="cs-section">
            <div class="cs-section-header">
                <h2><?php _e('Assign Products to Child Users', 'club-sales'); ?></h2>
            </div>
            
            <?php if (empty($children)): ?>
                <p><?php _e('You have not added any child users yet. Add a child user first to assign products.', 'club-sales'); ?></p>
            <?php else: ?>
                <div class="cs-form-group">
                    <label for="child_id"><?php _e('Select Child User', 'club-sales'); ?></label>
                    <select id="child_id" name="child_id" class="child-select">
                        <option value=""><?php _e('Select a child user...', 'club-sales'); ?></option>
                        <?php foreach ($children as $child): ?>
                            <option value="<?php echo esc_attr($child['id']); ?>" <?php selected($selected_child_id, $child['id']); ?>>
                                <?php echo esc_html($child['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="cs-child-products" style="<?php echo $selected_child_id ? '' : 'display:none;'; ?>">
                    <?php if ($selected_child_id): ?>
                        <div class="cs-section-header">
                            <h3><?php _e('Currently Assigned Products', 'club-sales'); ?></h3>
                        </div>
                        
                        <div class="cs-assigned-products">
                            <?php if (empty($assigned_products)): ?>
                                <p class="cs-empty"><?php _e('No products assigned to this child user.', 'club-sales'); ?></p>
                            <?php else: ?>
                                <?php foreach ($assigned_products as $product): 
                                    if (empty($product['name'])) continue; // Skip products without details
                                ?>
                                    <div class="cs-assigned-product">
                                        <div class="cs-product-info">
                                            <span class="cs-product-name"><?php echo esc_html($product['name']); ?></span>
                                            <span class="cs-product-meta"><?php _e('SKU:', 'club-sales'); ?> <?php echo esc_html($product['sku'] ?? 'N/A'); ?></span>
                                            <span class="cs-product-price"><?php echo esc_html($product['price'] ?? '0'); ?> <?php echo esc_html(get_option('cs_settings')['currency'] ?? 'SEK'); ?></span>
                                        </div>
                                        <form method="post" class="unassign-form">
                                            <?php wp_nonce_field('unassign_product', 'cs_unassign_nonce'); ?>
                                            <input type="hidden" name="action" value="cs_unassign_product">
                                            <input type="hidden" name="child_id" value="<?php echo esc_attr($selected_child_id); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo esc_attr($product['product_id']); ?>">
                                            <button type="submit" class="cs-unassign-product">
                                                <span class="dashicons dashicons-no-alt"></span> <?php _e('Unassign', 'club-sales'); ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($selected_child_id): ?>
                <div class="cs-section">
                    <div class="cs-section-header">
                        <h3><?php _e('Sales Materials', 'club-sales'); ?></h3>
                    </div>
                    <div class="cs-sales-materials" style="display:none;">
                        <!-- Sales materials will be loaded here dynamically -->
                    </div>
                </div>
            <?php endif; ?>
                        <div class="cs-section-header">
                            <h3><?php _e('Assign New Products', 'club-sales'); ?></h3>
                            <form method="get" class="cs-search-container">
                                <input type="hidden" name="child_id" value="<?php echo esc_attr($selected_child_id); ?>">
                                <input type="text" name="search" id="product-search" placeholder="<?php _e('Search for products...', 'club-sales'); ?>" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
                                <button type="submit" id="search-button">
                                    <span class="dashicons dashicons-search"></span>
                                </button>
                            </form>
                        </div>
                        
                        <div class="cs-products-grid">
                            <?php if (empty($products)): ?>
                                <p class="cs-empty"><?php _e('No products found.', 'club-sales'); ?></p>
                            <?php else: ?>
                                <?php foreach ($products as $product): 
                                    $is_assigned = !empty($assigned_product_ids) && in_array($product['id'], $assigned_product_ids);
                                ?>
                                    <div class="cs-product-card <?php echo $is_assigned ? 'assigned' : ''; ?>">
                                        <div class="cs-product-image">
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?>">
                                            <?php else: ?>
                                                <span class="dashicons dashicons-format-image"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="cs-product-info">
                                            <div class="cs-product-name"><?php echo esc_html($product['name']); ?></div>
                                            <div class="cs-product-sku"><?php _e('SKU:', 'club-sales'); ?> <?php echo esc_html($product['sku'] ?? 'N/A'); ?></div>
                                            <div class="cs-product-price"><?php echo esc_html($product['price'] ?? '0'); ?> <?php echo esc_html(get_option('cs_settings')['currency'] ?? 'SEK'); ?></div>
                                        </div>
                                        <?php if ($is_assigned): ?>
                                            <form method="post" class="unassign-form">
                                                <?php wp_nonce_field('unassign_product', 'cs_unassign_nonce'); ?>
                                                <input type="hidden" name="action" value="cs_unassign_product">
                                                <input type="hidden" name="child_id" value="<?php echo esc_attr($selected_child_id); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo esc_attr($product['id']); ?>">
                                                <button type="submit" class="cs-assign-btn assigned">
                                                    <?php _e('Unassign', 'club-sales'); ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" class="assign-form">
                                                <?php wp_nonce_field('assign_product', 'cs_assign_nonce'); ?>
                                                <input type="hidden" name="action" value="cs_assign_product">
                                                <input type="hidden" name="child_id" value="<?php echo esc_attr($selected_child_id); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo esc_attr($product['id']); ?>">
                                                <button type="submit" class="cs-assign-btn">
                                                    <?php _e('Assign', 'club-sales'); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
   /**
 * Assigned products tab content (for child users)
 */
public static function assigned_products_tab() {
    $is_child = CS_Child_Manager::is_child_user();
    $user_id = get_current_user_id();
    
    $products = CS_Child_Manager::get_child_products($user_id);
    ?>
    <div class="cs-section">
        <div class="cs-section-header">
            <h2><?php _e('Your Assigned Products', 'club-sales'); ?></h2>
        </div>
        
        <?php if (empty($products)): ?>
            <p><?php _e('You do not have any assigned products yet.', 'club-sales'); ?></p>
        <?php else: ?>
            <div class="cs-products-container">
                <div class="cs-products-list" id="child-products-list">
                    <?php 
                    $first = true;
                    foreach ($products as $product): 
                        if (empty($product['name'])) continue; // Skip products without details
                        
                        // Add 'selected' class to the first product
                        $selected_class = $first ? 'selected' : '';
                        $first = false;

                        // Get WooCommerce product
                        $wc_product = wc_get_product($product['product_id']);
                        
                        // Get URL for the product page
                        // This is the key change - ensure we get a valid product URL
                        $product_url = $wc_product ? $wc_product->get_permalink() : '#';
                        
                        // Fallback URL generation if permalink fails
                        if ($product_url === '#' || empty($product_url)) {
                            $product_url = add_query_arg('product_id', $product['product_id'], home_url('/product'));
                        }
                    ?>
                        <div class="cs-product-card <?php echo $selected_class; ?>" 
                             data-id="<?php echo esc_attr($product['product_id']); ?>"
                             data-name="<?php echo esc_attr($product['name']); ?>"
                             data-total-price="<?php echo number_format($product['total_price'], 2, '.', ''); ?>"
                             data-sku="<?php echo esc_attr($product['sku'] ?? 'N/A'); ?>">
                            <div class="cs-product-image">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?>">
                                <?php else: ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                <?php endif; ?>
                            </div>
                            <div class="cs-product-info">
                                <div class="cs-product-name"><?php echo esc_html($product['name']); ?></div>
                                <div class="cs-product-sku">
                                    SKU: <?php echo esc_html($product['sku'] ?? 'N/A'); ?>
                                </div>
                                <div class="cs-product-price">
                                    <?php echo number_format($product['total_price'], 2); ?> <?php echo esc_html(get_option('cs_settings')['currency'] ?? 'SEK'); ?>
                                </div>
                                <div class="cs-product-info-actions">
                                    <a href="<?php echo esc_url($product_url); ?>" 
                                       class="cs-view-more-btn" 
                                       data-product-id="<?php echo esc_attr($product['product_id']); ?>"
                                       data-product-url="<?php echo esc_url($product_url); ?>"
                                       target="_blank">
                                        <?php _e('View Product', 'club-sales'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
				</div>
            </div>
            
            <script>
             jQuery(document).ready(function($) {
                // Add event listener to View More buttons
                $('.cs-view-more-btn').on('click', function(e) {
                    // Prevent any parent event handlers from triggering
                    e.preventDefault();
                    e.stopPropagation();

                    // Get the product URL directly from the data attribute
                    const productUrl = $(this).data('product-url');
                    
                    // Log the URL for debugging
                    console.log('Redirecting to:', productUrl);

                    // Redirect to the product page
                    window.location.href = productUrl;

                    // Prevent default link behavior
                    return false;
                });

                // Modify product card click to ignore View More button
                $('.cs-product-card').on('click', function(e) {
                    // Check if the click was on the View More button
                    if ($(e.target).hasClass('cs-view-more-btn') || $(e.target).closest('.cs-view-more-btn').length) {
                        return false;
                    }

                    // Rest of the existing product selection logic
                    const productId = $(this).data('id');
                    console.log('Product clicked:', productId);
                    
                    // Function to update selected products list
                    function updateSelectedProductsList() {                        
                        let html = '';
                        let totalAmount = 0;
                        
                        const product = {
                            id: productId,
                            name: $(this).data('name'),
                            price: $(this).data('total-price'),
                            total_price: $(this).data('total-price'),
                            sku: $(this).data('sku')
                        };

                        // Clear all selection indicators
                        $('.cs-product-card').removeClass('selected');
                        $('.cs-selected-checkmark, .cs-selected-text').remove();
                        
                        // Add selection to clicked product
                        $(this).addClass('selected');
                        $(this).find('.cs-product-sku').append('<span class="cs-selected-checkmark">✓</span>');
                        $(this).find('.cs-product-price').append('<span class="cs-selected-text"> - Selected</span>');

                        // Update total amount and product list
                        totalAmount = parseFloat(product.price);
                        
                        html += `
                            <div class="cs-selected-product" data-id="${product.id}">
                                <div class="cs-product-details">
                                    <span class="cs-product-title">${product.name}</span>
                                    <span class="cs-product-meta">SKU: ${product.sku || 'N/A'}</span>
                                </div>
                                <div class="cs-product-price-wrapper">
                                    <span class="cs-product-price">${totalAmount.toFixed(2)} ${csChildAjax.currency || 'SEK'}</span>
                                </div>
                            </div>
                        `;
                        
                        $('#selected-products-list').html(html);
                        $('#total_amount').val(totalAmount.toFixed(2));
                    }

                    // Call the update function
                    updateSelectedProductsList.call($(this));
                });
            });
				jQuery(document).ready(function($) {
    // Thumbnail click handler
    $('.cs-product-thumbnail').on('click', function() {
        // Get the full-size image URL from the clicked thumbnail
        const fullSizeImageUrl = $(this).find('img').data('full-size');
        const mainImage = $('.cs-main-image img');
        
        // Update the main image source
        mainImage.attr('src', fullSizeImageUrl);
    });

    // Enhance thumbnails to store full-size image URLs
    $('.cs-product-thumbnails .cs-product-thumbnail img').each(function() {
        // Store the full-size image URL as a data attribute
        $(this).data('full-size', $(this).closest('a').attr('href') || $(this).attr('src'));
    });
});
            </script>
        <?php endif; ?>
    </div>
    <?php
}
}
