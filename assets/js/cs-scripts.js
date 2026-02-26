/**
 * Club Sales Plugin - Complete JavaScript Functionality
 * Includes: Cart Widget, Product Selection, Orders, Stats, Forms, Modals, and ALL Features
 */

(function ($) {
	'use strict';

	// ============================================
	// GLOBAL VARIABLES
	// ============================================

	let selectedProducts = [];
	let totalAmount = 0;
	let cartItems = [];
	let combinationMode = false;
	let currentVendorId = null;

	let orderProducts = []; // Products added to the current order - GLOBAL SCOPE
	window.orderProducts = orderProducts; // ✅ Expose to window for child-scripts access
	let allProducts = []; // All available products for dropdown

	// ============================================
	// AUTO-LOAD ASSIGNED PRODUCTS FOR CHILD USERS
	// ============================================
	
	function loadAssignedProductsForChild() {
		// Check if user is a child user from csAjax (set by PHP)
		const isChildUser = csAjax.isChildUser === true || csAjax.isChildUser === '1';
		
		if (!isChildUser) {
			console.log('⚪ Not a child user, skipping assigned products loading');
			return;
		}
		
		console.log('👶 Child user detected! Loading assigned products...');
		
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_search_products',
				nonce: csAjax.nonce,
				search: '',
				category: '',
				sort: 'name-asc'
			},
			success: function(response) {
				if (response.success && response.data && response.data.products) {
					console.log('✅ Loaded ' + response.data.products.length + ' assigned products for child');
					
					// Populate cartItems with assigned products
					cartItems = response.data.products.map(function(product) {
						return {
							id: product.id,
							name: product.name,
							price: parseFloat(product.price),
							rrp: parseFloat(product.rrp),
							total_price: parseFloat(product.total_price || product.price),
							sku: product.sku || '',
							supplier: product.supplier || '',
							can_be_combined: product.can_be_combined || false,
							image: product.image || ''
						};
					});
					
					console.log('📦 CartItems populated:', cartItems.length, 'products');
					
					// Update product dropdown in Add Order tab
					if (typeof loadProductsForOrderDropdown === 'function') {
						loadProductsForOrderDropdown();
					}
					
					// Trigger products loaded event
					$(document).trigger('productsLoaded');
				} else {
					console.log('⚠️ No assigned products found for child user');
				}
			},
			error: function(xhr, status, error) {
				console.error('❌ Error loading assigned products:', error);
			}
		});
	}
	
	// Load assigned products on page load for child users
	$(document).ready(function() {
		loadAssignedProductsForChild();
	});

	// ============================================
	// SALES MATERIAL TAB UPDATE (Stub Function)
	// ============================================
	
	window.updateSalesMaterialTab = function(productId) {
		// This is a stub function - actual implementation is in cs-child-scripts.js
		// Call child script version if available
		if (typeof window.updateSalesMaterialTabChild === 'function') {
			window.updateSalesMaterialTabChild(productId);
		}
	};

	// ============================================
	// PRODUCT DETAIL VIEW FUNCTIONS (Define Early)
	// ============================================

	window.showProductDetail = function (productId) {
		// Find product data
		const $productCard = $(`.cs-product-card[data-id="${productId}"]`);
		if (!$productCard.length) {
			console.error('Product not found:', productId);
			alert('Product card not found. Product ID: ' + productId);
			return;
		}

		// Get product data
		const productData = {
			id: $productCard.data('id'),
			name: $productCard.data('name'),
			price: $productCard.data('price'),
			rrp: $productCard.data('rrp'),
			sku: $productCard.data('sku'),
			supplier: $productCard.data('store-name'),
			image: $productCard.find('.cs-product-image img').attr('src') || ''
		};
		// Calculate profit
		const profit = parseFloat(productData.rrp) - parseFloat(productData.price);

		// Populate detail view
		$('#cs-detail-main-image').attr('src', productData.image);
		$('#cs-detail-supplier-name').text(productData.supplier);
		$('#cs-detail-supplier-name-box').text(productData.supplier);
		$('#cs-detail-sku').text(productData.sku);
		$('#cs-detail-title').text(productData.name);
		$('#cs-detail-club-price').text(parseFloat(productData.price).toFixed(2) + ' kr');
		$('#cs-detail-rrp').text(parseFloat(productData.rrp).toFixed(2) + ' kr');
		$('#cs-detail-profit').text(profit.toFixed(2) + ' kr');

		// Check if product is selected
		const isSelected = cartItems.some(item => item.id === productData.id);
		const $selectBtn = $('#cs-detail-select-btn');

		if (isSelected) {
			$selectBtn.addClass('selected').html(`
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<polyline points="20 6 9 17 4 12"></polyline>
</svg>
Produkt vald
`);
		} else {
			$selectBtn.removeClass('selected').html(`
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<path d="M12 5v14M5 12h14"></path>
</svg>
Välj produkt
`);
		}

		// Fetch product description via AJAX
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_product_description',
				nonce: csAjax.nonce,
				product_id: productData.id
			},
			success: function (response) {
				if (response.success && response.data.description) {
					$('#cs-detail-description-content').html(response.data.description);
				} else {
					$('#cs-detail-description-content').html('<p>Ingen beskrivning tillgänglig.</p>');
				}
			},
			error: function (xhr, status, error) {
				console.error('AJAX error:', status, error);
				console.error('Response:', xhr.responseText);
				$('#cs-detail-description-content').html('<p>Fel vid hämtning av beskrivning.</p>');
			}
		});

		// Store current product ID
		$('#cs-product-detail-view').data('current-product-id', productData.id);

		// Show detail view
		$('#cs-product-detail-view').css('display', 'block').fadeIn(300);

		// Hide products section
		$('.cs-products-section').hide();
		$('.cs-product-header').hide();
		$('.cs-cart-widget').hide();
		// Scroll to top
		window.scrollTo({ top: 0, behavior: 'smooth' });
	};

	function hideProductDetail() {
		$('#cs-product-detail-view').fadeOut(300, function () {
			$(this).css('display', 'none');
		});
		$('.cs-products-section').fadeIn(300);
		$('.cs-product-header').fadeIn(300);
		$('.cs-cart-widget').fadeIn(300);
	}

	function initProductDetailView() {
		// Back button
		$('#cs-back-to-products').on('click', function () {
			hideProductDetail();
		});

		// Select/Deselect button
		$('#cs-detail-select-btn').on('click', function () {
			const productId = $('#cs-product-detail-view').data('current-product-id');
			const $productCard = $(`.cs-product-card[data-id="${productId}"]`);

			if (!$productCard.length) return;

			const isSelected = $(this).hasClass('selected');

			if (isSelected) {
				// Deselect
				$(this).removeClass('selected').html(`
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<path d="M12 5v14M5 12h14"></path>
</svg>
Välj produkt
`);

				removeFromCart(productId);
				$productCard.removeClass('selected');
			} else {
				// Select
				$(this).addClass('selected').html(`
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<polyline points="20 6 9 17 4 12"></polyline>
</svg>
Produkt vald
`);

				const product = {
					id: $productCard.data('id'),
					name: $productCard.data('name'),
					price: $productCard.data('price'), // Base price (what you pay to supplier)
					rrp: $productCard.data('rrp'), // Customer price
					total_price: $productCard.data('rrp'), // Use RRP as the selling price
					sku: $productCard.data('sku'),
					image: $productCard.find('.cs-product-image img').attr('src'),
					store_name: $productCard.data('store-name'),
					vendor_id: $productCard.data('vendor-id'),
					can_be_combined: $productCard.data('can-be-combined')
				};

				addToCart(product);
				$productCard.addClass('selected');
			}

			updateSelectedProductsList();
		});
	}

	// ============================================
	// CART WIDGET FUNCTIONALITY
	// ============================================

	// Load cart from localStorage on page load
	function loadCartFromStorage() {
		const saved = localStorage.getItem('cs_cart_items');
		if (saved) {
			try {
				cartItems = JSON.parse(saved);
				updateCartWidget();
			} catch (e) {
				console.error('Error loading cart:', e);
				cartItems = [];
			}
		}
	}

	// Save cart to localStorage
	function saveCartToStorage() {
		localStorage.setItem('cs_cart_items', JSON.stringify(cartItems));
	}

	// Add product to cart
	function addToCart(product) {
		const existingIndex = cartItems.findIndex(item => item.id === product.id);

		if (existingIndex === -1) {
			console.log('🛒 Adding to cart:', {
				id: product.id,
				name: product.name,
				price: product.price,
				rrp: product.rrp
			});
			
			cartItems.push({
				id: product.id,
				name: product.name,
				price: product.price,
				rrp: product.rrp,
				total_price: product.total_price || product.price,
				sku: product.sku,
				image: product.image,
				supplier: product.store_name || 'LAMBES',
				vendor_id: product.vendor_id,
				can_be_combined: product.can_be_combined || false,
				category: product.category || 'Övrigt'
			});

			saveCartToStorage();
			updateCartWidget();

			// Auto-expand widget when adding product
			$('.cs-cart-widget').removeClass('collapsed');

			showNotification('Produkt tillagd i varukorgen!', 'success');
			return true;
		} else {
			showNotification('Produkten finns redan i varukorgen', 'info');
			return false;
		}
	}

	// Remove product from cart
	function removeFromCart(productId) {
		cartItems = cartItems.filter(item => item.id !== productId);

		// Update product card visual state
		$(`.cs-product-card[data-id="${productId}"]`).removeClass('selected');

		// Check if we should exit combination mode
		checkCombinationModeStatus();

		saveCartToStorage();
		updateCartWidget();
		showNotification('Produkt borttagen från varukorgen', 'success');
	}

	// Update cart widget display
	function updateCartWidget() {
		const $cartItems = $('#cs-cart-items');
		const $cartCount = $('.cs-cart-count');
		const $productCount = $('.cs-cart-product-count');
		const $cartNote = $('.cs-cart-note');
		const $continueBtn = $('#cs-continue-to-manage');

		const itemCount = cartItems.length;
		function truncateTextByWords(text, maxWords) {
			if (!text) return '';
			const words = text.split(' ');
			if (words.length > maxWords) {
				return words.splice(0, maxWords).join(' ') + '...';
			}
			return text;
		}
		// Update counts
		$cartCount.text(itemCount);
		$productCount.text(`${itemCount} produkt${itemCount !== 1 ? 'er' : ''}`);
		$cartNote.text(`${itemCount} produkt${itemCount !== 1 ? 'er' : ''} vald${itemCount !== 1 ? 'a' : ''}`);

		// Enable/disable continue button
		$continueBtn.prop('disabled', itemCount === 0);

		// Update cart items display
		if (itemCount === 0) {
			$cartItems.html('<div class="cs-cart-empty">Inga produkter valda</div>');
		} else {
			let html = '';
			cartItems.forEach(function (item) {
				const imageSrc = item.image || '';
				const imageHtml = imageSrc ?
					`<img src="${imageSrc}" alt="${item.name}">` :
					'<div class="cs-product-placeholder"><span class="dashicons dashicons-format-image"></span></div>';
				const truncatedName = truncateTextByWords(item.name, 3);
				html += `
<div class="cs-cart-item" data-product-id="${item.id}">
<div class="cs-cart-item-image">
${imageHtml}
</div>
<div class="cs-cart-item-info">
<div class="cs-cart-item-name">${truncatedName}</div>
<div class="cs-cart-item-supplier">${item.supplier}</div>
</div>
<button class="cs-cart-item-view" data-product-id="${item.id}" type="button">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
<circle cx="12" cy="12" r="3"></circle>
</svg>
</button>
<button class="cs-cart-item-remove" data-product-id="${item.id}" type="button">
<span class="dashicons dashicons-no-alt"></span>
</button>
</div>
`;
			});
			$cartItems.html(html);

			// Attach remove handlers
			$('.cs-cart-item-remove').off('click').on('click', function (e) {
				e.stopPropagation();
				const productId = $(this).data('product-id');
				removeFromCart(productId);
			});
			// View button in cart
			$('.cs-cart-item-view').off('click').on('click', function (e) {
				e.stopPropagation();
				const productId = $(this).data('product-id');
				showProductDetail(productId);
			});
		}

		// Update global selectedProducts for backward compatibility
		window.selectedProducts = cartItems;
		// Save selected products to server for Sales Material tab
		saveSelectedProductsToServer();
	// Save selected products to server for Sales Material tab
	function saveSelectedProductsToServer() {
		if (!window.selectedProducts) return;
		const productIds = window.selectedProducts.map(p => parseInt(p.id));
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_save_selected_products',
				nonce: csAjax.nonce,
				product_ids: JSON.stringify(productIds)
			},
			success: function(response) {
				// Products saved successfully
			},
			error: function(xhr, status, error) {
				// Error saving products
			}
		});
	}
	}

	// Initialize cart widget interactions
	function initCartWidget() {
		// FAB click (Expand) - Product tab only
		$('#tab-assign-product .cs-cart-fab').off('click').on('click', function () {
			$('#cs-cart-widget').removeClass('collapsed');
		});

		// Minimize click (Collapse) - Product tab only
		$('#tab-assign-product .cs-cart-minimize').off('click').on('click', function (e) {
			e.stopPropagation();
			$('#cs-cart-widget').addClass('collapsed');
		});

		// Continue to Manage Sellers button
		$('#cs-continue-to-manage').off('click').on('click', function () {
			if (cartItems.length === 0) {
				showNotification('Vänligen välj minst en produkt', 'error');
				return;
			}

			// Switch to manage sellers tab
			$('.cs-tab-item[data-tab="manage-children"]').trigger('click');
			showNotification('Gå till Hantera säljare för att tilldela produkter', 'info');
		});

		// Load cart from storage
		loadCartFromStorage();
	}

	// ============================================
	// NOTIFICATION SYSTEM
	// ============================================

	function showNotification(message, type = 'success') {
		const notificationClass = type === 'success' ? 'cs-notification-success' :
			type === 'error' ? 'cs-notification-error' :
				'cs-notification-info';

		const iconClass = type === 'success' ? 'dashicons-yes-alt' :
			type === 'error' ? 'dashicons-warning' :
				'dashicons-info';

		const notification = $(`
<div class="cs-notification ${notificationClass}">
<span class="dashicons ${iconClass}"></span>
<span>${message}</span>
</div>
`);

		// Remove any existing notifications
		$('.cs-notification').remove();

		$('body').append(notification);

		setTimeout(function () {
			notification.addClass('show');
		}, 10);

		setTimeout(function () {
			notification.removeClass('show');
			setTimeout(function () {
				notification.remove();
			}, 300);
		}, 3000);
	}

	// ============================================
	// STATS UPDATE FUNCTIONALITY
	// ============================================

	function triggerStatsUpdate() {
		$(document).trigger('cs_sale_added', { timestamp: Date.now() });

		if (typeof window.triggerStatsUpdate === 'function') {
			window.triggerStatsUpdate();
		}
	}

	// ============================================
	// TAB SWITCHING FUNCTIONALITY
	// ============================================

	function initTabs() {
		$('.cs-tab-item').on('click', function () {
			const tabId = $(this).data('tab');

			$('.cs-tab-item').removeClass('active');
			$(this).addClass('active');

			$('.cs-tab-pane').removeClass('active');
			$('#tab-' + tabId).addClass('active');

			if (tabId === 'assign-product') {
				loadProducts();
			} else if (tabId === 'orders') {
				loadOrders();
			} else if (tabId === 'stats') {
				loadStats();
			} else if (tabId === 'sales-material') {
				// Just call loadSalesMaterials directly - no AJAX needed
				setTimeout(function () {
					loadSalesMaterials();
				}, 100);
			}
		});
	}

	// ============================================
	// PRODUCT SEARCH FUNCTIONALITY
	// ============================================

	// Fetch and populate product categories
	function loadProductCategories() {
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_product_categories',
				nonce: csAjax.nonce
			},
			success: function (response) {
				if (response.success && response.data) {
					const $container = $('#category-filter-container');
					$container.empty();

					// Create collapsible category section
					const $categoryAccordion = $(`
						<div class="cs-category-accordion">
							<div class="cs-category-accordion-header">
								<span class="cs-category-accordion-title">Produktkategorier</span>
								<svg class="cs-category-accordion-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="6 9 12 15 18 9"></polyline>
								</svg>
							</div>
							<div class="cs-category-accordion-content">
								<div class="cs-category-list"></div>
							</div>
						</div>
					`);

					const $categoryList = $categoryAccordion.find('.cs-category-list');

					// Add categories
					response.data.forEach(function (category) {
						$categoryList.append(`
                            <label class="cs-category-checkbox-item">
                                <input type="checkbox" value="${category.slug}" class="cs-category-checkbox">
                                <span class="cs-checkbox-custom"></span>
                                <span class="cs-checkbox-label">${category.name} <span class="cs-count">(${category.count})</span></span>
                            </label>
                        `);
					});

					$container.append($categoryAccordion);
				}
			},
			error: function (xhr, status, error) {
				console.error('Failed to load categories:', error);
				$('#category-filter-container').html('<p class="cs-error">Kunde inte ladda kategorier.</p>');
			}
		});
	}

	// Initialize categories on page load
	loadProductCategories();

	// Category accordion toggle handler
	$(document).on('click', '.cs-category-accordion-header', function() {
		const $accordion = $(this).closest('.cs-category-accordion');
		const $content = $accordion.find('.cs-category-accordion-content');
		const $icon = $(this).find('.cs-category-accordion-icon');
		
		$accordion.toggleClass('active');
		
		if ($accordion.hasClass('active')) {
			$content.slideDown(300);
			$icon.css('transform', 'rotate(180deg)');
		} else {
			$content.slideUp(300);
			$icon.css('transform', 'rotate(0deg)');
		}
	});

	// Category filter change handler (Checkboxes)
	$(document).on('change', '.cs-category-checkbox', function () {
		// Collect all checked categories
		const selectedCategories = [];
		$('.cs-category-checkbox:checked').each(function () {
			selectedCategories.push($(this).val());
		});

		const searchQuery = $('#product-search').val();

		// Show loading state
		$('#products-list').html(`
            <div class="cs-loading-products">
            <span class="dashicons dashicons-update cs-spin"></span>
            <p>Laddar produkter...</p>
            </div>
        `);

		// Reload products with category filter
		loadProductsWithFilter(searchQuery, selectedCategories);
	});

	// Modified product search to include category
	function initProductSearch() {
		$('#product-search').on('input', function () {
			const searchQuery = $(this).val();

			// Collect all checked categories
			const selectedCategories = [];
			$('.cs-category-checkbox:checked').each(function () {
				selectedCategories.push($(this).val());
			});

			// Show loading state
			$('#products-list').html(`
                <div class="cs-loading-products">
                <span class="dashicons dashicons-update cs-spin"></span>
                <p>Söker produkter...</p>
                </div>
            `);

			// Reload products with search and category
			loadProductsWithFilter(searchQuery, selectedCategories);
		});
	}

	// New unified product loading function with filters
	function loadProductsWithFilter(searchQuery = '', categorySlug = '') {
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_search_products',
				nonce: csAjax.nonce,
				search_term: searchQuery,
				category: categorySlug
			},
			success: function (response) {
				// CORRECTED: Access response.data.data (nested structure)
				if (response.success && response.data && response.data.data) {
					displayProducts(response.data.data);

					// Restore cart selections
					cartItems.forEach(function (item) {
						const $card = $(`.cs-product-card[data-id="${item.id}"]`);
						if ($card.length) {
							$card.addClass('selected');
							if ($card.find('.cs-selected-checkmark-circle').length === 0) {
								$card.prepend('<div class="cs-selected-checkmark-circle"></div>');
							}
						}
					});

					attachProductCardHandlers();
				} else {
					console.warn('No products found or invalid response', response);
					$('#products-list').html(`
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                        <p>Inga produkter hittades.</p>
                    </div>
                `);
				}
			},
			error: function (xhr, status, error) {
				console.error('Failed to load products:', error);
				console.error('Response:', xhr.responseText); // Additional debug
				$('#products-list').html(`
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: red;">
                    <p>Fel vid laddning av produkter.</p>
                </div>
            `);
			}
		});
	}

	// Search for products
	function searchProducts(term) {
		$('#products-list').html('<div class="cs-loading-products"><span class="dashicons dashicons-update cs-spin"></span><p>Searching products...</p></div>');

		const data = {
			action: 'cs_search_products',
			nonce: csAjax.nonce,
			search_term: term
		};

		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: data,
			timeout: 10000,
			success: function (response) {

				if (response.success && response.data && response.data.data) {
					displayProducts(response.data.data); // Correctly accessing response.data.data
				} else {
					console.warn("No products found or invalid response", response);
					$('#products-list').html('<div class="cs-no-products"><span class="dashicons dashicons-inbox"></span><p>No products found.</p></div>');
				}
			},
			error: function (xhr, status, error) {
				console.error("Search error:", status, error);

				$('#products-list').html(`
<div class="cs-no-products">
<span class="dashicons dashicons-warning"></span>
<p>Error searching products. ${status === 'timeout' ? 'Request timed out.' : 'Please try again.'}</p>
</div>
`);
			}
		});
	}

	// ============================================
	// LOCAL STORAGE FUNCTIONS
	// ============================================

	function saveSelectedProductToLocalStorage(product) {
		if (product) {
			localStorage.setItem('cs_selected_product', JSON.stringify(product));
		}
	}

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

	// ============================================
	// DISPLAY PRODUCTS
	// ============================================

	function displayProducts(products) {
		if (!products || products.length === 0) {
			$('#products-list').html('<div class="cs-no-products"><span class="dashicons dashicons-inbox"></span><p>No products found.</p></div>');
			return;
		}

		let html = '';
		products.forEach(function (product) {
			// Calculate prices
			let clubSalesPrice;
			if (typeof CS_Child_Manager === 'undefined' || !CS_Child_Manager.is_child_user) {
				clubSalesPrice = Math.floor(parseFloat(product.total_price) / 10) * 10 + 9;
			} else {
				clubSalesPrice = parseFloat(product.total_price);
			}

			// Use RRP from product data if available, otherwise fallback to calculation
			const rrpPrice = product.rrp && parseFloat(product.rrp) > 0 ? parseFloat(product.rrp) : (clubSalesPrice * 1.4);
			const actualPrice = parseFloat(product.price) || 0;
			const vinst = rrpPrice - actualPrice;

// Product debug info available if needed

			// Check if this product is in cart
			const isSelected = cartItems.some(item => item.id === product.id);
			const cardClass = isSelected ? 'cs-product-card selected' : 'cs-product-card';

			const productUrl = product.permalink || `${csAjax.siteUrl}/product/${product.id}`;

			// Truncate title to 3 words
			const words = product.name.split(' ');
			const truncatedTitle = words.length > 3 ? words.slice(0, 3).join(' ') + '...' : product.name;

			// Check if product can be combined
			const canBeCombined = product.can_be_combined === true || product.can_be_combined === 'yes' || product.can_be_combined === 1;

			html += `
<div class="${cardClass}" 
data-id="${product.id}" 
data-name="${product.name}" 
data-price="${actualPrice}" 
data-rrp="${rrpPrice}"
data-total-price="${clubSalesPrice}"
data-sku="${product.sku}"
data-vendor-id="${product.vendor_id || 0}"
data-vendor-name="${product.vendor_name || 'N/A'}"
data-store-name="${product.store_name || 'N/A'}"
data-can-be-combined="${canBeCombined}">

<div class="cs-selected-checkmark-circle"></div>

${canBeCombined ? `
<div class="cs-kombineras-badge">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
</svg>
Kombineras
</div>
` : ''}

<div class="cs-product-image">
${product.image ? `<img src="${product.image}" alt="${product.name}">` : '<div class="cs-product-placeholder"><span class="dashicons dashicons-format-image"></span></div>'}
</div>

<div class="cs-product-info">
<div class="cs-supplier-badge">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
<polyline points="9 22 9 12 15 12 15 22"></polyline>
</svg>
Leverantör: ${product.store_name || 'LAMBES'}
</div>

<h3 class="cs-product-name">${truncatedTitle}</h3>

<div class="cs-product-pricing">
<div class="cs-price-box highlight">
<span class="cs-price-label">NI BETALAR</span>
<span class="cs-price-value">${clubSalesPrice.toFixed(2)} kr</span>
</div>
<div class="cs-price-box">
<span class="cs-price-label">RRP</span>
<span class="cs-price-value">${rrpPrice.toFixed(2)} kr</span>
</div>
</div>

<div class="cs-vinst-box">
<span class="cs-price-label">VINST</span>
<span class="cs-price-value">${vinst.toFixed(2)} kr</span>
</div>

<a href="${productUrl}" class="cs-details-link" onclick="event.stopPropagation();">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
<circle cx="12" cy="12" r="3"></circle>
</svg>
Läs mer om produkten
</a>
</div>
</div>
`;
		});

		$('#products-list').html(html);

		// Attach product card click handlers
		attachProductCardHandlers();

		// Trigger event for restoration of cart state
		$(document).trigger('productsLoaded');
	}
	function attachProductCardHandlers() {
		// Product card click - ONLY for products tab, not kafeteria
		$('#tab-assign-product .cs-product-card').off('click').on('click', function (e) {
			// Don't trigger if clicking the details link
			if ($(e.target).hasClass('cs-details-link') || $(e.target).closest('.cs-details-link').length) {
				e.preventDefault();
				const productId = $(this).data('id');
				window.showProductDetail(productId);
				return false;
			}

			const $card = $(this);
			const productId = $card.data('id');
			const vendorId = $card.data('vendor-id');
			const canBeCombined = $card.data('can-be-combined') === true || $card.data('can-be-combined') === 'true';

			const product = {
				id: productId,
				name: $card.data('name'),
				price: $card.data('price'), // Base price (what you pay)
				rrp: $card.data('rrp'), // Customer RRP price
				total_price: $card.data('total-price') || $card.data('price'), // Swedish calculated price (what you actually pay)
				sku: $card.data('sku'),
				image: $card.find('.cs-product-image img').length ?
					$card.find('.cs-product-image img').attr('src') : null,
				store_name: $card.data('store-name'),
				vendor_id: vendorId,
				can_be_combined: canBeCombined
			};

			// Handle selection based on combination mode
			if ($card.hasClass('selected')) {
				// Deselecting a product
				$card.removeClass('selected');
				removeFromCart(productId);

				// If this was a combinable product, check if we should exit combination mode
				if (canBeCombined) {
					checkCombinationModeStatus();
				}
			} else {
				// Selecting a product

				// Check if cart has items
				if (cartItems.length > 0) {
					const firstItem = cartItems[0];

					// If trying to select from different vendor, replace cart
					if (currentVendorId !== null && currentVendorId !== vendorId) {
						clearCartAndStartFresh(vendorId, product, $card, canBeCombined);
						return;
					}

					// If cart has non-combinable item (same vendor), replace it with new product
					if (!firstItem.can_be_combined && vendorId === currentVendorId) {
						// Remove the existing non-combiner
						replaceCartWithNewProduct(vendorId, product, $card, canBeCombined);
						return;
					}

					// If cart has combinable items and trying to add non-combinable (same vendor)
					if (firstItem.can_be_combined && !canBeCombined && vendorId === currentVendorId) {
						// Replace all combiners with this single non-combiner
						replaceCartWithNewProduct(vendorId, product, $card, canBeCombined);
						return;
					}

					// If in combination mode and trying to add non-combinable
					if (combinationMode && !canBeCombined) {
						showNotification('Avsluta kombinering först innan du väljer andra produkter', 'error');
						return;
					}
				}

				// If selecting a combinable product
				if (canBeCombined) {
					// Enter combination mode BEFORE adding to cart
					enterCombinationMode(vendorId);
				} else {
					// Selecting a non-combinable product
					// This should only happen if cart is empty (previous checks prevent otherwise)
					currentVendorId = vendorId;
				}

				$card.addClass('selected');
				if ($card.find('.cs-selected-checkmark-circle').length === 0) {
					$card.prepend('<div class="cs-selected-checkmark-circle"></div>');
				}
				addToCart(product);
				assignProductToAllChildren(productId);
			}

			updateSelectedProductsList();
		});

		// Details link click
		$('.cs-details-link').off('click').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			const productId = $(this).closest('.cs-product-card').data('id');
			window.showProductDetail(productId);
			return false;
		});
	}

	/**
 * Clear cart and start fresh with a product from new vendor
 */
	function clearCartAndStartFresh(vendorId, product, $card, canBeCombined) {
		// Get current cart items for notification
		const oldVendor = cartItems.length > 0 ? cartItems[0].supplier : '';

		// Clear all selections visually
		$('.cs-product-card').removeClass('selected');

		// Clear cart
		cartItems = [];

		// Exit combination mode if active
		if (combinationMode) {
			exitCombinationMode();
		}

		// Set new vendor
		currentVendorId = vendorId;

		// If the new product is combinable, enter combination mode FIRST
		if (canBeCombined) {
			enterCombinationMode(vendorId);
		}

		// Then add the new product
		$card.addClass('selected');
		if ($card.find('.cs-selected-checkmark-circle').length === 0) {
			$card.prepend('<div class="cs-selected-checkmark-circle"></div>');
		}
		addToCart(product);
		updateSalesMaterialTab(product.id);
		assignProductToAllChildren(product.id);

		// Save and update
		saveCartToStorage();
		updateCartWidget();
		updateSelectedProductsList();

		// Show notification
		showNotification(`Varukorg rensad. Nu väljer du från ${product.store_name}`, 'info');
	}

	/**
 * Replace existing cart items with new product (same vendor)
 */
	function replaceCartWithNewProduct(vendorId, product, $card, canBeCombined) {
		// Store old product names for notification
		const oldProducts = cartItems.map(item => item.name).join(', ');

		// Clear all selections visually
		$('.cs-product-card').removeClass('selected');

		// Clear cart
		cartItems = [];

		// Exit combination mode if active
		if (combinationMode) {
			exitCombinationMode();
		}

		// Keep the same vendor
		currentVendorId = vendorId;

		// If the new product is combinable, enter combination mode FIRST
		if (canBeCombined) {
			enterCombinationMode(vendorId);
		}

		// Then add the new product
		$card.addClass('selected');
		if ($card.find('.cs-selected-checkmark-circle').length === 0) {
			$card.prepend('<div class="cs-selected-checkmark-circle"></div>');
		}
		addToCart(product);
		updateSalesMaterialTab(product.id);
		assignProductToAllChildren(product.id);

		// Save and update
		saveCartToStorage();
		updateCartWidget();
		updateSelectedProductsList();

		// Show notification
		showNotification(`Tidigare produkt ersatt med ${product.name}`, 'success');
	}

	/**
 * Check if a product can be selected based on current cart state
 */
	function canSelectProduct(vendorId, canBeCombined) {
		// If cart is empty, always allow
		if (cartItems.length === 0) {
			return true;
		}

		// If in combination mode, only allow combinable products from same vendor
		if (combinationMode) {
			return canBeCombined && vendorId === currentVendorId;
		}

		// Check if cart has any combinable products
		const hasCombinable = cartItems.some(item => item.can_be_combined);

		// If cart has combinable products, can only add more combinable from same vendor
		if (hasCombinable) {
			return canBeCombined && vendorId === currentVendorId;
		}

		// Otherwise, just check vendor matches
		return currentVendorId === null || vendorId === currentVendorId;
	}

	/**
 * Enter combination mode - show only combinable products from same vendor
 */
	function enterCombinationMode(vendorId) {
		if (combinationMode && currentVendorId === vendorId) {
			return; // Already in combination mode for this vendor
		}

		combinationMode = true;
		currentVendorId = vendorId;
		// Hide non-combinable products immediately
		$('.cs-product-card').each(function () {
			const $card = $(this);
			const cardVendorId = $card.data('vendor-id');
			const cardCanBeCombined = $card.data('can-be-combined') === true || $card.data('can-be-combined') === 'true';

			if (!cardCanBeCombined || cardVendorId !== vendorId) {
				$card.hide();
			} else {
				$card.show(); // Make sure combinable products from same vendor are visible
			}
		});

		// Show UI elements
		showCancelCombinationButton();
		showCombinationModeInfo();
	}

	/**
 * Exit combination mode - show all products again
 */
	function exitCombinationMode() {
		if (!combinationMode) return; // Not in combination mode
		combinationMode = false;

		// Show all products again
		$('.cs-product-card').fadeIn(300);

		// Hide "Avbryt kombinering" button
		hideCancelCombinationButton();

		// Hide info message
		hideCombinationModeInfo();

		// If cart is now empty, reset vendor
		if (cartItems.length === 0) {
			currentVendorId = null;
		}
	}
	/**
 * Check if we should stay in or exit combination mode
 */
	function checkCombinationModeStatus() {
		const hasCombinable = cartItems.some(item => item.can_be_combined);

		if (!hasCombinable && combinationMode) {
			exitCombinationMode();
		}

		if (cartItems.length === 0) {
			currentVendorId = null;
		}
	}

	/**
 * Show cancel combination button
 */
	function showCancelCombinationButton() {
		// Remove existing button if any
		$('#cs-cancel-combination-btn').remove();

		// Create and insert button
		const $button = $(`
<div id="cs-cancel-combination-btn" class="cs-combination-notice" style="display: none;">
<div class="cs-combination-notice-content">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
</svg>
<span>Kombinationsläge aktiverat</span>
<button class="cs-cancel-combination-link">Avbryt kombinering</button>
</div>
</div>
`);

		// Insert before products grid
		$('.cs-products-grid').before($button);

		// Fade in
		$button.fadeIn(300);

		// Attach click handler
		$('.cs-cancel-combination-link').off('click').on('click', function (e) {
			e.preventDefault();
			cancelCombination();
		});
	}

	/**
 * Hide cancel combination button
 */
	function hideCancelCombinationButton() {
		$('#cs-cancel-combination-btn').fadeOut(300, function () {
			$(this).remove();
		});
	}

	/**
 * Show combination mode info message
 */
	function showCombinationModeInfo() {
		// Remove existing info if any
		$('#cs-combination-info').remove();

		const $info = $(`
<div id="cs-combination-info" class="cs-info-banner cs-combination-info" style="display: none;">
<div class="cs-info-icon">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
<circle cx="12" cy="12" r="10"></circle>
<line x1="12" y1="16" x2="12" y2="12"></line>
<line x1="12" y1="8" x2="12.01" y2="8"></line>
</svg>
</div>
<div class="cs-info-text">
<strong>Kombinationsläge:</strong> 
Välj fler produkter som kan kombineras från samma leverantör.
</div>
</div>
`);

		// Insert before products grid
		$('.cs-products-grid').before($info);

		// Fade in
		$info.fadeIn(300);
	}

	/**
 * Hide combination mode info message
 */
	function hideCombinationModeInfo() {
		$('#cs-combination-info').fadeOut(300, function () {
			$(this).remove();
		});
	}

	/**
 * Cancel combination - deselect all combinable products and exit mode
 */
	function cancelCombination() {
		// Deselect all combinable products
		const combinableProducts = cartItems.filter(item => item.can_be_combined);

		combinableProducts.forEach(product => {
			removeFromCart(product.id);
		});

		// Exit combination mode
		exitCombinationMode();

		// Update display
		updateSelectedProductsList();

		showNotification('Kombinering avbruten', 'info');
	}


	// Load all products
	function loadProducts() {
		$('#products-list').html('<div class="cs-loading-products"><span class="dashicons dashicons-update cs-spin"></span><p>Loading products...</p></div>');
		searchProducts('');
	}

	// ============================================
	// ASSIGN PRODUCT TO CHILDREN
	// ============================================

	function assignProductToAllChildren(productId) {
		if (!productId) {
			console.error("Invalid product ID:", productId);
			return;
		}

		if (typeof csChildAjax !== 'undefined') {
			const requestData = {
				action: 'cciu_assign_product',
				nonce: csChildAjax.nonce,
				child_id: 'ALL',
				product_id: productId
			};

			$.ajax({
				url: csAjax.ajaxurl,
				type: 'POST',
				data: requestData,
				beforeSend: function () {
					console.log("Sending request to assign product to all children");
				},
				success: function (response) {
					console.log("Assignment response:", response);
					if (response.success) {
						console.log('Product assigned to all children successfully!');
						
						// Reload sales material tab if it's active
						if ($('[data-tab="sales-material"]').hasClass('active')) {
							console.log('📦 Reloading sales material after assignment...');
							setTimeout(function() {
								if (typeof window.updateSalesMaterialTab === 'function') {
									window.updateSalesMaterialTab(productId);
								}
							}, 500);
						}
					} else {
						console.error('Error response:', response);
					}
				},
				error: function (xhr, status, error) {
					console.error('AJAX Error:', status, error);
				}
			});
		} else {
			console.warn('Child management functionality not available.');
		}
	}

	// ============================================
	// HELPER FUNCTIONS
	// ============================================

	window.updateSelectionVisuals = function () {
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
	// ============================================
	// QUANTITY CHANGE LISTENER
	// ============================================

	function initQuantityChangeListener() {
		$('#product_quantity').on('input', function (e) {
			const $input = $(this);
			const rawValue = $input.val();

			if (rawValue === '' || rawValue === '0') {
				$input.val('');
				$('#total_amount').val('0.00');

				if (cartItems.length > 0) {
					cartItems[0].quantity = 0;
					updateSelectedProductsList();
				}
				return;
			}

			const newQuantity = parseInt(rawValue, 10);

			if (isNaN(newQuantity) || newQuantity < 1) {
				$input.val('');
				$('#total_amount').val('0.00');

				if (cartItems.length > 0) {
					cartItems[0].quantity = 0;
					updateSelectedProductsList();
				}
				return;
			}

			if (cartItems.length > 0) {
				cartItems[0].quantity = newQuantity;
				updateSelectedProductsList();
			}
		}).on('blur', function () {
			const $input = $(this);
			const rawValue = $input.val();

			if (rawValue === '' || rawValue === '0') {
				$input.val('');
				$('#total_amount').val('0.00');

				if (cartItems.length > 0) {
					cartItems[0].quantity = 0;
					updateSelectedProductsList();
				}
			}
		});

		// Add quantity change handlers for other quantity inputs
		$('.cs-quantity-input').on('change', function () {
			const productIndex = $(this).data('product-index');
			const newQuantity = parseInt($(this).val(), 10) || 1;

			$(this).val(newQuantity);

			if (window.selectedProducts[productIndex]) {
				window.selectedProducts[productIndex].quantity = newQuantity;
				updateSelectedProductsList();
			}
		});
	}



	// ============================================
	// PREPARE SALE SUBMISSION
	// ============================================

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

	// ============================================
	// SALE FORM SUBMISSION
	// ============================================

	function initSaleForm() {
		let isSubmitting = false;

		$('#cs-order-form').off('submit').on('submit', function (e) {
			e.preventDefault();
			console.log('🔴 SUBMIT HANDLER #1 EXECUTING');

			if (isSubmitting || $(this).data('child-submission')) {
				return false;
			}

			if (!cartItems || cartItems.length === 0) {
				alert('Please select at least one product');
				return false;
			}

			const quantity = $('#product_quantity').length ?
				$('#product_quantity').val() :
				(cartItems[0]?.quantity || 1);

			const productsWithQuantity = cartItems.map(product => ({
				...product,
				quantity: quantity
			}));

		// Calculate total amount (what YOU pay - Swedish calculated price)
		const totalAmount = productsWithQuantity.reduce((sum, p) => {
			const price = parseFloat(p.total_price || p.price) || 0;
			const qty = parseInt(p.quantity) || 1;
			return sum + (price * qty);
		}, 0);

		// Check pricing mode - if custom, use the manual total_amount value
		const pricingMode = $('.cs-pricing-card-active').data('pricing-mode') || 'rrp';
		let customerPays;
		
		if (pricingMode === 'custom') {
			// Use the custom price entered by user
			customerPays = parseFloat($('#total_amount').val()) || 0;
		} else {
			// Calculate customer pays from RRP (default)
			customerPays = productsWithQuantity.reduce((sum, p) => {
				const rrp = parseFloat(p.rrp) || 0;
				const qty = parseInt(p.quantity) || 1;
				return sum + (rrp * qty);
			}, 0);
		}

		const formData = {
			action: 'cs_add_sale',
			nonce: csAjax.nonce,
			customer_name: $('#customer_name').val(),
			email: $('#email').val(),
			phone: $('#phone').val(),
			address: $('#address').val(),
			total_amount: totalAmount.toFixed(2),
			customer_pays: customerPays.toFixed(2),
			sale_date: $('#sale_date').val() || new Date().toISOString().split('T')[0],
			notes: $('#notes').val(),
			products: JSON.stringify(productsWithQuantity)
		};

		isSubmitting = true;

			$.ajax({
				url: csAjax.ajaxurl,
				type: 'POST',
				data: formData,
				beforeSend: function () {
					$('.cs-submit-btn').text('Processing...').prop('disabled', true);
					console.log('Starting sale submission in main script');
				},
				success: function (response) {
					console.log('Sale submission response in main script:', response);

					isSubmitting = false;

					if (response.success) {
						triggerStatsUpdate();

						let successMessage = 'Sale added successfully!';
						const emailField = $('#email').val();
						if (emailField) {
							successMessage += ' A confirmation email has been sent to ' + emailField;
						}
						alert(successMessage);

						// Clear only customer-specific fields, keep products for quick multi-order entry
						$('#customer_name').val('');
						$('#phone').val('');
						$('#email').val('');
						$('#address').val('');
						$('#notes').val('');
						// Keep cartItems intact for rapid next order
						// cartItems = [];
						// saveCartToStorage();
						// updateCartWidget();
						// updateSelectedProductsList();

						if (typeof getCurrentSalesStats === 'function') {
							getCurrentSalesStats();
						}
					} else {
						console.error('Sale submission error:', response.data);
						alert('Error: ' + (response.data || 'Could not add sale'));
					}

					$('.cs-submit-btn').text('Add Sale').prop('disabled', false);
				},
				error: function (xhr, status, error) {
					isSubmitting = false;
					console.error('Sale submission AJAX error:', status, error);
					console.error('Response:', xhr.responseText);

					alert('Error: Could not add sale. Please try again.');
					$('.cs-submit-btn').text('Add Sale').prop('disabled', false);
				}
			});

			return false;
		});
	}

	// ============================================
	// LOAD ORDERS
	// ============================================

	function loadOrders() {
		$('#orders-list').html('<tr><td colspan="9" class="cs-loading">Loading orders...</td></tr>');

		const status = $('#order-status-filter').val();
		const userFilter = $('#order-user-filter').val() || '';

		let showDeleted = 'no';
		let actualStatus = status;

		if (status === 'deleted') {
			showDeleted = 'yes';
			actualStatus = '';
		}

		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_sales',
				nonce: csAjax.nonce,
				status: actualStatus,
				user_filter: userFilter,
				show_deleted: showDeleted
			},
			success: function (response) {

				if (response.success && response.data.sales) {
					displayOrders(response.data.sales, showDeleted === 'yes');

					// ADD THIS LINE: Update stats cards
					updateOrdersStats(response.data.sales);
					// ADD THIS LINE:
					updatePendingOrdersCount();
					// Load package overview for pending orders
					loadPackageOverview();
				} else {
					const emptyMessage = showDeleted === 'yes' ? 'No deleted orders found' : 'No orders found';
					$('#orders-list').html('<tr><td colspan="9">' + emptyMessage + '</td></tr>');

					// Reset stats to zero
					updateOrdersStats([]);
				}
			},
			error: function (xhr, status, error) {
				console.error('Error loading orders:', status, error);
				$('#orders-list').html('<tr><td colspan="9">Error loading orders. Please try again.</td></tr>');
			}
		});
	}
	// Update pending orders count
	function updatePendingOrdersCount() {

		jQuery.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_sales',  // ❌ This is correct action
				nonce: csAjax.nonce,
				status: '',
				user_filter: '',
				show_deleted: 'no'
			},
			success: function (response) {
				if (response.success && response.data && response.data.sales) {
					// Count pending orders (exclude deleted)
					const pendingCount = response.data.sales.filter(order =>
						!order.is_deleted && (order.status === 'pending' || order.status === 'Väntande')
					).length;
					jQuery('#pending-orders-count').text(pendingCount);

					// Update button state
					const $button = jQuery('#klarna-checkout-selected-btn');
					if (pendingCount === 0) {
						$button.prop('disabled', true).css('opacity', '0.5');
					} else {
						$button.prop('disabled', false).css('opacity', '1');
					}
				}
			},
			error: function (xhr, status, error) {
				// Error updating pending count
			}
		});
	}


	// Call this when orders are loaded
	jQuery(document).ready(function () {
		updatePendingOrdersCount();

		// Update every 30 seconds
		setInterval(updatePendingOrdersCount, 30000);
	});

	// ============================================
	// PACKAGE OVERVIEW
	// ============================================

	function loadPackageOverview() {
		console.log('📦 Loading package overview...');
		jQuery.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_package_overview',
				nonce: csAjax.nonce
			},
			success: function (response) {
				console.log('📦 Package overview response:', response);
				if (response.success && response.data) {
					displayPackageOverview(response.data.packages, response.data.total_products);
				} else {
					console.error('❌ Package overview failed:', response);
				}
			},
			error: function (xhr, status, error) {
				console.error('❌ Error loading package overview:', {
					status: status,
					error: error,
					responseText: xhr.responseText
				});
			}
		});
	}

	function displayPackageOverview(packages, totalProducts) {
		const $grid = $('#cs-package-grid');
		const $container = $('.cs-package-overview-container');
		
		// If no packages, hide the overview
		if (!packages || packages.length === 0) {
			$container.hide();
			return;
		}
		
		// Show container
		$container.show();
		
		// Calculate total boxes
		let totalBoxes = 0;
		packages.forEach(function(product) {
			totalBoxes += product.boxes_needed;
		});
		
		// Update header counts
		$('#cs-total-packages').text(totalBoxes + ' paket');
		$('#cs-package-count').text(totalProducts + ' produkter');
		
		// Build HTML
		let html = '';
		packages.forEach(function(product) {
			html += '<div class="cs-package-item">';
			html += '  <div class="cs-package-item-header">';
			html += '    <div class="cs-package-item-icon">';
			html += '      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
			html += '        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>';
			html += '        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>';
			html += '        <line x1="12" y1="22.08" x2="12" y2="12"></line>';
			html += '      </svg>';
			html += '    </div>';
			html += '    <div class="cs-package-item-name">' + product.product_name + '</div>';
			html += '  </div>';
			html += '  <div class="cs-package-item-stats">';
			html += '    <div class="cs-package-stat-row">';
			html += '      <span class="cs-package-stat-label">Sålt:</span>';
			html += '      <span class="cs-package-stat-value sold">' + product.sold + ' st</span>';
			html += '    </div>';
			html += '    <div class="cs-package-stat-row">';
			html += '      <span class="cs-package-stat-label">Förpackning:</span>';
			html += '      <span class="cs-package-stat-value packaging">' + product.packaging + ' st (' + product.boxes_needed + ' × ' + product.box_size + ')</span>';
			html += '    </div>';
			html += '    <div class="cs-package-stat-row">';
			html += '      <span class="cs-package-stat-label">Över:</span>';
			html += '      <span class="cs-package-stat-value extra">+' + product.extra + ' st</span>';
			html += '    </div>';
			html += '  </div>';
			html += '</div>';
		});
		
		$grid.html(html);
	}

	// Toggle collapse functionality
	$(document).on('click', '#cs-package-toggle, .cs-package-header', function(e) {
		if ($(e.target).closest('button').length && !$(e.target).is('.cs-package-header')) {
			return;
		}
		$('.cs-package-overview-container').toggleClass('collapsed');
	});

	// ============================================
	// DISPLAY ORDERS
	// ============================================

	function displayOrders(orders, isShowingDeleted = false) {
		if (!orders || orders.length === 0) {
			const emptyMessage = isShowingDeleted ? 'No deleted orders found' : 'No orders found';
			$('#orders-list').html('<tr><td colspan="9">' + emptyMessage + '</td></tr>');
			return;
		}

		let html = '';
		orders.forEach(function (order, index) {
			// Debug first order to see data structure
			if (index === 0) {
				console.log('🔍 First Order Data:', {
					id: order.id,
					customer_name: order.customer_name,
					user_name: order.user_name,
					team_name: order.team_name,
					swish_number: order.swish_number,
					is_child: order.is_child
				});
			}
			
			// Debug ALL child orders to see swish numbers
			if (order.is_child) {
				console.log(`👶 Child Order #${order.id}:`, {
					seller: order.user_name,
					team: order.team_name,
					swish_received: order.swish_number,
					swish_type: typeof order.swish_number,
					swish_empty: !order.swish_number || order.swish_number === ''
				});
			}
			
			const statusClass = 'cs-status-' + order.status;
			const isChild = order.is_child ? ' (Child)' : '';
			const isDeleted = order.is_deleted;

			const customerPays = parseFloat(order.customer_pays || 0);
			const baseAmount = parseFloat(order.base_amount || order.sale_amount || 0); // "Ni betalar" - what YOU pay
			const profit = parseFloat(order.profit || 0);
			const profitClass = profit >= 0 ? 'cs-profit-positive' : 'cs-profit-negative';

			// Debug log for first order
			const rowClass = isDeleted ? 'cs-deleted-row' : '';

			let statusDisplay = order.status;
			if (isDeleted) {
				statusDisplay = 'Deleted';
				if (order.deleted_at) {
					statusDisplay += ' (' + new Date(order.deleted_at).toLocaleDateString() + ')';
				}
			} else {
				switch (order.status) {
					case 'pending': statusDisplay = 'Pending'; break;
					case 'ordered_from_supplier': statusDisplay = 'Ordered from Supplier'; break;
					case 'completed': statusDisplay = 'Completed'; break;
				}
			}

			let actionButtons = '';

			if (isDeleted) {
				actionButtons = `
<button type="button" class="cs-order-action cs-restore-order" data-id="${order.id}">
<span class="dashicons dashicons-undo"></span> Restore
</button>
<button type="button" class="cs-order-action cs-permanently-delete-order" data-id="${order.id}">
<span class="dashicons dashicons-trash"></span> Delete Permanently
</button>
`;
			} else {
actionButtons = `<button type="button" class="cs-order-action cs-view-order" data-id="${order.id}">Visa</button>`;
actionButtons += `<button type="button" class="cs-order-action cs-delete-order" data-id="${order.id}">Radera</button>`;

if (order.status === 'ordered_from_supplier') {
const swishNum = order.swish_number || '';
console.log(`📲 Order #${order.id} Swish QR Button Data:`, {
	is_child: order.is_child,
	team_name: order.team_name,
	user_name: order.user_name,
	swish_from_backend: order.swish_number,
	swish_being_used: swishNum,
	swish_is_empty: swishNum === '',
	button_data_attribute: `data-swish-number="${swishNum}"`
});
actionButtons += `<button type="button" class="cs-order-action cs-swish-qr" data-id="${order.id}" data-amount="${customerPays}" data-order-number="${order.id}" data-swish-number="${swishNum}" data-customer-name="${order.customer_name || ''}" data-team-name="${order.team_name || order.user_name || ''}"><img src="https://subdomain.klubbforsaljning.se/wp-content/uploads/2026/02/swish-image.png" alt="Swish" style="height:18px;vertical-align:middle;" /> Swish QR</button>`;
actionButtons += `<button type="button" class="cs-order-action cs-complete-order" data-id="${order.id}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><polyline points="20 6 9 17 4 12"></polyline></svg>Slutför</button>`;
}
}

html += `
<tr data-order-id="${order.id}" data-date="${order.sale_date}" data-seller="${order.user_name}${isChild}" class="${rowClass}">
<td>${order.id}</td>
<td>${order.sale_date}</td>
<td>${order.customer_name}</td>
<td>${order.user_name}${isChild}</td>
<td>${customerPays.toFixed(2)} ${csAjax.currency}</td>
<td>${baseAmount.toFixed(2)} ${csAjax.currency}</td>
<td class="${profitClass}">${profit.toFixed(2)} ${csAjax.currency}</td>
<td><span class="cs-order-status ${statusClass}">${statusDisplay}</span></td>
<td class="cs-order-actions">${actionButtons}</td>
</tr>
`;
		});

		$('#orders-list').html(html);
		attachOrderActionHandlers();
	}

	// Calculate and display orders statistics
	function updateOrdersStats(orders) {
		const currency = csAjax.currency || 'SEK';

		// Calculate totals
		let totalOrders = orders.length;
		let totalSales = 0;
		let totalProfit = 0;

		orders.forEach(function (order) {
			// Only count non-deleted orders
			if (!order.is_deleted) {
				const customerPays = parseFloat(order.customer_pays || 0);
				const baseAmount = parseFloat(order.base_amount || order.sale_amount || 0);
				const profit = customerPays - baseAmount;

				totalSales += customerPays;
				totalProfit += profit;
			}
		});

		// Update the stat cards with animation
		animateStatValue('#cs-orders-total-count', totalOrders, '');
		animateStatValue('#cs-orders-total-sales', totalSales.toFixed(2), currency);
		animateStatValue('#cs-orders-total-profit', totalProfit.toFixed(2), currency);
	}

	// Animate stat value updates
	function animateStatValue(selector, value, suffix) {
		const $element = $(selector);

		// Remove loading spinner
		$element.find('.cs-loading-spinner').remove();

		// If it's a number, animate it
		if (typeof value === 'number' || !isNaN(parseFloat(value))) {
			const numValue = parseFloat(value) || 0;
			const currentValue = parseFloat($element.text().replace(/[^\d.-]/g, '')) || 0;

			$({ value: currentValue }).animate({ value: numValue }, {
				duration: 1000,
				easing: 'swing',
				step: function () {
					if (suffix) {
						$element.text(Math.round(this.value).toLocaleString() + ' ' + suffix);
					} else {
						$element.text(Math.round(this.value).toLocaleString());
					}
				},
				complete: function () {
					if (suffix) {
						$element.text(numValue.toLocaleString() + ' ' + suffix);
					} else {
						$element.text(numValue.toLocaleString());
					}
				}
			});
		} else {
			$element.text(value + (suffix ? ' ' + suffix : ''));
		}
	}

	// ============================================
	// ORDER ACTION HANDLERS
	// ============================================

	function attachOrderActionHandlers() {
		// View order
		$('.cs-view-order').on('click', function () {
			const orderId = $(this).data('id');
			showOrderDetails(orderId);
		});

		// Mark as delivered
		$('.cs-mark-delivered').on('click', function () {
			const orderId = $(this).data('id');
			const $button = $(this);
			const $row = $(this).closest('tr');

			if (confirm('Mark this order as delivered to customer?')) {
				$.ajax({
					url: csAjax.ajaxurl,
					type: 'POST',
					data: {
						action: 'cs_mark_order_delivered',
						nonce: csAjax.nonce,
						order_id: orderId
					},
					beforeSend: function () {
						$button.text('Marking...').prop('disabled', true);
					},
					success: function (response) {
						if (response.success) {
							$(document).trigger('cs_order_updated', { order_id: orderId });
							triggerStatsUpdate();

							$row.find('.cs-order-status')
								.removeClass('cs-status-ordered_from_supplier cs-status-ordered-from-supplier')
								.addClass('cs-status-completed')
								.text('Delivered to Customer');

							$button.remove();
							alert('Order marked as delivered successfully!');

							if (typeof getCurrentSalesStats === 'function') {
								getCurrentSalesStats();
							}
						} else {
							alert('Error: ' + (response.data || 'Could not mark order as delivered'));
							$button.text('Mark as Delivered').prop('disabled', false);
						}
					},
					error: function () {
						alert('Error: Could not mark order as delivered. Please try again.');
						$button.text('Mark as Delivered').prop('disabled', false);
					}
				});
			}
		});

		// Delete order
		$('.cs-delete-order').on('click', function () {
			const orderId = $(this).data('id');
			const $row = $(this).closest('tr');

			if (confirm('Are you sure you want to delete this order?')) {
				$.ajax({
					url: csAjax.ajaxurl,
					type: 'POST',
					data: {
						action: 'cs_delete_sale',
						nonce: csAjax.nonce,
						sale_id: orderId
					},
					beforeSend: function () {
						$row.find('.cs-delete-order').text('Deleting...').prop('disabled', true);
					},
					success: function (response) {
						if (response.success) {
							$(document).trigger('cs_sale_deleted', { sale_id: orderId });
							triggerStatsUpdate();

							$row.fadeOut(300, function () {
								$(this).remove();

								if (typeof getCurrentSalesStats === 'function') {
									getCurrentSalesStats();
								}

								if ($('#orders-list tr').length === 0) {
									$('#orders-list').html('<tr><td colspan="9">No orders found</td></tr>');
								}
							});

							alert('Order deleted successfully');
						} else {
							alert('Error: ' + (response.data || 'Could not delete order'));
							$row.find('.cs-delete-order').text('Delete').prop('disabled', false);
						}
					},
					error: function () {
						alert('Error: Could not delete order. Please try again.');
						$row.find('.cs-delete-order').text('Delete').prop('disabled', false);
					}
				});
			}
		});

		// Restore order
		$('.cs-restore-order').on('click', function () {
			const orderId = $(this).data('id');
			const $row = $(this).closest('tr');

			if (confirm('Are you sure you want to restore this order?')) {
				$.ajax({
					url: csAjax.ajaxurl,
					type: 'POST',
					data: {
						action: 'cs_restore_sale',
						nonce: csAjax.nonce,
						sale_id: orderId
					},
					beforeSend: function () {
						$row.find('.cs-restore-order').text('Restoring...').prop('disabled', true);
					},
					success: function (response) {
						if (response.success) {
							alert('Order restored successfully');
							loadOrders();
							triggerStatsUpdate();
						} else {
							alert('Error: ' + (response.data || 'Could not restore order'));
							$row.find('.cs-restore-order').html('<span class="dashicons dashicons-undo"></span> Restore').prop('disabled', false);
						}
					},
					error: function () {
						alert('Error: Could not restore order. Please try again.');
						$row.find('.cs-restore-order').html('<span class="dashicons dashicons-undo"></span> Restore').prop('disabled', false);
					}
				});
			}
		});

		// Permanently delete
		$('.cs-permanently-delete-order').on('click', function () {
			const orderId = $(this).data('id');
			const $row = $(this).closest('tr');

			if (confirm('Are you sure you want to permanently delete this order? This action cannot be undone.')) {
				$.ajax({
					url: csAjax.ajaxurl,
					type: 'POST',
					data: {
						action: 'cs_permanently_delete_sale',
						nonce: csAjax.nonce,
						sale_id: orderId
					},
					beforeSend: function () {
						$row.find('.cs-permanently-delete-order').text('Deleting...').prop('disabled', true);
					},
					success: function (response) {
						if (response.success) {
							alert('Order permanently deleted');
							$row.fadeOut(300, function () {
								$(this).remove();

								if ($('#orders-list tr').length === 0) {
									$('#orders-list').html('<tr><td colspan="9">No deleted orders found</td></tr>');
								}
							});
						} else {
							alert('Error: ' + (response.data || 'Could not permanently delete order'));
							$row.find('.cs-permanently-delete-order').html('<span class="dashicons dashicons-trash"></span> Delete Permanently').prop('disabled', false);
						}
					},
					error: function () {
						alert('Error: Could not permanently delete order. Please try again.');
						$row.find('.cs-permanently-delete-order').html('<span class="dashicons dashicons-trash"></span> Delete Permanently').prop('disabled', false);
					}
				});
			}
		});

		// Swish QR
		$('.cs-swish-qr').on('click', function () {
		const $button = $(this);
		console.log('🔵 Button clicked - all data attributes:', $button.data());
		const swishNumber = $button.data('swish-number');
		const amount = $button.data('amount');
		const orderNumber = $button.data('order-number');
		const customerName = $button.data('customer-name');
		const teamName = $button.data('team-name');
		console.log('🔵 Swish QR Click Data:', {
			swishNumber,
			amount,
			orderNumber,
			customerName,
			teamName
		});
		console.log('🔵 swishNumber type:', typeof swishNumber, 'empty?', swishNumber === '' || swishNumber === undefined);
		showSwishQRModal(swishNumber, amount, orderNumber, customerName, teamName);
	});

	// Complete Order (Slutför)
	$('.cs-complete-order').on('click', function () {
		const orderId = $(this).data('id');
		const $button = $(this);
		const $row = $(this).closest('tr');

		if (confirm('Är du säker på att du vill markera denna order som slutförd?')) {
			$.ajax({
				url: csAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'cs_complete_order',
					nonce: csAjax.nonce,
					order_id: orderId
				},
				beforeSend: function () {
					$button.text('Slutför...').prop('disabled', true);
				},
				success: function (response) {
					if (response.success) {
						alert('Order markerad som slutförd!');
						// Reload orders to show updated status
						if (typeof loadOrders === 'function') {
							loadOrders();
						}
					} else {
						alert('Fel: ' + (response.data || 'Kunde inte slutföra ordern'));
						$button.html('✓ Slutför').prop('disabled', false);
					}
				},
				error: function () {
					alert('Ett fel uppstod. Försök igen.');
					$button.html('✓ Slutför').prop('disabled', false);
				}
			});
		}
	});
}

