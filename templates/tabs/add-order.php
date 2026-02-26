<div class="cs-order-section">
<div class="cs-section-header">
    <h2><?php _e('Lägg till ny order', 'club-sales'); ?></h2>
    <p><?php _e('Välj produkt från varukorgen att beställa', 'club-sales'); ?></p>
</div>

<form id="cs-order-form" class="cs-form">
    <div class="cs-form-section">
        <h3><?php _e('Valda produkter', 'club-sales'); ?></h3>
        <p style="color: #666; margin-bottom: 15px;">Lägg till produkter i denna order</p>
        
        <!-- Product Selector -->
        <div class="cs-add-product-section">
            <div class="cs-product-selector-row">
                <div class="cs-product-select-wrapper">
                    <label><?php _e('Välj produkt', 'club-sales'); ?></label>
                    <select id="product-selector" class="cs-product-dropdown">
                        <option value=""><?php _e('-- Välj en produkt --', 'club-sales'); ?></option>
                    </select>
                </div>
                
                <div class="cs-quantity-input-wrapper">
                    <label><?php _e('Antal', 'club-sales'); ?></label>
                    <input type="number" id="product-quantity-input" min="1" value="1" class="cs-quantity-input">
                </div>
                
                <button type="button" id="add-product-to-order" class="cs-add-product-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <?php _e('Lägg till', 'club-sales'); ?>
                </button>
            </div>
        </div>
        
        <!-- Selected Products List -->
        <div id="order-products-list" class="cs-order-products-list">
            <div class="cs-empty-products"><?php _e('Inga produkter tillagda ännu', 'club-sales'); ?></div>
        </div>
        
        <!-- Order Summary -->
        <div id="order-summary" class="cs-order-summary" style="display: none;">
            <div class="cs-summary-row">
                <span><?php _e('Totalt antal produkter:', 'club-sales'); ?></span>
                <strong id="total-items-count">0</strong>
            </div>
            <div class="cs-summary-row cs-summary-total">
                <span><?php _e('Totalt belopp:', 'club-sales'); ?></span>
                <strong id="total-order-amount">0.00 SEK</strong>
            </div>
        </div>
        
        <!-- Tips Notice -->
        <div class="cs-cart-notice" style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-top: 15px;">
            <p style="margin: 0; color: #1976d2;">
                <strong>💡 Tips:</strong> För att lägga till fler produkter, gå till <strong>Produkter</strong>-fliken och välj produkter där.
            </p>
        </div>
    </div>
    
    <div class="cs-order-form-section">
        <h3><?php _e('Kunduppgifter', 'club-sales'); ?></h3>
        
        <div class="cs-form-row">
            <div class="cs-form-group">
                <label for="customer_name"><?php _e('Kundens namn', 'club-sales'); ?> *</label>
                <input type="text" id="customer_name" name="customer_name" required>
            </div>
            <div class="cs-form-group">
                <label for="phone"><?php _e('Telefonnummer', 'club-sales'); ?> *</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
        </div>
        
        <div class="cs-form-group">
            <label for="email"><?php _e('E-postadress', 'club-sales'); ?> *</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="cs-form-group">
            <label for="address"><?php _e('Leveransadress', 'club-sales'); ?> *</label>
            <textarea id="address" name="address" rows="3" required></textarea>
        </div>
        
        <!-- Pricing Selection -->
        <div class="cs-form-group">
            <label><?php _e('Försäljningspris', 'club-sales'); ?></label>
            <div class="cs-pricing-options">
                <div class="cs-pricing-card cs-pricing-card-active" data-pricing-mode="rrp">
                    <div class="cs-pricing-card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="cs-pricing-card-content">
                        <div class="cs-pricing-card-title"><?php _e('Rekommenderat pris (RRP)', 'club-sales'); ?></div>
                        <div class="cs-pricing-card-calculated" id="rrp-calculated">0.00 SEK</div>
                    </div>
                </div>
                
                <div class="cs-pricing-card" data-pricing-mode="custom">
                    <div class="cs-pricing-card-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </div>
                    <div class="cs-pricing-card-content">
                        <div class="cs-pricing-card-title"><?php _e('Eget pris', 'club-sales'); ?></div>
                        <div class="cs-pricing-card-description"><?php _e('Ange ditt eget försäljningspris', 'club-sales'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Pricing Info Messages -->
            <div class="cs-pricing-info cs-pricing-info-rrp" style="display: block;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
                </svg>
                <?php _e('Rekommenderat pris: Priset beräknas automatiskt baserat på antal × RRP', 'club-sales'); ?> (<span id="rrp-per-unit">0.00</span> SEK)
            </div>
            
            <div class="cs-pricing-info cs-pricing-info-custom" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                <?php _e('Eget pris: Du kan nu ange vilket pris du vill sälja produkten för i fältet nedan', 'club-sales'); ?>
            </div>
        </div>
        
        <!-- Summa and Date Row -->
        <div class="cs-form-row">
            <div class="cs-form-group">
                <label for="total_amount"><?php _e('Summa (SEK)', 'club-sales'); ?> *</label>
                <div class="cs-summa-input-wrapper">
                    <span class="cs-summa-currency">SEK</span>
                    <input type="number" id="total_amount" name="total_amount" step="0.01" min="0" required readonly>
                </div>
                <small class="cs-form-help"><?php _e('Summan beräknas automatiskt från RRP', 'club-sales'); ?></small>
            </div>
            
            <div class="cs-form-group">
                <label for="sale_date"><?php _e('Försäljningsdatum', 'club-sales'); ?> *</label>
                <input type="date" id="sale_date" name="sale_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <div class="cs-form-group">
            <label for="notes"><?php _e('Anteckningar', 'club-sales'); ?></label>
            <textarea id="notes" name="notes" rows="2"></textarea>
        </div>
    </div>
    
    <div class="cs-form-actions">
        <button type="submit" class="cs-submit-btn" disabled><?php _e('Lägg till beställning', 'club-sales'); ?></button>
    </div>
</form>	
</div>

<style>
/* Product Selector Section */
.cs-add-product-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 2px dashed #dee2e6;
}

