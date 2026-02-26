/**
 * Club Child Users - JavaScript functionality with Auto-Update Sales Material
 */
(function($) {
    'use strict';

    // Store reference to whether we're a child user
    const isChildUser = typeof CS_Child_Manager !== 'undefined' && CS_Child_Manager.is_child_user === true;
    
    // Auto-update functionality
    function triggerStatsUpdate() {
        // Trigger custom event for stats update
        $(document).trigger('cs_sale_added', { timestamp: Date.now() });
        
        // Also trigger global stats update if function exists
        if (typeof window.triggerStatsUpdate === 'function') {
            window.triggerStatsUpdate();
        }
        
        console.log('Stats update triggered from child scripts');
    }
    
    /**
     * Helper function to save selected product to localStorage
     */
    function saveSelectedProductToLocalStorage(product) {
        if (product) {
            localStorage.setItem('cs_selected_product', JSON.stringify(product));
        }
    }

    /**
     * Helper function to load selected product from localStorage
     */
    function loadSelectedProductFromLocalStorage() {
        const savedProduct = localStorage.getItem('cs_selected_product');
        if (savedProduct) {
            try {
                const product = JSON.parse(savedProduct);
                return product;
            } catch (e) {
                console.error('Error parsing saved product:', e);
            }
        }
        return null;
    }

// Initialize the child user management form
function initChildUserForm() {
    $('#cs-add-child-form').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const formData = {
            action: 'cciu_add_child',
            nonce: $('#cciu_nonce').val(),
            email: $('#child_email').val(),
            first_name: $('#child_first_name').val(),
            last_name: $('#child_last_name').val(),
            password: $('#child_password').val(),
            child_team: $('#child_team').val() || ''
        };

        // Validate the form
        if (!formData.email || !formData.first_name || !formData.last_name || !formData.password) {
            alert('Please fill in all required fields.');
            return;
        }

        console.log('=== ADDING CHILD USER ===');
        console.log('Form data:', formData);

        $.ajax({
            url: csChildAjax.ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#cs-add-child-form button[type="submit"]').prop('disabled', true).text('Lägger till...');
            },
            success: function(response) {
                console.log('Add child response:', response);
                
                if (response.success) {
                    $('#cs-add-child-form')[0].reset();
                    
                    const child = response.data.child;
                    const teamName = response.data.team_data ? response.data.team_data.name : 'N/A';
                    
                    // Format the date properly (matching existing rows)
                    const registeredDate = new Date(child.registered);
                    const formattedDate = registeredDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: '2-digit' 
                    });
                    
                    console.log('Selected products:', window.selectedProducts);
                    
                    // AUTO-ASSIGN SELECTED PRODUCTS TO NEW CHILD VIA AJAX
                    if (window.selectedProducts && window.selectedProducts.length > 0) {
                        console.log('Starting auto-assignment for', window.selectedProducts.length, 'products');
                        
                        // Show loading message
                        const loadingRow = `
<tr data-id="${child.id}" class="cs-loading-row">
    <td>${child.name}</td>
    <td class="td-email">${child.email}</td>
    <td>${teamName}</td>
    <td>${formattedDate}</td>
    <td>
        <span class="cs-status-badge cs-status-loading">
            <span class="cs-spinner"></span> Tilldelar produkter...
        </span>
    </td>
</tr>
`;
                        
                        // Add loading row
                        if ($('#children-list tr td[colspan]').length) {
                            $('#children-list').html(loadingRow);
                        } else {
                            $('#children-list').prepend(loadingRow);
                        }
                        
                        autoAssignProductsToChild(child.id, window.selectedProducts, function(success) {
                            console.log('Auto-assignment callback, success:', success);
                            
                            // Remove loading row
                            $('.cs-loading-row').remove();
                            
                            // Add final row with Radera button (like after refresh)
                            const newRow = `
<tr data-id="${child.id}" data-team-id="${response.data.team_data ? response.data.team_data.id : ''}">
    <td>${child.name}</td>
    <td class="td-email">${child.email}</td>
    <td>${teamName}</td>
    <td>${formattedDate}</td>
    <td>
        <button type="button" class="cs-action cs-remove-child" data-child-id="${child.id}">Radera</button>
    </td>
</tr>
`;
                            
                            if ($('#children-list tr td[colspan]').length) {
                                $('#children-list').html(newRow);
                            } else {
                                $('#children-list').prepend(newRow);
                            }
                            
                            // Update team card seller count
                            if (response.data.team_data && response.data.team_data.id) {
                                console.log('Attempting to update team card for team:', response.data.team_data);
                                
                                // Add slight delay to ensure DOM is updated
                                setTimeout(function() {
                                    updateTeamCardCount(response.data.team_data.id);
                                }, 100);
                            } else {
                                console.log('No team data in response:', response.data);
                            }
                            
                            // Re-attach event handlers for the new button
                            attachChildActions();
                            
                            if (success) {
                                alert('Säljare har lagts till och produkter har tilldelats automatiskt!');
                            } else {
                                alert('Säljare lades till men produkttilldelning misslyckades. Vänligen tilldela manuellt.');
                            }
                            
                            // Show password if generated
                            if (response.data.password) {
                                alert('Generated Password: ' + response.data.password + '\n\nPlease save this password!');
                            }
                            
                            // Clear selected products
                            window.selectedProducts = [];
                            if (typeof window.updateSelectedProductsList === 'function') {
                                window.updateSelectedProductsList();
                            }
                        });
                    } else {
                        console.log('No products selected - showing normal row');
                        
                        // No products selected - show normal row with Radera button
                        const newRow = `
<tr data-id="${child.id}" data-team-id="${response.data.team_data ? response.data.team_data.id : ''}">
    <td>${child.name}</td>
    <td class="td-email">${child.email}</td>
    <td>${teamName}</td>
    <td>${formattedDate}</td>
    <td>
        <button type="button" class="cs-action cs-remove-child" data-child-id="${child.id}">Radera</button>
    </td>
</tr>
`;
                        
                        if ($('#children-list tr td[colspan]').length) {
                            $('#children-list').html(newRow);
                        } else {
                            $('#children-list').prepend(newRow);
                        }
                        
                        // Update team card seller count
                        if (response.data.team_data && response.data.team_data.id) {
                            console.log('Attempting to update team card for team (no products):', response.data.team_data);
                            
                            // Add slight delay to ensure DOM is updated
                            setTimeout(function() {
                                updateTeamCardCount(response.data.team_data.id);
                            }, 100);
                        } else {
                            console.log('No team data in response (no products):', response.data);
                        }
                        
                        // Re-attach event handlers for the new button
                        attachChildActions();
                        
                        alert(response.data.message);
                        
                        // Show password if generated
                        if (response.data.password) {
                            alert('Generated Password: ' + response.data.password + '\n\nPlease save this password!');
                        }
                    }
                } else {
                    alert(response.data || 'Error adding child user.');
                }

                $('#cs-add-child-form button[type="submit"]').prop('disabled', false).text('Lägg Till Säljare');
            },
            error: function(xhr, status, error) {
                console.error('Error adding child:', { status, error, response: xhr.responseText });
                alert('Error adding child user. Please try again.');
                $('#cs-add-child-form button[type="submit"]').prop('disabled', false).text('Lägg Till Säljare');
            }
        });
        
        return false;
    });
}

// FIXED: Auto-assign function with proper error handling and logging
function autoAssignProductsToChild(childId, products, callback) {
    console.log('=== AUTO-ASSIGN START ===');
    console.log('Child ID:', childId);
    console.log('Products to assign:', products);
    console.log('Total products:', products.length);
    console.log('AJAX URL:', csChildAjax.ajaxurl);
    console.log('Nonce:', csChildAjax.nonce);
    
    // Check if products array is empty or undefined
    if (!products || products.length === 0) {
        console.log('No products to assign, calling callback with false');
        callback(false);
        return;
    }
    
    let assignedCount = 0;
    let failedCount = 0;
    const totalProducts = products.length;
    
    products.forEach(function(product, index) {
        console.log(`[${index + 1}/${totalProducts}] Attempting to assign product:`, product);
        
        const requestData = {
            action: 'cciu_assign_product',
            nonce: csChildAjax.nonce,
            child_id: childId,
            product_id: product.id
        };
        
        console.log('Request data:', requestData);
        
        $.ajax({
            url: csChildAjax.ajaxurl,
            type: 'POST',
            data: requestData,
            success: function(response) {
                assignedCount++;
                console.log(`✓ [${assignedCount}/${totalProducts}] Product assigned successfully:`, product.name);
                console.log('Response:', response);
                
                // Check if all products processed
                if (assignedCount + failedCount === totalProducts) {
                    console.log('=== AUTO-ASSIGN COMPLETE ===');
                    console.log('Success:', assignedCount, 'Failed:', failedCount);
                    callback(failedCount === 0);
                }
            },
            error: function(xhr, status, error) {
                failedCount++;
                console.error(`✗ [${failedCount} failures] Failed to assign product:`, product.name);
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response text:', xhr.responseText);
                console.error('Status code:', xhr.status);
                
                // Check if all products processed
                if (assignedCount + failedCount === totalProducts) {
                    console.log('=== AUTO-ASSIGN COMPLETE (with errors) ===');
                    console.log('Success:', assignedCount, 'Failed:', failedCount);
                    callback(failedCount === 0);
                }
            }
        });
    });
}

