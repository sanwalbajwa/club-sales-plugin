<div class="cs-sales-material-page">
    <!-- Header -->
    <div class="cs-sm-header">
        <div class="cs-sm-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                <polyline points="13 2 13 9 20 9"></polyline>
                <line x1="8" y1="13" x2="16" y2="13"></line>
                <line x1="8" y1="17" x2="16" y2="17"></line>
            </svg>
        </div>
        <div>
            <h2 class="cs-sm-title"><?php _e('Säljmaterial', 'club-sales'); ?></h2>
            <p class="cs-sm-subtitle"><?php _e('Allt material ni behöver för en framgångsrik kampanj', 'club-sales'); ?></p>
        </div>
    </div>
    
    <!-- Info Banner -->
    <div class="cs-sm-banner">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="18" cy="5" r="3"></circle>
            <circle cx="6" cy="12" r="3"></circle>
            <circle cx="18" cy="19" r="3"></circle>
            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
        </svg>
        <div>
            <strong><?php _e('Dela med era säljare', 'club-sales'); ?></strong>
            <p><?php _e('Alla material här kan delas direkt med era säljare. De får automatiskt tillgång via sina unika länkar.', 'club-sales'); ?></p>
        </div>
    </div>
    <!-- Material Cards Grid -->
    <div class="cs-material-cards-grid" id="cs-materials-grid">
        <div class="cs-loading-materials">
            <span class="dashicons dashicons-update cs-spin"></span>
            <p><?php _e('Laddar säljmaterial...', 'club-sales'); ?></p>
        </div>
    </div>
</div>

<!-- Material Detail Modal -->
<div id="cs-material-modal" class="cs-material-modal" style="display: none;">
    <div class="cs-material-modal-overlay"></div>
    <div class="cs-material-modal-content">
        <button class="cs-material-modal-close">&times;</button>
        
        <div class="cs-material-modal-body">
            <!-- Left side - Preview -->
            <div class="cs-material-preview-section">
                <div class="cs-material-badge" id="modal-material-badge">Kampanjmaterial</div>
                <div class="cs-material-preview-image" id="modal-preview-image">
                    <!-- Preview will be loaded here -->
                </div>
                <div class="cs-material-preview-actions">
                    <button class="cs-material-btn cs-material-btn-secondary" id="modal-download-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Ladda ner
                    </button>
                    <button class="cs-material-btn cs-material-btn-secondary" id="modal-share-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                        </svg>
                        Dela
                    </button>
                    <button class="cs-material-btn cs-material-btn-secondary" id="modal-print-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        Skriv ut
                    </button>
                </div>
            </div>
            
            <!-- Right side - Details -->
            <div class="cs-material-details-section">
                <div class="cs-material-tabs">
                    <button class="cs-material-tab active" data-tab="details">Kampanj</button>
                    <button class="cs-material-tab" data-tab="pdf">PDF</button>
                </div>
                
                <h2 id="modal-material-title">Kampanjbilder</h2>
                
                <div class="cs-material-info-section">
                    <h3>Om materialet</h3>
                    <p id="modal-material-description">
                        Detta kampanjmaterial är designat för att maximera er försäljning. Materialet innehåller information om era produkter och hur kunder kan beställa. Perfekt för att dela digitalt eller skriva ut.
                    </p>
                </div>
                
                <div class="cs-material-details-box">
                    <h3>Detaljer</h3>
                    <div class="cs-detail-row">
                        <span class="cs-detail-label">Typ</span>
                        <span class="cs-detail-value" id="modal-material-type">Kampanjmaterial</span>
                    </div>
                    <div class="cs-detail-row">
                        <span class="cs-detail-label">Filformat</span>
                        <span class="cs-detail-value" id="modal-file-format">PDF</span>
                    </div>
                    <div class="cs-detail-row">
                        <span class="cs-detail-label">Filstorlek</span>
                        <span class="cs-detail-value" id="modal-file-size">2.4 MB</span>
                    </div>
                    <div class="cs-detail-row">
                        <span class="cs-detail-label">Status</span>
                        <span class="cs-detail-value cs-status-available" id="modal-status">Tillgänglig</span>
                    </div>
                </div>
                
                <div class="cs-material-usage-box">
                    <h3>Användning</h3>
                    <p id="modal-usage-text">
                        Detta material kan användas för att marknadsföra er kampanj. Ladda ner och dela med era säljare eller använd i sociala medier.
                    </p>
                </div>
                
                <button class="cs-material-btn cs-material-btn-primary cs-material-btn-large" id="modal-main-download-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Ladda ner material
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.cs-sales-material-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px;
}