.cs-product-selector-row {
    display: grid;
    grid-template-columns: 1fr 150px auto;
    gap: 15px;
    align-items: end;
}

.cs-product-select-wrapper,
.cs-quantity-input-wrapper {
    display: flex;
    flex-direction: column;
}

.cs-product-select-wrapper label,
.cs-quantity-input-wrapper label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    font-size: 14px;
}

.cs-product-dropdown {
    width: 100%;
    padding: 12px 15px;
    font-size: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #fff;
    cursor: pointer;
    transition: all 0.3s ease !important;
}

.cs-product-dropdown:hover {
    border-color: #4CAF50;
}

.cs-product-dropdown:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.cs-quantity-input {
    width: 100%;
    padding: 12px 15px;
    font-size: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
}

.cs-quantity-input:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.cs-add-product-btn {
    display: flex;
    align-items: center;
    gap: 8px !important;
    padding: 12px 24px !important;
    background: #4CAF50 !important;
    color: white !important;
    border: none !important;
    border-radius: 8px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    white-space: nowrap !important;
    height: 46px !important;
}

.cs-add-product-btn:hover {
    background: #45a049 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3) !important;
}

.cs-add-product-btn:disabled {
    background: #ccc !important;
    cursor: not-allowed !important;
    transform: none !important;
}

/* Order Products List */
.cs-order-products-list {
    margin-top: 20px;
}

.cs-empty-products {
    text-align: center;
    color: #999;
    padding: 40px 20px;
    font-style: italic;
    background: #f9f9f9;
    border-radius: 8px;
    border: 2px dashed #ddd;
}

.cs-product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
    border: 1px solid #4CAF50;
    border-radius: 12px;
    margin-bottom: 12px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.cs-product-item-info {
    flex: 1;
}

.cs-product-item-name {
    font-weight: 500;
    font-size: 18px;
    color: #2c3e50;
    margin-bottom: 6px;
}

