<?php
/**
 * Ajax functionality for the Club Child Users
 */
class CS_Child_Ajax {
    /**
     * Initialize Ajax hooks
     */
    public static function init() {
        // Add child user
        add_action('wp_ajax_cciu_add_child', array(__CLASS__, 'add_child'));
        
		// Remove child user
		add_action('wp_ajax_cciu_remove_child', array(__CLASS__, 'remove_child'));

		// Edit child user
		add_action('wp_ajax_cs_edit_child', array(__CLASS__, 'edit_child'));

		// Get team color
		add_action('wp_ajax_cs_get_team_color', array(__CLASS__, 'get_team_color'));

		// Get child's products
		add_action('wp_ajax_cciu_get_child_products', array(__CLASS__, 'get_child_products'));
        
        // Assign product to child
        add_action('wp_ajax_cciu_assign_product', array(__CLASS__, 'assign_product'));
        
        // Unassign product from child
        add_action('wp_ajax_cciu_unassign_product', array(__CLASS__, 'unassign_product'));
        
        // Process form submissions
        add_action('init', array(__CLASS__, 'process_form_submissions'));
		//New Code
		add_action('wp_ajax_get_product_sales_materials', array(__CLASS__, 'get_product_sales_materials'));
		add_action('wp_ajax_nopriv_get_product_sales_materials', array(__CLASS__, 'get_product_sales_materials'));
		add_action('wp_ajax_get_product_sales_materials', array(__CLASS__, 'get_product_sales_materials'));
    add_action('wp_ajax_nopriv_get_product_sales_materials', array(__CLASS__, 'get_product_sales_materials'));
		
		add_action('wp_ajax_cs_add_team', array(__CLASS__, 'add_team'));
		add_action('wp_ajax_cs_update_team', array(__CLASS__, 'update_team'));
		add_action('wp_ajax_cs_delete_team', array(__CLASS__, 'delete_team'));
		add_action('wp_ajax_cs_get_teams', array(__CLASS__, 'get_teams'));
}
    
    /**
     * Process form submissions
     */
    public static function process_form_submissions() {
        // Check if we're processing an assignment form
        if (isset($_POST['action']) && ($_POST['action'] === 'cs_assign_product' || $_POST['action'] === 'cs_unassign_product')) {
            $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            
            if (!$child_id || !$product_id) {
                // Invalid IDs, don't process
                return;
            }
            
            // Check if this is an AJAX request
            $is_ajax = isset($_POST['ajax_submission']) && $_POST['ajax_submission'] === '1';
            
            // Verify user can manage children
            if (!CS_Child_Manager::can_manage_children()) {
                if ($is_ajax) {
                    wp_send_json_error(__('You do not have permission to manage child users.', 'club-sales'));
                } else {
                    wp_die(__('You do not have permission to manage child users.', 'club-sales'));
                }
                return;
            }
            
            // Check if this child belongs to the current user
            $parent_children = CS_Child_Manager::get_parent_children(get_current_user_id());
            $is_parent = false;
            
            foreach ($parent_children as $child) {
                if ($child['id'] == $child_id) {
                    $is_parent = true;
                    break;
                }
            }
            
            if (!$is_parent && !current_user_can('manage_options')) {
                if ($is_ajax) {
                    wp_send_json_error(__('You do not have permission to manage this child user.', 'club-sales'));
                } else {
                    wp_die(__('You do not have permission to manage this child user.', 'club-sales'));
                }
                return;
            }
            
            if ($_POST['action'] === 'cs_assign_product') {
                // Verify assign nonce
                if (!isset($_POST['cs_assign_nonce']) || !wp_verify_nonce($_POST['cs_assign_nonce'], 'assign_product')) {
                    if ($is_ajax) {
                        wp_send_json_error(__('Security check failed.', 'club-sales'));
                    } else {
                        wp_die(__('Security check failed.', 'club-sales'));
                    }
                    return;
                }
                
                // Assign the product
                $result = CS_Child_Manager::assign_product($child_id, $product_id, get_current_user_id());
                
                if ($is_ajax) {
                    if ($result) {
                        wp_send_json_success(array(
                            'message' => __('Product assigned successfully.', 'club-sales')
                        ));
                    } else {
                        wp_send_json_error(__('Failed to assign product.', 'club-sales'));
                    }
                } else {
                    // Set a success message for non-AJAX requests
                    add_action('cs_before_content', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Product assigned successfully.', 'club-sales') . '</p></div>';
                    });
                }
            } else {
                // Verify unassign nonce
                if (!isset($_POST['cs_unassign_nonce']) || !wp_verify_nonce($_POST['cs_unassign_nonce'], 'unassign_product')) {
                    if ($is_ajax) {
                        wp_send_json_error(__('Security check failed.', 'club-sales'));
                    } else {
                        wp_die(__('Security check failed.', 'club-sales'));
                    }
                    return;
                }
                
                // Unassign the product
                $result = CS_Child_Manager::unassign_product($child_id, $product_id);
                
                if ($is_ajax) {
                    if ($result) {
                        wp_send_json_success(array(
                            'message' => __('Product unassigned successfully.', 'club-sales')
                        ));
                    } else {
                        wp_send_json_error(__('Failed to unassign product.', 'club-sales'));
                    }
                } else {
                    // Set a success message for non-AJAX requests
                    add_action('cs_before_content', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Product unassigned successfully.', 'club-sales') . '</p></div>';
                    });
                }
            }
            