// Swish QR Modal logic - FIXED
window.showSwishQRModal = function(swishNumber, amount, orderNumber, customerName, teamName) {
	console.log('📱 Swish Modal Parameters:', {
		swishNumber,
		amount,
		orderNumber,
		customerName,
		teamName
	});
	console.log('📱 swishNumber type:', typeof swishNumber, 'value:', swishNumber, 'empty?', !swishNumber);
	
	let cleanNumber = String(swishNumber || '').replace(/\D/g, '');
	console.log('📱 After String conversion and regex:', cleanNumber);
	if (cleanNumber.startsWith('07')) {
		cleanNumber = '46' + cleanNumber.substring(1);
		console.log('📱 After 07 → 46 conversion:', cleanNumber);
	}
	console.log('📱 Final cleanNumber being passed:', cleanNumber);

	showLocalSwishQR(cleanNumber, amount, orderNumber, customerName, teamName);
};

window.showLocalSwishQR = function(cleanNumber, amount, orderNumber, customerName, teamName) {
	console.log('🟡 Local QR Parameters:', {
		cleanNumber,
		amount,
		orderNumber,
		customerName,
		teamName
	});
	console.log('🟡 cleanNumber empty?', !cleanNumber, 'length:', cleanNumber ? cleanNumber.length : 0);
	
	const swishUrl = `https://app.swish.nu/1/p/sw/?sw=${cleanNumber}&amt=${parseFloat(amount).toFixed(2)}&msg=Order%20${orderNumber}`;
	console.log('🟡 Generated Swish URL:', swishUrl);
	const container = document.getElementById('swish-qr-container');
	$('#swish-qr-image').hide();
	$('#swish-qr-modal').css('display', 'flex');
	$('#swish-qr-order-number').text(`Order #${orderNumber}`);
	$('#swish-qr-customer-name').text(customerName || '');
	$('#swish-qr-amount').text(`${parseFloat(amount).toFixed(2)} SEK`);
	$('#swish-qr-recipient').text(`Till\n${cleanNumber}`);
	
	// Show debug URL
	const debugHtml = '<strong>Belopp inkluderat i QR-kod</strong><br/>' + 
		parseFloat(amount).toFixed(2) + ' SEK<br/>Order #' + orderNumber + 
		'<br/><br/><small style="color:#666;word-break:break-all;">Debug URL: ' + swishUrl + '</small>';
	$('#swish-qr-amount-included').html(debugHtml);
	
	console.log('🟢 Setting Team Name (Local):', teamName);
	if (teamName) {
		$('#swish-qr-team-name').text(teamName).show();
	} else {
		$('#swish-qr-team-name').hide();
	}
	$('#swish-qr-container').show();
	
	if (window.QRCode) {
		// Clear previous QR
		container.innerHTML = '';
		// Create QR code
		new QRCode(container, {
			text: swishUrl,
			width: 256,
			height: 256,
			colorDark: '#000000',
			colorLight: '#ffffff',
			correctLevel: QRCode.CorrectLevel.H
		});
	} else {
		alert('QR Code library not loaded. Please refresh the page.');
	}
};

