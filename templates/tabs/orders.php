<div class="cs-order-parent">
<div class="cs-order-section-header">
    <div class="cs-header-left">
        <h2><?php _e('Your Orders', 'club-sales'); ?></h2>
    </div>
    
    <div class="cs-header-right">
        <div class="cs-filter-container">
            <select id="order-status-filter">
                <option value=""><?php _e('All Orders', 'club-sales'); ?></option>
                <option value="pending"><?php _e('Pending', 'club-sales'); ?></option>
                <option value="ordered_from_supplier"><?php _e('Ordered from Supplier', 'club-sales'); ?></option>
                <option value="completed"><?php _e('Completed', 'club-sales'); ?></option>
                <option value="deleted"><?php _e('My Deleted Orders', 'club-sales'); ?></option>
            </select>
            
            <?php if (!CS_Child_Manager::is_child_user()): ?>
            <select id="order-user-filter">
                <option value=""><?php _e('My & Children Orders', 'club-sales'); ?></option>
                <option value="my"><?php _e('My Orders', 'club-sales'); ?></option>
                <option value="children"><?php _e('Children Orders', 'club-sales'); ?></option>
            </select>
            <?php endif; ?>
        </div>
        

    </div>
</div>

<!-- Orders Statistics Cards -->
<div class="cs-orders-stats-container">
    <div class="cs-orders-stat-card">
        <div class="cs-stat-header">
            <div class="cs-stat-label"><?php _e('Totalt antal ordrar', 'club-sales'); ?></div>
            <div class="cs-stat-icon cs-stat-icon-orders">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
        </div>
        <div class="cs-stat-value" id="cs-orders-total-count">
            <span class="cs-loading-spinner"></span>
        </div>
    </div>

    <div class="cs-orders-stat-card">
        <div class="cs-stat-header">
            <div class="cs-stat-label"><?php _e('Total försäljning', 'club-sales'); ?></div>
            <div class="cs-stat-icon cs-stat-icon-sales">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
        </div>
        <div class="cs-stat-value" id="cs-orders-total-sales">
            <span class="cs-loading-spinner"></span>
        </div>
    </div>

    <div class="cs-orders-stat-card cs-stat-card-highlight">
        <div class="cs-stat-header">
            <div class="cs-stat-label"><?php _e('Total vinst', 'club-sales'); ?></div>
            <div class="cs-stat-icon cs-stat-icon-profit">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
        </div>
        <div class="cs-stat-value" id="cs-orders-total-profit">
            <span class="cs-loading-spinner"></span>
        </div>
    </div>

    <?php if (!CS_Child_Manager::is_child_user()): ?>
    <div class="cs-orders-stat-card cs-stat-card-action">
        <button type="button" id="klarna-checkout-selected-btn" class="cs-bulk-order-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <?php _e('Beställ Väntande Ordrar', 'club-sales'); ?> ( <span id="pending-orders-count">0</span> )
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Package Overview Section (Förpackningsöversikt) -->
<div class="cs-package-overview-container" style="display: none;">
    <div class="cs-package-header">
        <div class="cs-package-header-left">
            <div class="cs-package-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
            </div>
            <div class="cs-package-title">
                <h3><?php _e('Förpackningsöversikt', 'club-sales'); ?></h3>
                <p><span id="cs-total-packages">0 paket</span> • <span id="cs-package-count">0 produkter</span></p>
            </div>
        </div>
        <button class="cs-package-toggle" id="cs-package-toggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </button>
    </div>
    
    <div class="cs-package-body" id="cs-package-body">
        <div class="cs-package-grid" id="cs-package-grid">
            <!-- Package items will be loaded here -->
            <div class="cs-package-loading">
                <span class="cs-loading-spinner"></span>
                <p><?php _e('Laddar förpackningsöversikt...', 'club-sales'); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="cs-orders-container">
    <table class="cs-orders-table">
        <thead>
            <tr>
                <th><?php _e('Order #', 'club-sales'); ?></th>
                <th><?php _e('Datum', 'club-sales'); ?></th>
                <th><?php _e('Kundnamn', 'club-sales'); ?></th>
                <th><?php _e('Säljare', 'club-sales'); ?></th>
                <th><?php _e('Kunden betalar', 'club-sales'); ?></th>
                <th><?php _e('NI betalar', 'club-sales'); ?></th>
				<th><?php _e('Vinst', 'club-sales'); ?></th>
                <th><?php _e('Status', 'club-sales'); ?></th>
                <th><?php _e('Alternativ', 'club-sales'); ?></th>
            </tr>
        </thead>
        <tbody id="orders-list">
            <!-- Orders will be loaded here -->
            <tr>
                <td colspan="9" class="cs-loading"><?php _e('Loading orders...', 'club-sales'); ?></td>
            </tr>
        </tbody>
    </table>
</div>
</div>

<style>
/* Orders Statistics Cards */
.cs-orders-stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 25px 0 30px 0;
    padding: 0;
}

.cs-orders-stat-card {
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 2px solid #f0f0f0;
    min-height: 120px;
}

.cs-orders-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.cs-stat-card-highlight {
    background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
    border-color: #4CAF50;
}

.cs-stat-card-action {
    background: #e8f5e9;
    border: none;
    box-shadow: 0 0 15px rgba(76, 175, 80, 0.3);
    align-items: center;
    justify-content: center;
}

.cs-stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    margin-bottom: 10px;
}

.cs-stat-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 10px;
}