            // For non-AJAX requests, redirect to avoid form resubmission
            if (!$is_ajax) {
                $redirect_url = add_query_arg(array(
                    'tab' => 'assign-products',
                    'child_id' => $child_id
                ), remove_query_arg(array('action', 'cs_assign_nonce', 'cs_unassign_nonce', 'ajax_submission')));
                
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    }
    public static function generate_random_password($length = 12) {
    // Define character sets
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special_chars = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    // Combine all character sets
    $all_chars = $uppercase . $lowercase . $numbers . $special_chars;

    // Ensure at least one character from each set
    $password = 
        $uppercase[random_int(0, strlen($uppercase) - 1)] .
        $lowercase[random_int(0, strlen($lowercase) - 1)] .
        $numbers[random_int(0, strlen($numbers) - 1)] .
        $special_chars[random_int(0, strlen($special_chars) - 1)];

    // Fill the rest of the password with random characters
    for ($i = 4; $i < $length; $i++) {
        $password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
    }

    // Shuffle the password to randomize the position of required characters
    $password_array = str_split($password);
    shuffle($password_array);
    return implode('', $password_array);
}
    /**
     * Add a child user
     */
public static function add_child() {
    // Check nonce
    check_ajax_referer('cciu_add_child', 'nonce');
    
    // Check permissions
    if (!CS_Child_Manager::can_manage_children()) {
        wp_send_json_error(__('You do not have permission to add child users.', 'club-sales'));
        return;
    }
    
    // Check if a random password is requested
    $generate_random_password = isset($_POST['generate_random_password']) && $_POST['generate_random_password'] === 'yes';
    
    // Get and sanitize data
    $child_data = array(
        'email' => sanitize_email($_POST['email']),
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        // Use random password if requested, otherwise use provided password
        'password' => $generate_random_password ? 
            self::generate_random_password() : 
            $_POST['password']
    );
    
    // Get team assignment if provided
    $team_id = isset($_POST['child_team']) ? intval($_POST['child_team']) : 0;
    
    // Add child
    $result = CS_Child_Manager::add_child($child_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    // If team was selected, assign it to the child
    if ($team_id > 0) {
        // Verify the team belongs to the parent
        global $wpdb;
        $table_name = $wpdb->prefix . 'cs_teams';
        $team_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id = %d AND user_id = %d",
            $team_id,
            get_current_user_id()
        ));
        
        if ($team_exists) {
            // Assign team to child
            update_user_meta($result, 'assigned_team', $team_id);
            error_log('Assigned child ' . $result . ' to team ' . $team_id);
        }
    }
    
