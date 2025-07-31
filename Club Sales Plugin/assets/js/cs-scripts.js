/**
 * Club Sales Plugin - JavaScript functionality with Auto-Update Sales Material
 */

(function($) {
	'use strict';

	// Store selected products
	let selectedProducts = [];
	let totalAmount = 0;
	initQuantityChangeListener();
	
	// Auto-update functionality
	function triggerStatsUpdate() {
		// Trigger custom event for stats update
		$(document).trigger('cs_sale_added', { timestamp: Date.now() });
		
		// Also trigger global stats update if function exists
		if (typeof window.triggerStatsUpdate === 'function') {
			window.triggerStatsUpdate();
		}
		
		console.log('Stats update triggered from main scripts');
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
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'refresh_sales_material',
				product_id: productId,
				nonce: csAjax.nonce
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
	
	// Tab switching functionality
	function initTabs() {
		$('.cs-tab-item').on('click', function() {
			const tabId = $(this).data('tab');

			// Update active tab
			$('.cs-tab-item').removeClass('active');
			$(this).addClass('active');

			// Show the selected tab content
			$('.cs-tab-pane').removeClass('active');
			$('#tab-' + tabId).addClass('active');

			// Load data for the selected tab if needed
			if (tabId === 'assign-product') { // Updated from 'products' to 'assign-product'
				loadProducts();
			} else if (tabId === 'orders') {
				loadOrders();
			} else if (tabId === 'stats') {
				loadStats();
			} else if (tabId === 'assigned-products') {
				// Assigned products tab is handled by CS_Child_Shortcodes::assigned_products_tab
				// No additional JS needed as content is rendered server-side
			} else if (tabId === 'manage-children') {
				// Child management tab is handled by CS_Child_Shortcodes::manage_children_tab
				// Additional JS is in cs-child-scripts.js
			} else if (tabId === 'sales-material') {
				// When switching to Sales Material tab, check if we have a selected product
				setTimeout(function() {
					const savedProduct = localStorage.getItem('cs_selected_product');
					if (savedProduct) {
						try {
							const product = JSON.parse(savedProduct);
							if (product && product.id) {
								updateSalesMaterialTab(product.id);
							}
						} catch (e) {
							console.error('Error parsing saved product:', e);
						}
					}
				}, 300);
			}
		});
	}

	// Product search functionality
	function initProductSearch() {
		$('#search-button').on('click', function() {
			const searchTerm = $('#product-search').val().trim();
			if (searchTerm.length > 2) {
				searchProducts(searchTerm);
			}
		});

		// Also trigger search on enter key
		$('#product-search').on('keypress', function(e) {
			if (e.which === 13) {
				const searchTerm = $(this).val().trim();
				if (searchTerm.length > 2) {
					searchProducts(searchTerm);
				}
				e.preventDefault();
			}
		});
	}

	// Search for products
function searchProducts(term) {
    // Add more robust error handling and logging
    console.time('Product Search'); // Performance tracking
    
    $('#products-list').html('<div class="cs-loading">Searching products...</div>');

    const data = {
        action: 'cs_search_products',
        nonce: csAjax.nonce,
        search_term: term
    };

    $.ajax({
        url: csAjax.ajaxurl,
        type: 'POST',
        data: data,
        timeout: 10000, // 10-second timeout
        beforeSend: function(xhr) {
            console.log("Sending product search request");
        },
        success: function(response) {
            console.timeEnd('Product Search');
            
            if (response.success && response.data && response.data.data) {
                console.log(`Products found: ${response.data.data.length}`);
                displayProducts(response.data.data);
            } else {
                console.warn("No products found or invalid response", response);
                $('#products-list').html('<div class="cs-empty-selection">No products found.</div>');
            }
        },
        error: function(xhr, status, error) {
            console.timeEnd('Product Search');
            console.error("Search error:", status, error);
            
            // More informative error message
            $('#products-list').html(`
                <div class="cs-empty-selection">
                    Error searching products. 
                    ${status === 'timeout' ? 'Request timed out.' : 'Please try again.'}
                </div>
            `);
        }
    });
}

/**
 * Store selected products in localStorage and handle persistence
 */
function saveSelectedProductToLocalStorage(product) {
    // Save the selected product to localStorage for persistence
    if (product) {
        localStorage.setItem('cs_selected_product', JSON.stringify(product));
    }
}

function loadSelectedProductFromLocalStorage() {
    // Load the selected product from localStorage if available
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
	
	// Display products in the grid
function displayProducts(products) {
    
    if (!products || products.length === 0) {
        $('#products-list').html('<div class="cs-empty-selection">No products found.</div>');
        return;
    }

    // Load any previously selected product
    const savedProduct = loadSelectedProductFromLocalStorage();
    
    let html = '';
    products.forEach(function(product) {
        // Special rounding for parents (Club Leaders)
        let clubSalesPrice;
        if (typeof CS_Child_Manager === 'undefined' || !CS_Child_Manager.is_child_user) {
            // Parent user - apply Swedish rounding
            // Round to nearest 9 in the current 10s group
            clubSalesPrice = Math.floor(parseFloat(product.total_price) / 10) * 10 + 9;
        } else {
            // Child user - use regular price
            clubSalesPrice = parseFloat(product.total_price);
        }
        
        // Check if this product was previously selected
        const isSelected = savedProduct && savedProduct.id === product.id;
        const buttonClass = isSelected ? 'cs-product-card selected' : 'cs-product-card';

        // Determine the correct product URL
        const productUrl = product.permalink || `${csAjax.siteUrl}/product/${product.id}`;

       // Add this function at the top of your script or in a utility section
function truncateProductTitle(title, wordLimit = 3) {
    // Split the title into words
    const words = title.split(' ');
    
    // If the title has 3 or fewer words, return the full title
    if (words.length <= wordLimit) {
        return title;
    }
    
    // Take first 3 words and add ellipsis
    return words.slice(0, wordLimit).join(' ') + '...';
}

// Then modify the product name rendering
html += `
<div class="${buttonClass}" 
     data-id="${product.id}" 
     data-name="${product.name}" 
     data-price="${clubSalesPrice}" 
     data-sku="${product.sku}">
    <div class="cs-product-image">
        ${product.image ? `<img src="${product.image}" alt="${product.name}">` : '<span class="dashicons dashicons-format-image"></span>'}
    </div>
    <div class="cs-product-info">
        <div class="cs-product-name">${truncateProductTitle(product.name)}</div>
        <div class="cs-product-sku">
            SKU: ${product.sku || 'N/A'} 
            ${isSelected ? '<span class="cs-selected-checkmark">✓</span>' : ''}
        </div>
        <div class="cs-product-price">
            ${clubSalesPrice.toFixed(2)} ${csAjax.currency}
            ${isSelected ? '<span class="cs-selected-text"> - Selected</span>' : ''}
        </div>
    </div>
    <div class="cs-product-info-actions">
        <a href="${productUrl}" class="cs-view-more-btn" data-product-id="${product.id}" data-product-url="${productUrl}">View More</a>
    </div>
</div>
`;
    });

    $('#products-list').html(html);
    // Add event listener to View More buttons
    $('.cs-view-more-btn').on('click', function(e) {
        // Prevent any parent event handlers from triggering
        e.preventDefault();
        e.stopPropagation();

        // Get the product URL directly from the data attribute
        const productUrl = $(this).data('product-url');


        // Redirect to the product page
        window.location.href = productUrl;

        // Prevent default link behavior
        return false;
    });

    // Modify product card click to ignore View More button and update Sales Material
    $('.cs-product-card').on('click', function(e) {
		// Check if the click was on the View More button
		if ($(e.target).hasClass('cs-view-more-btn') || $(e.target).closest('.cs-view-more-btn').length) {
			return false;
		}

		// Rest of the existing product selection logic
		const productId = $(this).data('id');
		
		const product = {
			id: productId,
			name: $(this).data('name'),
			price: $(this).data('price'),
			total_price: $(this).data('price'),
			sku: $(this).data('sku'),
			image: $(this).find('.cs-product-image img').length ? $(this).find('.cs-product-image img').attr('src') : null
		};

		// First remove selection from all products
		$('.cs-product-card').removeClass('selected');
		
		// Remove any existing indicators
		$('.cs-selected-checkmark, .cs-selected-text').remove();
		
		// Add selection indicators to this product
		$(this).addClass('selected');
		$(this).find('.cs-product-sku').append('<span class="cs-selected-checkmark">✓</span>');
		$(this).find('.cs-product-price').append('<span class="cs-selected-text"> - Selected</span>');
		
		// Clear the selected products array
		window.selectedProducts = [];
		
		// Add this product to selected products
		window.selectedProducts.push(product);
		
		// Save selected product to localStorage
		saveSelectedProductToLocalStorage(product);
		
		// Update selected products list
		updateSelectedProductsList();
		
		// NEW: Update Sales Material tab immediately
		updateSalesMaterialTab(productId);
		
		// Assign this product to all children
		assignProductToAllChildren(productId);
	});
}
	    function generateStrongPassword() {
    const length = 12;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
    let password = '';

    const categories = [
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'abcdefghijklmnopqrstuvwxyz',
        '0123456789',
        '!@#$%^&*()_+-=[]{}|;:,.<>?'
    ];

    categories.forEach(category => {
        password += category[Math.floor(Math.random() * category.length)];
    });

    for (let i = password.length; i < length; i++) {
        password += charset[Math.floor(Math.random() * charset.length)];
    }

    return password.split('').sort(() => 0.5 - Math.random()).join('');
}

jQuery(document).ready(function($) {
    // Delay attaching the click handler to ensure the DOM is fully ready
    setTimeout(function() {
        $('#generate-password').on('click', function(e) {
            e.preventDefault();
            const generatedPassword = generateStrongPassword();
            $('#child_password').val(generatedPassword);

            $('#password-suggestions-list').html(`
                <p><strong>Generated Password:</strong> ${generatedPassword}</p>
                <p>✓ Length: 12 characters</p>
                <p>✓ Includes uppercase letters</p>
                <p>✓ Includes lowercase letters</p>
                <p>✓ Includes numbers</p>
                <p>✓ Includes special characters</p>
            `);
        });
    }, 1000); // Wait 500ms before attaching the handler
});
	// Add function to assign product to all children
	function assignProductToAllChildren(productId) {
    // Validate inputs
    if (!productId) {
        console.error("Invalid product ID:", productId);
        return;
    }
    // Get the selected product's total price from the current context
    const selectedProduct = $('.cs-product-card.selected');
    const totalPrice = selectedProduct.data('price');   
    // Check if csChildAjax is defined (it should be if child management is active)
    if (typeof csChildAjax !== 'undefined') {
        // Create data for request - using ALL as child_id (must be uppercase)
        const requestData = {
            action: 'cciu_assign_product',
            nonce: csChildAjax.nonce,
            child_id: 'ALL',  // Use uppercase 'ALL' since the PHP code might be case-sensitive
            product_id: productId
        };   
        $.ajax({
            url: csAjax.ajaxurl,
            type: 'POST',
            data: requestData,
            beforeSend: function() {
                console.log("Sending request to assign product to all children");
            },
            success: function(response) {
                console.log("Assignment response:", response);
                if (response.success) {
                    alert('Product assigned to all children successfully!');
                } else {
                    console.error('Error response:', response);
                    alert('Error: ' + (response.data || 'Could not assign product to all children'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response Text:', xhr.responseText);
                alert('Error: Could not assign product to all children. Please try again.');
            }
        });
    } else {
        console.warn('Child management functionality not available. csChildAjax is not defined.');
    }
}

	window.updateSelectionVisuals = function() {	
		// Clear all indicators first
		$('.cs-product-card').removeClass('selected');
		$('.cs-selected-checkmark, .cs-selected-text').remove();
		
		// Find the selected product and update it
		if (selectedProducts.length > 0) {
			const selectedId = selectedProducts[0].id;		
			const selectedCard = $(`.cs-product-card[data-id="${selectedId}"]`);		
			if (selectedCard.length > 0) {
				selectedCard.addClass('selected');
				
				// Add indicators
				selectedCard.find('.cs-product-sku').append('<span class="cs-selected-checkmark">✓</span>');
				selectedCard.find('.cs-product-price').append('<span class="cs-selected-text"> - Selected</span>');
			}
		}
	};

	function roundPriceToNearestNine(price) {
    const rounded = Math.ceil(price);
    const lastDigit = rounded % 10;

    if (lastDigit <= 1) {
        return Math.floor(rounded / 10) * 10 - 1;
    } else {
        return Math.floor(rounded / 10) * 10 + 9;
    }
}
	
	// Update the list of selected products
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

            const isParentUser = typeof CS_Child_Manager === 'undefined' || !CS_Child_Manager.is_child_user;
            
           const rawPrice = parseFloat(product.price);
		   const productPrice = isParentUser ? roundPriceToNearestNine(rawPrice) : rawPrice;
            
            const quantity = product.quantity || 1;
            
            if ($('#product_quantity').length) {
                $('#product_quantity').val(quantity);
            }

            const lineTotal = productPrice * quantity;
            localTotalAmount += lineTotal;

            html += `
            <div class="cs-selected-product" data-id="${product.id}">
                <div class="cs-product-details">
                    <span class="cs-product-title">${product.name}</span>
                    <span class="cs-product-meta">SKU: ${product.sku || 'N/A'}</span>
                </div>
                <div class="cs-product-price-wrapper">
                    <span class="cs-product-price">${lineTotal.toFixed(2)} ${csAjax.currency}</span>
                    <button type="button" class="cs-remove-product" data-id="${product.id}">
                        <span class="dashicons dashicons-no-alt"></span> Remove
                    </button>
                </div>
            </div>
            `;
        });
    }
    
    $('#selected-products-list').html(html);
    
    $('#total_amount').val(localTotalAmount.toFixed(2));
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

	/**
 * Initializes the event listener for the quantity input field.
 */

function initQuantityChangeListener() {
    $('#product_quantity').on('input', function(e) {
        const $input = $(this);
        const rawValue = $input.val();
        
        // If input is completely empty or becomes 0, clear it
        if (rawValue === '' || rawValue === '0') {
            $input.val('');
            
            // Clear total amount
            $('#total_amount').val('0.00');
            
            // Clear selected products
            if (window.selectedProducts && window.selectedProducts.length > 0) {
                window.selectedProducts[0].quantity = 0;
                updateSelectedProductsList();
            }
            
            return;
        }
        
        // Validate input as a positive number
        const newQuantity = parseInt(rawValue, 10);
        
        // If not a valid positive number, set to 1
        if (isNaN(newQuantity) || newQuantity < 1) {
            $input.val('');
            
            // Clear total amount
            $('#total_amount').val('0.00');
            
            // Clear selected products
            if (window.selectedProducts && window.selectedProducts.length > 0) {
                window.selectedProducts[0].quantity = 0;
                updateSelectedProductsList();
            }
            
            return;
        }
        
        // Update the quantity in the selectedProducts array
        if (window.selectedProducts && window.selectedProducts.length > 0) {
            window.selectedProducts[0].quantity = newQuantity;
            updateSelectedProductsList();
        }
    }).on('blur', function() {
        const $input = $(this);
        const rawValue = $input.val();
        
        // If input is empty or becomes 0 on blur, clear it
        if (rawValue === '' || rawValue === '0') {
            $input.val('');
            
            // Clear total amount
            $('#total_amount').val('0.00');
            
            // Clear selected products
            if (window.selectedProducts && window.selectedProducts.length > 0) {
                window.selectedProducts[0].quantity = 0;
                updateSelectedProductsList();
            }
        }
    });
}
    // Add quantity change handlers
    $('.cs-quantity-input').on('change', function() {
        const productIndex = $(this).data('product-index');
        const newQuantity = parseInt($(this).val(), 10) || 1;
        
        // Ensure quantity is at least 1
        $(this).val(newQuantity);
        
        // Update the quantity in the selectedProducts array
        if (window.selectedProducts[productIndex]) {
            window.selectedProducts[productIndex].quantity = newQuantity;
            
            // Recalculate and update the list
            updateSelectedProductsList();
        }
    });

	function prepareSaleSubmission() {
    if (!window.selectedProducts || window.selectedProducts.length === 0) {
        alert('Please select at least one product');
        return false;
    }

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
        // Include quantities in the products data
        products: JSON.stringify(window.selectedProducts.map(product => ({
            id: product.id,
            name: product.name,
            price: product.price,
            sku: product.sku,
            quantity: product.quantity || 1
        })))
    };

    return formData;
}

	// Load all products on initial page load
	function loadProducts() {
    $('#products-list').html('<div class="cs-loading">Loading products...</div>');
    
    // Load any previously selected product
    const savedProduct = loadSelectedProductFromLocalStorage();
    if (savedProduct) {
        // Initialize selectedProducts array with the saved product
        selectedProducts = [savedProduct];        
        // Update the selected products list
        updateSelectedProductsList();
        
        // NEW: Update Sales Material tab for the saved product
        updateSalesMaterialTab(savedProduct.id);
    } else {
        // Reset selected products
        selectedProducts = [];
    }
    
    searchProducts('');
}

	// Handle form submission for adding a sale
	function initSaleForm() {
    let isSubmitting = false; // Flag to prevent multiple submissions

    $('#cs-order-form').off('submit').on('submit', function(e) {
        e.preventDefault();

        // Prevent multiple submissions and check if we should handle this submission
        if (isSubmitting || $(this).data('child-submission')) {
            return false;
        }

        // Validate product selection
        if (!window.selectedProducts || window.selectedProducts.length === 0) {
            alert('Please select at least one product');
            return false;
        }

        // Get the quantity from the input, with fallback
        const quantity = $('#product_quantity').length ? 
            $('#product_quantity').val() : 
            (window.selectedProducts[0]?.quantity || 1);

        // Modify the selected products to include quantity
        const productsWithQuantity = window.selectedProducts.map(product => ({
            ...product,
            quantity: quantity
        }));

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
            products: JSON.stringify(productsWithQuantity)
        };

        // Validate required fields
        if (!formData.customer_name || !formData.phone || !formData.email || !formData.address) {
            alert('Please fill in all required fields');
            return false;
        }

        // Set flag to prevent multiple submissions
        isSubmitting = true;

        $.ajax({
            url: csAjax.ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('.cs-submit-btn').text('Processing...').prop('disabled', true);
                console.log('Starting sale submission in main script');
            },
            success: function(response) {
                console.log('Sale submission response in main script:', response);
                
                // Reset submission flag
                isSubmitting = false;

                if (response.success) {
                    // Trigger stats update
                    triggerStatsUpdate();
                    
                    let successMessage = 'Sale added successfully!';
					const emailField = $('#email').val();
					if (emailField) {
						successMessage += ' A confirmation email has been sent to ' + emailField;
					}
					alert(successMessage);
                    
                    // Reset form
                    $('#cs-order-form')[0].reset();
                    window.selectedProducts = [];
                    
                    // Update selected products list
                    if (typeof window.updateSelectedProductsList === 'function') {
                        window.updateSelectedProductsList();
                    }
                    
                    // Refresh current sales stats
                    if (typeof getCurrentSalesStats === 'function') {
                        getCurrentSalesStats();
                    }
                } else {
                    // Log detailed error information
                    console.error('Sale submission error:', response.data);
                    alert('Error: ' + (response.data || 'Could not add sale'));
                }
                
                // Always reset button state
                $('.cs-submit-btn').text('Add Sale').prop('disabled', false);
            },
            error: function(xhr, status, error) {
                // Reset submission flag
                isSubmitting = false;

                console.error('Sale submission AJAX error:', status, error);
                console.error('Response text:', xhr.responseText);
                
                alert('Error: Could not add sale. Please try again.');
                
                // Reset button state
                $('.cs-submit-btn').text('Add Sale').prop('disabled', false);
            }
        });

        return false;
    });
}

	// Load user's orders
	function loadOrders() {
		$('#orders-list').html('<tr><td colspan="8" class="cs-loading">Loading orders...</td></tr>');

		const status = $('#order-status-filter').val();
		const userFilter = $('#order-user-filter').val() || '';

		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_sales',
				nonce: csAjax.nonce,
				status: status,
				user_filter: userFilter
			},
			success: function(response) {
				if (response.success && response.data.sales) {
					displayOrders(response.data.sales);
				} else {
					$('#orders-list').html('<tr><td colspan="8">No orders found</td></tr>');
				}
			},
			error: function() {
				$('#orders-list').html('<tr><td colspan="8">Error loading orders. Please try again.</td></tr>');
			}
		});
	}

	// Display orders in the table
	function displayOrders(orders) {
    if (!orders || orders.length === 0) {
        $('#orders-list').html('<tr><td colspan="7">No orders found</td></tr>');
        return;
    }

    let html = '';
    orders.forEach(function(order) {
        const statusClass = 'cs-status-' + order.status;
        const isChild = order.is_child ? ' (Child)' : '';

        // NO RESET BUTTON - Orders are either 'pending' or 'completed'
        // If user goes to checkout and comes back without completing, it stays 'pending'
        
        html += `
<tr data-order-id="${order.id}">
    <td>${order.id}</td>
    <td>${order.sale_date}</td>
    <td>${order.customer_name}</td>
    <td>${order.user_name}${isChild}</td>
    <td>${order.sale_amount} ${csAjax.currency}</td>
    <td><span class="cs-order-status ${statusClass}">${order.status}</span></td>
    <td class="cs-order-actions">
        <button type="button" class="cs-order-action cs-view-order" data-id="${order.id}">View</button>
        <button type="button" class="cs-order-action cs-delete-order" data-id="${order.id}">Delete</button>
    </td>
</tr>
`;
    });

    $('#orders-list').html(html);

    // Add action handlers
    $('.cs-view-order').on('click', function() {
        const orderId = $(this).data('id');
        showOrderDetails(orderId);
    });

    // Delete order handler with stats update
    $('.cs-delete-order').on('click', function() {
        const orderId = $(this).data('id');
        const $row = $(this).closest('tr');
        
        // Confirm deletion
        if (confirm('Are you sure you want to delete this order?')) {
            $.ajax({
                url: csAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cs_delete_sale',
                    nonce: csAjax.nonce,
                    sale_id: orderId
                },
                beforeSend: function() {
                    $row.find('.cs-delete-order').text('Deleting...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // Trigger stats update
                        $(document).trigger('cs_sale_deleted', { sale_id: orderId });
                        triggerStatsUpdate();
                        
                        // Remove the row from the table
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Refresh current sales stats
                            getCurrentSalesStats();
                            
                            // If no orders left, show empty message
                            if ($('#orders-list tr').length === 0) {
                                $('#orders-list').html('<tr><td colspan="7">No orders found</td></tr>');
                            }
                        });
                        
                        alert('Order deleted successfully');
                    } else {
                        alert('Error: ' + (response.data || 'Could not delete order'));
                        $row.find('.cs-delete-order').text('Delete').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Error: Could not delete order. Please try again.');
                    $row.find('.cs-delete-order').text('Delete').prop('disabled', false);
                }
            });
        }
    });

    // Remove all reset button handlers since we don't have reset buttons anymore
}
	
