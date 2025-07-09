<?php
/**
 * Class for managing child users and their product assignments
 */
class CS_Child_Manager {
    /**
     * Add a child user
     * 
     * @param array $data Child user data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    public static function add_child($data) {
    // Extensive logging and error checking
    error_log('=== Adding Child User ===');
    error_log('Input Data: ' . print_r($data, true));
    
    // Validate required fields
    $required_fields = array('email', 'first_name', 'last_name', 'password');
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            error_log("Missing required field: {$field}");
            return new WP_Error('missing_field', sprintf(__('Missing required field: %s', 'club-sales'), $field));
        }
    }
    
    // Check email uniqueness
    if (email_exists($data['email'])) {
        error_log('Email already exists: ' . $data['email']);
        return new WP_Error('email_exists', __('A user with this email already exists.', 'club-sales'));
    }
    
    // Create unique username
    $username = sanitize_user(current(explode('@', $data['email'])), true);
    $counter = 1;
    $new_username = $username;
    while (username_exists($new_username)) {
        $new_username = $username . $counter;
        $counter++;
    }
    
    // Get current user (parent) ID
    $parent_id = get_current_user_id();
    error_log('Parent User ID: ' . $parent_id);
    
    // Create user
    $user_id = wp_create_user($new_username, $data['password'], $data['email']);
    
    if (is_wp_error($user_id)) {
        error_log('User Creation Error: ' . $user_id->get_error_message());
        return $user_id;
    }
    
    error_log('New User Created - ID: ' . $user_id);
    
    // Update user profile
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => sanitize_text_field($data['first_name']),
        'last_name' => sanitize_text_field($data['last_name']),
        'display_name' => sanitize_text_field($data['first_name'] . ' ' . $data['last_name'])
    ));
    
    // Set child user role
    $user = new WP_User($user_id);
    $user->set_role('club_child_user');
    
    // CRITICAL: Ensure parent-child relationship is created
    $relationship_result = self::add_child_parent_relationship($user_id, $parent_id);
    
    if ($relationship_result === false) {
        error_log('CRITICAL: Failed to create parent-child relationship');
        // Optional: You might want to delete the user or take corrective action
    }
    
    // Optional: Send welcome email
    self::send_child_user_email($user_id, $data['password']);
    
    return $user_id;
}
    
    /**
     * Add a child-parent relationship
     * 
     * @param int $child_id Child user ID
     * @param int $parent_id Parent user ID
     * @return bool Success
     */
    public static function add_child_parent_relationship($child_id, $parent_id) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'cs_child_parent',
            array(
                'child_id' => intval($child_id),
                'parent_id' => intval($parent_id)
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Remove a child user
     * 
     * @param int $child_id Child user ID
     * @param int $parent_id Parent user ID (for verification)
     * @return bool Success
     */
    public static function remove_child($child_id, $parent_id) {
        global $wpdb;
        
        // Verify the child belongs to this parent
        $relationship = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cs_child_parent WHERE child_id = %d AND parent_id = %d",
            $child_id,
            $parent_id
        ));
        
        if (!$relationship) {
            return false;
        }
        
        // Remove the parent-child relationship
        $wpdb->delete(
            $wpdb->prefix . 'cs_child_parent',
            array('child_id' => $child_id),
            array('%d')
        );
        
        // Remove all product assignments
        $wpdb->delete(
            $wpdb->prefix . 'cs_child_products',
            array('child_id' => $child_id),
            array('%d')
        );
        
        // Decide whether to delete the user entirely or just remove the relationship
        $delete_users = get_option('cs_child_user_settings')['delete_users_on_removal'] ?? 'no';
        
        if ($delete_users === 'yes') {
            // Delete the user completely
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            return wp_delete_user($child_id);
        }
        
