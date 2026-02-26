<div class="cs-dashboard">
    <div class="cs-tabs">
        <ul class="cs-tab-list">
            <?php 
            $first_tab = true;
            foreach ($tabs as $tab_id => $tab) : 
                $active_class = $first_tab ? ' active' : '';
                $first_tab = false;
                
                // Default icon if not set
                $default_icons = array(
                    'assign-product' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
                    'manage-children' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
                    'sales-material' => '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line>',
                    'add-order' => '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>',
                    'orders' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
                    'kafeteria' => '<path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line>',
                    'stats' => '<circle cx="12" cy="12" r="10"></circle><polyline points="9 12 11 14 15 10"></polyline>'
                );
                
                $icon_svg = isset($default_icons[$tab_id]) ? $default_icons[$tab_id] : '<circle cx="12" cy="12" r="10"></circle>';
            ?>
            <li class="cs-tab-item<?php echo $active_class; ?>" data-tab="<?php echo esc_attr($tab_id); ?>">
                <svg class="cs-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <?php echo $icon_svg; ?>
                </svg>
                <span class="cs-tab-label"><?php echo esc_html($tab['title']); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        
        <!-- Tab Content -->
        <div class="cs-tab-content">
            <?php 
            $first = true;
            foreach ($tabs as $tab_id => $tab) : 
                $class = $first ? 'cs-tab-pane active' : 'cs-tab-pane';
                $first = false;
            ?>
                <div class="<?php echo esc_attr($class); ?>" id="tab-<?php echo esc_attr($tab_id); ?>">
                    <?php 
                    if (isset($tab['content_callback']) && is_callable($tab['content_callback'])) {
                        call_user_func($tab['content_callback']); 
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// Debug: Log tabs to console
console.log('🔍 Dashboard loaded with tabs:', <?php echo json_encode(array_keys($tabs)); ?>);
console.log('🔍 Total tab count:', <?php echo count($tabs); ?>);
</script>