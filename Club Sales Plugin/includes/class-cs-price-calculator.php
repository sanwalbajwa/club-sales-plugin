<?php
class CS_Price_Calculator {
    /**
     * Calculate total price with margin and VAT with Swedish rounding
     * 
     * @param float $base_price Base price of the product
     * @param float|null $margin_rate Custom margin rate (optional)
     * @param float|null $vat_rate Custom VAT rate (optional)
     * @param bool $use_swedish_rounding Whether to use Swedish rounding method (default: false)
     * @return array Detailed price breakdown
     */
    public static function calculate_price($base_price, $margin_rate = null, $vat_rate = null, $use_swedish_rounding = false) {
        // Default fallback values if no custom rates provided
        if ($margin_rate === null) {
            // Use global margin setting
            $margin_rate = floatval(get_option('club_sales_global_margin', 12));
        }
        
        // Default VAT rate (Swedish standard)
        $vat_rate = $vat_rate !== null ? floatval($vat_rate) : 25.0;
        
        // Validate inputs
        $base_price = floatval($base_price);
        
        // Calculate margin amount
        $margin_amount = $base_price * ($margin_rate / 100);
        
        // Price with margin
        $price_with_margin = $base_price + $margin_amount;
        
        // Calculate VAT amount
        $vat_amount = $price_with_margin * ($vat_rate / 100);
        
        // Total price
        $total_price = $price_with_margin + $vat_amount;
        
        // Apply Swedish rounding if specified
        if ($use_swedish_rounding) {
            $total_price = self::round_price_to_nearest_nine($total_price);
        }
        
        // Return detailed breakdown
        return [
            'base_price' => round($base_price, 2),
            'margin_rate' => round($margin_rate, 2),
            'margin_amount' => round($margin_amount, 2),
            'vat_rate' => round($vat_rate, 2),
            'vat_amount' => round($vat_amount, 2),
            'total_price' => round($total_price, 2),
            'rounded_price' => $use_swedish_rounding ? self::round_price_to_nearest_nine($total_price) : $total_price
        ];
    }

    /**
     * Round price to the nearest nine (Swedish rounding method)
     * 
     * @param float $price Price to round
     * @return float Rounded price
     */
    public static function round_price_to_nearest_nine($price) {
    // Round up to the nearest whole number first
    $rounded = ceil($price);

    // Get the last digit
    $last_digit = $rounded % 10;

    if ($last_digit <= 1) {
        // Round to previous 9 (e.g., 120 -> 119)
        $result = floor($rounded / 10) * 10 - 1;
    } else {
        // Round to current 10s group's 9 (e.g., 122 -> 129)
        $result = floor($rounded / 10) * 10 + 9;
    }

    return floatval($result);
}

    /**
     * Update product price meta with calculated values
     */
    public static function update_product_price_meta($product_id, $base_price, $margin_rate = null, $vat_rate = null) {
        // For parent users, use Swedish rounding
        $current_user = wp_get_current_user();
        $use_swedish_rounding = !CS_Child_Manager::is_child_user($current_user->ID);

        // Calculate prices
        $price_breakdown = self::calculate_price($base_price, $margin_rate, $vat_rate, $use_swedish_rounding);
        
        // Update custom meta for club sales without touching WooCommerce price
        update_post_meta($product_id, '_club_sales_base_price', $price_breakdown['base_price']);
        update_post_meta($product_id, '_club_sales_margin_rate', $price_breakdown['margin_rate']);
        update_post_meta($product_id, '_club_sales_margin_amount', $price_breakdown['margin_amount']);
        update_post_meta($product_id, '_club_sales_vat_rate', $price_breakdown['vat_rate']);
        update_post_meta($product_id, '_club_sales_vat_amount', $price_breakdown['vat_amount']);
        update_post_meta($product_id, '_club_sales_total_price', $price_breakdown['total_price']);
        
        // Add the Swedish rounded price separately
        update_post_meta($product_id, '_club_sales_rounded_price', $price_breakdown['rounded_price']);
    }
    
    /**
     * Get the calculated club sales price for a product
     */
    public static function get_club_sales_price($product_id, $use_rounding = false) {
    // Get the base price from the product
    $product = wc_get_product($product_id);
    if (!$product) {
        error_log("Cannot get product with ID: {$product_id}");
        return 0;
    }

    // Get base price
    $base_price = $product->get_regular_price();
    
    // Try to get custom margin and VAT from ACF or other custom fields
    $margin_rate = null;
    $vat_rate = null;

    // Check for custom margin using Advanced Custom Fields
    if (function_exists('get_field')) {
        $margin_rate = get_field('margin', $product_id);
        $vat_rate = get_field('vat', $product_id);
    }

    // If no custom margin, use global margin
    if ($margin_rate === null) {
        $margin_rate = floatval(get_option('club_sales_global_margin', 12));
    }

    // If no custom VAT, use default VAT
    if ($vat_rate === null) {
        $vat_rate = 25.0; // Swedish standard VAT
    }

    // Calculate the detailed price breakdown
    $price_details = self::calculate_price($base_price, $margin_rate, $vat_rate);

    // Determine if rounding should be applied
    // This is the key change - make rounding decision based on user role
    $current_user = wp_get_current_user();
    $apply_rounding = !CS_Child_Manager::is_child_user($current_user->ID);

    // Apply rounding if specified and user is a parent
    if ($use_rounding && $apply_rounding) {
        $total_price = self::round_price_to_nearest_nine($price_details['total_price']);
    } else {
        $total_price = $price_details['total_price'];
    }
    return $total_price;
}    
    /**
     * Auto-calculate product prices when saved
     */
    public static function auto_calculate_product_prices($post_id) {
        // Check if this is a WooCommerce product
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Get base price
        $product = wc_get_product($post_id);
        $base_price = $product->get_regular_price();
        
        // Update product price meta for club sales
        self::update_product_price_meta($post_id, $base_price);
    }

    /**
     * Detailed rounding test method for validation
     */
    public static function detailed_rounding_test() {
        ob_start();
        
        echo "Detailed Rounding Test:\n";
        echo "-------------------\n";
        
        $test_cases = [
            120 => 129,
            190 => 199,
            145 => 149,
            167 => 169,
            180 => 189,
            199 => 199,
            140 => 149,
            135 => 139
        ];
        
        foreach ($test_cases as $input => $expected) {
            $rounded = self::round_price_to_nearest_nine($input);
            $status = $rounded == $expected ? "PASS" : "FAIL";
            
            printf(
                "Input: %s, Rounded: %s, Expected: %s, Status: %s\n", 
                $input, 
                $rounded, 
                $expected, 
                $status
            );
        }
        
        return ob_get_clean();
    }
}

// Add hooks to calculate prices when products are saved
add_action('save_post', array('CS_Price_Calculator', 'auto_calculate_product_prices'), 20, 1);
add_action('woocommerce_update_product', array('CS_Price_Calculator', 'auto_calculate_product_prices'), 20, 1);

// Add this outside the class definition for testing purposes
add_shortcode('cs_detailed_rounding_test', function() {
    // Wrap in a pre tag for better readability
    return '<pre>' . CS_Price_Calculator::detailed_rounding_test() . '</pre>';
});