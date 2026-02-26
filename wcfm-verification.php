/**
 * WCFM Settings Verification Test
 * 
 * Add this shortcode to a page to verify the fixes are working:
 * [wcfm_settings_test]
 */

add_shortcode('wcfm_settings_test', function() {
    ob_start();
    ?>
    <div style="padding: 20px; background: #f5f5f5; border: 2px solid #0073aa; border-radius: 8px; max-width: 800px; margin: 20px auto;">
        <h2 style="color: #0073aa; margin-top: 0;">✅ WCFM Settings Fix Verification</h2>
        
        <div style="background: white; padding: 15px; margin: 10px 0; border-radius: 4px;">
            <h3 style="margin-top: 0;">1. Echo Statement Check</h3>
            <?php
            // Check if the problematic echo has been removed
            $file_content = file_get_contents(CS_PLUGIN_DIR . 'club-sales.php');
            $bad_pattern = 'echo $vat_rate;' . "\n" . '    return floatval($vat_rate);';
            
            if (strpos($file_content, $bad_pattern) !== false) {
                echo '<p style="color: red;">❌ <strong>FAILED:</strong> Echo statement still present in cs_get_current_vat_rate()</p>';
                echo '<p>Action needed: Remove the echo statement on line ~673</p>';
            } else {
                echo '<p style="color: green;">✅ <strong>PASSED:</strong> Echo statement removed from cs_get_current_vat_rate()</p>';
            }
            ?>
        </div>
        
        <div style="background: white; padding: 15px; margin: 10px 0; border-radius: 4px;">
            <h3 style="margin-top: 0;">2. AJAX Protection Checks</h3>
            <?php
            $protections = [
                'cs_sync_tax_class_with_vat' => 'ACF Save Hook',
                'cs_sync_tax_class_with_vat_wcfm' => 'WCFM Product Hook',
                'cs_sync_vat_from_tax_class_change' => 'Meta Update Hook'
            ];
            
            foreach ($protections as $function => $name) {
                if (function_exists($function)) {
                    $reflection = new ReflectionFunction($function);
                    $source = file_get_contents($reflection->getFileName());
                    $start = $reflection->getStartLine();
                    $end = $reflection->getEndLine();
                    $function_code = implode("\n", array_slice(explode("\n", $source), $start - 1, $end - $start + 1));
                    
                    if (strpos($function_code, 'wp_doing_ajax') !== false && strpos($function_code, 'wcfm') !== false) {
                        echo "<p style='color: green;'>✅ <strong>$name:</strong> AJAX protection enabled</p>";
                    } else {
                        echo "<p style='color: red;'>❌ <strong>$name:</strong> Missing AJAX protection</p>";
                    }
                } else {
                    echo "<p style='color: orange;'>⚠️ <strong>$name:</strong> Function not found</p>";
                }
            }
            ?>
        </div>
        
        <div style="background: white; padding: 15px; margin: 10px 0; border-radius: 4px;">
            <h3 style="margin-top: 0;">3. Active Hooks Summary</h3>
            <?php
            global $wp_filter;
            $critical_hooks = ['acf/save_post', 'updated_post_meta', 'wcfm_product_manage_after_process', 'save_post'];
            
            foreach ($critical_hooks as $hook_name) {
                if (isset($wp_filter[$hook_name])) {
                    $count = 0;
                    foreach ($wp_filter[$hook_name] as $priority => $callbacks) {
                        $count += count($callbacks);
                    }
                    echo "<p><strong>{$hook_name}:</strong> {$count} callback(s) registered</p>";
                }
            }
            ?>
        </div>
        
        <div style="background: #ffffcc; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #ffcc00;">
            <h3 style="margin-top: 0;">Next Steps</h3>
            <ol style="margin-bottom: 0;">
                <li>Clear your browser cache (Ctrl+Shift+R)</li>
                <li>Go to WCFM Settings page</li>
                <li>Try adding a custom field</li>
                <li>Check browser console for errors</li>
                <li>Verify the field saves successfully</li>
            </ol>
        </div>
        
        <div style="background: white; padding: 15px; margin: 10px 0; border-radius: 4px;">
            <h3 style="margin-top: 0;">Debug Information</h3>
            <ul style="font-family: monospace; font-size: 12px; margin-bottom: 0;">
                <li><strong>Plugin Version:</strong> <?php echo CS_VERSION; ?></li>
                <li><strong>WP AJAX:</strong> <?php echo wp_doing_ajax() ? 'Yes' : 'No'; ?></li>
                <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                <li><strong>WooCommerce:</strong> <?php echo class_exists('WooCommerce') ? WC()->version : 'Not installed'; ?></li>
                <li><strong>ACF:</strong> <?php echo function_exists('get_field') ? 'Active' : 'Not active'; ?></li>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