.cs-sm-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 40px;
}

.cs-sm-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #00B050 0%, #00A848 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.cs-sm-icon svg {
    stroke: white;
    fill: transparent;
    width: 32px;
}

.cs-sm-title {
    font-size: 28px !important;
    font-weight: 600 !important;
    margin: 0 0 4px 0 !important;
    color: #1a1a1a !important;
}

.cs-sm-subtitle {
    font-size: 14px;
    color: #666;
    margin: 0 !important;
}
.cs-sm-banner {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: #e3f2fd;
    border: 2px solid #90caf9;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 32px;
}

.cs-sm-banner svg {
    flex-shrink: 0;
    stroke: white;
    background: #2196f3;
    fill: transparent;
    width: 48px;
    height: 48px;
    padding: 12px;
    border-radius: 12px;
}

.cs-sm-banner strong {
    display: block;
    color: #1976d2;
    margin-bottom: 4px;
    font-size: 15px;
    font-weight: 500;
}

.cs-sm-banner p {
    color: #1565c0;
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
}
/* Material Cards Grid */
/* .cs-material-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
} */

.cs-material-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    cursor: pointer;
    display: inline-flex;
    flex-direction: column;
    gap: 15px;
    width: 49%;
}

.cs-material-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.cs-card-green {
    border-color: #4CAF50;
}

.cs-card-blue {
    border-color: #2196F3;
}

.cs-card-teal {
    border-color: #00bcd4;
}

.cs-card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.cs-card-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background-color: #e8f5e9;
}

.cs-card-icon svg {
    width: 32px;
    fill: transparent;
    stroke: #00A848;
}

.cs-card-title-section {
    flex: 1;
}

.cs-card-title {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.cs-card-subtitle {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
}

/* Products List */
.cs-products-list {
    margin-bottom: 16px;
    background: #f0f9f4;
    padding: 12px;
    border-radius: 12px;
}

.cs-product-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: #e8f5e9;
    border-radius: 10px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #d4edda;
}

.cs-product-item:last-child {
    margin-bottom: 0;
}

.cs-product-item:hover {
    background: #dcf0dd;
    border-color: #c3e6cb;
    box-shadow: 0 2px 8px rgba(0, 176, 80, 0.1);
}

.cs-product-thumb {
    width: 52px;
    height: 52px;
    object-fit: cover;
    border-radius: 8px;
    background: white;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    flex-shrink: 0;
}

.cs-material-cards-grid .cs-product-info {
    flex: 1;
    min-width: 0;
    padding: 0px;
}

.cs-material-cards-grid .cs-product-info h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cs-material-cards-grid .cs-product-info span {
    font-size: 13px;
    color: #00A848;
    font-weight: 500;
}

.cs-product-eye {
    width: 40px !important;
    height: 40px !important;
    border-radius: 8px !important;
    border: none !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: all 0.2s;
    flex-shrink: 0;
    padding: 0px !important;
}

.cs-product-eye svg {
    stroke: #4CAF50;
    fill: transparent;
    width: 20px;
    height: 20px;
}

.cs-card-actions {
    display: flex;
    gap: 12px;
    margin-top: auto;
}

