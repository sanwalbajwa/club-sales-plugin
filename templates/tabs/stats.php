<div class="cs-stats-dashboard">
    <!-- Left Column - Sales/Profit Graph -->
    <div class="cs-stat-container">
        <h2 class="cs-heading"><?php _e('Generated profit:', 'club-sales'); ?></h2>
        <div class="cs-value cs-profit-value"><?php echo number_format($stats['total_profit'], 0); ?> <?php echo esc_html(get_option('cs_settings')['currency'] ?? 'SEK'); ?></div>
        
        <!-- Sales chart container -->
        <div class="cs-chart-container">
            <canvas id="sales-chart"></canvas>
        </div>
        
        <div class="cs-chart-controls">
            <button class="cs-chart-period active" data-period="month"><?php _e('Month', 'club-sales'); ?></button>
            <button class="cs-chart-period" data-period="quarter"><?php _e('Quarter', 'club-sales'); ?></button>
            <button class="cs-chart-period" data-period="year"><?php _e('Year', 'club-sales'); ?></button>
        </div>
    </div>
    
    <!-- Right Column - Statistics -->
    <div class="cs-stat-container">
        <h2 class="cs-heading"><?php _e('Statistics', 'club-sales'); ?></h2>
        
        <div class="cs-mini-stats">
            <div class="cs-mini-stat">
                <span class="cs-mini-label"><?php _e('Opportunities', 'club-sales'); ?></span>
                <span class="cs-mini-value cs-opportunities"><?php echo $stats['opportunities']; ?></span>
            </div>
            <div class="cs-mini-stat">
                <span class="cs-mini-label"><?php _e('Completed Sales', 'club-sales'); ?></span>
                <span class="cs-mini-value cs-completed-sales"><?php echo $stats['completed_sales']; ?></span>
            </div>
        </div>
        
        <!-- Auto-update status indicator -->
        <div class="cs-update-status" id="cs-update-status" style="display: none;">
            <span class="cs-update-text"></span>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Global variables for chart and update tracking
    let salesChart = null;
    let currentPeriod = 'month';
    let updateTimer = null;
    let isUpdating = false;
    
    // Initialize stats with auto-update functionality
    function initAutoUpdateStats() {
        console.log('Initializing auto-update stats system');
        
        // Start polling for updates every 5 seconds
        startStatsPolling();
        
        // Listen for custom events from other parts of the application
        $(document).on('cs_sale_added cs_sale_deleted cs_order_updated', function(event, data) {
            console.log('Received event:', event.type, data);
            updateStatsImmediately();
        });
        
        // Initialize chart
        initResponsiveSalesChart();
    }
    
    // Polling mechanism to check for updates
    function startStatsPolling() {
        // Poll every 10 seconds for updates
        setInterval(function() {
            if (!isUpdating) {
                checkForStatsUpdate();
            }
        }, 10000); // 10 seconds
    }
    
    // Check if stats need updating (lightweight check)
    function checkForStatsUpdate() {
        $.ajax({
            url: csAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cs_check_stats_update',
                nonce: csAjax.nonce,
                last_update: Date.now()
            },
            success: function(response) {
                if (response.success && response.data.needs_update) {
                    updateStatsImmediately();
                }
            },
            error: function(xhr, status, error) {
            }
        });
    }
    
    // Immediately update stats and chart
    function updateStatsImmediately() {
        if (isUpdating) {
            return;
        }
        
        isUpdating = true;
        showUpdateStatus('Updating statistics...', 'updating');
        
        // Update stats data
        $.ajax({
            url: csAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cs_get_stats',
                nonce: csAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    
                    // Update profit display with animation
                    animateValueUpdate('.cs-profit-value', response.data.total_profit, csAjax.currency || 'SEK');
                    
                    // Update opportunities and completed sales
                    animateValueUpdate('.cs-opportunities', response.data.opportunities);
                    animateValueUpdate('.cs-completed-sales', response.data.completed_sales);
                    
                    // Update chart data
                    updateChartData(currentPeriod);
                    
                    showUpdateStatus('Statistics updated!', 'success');
                } else {
                    showUpdateStatus('Update failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                showUpdateStatus('Update failed', 'error');
            },
            complete: function() {
                isUpdating = false;
                
                // Hide status after 3 seconds
                setTimeout(function() {
                    hideUpdateStatus();
                }, 3000);
            }
        });
    }
    
    // Animate value updates
    function animateValueUpdate(selector, newValue, suffix = '') {
        const $element = $(selector);
        const currentValue = parseInt($element.text().replace(/[^\d]/g, '')) || 0;
        
        if (currentValue !== newValue) {
            // Add highlight effect
            $element.addClass('cs-updating');
            
            // Animate the number change
            $({ value: currentValue }).animate({ value: newValue }, {
                duration: 1000,
                step: function() {
                    if (suffix) {
                        $element.text(Math.round(this.value).toLocaleString() + ' ' + suffix);
                    } else {
                        $element.text(Math.round(this.value));
                    }
                },
                complete: function() {
                    $element.removeClass('cs-updating');
                }
            });
        }
    }
    
    // Show update status
    function showUpdateStatus(message, type) {
        const $status = $('#cs-update-status');
        const $text = $status.find('.cs-update-text');
        
        $text.text(message);
        $status.removeClass('cs-status-updating cs-status-success cs-status-error');
        $status.addClass('cs-status-' + type);
        $status.fadeIn(200);
    }
    
    // Hide update status
    function hideUpdateStatus() {
        $('#cs-update-status').fadeOut(200);
    }
    
    // Sales Chart Functionality with auto-update
    function initResponsiveSalesChart() {
        const ctx = document.getElementById('sales-chart').getContext('2d');

        // Chart configuration
        const chartConfig = {
            type: 'line',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sales (SEK)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        };

        // Initialize with empty chart
        updateChart('month', {
            labels: [],
            datasets: [{
                label: 'Monthly Sales (SEK)',
                data: [],
                backgroundColor: 'rgba(76, 175, 80, 0.2)',
                borderColor: 'rgba(76, 175, 80, 1)',
                borderWidth: 2,
                tension: 0.4
            }]
        });

        // Function to update chart
        function updateChart(period, data) {
            // Destroy existing chart if it exists
            if (salesChart) {
                salesChart.destroy();
            }

            // Create new chart with data
            chartConfig.data = data;
            salesChart = new Chart(ctx, chartConfig);

            // Update active button styling
            document.querySelectorAll('.cs-chart-period').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.cs-chart-period[data-period="${period}"]`).classList.add('active');
        }

        // Add event listeners to period buttons
        document.querySelectorAll('.cs-chart-period').forEach(button => {
            button.addEventListener('click', function() {
                const period = this.dataset.period;
                currentPeriod = period;
                updateChartData(period);
            });
        });

        // Function to update chart data
        window.updateChartData = function(period) {
            fetchSalesData(period);
        };

        // Fetch real sales data via AJAX
        function fetchSalesData(period) {
            $.ajax({
                url: csAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cs_get_sales_chart_data',
                    nonce: csAjax.nonce,
                    period: period
                },
                success: function(response) {
                    if (response.success && response.data) {
                        updateChart(period, response.data);
                    }
                },
                error: function() {
                    console.error('Failed to fetch sales chart data');
                }
            });
        }

        // Fetch initial data
        fetchSalesData('month');
    }
    
    // Initialize the auto-update system
    initAutoUpdateStats();
    
    // Trigger initial load
    setTimeout(function() {
        updateStatsImmediately();
    }, 1000);
});

// Global function to trigger stats update from other scripts
window.triggerStatsUpdate = function() {
    jQuery(document).trigger('cs_stats_manual_update');
};
</script>

<style>
/* Auto-update status styling */
.cs-update-status {
    margin-top: 15px;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 13px;
    text-align: center;
    transition: all 0.3s ease;
}

.cs-status-updating {
    background-color: #e3f2fd;
    color: #1976d2;
    border: 1px solid #bbdefb;
}

.cs-status-success {
    background-color: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.cs-status-error {
    background-color: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

/* Animation for updating values */
.cs-updating {
    background-color: #fff3cd;
    border-radius: 4px;
    padding: 2px 4px;
    transition: background-color 0.3s ease;
}

/* Pulsing animation for real-time updates */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.cs-updating {
    animation: pulse 1s ease-in-out;
}

/* Enhanced mini stats styling */
.cs-mini-stats {
    position: relative;
}

.cs-mini-stat {
    position: relative;
    overflow: hidden;
}

.cs-mini-value {
    transition: all 0.3s ease;
}

/* Live update indicator styling */
.cs-live-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    font-size: 12px;
    color: #666;
}

.cs-live-dot {
    width: 8px;
    height: 8px;
    background-color: #4CAF50;
    border-radius: 50%;
    animation: pulse-dot 2s infinite;
}

.cs-live-text {
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@keyframes pulse-dot {
    0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
    }
    
    70% {
        transform: scale(1);
        box-shadow: 0 0 0 10px rgba(76, 175, 80, 0);
    }
    
    100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
    }
}
</style>