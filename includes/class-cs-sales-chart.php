<?php
class CS_Sales_Chart {
    /**
     * Register AJAX action for fetching sales chart data
     */
    public static function init_ajax_hooks() {
        add_action('wp_ajax_cs_get_sales_chart_data', array(__CLASS__, 'get_sales_chart_data'));
        add_action('wp_ajax_nopriv_cs_get_sales_chart_data', array(__CLASS__, 'get_sales_chart_data'));
    }

    /**
     * Fetch sales data for different chart periods
     */
    public static function get_sales_chart_data() {
        // Verify nonce
        check_ajax_referer('cs-ajax-nonce', 'nonce');

        // Get current user ID
        $user_id = get_current_user_id();

        // Get requested period
        $period = sanitize_text_field($_POST['period']);

        // Validate period
        $allowed_periods = ['month', 'quarter', 'year'];
        if (!in_array($period, $allowed_periods)) {
            wp_send_json_error('Invalid period');
            return;
        }

        global $wpdb;

        // Prepare the query based on period
        switch ($period) {
            case 'month':
                $data = self::get_monthly_sales_data($user_id);
                break;
            case 'quarter':
                $data = self::get_quarterly_sales_data($user_id);
                break;
            case 'year':
                $data = self::get_yearly_sales_data($user_id);
                break;
        }

        // Send JSON response
        wp_send_json_success($data);
    }

    /**
     * Get all user IDs to include (parent + children)
     */
    private static function get_user_ids_to_include($user_id) {
        $user_ids = array($user_id);
        
        // Check if user can manage children
        if (CS_Child_Manager::can_manage_children($user_id)) {
            $children = CS_Child_Manager::get_parent_children($user_id);
            if (!empty($children)) {
                foreach ($children as $child) {
                    $user_ids[] = $child['id'];
                }
            }
        }
        
        return $user_ids;
    }

    /**
     * Get monthly sales data including child sales
     */
    private static function get_monthly_sales_data($user_id) {
        global $wpdb;
        
        // Get all user IDs to include
        $user_ids = self::get_user_ids_to_include($user_id);
        $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));

        // Get sales for the last 6 months
        $query = $wpdb->prepare(
            "SELECT 
                DATE_FORMAT(sale_date, '%%Y-%%m') AS month_key,
                DATE_FORMAT(sale_date, '%%b') AS month,
                SUM(sale_amount) AS total_sales,
                COUNT(*) AS sale_count
            FROM {$wpdb->prefix}cs_sales
            WHERE user_id IN ($placeholders)
            AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month_key
            ORDER BY month_key",
            ...$user_ids
        );

        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Fill in missing months with zero values
        $all_months = array();
        for ($i = 5; $i >= 0; $i--) {
            $month_key = date('Y-m', strtotime("-$i months"));
            $month_name = date('M', strtotime("-$i months"));
            $all_months[$month_key] = array(
                'month' => $month_name,
                'total_sales' => 0,
                'sale_count' => 0
            );
        }
        
        // Update with actual data
        foreach ($results as $result) {
            $all_months[$result['month_key']] = array(
                'month' => $result['month'],
                'total_sales' => floatval($result['total_sales']),
                'sale_count' => intval($result['sale_count'])
            );
        }

        // Prepare data for Chart.js
        $chart_data = array_values($all_months);
        
        return [
            'labels' => array_column($chart_data, 'month'),
            'datasets' => [
                [
                    'label' => 'Monthly Sales (SEK)',
                    'data' => array_column($chart_data, 'total_sales'),
                    'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
                    'borderColor' => 'rgba(76, 175, 80, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4 // Smooth curves
                ]
            ],
            'metadata' => [
                'sale_counts' => array_column($chart_data, 'sale_count'),
                'includes_children' => count($user_ids) > 1
            ]
        ];
    }

    /**
     * Get quarterly sales data including child sales
     */
    private static function get_quarterly_sales_data($user_id) {
        global $wpdb;
        
        // Get all user IDs to include
        $user_ids = self::get_user_ids_to_include($user_id);
        $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));

        // Get sales for the last 4 quarters
        $query = $wpdb->prepare(
            "SELECT 
                CONCAT(YEAR(sale_date), '-Q', QUARTER(sale_date)) AS quarter_key,
                CONCAT('Q', QUARTER(sale_date)) AS quarter,
                YEAR(sale_date) AS year,
                SUM(sale_amount) AS total_sales,
                COUNT(*) AS sale_count
            FROM {$wpdb->prefix}cs_sales
            WHERE user_id IN ($placeholders)
            AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
            GROUP BY quarter_key
            ORDER BY quarter_key",
            ...$user_ids
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        // Prepare data for Chart.js
        return [
            'labels' => array_map(function($row) {
                return $row['quarter'] . ' ' . $row['year'];
            }, $results),
            'datasets' => [
                [
                    'label' => 'Quarterly Sales (SEK)',
                    'data' => array_column($results, 'total_sales'),
                    'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
                    'borderColor' => 'rgba(76, 175, 80, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4
                ]
            ],
            'metadata' => [
                'sale_counts' => array_column($results, 'sale_count'),
                'includes_children' => count($user_ids) > 1
            ]
        ];
    }

    /**
     * Get yearly sales data including child sales
     */
    private static function get_yearly_sales_data($user_id) {
        global $wpdb;
        
        // Get all user IDs to include
        $user_ids = self::get_user_ids_to_include($user_id);
        $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));

        // Get sales for the last 3 years
        $query = $wpdb->prepare(
            "SELECT 
                YEAR(sale_date) AS year,
                SUM(sale_amount) AS total_sales,
                COUNT(*) AS sale_count
            FROM {$wpdb->prefix}cs_sales
            WHERE user_id IN ($placeholders)
            AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)
            GROUP BY year
            ORDER BY year",
            ...$user_ids
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        // Prepare data for Chart.js
        return [
            'labels' => array_column($results, 'year'),
            'datasets' => [
                [
                    'label' => 'Yearly Sales (SEK)',
                    'data' => array_column($results, 'total_sales'),
                    'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
                    'borderColor' => 'rgba(76, 175, 80, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4
                ]
            ],
            'metadata' => [
                'sale_counts' => array_column($results, 'sale_count'),
                'includes_children' => count($user_ids) > 1
            ]
        ];
    }
}

// Initialize AJAX hooks
add_action('init', array('CS_Sales_Chart', 'init_ajax_hooks'));