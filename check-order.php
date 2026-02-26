<?php
// Temporary debug script - DELETE AFTER USE
require_once('wp-load.php');

global $wpdb;

// Get the most recent order
$latest_order = $wpdb->get_row("
    SELECT id, customer_name, sale_amount, customer_pays, 
           (customer_pays - sale_amount) as calculated_profit
    FROM {$wpdb->prefix}cs_sales 
    ORDER BY id DESC 
    LIMIT 1
", ARRAY_A);

echo "<h2>Latest Order (ID: {$latest_order['id']})</h2>";
echo "<pre>";
echo "Customer: {$latest_order['customer_name']}\n";
echo "sale_amount (NI betalar): {$latest_order['sale_amount']}\n";
echo "customer_pays (Kunden betalar): {$latest_order['customer_pays']}\n";
echo "Calculated Profit: {$latest_order['calculated_profit']}\n";
echo "</pre>";

// Show exact column types
$table_info = $wpdb->get_results("DESCRIBE {$wpdb->prefix}cs_sales", ARRAY_A);
echo "<h3>Table Structure:</h3><pre>";
foreach ($table_info as $column) {
    if (in_array($column['Field'], ['sale_amount', 'customer_pays', 'profit'])) {
        print_r($column);
    }
}
echo "</pre>";
?>
