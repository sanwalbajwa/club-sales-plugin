<?php
/**
 * Emergency Script to Fix Swapped Order Values
 * 
 * Run this file ONCE to swap the customer_pays and sale_amount values
 * that were stored incorrectly in the database.
 * 
 * BACKUP YOUR DATABASE BEFORE RUNNING THIS!
 * 
 * To run: Visit http://yoursite.com/wp-content/plugins/club-sales/fix-swapped-orders.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check - only allow admins
if (!current_user_can('manage_options')) {
    die('Unauthorized access. Only administrators can run this script.');
}

global $wpdb;

// First, check if customer_pays column exists
$column_check = $wpdb->get_results($wpdb->prepare(
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = %s 
    AND TABLE_NAME = %s 
    AND COLUMN_NAME = 'customer_pays'",
    DB_NAME,
    $wpdb->prefix . 'cs_sales'
));

if (empty($column_check)) {
    echo "<h2>❌ Error: customer_pays column does not exist!</h2>";
    echo "<p>Adding the column now...</p>";
    
    $result = $wpdb->query("ALTER TABLE {$wpdb->prefix}cs_sales ADD COLUMN customer_pays decimal(10,2) DEFAULT 0.00 AFTER sale_amount");
    
    if ($result === false) {
        die("<p>Failed to add customer_pays column: " . $wpdb->last_error . "</p>");
    }
    
    echo "<p>✅ Column added successfully!</p>";
}

// Get all orders where values might be swapped
// Indicator: customer_pays < sale_amount (customer pays less than what you pay? That's backwards!)
$swapped_orders = $wpdb->get_results(
    "SELECT id, customer_name, sale_amount, customer_pays, profit 
    FROM {$wpdb->prefix}cs_sales 
    WHERE customer_pays < sale_amount 
    OR profit < 0
    ORDER BY id DESC"
);

echo "<h1>🔍 Swapped Order Detection and Fix</h1>";
echo "<p>Found " . count($swapped_orders) . " orders with potential issues.</p>";

if (empty($swapped_orders)) {
    echo "<p>✅ No orders need fixing!</p>";
    exit;
}

// Show preview before fixing
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<h2>⚠️ Preview of Orders to Fix:</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr>
        <th>Order ID</th>
        <th>Customer</th>
        <th>CURRENT<br/>Customer Pays</th>
        <th>CURRENT<br/>You Pay</th>
        <th>CURRENT<br/>Profit</th>
        <th>→</th>
        <th>FIXED<br/>Customer Pays</th>
        <th>FIXED<br/>You Pay</th>
        <th>FIXED<br/>Profit</th>
    </tr>";
    
    foreach ($swapped_orders as $order) {
        $current_customer_pays = floatval($order->customer_pays);
        $current_sale_amount = floatval($order->sale_amount);
        $current_profit = floatval($order->profit);
        
        // Swapped values
        $fixed_customer_pays = $current_sale_amount;
        $fixed_sale_amount = $current_customer_pays;
        $fixed_profit = $fixed_customer_pays - $fixed_sale_amount;
        
        echo "<tr>";
        echo "<td>{$order->id}</td>";
        echo "<td>{$order->customer_name}</td>";
        echo "<td style='background:#ffcccc'>{$current_customer_pays} SEK</td>";
        echo "<td style='background:#ffcccc'>{$current_sale_amount} SEK</td>";
        echo "<td style='background:#ffcccc'>{$current_profit} SEK</td>";
        echo "<td>→</td>";
        echo "<td style='background:#ccffcc'><strong>{$fixed_customer_pays} SEK</strong></td>";
        echo "<td style='background:#ccffcc'><strong>{$fixed_sale_amount} SEK</strong></td>";
        echo "<td style='background:#ccffcc'><strong>{$fixed_profit} SEK</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>⚠️ IMPORTANT!</h2>";
    echo "<ol>";
    echo "<li><strong>BACKUP YOUR DATABASE FIRST!</strong></li>";
    echo "<li>Review the preview above carefully</li>";
    echo "<li>Make sure the 'FIXED' values make sense</li>";
    echo "<li>Customer should pay MORE than what you pay to supplier</li>";
    echo "</ol>";
    
    echo "<p><a href='?confirm=yes' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; font-size: 18px; border-radius: 5px; display: inline-block;'>✅ YES, FIX THESE ORDERS</a></p>";
    echo "<p><a href='javascript:history.back()' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>❌ Cancel</a></p>";
    
    exit;
}

// Actually perform the swap
echo "<h2>🔧 Fixing Orders...</h2>";

$fixed_count = 0;
$failed_count = 0;

foreach ($swapped_orders as $order) {
    $current_customer_pays = floatval($order->customer_pays);
    $current_sale_amount = floatval($order->sale_amount);
    
    // Swap the values
    $new_customer_pays = $current_sale_amount;
    $new_sale_amount = $current_customer_pays;
    $new_profit = $new_customer_pays - $new_sale_amount;
    
    $result = $wpdb->update(
        $wpdb->prefix . 'cs_sales',
        array(
            'customer_pays' => $new_customer_pays,
            'sale_amount' => $new_sale_amount,
            'profit' => $new_profit
        ),
        array('id' => $order->id),
        array('%f', '%f', '%f'),
        array('%d')
    );
    
    if ($result !== false) {
        echo "<p>✅ Order #{$order->id} fixed: Customer pays {$new_customer_pays} SEK, You pay {$new_sale_amount} SEK, Profit {$new_profit} SEK</p>";
        $fixed_count++;
    } else {
        echo "<p>❌ Failed to fix Order #{$order->id}: " . $wpdb->last_error . "</p>";
        $failed_count++;
    }
}

echo "<h2>✅ Done!</h2>";
echo "<p>Fixed: {$fixed_count} orders</p>";
echo "<p>Failed: {$failed_count} orders</p>";

echo "<p><strong>Please refresh your orders page to see the corrected values.</strong></p>";
echo "<p><a href='#' onclick='window.location.reload()'>Reload this page to verify</a></p>";

// After fixing, recommend deleting this file
echo "<hr>";
echo "<h3>⚠️ Security Recommendation</h3>";
echo "<p>Please DELETE this file after you've confirmed the fix worked:</p>";
echo "<code>wp-content/plugins/club-sales/fix-swapped-orders.php</code>";