// Function to update team card seller count
function updateTeamCardCount(teamId) {
    console.log('=== UPDATE TEAM CARD COUNT ===');
    console.log('Team ID:', teamId);
    console.log('Team ID type:', typeof teamId);
    
    // If no team ID, nothing to update
    if (!teamId || teamId === '' || teamId === 'undefined') {
        console.log('No valid team ID provided, skipping update');
        return;
    }
    
    // Try multiple selectors to find the team card (might be in different contexts)
    let $teamCard = $('.cs-team-card[data-team-id="' + teamId + '"]');
    
    // If not found, try with parseInt
    if ($teamCard.length === 0) {
        const teamIdNum = parseInt(teamId);
        console.log('Trying with parsed ID:', teamIdNum);
        
        $('.cs-team-card').each(function() {
            const cardTeamId = $(this).data('team-id');
            console.log('Checking card with team-id:', cardTeamId, 'Type:', typeof cardTeamId);
            
            if (parseInt(cardTeamId) === teamIdNum) {
                $teamCard = $(this);
                console.log('✓ Found matching team card!');
                return false; // break
            }
        });
    }
    
    console.log('Team card selector:', '.cs-team-card[data-team-id="' + teamId + '"]');
    console.log('Team cards found:', $teamCard.length);
    
    if ($teamCard.length === 0) {
        console.log('⚠️ Team card not found for ID:', teamId);
        console.log('Available team cards:', $('.cs-team-card').length);
        
        // Log all team card IDs for debugging
        $('.cs-team-card').each(function() {
            console.log('  - Team card ID:', $(this).data('team-id'), 'Type:', typeof $(this).data('team-id'));
        });
        
        return;
    }
    
    // Count sellers in this team from the table
    let sellersCount = 0;
    
    // Try direct selector first
    let $sellersInTeam = $('#children-list tr[data-team-id="' + teamId + '"]');
    sellersCount = $sellersInTeam.length;
    
    // If no results, try comparing as numbers
    if (sellersCount === 0) {
        const teamIdNum = parseInt(teamId);
        $('#children-list tr').each(function() {
            const rowTeamId = $(this).data('team-id');
            if (rowTeamId && parseInt(rowTeamId) === teamIdNum) {
                sellersCount++;
            }
        });
    }
    
    console.log('Sellers table selector:', '#children-list tr[data-team-id="' + teamId + '"]');
    console.log('Sellers found in table:', sellersCount);
    
    // Log all rows for debugging
    console.log('All rows in children list:');
    $('#children-list tr').each(function(index) {
        const rowTeamId = $(this).data('team-id');
        console.log('  Row', index, '- team-id:', rowTeamId, 'Type:', typeof rowTeamId);
    });
    
    // Update the seller count display
    const newText = sellersCount + ' säljare';
    const $sellerElement = $teamCard.find('.cs-team-sellers');
    
    console.log('Seller element found:', $sellerElement.length);
    console.log('Current text:', $sellerElement.text());
    
    $sellerElement.text(newText);
	
	// Reset animation after a moment
    setTimeout(function() {
        $sellerElement.css({
            'transform': 'scale(1)',
            'color': '',
            'font-weight': ''
        });
    }, 500);
    
    console.log('✅ Team card updated successfully');
    console.log('New text:', newText);
    console.log('=== END UPDATE ===');
}

    // Handle Sales Material tab functionality
    function initSalesMaterialTab() {
        // Initialize child select dropdown in the sales material tab
        $('#sales_material_child_id').on('change', function() {
            const childId = $(this).val();
            if (childId) {
                // Redirect to the same page with child_id parameter
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('child_id', childId);
                window.location.href = currentUrl.toString();
            }
        });

        // Load sales materials for selected products
        $('.cs-material-card').on('click', function() {
            const productId = $(this).data('product-id');
            // You can add additional behavior when clicking on a material card
        });
    }
    
    // NEW FUNCTION: Update Sales Material Tab when product is selected
    window.updateSalesMaterialTabChild = function updateSalesMaterialTab(productId) {
        console.log('Updating Sales Material tab for product:', productId);
        
        // Check if sales material tab exists
        const salesMaterialTab = $('#tab-sales-material');
        if (!salesMaterialTab.length) {
            console.log('Sales Material tab not found');
            return;
        }
        
        // Instead of replacing the entire tab, just trigger a reload of products
        // by dispatching a custom event that the sales-material template can listen to
        const productsContainer = salesMaterialTab.find('#cs-products-container');
        const productsCount = salesMaterialTab.find('#cs-products-count');
        
        if (productsContainer.length) {
            // Show loading state
            productsContainer.html('<div style="text-align: center; padding: 20px; color: #666;">Laddar...</div>').show();
            
            // Trigger product reload by calling the AJAX directly
            $.ajax({
                url: typeof csAjax !== 'undefined' ? csAjax.ajaxurl : csChildAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cs_get_selected_products',
                    nonce: typeof csAjax !== 'undefined' ? csAjax.nonce : csChildAjax.nonce
                },
                success: function(response) {
                    console.log('📦 Sales Material - Products reloaded:', response);
                    if (response.success && response.data.products && response.data.products.length > 0) {
                        const products = response.data.products;
                        productsContainer.show();
                        
                        let html = '';
                        let materialsHtml = '';
                        let hasMaterials = false;
                        
                        products.forEach(function(product) {
                            html += '<div class="cs-sm-product-item">';
                            html += '<img src="' + product.image + '" alt="' + product.name + '" class="cs-sm-product-img">';
                            html += '<div class="cs-sm-product-info">';
                            html += '<h4>' + product.name + '</h4>';
                            html += '<div class="cs-sm-product-price">' + product.price + '</div>';
                            html += '</div>';
                            html += '</div>';
                            
                            // Build sales materials list
                            if (product.sales_pitches) {
                                hasMaterials = true;
                                materialsHtml += '<div class="cs-sm-material-item">';
                                materialsHtml += '<div class="cs-sm-material-info">';
                                materialsHtml += '<div class="cs-sm-material-icon">';
                                materialsHtml += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>';
                                materialsHtml += '</div>';
                                materialsHtml += '<div class="cs-sm-material-details">';
                                materialsHtml += '<h4>Sales Pitches</h4>';
                                materialsHtml += '<p>' + product.name + '</p>';
                                materialsHtml += '</div>';
                                materialsHtml += '</div>';
                                materialsHtml += '<div class="cs-sm-material-actions">';
                                materialsHtml += '<a href="' + product.sales_pitches + '" class="cs-sm-material-btn" download><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Ladda ner</a>';
                                materialsHtml += '<a href="' + product.sales_pitches + '" class="cs-sm-material-btn secondary" target="_blank"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> Förhandgranska</a>';
                                materialsHtml += '</div>';
                                materialsHtml += '</div>';
                            }
                            
                            if (product.product_image) {
                                hasMaterials = true;
                                materialsHtml += '<div class="cs-sm-material-item">';
                                materialsHtml += '<div class="cs-sm-material-info">';
                                materialsHtml += '<div class="cs-sm-material-icon">';
                                materialsHtml += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
                                materialsHtml += '</div>';
                                materialsHtml += '<div class="cs-sm-material-details">';
                                materialsHtml += '<h4>Product Image</h4>';
                                materialsHtml += '<p>' + product.name + '</p>';
                                materialsHtml += '</div>';
                                materialsHtml += '</div>';
                                materialsHtml += '<div class="cs-sm-material-actions">';
                                materialsHtml += '<a href="' + product.product_image + '" class="cs-sm-material-btn" download><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Ladda ner</a>';
                                materialsHtml += '<a href="' + product.product_image + '" class="cs-sm-material-btn secondary" target="_blank"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> Förhandgranska</a>';
                                materialsHtml += '</div>';
                                materialsHtml += '</div>';
                            }
                            
                            if (product.social_media_content) {
                                hasMaterials = true;
                                materialsHtml += '<div class="cs-sm-material-item">';
                                materialsHtml += '<div class="cs-sm-material-info">';
                                materialsHtml += '<div class="cs-sm-material-icon">';
                                materialsHtml += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>';
                                materialsHtml += '</div>';
                                materialsHtml += '<div class="cs-sm-material-details">';
                                materialsHtml += '<h4>Social Media Content</h4>';
                                materialsHtml += '<p>' + product.name + '</p>';
                                materialsHtml += '</div>';
                                materialsHtml += '</div>';
                                materialsHtml += '<div class="cs-sm-material-actions">';
                                materialsHtml += '<a href="' + product.social_media_content + '" class="cs-sm-material-btn" download><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Ladda ner</a>';
                                materialsHtml += '<a href="' + product.social_media_content + '" class="cs-sm-material-btn secondary" target="_blank"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> Förhandgranska</a>';
                                materialsHtml += '</div>';
                                materialsHtml += '</div>';
                            }
                        });
                        
                        productsContainer.html(html);
                        
                        const materialsList = salesMaterialTab.find('#cs-materials-list');
                        if (materialsList.length) {
                            if (hasMaterials) {
                                materialsList.html(materialsHtml).show();
                            } else {
                                materialsList.html('<p style="text-align: center; color: #9ca3af; padding: 20px;">Inga säljmaterial tillgängliga för dessa produkter</p>').show();
                            }
                        }
                        
                        const count = products.length;
                        productsCount.text(count + ' produkt' + (count > 1 ? 'er' : '') + ' valda');
                        
                        console.log('✅ Sales Material tab updated with', count, 'products');
                    } else {
                        productsContainer.hide();
                        const materialsList = salesMaterialTab.find('#cs-materials-list');
                        if (materialsList.length) {
                            materialsList.hide();
                        }
                        productsCount.text('Inga produkter valda ännu');
                    }
                },
                error: function() {
                    productsContainer.hide();
                    productsCount.text('Inga produkter valda ännu');
                }
            });
        }
    }
    
    // Also expose as updateSalesMaterialTab for backward compatibility
    window.updateSalesMaterialTab = window.updateSalesMaterialTabChild;
    