        return true;
    }
    
    /**
     * Get children for a parent
     * 
     * @param int $parent_id Parent user ID
     * @return array Array of child users
     */
    public static function get_parent_children($parent_id) {
        global $wpdb;
        
        $children = $wpdb->get_results($wpdb->prepare(
            "SELECT c.child_id, u.user_email, u.display_name, u.user_registered
            FROM {$wpdb->prefix}cs_child_parent c
            JOIN {$wpdb->users} u ON c.child_id = u.ID
            WHERE c.parent_id = %d
            ORDER BY u.display_name",
            $parent_id
        ));
        
        if (!$children) {
            return array();
        }
        
        $result = array();
        
        foreach ($children as $child) {
            $child_data = get_userdata($child->child_id);
            
            if ($child_data) {
                $result[] = array(
                    'id' => $child->child_id,
                    'email' => $child->user_email,
                    'name' => $child->display_name,
                    'registered' => $child->user_registered,
                    'first_name' => $child_data->first_name,
                    'last_name' => $child_data->last_name
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Get parent for a child
     * 
     * @param int $child_id Child user ID
     * @return array|false Parent data or false if not found
     */
    public static function get_child_parent($child_id) {
        global $wpdb;
        
        $parent = $wpdb->get_row($wpdb->prepare(
            "SELECT p.parent_id, u.user_email, u.display_name
            FROM {$wpdb->prefix}cs_child_parent p
            JOIN {$wpdb->users} u ON p.parent_id = u.ID
            WHERE p.child_id = %d",
            $child_id
        ));
        
        if (!$parent) {
            return false;
        }
        
        return array(
            'id' => $parent->parent_id,
            'email' => $parent->user_email,
            'name' => $parent->display_name
        );
    }

	/**
 * Assign a product to a child or all children of a parent
 * 
 * @param int|string $child_id Child user ID or 'all' to assign to all children
 * @param int $product_id Product ID
 * @param int $assigned_by User ID assigning the product
 * @return bool Success
 */
//     public static function assign_product($child_id, $product_id, $assigned_by) {
//     global $wpdb;
    
// 		// Log for debugging
// 		error_log('CS_Child_Manager::assign_product called');
// 		error_log("Parameters: child_id={$child_id}, product_id={$product_id}, assigned_by={$assigned_by}");

// 		// Check if already assigned
// 		$existing = $wpdb->get_var($wpdb->prepare(
// 			"SELECT id FROM {$wpdb->prefix}cs_child_products
//         WHERE child_id = %d AND product_id = %d",
// 			$child_id,
// 			$product_id
// 		));

// 		error_log('Existing query: ' . $wpdb->last_query);

// 		if ($existing) {
// 			error_log('Product already assigned (ID: ' . $existing . ')');
// 			return true; // Already assigned
// 		}

// 		// Insert new assignment
// 		$result = $wpdb->insert(
// 			$wpdb->prefix . 'cs_child_products',
// 			array(
// 				'child_id' => intval($child_id),
// 				'product_id' => intval($product_id),
// 				'assigned_by' => intval($assigned_by)
// 			),
// 			array('%d', '%d', '%d')
// 		);

// 		if ($result === false) {
// 			error_log('Insert error: ' . $wpdb->last_error);
// 		} else {
// 			error_log('Assignment successful. Insert ID: ' . $wpdb->insert_id);
// 		}

// 		return $result !== false;
// 	}

public static function assign_product($child_id, $product_id, $assigned_by) {
    global $wpdb;
    
    error_log("Assigning product {$product_id} to child {$child_id} by {$assigned_by}");

    // Validate inputs
    if (empty($product_id) || !is_numeric($product_id) || $product_id <= 0) {
        error_log("Invalid product ID: {$product_id}");
        return false;
    }
    
    // Handle case where child_id is 'all' or 'ALL' - assign to all children of this parent
    if ($child_id === 'all' || $child_id === 'ALL') {
        error_log("Assigning product {$product_id} to ALL children of parent {$assigned_by}");
        $children = self::get_parent_children($assigned_by);
        $success = true;
        
        if (!empty($children)) {
            foreach ($children as $child) {
                // Remove all existing product assignments for this child
                self::remove_all_child_products($child['id']);
                
                // Assign the new product
                $result = self::assign_product_to_single_child($child['id'], $product_id, $assigned_by);
                if (!$result) {
                    error_log("Failed to assign product {$product_id} to child {$child['id']}");
                    $success = false;
                }
            }
        } else {
            error_log("No children found for parent {$assigned_by}");
            return false;
        }
        
        return $success;
    } else {
        // Remove all existing product assignments for this child
        self::remove_all_child_products($child_id);
        
        // Assign to a single child
        return self::assign_product_to_single_child($child_id, $product_id, $assigned_by);
    }
}
/**
 * Remove all products assigned to a child
 * 
 * @param int $child_id Child user ID
 * @return bool Success
 */
public static function remove_all_child_products($child_id) {
    global $wpdb;
    
    error_log("Removing all products for child {$child_id}");
    
    $result = $wpdb->delete(
        $wpdb->prefix . 'cs_child_products',
        array('child_id' => intval($child_id)),
        array('%d')
    );
    
    if ($result === false) {
        error_log("Error removing all products: " . $wpdb->last_error);
        return false;
    } else {
        error_log("Successfully removed all products for child {$child_id}. Affected rows: {$result}");
        return true;
    }
}
/**
 * Assign a product to a single child
 * 
 * @param int $child_id Child user ID
 * @param int $product_id Product ID
 * @param int $assigned_by User ID assigning the product
 * @return bool Success
 */
private static function assign_product_to_single_child($child_id, $product_id, $assigned_by) {
    global $wpdb;
    
    // Ensure we're working with numeric IDs
    $child_id = intval($child_id);
    $product_id = intval($product_id);
    $assigned_by = intval($assigned_by);
    
    if ($child_id <= 0) {
        error_log("Invalid child ID: {$child_id}");
        return false;
    }
    
    if ($product_id <= 0) {
        error_log("Invalid product ID: {$product_id}");
        return false;
    }
    
    error_log("Assigning product {$product_id} to child {$child_id} by {$assigned_by}");
    
    // Verify if child user exists
    $child_exists = get_userdata($child_id);
    if (!$child_exists) {
        error_log("Child user with ID {$child_id} does not exist");
        return false;
    }
    
    // Verify if product exists
    if (function_exists('wc_get_product')) {
        $product = wc_get_product($product_id);
        if (!$product) {
            error_log("Product with ID {$product_id} does not exist");
            return false;
        }
    }
    
    // We're no longer checking if already assigned since we're removing all products first
    
    // Insert new assignment with current timestamp
    $current_time = current_time('mysql');
    
    // Make sure we use 'assigned_at' instead of 'created_at' if that's the field name in the table
    // Check the table structure to determine which field name to use
    $table_fields = $wpdb->get_results("DESCRIBE {$wpdb->prefix}cs_child_products");
    $has_assigned_at = false;
    $has_created_at = false;
    
    foreach ($table_fields as $field) {
        if ($field->Field === 'assigned_at') {
            $has_assigned_at = true;
        }
        if ($field->Field === 'created_at') {
            $has_created_at = true;
        }
    }
    
    $data = array(
        'child_id' => $child_id,
        'product_id' => $product_id,
        'assigned_by' => $assigned_by
    );
    
    $formats = array('%d', '%d', '%d');
    
    // Add the appropriate timestamp field
    if ($has_assigned_at) {
        $data['assigned_at'] = $current_time;
        $formats[] = '%s';
    } else if (!$has_created_at) {
        // If neither field exists, add assigned_at to the table
        $wpdb->query("ALTER TABLE {$wpdb->prefix}cs_child_products ADD assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        $data['assigned_at'] = $current_time;
        $formats[] = '%s';
    }
    // Note: If has_created_at is true but has_assigned_at is false, we don't need to add anything
    // because created_at will automatically be set by DEFAULT CURRENT_TIMESTAMP
    
    // Insert the record
    $result = $wpdb->insert(
        $wpdb->prefix . 'cs_child_products',
        $data,
        $formats
    );
    
    if ($result === false) {
        error_log("Insert error for product assignment: " . $wpdb->last_error);
        return false;
    } else {
        error_log("Assignment successful. Insert ID: " . $wpdb->insert_id);
        return true;
    }
}


    /**
     * Remove a product assignment from a child
     * 
     * @param int $child_id Child user ID
     * @param int $product_id Product ID
     * @return bool Success
     */
    public static function unassign_product($child_id, $product_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'cs_child_products',
            array(
                'child_id' => intval($child_id),
                'product_id' => intval($product_id)
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get products assigned to a child
     * 
     * @param int $child_id Child user ID
     * @return array Array of assigned products
     */
    
    
    /**
     * Check if a user can manage child users
     * 
     * @param int $user_id User ID to check
     * @return bool True if user can manage children
     */
    public static function can_manage_children($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        $managing_roles = get_option('cs_child_user_settings', array('managing_roles' => array('administrator')))['managing_roles'];
        
        foreach ($user->roles as $role) {
            if (in_array($role, $managing_roles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a user is a child user
     * 
     * @param int $user_id User ID to check
     * @return bool True if user is a child user
     */
    public static function is_child_user($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('club_child_user', $user->roles);
	}
	// In class-cs-child-manager.php
	public static function get_child_products($child_id) {
    global $wpdb;
    
    // Debug logging
    error_log("===== Getting Child Products =====");
    error_log("Child ID: " . $child_id);
    
    // If no child ID is provided, use current user
    if (!$child_id) {
        $child_id = get_current_user_id();
    }
    
    // First, verify the child user exists and is a child user
    $user = get_userdata($child_id);
    if (!$user || !in_array('club_child_user', $user->roles)) {
        error_log("Invalid child user or not a child user role");
        return array();
    }

    // Get the parent of this child
    $parent = self::get_child_parent($child_id);
    if (!$parent) {
        error_log("No parent found for this child");
        return array();
    }

    // Fetch child's products with a fresh query
    $products = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT cp.product_id 
        FROM {$wpdb->prefix}cs_child_products cp
        WHERE cp.child_id = %d",
        $child_id
    ));

    error_log("Number of assigned products: " . count($products));

    $result = array();

    // Get global margin rate
    $global_margin = floatval(get_option('club_sales_global_margin', 12));

    foreach ($products as $product_row) {
        // Extensive logging for each product
        error_log("Processing Product ID: " . $product_row->product_id);

        $wc_product = wc_get_product($product_row->product_id);

        if (!$wc_product) {
            error_log("Could not retrieve WooCommerce product: {$product_row->product_id}");
            continue;
        }

        // Get base price
        $base_price = $wc_product->get_regular_price();
        
        // Try to get custom margin and VAT rates
        $margin_rate = null;
        $vat_rate = null;
        
        // Check for custom rates using ACF
        if (function_exists('get_field')) {
            $margin_rate = get_field('margin', $product_row->product_id);
            $vat_rate = get_field('vat', $product_row->product_id);
        }
        
        // If no custom margin, use global margin
        $margin_rate = $margin_rate !== null ? floatval($margin_rate) : $global_margin;
        
        // If no custom VAT rate, use default (25%)
        $vat_rate = $vat_rate !== null ? floatval($vat_rate) : 25.0;
        
        // Calculate margin amount
        $margin_amount = $base_price * ($margin_rate / 100);
        
        // Price with margin
        $price_with_margin = $base_price + $margin_amount;
        
        // Calculate VAT amount
        $vat_amount = $price_with_margin * ($vat_rate / 100);
        
        // Calculate total price
        $total_price = $price_with_margin + $vat_amount;
        
        // Apply Swedish rounding (only for parent users)
        $current_user = wp_get_current_user();
        $use_swedish_rounding = self::is_child_user($current_user->ID);
        
        if ($use_swedish_rounding) {
            $total_price = CS_Price_Calculator::round_price_to_nearest_nine($total_price);
        }
        
        $result[] = array(
            'product_id' => $wc_product->get_id(),
            'name' => $wc_product->get_name(),
            'base_price' => $base_price,
            'margin_rate' => $margin_rate,
            'margin_amount' => $margin_amount,
            'vat_rate' => $vat_rate,
            'vat_amount' => $vat_amount,
            'total_price' => $total_price,
            'sku' => $wc_product->get_sku(),
            'image' => wp_get_attachment_image_url($wc_product->get_image_id(), 'thumbnail')
        );
        
        // Extensive logging of product calculation
        error_log(sprintf(
            "Product Calculation Details:\n" .
            "Base Price: %s\n" .
            "Margin Rate: %s%%\n" .
            "Margin Amount: %s\n" .
            "Price with Margin: %s\n" .
            "VAT Rate: %s%%\n" .
            "VAT Amount: %s\n" .
            "Total Price: %s\n" .
            "Swedish Rounding Applied: %s",
            $base_price,
            $margin_rate,
            $margin_amount,
            $price_with_margin,
            $vat_rate,
            $vat_amount,
            $total_price,
            $use_swedish_rounding ? 'Yes' : 'No'
        ));
    }

    error_log("Final Result: " . print_r($result, true));
    return $result;
}
public static function send_child_user_email($user_id, $password) {
    // Ensure WP_Mail is available
    if (!function_exists('wp_mail')) {
        require_once(ABSPATH . 'wp-includes/pluggable.php');
    }
    
    // Get user data
    $user = get_userdata($user_id);
    
    if (!$user) {
        error_log("Failed to send child user email: Invalid user ID {$user_id}");
        return false;
    }
    
    // Prepare email details
    $to = $user->user_email;
    $subject = 'Your Child User Account Has Been Created';
    
    // Use HTML email for better formatting
    $message = sprintf(
        "<html><body>" .
        "<h2>Welcome, %s!</h2>" .
        "<p>An account has been created for you in our system.</p>" .
        "<ul>" .
        "<li><strong>Username:</strong> %s</li>" .
        "<li><strong>Password:</strong> %s</li>" .
        "</ul>" .
        "<p>Please log in and change your password.</p>" .
        "</body></html>",
        esc_html($user->first_name),
        esc_html($user->user_login),
        esc_html($password)
    );
    
    // Email headers
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    // Attempt to send email with extensive logging
    $email_sent = wp_mail($to, $subject, $message, $headers);
    
    // Log email sending result
    if ($email_sent) {
        error_log("Child user account email sent successfully to {$to}");
    } else {
        // Get more details about email sending failure
        global $phpmailer;
        
        error_log("Failed to send child user email to {$to}");
        
        // If PHPMailer is available, log additional details
        if (!empty($phpmailer)) {
            error_log("PHPMailer Error: " . print_r($phpmailer->ErrorInfo, true));
        }
    }
    
    return $email_sent;
}
}