    // Get the new child's details for response
    $children = CS_Child_Manager::get_parent_children(get_current_user_id());
    $new_child = null;
    
    foreach ($children as $child) {
        if ($child['id'] == $result) {
            $new_child = $child;
            break;
        }
    }
    
    // Add team info to child data
    if ($team_id > 0 && isset($team_exists) && $team_exists) {
        $team = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM $table_name WHERE id = %d",
            $team_id
        ));
        if ($team) {
            $new_child['team_name'] = $team->name;
        }
    }
    
    wp_send_json_success(array(
    'message' => __('Child user added successfully.', 'club-sales'),
    'child' => $new_child,
    // Include the password if it was randomly generated
    'password' => $generate_random_password ? $child_data['password'] : null,
    'team_assigned' => $team_id > 0,
    'team_data' => ($team_id > 0 && isset($team) && $team) ? array(
        'id' => $team_id,
        'name' => $team->name
    ) : null
));
}
    
/**
 * Remove a child user
 */
public static function remove_child() {
    // FIXED: Check for both possible nonce names
    $nonce_verified = false;
    
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'cs-child-ajax-nonce')) {
        $nonce_verified = true;
    } elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'cciu_nonce')) {
        $nonce_verified = true;
    }
    
    if (!$nonce_verified) {
        error_log('Remove child: Nonce verification failed');
        wp_send_json_error(__('Security verification failed', 'club-sales'));
        return;
    }
    
    // Check permissions
    if (!CS_Child_Manager::can_manage_children()) {
        wp_send_json_error(__('You do not have permission to remove child users.', 'club-sales'));
        return;
    }
    
    // Get child ID
    $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
    
    error_log('Remove child: Child ID = ' . $child_id);
    
    if (!$child_id) {
        wp_send_json_error(__('Invalid child user ID.', 'club-sales'));
        return;
    }
    
    // Remove child
    $result = CS_Child_Manager::remove_child($child_id, get_current_user_id());
    
    if (!$result) {
        wp_send_json_error(__('Failed to remove child user.', 'club-sales'));
        return;
    }
    
    wp_send_json_success(array(
        'message' => __('Child user removed successfully.', 'club-sales')
    ));
}
    
    /**
     * Get products assigned to a child
     */
	// In class-cs-child-ajax.php
	public static function get_child_products() {
		// Add debugging
		error_log('cciu_get_child_products called');

		// Check nonce
		check_ajax_referer('cs-child-ajax-nonce', 'nonce');

		// Get child ID
		$child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
		error_log('Requested child ID: ' . $child_id);

		// If no child ID provided, use current user ID
		if (!$child_id) {
			$child_id = get_current_user_id();
			error_log('Using current user ID: ' . $child_id);
		}

		if (!$child_id) {
			wp_send_json_error(__('Invalid child user ID.', 'club-sales'));
			return;
		}

		// Check if current user can view this child's products
		$current_user_id = get_current_user_id();
		error_log('Current user ID: ' . $current_user_id);

		// If the current user is the child, or is a parent of the child, allow access
		$is_parent = false;
		$parent_children = CS_Child_Manager::get_parent_children($current_user_id);

		error_log('Parent children: ' . print_r($parent_children, true));

		foreach ($parent_children as $child) {
			if ($child['id'] == $child_id) {
				$is_parent = true;
				break;
			}
		}

		if ($current_user_id != $child_id && !$is_parent && !current_user_can('manage_options')) {
			wp_send_json_error(__('You do not have permission to view this child\'s products.', 'club-sales'));
			return;
		}

		// Get child's products
		$products = CS_Child_Manager::get_child_products($child_id);
		error_log('Products found for child: ' . count($products));
		error_log('Products data: ' . print_r($products, true));

		wp_send_json_success(array(
			'products' => $products
		));
	}
    
    /**
     * Assign a product to a child
     */
	public static function assign_product() {
    // Check nonce
    check_ajax_referer('cs-child-ajax-nonce', 'nonce');

    // Log for debugging
    error_log('AJAX assign_product called');
    error_log('POST data: ' . print_r($_POST, true));

    // Check permissions
    if (!CS_Child_Manager::can_manage_children()) {
        wp_send_json_error(__('You do not have permission to assign products.', 'club-sales'));
        return;
    }

    // Get data
    $child_id = isset($_POST['child_id']) ? $_POST['child_id'] : ''; // Accept string for 'ALL'
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    error_log('Child ID: ' . $child_id . ', Product ID: ' . $product_id);

    // Validate product ID
    if ($product_id <= 0) {
        wp_send_json_error(__('Invalid product ID.', 'club-sales'));
        return;
    }

    // Handle 'ALL' or 'all' child ID - pass directly to CS_Child_Manager::assign_product
    if ($child_id === 'ALL' || $child_id === 'all') {
        error_log('Assigning product to ALL children');
        
        // Call the CS_Child_Manager::assign_product method which already handles 'all'
        $result = CS_Child_Manager::assign_product('all', $product_id, get_current_user_id());

        if (!$result) {
            wp_send_json_error(__('Failed to assign product to all children.', 'club-sales'));
            return;
        }

        wp_send_json_success(array(
            'message' => __('Product assigned to all children successfully.', 'club-sales'),
			'redirect' => true
        ));
        return;
    }

    // Normal case - validate child_id as integer
    $child_id = intval($child_id);
    if ($child_id <= 0) {
        wp_send_json_error(__('Invalid child user ID.', 'club-sales'));
        return;
    }

    // Check if child belongs to current user
    $parent_children = CS_Child_Manager::get_parent_children(get_current_user_id());
    $is_parent = false;

    foreach ($parent_children as $child) {
        if ($child['id'] == $child_id) {
            $is_parent = true;
            break;
        }
    }

    if (!$is_parent && !current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to assign products to this child.', 'club-sales'));
        return;
    }

    // Call the CS_Child_Manager::assign_product method
    $result = CS_Child_Manager::assign_product($child_id, $product_id, get_current_user_id());

    if (!$result) {
        wp_send_json_error(__('Failed to assign product.', 'club-sales'));
        return;
    }

    // Get product details for response
    $product_data = null;

    if (function_exists('wc_get_product')) {
        $product = wc_get_product($product_id);

        if ($product) {
            $product_data = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'sku' => $product->get_sku(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')
            );
        }
    }

    wp_send_json_success(array(
        'message' => __('Product assigned successfully.', 'club-sales'),
        'product' => $product_data
    ));
}
	public static function get_product_sales_materials() {
    $debug_info = array(
        'method_called' => true,
        'post_data' => $_POST,
        'nonce_check' => false,
        'product_id' => 0,
        'acf_active' => function_exists('get_field'),
        'sales_description' => null,
        'sales_image_1' => null,
        'sales_image_2' => null,
        'materials' => null
    );

    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'cs-child-ajax-nonce')) {
        $debug_info['nonce_check'] = false;
        wp_send_json_error(array(
            'message' => 'Invalid nonce',
            'debug' => $debug_info
        ));
        return;
    }
    $debug_info['nonce_check'] = true;

    // Get the product ID
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $debug_info['product_id'] = $product_id;

    if (!$product_id) {
        wp_send_json_error(array(
            'message' => 'Invalid product ID',
            'debug' => $debug_info
        ));
        return;
    }

    // Check if Advanced Custom Fields (ACF) is active
    if (!function_exists('get_field')) {
        wp_send_json_error(array(
            'message' => 'Advanced Custom Fields plugin is not active',
            'debug' => $debug_info
        ));
        return;
    }

    // Fetch sales materials using ACF fields
    $sales_description = get_field('sales_description', $product_id);
    $sales_image_1 = get_field('sales_image_1', $product_id);
    $sales_image_2 = get_field('sales_image_2', $product_id);

    $debug_info['sales_description'] = $sales_description;
    $debug_info['sales_image_1'] = $sales_image_1;
    $debug_info['sales_image_2'] = $sales_image_2;

    $materials = array(
        'description' => $sales_description ?: '',
        'image_1' => $sales_image_1 ? wp_get_attachment_image_url($sales_image_1, 'medium') : '',
        'image_2' => $sales_image_2 ? wp_get_attachment_image_url($sales_image_2, 'medium') : ''
    );

    $debug_info['materials'] = $materials;

    wp_send_json_success(array(
        'materials' => $materials,
        'debug' => $debug_info
    ));
}
    /**
     * Unassign a product from a child
     */
    public static function unassign_product() {
        // Check nonce
        check_ajax_referer('cs-child-ajax-nonce', 'nonce');
        
        // Log for debugging
        error_log('AJAX unassign_product called');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Check permissions
        if (!CS_Child_Manager::can_manage_children()) {
            wp_send_json_error(__('You do not have permission to unassign products.', 'club-sales'));
            return;
        }
        
        // Get data
        $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$child_id || !$product_id) {
            wp_send_json_error(__('Invalid child user ID or product ID.', 'club-sales'));
            return;
        }
        
        // Check if child belongs to current user
        $parent_children = CS_Child_Manager::get_parent_children(get_current_user_id());
        $is_parent = false;
        
        foreach ($parent_children as $child) {
            if ($child['id'] == $child_id) {
                $is_parent = true;
                break;
            }
        }
        
        if (!$is_parent && !current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to unassign products from this child.', 'club-sales'));
            return;
        }
        
        // Unassign product
        $result = CS_Child_Manager::unassign_product($child_id, $product_id);
        
        if (!$result) {
            wp_send_json_error(__('Failed to unassign product.', 'club-sales'));
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Product unassigned successfully.', 'club-sales')
        ));
    }
