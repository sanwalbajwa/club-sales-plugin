<?php
/**
 * Kafeteria Tab Template
 * Displays WooCommerce products from the "Kafeteria" category with filtering
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cs-kafeteria-container">
    <!-- Header Section -->
    <div class="cs-kafeteria-header">
        <div class="cs-kafeteria-header-content">
            <div class="cs-kafeteria-header-left">
                <h2 class="cs-kafeteria-title"><?php _e('Kafeteria E-handel', 'club-sales'); ?></h2>
            </div>
            <button id="cs-cart-icon-btn" class="cs-cart-icon-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span id="cs-cart-badge" class="cs-cart-badge" style="display: none;">0</span>
                <span class="cs-cart-icon-label"><?php _e('Varukorg', 'club-sales'); ?></span>
            </button>
        </div>
        <p class="cs-kafeteria-subtitle"><?php _e('Beställ allt ni behöver för laget eller klassens kafeteria - levererat direkt till er', 'club-sales'); ?></p>
        <!-- Search Bar -->
        <div class="cs-kafeteria-search-container">
            <svg class="cs-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text" id="cs-kafeteria-search" class="cs-kafeteria-search-input" placeholder="<?php _e('Sök efter produkter, leverantörer...', 'club-sales'); ?>">
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="cs-kafeteria-content">
        <!-- Sidebar with Categories -->
        <aside class="cs-kafeteria-sidebar">
            <div class="cs-categories-header">
                <h3><?php _e('Filter', 'club-sales'); ?></h3>
            </div>
            <div class="cs-category-accordion active">
                <div class="cs-category-accordion-header">
                    <span class="cs-category-accordion-title"><?php _e('Kategorier', 'club-sales'); ?></span>
                    <svg class="cs-category-accordion-icon" style="transform: rotate(180deg);" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
                <div class="cs-category-accordion-content" style="display: block;">
                    <div class="cs-categories-list">
                        <button class="cs-category-item active" data-category="alla-produkter" data-category-id="all">
                            <svg class="cs-category-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            </svg>
                            <span class="cs-category-label"><?php _e('Alla produkter', 'club-sales'); ?></span>
                            <span class="cs-category-count" data-category="all">38</span>
                        </button>
                        
                        <!-- Categories will be loaded dynamically via AJAX -->
                        <div id="cs-dynamic-categories"></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Products Grid -->
        <main class="cs-kafeteria-main">
            <!-- Sort and Filter Controls -->
            <div class="cs-kafeteria-controls">
                <div class="cs-product-count">
                    <?php _e('Visar', 'club-sales'); ?> <span id="cs-visible-count">38</span> <?php _e('produkter', 'club-sales'); ?>
                </div>
                <div class="cs-sort-controls">
                    <label for="cs-sort-select"><?php _e('Populärast', 'club-sales'); ?></label>
                    <select id="cs-sort-select" class="cs-sort-select">
                        <option value="popularity"><?php _e('Populärast', 'club-sales'); ?></option>
                        <option value="price-asc"><?php _e('Pris: Lägst först', 'club-sales'); ?></option>
                        <option value="price-desc"><?php _e('Pris: Högst först', 'club-sales'); ?></option>
                        <option value="name-asc"><?php _e('Namn: A-Ö', 'club-sales'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Products Grid Container -->
            <div id="cs-kafeteria-products" class="cs-kafeteria-products-grid">
                <!-- Loading State -->
                <div class="cs-loading-state">
                    <div class="cs-spinner"></div>
                    <p><?php _e('Laddar produkter...', 'club-sales'); ?></p>
                </div>
            </div>

            <!-- Empty State -->
            <div id="cs-kafeteria-empty" class="cs-kafeteria-empty" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <p><?php _e('Inga produkter hittades', 'club-sales'); ?></p>
                <small><?php _e('Försök med ett annat sökord eller kategori', 'club-sales'); ?></small>
            </div>
        </main>
    </div>

    <!-- Cart Modal -->
    <div id="cs-cart-modal" class="cs-cart-modal" style="display: none;">
        <div class="cs-cart-modal-overlay"></div>
        <div class="cs-cart-modal-content">
            <div class="cs-cart-modal-header">
                <h3><?php _e('Varukorg', 'club-sales'); ?></h3>
                <button id="cs-cart-modal-close" class="cs-cart-modal-close">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <div id="cs-kafeteria-cart-items" class="cs-cart-modal-items">
                <!-- Cart items will be added here dynamically -->
                <div class="cs-cart-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <p><?php _e('Varukorgen är tom', 'club-sales'); ?></p>
                </div>
            </div>
            
            <div id="cs-kafeteria-cart-footer" class="cs-cart-modal-footer" style="display: none;">
                <div class="cs-cart-total">
                    <span><?php _e('Totalt:', 'club-sales'); ?></span>
                    <strong id="cs-kafeteria-cart-total">0 kr</strong>
                </div>
                <button id="cs-checkout-btn" class="cs-checkout-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                    <?php _e('Gå till kassan', 'club-sales'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