// Initialize order filters and processing
function initOrderFilters() {
    $('#klarna-checkout-selected-btn').on('click', function() {
        // Collect all pending order IDs
        const pendingOrders = [];
        
        // Find all rows with 'pending' status and extract their actual order IDs
        $('.cs-orders-table tbody tr').each(function() {
            const status = $(this).find('.cs-order-status').text().trim().toLowerCase();
            
            // Use the first column which should contain the order number
            const orderId = $(this).find('td:first-child').text().trim();
            
            if (status === 'pending') {
                pendingOrders.push(orderId);
            }
        });
        
        console.log("Pending Order IDs:", pendingOrders);
        
        if (pendingOrders.length === 0) {
            alert('No pending orders to process');
            return;
        }
        processKlarnaCheckout(pendingOrders);
    });
}

// Show order details in a popup
function showOrderDetails(orderId) {
    // Create modal container if it doesn't exist
    if ($('#cs-order-modal').length === 0) {
        $('body').append(`
            <div id="cs-order-modal" class="cs-modal">
                <div class="cs-modal-content">
                    <span class="cs-modal-close">&times;</span>
                    <div id="cs-modal-body"></div>
                </div>
            </div>
        `);
        
        // Add modal styles
        $('<style>')
        .text(`
            .cs-modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.4);
            }
            .cs-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 800px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                color: black;
                position: relative;
            }
            .cs-modal-close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            .cs-modal-close:hover {
                color: black;
            }
            .cs-modal-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 20px;
                gap: 10px;
            }
            .cs-edit-order-btn {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            .cs-edit-order-btn:hover {
                background-color: #45a049;
            }
        `)
        .appendTo('head');
        
        // Handle modal close button
        $('.cs-modal-close').on('click', function() {
            $('#cs-order-modal').hide();
        });
        
        // Close modal when clicking outside
        $(window).on('click', function(event) {
            if ($(event.target).is('#cs-order-modal')) {
                $('#cs-order-modal').hide();
            }
        });
    }
    // Fetch order details
    $.ajax({
        url: csAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'cs_get_order_details',
            nonce: csAjax.nonce,
            order_id: orderId
        },
        beforeSend: function() {
            $('#cs-modal-body').html('<div class="cs-loading">Loading order details...</div>');
            $('#cs-order-modal').show();
        },
        success: function(response) {
            if (response.success && response.data) {
                const order = response.data;
                
                // Parse products
                let productsHtml = '';
                let totalAmount = 0;
                
                if (order.products && Array.isArray(order.products) && order.products.length > 0) {
                    order.products.forEach(function(product) {
                        productsHtml += `
                            <div class="cs-order-product-item">
                                <div class="cs-product-name">${product.name || product.title || 'Unknown Product'}</div>
                                <div class="cs-product-price">${product.price || 0} ${csAjax.currency}</div>
                            </div>
                        `;
                        totalAmount += parseFloat(product.price || 0);
                    });
                } else {
                    productsHtml = '<p>No products found for this order.</p>';
                }
                
                const orderDetailsHtml = `
                    <div class="cs-order-detail-section">
                        <h3>Order #${order.id} Details</h3>
                        <table class="cs-order-detail-table">
                            <tr>
                                <th>Order Date:</th>
                                <td>${order.sale_date}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td><span class="cs-order-status cs-status-${order.status}">${order.status}</span></td>
                            </tr>
                            <tr>
                                <th>Customer:</th>
                                <td>${order.customer_name}</td>
                            </tr>
							<tr>
								<th>Email:</th>
								<td>${order.email || 'N/A'}</td>
							</tr>
                            <tr>
                                <th>Phone:</th>
                                <td>${order.phone || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td>${order.address || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Total Amount:</th>
                                <td><strong>${order.sale_amount} ${csAjax.currency}</strong></td>
                            </tr>
                            <tr>
                                <th>Notes:</th>
                                <td>${order.notes || 'No notes'}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="cs-order-detail-section">
                        <h3>Products</h3>
                        <div class="cs-order-product-list">
                            ${productsHtml}
                        </div>
                    </div>
                    
                    <div class="cs-modal-actions">
                        <button class="cs-edit-order-btn" data-order-id="${order.id}">Edit Order</button>
                    </div>
                `;
                
                $('#cs-modal-body').html(orderDetailsHtml);
                
                // Add click handler for edit button
                $('.cs-edit-order-btn').on('click', function() {
                    const editOrderId = $(this).data('order-id');
                    
                    // Populate edit form
                    populateEditOrderForm(order);
                });
            } else {
                $('#cs-modal-body').html('<p>Error loading order details: ' + (response.data || 'Unknown error') + '</p>');
            }
        },
        error: function() {
            $('#cs-modal-body').html('<p>Error loading order details. Please try again.</p>');
        }
    });
}