// ============================================
// ADD TEAM
// ============================================
public static function add_team() {
    error_log('ADD TEAM: Request received');
    error_log('POST data: ' . print_r($_POST, true));
    
    // FIXED: Use correct nonce name
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cs_add_team')) {
        error_log('ADD TEAM: Nonce verification failed');
        wp_send_json_error(array(
            'message' => __('Security check failed', 'club-sales')
        ));
        return;
    }
    
    // Check user is logged in
    if (!is_user_logged_in()) {
        error_log('ADD TEAM: User not logged in');
        wp_send_json_error(array(
            'message' => __('You must be logged in', 'club-sales')
        ));
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Validate input
    $team_name = isset($_POST['team_name']) ? sanitize_text_field($_POST['team_name']) : '';
    $swish_number = isset($_POST['team_swish']) ? sanitize_text_field($_POST['team_swish']) : '';
    $color = isset($_POST['team_color']) ? sanitize_hex_color($_POST['team_color']) : '#10b981';
    
    error_log('ADD TEAM: Validated data - Name: ' . $team_name . ', Swish: ' . $swish_number . ', Color: ' . $color);
    
    if (empty($team_name)) {
        error_log('ADD TEAM: Team name is empty');
        wp_send_json_error(array(
            'message' => __('Team name is required', 'club-sales')
        ));
        return;
    }
    
    // Validate Swish number if provided
    if (!empty($swish_number)) {
        $swish_number = preg_replace('/[\s\-]/', '', $swish_number);
        if (!preg_match('/^\d{10}$/', $swish_number)) {
            wp_send_json_error(array('message' => __('Invalid Swish number format. Must be 10 digits.', 'club-sales')));
            return;
        }
    }
    
    // Insert into database
    global $wpdb;
    $table_name = $wpdb->prefix . 'cs_teams';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'name' => $team_name,
            'swish_number' => $swish_number,
            'color' => $color,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        error_log('ADD TEAM: Database insert failed - ' . $wpdb->last_error);
        wp_send_json_error(array(
            'message' => __('Failed to create team', 'club-sales'),
            'error' => $wpdb->last_error
        ));
        return;
    }
    
    $team_id = $wpdb->insert_id;
    error_log('ADD TEAM: Success - Team ID: ' . $team_id);
    
    wp_send_json_success(array(
        'message' => __('Team created successfully', 'club-sales'),
        'team_id' => $team_id,
        'team' => array(
            'id' => $team_id,
            'name' => $team_name,
            'swish_number' => $swish_number,
            'color' => $color,
            'seller_count' => 0
        )
    ));
}