.cs-stat-icon svg {
    fill: transparent;
    width: 20px;
    height: 20px;
}

.cs-stat-icon-orders {
    background: #e3f2fd;
    color: #1976d2;
}

.cs-stat-icon-sales {
    background: #fff3e0;
    color: #f57c00;
}

.cs-stat-icon-profit {
    background: #e8f5e9;
    color: #4CAF50;
}

.cs-stat-label {
    font-size: 13px;
    color: #666;
    font-weight: 500;
    line-height: 1.4;
}

.cs-stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    line-height: 1.2;
    margin-top: auto;
}

.cs-stat-card-highlight .cs-stat-value {
    color: #4CAF50;
}

/* Loading spinner */
.cs-loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #4CAF50;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Orders Header Layout */
.cs-order-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    gap: 20px;
    flex-wrap: wrap;
}

.cs-header-left h2 {
    margin: 0;
    font-size: 24px;
    color: #333;
}

.cs-header-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.cs-filter-container {
    display: flex;
    gap: 10px;
    align-items: center;
}

.cs-filter-container select {
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cs-filter-container select:hover {
    border-color: #4CAF50;
}

.cs-filter-container select:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

/* Bulk Order Button */
.cs-bulk-order-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.cs-bulk-order-btn:hover {
    background: #45a049;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.cs-bulk-order-btn:active {
    transform: translateY(0);
}

.cs-bulk-order-btn svg {
    flex-shrink: 0;
    fill: transparent;
}

#pending-orders-count {
    font-weight: 700;
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 8px;
    border-radius: 12px;
}

/* Responsive */
@media (max-width: 1024px) {
    .cs-order-section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .cs-header-right {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
    }
    
    .cs-filter-container {
        width: 100%;
        flex-direction: column;
    }
    
    .cs-filter-container select {
        width: 100%;
    }
    
    .cs-bulk-order-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .cs-orders-stats-container {
        grid-template-columns: 1fr;
        gap: 15px;
        margin: 20px 0;
    }
    
    .cs-orders-stat-card {
        padding: 16px;
    }
    
    .cs-stat-icon {
        width: 50px;
        height: 50px;
        margin-right: 12px;
    }
    
    .cs-stat-icon svg {
        width: 24px;
        height: 24px;
    }
    
    .cs-stat-value {
        font-size: 24px;
    }
}

/* Add margin to orders container */
.cs-orders-container {
    margin-top: 10px;
}

/* Package Overview Styles */
.cs-package-overview-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    margin: 25px 0;
    overflow: hidden;
    border: 2px solid #e8f5e9;
}

.cs-package-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
    cursor: pointer;
    transition: all 0.3s ease;
}

.cs-package-header:hover {
    background: linear-gradient(135deg, #e8f5e9 0%, #dcedc8 100%);
}

.cs-package-header-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.cs-package-icon {
    width: 48px;
    height: 48px;
    background: #4CAF50;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.cs-package-icon svg {
    fill: transparent;
}

.cs-package-title h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}

.cs-package-title p {
    margin: 4px 0 0 0;
    font-size: 14px;
    color: #666;
}

.cs-package-toggle {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: all 0.3s ease;
    color: #4CAF50;
}

.cs-package-toggle:hover {
    background: rgba(76, 175, 80, 0.1);
}

.cs-package-toggle svg {
    transition: transform 0.3s ease;
    fill: transparent;
}

.cs-package-overview-container.collapsed .cs-package-toggle svg {
    transform: rotate(180deg);
}

.cs-package-body {
    padding: 20px 25px;
    max-height: 500px;
    overflow-y: auto;
    transition: all 0.3s ease;
}

.cs-package-overview-container.collapsed .cs-package-body {
    max-height: 0;
    padding: 0 25px;
    overflow: hidden;
}

.cs-package-grid {
    display: grid;
    gap: 15px;
}
 
.cs-package-item {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 16px;
    transition: all 0.3s ease;
}

.cs-package-item:hover {
    border-color: #4CAF50;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.15);
}

.cs-package-item-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.cs-package-item-icon {
    width: 40px;
    height: 40px;
    background: #e8f5e9;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.cs-package-item-icon svg {
    width: 20px;
    height: 20px;
    color: #4CAF50;
    fill: transparent;
}

.cs-package-item-name {
    font-size: 15px;
    font-weight: 600;
    color: #2c3e50;
    line-height: 1.3;
}

.cs-package-item-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cs-package-stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
}

.cs-package-stat-label {
    color: #666;
}

.cs-package-stat-value {
    font-weight: 600;
    color: #2c3e50;
}

.cs-package-stat-value.extra {
    color: #ff6b6b;
}

.cs-package-stat-value.sold {
    color: #4CAF50;
}

.cs-package-stat-value.packaging {
    color: #2196F3;
}

.cs-package-loading {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.cs-package-loading p {
    margin-top: 15px;
    font-size: 14px;
}

.cs-package-empty {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.cs-package-empty p {
    font-size: 16px;
    color: #666;
}

/* Responsive Package Overview */
@media (max-width: 768px) {
    .cs-package-grid {
        grid-template-columns: 1fr;
    }
    
    .cs-package-header {
        padding: 15px 20px;
    }
    
    .cs-package-body {
        padding: 15px 20px;
    }
    
    .cs-package-title h3 {
        font-size: 16px;
    }
    
    .cs-package-title p {
        font-size: 13px;
    }
}

/*CSS TEST123*/
</style>

<?php include_once(dirname(__DIR__).'/swish-qr-modal.html'); ?>