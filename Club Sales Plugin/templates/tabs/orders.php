<div class="cs-section-header">
    <h2><?php _e('Your Orders', 'club-sales'); ?></h2>
	<div class="cs-filter-parent-container">
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
    
    <?php if (!CS_Child_Manager::is_child_user()): ?>
    <div class="cs-bulk-actions">
        <button type="button" id="klarna-checkout-selected-btn" class="cs-klarna-btn">
            <?php _e('Process Orders', 'club-sales'); ?>
        </button>
    </div>
	</div>
    <?php endif; ?>
</div>

<div class="cs-orders-container">
    <table class="cs-orders-table">
        <thead>
            <tr>
                <th><?php _e('Order #', 'club-sales'); ?></th>
                <th><?php _e('Date', 'club-sales'); ?></th>
                <th><?php _e('Customer', 'club-sales'); ?></th>
                <th><?php _e('User', 'club-sales'); ?></th>
                <th><?php _e('Customer Pays', 'club-sales'); ?></th>
                <th><?php _e('Amount', 'club-sales'); ?></th>
				<th><?php _e('Profit', 'club-sales'); ?></th>
                <th><?php _e('Status', 'club-sales'); ?></th>
                <th><?php _e('Actions', 'club-sales'); ?></th>
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