// ============================================
// UPDATE TEAM
// ============================================
public static function update_team() {
    error_log('UPDATE TEAM: Request received');
    error_log('POST data: ' . print_r($_POST, true));
    
    // FIXED: Use correct nonce name
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cs_add_team')) {
        error_log('UPDATE TEAM: Nonce verification failed');
        wp_send_json_error(array(
            'message' => __('Security check failed', 'club-sales')
        ));
        return;
    }
    
    // Check user is logged in
    if (!is_user_logged_in()) {
        error_log('UPDATE TEAM: User not logged in');
        wp_send_json_error(array(
            'message' => __('You must be logged in', 'club-sales')
        ));
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Validate input
    $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
    $team_name = isset($_POST['team_name']) ? sanitize_text_field($_POST['team_name']) : '';
    $swish_number = isset($_POST['team_swish']) ? sanitize_text_field($_POST['team_swish']) : '';
    $color = isset($_POST['team_color']) ? sanitize_hex_color($_POST['team_color']) : '#10b981';
    
    error_log('UPDATE TEAM: Validated data - ID: ' . $team_id . ', Name: ' . $team_name . ', Color: ' . $color);
    
    if (empty($team_id)) {
        error_log('UPDATE TEAM: Team ID is empty');
        wp_send_json_error(array(
            'message' => __('Team ID is required', 'club-sales')
        ));
        return;
    }
    
    if (empty($team_name)) {
        error_log('UPDATE TEAM: Team name is empty');
        wp_send_json_error(array(
            'message' => __('Team name is required', 'club-sales')
        ));
        return;
    }
    
    // Validate Swish number if provided
    if (!empty($swish_number)) {
        $swish_number = preg_replace('/[\s\-]/', '', $swish_number);
        if (!preg_match('/^\d{10}$/', $swish_number)) {
            wp_send_json_error(array('message' => __('Invalid Swish number format. Must be 10 digits.', 'club-sales')));
            return;
        }
    }
    
    // Verify team belongs to user
    global $wpdb;
    $table_name = $wpdb->prefix . 'cs_teams';
    
    $team_owner = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $table_name WHERE id = %d",
        $team_id
    ));
    
    if ($team_owner != $user_id) {
        error_log('UPDATE TEAM: Team does not belong to user');
        wp_send_json_error(array(
            'message' => __('You do not have permission to edit this team', 'club-sales')
        ));
        return;
    }
    
    // Update database
    $result = $wpdb->update(
        $table_name,
        array(
            'name' => $team_name,
            'swish_number' => $swish_number,
            'color' => $color
        ),
        array('id' => $team_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        error_log('UPDATE TEAM: Database update failed - ' . $wpdb->last_error);
        wp_send_json_error(array(
            'message' => __('Failed to update team', 'club-sales'),
            'error' => $wpdb->last_error
        ));
        return;
    }
    
    error_log('UPDATE TEAM: Success - Rows affected: ' . $result);
    
    wp_send_json_success(array(
        'message' => __('Team updated successfully', 'club-sales'),
        'team' => array(
            'id' => $team_id,
            'name' => $team_name,
            'swish_number' => $swish_number,
            'color' => $color
        )
    ));
}

