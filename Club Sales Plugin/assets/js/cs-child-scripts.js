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

            const formData = {
                action: 'cciu_add_child',
                nonce: $('#cciu_nonce').val(),
                email: $('#child_email').val(),
                first_name: $('#child_first_name').val(),
                last_name: $('#child_last_name').val(),
                password: $('#child_password').val()
            };

            // Validate the form
            if (!formData.email || !formData.first_name || !formData.last_name || !formData.password) {
                alert('Please fill in all required fields.');
                return;
            }

            $.ajax({
                url: csChildAjax.ajaxurl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    // Disable the submit button and show loading state
                    $('#cs-add-child-form button[type="submit"]').prop('disabled', true).text('Adding...');
                },
                success: function(response) {
                    if (response.success) {
                        // Clear form and show success message
                        $('#cs-add-child-form')[0].reset();
                        alert(response.data.message);

                        // Add the new child to the table
                        if (response.data.child) {
                            const child = response.data.child;
                            const newRow = `
<tr data-id="${child.id}">
<td>${child.name}</td>
<td>${child.email}</td>
<td>${formatDate(child.registered)}</td>
<td>
<div class="cs-actions">
<button type="button" class="cs-action cs-assign-products" data-id="${child.id}" data-name="${child.name}">Assign Products</button>
<button type="button" class="cs-action cs-remove-child" data-id="${child.id}">Remove</button>
</div>
</td>
</tr>
`;

                            // Check if the table is empty
                            if ($('#children-list tr td[colspan="4"]').length) {
                                $('#children-list').html(newRow);
                            } else {
                                $('#children-list').append(newRow);
                            }

                            // Add event listeners to the new buttons
                            attachChildActions();
                            window.location.reload();
                        }
                    } else {
                        alert(response.data || 'Error adding child user.');
                    }

                    // Re-enable the submit button
                    $('#cs-add-child-form button[type="submit"]').prop('disabled', false).text('Add Child User');
                },
                error: function() {
                    alert('Error adding child user. Please try again.');
                    $('#cs-add-child-form button[type="submit"]').prop('disabled', false).text('Add Child User');
                }
            });
        });
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
    function updateSalesMaterialTab(productId) {
        console.log('Updating Sales Material tab for product:', productId);
        
        // Check if sales material tab exists
        const salesMaterialTab = $('#tab-sales-material');
        if (!salesMaterialTab.length) {
            console.log('Sales Material tab not found');
            return;
        }
        
        // Show loading state
        salesMaterialTab.html('<div class="cs-loading">Loading sales materials...</div>');
        
        // Make AJAX request to refresh sales material content
        $.ajax({
            url: csChildAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'refresh_sales_material',
                product_id: productId,
                nonce: csChildAjax.nonce
            },
            success: function(response) {
                console.log('Sales material refresh response:', response);
                
                if (response.success && response.data.content) {
                    // Update the sales material tab content
                    salesMaterialTab.html(response.data.content);
                    console.log('Sales Material tab updated successfully');
                } else {
                    console.error('Failed to refresh sales material:', response.data);
                    salesMaterialTab.html('<div class="cs-error">Error loading sales materials</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error refreshing sales material:', status, error);
                salesMaterialTab.html('<div class="cs-error">Error loading sales materials. Please try again.</div>');
            }
        });
    }
    
    // Attach event listeners to child action buttons
    function attachChildActions() {
        // Remove child button
        $('.cs-remove-child').off('click').on('click', function() {
            const childId = $(this).data('id');
            const row = $(this).closest('tr');

            if (confirm('Are you sure you want to remove this child user?')) {
                $.ajax({
                    url: csChildAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cciu_remove_child',
                        nonce: csChildAjax.nonce,
                        child_id: childId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Remove the row from the table
                            row.fadeOut(300, function() {
                                $(this).remove();

                                // If no children left, add empty message
                                if ($('#children-list tr').length === 0) {
                                    $('#children-list').html('<tr><td colspan="4">No child users found.</td></tr>');
                                }
                            });

                            alert(response.data.message);
                        } else {
                            alert(response.data || 'Error removing child user.');
                        }
                    },
                    error: function() {
                        alert('Error removing child user. Please try again.');
                    }
                });
            }
        });

        // Assign products button
        $('.cs-assign-products').off('click').on('click', function() {
            const childId = $(this).data('id');
            const childName = $(this).data('name');

            // If we're on the dashboard, switch to the assign products tab
            if ($('.cs-tab-item[data-tab="assign-products"]').length) {
                $('.cs-tab-item[data-tab="assign-products"]').trigger('click');

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
            // Mark this as a child submission to prevent duplicate handling
            $(this).data('child-submission', true);

            // For child users, ensure the assigned product is selected
            if (isChildUser) {          
                // If no products are selected, attempt to use the first child product
                if (!window.selectedProducts || window.selectedProducts.length === 0) {             
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

            // Prevent multiple submissions
            if (!window.selectedProducts || window.selectedProducts.length === 0) {
                alert('Please select at least one product');
                e.preventDefault();
                return false;
            }

            // Existing form submission logic
            const formData = {
                action: 'cs_add_sale',
                nonce: csAjax.nonce,
                customer_name: $('#customer_name').val(),
                email: $('#email').val(),
                phone: $('#phone').val(),
                address: $('#address').val(),
                total_amount: $('#total_amount').val(),
                sale_date: $('#sale_date').val(),
                notes: $('#notes').val(),
                products: JSON.stringify(window.selectedProducts)
            };

            $.ajax({
                url: csAjax.ajaxurl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('.cs-submit-btn').text('Processing...').prop('disabled', true);
                },
                success: function(response) {          
                    if (response.success) {
                        // Trigger stats update
                        triggerStatsUpdate();
                        
                        alert('Sale added successfully!');
                        // Reset form
                        $('#cs-order-form')[0].reset();
                        window.selectedProducts = [];
                        
                        // Ensure updateSelectedProductsList is defined
                        if (typeof window.updateSelectedProductsList === 'function') {
                            window.updateSelectedProductsList();
                        }
                        
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

    // Tab activation handler
    $('.cs-tabs-nav a').on('click', function() {
        const tabId = $(this).attr('href').replace('#', '');
        
        // Trigger custom event when tab is activated
        setTimeout(function() {
            $(document).trigger('cs_tab_activated', [tabId]);
        }, 100);
    });
    
    // Handler for sales material tab activation
    $(document).on('cs_tab_activated', function(e, tabId) {
        if (tabId === 'sales-material') {
            const savedProduct = loadSelectedProductFromLocalStorage();
            if (savedProduct && savedProduct.id) {
                updateSalesMaterialTab(savedProduct.id);
            }
        }
    });

    // Global function to refresh sales material
    window.refreshSalesMaterial = function(productId, childId) {
        $('.cs-sales-materials-container').addClass('loading');
        
        $.ajax({
            url: csChildAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'refresh_sales_material',
                product_id: productId || 0,
                child_id: childId || 0,
                nonce: csChildAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data.content) {
                    // Replace the entire section content
                    $('.cs-section').replaceWith(response.data.content);
                }
            },
            error: function() {
                console.error('Failed to refresh sales material');
            },
            complete: function() {
                $('.cs-sales-materials-container').removeClass('loading');
            }
        });
    };
    
    // Add custom CSS for loading state
    $('<style>')
        .prop('type', 'text/css')
        .html('.cs-sales-materials-container.loading { opacity: 0.5; pointer-events: none; }')
        .appendTo('head');

})(jQuery);