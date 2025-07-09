<?php
class CS_Klarna {
    private static function get_api_credentials() {
        $settings = get_option('cs_settings', array());
        
        return array(
            'merchant_id' => $settings['klarna_merchant_id'] ?? '',
            'shared_secret' => $settings['klarna_shared_secret'] ?? '',
            'test_mode' => isset($settings['klarna_test_mode']) && $settings['klarna_test_mode'] === 'yes'
        );
    }
    
    private static function get_api_url() {
        $credentials = self::get_api_credentials();
        
        if ($credentials['test_mode']) {
            return 'https://api.playground.klarna.com/';
        } else {
            return 'https://api.klarna.com/';
        }
    }
    
    public static function create_order($sale_ids) {
        global $wpdb;
        
        if (empty($sale_ids)) {
            throw new Exception('No sales provided');
        }
        
        $sale_ids_str = implode(',', array_map('intval', $sale_ids));
        $sales = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cs_sales WHERE id IN ($sale_ids_str)");
        
        if (empty($sales)) {
            throw new Exception('No sales found');
        }
        
        $credentials = self::get_api_credentials();
        
        if (empty($credentials['merchant_id']) || empty($credentials['shared_secret'])) {
            throw new Exception('Klarna API credentials not configured');
        }
        
        // Create a cart from the sales
        $order_lines = array();
        $total_amount = 0;
        
        foreach ($sales as $sale) {
            $products = json_decode($sale->products, true);
            
            if (!empty($products)) {
                foreach ($products as $product) {
                    $order_lines[] = array(
                        'name' => $product['name'],
                        'quantity' => 1,
                        'unit_price' => intval($product['price'] * 100), // Klarna uses amounts in cents
                        'tax_rate' => 2500, // 25% tax rate (Sweden)
                        'total_amount' => intval($product['price'] * 100),
                        'total_tax_amount' => intval($product['price'] * 0.2 * 100) // 20% of price is tax
                    );
                    
                    $total_amount += $product['price'];
                }
            } else {
                // If no products, just add the total as a single line
                $order_lines[] = array(
                    'name' => 'Sale #' . $sale->id,
                    'quantity' => 1,
                    'unit_price' => intval($sale->sale_amount * 100),
                    'tax_rate' => 2500,
                    'total_amount' => intval($sale->sale_amount * 100),
                    'total_tax_amount' => intval($sale->sale_amount * 0.2 * 100)
                );
                
                $total_amount += $sale->sale_amount;
            }
        }
        
        // Create Klarna order
        $order_data = array(
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-SE',
            'order_amount' => intval($total_amount * 100),
            'order_tax_amount' => intval($total_amount * 0.2 * 100),
            'order_lines' => $order_lines,
            'merchant_urls' => array(
                'terms' => site_url('/terms/'),
                'checkout' => site_url('/checkout/'),
                'confirmation' => site_url('/confirmation/'),
                'push' => site_url('/wc-api/klarna_push/'),
            )
        );
        
        // Call Klarna API
        $api_url = self::get_api_url() . 'checkout/v3/orders';
        
        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($credentials['merchant_id'] . ':' . $credentials['shared_secret'])
            ),
            'body' => json_encode($order_data)
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['checkout_url'])) {
            throw new Exception('Invalid response from Klarna');
        }
        
        // Update sales with Klarna order ID
        if (!empty($body['order_id'])) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}cs_sales SET klarna_order_id = %s WHERE id IN ($sale_ids_str)",
                $body['order_id']
            ));
        }
        
        return $body['checkout_url'];
    }
    
    public static function get_order($order_id) {
        $credentials = self::get_api_credentials();
        
        if (empty($credentials['merchant_id']) || empty($credentials['shared_secret'])) {
            throw new Exception('Klarna API credentials not configured');
        }
        
        $api_url = self::get_api_url() . 'checkout/v3/orders/' . $order_id;
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($credentials['merchant_id'] . ':' . $credentials['shared_secret'])
            )
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}