// ============================================
// DELETE TEAM
// ============================================
public static function delete_team() {
    error_log('DELETE TEAM: Request received');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Get the nonce
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    error_log('DELETE TEAM: Nonce received: ' . $nonce);
    
    if (empty($nonce)) {
        error_log('DELETE TEAM: No nonce provided');
        wp_send_json_error(array(
            'message' => __('No security token provided', 'club-sales')
        ));
        return;
    }
    
    // Try all possible nonce verifications
    $nonce_verified = false;
    $nonce_action_used = '';
    
    // Try cs-child-ajax-nonce
    if (wp_verify_nonce($nonce, 'cs-child-ajax-nonce')) {
        $nonce_verified = true;
        $nonce_action_used = 'cs-child-ajax-nonce';
        error_log('DELETE TEAM: Nonce verified with cs-child-ajax-nonce');
    }
    // Try cs_add_team
    elseif (wp_verify_nonce($nonce, 'cs_add_team')) {
        $nonce_verified = true;
        $nonce_action_used = 'cs_add_team';
        error_log('DELETE TEAM: Nonce verified with cs_add_team');
    }
    // Try cciu_nonce
    elseif (wp_verify_nonce($nonce, 'cciu_nonce')) {
        $nonce_verified = true;
        $nonce_action_used = 'cciu_nonce';
        error_log('DELETE TEAM: Nonce verified with cciu_nonce');
    }
    
    if (!$nonce_verified) {
        error_log('DELETE TEAM: All nonce verifications failed');
        error_log('DELETE TEAM: Tried: cs-child-ajax-nonce, cs_add_team, cciu_nonce');
        
        // Return detailed error for debugging
        wp_send_json_error(array(
            'message' => __('Security check failed - nonce invalid', 'club-sales'),
            'debug' => array(
                'nonce_received' => substr($nonce, 0, 10) . '...',
                'nonce_length' => strlen($nonce),
                'tried_actions' => array('cs-child-ajax-nonce', 'cs_add_team', 'cciu_nonce')
            )
        ));
        return;
    }
    
    error_log('DELETE TEAM: Nonce verified successfully with: ' . $nonce_action_used);
    
    // Check user is logged in
    if (!is_user_logged_in()) {
        error_log('DELETE TEAM: User not logged in');
        wp_send_json_error(array(
            'message' => __('You must be logged in', 'club-sales')
        ));
        return;
    }
    
    $user_id = get_current_user_id();
    $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
    
    error_log('DELETE TEAM: User ID: ' . $user_id . ', Team ID: ' . $team_id);
    
    if (empty($team_id)) {
        error_log('DELETE TEAM: Team ID is empty');
        wp_send_json_error(array(
            'message' => __('Team ID is required', 'club-sales')
        ));
        return;
    }
    
    // Verify team belongs to user
    global $wpdb;
    $table_name = $wpdb->prefix . 'cs_teams';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
    if ($table_exists !== $table_name) {
        error_log('DELETE TEAM: Table does not exist: ' . $table_name);
        wp_send_json_error(array(
            'message' => __('Teams table does not exist', 'club-sales')
        ));
        return;
    }
    
    $team_owner = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$table_name} WHERE id = %d",
        $team_id
    ));
    
    error_log('DELETE TEAM: Team owner: ' . $team_owner . ', Current user: ' . $user_id);
    
    if (!$team_owner) {
        error_log('DELETE TEAM: Team not found with ID: ' . $team_id);
        wp_send_json_error(array(
            'message' => __('Team not found', 'club-sales')
        ));
        return;
    }
    
    if ($team_owner != $user_id) {
        error_log('DELETE TEAM: User does not own this team');
        wp_send_json_error(array(
            'message' => __('You do not have permission to delete this team', 'club-sales')
        ));
        return;
    }
    
    // Delete from database
    $result = $wpdb->delete(
        $table_name,
        array('id' => $team_id),
        array('%d')
    );
    
    if ($result === false) {
        error_log('DELETE TEAM: Database delete failed - ' . $wpdb->last_error);
        wp_send_json_error(array(
            'message' => __('Failed to delete team from database', 'club-sales'),
            'error' => $wpdb->last_error
        ));
        return;
    }
    
    if ($result === 0) {
        error_log('DELETE TEAM: No rows affected - team may not exist');
        wp_send_json_error(array(
            'message' => __('Team not found or already deleted', 'club-sales')
        ));
        return;
    }
    
    error_log('DELETE TEAM: Success - Rows deleted: ' . $result);
    
    wp_send_json_success(array(
        'message' => __('Team deleted successfully', 'club-sales'),
        'deleted_count' => $result
    ));
}