.cs-card-btn {
    display: inline-flex !important;
    align-items: center;
    gap: 8px;
    padding: 10px 20px !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    transition: all 0.2s;
    border: 1px solid !important;
    flex: 1;
    justify-content: center;
}

.cs-card-btn svg {
    flex-shrink: 0;
    fill: transparent;
}

.cs-card-btn-primary {
    background: linear-gradient(135deg, #00B050 0%, #00A848 100%) !important;
    color: white !important;
    border-color: linear-gradient(135deg, #00B050 0%, #00A848 100%) !important;
}

.cs-card-btn-primary:hover {
    background: linear-gradient(135deg, #4cb44f 0%, #3ca43f 100%) !important;
    border-color: linear-gradient(135deg, #4cb44f 0%, #3ca43f 100%) !important;
    transform: translateY(-2px);
}

.cs-card-btn-secondary {
    background: white !important;
    color: #374151 !important;
    border-color: #d1d5db !important;
}

.cs-card-btn-secondary:hover {
    background: #f9fafb !important;
    border-color: #9ca3af !important;
}

/* Loading/Empty States */
.cs-loading-materials,
.cs-empty-state,
.cs-error-state {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
}

.cs-empty-state h3,
.cs-error-state h3 {
    font-size: 20px;
    color: #2c3e50;
    margin: 20px 0 10px 0;
}

.cs-empty-state p,
.cs-error-state p {
    font-size: 15px;
    color: #666;
    margin: 0;
}

.cs-empty-state svg{
    fill: transparent;
    stroke: #00A848;
}

.cs-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Material Modal */
.cs-material-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cs-material-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
}

.cs-material-modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    max-width: 1000px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    z-index: 10001;
}

.cs-material-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: transparent !important;
    border: none !important;
    font-size: 24px !important;
    cursor: pointer !important;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: all 0.3s ease;
    color: #333;
}

.cs-material-modal-close:hover {
    background: rgba(0,0,0,0.2);
    transform: rotate(90deg);
}

.cs-material-modal-body {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    max-height: 90vh;
}

/* Modal Left Side - Preview */
.cs-material-preview-section {
    background: #ffffff;
    padding: 40px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.cs-material-badge {
    background: linear-gradient(135deg, #00B050 0%, #00A848 100%);
    color: white;
    padding: 6px 25px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    align-self: flex-start;
}

.cs-material-preview-image {
    flex: 1;
    background: #f8f9fa;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    overflow: hidden;
    position: relative;
}

.cs-preview-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #4CAF50;
}

.cs-pdf-preview,
.cs-file-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    color: #6c757d;
}

.cs-pdf-preview small,
.cs-file-preview small {
    font-size: 12px;
    color: #999;
}

.cs-material-preview-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

/* Modal Right Side - Details */
.cs-material-details-section {
    padding: 40px;
    overflow-y: auto;
}

.cs-material-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.cs-material-tab {
    padding: 8px 20px !important;
    border-radius: 20px !important;
    border: 2px solid #e0e0e0 !important;
    background: transparent !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #666 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
}

.cs-material-tab.active {
    background: #4CAF50 !important;
    border-color: #4CAF50 !important;
    color: white !important;
}

#modal-material-title {
    font-size: 24px !important;
    font-weight: 700 !important;
    color: #2c3e50 !important;
    margin: 0 0 20px 0 !important;
}

.cs-material-info-section h3,
.cs-material-usage-box h3 {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 10px 0;
}

.cs-material-info-section p,
.cs-material-usage-box p {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin: 0 0 20px 0;
}

.cs-material-details-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.cs-material-details-box h3 {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 15px 0;
}

.cs-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
}

.cs-detail-row:last-child {
    border-bottom: none;
}

.cs-detail-label {
    font-size: 14px;
    color: #666;
}

.cs-detail-value {
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
}

.cs-status-available {
    color: #4CAF50;
}