// Attach event listeners to child action buttons
function attachChildActions() {
    // Edit child button - using event delegation
    $(document).off('click', '.cs-edit-child').on('click', '.cs-edit-child', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $button = $(this);
        const childId = $button.data('child-id');
        const childName = $button.data('child-name');
        const childEmail = $button.data('child-email');
        const childTeam = $button.data('child-team');
        
        console.log('Edit child clicked:', { childId, childName, childEmail, childTeam });
        
        if (!childId) {
            alert('Invalid seller ID');
            return false;
        }
        
        // Populate modal with child data
        $('#edit_child_id').val(childId);
        $('#edit_child_name').val(childName);
        $('#edit_child_email').val(childEmail);
        $('#edit_child_team').val(childTeam || '');
        
        // Show modal
        $('#cs-edit-child-modal').fadeIn(300);
    });
    
    // Remove child button - using event delegation
    $(document).off('click', '.cs-remove-child').on('click', '.cs-remove-child', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $button = $(this);
        const childId = $button.data('child-id');
        const $row = $button.closest('tr');
        const teamId = $row.data('team-id'); // Get team ID before removing row
        
        console.log('Delete child clicked, ID:', childId, 'Team ID:', teamId);
        
        if (!childId) {
            alert('Invalid user ID');
            return false;
        }
        
        if (!confirm('Are you sure you want to remove this seller?')) {
            return false;
        }
        
        // Disable button
        $button.prop('disabled', true).text('Removing...');
        
        // Delete via AJAX
        $.ajax({
            url: csChildAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cciu_remove_child',
                nonce: csChildAjax.nonce,
                child_id: childId
            },
            success: function(response) {
                console.log('Delete child response:', response);
                
                if (response.success) {
                    // Fade out and remove row
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Update team card count if child was in a team
                        if (teamId) {
                            updateTeamCardCount(teamId);
                        }
                        
                        // Check if table is now empty
                        if ($('#children-list tr').length === 0) {
                            $('#children-list').html('<tr><td colspan="5" class="cs-no-data">No sellers found.</td></tr>');
                        }
                    });
                    
                    alert('Seller removed successfully!');
                } else {
                    alert('Error: ' + (response.data || 'Could not remove seller'));
                    $button.prop('disabled', false).text('Radera');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response:', xhr.responseText);
                alert('Error: Could not remove seller. Please try again.');
                $button.prop('disabled', false).text('Radera');
            }
        });
        
        return false;
    });

    // Assign products button
    $('.cs-assign-products').off('click').on('click', function() {
        const childId = $(this).data('id');
        const childName = $(this).data('name');

        // If we're on the dashboard, switch to the assign products tab
        if ($('.cs-tab-item[data-tab="assign-product"]').length) {
            $('.cs-tab-item[data-tab="assign-product"]').trigger('click');

            // Wait for the tab to load, then select the child
            setTimeout(function() {
                $('#child_id').val(childId);
                loadChildProducts(childId);
            }, 300);
        } else {
            // Redirect to the assign products page if not in tab layout
            alert('Please go to the Assign Products tab to assign products to ' + childName);
        }
    });
}
    // Helper function to format dates
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    // Initialize child select dropdown in the assign products tab
    function initChildSelect() {
        $('#child_id').on('change', function() {
            const childId = $(this).val();
            if (childId) {
                loadChildProducts(childId);
            } else {
                // Hide the products container
                $('.cs-child-products').hide();
            }
        });
    }

    // Load child products via AJAX
    function loadChildProducts(childId) {
        // Show loading indicator 
        $('.cs-child-products').show().html('<div class="cs-loading">Loading products for this child...</div>');

        $.ajax({
            url: csChildAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cciu_get_child_products',
                nonce: csChildAjax.nonce,
                child_id: childId
            },
            success: function(response) {
                if (response.success) {
                    // Refresh product display with AJAX
                    refreshAssignProductsView(childId);
                } else {
                    $('.cs-child-products').html('<div class="cs-error">Error loading products: ' + (response.data || 'Unknown error') + '</div>');
                }
            },
            error: function() {
                $('.cs-child-products').html('<div class="cs-error">Error loading products. Please try again.</div>');
            }
        });
    }

    // Refresh the assign products view 
    function refreshAssignProductsView(childId) {
        // Show loading indicator while refreshing
        $('.cs-child-products').prepend('<div class="cs-loading-refresh">Refreshing product list...</div>');

        // Get current search term if any
        const searchTerm = $('#product-search').val() || '';

        // Add a timestamp to prevent caching
        const timestamp = new Date().getTime();

        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: {
                child_id: childId,
                search: searchTerm,
                ajax: 1,
                _: timestamp // Prevent caching
            },
            success: function(html) {
                // Remove loading indicator
                $('.cs-loading-refresh').remove();
                // Extract only the cs-child-products div from the response
                const tempDiv = $('<div>').html(html);
                const productsHtml = tempDiv.find('.cs-child-products').html();

                if (productsHtml) {
                    $('.cs-child-products').html(productsHtml);

                    // Re-attach event handlers for assign/unassign buttons
                    initProductAssignButtons();
                } else {
                    $('.cs-child-products').html('<div class="cs-error">Error loading products content.</div>');
                }
            },
            error: function(xhr, status, error) {
                // Remove loading indicator
                $('.cs-loading-refresh').remove();

                console.error("Error refreshing products view:", status, error);
                $('.cs-child-products').html('<div class="cs-error">Error loading products. Please try again.</div>');
            }
        });
    }

    // Initialize assign/unassign product buttons
    function initProductAssignButtons() {
        $('.cs-child-products').off('click', '.cs-assign-btn, .cs-assign-all-btn, .cs-unassign-product')
        .on('click', '.cs-assign-btn, .cs-assign-all-btn, .cs-unassign-product', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            const isUnassign = $(this).hasClass('assigned') || $(this).hasClass('cs-unassign-product');
            const isAssignAll = $(this).hasClass('cs-assign-all-btn');
            const childId = form.find('input[name="child_id"]').val();
            const productId = form.find('input[name="product_id"]').val();
            const requestData = {
                action: isUnassign ? 'cciu_unassign_product' : 'cciu_assign_product',
                nonce: csChildAjax.nonce,
                child_id: childId,
                product_id: productId
            };

            $.ajax({
                url: csChildAjax.ajaxurl,
                type: 'POST',
                data: requestData,
                beforeSend: function() {
                    $(e.target).prop('disabled', true).text('Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        // Update Sales Material tab immediately without page reload
                        updateSalesMaterialTab(productId);
                        
                        // FORCE PAGE RELOAD with timestamp to prevent caching
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('_t', Date.now());
                        
                        // Add child_id if not assigning to all
                        if (!isAssignAll && childId) {
                            currentUrl.searchParams.set('child_id', childId);
                        }                    
                        // Confirm before reload
                        if (confirm('Product assigned successfully. Click OK to refresh the page.')) {
                            window.location.href = currentUrl.toString();
                        }
                    } else {
                        alert('Error: ' + (response.data || 'Could not assign product'));
                        $(e.target).prop('disabled', false).text('Assign');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("DEBUGGING: AJAX Error", {status, error});
                    alert('Error: Could not assign product. Please try again.');
                    $(e.target).prop('disabled', false).text('Assign');
                }
            });
        });
    }

    // Define the global updateSelectedProductsList function if it doesn't exist
    function defineUpdateSelectedProductsList() {
        if (typeof window.updateSelectedProductsList !== 'function') {
            window.updateSelectedProductsList = function() {                
                let html = '';
                let totalAmount = 0;
                
                if (!window.selectedProducts || window.selectedProducts.length === 0) {
                    html = '<div class="cs-empty-selection">No products selected</div>';
                } else {
                    window.selectedProducts.forEach(function(product) {
                        totalAmount += parseFloat(product.price);
                        html += `
                            <div class="cs-selected-product" data-id="${product.id}">
                                <div class="cs-product-details">
                                    <span class="cs-product-title">${product.name}</span>
                                    <span class="cs-product-meta">SKU: ${product.sku || 'N/A'}</span>
                                </div>
                                <div class="cs-product-price-wrapper">
                                    <span class="cs-product-price">${product.price} ${csChildAjax.currency || 'SEK'}</span>
                                    <button type="button" class="cs-remove-product" data-id="${product.id}">
                                        <span class="dashicons dashicons-no-alt"></span> Remove
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                }
                
                $('#selected-products-list').html(html);
                $('#total_amount').val(totalAmount.toFixed(2));
                
                // Update Klarna checkout button state if it exists
                if ($('#klarna-checkout-btn').length) {
                    $('#klarna-checkout-btn').prop('disabled', !window.selectedProducts || window.selectedProducts.length === 0);
                }
                
                // Add remove handlers
                $('.cs-remove-product').on('click', function(e) {
                    e.preventDefault();
                    const productId = $(this).data('id');
                    
                    // For child users, don't allow removing the assigned product
                    if (typeof CS_Child_Manager !== 'undefined' && CS_Child_Manager.is_child_user === true) {
                        alert('As a child user, you cannot remove your assigned product.');
                        return;
                    }
                    
                    // Remove from selected products
                    window.selectedProducts = window.selectedProducts.filter(item => item.id !== productId);
                    
                    // Update selected products list
                    window.updateSelectedProductsList();
                    
                    // Update product cards if we're on the products tab
                    $(`.cs-product-card[data-id="${productId}"]`).removeClass('selected');
                });
            };
        }
    }

    // Modify the product selection handler to work across different contexts
    function loadProductSalesMaterials(productId) {
        $.ajax({
            url: csChildAjax.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_product_sales_materials',
                nonce: csChildAjax.nonce,
                product_id: productId
            },
            success: function(response) {
                // Create debug output container
                const debugContainer = $('<div class="cs-debug-info"></div>');

                if (response.success) {
                    const materials = response.data.materials;
                    const materialsContainer = $('.cs-sales-materials');
                    materialsContainer.empty();

                    // Add debug information
                    debugContainer.append('<h4>Debug Information</h4>');
                    debugContainer.append(`<pre>${JSON.stringify(response.data.debug, null, 2)}</pre>`);

                    // Add description if exists
                    if (materials.description) {
                        materialsContainer.append(`
<div class="cs-sales-description">
<h3>Sales Description</h3>
<p>${materials.description}</p>
</div>
`);
                    }

                    // Add images if they exist
                    const imagesContainer = $('<div class="cs-sales-images"></div>');

                    if (materials.image_1) {
                        imagesContainer.append(`
<div class="cs-sales-image">
<img src="${materials.image_1}" alt="Sales Material Image 1">
</div>
`);
                    }

                    if (materials.image_2) {
                        imagesContainer.append(`
<div class="cs-sales-image">
<img src="${materials.image_2}" alt="Sales Material Image 2">
</div>
`);
                    }

                    materialsContainer.append(imagesContainer);

                    // Append debug container
                    materialsContainer.append(debugContainer);

                    // Show the materials section
                    materialsContainer.show();
                } else {
                    // Add error debug information
                    $('.cs-sales-materials')
                        .html(`
<p>Error loading sales materials</p>
<div class="cs-debug-info">
<h4>Debug Information</h4>
<pre>${JSON.stringify(response.data, null, 2)}</pre>
</div>
`)
                        .show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('.cs-sales-materials')
                    .html(`
<p>Error loading sales materials</p>
<div class="cs-debug-info">
<h4>AJAX Error</h4>
<pre>Status: ${status}\nError: ${error}\nResponse: ${xhr.responseText}</pre>
</div>
`)
                    .show();
            }
        });
    }

    // Swedish rounding function (client-side implementation)
    function swedishRound(price) {
        // Check if we're in child user context
        const isChildUser = typeof CS_Child_Manager !== 'undefined' && CS_Child_Manager.is_child_user === true;
        
        // If child user, return exact price
        if (isChildUser) {
            return price;
        }
        
        // Convert to number
        price = parseFloat(price);
        
        // Round up to the nearest whole number
        let rounded = Math.ceil(price);
        
        // Find the last digit
        let lastDigit = rounded % 10;
        
        // Rounding logic to nearest 9
        if (lastDigit <= 1) {
            // Round down to 9 of the current 10s group
            return Math.floor(rounded / 10) * 10 - 1;
        } else if (lastDigit >= 2 && lastDigit <= 8) {
            // Round to 9 of the current 10s group
            return Math.floor(rounded / 10) * 10 + 9;
        } else {
            // Already at 9, keep as is
            return rounded;
        }
    }

    function makeChildProductsSelectable() {
        if ($('#child-products-list').length) {
            $('#child-products-list .cs-product-card').on('click', function() {
                // Remove selection from all products first
                $('#child-products-list .cs-product-card').removeClass('selected');
                
                const productId = $(this).data('id');
                const productName = $(this).data('name');
                
                // Update Sales Material tab when product is selected
                updateSalesMaterialTab(productId);
                
                // Determine if we're a child user
                const isChildUser = typeof CS_Child_Manager !== 'undefined' && CS_Child_Manager.is_child_user === true;
                
                // Get total price
                let productTotalPrice = parseFloat($(this).data('total-price'));
                
                // Apply Swedish rounding for parent users
                if (!isChildUser) {
                    productTotalPrice = swedishRound(productTotalPrice);
                }
                
                const productSku = $(this).data('sku');
                
                const product = {
                    id: productId,
                    name: productName,
                    price: productTotalPrice,
                    total_price: productTotalPrice,
                    sku: productSku || 'N/A',
                    image: $(this).find('.cs-product-image img').attr('src') || '',
                    quantity: 1 // Always set default quantity to 1
                };

                // Make sure selectedProducts is available globally
                window.selectedProducts = [product];

                // Update the product list in the Add Order tab
                if (typeof window.updateSelectedProductsList === 'function') {
                    window.updateSelectedProductsList();
                }

                // Add selected class to the clicked product
                $(this).addClass('selected');

                // Clear existing selection indicators
                $('.cs-selected-checkmark, .cs-selected-text').remove();
                
                // Add selection indicators
                $(this).find('.cs-product-sku').append('<span class="cs-selected-checkmark">✓</span>');
                $(this).find('.cs-product-price').append('<span class="cs-selected-text"> - Selected</span>');
            });
        }
    }

    // ENHANCED: Update main product selection in Assign Product tab to also update Sales Material
    function enhanceMainProductSelection() {
        // Override the main product selection to also update Sales Material tab
        $(document).on('click', '.cs-product-card', function(e) {
            // Check if this is in the assign products tab
            if ($(this).closest('#tab-assign-product').length) {
                const productId = $(this).data('id');
                console.log('Product selected in Assign Product tab:', productId);
                
                // Update Sales Material tab immediately
                updateSalesMaterialTab(productId);
            }
        });
    }

    // Rest of your existing functions...
    function updateSelectedProductsList() {
        let html = '';
        let localTotalAmount = 0;
        
        if (!window.selectedProducts || window.selectedProducts.length === 0 || 
            (window.selectedProducts[0].quantity === 0)) {
            html = '<div class="cs-empty-selection">No products selected</div>';
        } else {
            window.selectedProducts.forEach(function(product, index) {
                // Skip if quantity is 0
                if (product.quantity === 0) return;

                // Determine if we're a child user
                const isChildUser = typeof CS_Child_Manager !== 'undefined' && CS_Child_Manager.is_child_user === true;
                
                // For parent users, apply Swedish rounding
                const productPrice = !isChildUser ? 
                    swedishRound(parseFloat(product.price)) : 
                    parseFloat(product.price);
                
                if ($('#product_quantity').length) {
                    $('#product_quantity').val(product.quantity);
                }

                const lineTotal = productPrice * product.quantity;
                localTotalAmount += lineTotal;

                html += `
                <div class="cs-selected-product" data-id="${product.id}">
                    <div class="cs-product-details">
                        <span class="cs-product-title">${product.name}</span>
                        <span class="cs-product-meta">SKU: ${product.sku || 'N/A'}</span>
                    </div>
                    <div class="cs-product-price-wrapper">
                        <span class="cs-product-price">${lineTotal.toFixed(2)} ${csChildAjax.currency || 'SEK'}</span>
                        <button type="button" class="cs-remove-product" data-id="${product.id}">
                            <span class="dashicons dashicons-no-alt"></span> Remove
                        </button>
                    </div>
                </div>
                `;
            });
        }
        
        $('#selected-products-list').html(html);
        
        // Apply rounding to total amount for parent users
        const isChildUser = typeof CS_Child_Manager !== 'undefined' && CS_Child_Manager.is_child_user === true;
        const finalTotalAmount = !isChildUser ? 
            swedishRound(localTotalAmount) : 
            localTotalAmount;
        
        $('#total_amount').val(finalTotalAmount.toFixed(2));
        
        // Update Klarna checkout button state
        $('#klarna-checkout-btn').prop('disabled', !window.selectedProducts || window.selectedProducts.length === 0);

        // Re-attach remove handlers
        $('.cs-remove-product').off('click').on('click', function(e) {
            e.preventDefault();
            const productId = $(this).data('id');

            // Clear selection
            window.selectedProducts = []; // Assuming single product logic remains

            // Update selected products list (will show empty)
            updateSelectedProductsList();

            // Update product cards if we're on the products tab
            const productCard = $(`.cs-product-card[data-id="${productId}"]`);
            if (productCard.length) {
                productCard.removeClass('selected');
                productCard.find('.cs-selected-checkmark, .cs-selected-text').remove();
            }

            // Remove from localStorage
            localStorage.removeItem('cs_selected_product');
        });
    }

    function autoSelectAssignedProductForChild() {
        $.ajax({
            url: csChildAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cciu_get_child_products',
                nonce: csChildAjax.nonce,
                child_id: 0 // 0 means current user
            },
            success: function(response) {           
                if (response.success && response.data.products && response.data.products.length === 1) {
                    const assignedProduct = response.data.products[0];
                    
                    // Determine if we're a child user
                    const isChildUser = typeof CS_Child_Manager !== 'undefined' && CS_Child_Manager.is_child_user === true;
                    
                    // Always use total_price from the product
                    let productPrice = parseFloat(assignedProduct.total_price || 0);
                    
                    // Apply Swedish rounding for parent users
                    if (!isChildUser) {
                        productPrice = swedishRound(productPrice);
                    }
                    
                    const product = {
                        id: assignedProduct.product_id,
                        name: assignedProduct.name,
                        price: productPrice,
                        total_price: productPrice,
                        sku: assignedProduct.sku || 'N/A',
                        image: assignedProduct.image,
                        quantity: 1
                    };                
                    // Set global selected products
                    window.selectedProducts = [product];
                    
                    // Explicitly set the total amount
                    $('#total_amount').val(productPrice.toFixed(2));
                    
                    // Populate the selected products list
                    if (typeof window.updateSelectedProductsList === 'function') {
                        window.updateSelectedProductsList();
                    }
                    
                    // Update product selection visuals
                    if (typeof updateChildSelectionVisuals === 'function') {
                        updateChildSelectionVisuals();
                    }
                    
                    // Optional: Highlight the corresponding product card
                    if ($('#child-products-list').length) {
                        const productCard = $(`#child-products-list .cs-product-card[data-id="${product.id}"]`);
                        productCard.addClass('selected');
                    }
                    
                    // Update Sales Material tab for the assigned product
                    updateSalesMaterialTab(product.id);
                }
            }
        });
    }

    // Update child selection visuals
    function updateChildSelectionVisuals() {
        // Clear all indicators first
        $('#child-products-list .cs-product-card').removeClass('selected');
        $('#child-products-list .cs-selected-checkmark, #child-products-list .cs-selected-text').remove();

        // Find the selected product and update it
        if (typeof window.selectedProducts !== 'undefined' && window.selectedProducts.length > 0) {
            const selectedId = window.selectedProducts[0].id;
            const selectedCard = $(`#child-products-list .cs-product-card[data-id="${selectedId}"]`);

            if (selectedCard.length > 0) {
                selectedCard.addClass('selected');
                selectedCard.find('.cs-product-sku').append('<span class="cs-selected-checkmark">✓</span>');
                selectedCard.find('.cs-product-price').append('<span class="cs-selected-text"> - Selected</span>');
            }
        }
    }
    
    function initSaleForm() {
        const isChildUser = $('#child-products-list').length > 0 || 
            (typeof CS_Child_Manager !== 'undefined' && 
             CS_Child_Manager.is_child_user === true) ||
            $('body').hasClass('child-user');
        
        $('#cs-order-form').off('submit').on('submit', function(e) {
            console.log('🔵 SUBMIT HANDLER #4 (CHILD SCRIPTS) EXECUTING');
            
            // Mark this as a child submission to prevent duplicate handling
            $(this).data('child-submission', true);

            // ✅ PRIORITY: Use orderProducts if it exists and has products with quantity
            // Otherwise fall back to selectedProducts
            let productsToSubmit;
            if (window.orderProducts && window.orderProducts.length > 0) {
                productsToSubmit = window.orderProducts;
                console.log('📦 Products source: orderProducts');
            } else {
                productsToSubmit = window.selectedProducts || [];
                console.log('📦 Products source: selectedProducts (fallback)');
            }
            
            console.log('📦 Products array BEFORE quantity check:', JSON.parse(JSON.stringify(productsToSubmit)));

            // For child users, ensure the assigned product is selected
            if (isChildUser) {          
                // If no products are selected, attempt to use the first child product
                if (!productsToSubmit || productsToSubmit.length === 0) {             
                    const firstProductCard = $('#child-products-list .cs-product-card').first();
                    
                    if (firstProductCard.length) {
                        const productId = firstProductCard.data('id');
                        const productName = firstProductCard.data('name');
                        const productTotalPrice = parseFloat(firstProductCard.data('total-price'));
                        const productSku = firstProductCard.data('sku');
                        
                        const product = {
                            id: productId,
                            name: productName,
                            price: productTotalPrice,
                            total_price: productTotalPrice,
                            sku: productSku || 'N/A',
                            image: firstProductCard.find('.cs-product-image img').attr('src') || ''
                        };              
                        productsToSubmit = [product];
                        window.selectedProducts = [product];                 
                        // Update the selected products list
                        if (typeof window.updateSelectedProductsList === 'function') {
                            window.updateSelectedProductsList();
                        }
                    } else {
                        console.error("No product card found in child-products-list");
                    }
                }
            }

            // Validate products are selected
            if (!productsToSubmit || productsToSubmit.length === 0) {
                alert('Please select at least one product');
                e.preventDefault();
                return false;
            }

            // ✅ Ensure all products have a valid quantity (use existing or default to 1)
            productsToSubmit = productsToSubmit.map(p => {
                const existingQty = p.quantity;
                const parsedQty = parseInt(existingQty);
                const finalQty = (!isNaN(parsedQty) && parsedQty > 0) ? parsedQty : 1;
                
                console.log(`  Product ${p.id}: existingQty=${existingQty}, parsedQty=${parsedQty}, finalQty=${finalQty}`);
                
                return {
                    ...p,
                    quantity: finalQty
                };
            });

            console.log('📊 Products with quantities AFTER mapping:', JSON.parse(JSON.stringify(productsToSubmit)));

            // Calculate totals from selectedProducts
            const totalAmount = productsToSubmit.reduce((sum, p) => {
                const price = parseFloat(p.total_price || p.price) || 0;
                const quantity = parseInt(p.quantity) || 1;
                return sum + (price * quantity);
            }, 0);
            
            // Check pricing mode - if custom, use the manual total_amount value
            const pricingMode = $('.cs-pricing-card-active').data('pricing-mode') || 'rrp';
            let customerPays;
            
            if (pricingMode === 'custom') {
                // Use the custom price entered by user
                customerPays = parseFloat($('#total_amount').val()) || 0;
            } else {
                // Calculate from RRP (default)
                customerPays = productsToSubmit.reduce((sum, p) => {
                    const rrp = parseFloat(p.rrp) || 0;
                    const quantity = parseInt(p.quantity) || 1;
                    return sum + (rrp * quantity);
                }, 0);
            }

            console.log('💰 CHILD HANDLER CALCULATION:');
            console.log('  totalAmount (NI betalar):', totalAmount);
            console.log('  customerPays (Kunden betalar):', customerPays);
            console.log('  Expected profit:', customerPays - totalAmount);
            console.log('  Products to submit:', productsToSubmit);
            console.log('  Window selected products:', window.selectedProducts);

            // Existing form submission logic
            const formData = {
                action: 'cs_add_sale',
                nonce: csAjax.nonce,
                customer_name: $('#customer_name').val(),
                email: $('#email').val(),
                phone: $('#phone').val(),
                address: $('#address').val(),
                total_amount: totalAmount.toFixed(2),
                customer_pays: customerPays.toFixed(2),
                sale_date: $('#sale_date').val(),
                notes: $('#notes').val(),
                products: JSON.stringify(productsToSubmit)
            };

            $.ajax({
                url: csAjax.ajaxurl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('.cs-submit-btn').text('Processing...').prop('disabled', true);
                },
                success: function(response) {          
                    console.log('✅ BACKEND RESPONSE (Child Handler):');
                    console.log('  Success:', response.success);
                    console.log('  Full response:', response);
                    
                    if (response.success) {
                        // Trigger stats update
                        triggerStatsUpdate();
                        
                        alert('Sale added successfully!');
                        
                        // Clear only customer-specific fields, keep products for quick multi-order entry
                        $('#customer_name').val('');
                        $('#phone').val('');
                        $('#email').val('');
                        $('#address').val('');
                        $('#notes').val('');
                        
                        // Keep products intact for rapid next order
                        // window.selectedProducts = [];
                        // window.orderProducts = [];
                        
                        // No need to update display, products remain visible
                        // if (typeof window.updateSelectedProductsList === 'function') {
                        //     window.updateSelectedProductsList();
                        // }
                        
                        // Refresh current sales
                        if (typeof getCurrentSalesStats === 'function') {
                            getCurrentSalesStats();
                        }
                    } else {
                        alert('Error: ' + (response.data || 'Could not add sale'));
                    }
                    
                    // Always reset button state
                    $('.cs-submit-btn').text('Add Sale').prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error("Sale submission error:", status, error);
                    
                    alert('Error: Could not add sale. Please try again.');
                    
                    // Always reset button state
                    $('.cs-submit-btn').text('Add Sale').prop('disabled', false);
                }
            });

            e.preventDefault();
            return false;
        });
    }
    
    function enhanceSalesMaterialPricing() {
        $('.cs-product-card, .cs-sales-material-container').each(function() {
            const $priceElement = $(this).find('.cs-product-price');
            const originalPrice = parseFloat($priceElement.text());
            
            // Apply Swedish rounding for parent users
            if (typeof CS_Child_Manager === 'undefined' || !CS_Child_Manager.is_child_user) {
                const roundedPrice = swedishRound(originalPrice);
                $priceElement.text(`${roundedPrice} ${csAjax.currency || 'SEK'}`);
            }
        });
    }

    // Document ready function
    $(document).ready(function() {
        autoSelectAssignedProductForChild();
        initSaleForm();
        // Initialize the child form 
        initChildUserForm();
        
        // Initialize sales material tab
        initSalesMaterialTab();
        
        // Initialize child select dropdown
        initChildSelect();
        
        // Define updateSelectedProductsList if needed
        defineUpdateSelectedProductsList();
        
        // Make child products selectable
        makeChildProductsSelectable();
        
        // Enhance main product selection
        enhanceMainProductSelection();
        
        enhanceSalesMaterialPricing();
        
        // Attach event listeners to child action buttons
        attachChildActions();
        
        $(document).ajaxComplete(function() {
            makeChildProductsSelectable();
            enhanceSalesMaterialPricing();
        });
        
        // Handle tab switching for the child user
        $('.cs-tab-item').on('click', function() {
            const tabId = $(this).data('tab');

            if (tabId === 'assigned-products') {
                // After the assigned products tab is loaded, filter to show only the latest product
                setTimeout(function() {
                    // Get all product cards
                    const productCards = $('#child-products-list .cs-product-card');

                    // If more than one product is shown, hide all but the first one (which should be the latest assigned)
                    if (productCards.length > 1) {
                        // Get the latest product from localStorage if available
                        const savedProduct = loadSelectedProductFromLocalStorage();

                        if (savedProduct) {
                            // Hide all products first
                            productCards.hide();

                            // Show only the product that matches the saved product ID
                            $(`.cs-product-card[data-id="${savedProduct.id}"]`).show();
                            
                            // Update Sales Material tab for the saved product
                            updateSalesMaterialTab(savedProduct.id);
                        } else {
                            // If no saved product, just show the first one (usually the latest)
                            $(productCards.get(0)).show();
                            $(productCards.slice(1)).hide();
                            
                            // Update Sales Material tab for the first product
                            const firstProductId = $(productCards.get(0)).data('id');
                            if (firstProductId) {
                                updateSalesMaterialTab(firstProductId);
                            }
                        }
                    } else if (productCards.length === 1) {
                        // If only one product, update Sales Material tab for it
                        const productId = productCards.data('id');
                        if (productId) {
                            updateSalesMaterialTab(productId);
                        }
                    }
                }, 300);
            } else if (tabId === 'add-order') {
                // If switching to Add Order tab, auto-select the child's assigned product
                if (isChildUser) {
                    setTimeout(autoSelectAssignedProductForChild, 500);
                }
            } else if (tabId === 'sales-material') {
                // When switching to Sales Material tab, check if we have a selected product
                setTimeout(function() {
                    const savedProduct = loadSelectedProductFromLocalStorage();
                    if (savedProduct && savedProduct.id) {
                        updateSalesMaterialTab(savedProduct.id);
                    } else {
                        // Try to get the first assigned product for child users
                        if (isChildUser) {
                            const firstProductCard = $('#child-products-list .cs-product-card').first();
                            if (firstProductCard.length) {
                                const productId = firstProductCard.data('id');
                                if (productId) {
                                    updateSalesMaterialTab(productId);
                                }
                            }
                        }
                    }
                }, 300);
            }
        });
        
        // Auto-select product on page load if Add Order tab is active and user is a child
        if (isChildUser && $('#tab-add-order').hasClass('active')) {
            setTimeout(autoSelectAssignedProductForChild, 500);
        }
    });
	