// ============================================
// GET TEAMS - For AJAX dropdown refresh
// ============================================
public static function get_teams() {
    // Verify nonce
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    
    $nonce_verified = false;
    if (wp_verify_nonce($nonce, 'cs-child-ajax-nonce')) {
        $nonce_verified = true;
    } elseif (wp_verify_nonce($nonce, 'cciu_nonce')) {
        $nonce_verified = true;
    }
    
    if (!$nonce_verified) {
        wp_send_json_error(array('message' => __('Security check failed', 'club-sales')));
        return;
    }
    
    // Check user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in', 'club-sales')));
        return;
    }
    
    $user_id = get_current_user_id();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cs_teams';
    
    // Get all teams for this user
    $teams = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, color, swish_number FROM {$table_name} WHERE user_id = %d ORDER BY name ASC",
        $user_id
    ));
    
    // Get seller counts for each team
    $teams_with_counts = array();
    foreach ($teams as $team) {
        $seller_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cs_child_parent cp 
             INNER JOIN {$wpdb->prefix}usermeta um ON cp.child_id = um.user_id 
             WHERE cp.parent_id = %d AND um.meta_key = 'assigned_team' AND um.meta_value = %d",
            $user_id,
            $team->id
        ));
        
        $teams_with_counts[] = array(
            'id' => $team->id,
            'name' => $team->name,
            'color' => $team->color,
            'swish_number' => $team->swish_number,
            'seller_count' => intval($seller_count)
        );
    }
    