strong#total-order-amount, strong#total-items-count{
    font-size: 18px;
    font-weight: 500;
}

.cs-product-item-meta {
    font-size: 13px;
    color: #666;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.cs-product-item-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.cs-product-item-quantity {
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #4CAF50;
}

.cs-qty-btn {
    background: #4CAF50;
    color: white;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.cs-qty-btn:hover {
    background: #45a049;
    transform: scale(1.1);
}

.cs-qty-value {
    min-width: 30px;
    text-align: center;
    font-weight: 500;
    font-size: 18px;
}

.cs-product-item-price {
    font-weight: 500;
    font-size: 18px;
    color: #4CAF50;
    min-width: 100px;
    text-align: right;
}

.cs-remove-product-btn {
    background: #f44336;
    color: white;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.cs-remove-product-btn:hover {
    background: #d32f2f;
    transform: scale(1.1);
}

/* Order Summary */
.cs-order-summary {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    border: 1px solid #4CAF50;
}

.cs-summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 15px;
}

.cs-summary-total {
    border-top: 1px solid #4CAF50;
    margin-top: 10px;
    padding-top: 15px;
    font-size: 18px;
    color: #2c3e50;
}

/* Tips Notice - Always visible */
.cs-cart-notice {
    display: block !important;
}

/* Pricing Selection Cards */
.cs-pricing-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.cs-pricing-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #ffffff;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.cs-pricing-card:hover {
    border-color: #4CAF50;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.cs-pricing-card-active {
    background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
    border-color: #4CAF50;
    border-width: 2px;
    box-shadow: 0 4px 16px rgba(76, 175, 80, 0.2);
}

.cs-pricing-card-icon {
    width: 48px;
    height: 48px;
    background: #4CAF50;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.cs-pricing-card:not(.cs-pricing-card-active) .cs-pricing-card-icon {
    background: #e0e0e0;
}

.cs-pricing-card-icon svg {
    fill: transparent;
    stroke: white;
}

.cs-pricing-card:not(.cs-pricing-card-active) .cs-pricing-card-icon svg {
    stroke: #666;
}

.cs-pricing-card-content {
    flex: 1;
}

.cs-pricing-card-title {
    font-weight: 500;
    font-size: 16px;
    color: #2c3e50;
    margin-bottom: 4px;
}

.cs-pricing-card-description {
    font-size: 13px;
    color: #666;
}

.cs-pricing-card-calculated {
    font-size: 18px;
    font-weight: 500;
    color: #4CAF50;
    margin-top: 4px;
}

/* Pricing Info Messages */
.cs-pricing-info {
    display: flex;
    align-items: start;
    gap: 10px;
    padding: 12px 15px;
    border-radius: 8px;
    font-size: 13px;
    margin-top: 15px;
}

.cs-pricing-info-rrp {
    background: #fff8e1;
    color: #f57c00;
    border-left: 4px solid #ffa726;
}

.cs-pricing-info-custom {
    background: #fce4ec;
    color: #c2185b;
    border-left: 4px solid #ec407a;
}

.cs-pricing-info svg {
    flex-shrink: 0;
    margin-top: 2px;
    fill: transparent;
}

/* Summa Input Wrapper */
.cs-summa-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.cs-summa-currency {
    position: absolute;
    left: 15px;
    font-weight: 700;
    color: #666;
    pointer-events: none;
    font-size: 15px;
}

.cs-summa-input-wrapper input {
    padding-left: 55px !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    color: #4CAF50 !important;
}

.cs-summa-input-wrapper input:read-only {
    background: #f5f5f5;
    cursor: not-allowed;
}

.cs-summa-input-wrapper input:not(:read-only) {
    background: #ffffff;
    border-color: #4CAF50;
}

.cs-form-help {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #666;
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
    .cs-product-selector-row {
        grid-template-columns: 1fr;
    }
    
    .cs-add-product-btn {
        width: 100%;
        justify-content: center;
    }
    
    .cs-product-item {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .cs-product-item-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .cs-pricing-options {
        grid-template-columns: 1fr;
    }
}
</style>