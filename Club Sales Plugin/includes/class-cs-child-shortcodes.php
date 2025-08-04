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
		add_filter('cs_dashboard_tabs', array(__CLASS__, 'modify_dashboard_tabs'), 10, 1);
        
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
public static function modify_dashboard_tabs($tabs) {
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    // Check if current user is a child user
    if (in_array('club_child_user', $user_roles)) {
        // Child users can only see assigned products, add order, sales material, and stats tabs
        $child_tabs = array();
        
        // Existing tabs for child users
        if (isset($tabs['assigned-products'])) {
            $child_tabs['assigned-products'] = $tabs['assigned-products'];
        }
        
        if (isset($tabs['add-order'])) {
            $child_tabs['add-order'] = $tabs['add-order'];
        }
        
        // Add Orders tab for child users
        $child_tabs['orders'] = array(
            'icon' => 'dashicons-list-view',
            'title' => __('Orders', 'club-sales'),
            'content_callback' => array('CS_Child_Shortcodes', 'child_orders_tab')
        );
        
        // Add sales material tab
        $child_tabs['sales-material'] = array(
            'icon' => 'dashicons-media-document',
            'title' => __('Sales Material', 'club-sales'),
            'content_callback' => array('CS_Child_Shortcodes', 'sales_material_tab')
        );
        
        // Add stats tab
        if (isset($tabs['stats'])) {
            $child_tabs['stats'] = $tabs['stats'];
        }
        
        return $child_tabs;
    }
    
    // If not a child user, return original tabs
    return $tabs;
}

/**
 * Sales material tab content
 */