wp_send_json_success(array(
        'teams' => $teams_with_counts
    ));
}

/**
 * Edit child user information
 */
public static function edit_child() {
    check_ajax_referer('cs_edit_child', 'nonce');
    
    $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
    $child_name = isset($_POST['child_name']) ? sanitize_text_field($_POST['child_name']) : '';
    $child_email = isset($_POST['child_email']) ? sanitize_email($_POST['child_email']) : '';
    $child_team = isset($_POST['child_team']) ? intval($_POST['child_team']) : 0;
    
    if (!$child_id || !$child_name || !$child_email) {
        wp_send_json_error('Invalid data provided');
        return;
    }
    
    // Verify current user has permission to edit this child
    $current_user_id = get_current_user_id();
    $child_parent = CS_Child_Manager::get_child_parent($child_id);
    
    if (!$child_parent || intval($child_parent['id']) !== intval($current_user_id)) {
        wp_send_json_error('You do not have permission to edit this seller');
        return;
    }
    
    // Update user data
    $user_data = array(
        'ID' => $child_id,
        'display_name' => $child_name,
        'user_email' => $child_email
    );
    
    $result = wp_update_user($user_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    // Update team assignment
    if ($child_team > 0) {
        update_user_meta($child_id, 'assigned_team', $child_team);
    } else {
        delete_user_meta($child_id, 'assigned_team');
    }
    
    wp_send_json_success(array(
        'message' => 'Seller updated successfully',
        'child_id' => $child_id,
        'child_name' => $child_name,
        'child_email' => $child_email,
        'child_team' => $child_team
    ));
}

/**
 * Get team color by team ID
 */
public static function get_team_color() {
    check_ajax_referer('cs-child-ajax-nonce', 'nonce');
    
    $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
    
    if (!$team_id) {
        wp_send_json_error('Invalid team ID');
        return;
    }
    
    global $wpdb;
    $team = $wpdb->get_row($wpdb->prepare(
        "SELECT color FROM {$wpdb->prefix}cs_teams WHERE id = %d",
        $team_id
    ));
    
    if (!$team) {
        wp_send_json_error('Team not found');
        return;
    }
    
    wp_send_json_success(array(
        'color' => $team->color
    ));
}

}