.cs-material-usage-box {
    background: #e8f5e9;
    border-left: 4px solid #4CAF50;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.cs-material-btn {
    padding: 10px 16px;
    border-radius: 8px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.cs-material-btn svg{
    fill: transparent;
}

.cs-material-btn-large {
    width: 100%;
    padding: 14px 24px !important;
    font-size: 16px !important;
}

.cs-material-btn-primary {
    background: #4CAF50 !important;
    color: white !important;
}

.cs-material-btn-primary:hover {
    background: #45a049 !important;
}

.cs-material-btn-secondary {
    background: transparent !important;
    color: #666 !important;
    border: 2px solid #e0e0e0 !important;
}

.cs-material-btn-secondary:hover {
    border-color: #4CAF50  !important;
    color: #4CAF50 !important;
}

/* Responsive */
@media (max-width: 992px) {
    .cs-material-modal-body {
        grid-template-columns: 1fr;
    }
    
    .cs-material-preview-section {
        padding: 20px;
    }
    
    .cs-material-details-section {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .cs-material-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .cs-material-preview-actions {
        grid-template-columns: 1fr;
    }
    
    .cs-sm-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .cs-material-card{
        width: 100%;
    }
    .cs-card-actions{
        flex-direction: column; 
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load materials when this tab becomes active
    function loadSalesMaterials() {
        console.log('📦 Loading sales materials...');
        
        $.ajax({
            url: csAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cs_get_selected_products',
                nonce: csAjax.nonce
            },
            success: function(response) {
                console.log('✅ Materials response:', response);
                if (response.success && response.data.products && response.data.products.length > 0) {
                    displayMaterialCards(response.data.products);
                } else {
                    showEmptyState();
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error loading materials:', error);
                showErrorState();
            }
        });
    }
    
    // Display material cards (3 cards total, grouped by type)
    function displayMaterialCards(products) {
        const $grid = $('#cs-materials-grid');
        $grid.empty();
        
        console.log('📦 Displaying', products.length, 'products');
        
        // Group materials by type
        const materialGroups = {
            product_images: [],
            social_media: [],
            sales_pitches: []
        };
        
        products.forEach(function(product) {
            console.log('Product materials:', {
                id: product.id,
                name: product.name,
                sales_pitches: product.sales_pitches,
                product_image: product.product_image,
                social_media_content: product.social_media_content
            });
            
            if (product.product_image) {
                materialGroups.product_images.push(product);
            }
            if (product.social_media_content) {
                materialGroups.social_media.push(product);
            }
            if (product.sales_pitches) {
                materialGroups.sales_pitches.push(product);
            }
        });
        
        // Card 1: Valda produkter (if any product has images)
        if (materialGroups.product_images.length > 0) {
            const firstProduct = materialGroups.product_images[0];
            const productCount = materialGroups.product_images.length;
            const productNames = materialGroups.product_images.map(p => p.name).join(', ');
            
            $grid.append(createMaterialCard({
                title: 'Valda produkter',
                subtitle: productCount === 1 ? firstProduct.name : `${productCount} produkter valda`,
                icon: 'package',
                color: 'green',
                materialType: 'product_image',
                materialUrl: firstProduct.product_image,
                productName: productNames,
                productId: firstProduct.id,
                fileFormat: getFileExtension(firstProduct.product_image),
                badge: 'Produktbild',
                productCount: productCount,
                allProducts: materialGroups.product_images
            }));
        }
        
        // Card 2: Kampanjbilder (if any product has social media content)
        if (materialGroups.social_media.length > 0) {
            const firstProduct = materialGroups.social_media[0];
            const productCount = materialGroups.social_media.length;
            
            $grid.append(createMaterialCard({
                title: 'Kampanjbilder',
                subtitle: 'Professionella bilder för sociala medier',
                icon: 'image',
                color: 'blue',
                materialType: 'social_media_content',
                materialUrl: firstProduct.social_media_content,
                productName: firstProduct.name,
                productId: firstProduct.id,
                fileFormat: getFileExtension(firstProduct.social_media_content),
                badge: 'Kampanjmaterial',
                productCount: productCount,
                allProducts: materialGroups.social_media
            }));
        }
        
        // Card 3: Material från administratör (if any product has sales pitches)
        if (materialGroups.sales_pitches.length > 0) {
            const firstProduct = materialGroups.sales_pitches[0];
            const productCount = materialGroups.sales_pitches.length;
            
            $grid.append(createMaterialCard({
                title: 'Material från administratör',
                subtitle: 'Ladda upp er logo, lag-/klassbilder och annat material som hjälper försäljningen',
                icon: 'upload',
                color: 'teal',
                materialType: 'sales_pitches',
                materialUrl: firstProduct.sales_pitches,
                productName: firstProduct.name,
                productId: firstProduct.id,
                fileFormat: getFileExtension(firstProduct.sales_pitches),
                badge: 'Säljmaterial',
                productCount: productCount,
                allProducts: materialGroups.sales_pitches
            }));
        }
        
        if ($grid.children().length === 0) {
            showEmptyState();
            return;
        }
        
        // Attach card click handlers
        attachCardHandlers();
    }
    
    // Create a material card
    function createMaterialCard(data) {
        const iconSvg = getIconSvg(data.icon);
        const colorClass = `cs-card-${data.color}`;
        
        console.log('🎴 Creating card:', {
            title: data.title,
            productCount: data.allProducts ? data.allProducts.length : 0,
            materialType: data.materialType
        });
        
        // Build product list
        let productsList = '';
        if (data.allProducts && data.allProducts.length > 0) {
            data.allProducts.forEach(function(product) {
                let materialUrl = '';
                if (data.materialType === 'product_image') {
                    materialUrl = product.product_image;
                } else if (data.materialType === 'social_media_content') {
                    materialUrl = product.social_media_content;
                } else if (data.materialType === 'sales_pitches') {
                    materialUrl = product.sales_pitches;
                }
                
                if (materialUrl) {
                    productsList += `
                        <div class="cs-product-item" data-url="${materialUrl}" data-name="${product.name}">
                            <div class="cs-product-info">
                                <h4>${product.name}</h4>
                                <span>${product.price || ''}</span>
                            </div>
                            <button class="cs-product-eye" type="button">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    `;
                }
            });
        }
        
        // Store all material URLs for bulk download
        const allMaterialUrls = data.allProducts ? data.allProducts.map(p => {
            if (data.materialType === 'product_image') return p.product_image;
            if (data.materialType === 'social_media_content') return p.social_media_content;
            if (data.materialType === 'sales_pitches') return p.sales_pitches;
            return null;
        }).filter(url => url) : [];
        
        return `
            <div class="cs-material-card ${colorClass}" 
                 data-material-type="${data.materialType}" 
                 data-all-urls='${JSON.stringify(allMaterialUrls)}'
                 data-badge="${data.badge}">
                <div class="cs-card-header">
                    <div class="cs-card-icon ${colorClass}-icon">
                        ${iconSvg}
                    </div>
                    <div class="cs-card-title-section">
                        <h3 class="cs-card-title">${data.title}</h3>
                        <p class="cs-card-subtitle">${data.subtitle}</p>
                    </div>
                </div>
                ${productsList ? `<div class="cs-products-list">${productsList}</div>` : ''}
                <div class="cs-card-actions">
                    <button class="cs-card-btn cs-card-btn-primary cs-card-download" type="button">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Ladda ner
                    </button>
                    <button class="cs-card-btn cs-card-btn-secondary cs-card-preview" type="button">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Förhandsgranska
                    </button>
                </div>
            </div>
        `;
    }
    
    // Get icon SVG
    function getIconSvg(icon) {
        const icons = {
            package: '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
            image: '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
            upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line>'
        };
        return `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${icons[icon] || icons.package}</svg>`;
    }
    
    // Get file extension
    function getFileExtension(url) {
        if (!url) return 'Unknown';
        const ext = url.split('.').pop().split('?')[0].toUpperCase();
        return ext.length > 4 ? 'File' : ext;
    }
    
    // Attach click handlers to cards
    function attachCardHandlers() {
        // Download button - downloads all materials in the card
        $('.cs-card-download').off('click').on('click', function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.cs-material-card');
            const allUrls = JSON.parse($card.data('all-urls') || '[]');
            console.log('📥 Download all clicked:', allUrls.length, 'files');
            
            if (allUrls.length === 0) {
                alert('Inga filer tillgängliga för nedladdning');
                return;
            }
            
            // Download all files
            allUrls.forEach(function(url, index) {
                setTimeout(function() {
                    downloadMaterial(url, 'material-' + (index + 1));
                }, index * 500); // Stagger downloads by 500ms
            });
        });
        
        // Preview button - shows first material
        $('.cs-card-preview').off('click').on('click', function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.cs-material-card');
            const $firstProduct = $card.find('.cs-product-item').first();
            
            console.log('👁️ Preview button clicked');
            
            if ($firstProduct.length > 0) {
                const url = $firstProduct.data('url');
                const name = $firstProduct.data('name');
                const badge = $card.data('badge');
                const materialType = $card.data('material-type');
                
                console.log('Showing first product:', { url, name, badge, materialType });
                showMaterialModalFromUrl(url, name, badge, materialType);
            } else {
                console.warn('No products found in card');
                alert('Inga produkter tillgängliga för förhandsgranskning');
            }
        });
        
        // Product item eye button click
        $('.cs-product-eye').off('click').on('click', function(e) {
            e.stopPropagation();
            const $product = $(this).closest('.cs-product-item');
            const $card = $product.closest('.cs-material-card');
            const url = $product.data('url');
            const name = $product.data('name');
            const badge = $card.data('badge');
            const materialType = $card.data('material-type');
            console.log('👁️ Product eye clicked:', name);
            showMaterialModalFromUrl(url, name, badge, materialType);
        });
        
        // Product item click
        $('.cs-product-item').off('click').on('click', function(e) {
            // Don't trigger if clicking the eye button
            if ($(e.target).closest('.cs-product-eye').length === 0) {
                const $card = $(this).closest('.cs-material-card');
                const url = $(this).data('url');
                const name = $(this).data('name');
                const badge = $card.data('badge');
                const materialType = $card.data('material-type');
                console.log('📂 Product clicked:', name);
                showMaterialModalFromUrl(url, name, badge, materialType);
            }
        });
    }
    
    
    // Show material modal from URL (new simplified function)
    function showMaterialModalFromUrl(url, productName, badge, materialType) {
        const fileFormat = getFileExtension(url);
        
        console.log('📂 Opening modal for:', {
            url,
            productName,
            fileFormat,
            badge,
            materialType
        });
        
        // Update modal content
        $('#modal-material-badge').text(badge);
        $('#modal-material-title').text(productName);
        
        // Set descriptions based on material type
        let description, usage;
        if (materialType === 'product_image') {
            description = `Produktbilder för ${productName}. Använd dessa bilder för att visa produkten på bästa sätt.`;
            usage = 'Ladda ner produktbilderna och dela med era säljare. Perfekt för produktkataloger och sociala medier.';
        } else if (materialType === 'social_media_content') {
            description = `Detta kampanjmaterial är designat för att maximera er försäljning. Materialet innehåller information om era produkter och hur kunder kan beställa. Perfekt för att dela digitalt eller skriva ut.`;
            usage = 'Detta material kan användas för att marknadsföra er kampanj. Ladda ner och dela med era säljare eller använd i sociala medier.';
        } else if (materialType === 'sales_pitches') {
            description = `Säljmaterial och presentations dokumentation för ${productName}. Innehåller argument och information för säljare.`;
            usage = 'Använd detta material för att ge era säljare bästa möjliga förutsättningar. Innehåller säljargument och produktinformation.';
        }
        
        $('#modal-material-description').text(description);
        $('#modal-material-type').text(badge);
        $('#modal-file-format').text(fileFormat);
        $('#modal-usage-text').text(usage);
        
        // Load preview
        loadMaterialPreview(url, fileFormat, $('#modal-preview-image'));
        
        // Get file size
        getFileSize(url, function(size) {
            $('#modal-file-size').text(formatFileSize(size));
        });
        
        // Attach modal action handlers
        $('#modal-download-btn, #modal-main-download-btn').off('click').on('click', function() {
            downloadMaterial(url, productName);
        });
        
        $('#modal-share-btn').off('click').on('click', function() {
            shareMaterial(url, productName);
        });
        
        $('#modal-print-btn').off('click').on('click', function() {
            printMaterial(url);
        });
        
        // Show modal with fade animation
        $('#cs-material-modal').fadeIn(300);
    }
    
    // Show material modal (legacy function kept for compatibility)
    function showMaterialModal($card) {
        const materialUrl = $card.data('material-url');
        const productName = $card.data('product-name');
        const fileFormat = $card.data('file-format');
        const badge = $card.data('badge');
        const materialType = $card.data('material-type');
        
        if (materialUrl) {
            showMaterialModalFromUrl(materialUrl, productName, badge, materialType);
            return;
        }
        
        console.log('📂 Opening modal for:', {
            materialType
        });
        
        // Update modal content
        $('#modal-material-badge').text(badge);
        $('#modal-material-title').text($card.find('.cs-card-title').text());
        
        // Set descriptions based on material type
        let description, usage;
        if (materialType === 'product_image') {
            description = `Produktbilder för ${productName}. Använd dessa bilder för att visa produkten på bästa sätt.`;
            usage = 'Ladda ner produktbilderna och dela med era säljare. Perfekt för produktkataloger och sociala medier.';
        } else if (materialType === 'social_media_content') {
            description = `Detta kampanjmaterial är designat för att maximera er försäljning. Materialet innehåller information om era produkter och hur kunder kan beställa. Perfekt för att dela digitalt eller skriva ut.`;
            usage = 'Detta material kan användas för att marknadsföra er kampanj. Ladda ner och dela med era säljare eller använd i sociala medier.';
        } else if (materialType === 'sales_pitches') {
            description = `Säljmaterial och presentations dokumentation för ${productName}. Innehåller argument och information för säljare.`;
            usage = 'Använd detta material för att ge era säljare bästa möjliga förutsättningar. Innehåller säljargument och produktinformation.';
        }
        
        $('#modal-material-description').text(description);
        $('#modal-material-type').text(badge);
        $('#modal-file-format').text(fileFormat);
        $('#modal-usage-text').text(usage);
        
        // Load preview
        loadMaterialPreview(materialUrl, fileFormat, $('#modal-preview-image'));
        
        // Get file size
        getFileSize(materialUrl, function(size) {
            $('#modal-file-size').text(formatFileSize(size));
        });
        
        // Attach modal action handlers
        $('#modal-download-btn, #modal-main-download-btn').off('click').on('click', function() {
            downloadMaterial(materialUrl, productName);
        });
        
        $('#modal-share-btn').off('click').on('click', function() {
            shareMaterial(materialUrl, productName);
        });
        
        $('#modal-print-btn').off('click').on('click', function() {
            printMaterial(materialUrl);
        });
        
        // Show modal with fade animation
        $('#cs-material-modal').fadeIn(300);
    }
    
    // Load material preview
    function loadMaterialPreview(url, format, $container) {
        $container.empty().html('<div class="cs-preview-loading"><span class="dashicons dashicons-update cs-spin"></span></div>');
        
        const imageFormats = ['JPG', 'JPEG', 'PNG', 'GIF', 'WEBP', 'SVG'];
        const pdfFormats = ['PDF'];
        
        setTimeout(function() {
            if (imageFormats.includes(format)) {
                $container.html(`<img src="${url}" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">`);
            } else if (pdfFormats.includes(format)) {
                $container.html(`
                    <div class="cs-pdf-preview">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                            <polyline points="13 2 13 9 20 9"></polyline>
                        </svg>
                        <p>PDF Document</p>
                        <small>Klicka på Ladda ner för att öppna</small>
                    </div>
                `);
            } else {
                $container.html(`
                    <div class="cs-file-preview">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="2">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                            <polyline points="13 2 13 9 20 9"></polyline>
                        </svg>
                        <p>${format} File</p>
                    </div>
                `);
            }
        }, 300);
    }
    
    // Download material
    function downloadMaterial(url, filename) {
        console.log('📥 Download function called');
        console.log('   URL:', url);
        console.log('   URL type:', typeof url);
        console.log('   Filename:', filename);
        
        if (!url || url === 'undefined' || url === 'null' || url === '') {
            console.error('❌ Invalid URL - cannot download');
            alert('Ingen fil tillgänglig för nedladdning. Kontrollera att filen har laddats upp i produkten.');
            return;
        }
        
        console.log('✅ Creating download link...');
        const link = document.createElement('a');
        link.href = url;
        link.download = filename || 'material';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        console.log('✅ Download initiated');
    }
    
    // Share material
    function shareMaterial(url, productName) {
        if (navigator.share) {
            navigator.share({
                title: 'Säljmaterial - ' + productName,
                url: url
            }).then(() => {
                alert('Material delat!');
            }).catch(console.error);
        } else {
            // Fallback: copy to clipboard
            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            alert('Länk kopierad till urklipp!');
        }
    }
    
    // Print material
    function printMaterial(url) {
        window.open(url, '_blank');
    }
    
    // Get file size
    function getFileSize(url, callback) {
        fetch(url, { method: 'HEAD' })
            .then(response => {
                const size = response.headers.get('content-length');
                callback(parseInt(size) || 0);
            })
            .catch(() => callback(0));
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return 'Unknown';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Show empty state
    function showEmptyState() {
        $('#cs-materials-grid').html(`
            <div class="cs-empty-state">
                <svg width="80" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
                <h3>Inget säljmaterial tillgängligt</h3>
                <p>Välj produkter i <strong>Produkter</strong>-fliken för att se deras säljmaterial.</p>
            </div>
        `);
    }
    
    // Show error state
    function showErrorState() {
        $('#cs-materials-grid').html(`
            <div class="cs-error-state">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h3>Kunde inte ladda material</h3>
                <p>Försök igen senare.</p>
            </div>
        `);
    }
    
    // Close modal
    $('.cs-material-modal-close, .cs-material-modal-overlay').off('click').on('click', function() {
        $('#cs-material-modal').fadeOut(300);
    });
    
    // Tab switching in modal
    $('.cs-material-tab').off('click').on('click', function() {
        $('.cs-material-tab').removeClass('active');
        $(this).addClass('active');
    });
    
    // Load materials when tab is shown
    $(document).on('click', '[data-tab="sales-material"]', function() {
        console.log('📂 Sales Material tab clicked, loading materials...');
        setTimeout(loadSalesMaterials, 100);
    });
    
    // Initial load if already on this tab
    if ($('[data-tab="sales-material"]').hasClass('active')) {
        console.log('📂 Already on Sales Material tab, loading...');
        loadSalesMaterials();
    }
    
    // Global function for updating from other scripts
    window.updateSalesMaterialTab = function(productId) {
        console.log('🔄 Updating Sales Material tab for product:', productId);
        loadSalesMaterials();
    };
});
</script>
