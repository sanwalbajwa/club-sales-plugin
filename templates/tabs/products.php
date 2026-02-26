<!-- Info Banner -->
<div class="cs-product-header">	
	<div class="cs-product-subheader">
    <div class="cs-info-banner">
        <div class="cs-info-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
        </div>
        <div class="cs-info-text">
            <strong><?php _e('Tips:', 'club-sales'); ?></strong> 
            <?php _e('Vissa produkter kan kombineras med', 'club-sales'); ?> 
            <strong><?php _e('specifika', 'club-sales'); ?></strong> 
            <?php _e('andra produkter från samma leverantör. Produkter med', 'club-sales'); ?>
            <span class="cs-combine-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
            </span>
            <?php _e('kan kombineras.', 'club-sales'); ?>
        </div>
    </div>
    
    <!-- Product Catalog Header -->
    <div class="cs-catalog-header">
        <div class="cs-catalog-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
        </div>
        <div class="cs-catalog-info">
            <h2><?php _e('Produktkatalog', 'club-sales'); ?></h2>
            <p><?php _e('Klicka på produkter för att välja dem', 'club-sales'); ?></p>
        </div>
    </div>
	</div>
</div>

<div class="cs-products-section">
    <!-- Shopping Cart Widget (Top Right) -->
    <!-- Shopping Cart Widget (Floating Bottom Right) -->
    <div class="cs-cart-widget collapsed" id="cs-cart-widget">
        <!-- Minimized State (FAB) -->
        <div class="cs-cart-fab">
            <div class="cs-cart-icon-wrapper">
                <span class="dashicons dashicons-cart"></span>
                <span class="cs-cart-count">0</span>
            </div>
            <div class="cs-cart-fab-label">
                <span class="cs-cart-fab-text"><?php _e('Produkter ni valt att sälja', 'club-sales'); ?></span>
                <span class="cs-cart-product-count">0 produkter</span>
            </div>
             <span class="dashicons dashicons-arrow-up-alt2 cs-toggle-icon"></span>
        </div>

        <!-- Expanded State (Popup) -->
        <div class="cs-cart-popup">
            <div class="cs-cart-header">
                <div class="cs-cart-icon">
                    <span class="dashicons dashicons-cart"></span>
                    <span class="cs-cart-count">0</span>
                </div>
                <div class="cs-cart-title">
                    <strong><?php _e('Produkter ni valt att sälja', 'club-sales'); ?></strong>
                    <span class="cs-cart-product-count">0 produkter</span>
                </div>
                <button class="cs-cart-minimize">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
            </div>
            <div class="cs-cart-body">
                <div id="cs-cart-items" class="cs-cart-items">
                    <!-- Selected products will appear here -->
                    <div class="cs-cart-empty">
                        <?php _e('Inga produkter valda', 'club-sales'); ?>
                    </div>
                </div>
                <div class="cs-cart-footer">
                    <button class="cs-continue-btn" id="cs-continue-to-manage">
                        <?php _e('Fortsätt till Hantera säljare', 'club-sales'); ?> →
                    </button>
                    <p class="cs-cart-note"><?php _e('0 produkter valda', 'club-sales'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Layout with Sidebar -->
    <div class="cs-products-layout">
        <!-- Sidebar Filter -->
        <div class="cs-products-sidebar">
            <div class="cs-filter-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
                <h3><?php _e('Kategorier', 'club-sales'); ?></h3>
            </div>
            <div id="category-filter-container" class="cs-category-checkbox-list">
                <!-- Checkboxes will be loaded here via JS -->
                <div class="cs-filter-loading">
                    <span class="dashicons dashicons-update cs-spin"></span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="cs-products-main">
            <!-- Search Section -->
            <div class="cs-search-container">
                <div class="cs-search-box full-width">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" id="product-search" placeholder="<?php _e('Sök efter produkter...', 'club-sales'); ?>">
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="cs-products-grid" id="products-list">
                <!-- Products will be loaded here via AJAX -->
                <div class="cs-loading-products">
                    <span class="dashicons dashicons-update cs-spin"></span>
                    <p><?php _e('Loading products...', 'club-sales'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
   
    <!-- Product Detail View (Hidden by default) -->
    <div class="cs-product-detail-view" id="cs-product-detail-view">
        <div class="cs-detail-header">
            <button class="cs-back-to-products" id="cs-back-to-products">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <?php _e('Tillbaka till produkter', 'club-sales'); ?>
            </button>
        </div>
        
        <div class="cs-detail-content">
            <div class="cs-detail-left">
                <div class="cs-detail-image">
                    <img id="cs-detail-main-image" src="" alt="">
                </div>
				<div class="cs-detail-childs">
					<!-- SKU Box -->
					<div class="cs-detail-info-box">
						<div class="cs-detail-info-icon cs-detail-icon-green">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
								<line x1="7" y1="7" x2="7.01" y2="7"></line>
							</svg>
						</div>
						<div class="cs-detail-info-content">
							<div class="cs-detail-info-label">SKU</div>
							<div class="cs-detail-info-value" id="cs-detail-sku">LAM-001</div>
						</div>
					</div>
					
					<!-- Leverantör Box -->
					<div class="cs-detail-info-box">
						<div class="cs-detail-info-icon cs-detail-icon-green">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
								<polyline points="9 22 9 12 15 12 15 22"></polyline>
							</svg>
						</div>
						<div class="cs-detail-info-content">
							<div class="cs-detail-info-label">Leverantör</div>
							<div class="cs-detail-info-value" id="cs-detail-supplier-name-box">LAMBES</div>
						</div>
					</div>
				</div>
            </div>
            
            <div class="cs-detail-right">
                <div class="cs-supplier-badge" id="cs-detail-supplier">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <?php _e('Leverantör:', 'club-sales'); ?> <span id="cs-detail-supplier-name"></span>
                </div>
                
                <h1 class="cs-detail-title" id="cs-detail-title"></h1>
                
                <div class="cs-detail-pricing-grid">
                    <div class="cs-price-box highlight">
                        <span class="cs-price-label"><?php _e('NI BETALAR', 'club-sales'); ?></span>
                        <span class="cs-price-value" id="cs-detail-club-price"></span>
                    </div>
                    <div class="cs-price-box">
                        <span class="cs-price-label"><?php _e('RRP', 'club-sales'); ?></span>
                        <span class="cs-price-value" id="cs-detail-rrp"></span>
                    </div>
                </div>
                
                <div class="cs-vinst-box">
                    <span class="cs-price-label"><?php _e('VINST PER PRODUKT', 'club-sales'); ?></span>
                    <span class="cs-price-value" id="cs-detail-profit"></span>
                </div>
                
                <div class="cs-detail-description">
                    <h3>
                        <div class="cs-catalog-icon" id="cs-desc-icon">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
								<polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
								<line x1="12" y1="22.08" x2="12" y2="12"></line>
							</svg>
						</div>
                        <?php _e('Beskrivning', 'club-sales'); ?>
                    </h3>
                    <div id="cs-detail-description-content"></div>
                </div>
                
                <button class="cs-detail-select-btn" id="cs-detail-select-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <?php _e('Produkt vald', 'club-sales'); ?>
                </button>
            </div>
        </div>
    </div>