// New function to populate edit order form
function populateEditOrderForm(order) {
    // Create edit modal if it doesn't exist
    if ($('#cs-edit-order-modal').length === 0) {
        $('body').append(`
            <div id="cs-edit-order-modal" class="cs-modal">
                <div class="cs-modal-content">
                    <span class="cs-edit-modal-close">&times;</span>
                    <h2>Edit Order</h2>
                    
                    <form id="cs-edit-order-form" class="cs-form">
        <input type="hidden" id="edit-order-id" name="order_id">
        
        <div class="cs-form-group">
            <label for="edit-customer-name">Customer Name</label>
            <input type="text" id="edit-customer-name" name="customer_name" required>
        </div>
        
        <div class="cs-form-group">
            <label for="edit-email">Email</label>
            <input type="email" id="edit-email" name="email">
        </div>
        
        <div class="cs-form-group">
            <label for="edit-phone">Phone</label>
            <input type="tel" id="edit-phone" name="phone">
        </div>
        
        <div class="cs-form-group">
            <label for="edit-address">Address</label>
            <textarea id="edit-address" name="address"></textarea>
        </div>
        
        <div class="cs-form-group">
            <label for="edit-sale-date">Sale Date</label>
            <input type="date" id="edit-sale-date" name="sale_date" required>
        </div>
        
        <div class="cs-form-group">
            <label for="edit-notes">Notes</label>
            <textarea id="edit-notes" name="notes"></textarea>
        </div>
        
        <div class="cs-form-actions">
            <button type="submit" class="cs-submit-btn">Save Changes</button>
        </div>
    </form>
                </div>
            </div>
        `);
        
        // Add modal styles
        $('<style>')
        .text(`
            #cs-edit-order-modal {
                display: none;
                position: fixed;
                z-index: 1100;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.4);
            }
            #cs-edit-order-modal .cs-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 600px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                position: relative;
            }
            #cs-edit-order-modal .cs-edit-modal-close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            #cs-edit-order-modal .cs-edit-modal-close:hover {
                color: black;
            }
            #cs-edit-order-modal .cs-form-group {
                margin-bottom: 15px;
            }
            #cs-edit-order-modal label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #333;
            }
            #cs-edit-order-modal input,
            #cs-edit-order-modal textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                color: #333;
            }
            #cs-edit-order-modal .cs-submit-btn {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            #cs-edit-order-modal .cs-submit-btn:hover {
                background-color: #45a049;
            }
        `)
        .appendTo('head');
        $('#edit-email').val(order.email || '');
        // Close edit modal
        $(document).on('click', '.cs-edit-modal-close', function() {
            $('#cs-edit-order-modal').hide();
        });
    }

    // Populate form with order details
    $('#edit-order-id').val(order.id);
    $('#edit-customer-name').val(order.customer_name);
	$('#edit-email').val(order.email);
    $('#edit-phone').val(order.phone);
    $('#edit-address').val(order.address);
    
    // Ensure date is in correct format
    const formattedDate = order.sale_date ? 
        new Date(order.sale_date).toISOString().split('T')[0] : 
        new Date().toISOString().split('T')[0];
    
    $('#edit-sale-date').val(formattedDate);
    $('#edit-notes').val(order.notes);
    
    // Add Geoapify Autocomplete to edit address field
    function initEditAddressAutocomplete() {
        // Ensure we have GeoapifyConfig and jQuery
        if (typeof GeoapifyConfig === 'undefined' || !window.jQuery) {
            console.error('Geoapify configuration or jQuery not loaded');
            return;
        }

        const addressInput = $('#edit-address');
        
        // Validate address input exists
        if (addressInput.length === 0) {
            console.warn('Edit Address input not found');
            return;
        }

        // Create autocomplete container wrapper
        const autocompleteWrapper = $('<div class="address-autocomplete-wrapper"></div>');
        addressInput.wrap(autocompleteWrapper);

        // Create suggestions container
        const suggestionsContainer = $('<div class="address-suggestions-container"></div>');
        addressInput.after(suggestionsContainer);

        // Add styles (if not already added)
        if ($('#address-autocomplete-styles').length === 0) {
            $('<style>')
                .attr('id', 'address-autocomplete-styles')
                .text(`
                    .address-autocomplete-wrapper {
                        position: relative;
                        width: 100%;
                    }
                    .address-suggestions-container {
                        position: absolute;
                        top: 100%;
                        left: 0;
                        width: 100%;
                        max-height: 250px;
                        overflow-y: auto;
                        background: white;
                        border: 1px solid #ddd;
                        z-index: 1000;
                        display: none;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }
                    .address-suggestion {
                        padding: 10px 15px;
                        cursor: pointer;
                        border-bottom: 1px solid #f0f0f0;
                    }
                    .address-suggestion:hover {
                        background-color: #f5f5f5;
                    }
                `)
                .appendTo('head');
        }

        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Fetch address suggestions
        function fetchAddressSuggestions(query) {
            const container = $('.address-suggestions-container');
            
            // Minimum query length
            if (query.trim().length < 3) {
                container.hide();
                return;
            }

            // Show loading
            container.html('<div style="padding:10px;">Searching...</div>').show();

            // AJAX request to Geoapify
            $.ajax({
                url: 'https://api.geoapify.com/v1/geocode/autocomplete',
                method: 'GET',
                data: {
                    text: query,
                    apiKey: GeoapifyConfig.apiKey,
                    filter: {
                        countrycode: GeoapifyConfig.country.toLowerCase()
                    },
                    limit: GeoapifyConfig.limit,
                    format: 'json'
                },
                success: function(response) {
                    container.empty();

                    if (response.results && response.results.length > 0) {
                        response.results.forEach(result => {
                            $('<div>')
                                .addClass('address-suggestion')
                                .text(result.formatted)
                                .on('click', function() {
                                    addressInput.val(result.formatted);
                                    container.hide();
                                })
                                .appendTo(container);
                        });
                        container.show();
                    } else {
                        container.html('<div style="padding:10px;">No results found</div>').show();
                    }
                },
                error: function() {
                    container.html('<div style="padding:10px;">Error fetching suggestions</div>').show();
                }
            });
        }

        // Event listeners
        addressInput
            .attr('autocomplete', 'off')
            .on('input', debounce(function() {
                fetchAddressSuggestions($(this).val());
            }, 300))
            .on('focus', function() {
                const query = $(this).val();
                if (query.trim().length >= 3) {
                    fetchAddressSuggestions(query);
                }
            });

        // Close suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#edit-address, .address-suggestions-container').length) {
                $('.address-suggestions-container').hide();
            }
        });
    }

    // Call address autocomplete initialization
    initEditAddressAutocomplete();
    
    // Form submission handler with stats update
    $('#cs-edit-order-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        
		console.log('Form Submission Data:', {
			customer_name: $('#customer_name').val(),
			email: $('#email').val(),
			phone: $('#phone').val(),
			address: $('#address').val(),
			total_amount: $('#total_amount').val(),
			sale_date: $('#sale_date').val(),
			notes: $('#notes').val(),
			products: window.selectedProducts
		});
        // Collect form data (remove sale_amount)
        const formData = {
            action: 'cs_update_order',
            nonce: csAjax.nonce,
            order_id: $('#edit-order-id').val(),
            customer_name: $('#edit-customer-name').val(),
			email: $('#edit-email').val(),
            phone: $('#edit-phone').val(),
            address: $('#edit-address').val(),
            sale_date: $('#edit-sale-date').val(),
            notes: $('#edit-notes').val()
        };
		console.log('Form data being sent:', formData);
        console.log('Email value:', $('#email').val());
        // AJAX call to update order
        $.ajax({
            url: csAjax.ajaxurl,
            method: 'POST',
            data: formData,
            beforeSend: function() {
                // Disable submit button and show loading state
                $('.cs-submit-btn')
                    .prop('disabled', true)
                    .text('Updating...');
            },
            success: function(response) {
                if (response.success) {
                    // Trigger stats update if indicated
                    if (response.data && response.data.stats_updated) {
                        $(document).trigger('cs_order_updated', { order_id: formData.order_id });
                        triggerStatsUpdate();
                    }
                    
                    // Update the row in the orders table
                    const updatedOrder = response.data.order;
                    const $row = $(`tr[data-order-id="${updatedOrder.id}"]`);
                    
                    if ($row.length) {
                        // Update visible row data
                        $row.find('.order-customer-name').text(updatedOrder.customer_name);
                        // Keep the existing amount
                        $row.find('.order-date').text(updatedOrder.sale_date);
                    }

                    // Close both modals
                    $('#cs-edit-order-modal').hide();
                    $('#cs-order-modal').hide();

                    // Show success message
                    alert('Order updated successfully');

                    // Optional: Refresh orders list
                    if (typeof loadOrders === 'function') {
                        loadOrders();
                    }
                } else {
                    // Show error message
                    alert('Error: ' + (response.data || 'Could not update order'));
                }
            },
            error: function() {
                alert('Error: Could not update order. Please try again.');
            },
            complete: function() {
                // Reset submit button
                $('.cs-submit-btn')
                    .prop('disabled', false)
                    .text('Save Changes');
            }
        });
    });
    
    // Show the edit modal
    $('#cs-edit-order-modal').show();
}
	// Process Klarna checkout
	function processKlarnaCheckout(saleIds) {
    console.log("Processing checkout for sale IDs:", saleIds);
    
    // Show loading state
    $('#klarna-checkout-selected-btn').text('Processing...').prop('disabled', true);
    
    $.ajax({
        url: csAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'cs_process_klarna',
            nonce: csAjax.nonce,
            sale_ids: saleIds
        },
        timeout: 30000, // 30 second timeout
        success: function(response) {
            console.log("Checkout response:", response);
            
            if (response.success && response.data && response.data.redirect_url) {
                // Trigger stats update if indicated
                if (response.data.stats_updated) {
                    triggerStatsUpdate();
                }
                
                // Show success message
                const processedCount = response.data.processed_count || 1;
                const checkoutType = response.data.checkout_type || 'checkout';
                // Handle the redirect URL
                const redirectUrl = response.data.redirect_url;
                console.log("Redirecting to:", redirectUrl);
                
                // Direct redirect to WooCommerce checkout
                window.location.href = redirectUrl;
                
            } else {
                console.error("Invalid response structure:", response);
                alert('Error: ' + (response.data || 'Could not process orders - invalid response'));
                $('#klarna-checkout-selected-btn').text('Process Orders').prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            let errorMessage = 'Error: Could not process orders. Please try again.';
            
            // Try to extract more specific error message
            if (xhr.responseText) {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.data) {
                        errorMessage = 'Error: ' + errorResponse.data;
                    }
                } catch (e) {
                    // If JSON parsing fails, use the raw response
                    errorMessage = 'Error: ' + xhr.responseText.substring(0, 100);
                }
            }
            
            alert(errorMessage);
            $('#klarna-checkout-selected-btn').text('Process Orders').prop('disabled', false);
        }
    });
}
	// Load user stats
	function loadStats() {
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_stats',
				nonce: csAjax.nonce
			},
			success: function(response) {
				if (response.success) {
					renderStatsDashboard(response.data);
				}
			}
		});
	}

	// Render stats dashboard with charts
	function renderStatsDashboard(stats) {
		// Update stats values
		$('.cs-total-profit').text(stats.total_profit + ' ' + csAjax.currency);
		$('.cs-opportunities').text(stats.opportunities);
		$('.cs-completed-sales').text(stats.completed_sales);

		// Initialize sales chart if it exists
		if ($('#sales-chart').length > 0) {
			initSalesChart();
		}
	}

	// Initialize sales chart
	function initSalesChart() {
		const ctx = document.getElementById('sales-chart').getContext('2d');

		// Sample data - in a real implementation, this would come from the server
		const data = {
			labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
			datasets: [{
				label: 'Sales (SEK)',
				backgroundColor: 'rgba(76, 175, 80, 0.2)',
				borderColor: 'rgba(76, 175, 80, 1)',
				borderWidth: 2,
				data: [1500, 2500, 1800, 3200, 4200, 3800]
			}]
		};

		new Chart(ctx, {
			type: 'line',
			data: data,
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
		});
	}

	// Get current sales stats
	function getCurrentSalesStats() {
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_stats',
				nonce: csAjax.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#current-sales-count').text(response.data.completed_sales);
					$('#current-sales-amount').text(response.data.total_profit + ' ' + csAjax.currency);
				}
			}
		});
	}

	// Filter orders by status
	function initOrderFilter() {
		$('#order-status-filter').on('change', function() {
			loadOrders();
		});
	}

	// Initialize Klarna checkout
	function initKlarnaCheckout() {
    $('#klarna-checkout-btn').on('click', function() {
        if (selectedProducts.length === 0) {
            alert('Please select at least one product');
            return;
        }

        // First save the sale to get sale ID
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
            products: JSON.stringify(selectedProducts)
        };

        // Validate form
        if (!formData.customer_name || !formData.email || !formData.phone || !formData.address) {
            alert('Please fill in all required fields');
            return;
        }

        $.ajax({
            url: csAjax.ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#klarna-checkout-btn').text('Processing...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success && response.data.sale_id) {
                    // Trigger stats update
                    triggerStatsUpdate();
                    
                    // Process checkout with the new sale ID
                    processKlarnaCheckout([response.data.sale_id]);
                } else {
                    alert('Error: ' + (response.data || 'Could not add sale'));
                    $('#klarna-checkout-btn').text('Continue to Checkout').prop('disabled', false);
                }
            },
            error: function() {
                alert('Error: Could not add sale. Please try again.');
                $('#klarna-checkout-btn').text('Continue to Checkout').prop('disabled', false);
            }
        });
    });
}
	// Initialize period controls for chart
	function initChartControls() {
		$('.cs-chart-period').on('click', function() {
			$('.cs-chart-period').removeClass('active');
			$(this).addClass('active');

			const period = $(this).data('period');
			// Update chart based on period (to be implemented)
		});
	}

	// Address Autocomplete functionality
	function initAddressAutocomplete() {
    // Ensure we have GeoapifyConfig and jQuery
    if (typeof GeoapifyConfig === 'undefined' || !window.jQuery) {
        console.error('Geoapify configuration or jQuery not loaded');
        return;
    }

    const addressInput = $('#address');
    
    // Validate address input exists
    if (addressInput.length === 0) {
        console.warn('Address input not found');
        return;
    }

    // Create autocomplete container wrapper
    const autocompleteWrapper = $('<div class="address-autocomplete-wrapper"></div>');
    addressInput.wrap(autocompleteWrapper);

    // Create suggestions container
    const suggestionsContainer = $('<div class="address-suggestions-container"></div>');
    addressInput.after(suggestionsContainer);

    // Add styles
    $('<style>')
        .attr('id', 'address-autocomplete-styles')
        .text(`
            .address-autocomplete-wrapper {
                position: relative;
                width: 100%;
            }
            .address-suggestions-container {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                max-height: 250px;
                overflow-y: auto;
                background: white;
                border: 1px solid #ddd;
                z-index: 1000;
                display: none;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .address-suggestion {
                padding: 10px 15px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
            }
            .address-suggestion:hover {
                background-color: #f5f5f5;
            }
        `)
        .appendTo('head');

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Fetch address suggestions
    function fetchAddressSuggestions(query) {
        const container = $('.address-suggestions-container');
        
        // Minimum query length
        if (query.trim().length < 3) {
            container.hide();
            return;
        }

        // Show loading
        container.html('<div style="padding:10px;">Searching...</div>').show();

        // AJAX request to Geoapify
        $.ajax({
            url: 'https://api.geoapify.com/v1/geocode/autocomplete',
            method: 'GET',
            data: {
                text: query,
                apiKey: GeoapifyConfig.apiKey,
                filter: {
                    countrycode: GeoapifyConfig.country.toLowerCase()
                },
                limit: GeoapifyConfig.limit,
                format: 'json'
            },
            success: function(response) {
                container.empty();

                if (response.results && response.results.length > 0) {
                    response.results.forEach(result => {
                        $('<div>')
                            .addClass('address-suggestion')
                            .text(result.formatted)
                            .on('click', function() {
                                addressInput.val(result.formatted);
                                container.hide();
                            })
                            .appendTo(container);
                    });
                    container.show();
                } else {
                    container.html('<div style="padding:10px;">No results found</div>').show();
                }
            },
            error: function() {
                container.html('<div style="padding:10px;">Error fetching suggestions</div>').show();
            }
        });
    }

    // Event listeners
    addressInput
        .attr('autocomplete', 'off')
        .on('input', debounce(function() {
            fetchAddressSuggestions($(this).val());
        }, 300))
        .on('focus', function() {
            const query = $(this).val();
            if (query.trim().length >= 3) {
                fetchAddressSuggestions(query);
            }
        });

    // Close suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#address, .address-suggestions-container').length) {
            $('.address-suggestions-container').hide();
        }
    });
}

	// Document ready function
	$(document).ready(function() {
		// Initialize tabs
		initTabs();

		// Initialize product search
		initProductSearch();

		// Initialize sale form
		initSaleForm();
		setTimeout(initAddressAutocomplete, 500);
		// Initialize order filter
		if (typeof initOrderFilters === 'function') {
			initOrderFilters();
		}

		// Initialize Klarna checkout
		if (typeof initKlarnaCheckout === 'function') {
			initKlarnaCheckout();
		}

		// Initialize chart controls
		if (typeof initChartControls === 'function') {
			initChartControls();
		}

		// Initialize address autocomplete
		if (typeof initAddressAutocomplete === 'function') {
			initAddressAutocomplete();
		}

		// Load initial products for active tab
		if ($('#tab-assign-product').hasClass('active')) {
			loadProducts();
		}
		const savedProduct = localStorage.getItem('cs_selected_product');
    if (savedProduct) {
        try {
            const product = JSON.parse(savedProduct);           
            // Explicitly set window.selectedProducts
            window.selectedProducts = [product];
            
            // Update the selected products list
            updateSelectedProductsList();
            
            // NEW: Update Sales Material tab for the saved product
            updateSalesMaterialTab(product.id);
        } catch (e) {
            console.error('Error parsing saved product:', e);
        }
    }

    // Add this specific handler for the Add Order tab
    $('.cs-tab-item').on('click', function() {
        const tabId = $(this).data('tab');
        
        if (tabId === 'add-order') {
            
            // Force update of selected products list
            const savedProduct = localStorage.getItem('cs_selected_product');
            if (savedProduct) {
                try {
                    const product = JSON.parse(savedProduct);
                    window.selectedProducts = [product];
                    updateSelectedProductsList();
                } catch (e) {
                    console.error('Error parsing saved product:', e);
                }
            }
        } else if (tabId === 'sales-material') {
            // When switching to Sales Material tab, check if we have a selected product
            setTimeout(function() {
                const savedProduct = localStorage.getItem('cs_selected_product');
                if (savedProduct) {
                    try {
                        const product = JSON.parse(savedProduct);
                        if (product && product.id) {
                            updateSalesMaterialTab(product.id);
                        }
                    } catch (e) {
                        console.error('Error parsing saved product:', e);
                    }
                }
            }, 300);
        }
    });
		// Get current sales stats
		getCurrentSalesStats();

		// Add style for selected products
		$('<style>')
			.text(`
				.cs-product-card.selected { 
					border: 2px solid #4CAF50; 
					box-shadow: 0 0 10px rgba(76, 175, 80, 0.3); 
				}
				.cs-selected-checkmark {
					color: #4CAF50;
					margin-left: 5px;
					font-weight: bold;
					font-size: 16px;
				}
				.cs-selected-text {
					color: #4CAF50;
					font-weight: bold;
				}
				.cs-order-actions .cs-delete-order {
					margin-left: 5px;
					background-color: #f44336;
					color: white;
					border: none;
					padding: 17px 32px;
					border-radius: 25px;
					cursor: pointer;
				}
				.cs-order-actions .cs-delete-order:hover {
					background-color: #d32f2f;
					border: none;
					color: white;
				}
			`)
			.appendTo('head');
	});

})(jQuery);