public static function sales_material_tab($specific_product_id = 0, $override_child_id = 0) {
    // Remove the static flag to allow multiple renders for AJAX updates
    static $sales_material_rendered = false;
    
    // For AJAX requests, always allow re-rendering
    if (wp_doing_ajax() || $specific_product_id > 0) {
        $sales_material_rendered = false;
    }
    
    if ($sales_material_rendered && !wp_doing_ajax()) {
        return; // Exit if already rendered (only for non-AJAX requests)
    }
    
    $user_id = get_current_user_id();
    $is_child = CS_Child_Manager::is_child_user();
    
    // Debug logging
    error_log("Sales Material Tab - User ID: $user_id, Is Child: " . ($is_child ? 'Yes' : 'No') . ", Specific Product: $specific_product_id");
    
    $assigned_products = array();
    
    // If a specific product ID is provided, get that product's details
    if ($specific_product_id > 0) {
        $wc_product = wc_get_product($specific_product_id);
        if ($wc_product) {
            // Get the calculated price for this product
            $current_user = wp_get_current_user();
            $use_swedish_rounding = !CS_Child_Manager::is_child_user($current_user->ID);
            $total_price = CS_Price_Calculator::get_club_sales_price($specific_product_id, $use_swedish_rounding);
            
            $assigned_products = array(array(
                'product_id' => $specific_product_id,
                'name' => $wc_product->get_name(),
                'total_price' => $total_price,
                'sku' => $wc_product->get_sku()
            ));
            
            error_log("Using specific product: {$specific_product_id} - {$wc_product->get_name()}");
        }
    } else {
        // Original logic for getting all products
        if (!$is_child) {
            $children = CS_Child_Manager::get_parent_children($user_id);
            error_log("Found " . count($children) . " children for parent user $user_id");
            
            if (empty($children)) {
                ?>
                <div class="cs-section">
                    <div class="cs-section-header">
                        <h2><?php _e('Sales Material', 'club-sales'); ?></h2>
                    </div>
                    <p><?php _e('You have no child users to view sales materials for.', 'club-sales'); ?></p>
                </div>
                <?php
                $sales_material_rendered = true;
                return;
            }
            
            // Collect ALL unique products assigned to ALL children
            $all_products = array();
            $product_ids_seen = array();
            
            foreach ($children as $child) {
                $child_products = CS_Child_Manager::get_child_products($child['id']);
                error_log("Child ID: {$child['id']} ({$child['name']}) has " . count($child_products) . " products");
                
                foreach ($child_products as $product) {
                    if (!in_array($product['product_id'], $product_ids_seen)) {
                        $product_ids_seen[] = $product['product_id'];
                        $all_products[] = $product;
                        error_log("Added product ID: {$product['product_id']} - {$product['name']}");
                    }
                }
            }
            
            $assigned_products = $all_products;
            error_log("Total unique products across all children: " . count($assigned_products));
            
        } else {
            // For child users, use their own products
            $assigned_products = CS_Child_Manager::get_child_products($user_id);
            error_log("Child user $user_id has " . count($assigned_products) . " assigned products");
        }
    }
    
    // Check if we have products to display sales materials
    $has_products = !empty($assigned_products);
    
    ?>
    <div class="cs-section" id="sales-material-content">
        <div class="cs-section-header">
            <h2><?php _e('Sales Material', 'club-sales'); ?></h2>
        </div>
        
        <?php if ($has_products): ?>
            <?php 
            // Keep track of rendered product IDs to prevent duplicates
            $rendered_product_ids = [];
            
            foreach ($assigned_products as $product): 
                // Skip if already rendered or if product has no name
                if (empty($product['name']) || in_array($product['product_id'], $rendered_product_ids)) {
                    continue;
                }
                
                // Add current product ID to rendered list
                $rendered_product_ids[] = $product['product_id'];
                
                error_log("Rendering sales material for product: {$product['product_id']} - {$product['name']}");
                
                // Get WooCommerce product
                $wc_product = wc_get_product($product['product_id']);
                
                if (!$wc_product) {
                    error_log("WooCommerce product not found for ID: {$product['product_id']}");
                    continue;
                }
                
                // Determine if we're a parent user for rounding
                $current_user = wp_get_current_user();
                $use_swedish_rounding = !CS_Child_Manager::is_child_user($current_user->ID);
                
                // Get the product price with potential rounding
                $total_price = CS_Price_Calculator::get_club_sales_price(
                    $product['product_id'], 
                    $use_swedish_rounding
                );
                
                // Get product images
                $gallery_images = $wc_product->get_gallery_image_ids();
                $main_image_id = $wc_product->get_image_id();
                
                // Combine main image and gallery images
                $product_images = array_merge(
                    $main_image_id ? array($main_image_id) : array(), 
                    $gallery_images
                );
                
                // Get short description
                $short_description = $wc_product->get_short_description();
                
                // Get long description
                $long_description = $wc_product->get_description();
                
                // Get sales material files using ACF
                $sales_pitches = function_exists('get_field') ? get_field('sales_pitches', $product['product_id']) : null;
                $product_image_acf = function_exists('get_field') ? get_field('product_image', $product['product_id']) : null;
                $social_media_content = function_exists('get_field') ? get_field('social_media_content', $product['product_id']) : null;
                
                // Log ACF field values
                error_log("ACF Fields for product {$product['product_id']}:");
                error_log("- Sales Pitches: " . ($sales_pitches ? 'Found' : 'Not found'));
                error_log("- Product Image ACF: " . ($product_image_acf ? 'Found' : 'Not found'));
                error_log("- Social Media Content: " . ($social_media_content ? 'Found' : 'Not found'));
                
                // Prepare sales material details
                $sales_material = array();
                
                if ($sales_pitches) {
                    $sales_material[] = array(
                        'type' => 'file',
                        'name' => 'Sales Pitches',
                        'url' => is_array($sales_pitches) ? $sales_pitches['url'] : wp_get_attachment_url($sales_pitches)
                    );
                }
                
                if ($product_image_acf) {
                    $sales_material[] = array(
                        'type' => 'image',
                        'name' => 'Product Image',
                        'url' => is_array($product_image_acf) ? $product_image_acf['url'] : wp_get_attachment_url($product_image_acf)
                    );
                }
                
                if ($social_media_content) {
                    $sales_material[] = array(
                        'type' => 'image',
                        'name' => 'Social Media Content',
                        'url' => is_array($social_media_content) ? $social_media_content['url'] : wp_get_attachment_url($social_media_content)
                    );
                }
            ?>
                <div class="cs-sales-material-container" data-product-id="<?php echo esc_attr($product['product_id']); ?>">
                    <div class="cs-product-gallery">
                        <?php if (!empty($product_images)): ?>
                            <div class="cs-main-image">
                                <?php echo wp_get_attachment_image($product_images[0], 'large'); ?>
                            </div>
                            
                            <?php if (count($product_images) > 1): ?>
                                <div class="cs-product-thumbnails">
                                    <?php foreach ($product_images as $image_id): ?>
                                        <div class="cs-product-thumbnail">
                                            <?php 
                                            $thumbnail = wp_get_attachment_image($image_id, 'thumbnail');
                                            $full_image = wp_get_attachment_image_src($image_id, 'full');
                                            ?>
                                            <a href="<?php echo esc_url($full_image[0]); ?>">
                                                <?php echo $thumbnail; ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="cs-no-image">
                                <p><?php _e('No product image available', 'club-sales'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cs-product-details">
                        <h1 class="cs-product-title"><?php echo esc_html($product['name']); ?></h1>
                        
                        <div class="cs-product-price">
                            <strong><?php 
                                $currency = get_option('cs_settings')['currency'] ?? 'SEK';
                                if ($use_swedish_rounding) {
                                    $rounded_price = CS_Price_Calculator::round_price_to_nearest_nine($total_price);
                                    echo number_format($rounded_price, 0) . ' ' . esc_html($currency);
                                } else {
                                    echo number_format($total_price, 0) . ' ' . esc_html($currency);
                                }
                            ?></strong>
                        </div>
                        
                        <?php if (!empty($short_description)): ?>
                            <div class="cs-short-description">
                                <?php echo wpautop($short_description); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sales_material)): ?>
                            <div class="cs-sales-material-downloads">
                                <h3 class="cs-sales-material-title"><?php _e('Sales Material', 'club-sales'); ?></h3>
                                <div class="cs-sales-material-buttons">
                                    <div class="cs-sales-material-item">
                                        <?php foreach ($sales_material as $material): ?>
                                            <a href="<?php echo esc_url($material['url']); ?>" 
                                               class="cs-download-btn" 
                                               download 
                                               target="_blank">
                                                <?php echo esc_html($material['name']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="cs-no-sales-material">
                                <p><?php _e('No sales materials available for this product.', 'club-sales'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($long_description)): ?>
                        <div class="cs-full-description">
                            <h3><?php _e('Description', 'club-sales'); ?></h3>
                            <?php echo wpautop($long_description); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="cs-empty"><?php _e('No products available for sales materials.', 'club-sales'); ?></p>
        <?php endif; ?>
    </div> 
    
    <script>
    jQuery(document).ready(function($) {
        // Thumbnail click handler
        $('.cs-product-thumbnail').on('click', function(e) {
            e.preventDefault();
            const clickedImage = $(this).find('img');
            const mainImage = $('.cs-main-image img');
            
            // Swap images
            const tempSrc = mainImage.attr('src');
            const tempSrcset = mainImage.attr('srcset');
            
            mainImage.attr('src', clickedImage.attr('src'));
            mainImage.attr('srcset', clickedImage.attr('srcset'));
            
            clickedImage.attr('src', tempSrc);
            clickedImage.attr('srcset', tempSrcset);
        });

        // Add a data attribute to identify the current product for debugging
        console.log('Sales Material tab rendered for product(s):', 
            $('.cs-sales-material-container').map(function() {
                return $(this).data('product-id');
            }).get()
        );
    });
    </script>
    <?php
    
    // Only set the rendered flag for non-AJAX requests
    if (!wp_doing_ajax()) {
        $sales_material_rendered = true;
    }
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
    <div class="cs-section-header">
        <h2><?php _e('Your Orders', 'club-sales'); ?></h2>
        <div class="cs-filter-container">
            <select id="order-status-filter">
                <option value=""><?php _e('All Orders', 'club-sales'); ?></option>
                <option value="pending"><?php _e('Pending', 'club-sales'); ?></option>
                <option value="completed"><?php _e('Completed', 'club-sales'); ?></option>
            </select>
        </div>
    </div>

    <div class="cs-orders-container">
        <table class="cs-orders-table">
            <thead>
                <tr>
                    <th><?php _e('Order #', 'club-sales'); ?></th>
                    <th><?php _e('Date', 'club-sales'); ?></th>
                    <th><?php _e('Customer', 'club-sales'); ?></th>
                    <th><?php _e('Seller', 'club-sales'); ?></th>
                    <th><?php _e('Amount', 'club-sales'); ?></th>
                    <th><?php _e('Status', 'club-sales'); ?></th>
                    <th><?php _e('Actions', 'club-sales'); ?></th>
                </tr>
            </thead>
            <tbody id="orders-list">
                <tr>
                    <td colspan="7" class="cs-loading"><?php _e('Loading orders...', 'club-sales'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}
    /**
     * Manage children tab content
     */
    public static function manage_children_tab() {
        $children = CS_Child_Manager::get_parent_children(get_current_user_id());
        ?>
        <div class="cs-section">
            <div class="cs-section-header">
                <h2><?php _e('Add Child User', 'club-sales'); ?></h2>
            </div>
            
            <div class="cs-form-container">
                <form id="cs-add-child-form" class="cs-form">
                    <?php wp_nonce_field('cciu_add_child', 'cciu_nonce'); ?>
                    
                    <div class="cs-form-row">
                        <div class="cs-form-group">
                            <label for="child_first_name"><?php _e('First Name', 'club-sales'); ?> <span class="required">*</span></label>
                            <input type="text" id="child_first_name" name="child_first_name" required>
                        </div>
                        
                        <div class="cs-form-group">
                            <label for="child_last_name"><?php _e('Last Name', 'club-sales'); ?> <span class="required">*</span></label>
                            <input type="text" id="child_last_name" name="child_last_name" required>
                        </div>
                    </div>
                    
                    <div class="cs-form-group">
                        <label for="child_email"><?php _e('Email Address', 'club-sales'); ?> <span class="required">*</span></label>
                        <input type="email" id="child_email" name="child_email" required>
                    </div>
                    
                   <div class="cs-form-group">
    <label for="child_password"><?php _e('Password', 'club-sales'); ?> <span class="required">*</span></label>
    <div class="cs-password-container">
        <input type="password" id="child_password" name="child_password" required>
        <div class="cs-password-suggestions">
            <button type="button" id="generate-password" class="cs-generate-password-btn">
                <?php _e('Generate Strong Password', 'club-sales'); ?>
            </button>
            <div id="password-suggestions-list" class="cs-password-suggestions-list"></div>
        </div>
    </div>
</div>
                    
                    <div class="cs-form-actions">
                        <button type="submit" class="cs-submit-btn"><?php _e('Add Child User', 'club-sales'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="cs-section">
            <div class="cs-section-header">
                <h2><?php _e('Manage Child Users', 'club-sales'); ?></h2>
            </div>
            
            <div class="cs-children-list">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'club-sales'); ?></th>
                            <th><?php _e('Email', 'club-sales'); ?></th>
                            <th><?php _e('Registered', 'club-sales'); ?></th>
                            <th><?php _e('Actions', 'club-sales'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="children-list">
                        <?php if (empty($children)): ?>
                            <tr>
                                <td colspan="4"><?php _e('No child users found.', 'club-sales'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($children as $child): ?>
                                <tr data-id="<?php echo esc_attr($child['id']); ?>">
                                    <td><?php echo esc_html($child['name']); ?></td>
                                    <td><?php echo esc_html($child['email']); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($child['registered'])); ?></td>
                                    <td>
                                        <div class="cs-actions">

                                            <button type="button" class="cs-action cs-remove-child" data-id="<?php echo esc_attr($child['id']); ?>"><?php _e('Remove', 'club-sales'); ?></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
	//New Code
	
	//End Code
    
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
                        console.log("Updating selected products list");
                        
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
