<div class="cs-section-header">
    <h2><?php _e('Assign Product', 'club-sales'); ?></h2>
    <div class="cs-search-container">
        <input type="text" id="product-search" placeholder="<?php _e('Search for products...', 'club-sales'); ?>">
        <button type="button" id="search-button">
            <span class="dashicons dashicons-search"></span>
        </button>
    </div>
</div>

<div class="cs-products-container">
    <div class="cs-products-list" id="products-list">
        <!-- Products will be loaded here -->
        <div class="cs-loading"><?php _e('Loading products...', 'club-sales'); ?></div>
    </div>
</div>