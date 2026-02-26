<div class="cs-dashboard">
    <div class="cs-tabs">
        <ul class="cs-tab-list">
            <?php 
            $first = true;
            foreach ($tabs as $tab_id => $tab) : 
                $class = $first ? 'cs-tab-item active' : 'cs-tab-item';
                $first = false;
            ?>
                <li class="<?php echo esc_attr($class); ?>" data-tab="<?php echo esc_attr($tab_id); ?>">
                    <span class="<?php echo esc_attr($tab['icon']); ?>"></span> <?php echo esc_html($tab['title']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
        
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
                    } else {
                        echo '<p>No content available for this tab.</p>';
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>