/* ====================================
   TEAM/CLASS MANAGEMENT JAVASCRIPT
   ==================================== */

jQuery(document).ready(function($) {
    'use strict';
    
    // ============================================
    // TEAM MANAGEMENT MODULE
    // ============================================
    
    const TeamManager = {
        isEditing: false,
        currentTeamId: null,
        
        init: function() {
            console.log('🟢 TeamManager initializing...');
            this.bindEvents();
            this.closeDropdownsOnClickOutside();
            this.injectNotificationStyles();
            console.log('✅ TeamManager initialized successfully');
        },
        
        bindEvents: function() {
            console.log('🔵 Binding TeamManager events...');
            // Open add team modal
            $(document).on('click', '#cs-open-add-team-modal', this.openAddModal.bind(this));
            
            // Close modal
            $(document).on('click', '#cs-close-add-team-modal, #cs-cancel-add-team', this.closeModal.bind(this));
            $(document).on('click', '.cs-modal-overlay', this.closeModal.bind(this));
            
            // Submit team form (handles both add and edit)
            $(document).on('submit', '#cs-add-team-form', this.submitTeam.bind(this));
            
            // Settings button - toggle dropdown
            $(document).on('click', '.cs-team-settings-btn', this.toggleDropdown.bind(this));
            
            // Edit team
            $(document).on('click', '.cs-edit-team-btn', this.openEditModal.bind(this));
            
            // Delete team
            $(document).on('click', '.cs-delete-team-btn', this.deleteTeam.bind(this));
            
            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    TeamManager.closeModal();
                }
            });
            console.log('✅ TeamManager events bound');
        },
        
        openAddModal: function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            console.log('🟡 Opening add team modal');
            
            // Reset form for adding
            this.isEditing = false;
            this.currentTeamId = null;
            $('#team_id').val('');
            $('#cs-add-team-form')[0].reset();
            
            // Select first color by default
            $('input[name="team_color"]').first().prop('checked', true);
            
            // Update modal title
            $('#cs-modal-title').text('Skapa Nytt Lag/Klass');
            $('#cs-modal-team-count').text('Lägg till ett nytt lag eller klass');
            $('#cs-submit-team-btn').text('Skapa Lag/Klass');
            
            // Show modal
            $('#cs-add-team-modal').addClass('active').fadeIn(300);
            $('body').css('overflow', 'hidden');
            
            console.log('✅ Modal shown');
        },
        
        openEditModal: function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            console.log('🟠 Edit team button clicked');
            console.log('Opening edit team modal');
            
            // Close dropdown
            $('.cs-team-dropdown').removeClass('show');
            
            // Get team data from button attributes
            const $btn = $(e.currentTarget);
            const teamId = $btn.data('team-id');
            const teamName = $btn.data('team-name');
            const teamSwish = $btn.data('team-swish');
            const teamColor = $btn.data('team-color');
            
            console.log('Editing team:', { teamId, teamName, teamSwish, teamColor });
            
            // Set editing mode
            this.isEditing = true;
            this.currentTeamId = teamId;
            
            // Fill form with team data
            $('#team_id').val(teamId);
            $('#team_name').val(teamName);
            $('#team_swish').val(teamSwish);
            
            // Select the correct color
            $('input[name="team_color"][value="' + teamColor + '"]').prop('checked', true);
            
            // Update modal title
            $('#cs-modal-title').text('Redigera Lag/Klass');
            $('#cs-modal-team-count').text('Uppdatera lag- eller klassuppgifter');
            $('#cs-submit-team-btn').text('Uppdatera Lag/Klass');
            
            // Show modal
            $('#cs-add-team-modal').addClass('active').fadeIn(300);
            $('body').css('overflow', 'hidden');
        },
        
        closeModal: function(e) {
            if (e) e.preventDefault();
            
            console.log('Closing modal');
            
            $('#cs-add-team-modal').removeClass('active').fadeOut(300);
            $('body').css('overflow', 'auto');
            $('#cs-add-team-form')[0].reset();
            this.isEditing = false;
            this.currentTeamId = null;
        },
        