// Modal close logic
$(document).on('click', '#swish-qr-close, #swish-qr-close-btn', function() {
	$('#swish-qr-modal').hide();
	$('#swish-qr-image').attr('src', '').hide();
	$('#swish-qr-container').html('').hide();
	$('#swish-qr-info').text('');
	$('#swish-qr-recipient').text('');
});

	// ============================================
	// SHOW ORDER DETAILS MODAL
	// ============================================

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

			// Handle modal close button
			$('.cs-modal-close').on('click', function () {
				$('#cs-order-modal').hide();
			});

			// Close modal when clicking outside
			$(window).on('click', function (event) {
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
			beforeSend: function () {
				$('#cs-modal-body').html('<div class="cs-loading">Loading order details...</div>');
				$('#cs-order-modal').show();
			},
			success: function (response) {
				if (response.success && response.data) {
					const order = response.data;

					// Parse products
					let productsHtml = '';
					let totalAmount = 0;

					if (order.products && Array.isArray(order.products) && order.products.length > 0) {
						order.products.forEach(function (product) {
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
<h3>Beställa #${order.id} Detaljer</h3>
<p>Visa och redigera information för denna beställning</p>
<table class="cs-order-detail-table">
<tr>
<th>Beställningsdatum:</th>
<td>${order.sale_date}</td>
</tr>
<tr>
<th>Status:</th>
<td><span class="cs-order-status cs-status-${order.status}">${order.status}</span></td>
</tr>
<tr>
<th>Kund:</th>
<td>${order.customer_name}</td>
</tr>
<tr>
<th>E-post:</th>
<td>${order.email || 'N/A'}</td>
</tr>
<tr>
<th>Telefon:</th>
<td>${order.phone || 'N/A'}</td>
</tr>
<tr>
<th>Adress:</th>
<td>${order.address || 'N/A'}</td>
</tr>
<tr>
<th>Totalt belopp:</th>
<td>${order.sale_amount} ${csAjax.currency}</td>
</tr>
<tr>
<th>Anmärkningar:</th>
<td>${order.notes || 'No notes'}</td>
</tr>
</table>
</div>

<div class="cs-order-detail-section">
<h3>Produkter</h3>
<div class="cs-order-product-list">
${productsHtml}
</div>
</div>

<div class="cs-modal-actions">
<button class="cs-edit-order-btn" data-order-id="${order.id}">Redigera beställning</button>
</div>
`;

					$('#cs-modal-body').html(orderDetailsHtml);

					// Add click handler for edit button
					$('.cs-edit-order-btn').on('click', function () {
						const editOrderId = $(this).data('order-id');
						populateEditOrderForm(order);
					});
				} else {
					$('#cs-modal-body').html('<p>Error loading order details: ' + (response.data || 'Unknown error') + '</p>');
				}
			},
			error: function () {
				$('#cs-modal-body').html('<p>Error loading order details. Please try again.</p>');
			}
		});
	}

	// ============================================
	// POPULATE EDIT ORDER FORM
	// ============================================

	function populateEditOrderForm(order) {
		// Create edit modal if it doesn't exist
		if ($('#cs-edit-order-modal').length === 0) {
			$('body').append(`
<div id="cs-edit-order-modal" class="cs-modal">
<div class="cs-modal-content">
<span class="cs-edit-modal-close">&times;</span>
<h2>Redigera beställning</h2>

<form id="cs-edit-order-form" class="cs-form">
<input type="hidden" id="edit-order-id" name="order_id">

<div class="cs-form-group">
<label for="edit-customer-name">Kundens namn</label>
<input type="text" id="edit-customer-name" name="customer_name" required>
</div>

<div class="cs-form-group">
<label for="edit-order-status">Order Status</label>
<select id="edit-order-status" name="status" class="cs-status-select">
<option value="pending">Pending</option>
<option value="ordered_from_supplier">Ordered from Supplier</option>
<option value="completed">Delivered to Customer</option>
</select>
</div>

<div class="cs-form-group">
<label for="edit-email">E-post</label>
<input type="email" id="edit-email" name="email">
</div>

<div class="cs-form-group">
<label for="edit-phone">Telefon</label>
<input type="tel" id="edit-phone" name="phone">
</div>

<div class="cs-form-group">
<label for="edit-address">Adress</label>
<textarea id="edit-address" name="address"></textarea>
</div>

<div class="cs-form-group">
<label for="edit-sale-date">Försäljningsdatum</label>
<input type="date" id="edit-sale-date" name="sale_date" required>
</div>

<div class="cs-form-group">
<label for="edit-notes">Anteckningar</label>
<textarea id="edit-notes" name="notes"></textarea>
</div>

<div class="cs-form-actions">
<button type="button" class="cs-cancel-btn">Avbryt</button>
<button type="submit" class="cs-submit-btn">Spara ändringar</button>
</div>
</form>
</div>
</div>
`);

			// Close edit modal handlers
			$(document).on('click', '.cs-edit-modal-close, .cs-cancel-btn', function () {
				$('#cs-edit-order-modal').hide();
			});
		}

		// Populate form with order details
		$('#edit-order-id').val(order.id);
		$('#edit-customer-name').val(order.customer_name);
		$('#edit-email').val(order.email);
		$('#edit-phone').val(order.phone);
		$('#edit-address').val(order.address);
		$('#edit-order-status').val(order.status);

		// Ensure date is in correct format
		const formattedDate = order.sale_date ?
			new Date(order.sale_date).toISOString().split('T')[0] :
			new Date().toISOString().split('T')[0];

		$('#edit-sale-date').val(formattedDate);
		$('#edit-notes').val(order.notes);

		// Form submission handler
		$('#cs-edit-order-form').off('submit').on('submit', function (e) {
			e.preventDefault();

			const formData = {
				action: 'cs_update_order',
				nonce: csAjax.nonce,
				order_id: $('#edit-order-id').val(),
				customer_name: $('#edit-customer-name').val(),
				email: $('#edit-email').val(),
				phone: $('#edit-phone').val(),
				address: $('#edit-address').val(),
				sale_date: $('#edit-sale-date').val(),
				status: $('#edit-order-status').val(),
				notes: $('#edit-notes').val()
			};
			// AJAX call to update order
			$.ajax({
				url: csAjax.ajaxurl,
				method: 'POST',
				data: formData,
				beforeSend: function () {
					$('.cs-submit-btn')
						.prop('disabled', true)
						.text('Updating...');
				},
				success: function (response) {
					if (response.success) {
						// Trigger stats update if indicated
						if (response.data && response.data.stats_updated) {
							$(document).trigger('cs_order_updated', { order_id: formData.order_id });
							triggerStatsUpdate();
						}

						// Close both modals
						$('#cs-edit-order-modal').hide();
						$('#cs-order-modal').hide();

						alert('Order updated successfully');

						// Refresh orders list
						if (typeof loadOrders === 'function') {
							loadOrders();
						}
					} else {
						alert('Error: ' + (response.data || 'Could not update order'));
					}
				},
				error: function () {
					alert('Error: Could not update order. Please try again.');
				},
				complete: function () {
					$('.cs-submit-btn')
						.prop('disabled', false)
						.text('Spara ändringar');
				}
			});
		});

		// Show the edit modal
		$('#cs-edit-order-modal').show();
	}

	// ============================================
	// ORDER FILTERS
	// ============================================

	function initOrderFilters() {
		$('#order-status-filter, #order-user-filter').on('change', function () {
			loadOrders();
		});

		$('#klarna-checkout-selected-btn').on('click', function () {
			const pendingOrders = [];

			$('.cs-orders-table tbody tr').each(function () {
				const status = $(this).find('.cs-order-status').text().trim().toLowerCase();
				const orderId = $(this).find('td:first-child').text().trim();

				if (status === 'pending') {
					pendingOrders.push(orderId);
				}
			});
			if (pendingOrders.length === 0) {
				alert('No pending orders to process');
				return;
			}

			processKlarnaCheckout(pendingOrders);
		});
	}

	// ============================================
	// PROCESS KLARNA CHECKOUT
	// ============================================

	function processKlarnaCheckout(saleIds) {
		$('#klarna-checkout-selected-btn').text('Processing...').prop('disabled', true);

		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_process_klarna',
				nonce: csAjax.nonce,
				sale_ids: saleIds
			},
			timeout: 30000,
			success: function (response) {
				if (response.success && response.data && response.data.redirect_url) {
					if (response.data.stats_updated) {
						triggerStatsUpdate();
					}

					const redirectUrl = response.data.redirect_url;
					window.location.href = redirectUrl;
				} else {
					console.error("Invalid response structure:", response);
					alert('Error: ' + (response.data || 'Could not process orders - invalid response'));
					$('#klarna-checkout-selected-btn').text('Process Orders').prop('disabled', false);
				}
			},
			error: function (xhr, status, error) {
				console.error("AJAX error:", {
					status: status,
					error: error,
					responseText: xhr.responseText
				});

				let errorMessage = 'Error: Could not process orders. Please try again.';

				if (xhr.responseText) {
					try {
						const errorResponse = JSON.parse(xhr.responseText);
						if (errorResponse.data) {
							errorMessage = 'Error: ' + errorResponse.data;
						}
					} catch (e) {
						errorMessage = 'Error: ' + xhr.responseText.substring(0, 100);
					}
				}

				alert(errorMessage);
				$('#klarna-checkout-selected-btn').text('Process Orders').prop('disabled', false);
			}
		});
	}

	// ============================================
	// LOAD STATS
	// ============================================

	function loadStats() {
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_stats',
				nonce: csAjax.nonce
			},
			success: function (response) {
				if (response.success) {
					renderStatsDashboard(response.data);
				}
			}
		});
	}

	function renderStatsDashboard(stats) {
		$('.cs-total-profit').text(stats.total_profit + ' ' + csAjax.currency);
		$('.cs-opportunities').text(stats.opportunities);
		$('.cs-completed-sales').text(stats.completed_sales);

		if ($('#sales-chart').length > 0) {
			initSalesChart();
		}
	}

	function initSalesChart() {
		const ctx = document.getElementById('sales-chart').getContext('2d');

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

	function getCurrentSalesStats() {
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_stats',
				nonce: csAjax.nonce
			},
			success: function (response) {
				if (response.success) {
					$('#current-sales-count').text(response.data.completed_sales);
					$('#current-sales-amount').text(response.data.total_profit + ' ' + csAjax.currency);
				}
			}
		});
	}

	// ============================================
	// KLARNA CHECKOUT INITIALIZATION
	// ============================================

	function initKlarnaCheckout() {
		$('#klarna-checkout-btn').on('click', function () {
			if (cartItems.length === 0) {
				alert('Please select at least one product');
				return;
			}

			// Calculate total amount (what YOU pay - Swedish calculated price)
			const totalAmount = cartItems.reduce((sum, p) => {
				const price = parseFloat(p.total_price || p.price) || 0;
				const qty = parseInt(p.quantity) || 1;
				return sum + (price * qty);
			}, 0);

			// Check pricing mode - if custom, use the manual total_amount value
			const pricingMode = $('.cs-pricing-card-active').data('pricing-mode') || 'rrp';
			let customerPays;
			
			if (pricingMode === 'custom') {
				// Use the custom price entered by user
				customerPays = parseFloat($('#total_amount').val()) || 0;
			} else {
				// Calculate customer pays from RRP (default)
				customerPays = cartItems.reduce((sum, p) => {
					const rrp = parseFloat(p.rrp) || 0;
					const qty = parseInt(p.quantity) || 1;
					return sum + (rrp * qty);
				}, 0);
			}

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
				products: JSON.stringify(cartItems)
			};

			if (!formData.customer_name || !formData.email || !formData.phone || !formData.address) {
				alert('Please fill in all required fields');
				return;
			}

			$.ajax({
				url: csAjax.ajaxurl,
				type: 'POST',
				data: formData,
				beforeSend: function () {
					$('#klarna-checkout-btn').text('Processing...').prop('disabled', true);
				},
				success: function (response) {
					if (response.success && response.data.sale_id) {
						triggerStatsUpdate();
						processKlarnaCheckout([response.data.sale_id]);
					} else {
						alert('Error: ' + (response.data || 'Could not add sale'));
						$('#klarna-checkout-btn').text('Continue to Checkout').prop('disabled', false);
					}
				},
				error: function () {
					alert('Error: Could not add sale. Please try again.');
					$('#klarna-checkout-btn').text('Continue to Checkout').prop('disabled', false);
				}
			});
		});
	}

	// ============================================
	// CHART CONTROLS
	// ============================================

	function initChartControls() {
		$('.cs-chart-period').on('click', function () {
			$('.cs-chart-period').removeClass('active');
			$(this).addClass('active');

			const period = $(this).data('period');
			// Update chart based on period (to be implemented)
		});
	}

	// ============================================
	// ADDRESS AUTOCOMPLETE (GEOAPIFY)
	// ============================================

	function initAddressAutocomplete() {
		if (typeof GeoapifyConfig === 'undefined' || !window.jQuery) {
			console.error('Geoapify configuration or jQuery not loaded');
			return;
		}

		const addressInput = $('#address');

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
			return function (...args) {
				clearTimeout(timeout);
				timeout = setTimeout(() => func.apply(this, args), wait);
			};
		}

		// Fetch address suggestions
		function fetchAddressSuggestions(query) {
			const container = $('.address-suggestions-container');

			if (query.trim().length < 3) {
				container.hide();
				return;
			}

			container.html('<div style="padding:10px;">Searching...</div>').show();

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
				success: function (response) {
					container.empty();

					if (response.results && response.results.length > 0) {
						response.results.forEach(result => {
							$('<div>')
								.addClass('address-suggestion')
								.text(result.formatted)
								.on('click', function () {
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
				error: function () {
					container.html('<div style="padding:10px;">Error fetching suggestions</div>').show();
				}
			});
		}

		// Event listeners
		addressInput
			.attr('autocomplete', 'off')
			.on('input', debounce(function () {
				fetchAddressSuggestions($(this).val());
			}, 300))
			.on('focus', function () {
				const query = $(this).val();
				if (query.trim().length >= 3) {
					fetchAddressSuggestions(query);
				}
			});

		// Close suggestions when clicking outside
		$(document).on('click', function (e) {
			if (!$(e.target).closest('#address, .address-suggestions-container').length) {
				$('.address-suggestions-container').hide();
			}
		});
	}

	// ============================================
	// MOBILE HAMBURGER MENU
	// ============================================

	function initMobileHamburgerMenu() {
		if ($('.cs-mobile-menu-item').length > 0) {
			return;
		}

		const hamburgerHtml = `
<li class="cs-mobile-menu-item">
<button class="cs-hamburger-btn" type="button">
<i class="fas fa-bars"></i>
<span>Menu</span>
</button>
<div class="cs-mobile-tabs-dropdown">
<!-- Tabs will be cloned here -->
</div>
</li>
`;

		$('.cs-tab-list').prepend(hamburgerHtml);

		$('.cs-tab-list .cs-tab-item:not(.cs-mobile-menu-item)').each(function () {
			const $clonedTab = $(this).clone();
			$('.cs-mobile-tabs-dropdown').append($clonedTab);
		});

		$(document).on('click', '.cs-hamburger-btn', function (e) {
			e.stopPropagation();
			const $dropdown = $('.cs-mobile-tabs-dropdown');
			$dropdown.toggleClass('show');

			const $icon = $(this).find('i');
			if ($dropdown.hasClass('show')) {
				$icon.removeClass('fa-bars').addClass('fa-times');
			} else {
				$icon.removeClass('fa-times').addClass('fa-bars');
			}
		});

		$(document).on('click', '.cs-mobile-tabs-dropdown .cs-tab-item', function (e) {
			e.preventDefault();
			const tabId = $(this).data('tab');

			$('.cs-tab-item').removeClass('active');
			$(`.cs-tab-item[data-tab="${tabId}"]`).addClass('active');

			$('.cs-tab-pane').removeClass('active');
			$(`#tab-${tabId}`).addClass('active');

			$('.cs-mobile-tabs-dropdown').removeClass('show');
			$('.cs-hamburger-btn i').removeClass('fa-times').addClass('fa-bars');

			if (tabId === 'assign-product') {
				loadProducts();
			} else if (tabId === 'orders') {
				loadOrders();
			} else if (tabId === 'stats') {
				loadStats();
			} else if (tabId === 'sales-material') {
				setTimeout(function () {
					if (cartItems.length > 0) {
						updateSalesMaterialTab(cartItems[0].id);
					}
				}, 300);
			}
		});

		$(document).on('click', function (e) {
			if (!$(e.target).closest('.cs-mobile-menu-item').length) {
				$('.cs-mobile-tabs-dropdown').removeClass('show');
				$('.cs-hamburger-btn i').removeClass('fa-times').addClass('fa-bars');
			}
		});
	}

	// ============================================
	// PASSWORD GENERATOR
	// ============================================

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
	// ============================================
	// ADD ORDER TAB - USES CART PRODUCTS
	// ============================================

	// Update selected products list when switching to Add Order tab
	function initAddOrderTab() {
		// Update the list whenever we switch to this tab
		$(document).on('click', '.cs-tab-item[data-tab="add-order"]', function () {
			updateSelectedProductsList();
			
			// Load products into dropdown
			if (typeof loadProductsForOrderDropdown === 'function') {
				loadProductsForOrderDropdown();
			}

			// Show/hide notice based on cart
			if (cartItems.length > 0) {
				$('.cs-cart-notice').show();
			} else {
				$('.cs-cart-notice').hide();
			}
		});
	}

	// Override updateSelectedProductsList for Add Order form
	function updateSelectedProductsList() {
		let html = '';

		if (!cartItems || cartItems.length === 0) {
			html = '<div class="cs-empty-selection">Inga produkter i varukorgen. Gå till <strong>Produkter</strong>-fliken för att välja produkter.</div>';
			$('.cs-submit-btn').prop('disabled', true);
			$('.cs-cart-notice').hide();
		} else {
			// Create dropdown for product selection
			html += `
<div class="cs-product-selector-wrapper">
<label for="order-product-select"><?php _e('Välj produkt för denna order', 'club-sales'); ?></label>
<select id="order-product-select" class="cs-product-dropdown">
<option value="">-- Välj en produkt --</option>
`;

			cartItems.forEach(function (product, index) {
				const quantity = product.quantity || 1;
				const isParentUser = typeof CS_Child_Manager === 'undefined' || !CS_Child_Manager.is_child_user;
				const rawPrice = parseFloat(product.price);
				const productPrice = isParentUser ? roundPriceToNearestNine(rawPrice) : rawPrice;

				html += `
<option value="${index}" data-price="${productPrice}" data-name="${product.name}" data-sku="${product.sku}" data-supplier="${product.supplier || product.store_name}">
${product.name} - ${productPrice.toFixed(2)} ${csAjax.currency}
</option>
`;
			});

			html += `
</select>
</div>

<div id="selected-product-display" class="cs-selected-product-display" style="display: none;">
<div class="cs-product-card-compact">
<div class="cs-product-details">
<span class="cs-product-title" id="display-product-name"></span>
<span class="cs-product-meta">SKU: <span id="display-product-sku"></span></span>
<span class="cs-product-meta">Leverantör: <span id="display-product-supplier"></span></span>
</div>
<div class="cs-product-price-display">
<span id="display-product-price"></span>
</div>
</div>
</div>
`;

			$('.cs-cart-notice').show();
		}

		$('#selected-products-list').html(html);

		// Attach dropdown change handler
		$('#order-product-select').on('change', function () {
			const selectedIndex = $(this).val();

			if (selectedIndex === '') {
				$('#selected-product-display').hide();
				$('#total_amount').val('0.00');
				$('.cs-submit-btn').prop('disabled', true);
				return;
			}

			const $option = $(this).find('option:selected');
			const productPrice = parseFloat($option.data('price'));
			const productName = $option.data('name');
			const productSku = $option.data('sku');
			const productSupplier = $option.data('supplier');

			// Update display
			$('#display-product-name').text(productName);
			$('#display-product-sku').text(productSku || 'N/A');
			$('#display-product-supplier').text(productSupplier || 'N/A');
			$('#display-product-price').text(productPrice.toFixed(2) + ' ' + csAjax.currency);

			$('#selected-product-display').fadeIn(300);

			// Calculate total with quantity
			updateOrderTotalFromDropdown();

			$('.cs-submit-btn').prop('disabled', false);
		});

		// Also update when quantity changes
		$('#product_quantity').off('input').on('input', function () {
			updateOrderTotalFromDropdown();
		});
	}

	// New function to calculate total from dropdown selection
	function updateOrderTotalFromDropdown() {
		const selectedIndex = $('#order-product-select').val();

		if (selectedIndex === '') {
			$('#total_amount').val('0.00');
			return;
		}

		const $option = $('#order-product-select option:selected');
		const productPrice = parseFloat($option.data('price'));
		const quantity = parseInt($('#product_quantity').val()) || 1;

		const total = productPrice * quantity;

		$('#total_amount').val(total.toFixed(2));
	}

	function initAddOrderForm() {
		let isSubmitting = false;

		$('#cs-order-form').off('submit').on('submit', function (e) {
			e.preventDefault();
			console.log('🟡 SUBMIT HANDLER #2 EXECUTING');

			if (isSubmitting) {
				return false;
			}

			// ✅ FIX: Check if there are products in orderProducts (from multi-product order system)
			if (orderProducts.length === 0) {
				alert('Vänligen lägg till minst en produkt i ordern');
				return false;
			}

			// ✅ FIX: Use orderProducts directly with total_price included
			const selectedProducts = orderProducts.map(product => {
				console.log('🔍 Product being submitted:', product);
				return {
					id: product.id,
					name: product.name,
					price: product.price,
					rrp: product.rrp,
					total_price: product.total_price || product.price, // ✅ Include total_price
					sku: product.sku,
					quantity: product.quantity || 1, // Default to 1 if missing
					supplier: product.supplier
				};
			});

			console.log('🔍 Final products JSON:', JSON.stringify(selectedProducts));

			// Calculate totals
			const totalAmount = orderProducts.reduce((sum, p) => sum + ((p.total_price || p.price) * (p.quantity || 1)), 0);  // Swedish calculated price (what YOU pay)
			
			// Check pricing mode - if custom, use the manual total_amount value
			const pricingMode = $('.cs-pricing-card-active').data('pricing-mode') || 'rrp';
			let customerPays;
			
			if (pricingMode === 'custom') {
				// Use the custom price entered by user
				customerPays = parseFloat($('#total_amount').val()) || 0;
				console.log('💡 Using CUSTOM pricing mode - customer pays:', customerPays);
			} else {
				// Calculate from RRP (default mode)
				customerPays = orderProducts.reduce((sum, p) => sum + (p.rrp * (p.quantity || 1)), 0);
				console.log('💡 Using RRP pricing mode - customer pays:', customerPays);
			}
			
			console.log('==========================================');
			console.log('💰 JAVASCRIPT CALCULATION:');
			console.log('  totalAmount (NI betalar - Swedish calc):', totalAmount);
			console.log('  customerPays (Kunden betalar - RRP):', customerPays);
			console.log('  Expected profit:', customerPays - totalAmount);
			console.log('==========================================');
			
			const formData = {
				action: 'cs_add_sale',
				nonce: csAjax.nonce,
				customer_name: $('#customer_name').val().trim(),
				email: $('#email').val().trim(),
				phone: $('#phone').val().trim(),
				address: $('#address').val().trim(),
				total_amount: totalAmount.toFixed(2),
				customer_pays: customerPays.toFixed(2), // RRP - what customer pays
				sale_date: $('#sale_date').val(),
				notes: $('#notes').val(),
				products: JSON.stringify(selectedProducts)
			};
			
			console.log('📤 SENDING TO BACKEND:');
			console.log('  total_amount:', formData.total_amount, '← This goes to sale_amount (NI betalar)');
			console.log('  customer_pays:', formData.customer_pays, '← This goes to customer_pays (Kunden betalar)');
			console.log('  Full formData:', formData);
			console.log('==========================================');

			if (!formData.customer_name || !formData.phone || !formData.email || !formData.address) {
				alert('Vänligen fyll i alla obligatoriska fält');
				return false;
			}

			isSubmitting = true;

			$.ajax({
				url: csAjax.ajaxurl,
				type: 'POST',
				data: formData,
				beforeSend: function () {
					$('.cs-submit-btn').text('Behandlar...').prop('disabled', true);
				},
				success: function (response) {
					isSubmitting = false;

					console.log('==========================================');
					console.log('✅ BACKEND RESPONSE:');
					console.log('  Success:', response.success);
					console.log('  Order ID:', response.data?.order_id);
					console.log('  Customer Pays (saved):', response.data?.customer_pays);
					console.log('  Profit (calculated):', response.data?.profit);
					console.log('  Full response:', response);
					console.log('==========================================');

					if (response.success) {
						triggerStatsUpdate();

						alert('Beställning skapad!');

						// Clear only customer-specific fields, keep products for quick multi-order entry
						$('#customer_name').val('');
						$('#phone').val('');
						$('#email').val('');
						$('#address').val('');
						$('#notes').val('');
						
						// Keep orderProducts intact for rapid next order
						// orderProducts = [];
						// window.orderProducts = orderProducts;
						
						// No need to reset display, products remain visible
						// if (typeof renderOrderProducts === 'function') {
						// 	renderOrderProducts();
						// }
						// if (typeof updateOrderSummary === 'function') {
						// 	updateOrderSummary();
						// }

						if (typeof getCurrentSalesStats === 'function') {
							getCurrentSalesStats();
						}

						if (typeof loadOrders === 'function') {
							loadOrders();
						}
					} else {
						console.error('Order submission error:', response);
						alert('Fel: ' + (response.data || 'Kunde inte skapa beställning'));
					}

					$('.cs-submit-btn').text('Lägg till försäljning').prop('disabled', false);
				},
				error: function (xhr, status, error) {
					isSubmitting = false;
					console.error('Order submission AJAX error:', status, error);
					console.error('Response:', xhr.responseText);
					alert('Fel: Kunde inte skapa beställning. Försök igen.');
					$('.cs-submit-btn').text('Lägg till försäljning').prop('disabled', false);
				}
			});

			return false;
		});
	}
	// ============================================
	// SALES MATERIAL TAB FUNCTIONALITY
	// ============================================

	function initSalesMaterialTab() {
		// Load materials when tab is clicked
		$(document).on('click', '.cs-tab-item[data-tab="sales-material"]', function () {
			loadSalesMaterials();
		});

		// Modal close handlers
		$('.cs-modal-close, .cs-modal-overlay').on('click', function () {
			$(this).closest('.cs-material-modal').fadeOut(300);
		});
	}

	function loadSalesMaterials() {
		if (cartItems.length === 0) {
			$('#cs-material-products-grid').html('<div class="cs-material-empty">Inga produkter valda. Gå till Produkter-fliken för att välja produkter.</div>');
			return;
		}

		// Update product count
		$('#cs-products-count').text(`${cartItems.length} produkt${cartItems.length !== 1 ? 'er' : ''} vald${cartItems.length !== 1 ? 'a' : ''}`);

		// Display products from cart
		let productsHtml = '';
		cartItems.forEach(function (product) {
			productsHtml += `
<div class="cs-material-card" data-product-id="${product.id}">
<div class="cs-material-card-image">
${product.image ? `<img src="${product.image}" alt="${product.name}">` : '<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>'}
</div>
<div class="cs-material-card-title">${product.name}</div>
<div class="cs-material-card-price">${product.price} ${csAjax.currency}</div>
</div>
`;
		});

		$('#cs-material-products-grid').html(productsHtml);

		// Load campaign images
		loadCampaignImages();
	}

	function loadCampaignImages() {
		// Mock campaign images for now
		const campaignImages = [
			{ id: 1, title: 'Kampanjmaterial', type: 'PDF', size: '2.4 MB', url: '#' }
		];

		let imagesHtml = '';
		campaignImages.forEach(function (img) {
			imagesHtml += `
<div class="cs-material-card cs-campaign-card" data-image-id="${img.id}">
<div class="cs-material-card-image" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#2196F3" stroke-width="2">
<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
<circle cx="8.5" cy="8.5" r="1.5"></circle>
<polyline points="21 15 16 10 5 21"></polyline>
</svg>
</div>
<div class="cs-material-card-title">${img.title}</div>
<div class="cs-material-card-price">${img.type} • ${img.size}</div>
</div>
`;
		});

		$('#cs-campaign-images-grid').html(imagesHtml);
	}
	// ============================================
	// WCFM EMAIL VERIFICATION REORDER
	// ============================================

	jQuery(document).ready(function ($) {
		var $emailVerification = $('.wcfm_email_verified');
		var $confirmPassword = $('#confirm_pwd');

		if ($emailVerification.length && $confirmPassword.length) {
			$emailVerification.insertAfter($confirmPassword);
		}
	});

	// ============================================
	// VENDOR LOGIN ERROR DISPLAY
	// ============================================

	document.addEventListener('DOMContentLoaded', function () {
		function getUrlParameter(name) {
			name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\');
			var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
			var results = regex.exec(location.search);
			return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
		}

		var loginError = getUrlParameter('login_error');

		if (loginError) {
			var usernameInput = document.getElementById('user-1149d0d');

			if (usernameInput) {
				var errorDiv = document.createElement('div');
				errorDiv.className = 'cs-auth-message cs-auth-error';
				errorDiv.style.cssText = 'padding: 10px 15px; margin: 0 0 15px 0; border-radius: 4px; background-color: #fff2f0; border-left: 4px solid #f44336; color: #721c24;';
				errorDiv.textContent = loginError;

				var usernameFieldGroup = usernameInput.closest('.elementor-field-group');
				if (usernameFieldGroup) {
					usernameFieldGroup.parentNode.insertBefore(errorDiv, usernameFieldGroup);
				}
			}
		}
	});

	// ============================================
	// DOCUMENT READY - INITIALIZE EVERYTHING
	// ============================================

	$(document).ready(function () {
		// Initialize product detail view
		initProductDetailView();
		// Initialize cart widget
		initCartWidget();
		// Initialize all other features
		initTabs();
		initProductSearch();
		initSaleForm();
		initQuantityChangeListener();
		initOrderFilters();
		initKlarnaCheckout();
		initChartControls();
		initMobileHamburgerMenu();
		initAddOrderTab();
		initAddOrderForm();
		initMultiProductOrder();
		initSalesMaterialTab();

		// ============================================
		// MULTI-PRODUCT ORDER FUNCTIONALITY
		// ============================================

		function initMultiProductOrder() {
			// orderProducts is now global - no need to redeclare it here
			let isSubmitting = false;  // ✅ Track submission state to prevent duplicates

			// Load available products into dropdown
			window.loadProductsForOrderDropdown = function() {
				if (cartItems.length === 0) {
					$('#product-selector').html('<option value="">Inga produkter i varukorgen</option>').prop('disabled', true);
					$('#add-product-to-order').prop('disabled', true);
					return;
				}

				const $select = $('#product-selector');
				$select.empty().append('<option value="">-- Välj en produkt --</option>').prop('disabled', false);
				$('#add-product-to-order').prop('disabled', false);

				// Group products by category
				const productsByCategory = {};
				
				cartItems.forEach(product => {
					// Get category from product - handle both string and array formats
					let category = 'Övrigt';
					if (product.category) {
						category = product.category;
					} else if (product.categories && product.categories.length > 0) {
						category = product.categories[0].name;
					}
					
					if (!productsByCategory[category]) {
						productsByCategory[category] = [];
					}
					productsByCategory[category].push(product);
				});

				// Add products grouped by category
				Object.keys(productsByCategory).sort().forEach(category => {
					const $optgroup = $(`<optgroup label="${category}"></optgroup>`);
					
					productsByCategory[category].forEach(product => {
						$optgroup.append(`
							<option value="${product.id}" 
									data-price="${product.price}" 
									data-rrp="${product.rrp}"
									data-total-price="${product.total_price || product.price}"
									data-sku="${product.sku}"
									data-supplier="${product.supplier || ''}"
									data-can-combine="${product.can_be_combined || false}">
								${product.name} - ${parseFloat(product.rrp).toFixed(2)} SEK
							</option>
						`);
					});
					
					$select.append($optgroup);
				});
			};
			// Add product to order list
			$('#add-product-to-order').on('click', function () {
				const $select = $('#product-selector');
				const $quantityInput = $('#product-quantity-input');

				const productId = $select.val();
				const quantity = parseInt($quantityInput.val());

				if (!productId) {
					alert('Välj en produkt först');
					return;
				}

				if (quantity < 1) {
					alert('Antal måste vara minst 1');
					return;
				}

				const $selectedOption = $select.find('option:selected');
				   const productName = $selectedOption.text().split(' - ')[0];
				   const productPrice = parseFloat($selectedOption.data('price'));
				   const productRrp = parseFloat($selectedOption.data('rrp'));
				   const productTotalPrice = parseFloat($selectedOption.data('total-price')) || productPrice;
				   const productSku = $selectedOption.data('sku');
				   const productSupplier = $selectedOption.data('supplier');
				   const canBeCombined = $selectedOption.data('can-combine') === true ||
					   $selectedOption.data('can-combine') === 'yes' ||
					   $selectedOption.data('can-combine') === 1;
				if (orderProducts.length > 0) {
					const firstProduct = orderProducts[0];
					const firstProductSupplier = firstProduct.supplier;
					const firstProductCombinable = firstProduct.can_be_combined || false;

					// Rule 1: If first product is combinable, new product must be combinable from same supplier
					if (firstProductCombinable) {
						if (!canBeCombined) {
							alert('Du kan inte blanda kombinerbara och icke-kombinerbara produkter i samma order.');
							return;
						}
						if (productSupplier !== firstProductSupplier) {
							alert('Kombinerbara produkter måste vara från samma leverantör.');
							return;
						}
					}

					// Rule 2: If first product is NOT combinable, new product must NOT be combinable
					if (!firstProductCombinable && canBeCombined) {
						alert('Du kan inte blanda kombinerbara och icke-kombinerbara produkter i samma order.');
						return;
					}

					// Rule 3: Non-combinable products must be from same supplier
					if (!firstProductCombinable && !canBeCombined && productSupplier !== firstProductSupplier) {
						alert('Produkter i samma order måste vara från samma leverantör.');
						return;
					}
				}

				// Check if product already exists in order
				const existingIndex = orderProducts.findIndex(p => p.id === productId);

				if (existingIndex !== -1) {
					// Update quantity
					orderProducts[existingIndex].quantity += quantity;
					console.log('🔍 Updated existing product quantity:', orderProducts[existingIndex]);
				} else {
					const newProduct = {
						id: productId,
						name: productName,
						price: productPrice,
						rrp: productRrp,
						total_price: productTotalPrice,
						sku: productSku,
						supplier: productSupplier,
						quantity: quantity,
						can_be_combined: canBeCombined
					};
					console.log('🔍 Adding new product to order:', newProduct);
					orderProducts.push(newProduct);
				}

			// Reset form
			$select.val('');
			$quantityInput.val(1);

			// Update display
			renderOrderProducts();
			updateOrderSummary();
		});

		// Render order products
		function renderOrderProducts() {
			const $container = $('#order-products-list');

			if (orderProducts.length === 0) {
				$container.html('<div class="cs-empty-products">Inga produkter tillagda ännu</div>');
				return;
			}

			let html = '';
			orderProducts.forEach((product, index) => {
				const lineTotal = product.rrp * product.quantity;  // Use RRP for customer-facing price
				html += `
                <div class="cs-product-item" data-index="${index}">
                    <div class="cs-product-item-info">
                        <div class="cs-product-item-name">${product.name}</div>
                        <div class="cs-product-item-meta">
                            <span>SKU: ${product.sku}</span>
                            ${product.supplier ? `<span>Leverantör: ${product.supplier}</span>` : ''}
                            <span>${parseFloat(product.rrp).toFixed(2)} SEK / st</span>
                        </div>
                    </div>
                    <div class="cs-product-item-actions">
                        <div class="cs-product-item-quantity">
                            <button type="button" class="cs-qty-btn cs-qty-decrease" data-index="${index}">−</button>
                            <span class="cs-qty-value">${product.quantity}</span>
                            <button type="button" class="cs-qty-btn cs-qty-increase" data-index="${index}">+</button>
                        </div>
                        <div class="cs-product-item-price">${lineTotal.toFixed(2)} SEK</div>
                        <button type="button" class="cs-remove-product-btn" data-index="${index}">
                            <svg viewBox="0 0 24 24" width="24" height="24" style="stroke:#000; fill:none;" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
			});

			$container.html(html);
		}

		// Update order summary
		function updateOrderSummary() {
				const totalItems = orderProducts.reduce((sum, p) => sum + p.quantity, 0);
				// Calculate totals
				const totalAmount = orderProducts.reduce((sum, p) => sum + (p.price * p.quantity), 0);  // Base price (what you pay to supplier)
			const customerPays = orderProducts.reduce((sum, p) => sum + (p.rrp * p.quantity), 0);  // RRP (what customer pays)
			const rrpTotal = customerPays;  // Same as customerPays - RRP total

			$('#total-items-count').text(totalItems);
			$('#total-order-amount').text(customerPays.toFixed(2) + ' SEK');

			// Update pricing cards
			const rrpPerUnit = orderProducts.length > 0 ? orderProducts[0].rrp : 0;
			$('#rrp-calculated').text(rrpTotal.toFixed(2) + ' SEK');
			$('#rrp-per-unit').text(rrpPerUnit.toFixed(2));

				// Update total amount based on pricing mode (default to 'rrp')
				const pricingMode = $('.cs-pricing-card-active').data('pricing-mode') || 'rrp';
				if (pricingMode === 'rrp') {
					$('#total_amount').val(rrpTotal.toFixed(2));
				}

				if (orderProducts.length > 0) {
					$('#order-summary').show();
					$('.cs-submit-btn').prop('disabled', false);
				} else {
					$('#order-summary').hide();
					$('.cs-submit-btn').prop('disabled', true);
				}
			}

			// Pricing card selection
			$(document).on('click', '.cs-pricing-card', function() {
				const pricingMode = $(this).data('pricing-mode');
				
				// Update active state
				$('.cs-pricing-card').removeClass('cs-pricing-card-active');
				$(this).addClass('cs-pricing-card-active');
				
				// Show/hide info messages
				if (pricingMode === 'rrp') {
					$('.cs-pricing-info-rrp').show();
					$('.cs-pricing-info-custom').hide();
					$('#total_amount').prop('readonly', true);
					$('.cs-form-help').text('Summan beräknas automatiskt från RRP');
					
					// Recalculate RRP total
					const rrpTotal = orderProducts.reduce((sum, p) => sum + (p.rrp * p.quantity), 0);
					$('#total_amount').val(rrpTotal.toFixed(2));
				} else if (pricingMode === 'custom') {
					$('.cs-pricing-info-rrp').hide();
					$('.cs-pricing-info-custom').show();
					$('#total_amount').prop('readonly', false);
					$('.cs-form-help').text('Ange ditt önskade totalpris');
					
					// Keep current value but make it editable
					$('#total_amount').focus();
				}
			});

			// Increase quantity
			$(document).on('click', '.cs-qty-increase', function () {
				const index = $(this).data('index');
				orderProducts[index].quantity++;
				renderOrderProducts();
				updateOrderSummary();
			});

			// Decrease quantity
			$(document).on('click', '.cs-qty-decrease', function () {
				const index = $(this).data('index');
				if (orderProducts[index].quantity > 1) {
					orderProducts[index].quantity--;
					renderOrderProducts();
					updateOrderSummary();
				}
			});

			// Remove product
			$(document).on('click', '.cs-remove-product-btn', function () {
				const index = $(this).data('index');
				orderProducts.splice(index, 1);
				renderOrderProducts();
				updateOrderSummary();
			});

			// Submit order form
			$('#cs-order-form').off('submit').on('submit', function (e) {
				e.preventDefault();
				console.log('🟢 SUBMIT HANDLER #3 EXECUTING');

				if (isSubmitting) {
					console.log('⚠️ Already submitting, skipping Handler #3');
					return false;
				}

				if (orderProducts.length === 0) {
					alert('Lägg till minst en produkt i ordern');
					return;
				}

			// Get pricing mode
			const pricingMode = $('.cs-pricing-card-active').data('pricing-mode');
			const customPrice = parseFloat($('#total_amount').val()) || 0;
			
			// Calculate totals
			const totalAmount = orderProducts.reduce((sum, p) => sum + ((p.total_price || p.price) * p.quantity), 0);  // Swedish calculated price (what YOU pay)
			let customerPays;
			
			if (pricingMode === 'custom' && customPrice > 0) {
				// Use custom price entered by user
				customerPays = customPrice;
			} else {
				// Use RRP (default)
				customerPays = orderProducts.reduce((sum, p) => sum + (p.rrp * p.quantity), 0);
			}

			console.log('==========================================');
			console.log('💰 ORDER SUBMISSION (Handler #3):');
			console.log('  totalAmount (NI betalar):', totalAmount);
			console.log('  customerPays (Kunden betalar):', customerPays);
			console.log('  Expected profit:', customerPays - totalAmount);
			console.log('  Products:', orderProducts);
			console.log('==========================================');

			isSubmitting = true;

			const formData = {
				action: 'cs_add_sale',
				nonce: csAjax.nonce,
				customer_name: $('#customer_name').val(),
				phone: $('#phone').val(),
				email: $('#email').val(),
				address: $('#address').val(),
				sale_date: $('#sale_date').val(),
			notes: $('#notes').val(),
			total_amount: totalAmount.toFixed(2),
			customer_pays: customerPays.toFixed(2),
			products: JSON.stringify(orderProducts)
		};

		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: formData,
			beforeSend: function () {
				$('.cs-submit-btn').prop('disabled', true).text('Skapar order...');
			},
			success: function (response) {
				isSubmitting = false;

				if (response.success) {
					alert('Order skapad!');
					
					// Reset form fields but KEEP orderProducts for next order
					$('#customer_name').val('');
					$('#phone').val('');
					$('#email').val('');
					$('#address').val('');
					$('#notes').val('');
					$('#sale_date').val(new Date().toISOString().split('T')[0]);
					
					// DON'T clear orderProducts - keep them for next order
					// This allows quick entry of multiple orders with same products
					// orderProducts = [];
					// window.orderProducts = orderProducts;
					// renderOrderProducts();
					// updateOrderSummary();

					// Trigger refresh of orders tab
					if (typeof loadOrders === 'function') {
						loadOrders();
					}
				} else {
					alert('Fel: ' + (response.data || 'Kunde inte skapa order'));
				}
			},
			error: function () {
				isSubmitting = false;
				alert('Ett fel uppstod vid skapande av order');
			},
			complete: function () {
				$('.cs-submit-btn').prop('disabled', false).text('Lägg till beställning');
			}
		});
	});
			// Load on page load if we're on add-order tab
			if ($('#tab-add-order').hasClass('active')) {
				loadProductsForOrderDropdown();
			}
		}
		// Delayed initializations
		setTimeout(initAddressAutocomplete, 500);
		// Hide Cart Widget on Mobile when I click on Hamburger
		$("#mobileMenuToggle").click(function () {
			$(".cs-cart-widget").toggle();
		});
		// Password generator
		setTimeout(function () {
			$('#generate-password').on('click', function (e) {
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
		}, 1000);

		// Load initial products if on products tab
		if ($('#tab-assign-product').hasClass('active')) {
			loadProducts();
		}

		// Restore cart selections when products load
		$(document).on('productsLoaded', function () {
			cartItems.forEach(function (item) {
				const $card = $(`.cs-product-card[data-id="${item.id}"]`);
				if ($card.length) {
					$card.addClass('selected');
					$card.prepend(`
<div class="cs-selected-checkmark-circle">
</div>
`);
				}
			});

			attachProductCardHandlers();
		});

		// Tab switching handler
		$('.cs-tab-item').on('click', function () {
			const tabId = $(this).data('tab');

			if (tabId === 'orders') {
				setTimeout(loadOrders, 100);
			} else if (tabId === 'add-order') {
				updateSelectedProductsList();
			} else if (tabId === 'sales-material') {
				setTimeout(function () {
					if (cartItems.length > 0) {
						updateSalesMaterialTab(cartItems[0].id);
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

	// ============================================
	// KAFETERIA TAB FUNCTIONALITY
	// ============================================
	
	let kafeteriaCart = [];
	let kafeteriaProducts = [];
	let selectedCategory = 'all';
	
	// Initialize Kafeteria when tab is clicked
	$(document).on('click', '[data-tab="kafeteria"]', function() {
		if (!kafeteriaProducts.length) {
			loadKafeteriaCategories();
			loadKafeteriaProducts();
		}
	});
	
	// Load categories
	function loadKafeteriaCategories() {
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_kafeteria_categories',
				nonce: csAjax.nonce
			},
			success: function(response) {
				if (response.success && response.data.categories) {
					renderCategories(response.data.categories);
				}
			},
			error: function(xhr, status, error) {
				console.error('Error loading categories:', error);
			}
		});
	}
	
	// Render categories in sidebar
	function renderCategories(categories) {
		const $container = $('#cs-dynamic-categories');
		$container.empty();
		
		categories.forEach(function(category) {
			const $categoryBtn = $(`
				<button class="cs-category-item" data-category="${category.slug}" data-category-id="${category.id}">
					${category.icon}
					<span class="cs-category-label">${category.name}</span>
					<span class="cs-category-count" data-category="${category.id}">${category.count}</span>
				</button>
			`);
			$container.append($categoryBtn);
		});
	}
	
	// Load products
	function loadKafeteriaProducts(categoryId = 'all', search = '', sort = 'popularity') {
		$('#cs-kafeteria-products').html('<div class="cs-loading-state"><div class="cs-spinner"></div><p>Laddar produkter...</p></div>');
		$('#cs-kafeteria-empty').hide();
		
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_get_kafeteria_products',
				nonce: csAjax.nonce,
				category_id: categoryId,
				search: search,
				sort: sort
			},
			success: function(response) {
				if (response.success && response.data.products) {
					kafeteriaProducts = response.data.products;
					renderKafeteriaProducts(kafeteriaProducts);
					$('#cs-visible-count').text(response.data.total);
				} else {
					$('#cs-kafeteria-products').empty();
					$('#cs-kafeteria-empty').show();
				}
			},
			error: function(xhr, status, error) {
				console.error('Error loading products:', error);
				$('#cs-kafeteria-products').empty();
				$('#cs-kafeteria-empty').show();
			}
		});
	}
	
	// Render products grid
	function renderKafeteriaProducts(products) {
		const $container = $('#cs-kafeteria-products');
		$container.empty();
		
		if (products.length === 0) {
			$('#cs-kafeteria-empty').show();
			return;
		}
		
		$('#cs-kafeteria-empty').hide();
		
		products.forEach(function(product) {
			const inCart = kafeteriaCart.some(item => item.id === product.id);
			const btnClass = inCart ? 'cs-add-to-cart-btn in-cart' : 'cs-add-to-cart-btn';
			const btnText = inCart ? 'I varukorgen' : 'Lägg till';
			
			const $productCard = $(`
				<div class="cs-kafeteria-product-card" data-product-id="${product.id}">
					<div class="cs-product-image-wrapper">
						<img src="${product.image}" alt="${product.name}">
						${product.rating > 0 ? `
							<div class="cs-product-rating">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
									<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
								</svg>
								${product.rating.toFixed(1)}
							</div>
						` : ''}
						${product.supplier ? `
							<div class="cs-product-supplier-badge">
								${product.supplier}
							</div>
						` : ''}
					</div>
					<div class="cs-product-info">
						<h3 class="cs-product-name">${product.name}</h3>
						${product.description ? `<p class="cs-product-description">${product.description}</p>` : ''}
						<div class="cs-product-pricing">
							<div class="cs-product-prices">
								<div class="cs-product-price">${product.price.toFixed(2)} kr</div>
								${product.rrp > 0 ? `<div class="cs-product-rrp">RRP: ${product.rrp.toFixed(2)} kr</div>` : ''}
								${product.sku ? `<div class="cs-product-sku">SKU: ${product.sku}</div>` : ''}
							</div>
						</div>
						<button class="${btnClass}" data-product-id="${product.id}">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								${inCart ? 
									'<polyline points="20 6 9 17 4 12"></polyline>' : 
									'<path d="M12 5v14M5 12h14"></path>'
								}
							</svg>
							${btnText}
						</button>
					</div>
				</div>
			`);
			
			$container.append($productCard);
		});
	}
	
	// Category click handler
	$(document).on('click', '.cs-category-item', function() {
		$('.cs-category-item').removeClass('active');
		$(this).addClass('active');
		
		const categoryId = $(this).data('category-id');
		selectedCategory = categoryId;
		
		const search = $('#cs-kafeteria-search').val();
		const sort = $('#cs-sort-select').val();
		
		loadKafeteriaProducts(categoryId, search, sort);
	});
	
	// Search handler
	let searchTimeout;
	$(document).on('input', '#cs-kafeteria-search', function() {
		clearTimeout(searchTimeout);
		const search = $(this).val();
		
		searchTimeout = setTimeout(function() {
			const sort = $('#cs-sort-select').val();
			loadKafeteriaProducts(selectedCategory, search, sort);
		}, 500);
	});
	
	// Sort handler
	$(document).on('change', '#cs-sort-select', function() {
		const sort = $(this).val();
		const search = $('#cs-kafeteria-search').val();
		loadKafeteriaProducts(selectedCategory, search, sort);
	});
	
	// Add to cart handler - Kafeteria only
	$(document).on('click', '#tab-kafeteria .cs-add-to-cart-btn', function(e) {
		e.stopPropagation();
		e.preventDefault();
		
		const productId = $(this).data('product-id');
		const product = kafeteriaProducts.find(p => p.id === productId);
		
		if (!product) return;
		
		const existingItem = kafeteriaCart.find(item => item.id === productId);
		
		if (existingItem) {
			// Remove from cart
			kafeteriaCart = kafeteriaCart.filter(item => item.id !== productId);
			$(this).removeClass('in-cart').html(`
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M12 5v14M5 12h14"></path>
				</svg>
				Lägg till
			`);
		} else {
			// Add to cart
			kafeteriaCart.push({
				...product,
				quantity: 1
			});
			$(this).addClass('in-cart').html(`
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="20 6 9 17 4 12"></polyline>
				</svg>
				I varukorgen
			`);
		}
		
		updateKafeteriaCartWidget();
	});
	
	// Update kafeteria cart widget
	function updateKafeteriaCartWidget() {
		const itemCount = kafeteriaCart.reduce((sum, item) => sum + item.quantity, 0);
		const totalAmount = kafeteriaCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
		
		if (itemCount > 0) {
			$('#cs-cart-badge').text(itemCount).show();
			renderCartItems();
			$('#cs-kafeteria-cart-footer').show();
			$('#cs-kafeteria-cart-total').text(totalAmount.toFixed(2) + ' kr');
		} else {
			$('#cs-cart-badge').hide();
			$('#cs-kafeteria-kafeteria-cart-items').html(`
				<div class="cs-cart-empty">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="9" cy="21" r="1"></circle>
						<circle cx="20" cy="21" r="1"></circle>
						<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
					</svg>
					<p>Varukorgen är tom</p>
				</div>
			`);
			$('#cs-kafeteria-cart-footer').hide();
		}
	}
	
	// Render cart items
	function renderCartItems() {
		const $container = $('#cs-kafeteria-cart-items');
		$container.empty();
		
		kafeteriaCart.forEach(function(item) {
			const $cartItem = $(`
				<div class="cs-cart-item" data-product-id="${item.id}">
					<img src="${item.image}" alt="${item.name}" class="cs-cart-item-image">
					<div class="cs-cart-item-details">
						<div class="cs-cart-item-name">${item.name}</div>
						<div class="cs-cart-item-price">${item.price.toFixed(2)} kr</div>
						<div class="cs-cart-item-quantity">
							<button class="cs-qty-btn cs-qty-decrease" data-product-id="${item.id}">−</button>
							<span class="cs-qty-value">${item.quantity}</span>
							<button class="cs-qty-btn cs-qty-increase" data-product-id="${item.id}">+</button>
						</div>
					</div>
					<button class="cs-cart-item-remove" data-product-id="${item.id}">×</button>
				</div>
			`);
			$container.append($cartItem);
		});
	}
	
	// Cart modal toggle
	$(document).on('click', '#cs-cart-icon-btn', function() {
		$('#cs-cart-modal').fadeIn(300);
		$('body').css('overflow', 'hidden');
	});
	
	// Close cart modal
	$(document).on('click', '#cs-cart-modal-close, .cs-cart-modal-overlay', function() {
		$('#cs-cart-modal').fadeOut(300);
		$('body').css('overflow', '');
	});
	
	// Prevent modal content clicks from closing modal
	$(document).on('click', '.cs-cart-modal-content', function(e) {
		e.stopPropagation();
	});
	
	// Increase quantity - Kafeteria only
	$(document).on('click', '#cs-cart-modal .cs-qty-increase', function() {
		const productId = $(this).data('product-id');
		const item = kafeteriaCart.find(i => i.id === productId);
		if (item) {
			item.quantity++;
			updateKafeteriaCartWidget();
		}
	});
	
	// Decrease quantity - Kafeteria only
	$(document).on('click', '#cs-cart-modal .cs-qty-decrease', function() {
		const productId = $(this).data('product-id');
		const item = kafeteriaCart.find(i => i.id === productId);
		if (item && item.quantity > 1) {
			item.quantity--;
			updateKafeteriaCartWidget();
		}
	});
	
	// Remove from cart - Kafeteria only
	$(document).on('click', '#cs-cart-modal .cs-cart-item-remove', function() {
		const productId = $(this).data('product-id');
		kafeteriaCart = kafeteriaCart.filter(item => item.id !== productId);
		
		// Update button state
		$(`.cs-add-to-cart-btn[data-product-id="${productId}"]`).removeClass('in-cart').html(`
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<path d="M12 5v14M5 12h14"></path>
			</svg>
			Lägg till
		`);
		
		updateKafeteriaCartWidget();
	});
	
	// Checkout - Proceed to WooCommerce Checkout
	$(document).on('click', '#cs-checkout-btn', function() {
		if (kafeteriaCart.length === 0) {
			alert('Varukorgen är tom');
			return;
		}
		
		const $btn = $(this);
		const originalText = $btn.text();
		
		// Disable button and show loading state
		$btn.prop('disabled', true).text('Bearbetar...');
		
		// Send cart to server to add to WooCommerce cart
		$.ajax({
			url: csAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cs_kafeteria_checkout',
				nonce: csAjax.nonce,
				cart_items: JSON.stringify(kafeteriaCart)
			},
			success: function(response) {
				if (response.success && response.data.checkout_url) {
					// Clear the kafeteria cart
					kafeteriaCart = [];
					updateKafeteriaCartWidget();
					window.location.href = response.data.checkout_url;
				} else {
					alert(response.data.message || 'Något gick fel');
					$btn.prop('disabled', false).text(originalText);
				}
			},
			error: function(xhr, status, error) {
				console.error('Checkout error:', error);
				alert('Kunde inte slutföra beställningen. Försök igen.');
				$btn.prop('disabled', false).text(originalText);
			}
		});
	});

})(jQuery);
