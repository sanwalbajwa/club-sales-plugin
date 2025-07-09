<?php
class CS_Order_Confirmation {
    /**
     * Initialize the order confirmation functionality
     */
    public static function init() {
        // Hook into getting order details to send email
        add_action('cs_order_details_viewed', array(__CLASS__, 'send_order_confirmation_email'), 10, 1);
    }
    
    /**
     * Send order confirmation email to customer
     * 
     * @param array $order Order details
     * @return bool Whether email was sent successfully
     */
    public static function send_order_confirmation_email($order) {
        // Ensure we have the necessary order details
        if (empty($order['customer_name']) || empty($order['products'])) {
            error_log('Insufficient order details for email');
            return false;
        }

        // Try to get customer email from user meta or order details
        $customer_email = $order['email'] ?? null;

		// If not found, fallback to user data
		if (empty($customer_email) && !empty($order['user_id'])) {
			$user = get_userdata($order['user_id']);
			if ($user) {
				$customer_email = $user->user_email;
			}
		}

        // If no email found, log and exit
        if (empty($customer_email)) {
            error_log('No customer email found for order');
            return false;
        }

        // Ensure WP_Mail is available
        if (!function_exists('wp_mail')) {
            require_once(ABSPATH . 'wp-includes/pluggable.php');
        }

     $products = json_decode($order['products'], true);
    
    // Prepare product details HTML
    $product_details_html = '<table style="width:100%; border-collapse: collapse; margin-bottom: 20px;">';
    
    $total_amount = 0;
    foreach ($products as $product) {
        $line_total = floatval($product['price']) * intval($product['quantity'] ?? 1);
        $total_amount += $line_total;
        
        $product_details_html .= sprintf(
            '<tr>
                <td style="padding:10px; text-align:left;">%s</td>
                <td style="padding:10px; text-align:right;">%d × %.2f %s</td>
            </tr>',
            esc_html($product['name'] ?? 'Unknown Product'),
            intval($product['quantity'] ?? 1),
            $line_total,
            get_option('cs_settings')['currency'] ?? 'SEK'
        );
    }
    $product_details_html .= sprintf(
        '<tr style="font-weight:bold; border-top:1px solid #ddd;">
            <td style="padding:10px; text-align:left;">Order Total</td>
            <td style="padding:10px; text-align:right;">%.2f %s</td>
        </tr>',
        $total_amount,
        get_option('cs_settings')['currency'] ?? 'SEK'
    );
    $product_details_html .= '</table>';

    // Get current user (seller) details
    $current_user = wp_get_current_user();
    $association_name = get_user_meta($current_user->ID, 'cs_school', true) ?: get_bloginfo('name');

    // Email subject and headers
    $subject = sprintf('Order #%s Confirmation - Klubb for saljning', 
        $order['id'] ?? 'N/A'
    );
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        "From: Klubb for saljning <{$current_user->user_email}>"
    );
	$current_user = wp_get_current_user();
    $association_name = get_user_meta($current_user->ID, 'cs_school', true) ?: get_bloginfo('name');
    $seller_team = get_user_meta($current_user->ID, 'cs_team', true) ?: 'N/A';
    $seller_activity_type = get_user_meta($current_user->ID, 'cs_activity_type', true) ?: 'N/A';
    $print_date = date('F d', strtotime('+1 day'));
    $delivery_date = date('F d', strtotime('+7 days'));

    // Enhanced email body
    $message = sprintf(
        '<html>
        <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333; background-color: #f4f4f4;">
            <div style="background-color: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden;">
                <div style="background-color: #4CAF50; color: white; padding: 20px; text-align: center;">
                    <h1 style="margin: 0; font-size: 24px;">Order confirmed!</h1>
                </div>
                
                <div style="padding: 20px;">
                    <p style="color: #666; line-height: 1.6;">
                        Your order will be processed and shipped soon. We\'ll send you an email with tracking details when it goes out.
                    </p>
                    
                    <div style="background-color: #f9f9f9; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
                        <p style="margin: 0; color: #333;">
                            <strong>Order #%s</strong> will be processed on %s 
                            and should arrive by %s
                        </p>
                    </div>
                    
                    <div style="border-top: 1px solid #eee; border-bottom: 1px solid #eee; padding: 15px 0; margin: 20px 0;">
                        %s
                    </div>
                    
                    <div style="background-color: #f9f9f9; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #4CAF50;">Delivery Details</h3>
                        <p style="margin: 5px 0; color: #666;"><strong>Name:</strong> %s</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Address:</strong> %s</p>
                    </div>
                    
                    <div style="background-color: #f9f9f9; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #4CAF50;">Club Leader Information</h3>
                        <p style="margin: 5px 0; color: #666;"><strong>Name:</strong> %s</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Association:</strong> %s</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Team/Class:</strong> %s</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Activity Type:</strong> %s</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Contact Email:</strong> %s</p>
                    </div>
                    
                    %s
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <p style="color: #666;">
                            If you need to cancel, email <a href="mailto:%s" style="color: #4CAF50;">%s</a> 
                            within 30 minutes with your order number.
                        </p>
                    </div>
                </div>
                
                <div style="background-color: #4CAF50; color: white; padding: 15px; text-align: center;">
                    <p style="margin: 0;">Questions? Email us at <a href="mailto:%s" style="color: white;">%s</a></p>
                </div>
            </div>
        </body>
        </html>',
        esc_html($order['id'] ?? 'N/A'),
        $print_date,
        $delivery_date,
        $product_details_html,
        esc_html($order['customer_name']),
        esc_html($order['address'] ?? 'N/A'),
        esc_html($current_user->display_name),
        esc_html($association_name),
        esc_html($seller_team),
        esc_html($seller_activity_type),
        esc_html($current_user->user_email),
        // Notes section (conditionally added)
        !empty($order['notes']) ? 
            '<div style="background-color: #f9f9f9; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #4CAF50;">Additional Notes</h3>
                <p style="margin: 5px 0; color: #666;">' . esc_html($order['notes']) . '</p>
            </div>' : 
            '',
        esc_html($current_user->user_email),
        esc_html($current_user->user_email),
        esc_html($current_user->user_email),
        esc_html($current_user->user_email)
    );

    // Send email
    $email_sent = wp_mail($customer_email, $subject, $message, $headers);
    
    // Log email sending result
    if ($email_sent) {
        error_log('Order confirmation email sent successfully to ' . $customer_email);
    } else {
        error_log('Failed to send order confirmation email to ' . $customer_email);
    }

    return $email_sent;
}
}

// Initialize the class
CS_Order_Confirmation::init();