submitTeam: function(e) {
    e.preventDefault();
    e.stopPropagation(); // Add this to prevent form from bubbling
    
    const isEditing = this.isEditing;
    const teamId = this.currentTeamId;
    
    console.log('Submitting team form:', { isEditing, teamId });
    
    const $form = $('#cs-add-team-form');
    const $submitBtn = $('#cs-submit-team-btn');
    const originalText = $submitBtn.text();
    
    // Disable submit button
    $submitBtn.prop('disabled', true).text(isEditing ? 'Uppdaterar...' : 'Skapar...');
    
    // Get form data
    const formData = {
        action: isEditing ? 'cs_update_team' : 'cs_add_team',
        nonce: $('#cs_team_nonce').val(),
        team_name: $('#team_name').val().trim(),
        team_swish: $('#team_swish').val().trim(),
        team_color: $('input[name="team_color"]:checked').val()
    };
    
    // Add team ID if editing
    if (isEditing) {
        formData.team_id = teamId;
    }
    
    console.log('Form data:', formData);
    
    // Validate
    if (!formData.team_name) {
        alert('Vänligen ange ett lagnamn');
        $submitBtn.prop('disabled', false).text(originalText);
        return;
    }
    
    // Submit via AJAX
    $.ajax({
        url: csChildAjax.ajaxurl,
        type: 'POST',
        data: formData,
        success: function(response) {
            console.log('Team save response:', response);
            
            if (response.success) {
                // Show success notification
                TeamManager.showNotification(
                    isEditing ? 'Laget har uppdaterats!' : 'Laget har skapats!',
                    'success'
                );
                
                // Close modal
                TeamManager.closeModal();
                
                // Update UI without page reload
                if (isEditing) {
                    TeamManager.updateTeamCard(teamId, response.data.team);
                } else {
                    TeamManager.addTeamCard(response.data.team);
                }
                
                // Update team dropdown in add seller form
                TeamManager.updateTeamDropdown(response.data.team, isEditing);
                
                // Update team filter dropdown
                if (typeof window.updateTeamFilterDropdown === 'function') {
                    window.updateTeamFilterDropdown();
                }
                
                // Refresh orders list to show updated team information
                if (typeof window.loadOrders === 'function') {
                    window.loadOrders();
                }
                
                // Trigger stats update
                if (typeof window.triggerStatsUpdate === 'function') {
                    window.triggerStatsUpdate();
                }
                
                // IMPORTANT: Re-enable the button after successful submission
                $submitBtn.prop('disabled', false).text(originalText);
            } else {
                console.error('Team save failed:', response);
                alert(response.data.message || 'Ett fel uppstod vid sparandet');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', { xhr, status, error });
            console.error('Response text:', xhr.responseText);
            alert('Ett fel uppstod vid sparandet. Kontrollera konsolen för detaljer.');
            $submitBtn.prop('disabled', false).text(originalText);
        }
    });
    
    return false; // Prevent default form submission
},
        
deleteTeam: function(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    console.log('🔴 Delete team button clicked');
    
    // Close dropdown
    $('.cs-team-dropdown').removeClass('show');
    
    const teamId = $(e.currentTarget).data('team-id');
    const $teamCard = $('.cs-team-card[data-team-id="' + teamId + '"]');
    const teamName = $teamCard.find('h3').text();
    
    console.log('Delete team clicked:', teamId);
    
    // Confirm deletion
    if (!confirm('Är du säker på att du vill radera "' + teamName + '"? Detta kan inte ångras.')) {
        return;
    }
    
    // Show loading state
    $teamCard.css('opacity', '0.5');
    
    // Use csChildAjax.nonce which is the registered nonce
    const nonce = csChildAjax.nonce;
    
    console.log('Using nonce:', nonce);
    
    // Delete via AJAX
    $.ajax({
        url: csChildAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'cs_delete_team',
            nonce: nonce,
            team_id: teamId
        },
        beforeSend: function(xhr, settings) {
            console.log('Sending delete request:', settings.data);
        },
        success: function(response) {
            console.log('Delete team response:', response);
            
            if (response.success) {
                // Animate removal
                $teamCard.fadeOut(400, function() {
                    $(this).remove();
                    
                    // Check if there are any teams left
                    if ($('.cs-team-card').length === 0) {
                        // Show empty state
                        TeamManager.showEmptyState();
                    } else {
                        // Update team count
                        const currentCount = $('.cs-team-card').length;
                        $('.cs-teams-header-info p').text(
                            currentCount + (currentCount === 1 ? ' lag/klass skapad' : ' lag/klasser skapade')
                        );
                    }
                });
                
                // Remove from team dropdown in add seller form
                $('#child_team option[value="' + teamId + '"]').remove();
                
                // Update team filter dropdown
                if (typeof window.updateTeamFilterDropdown === 'function') {
                    window.updateTeamFilterDropdown();
                }
                
                TeamManager.showNotification('Laget har raderats', 'success');
            } else {
                console.error('Delete failed:', response);
                $teamCard.css('opacity', '1');
                alert('Fel: ' + (response.data.message || 'Ett fel uppstod vid raderingen'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', { xhr, status, error });
            console.error('Response text:', xhr.responseText);
            console.error('Status:', xhr.status);
            $teamCard.css('opacity', '1');
            
            if (xhr.status === 403) {
                alert('Säkerhetsfel: Sessionen kan ha gått ut. Vänligen ladda om sidan och försök igen.');
            } else {
                alert('AJAX Fel: Ett fel uppstod vid raderingen.');
            }
        }
    });
},
        
        toggleDropdown: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🟣 Settings button clicked');
            
            const teamId = $(e.currentTarget).data('team-id');
            const $dropdown = $('.cs-team-dropdown[data-team-id="' + teamId + '"]');
            
            console.log('Toggling dropdown for team:', teamId, 'Dropdown found:', $dropdown.length);
            
            // Close all other dropdowns
            $('.cs-team-dropdown').not($dropdown).removeClass('show');
            
            // Toggle current dropdown
            $dropdown.toggleClass('show');
            
            console.log('Dropdown is now:', $dropdown.hasClass('show') ? 'visible' : 'hidden');
        },
        
        closeDropdownsOnClickOutside: function() {
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.cs-team-settings').length) {
                    $('.cs-team-dropdown').removeClass('show');
                }
            });
        },
        
        showNotification: function(message, type) {
            type = type || 'success';
            
            console.log('Showing notification:', message, type);
            
            // Remove existing notifications
            $('.cs-notification').remove();
            
            const $notification = $('<div class="cs-notification cs-notification-' + type + '"></div>');
            
            let icon = type === 'success' ? 
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>' :
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
            
            $notification.html(icon + '<span>' + message + '</span>');
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);
            
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        },
        
        injectNotificationStyles: function() {
            // Add notification styles dynamically if not already present
            if (!$('#cs-notification-styles').length) {
                $('head').append(`
                    <style id="cs-notification-styles">
                        .cs-notification {
                            position: fixed;
                            top: 20px;
                            right: 20px;
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            padding: 15px 20px;
                            background: white;
                            border-radius: 8px;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                            font-size: 14px;
                            font-weight: 600;
                            z-index: 10001;
                            transform: translateX(400px);
                            opacity: 0;
                            transition: all 0.3s ease;
                            min-width: 300px;
                            max-width: 500px;
                        }
                        
                        .cs-notification.show {
                            transform: translateX(0);
                            opacity: 1;
                        }
                        
                        .cs-notification svg {
                            flex-shrink: 0;
                        }
                        
                        .cs-notification span {
                            flex: 1;
                        }
                        
                        .cs-notification-success {
                            background-color: #4CAF50;
                            color: white;
                        }
                        
                        .cs-notification-success svg {
                            stroke: white;
                        }
                        
                        .cs-notification-error {
                            background-color: #f44336;
                            color: white;
                        }
                        
                        .cs-notification-error svg {
                            stroke: white;
                        }
                        
                        @media (max-width: 768px) {
                            .cs-notification {
                                top: 10px;
                                right: 10px;
                                left: 10px;
                                min-width: unset;
                                max-width: unset;
                                transform: translateY(-100px);
                            }
                            
                            .cs-notification.show {
                                transform: translateY(0);
                            }
                        }
                    </style>
                `);
            }
        },
        
        updateTeamCard: function(teamId, teamData) {
            const $teamCard = $('.cs-team-card[data-team-id="' + teamId + '"]');
            
            if ($teamCard.length) {
                // Update team name
                $teamCard.find('h3').text(teamData.name);
                
                // Update swish
                $teamCard.find('.cs-team-swish').text('Swish: ' + teamData.swish_number);
                
                // Update color
                $teamCard.css('border', '2px solid ' + teamData.color);
                $teamCard.find('.cs-teams-card-icon').css('background-color', teamData.color);
                
                // Update data attributes
                $teamCard.find('.cs-edit-team-btn')
                    .data('team-name', teamData.name)
                    .data('team-swish', teamData.swish_number)
                    .data('team-color', teamData.color);
            }
        },
        
        addTeamCard: function(teamData) {
            let $teamsGrid = $('#cs-teams-grid');
            
            // Remove empty state if it exists
            $('.cs-teams-empty-state').remove();
            
            // Create teams grid if it doesn't exist
            if ($teamsGrid.length === 0) {
                $('.cs-teams-header').after('<div class="cs-teams-grid" id="cs-teams-grid"></div>');
                $teamsGrid = $('#cs-teams-grid');
            }
            
            const teamCard = `
                <div class="cs-team-card" style="border: 2px solid ${teamData.color}" data-team-id="${teamData.id}">
                    <div class="cs-teams-card-icon" style="background-color: ${teamData.color}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                            <line x1="4" y1="22" x2="4" y2="15"></line>
                        </svg>
                    </div>
                    <div class="cs-team-card-content">
                        <h3>${teamData.name}</h3>
                        <p class="cs-team-sellers">0 säljare</p>
                        <p class="cs-team-swish">Swish: ${teamData.swish_number}</p>
                    </div>
                    
                    <div class="cs-team-settings">
                        <button class="cs-team-settings-btn" data-team-id="${teamData.id}">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .66.39 1.26 1 1.51H21a2 2 0 0 1 0 4h-.09c-.7 0-1.31.39-1.51 1z"/>
                            </svg>
                        </button>
                        <div class="cs-team-dropdown" data-team-id="${teamData.id}">
                            <button class="cs-edit-team-btn" data-team-id="${teamData.id}" 
                                    data-team-name="${teamData.name}"
                                    data-team-swish="${teamData.swish_number}"
                                    data-team-color="${teamData.color}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Redigera
                            </button>
                            <button class="cs-delete-team-btn" data-team-id="${teamData.id}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                                Radera
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $teamsGrid.prepend(teamCard);
            
            // Update team count in header
            const currentCount = $('.cs-team-card').length;
            $('.cs-teams-header-info p').text(
                currentCount + (currentCount === 1 ? ' lag/klass skapad' : ' lag/klasser skapade')
            );
        },
        
        updateTeamDropdown: function(teamData, isEditing) {
            const $select = $('#child_team');
            
            if (isEditing) {
                // Update existing option
                $select.find('option[value="' + teamData.id + '"]').text(teamData.name);
            } else {
                // Add new option
                if ($select.find('option').length === 1 && $select.find('option').first().val() === '') {
                    // If only placeholder exists, add after it
                    $select.find('option').first().after(
                        $('<option>', {
                            value: teamData.id,
                            text: teamData.name
                        })
                    );
                } else {
                    $select.append(
                        $('<option>', {
                            value: teamData.id,
                            text: teamData.name
                        })
                    );
                }
            }
        },
        
        showEmptyState: function() {
            const emptyState = `
                <div class="cs-teams-empty-state">
                    <div class="cs-empty-section-content">
                        <h3>Redo att sätta igång?</h3>
                        <p>Skapa ditt första lag/klass för att komma igång!</p>
                    </div>
                </div>
            `;
            
            $('#cs-teams-grid').replaceWith(emptyState);
            
            // Update header count
            $('.cs-teams-header-info p').text('Inga lag/klasser skapade ännu');
        }
    };
    
    // Initialize Team Manager
    TeamManager.init();
    
    // Initialize Team Filter for Sellers Table
    initTeamFilter();
});

// ============================================
// TEAM FILTER FUNCTIONALITY
// ============================================
function initTeamFilter() {
    const $teamFilter = $('#cs-team-filter');
    
    if (!$teamFilter.length) {
        console.log('Team filter not found on this page');
        return;
    }
    
    // Handle filter change
    $teamFilter.on('change', function() {
        const selectedTeamId = $(this).val();
        console.log('Team filter changed to:', selectedTeamId);
        
        filterSellersByTeam(selectedTeamId);
    });
    
    // Inject filter styles
    injectTeamFilterStyles();
}

// Filter sellers table by team
function filterSellersByTeam(teamId) {
    const $rows = $('#children-list tr');
    const $countText = $('#cs-sellers-count-text');
    
    let visibleCount = 0;
    let totalCount = $rows.not('[colspan]').length;
    
    // If no team selected, show all
    if (!teamId || teamId === '') {
        $rows.show();
        $rows.removeClass('cs-filter-hidden');
        visibleCount = totalCount;
    } else {
        $rows.each(function() {
            const $row = $(this);
            const rowTeamId = $row.data('team-id');
            
            // Skip the "no data" row
            if ($row.find('td[colspan]').length) {
                return;
            }
            
            // Compare as strings to handle different types
            if (String(rowTeamId) === String(teamId)) {
                $row.show().removeClass('cs-filter-hidden');
                visibleCount++;
            } else {
                $row.hide().addClass('cs-filter-hidden');
            }
        });
    }
    
    // Update the count text
    if ($countText.length) {
        const filterText = teamId ? ' (filtrerat)' : '';
        $countText.text('Visar ' + visibleCount + ' av ' + totalCount + ' säljare' + filterText);
    }
    
    // Show "no results" message if all rows are hidden
    const $noDataRow = $('#children-list tr.cs-no-filter-results');
    if (visibleCount === 0 && totalCount > 0) {
        if (!$noDataRow.length) {
            $('#children-list').append(
                '<tr class="cs-no-filter-results"><td colspan="5" class="cs-no-data">Inga säljare i detta lag/klass.</td></tr>'
            );
        }
    } else {
        $noDataRow.remove();
    }
    
    console.log('Filter applied: showing', visibleCount, 'of', totalCount, 'sellers');
}

// Update team filter dropdown after team add/edit/delete
function updateTeamFilterDropdown() {
    const $teamFilter = $('#cs-team-filter');
    const $childTeamSelect = $('#child_team');
    
    if (!$teamFilter.length && !$childTeamSelect.length) {
        return;
    }
    
    console.log('Updating team filter dropdown via AJAX');
    
    $.ajax({
        url: csChildAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'cs_get_teams',
            nonce: csChildAjax.nonce
        },
        success: function(response) {
            console.log('Get teams response:', response);
            
            if (response.success && response.data.teams) {
                const teams = response.data.teams;
                const currentFilterValue = $teamFilter.val();
                const currentChildTeamValue = $childTeamSelect.val();
                
                // Update filter dropdown
                if ($teamFilter.length) {
                    $teamFilter.find('option:not(:first)').remove();
                    
                    teams.forEach(function(team) {
                        $teamFilter.append(
                            $('<option>', {
                                value: team.id,
                                text: team.name,
                                'data-color': team.color
                            })
                        );
                    });
                    
                    // Restore previous selection if still exists
                    if (currentFilterValue && $teamFilter.find('option[value="' + currentFilterValue + '"]').length) {
                        $teamFilter.val(currentFilterValue);
                    }
                }
                
                // Update child team dropdown in add seller form
                if ($childTeamSelect.length) {
                    $childTeamSelect.find('option:not(:first)').remove();
                    
                    teams.forEach(function(team) {
                        $childTeamSelect.append(
                            $('<option>', {
                                value: team.id,
                                text: team.name
                            })
                        );
                    });
                    
                    // Restore previous selection if still exists
                    if (currentChildTeamValue && $childTeamSelect.find('option[value="' + currentChildTeamValue + '"]').length) {
                        $childTeamSelect.val(currentChildTeamValue);
                    }
                }
                
                console.log('Team dropdowns updated with', teams.length, 'teams');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching teams:', error);
        }
    });
}

// Inject CSS styles for the team filter
function injectTeamFilterStyles() {
    if ($('#cs-team-filter-styles').length) {
        return;
    }
    
    $('head').append(`
        <style id="cs-team-filter-styles">
            /* Team Filter Container */
            .cs-section-header-with-filter {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .cs-section-header-left {
                display: flex;
                align-items: flex-start;
                gap: 15px;
            }
            
            .cs-team-filter-container {
                display: flex;
                align-items: center;
                gap: 10px;
                background: #f8f9fa;
                padding: 8px 15px;
                border-radius: 8px;
                border: 1px solid #e0e0e0;
            }
            
            .cs-filter-label {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 13px;
                font-weight: 500;
                color: #666;
                white-space: nowrap;
            }
            
            .cs-filter-label svg {
                color: #4CAF50;
            }
            
            .cs-team-filter-select {
                padding: 8px 35px 8px 12px;
                font-size: 14px;
                border: 1px solid #ddd;
                border-radius: 6px;
                background-color: white;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 12px center;
                background-size: 12px;
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                cursor: pointer;
                min-width: 180px;
                color: #333;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            
            .cs-team-filter-select:hover {
                border-color: #4CAF50;
            }
            
            .cs-team-filter-select:focus {
                outline: none;
                border-color: #4CAF50;
                box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            }
            
            /* Row filtering animation */
            #children-list tr {
                transition: opacity 0.2s ease, background-color 0.2s ease;
            }
            
            #children-list tr.cs-filter-hidden {
                display: none;
            }
            
            /* No results message styling */
            .cs-no-filter-results td {
                text-align: center;
                padding: 30px 20px !important;
                color: #666;
                font-style: italic;
            }
            
            /* Responsive styles */
            @media (max-width: 768px) {
                .cs-section-header-with-filter {
                    flex-direction: column;
                }
                
                .cs-team-filter-container {
                    width: 100%;
                    justify-content: space-between;
                }
                
                .cs-team-filter-select {
                    flex: 1;
                    min-width: unset;
                }
            }
        </style>
    `);
}

// Make updateTeamFilterDropdown globally accessible
window.updateTeamFilterDropdown = updateTeamFilterDropdown;

// =====================================================
// EDIT SELLER FUNCTIONALITY
// =====================================================

// Open edit seller modal
$('#cs-open-edit-child-modal, .cs-modal-close#cs-close-edit-child-modal, #cs-cancel-edit-child').on('click', function(e) {
    e.preventDefault();
    if ($(this).is('#cs-close-edit-child-modal') || $(this).is('#cs-cancel-edit-child')) {
        $('#cs-edit-child-modal').fadeOut(300);
    }
});

// Close modal when clicking overlay
$('#cs-edit-child-modal .cs-modal-overlay').on('click', function() {
    $('#cs-edit-child-modal').fadeOut(300);
});

// Handle edit seller form submission
$('#cs-edit-child-form').on('submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $submitBtn = $form.find('button[type="submit"]');
    const originalText = $submitBtn.text();
    
    const childId = $('#edit_child_id').val();
    const childName = $('#edit_child_name').val();
    const childEmail = $('#edit_child_email').val();
    const childTeam = $('#edit_child_team').val();
    
    console.log('Editing seller:', { childId, childName, childEmail, childTeam });
    
    if (!childId || !childName || !childEmail) {
        alert('Vänligen fyll i alla obligatoriska fält');
        return false;
    }
    
    // Disable submit button
    $submitBtn.prop('disabled', true).text('Sparar...');
    
    $.ajax({
        url: csChildAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'cs_edit_child',
            nonce: $('#cs_edit_child_nonce').val(),
            child_id: childId,
            child_name: childName,
            child_email: childEmail,
            child_team: childTeam
        },
        success: function(response) {
            console.log('Edit child response:', response);
            
            if (response.success) {
                // Close modal
                $('#cs-edit-child-modal').fadeOut(300);
                
                // Update the row in the table
                const $row = $('tr[data-child-id="' + childId + '"]');
                if ($row.length) {
                    // Update name
                    $row.find('td:nth-child(1)').text(childName);
                    
                    // Update email
                    $row.find('td:nth-child(2)').text(childEmail);
                    
                    // Update team badge
                    if (childTeam) {
                        const $teamOption = $('#edit_child_team option[value="' + childTeam + '"]');
                        const teamName = $teamOption.text();
                        
                        // Get team color
                        $.ajax({
                            url: csChildAjax.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'cs_get_team_color',
                                nonce: csChildAjax.nonce,
                                team_id: childTeam
                            },
                            success: function(colorResponse) {
                                if (colorResponse.success) {
                                    const teamColor = colorResponse.data.color;
                                    $row.find('td:nth-child(3)').html(`
                                        <div class="cs-team-badge">
                                            <span class="cs-team-color-dot" style="background-color: ${teamColor}"></span>
                                            ${teamName}
                                        </div>
                                    `);
                                }
                            }
                        });
                    } else {
                        $row.find('td:nth-child(3)').html(`
                            <div class="cs-team-badge">
                                <span class="cs-team-color-dot" style="background-color: #cccccc"></span>
                                Inget lag
                            </div>
                        `);
                    }
                    
                    // Update data attributes
                    $row.attr('data-team-id', childTeam || '');
                    $row.find('.cs-edit-child').attr('data-child-name', childName);
                    $row.find('.cs-edit-child').attr('data-child-email', childEmail);
                    $row.find('.cs-edit-child').attr('data-child-team', childTeam || '');
                }
                
                // Show success notification
                TeamManagement.showNotification('Säljare uppdaterad!', 'success');
                
                // Reset form
                $form[0].reset();
            } else {
                alert('Fel: ' + (response.data || 'Kunde inte uppdatera säljare'));
            }
            
            // Re-enable button
            $submitBtn.prop('disabled', false).text(originalText);
        },
        error: function(xhr, status, error) {
            console.error('Edit child error:', error);
            alert('Ett fel uppstod. Försök igen.');
            $submitBtn.prop('disabled', false).text(originalText);
        }
    });
    
    return false;
});

})(jQuery);