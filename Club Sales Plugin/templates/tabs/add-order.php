<div class="cs-section-header">
    <h2><?php _e('Add New Order', 'club-sales'); ?></h2>
</div>

<form id="cs-order-form" class="cs-form">
    <div class="cs-form-section">
        <h3><?php _e('Selected Products', 'club-sales'); ?></h3>
        <div id="selected-products-list" class="cs-selected-products">
            <div class="cs-empty-selection"><?php _e('No products selected', 'club-sales'); ?></div>
        </div>
    </div>
    
    <div class="cs-form-section">
        <h3><?php _e('Customer Details', 'club-sales'); ?></h3>
        
        <div class="cs-form-row">
            <div class="cs-form-group">
                <label for="customer_name"><?php _e('Customer Name', 'club-sales'); ?></label>
                <input type="text" id="customer_name" name="customer_name" required>
            </div>
            <div class="cs-form-group">
                <label for="phone"><?php _e('Phone Number', 'club-sales'); ?></label>
                <input type="tel" id="phone" name="phone" required>
            </div>
        </div>
        
       <div class="cs-form-group">
			<label for="email"><?php _e('Email Address', 'club-sales'); ?></label>
			<input type="email" id="email" name="email" required>
		</div>
        
        <div class="cs-form-group">
            <label for="address"><?php _e('Delivery Address', 'club-sales'); ?></label>
            <textarea id="address" name="address" rows="3" required></textarea>
        </div>
        
        <div class="cs-form-group">
            <label for="product_quantity"><?php _e('Product Quantity', 'club-sales'); ?></label>
            <input type="number" id="product_quantity" name="product_quantity" min="1" value="1" class="cs-quantity-input">
            <p class="cs-form-description"><?php _e('Enter the quantity of the selected product', 'club-sales'); ?></p>
        </div>
        
        <div class="cs-form-row">
            <div class="cs-form-group">
                <label for="total_amount"><?php _e('Total Amount', 'club-sales'); ?> (<?php echo esc_html(get_option('cs_settings')['currency'] ?? 'SEK'); ?>)</label>
                <input type="number" id="total_amount" name="total_amount" readonly>
            </div>
            
            <div class="cs-form-group">
                <label for="sale_date"><?php _e('Sale Date', 'club-sales'); ?></label>
                <input type="date" id="sale_date" name="sale_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <div class="cs-form-group">
            <label for="notes"><?php _e('Notes', 'club-sales'); ?></label>
            <textarea id="notes" name="notes" rows="2"></textarea>
        </div>
    </div>
    
    <div class="cs-form-actions">
        <button type="submit" class="cs-submit-btn"><?php _e('Add Sale', 'club-sales'); ?></button>
    </div>
</form>