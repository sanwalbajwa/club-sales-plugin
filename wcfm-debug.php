<?php
/**
 * WCFM Settings Debug Helper
 * Temporarily add to diagnose WCFM settings save issues
 * 
 * Add this line to club-sales.php to activate:
 * require_once CS_PLUGIN_DIR . 'wcfm-debug.php';
 */

// Log all WCFM-related AJAX requests
add_action('wp_ajax_wcfmmp_save_settings', 'cs_debug_wcfm_settings_before', 1);
add_action('wp_ajax_wcfm_ajax_controller', 'cs_debug_wcfm_ajax_before', 1);

function cs_debug_wcfm_settings_before() {
    error_log('=== WCFM Settings Save Started ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Start output buffering to catch any stray output
    ob_start(function($buffer) {
        if (!empty($buffer)) {
            error_log('⚠️ WCFM Settings - Unexpected output detected: ' . $buffer);
        }
        return ''; // Clear the buffer
    });
}

function cs_debug_wcfm_ajax_before() {
    error_log('=== WCFM AJAX Request ===');
    error_log('Action: ' . ($_POST['action'] ?? 'unknown'));
    error_log('Controller: ' . ($_POST['controller'] ?? 'unknown'));
    
    // Start output buffering
    ob_start(function($buffer) {
        if (!empty($buffer)) {
            error_log('⚠️ WCFM AJAX - Unexpected output: ' . $buffer);
        }
        return '';
    });
}

// Monitor all hooks that fire during WCFM operations
add_action('all', function($hook) {
    if (wp_doing_ajax() && isset($_POST['action']) && strpos($_POST['action'], 'wcfm') !== false) {
        static $hooks = [];
        if (!isset($hooks[$hook])) {
            $hooks[$hook] = true;
            // Only log significant hooks
            if (strpos($hook, 'save') !== false || 
                strpos($hook, 'update') !== false || 
                strpos($hook, 'meta') !== false ||
                strpos($hook, 'acf') !== false) {
                error_log('Hook fired during WCFM: ' . $hook);
            }
        }
    }
}, 1);

// Add filter to ensure JSON response isn't corrupted
add_filter('wp_die_ajax_handler', function($function) {
    if (wp_doing_ajax() && isset($_POST['action']) && strpos($_POST['action'], 'wcfm') !== false) {
        return function($message) {
            // Ensure buffer is clean before dying
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            die($message);
        };
    }
    return $function;
}, 1);
