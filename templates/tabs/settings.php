<div class="cs-settings-page">
    <h2 class="cs-settings-title"><?php _e('Inställningar', 'club-sales'); ?></h2>
    <p class="cs-settings-subtitle"><?php _e('Hantera dina konto- och företagsinställningar', 'club-sales'); ?></p>

    <?php
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Get user meta data
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);
    $phone = get_user_meta($user_id, 'billing_phone', true);
    $organization_name = get_user_meta($user_id, 'organization_name', true);
    $organization_number = get_user_meta($user_id, 'organization_number', true);
    $swish_number = get_user_meta($user_id, 'swish_number', true);
    ?>



    <!-- Profile Information Section -->
    <div class="cs-settings-section">
        <div class="cs-section-header-settings">
            <div class="cs-section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div>
                <h3 class="cs-section-title"><?php _e('Profilinformation', 'club-sales'); ?></h3>
                <p class="cs-section-description"><?php _e('Dina personliga uppgifter', 'club-sales'); ?></p>
            </div>
        </div>
        
        <form id="cs-profile-form" class="cs-settings-form">
            <div class="cs-form-group">
                <label for="username"><?php _e('Användarnamn', 'club-sales'); ?></label>
                <input type="text" id="username" name="username" class="cs-form-control" value="<?php echo esc_attr($current_user->user_login); ?>" disabled>
                <small class="cs-form-text"><?php _e('Ditt användarnamn kan inte ändras', 'club-sales'); ?></small>
            </div>
            
            <div class="cs-form-row">
                <div class="cs-form-group">
                    <label for="first_name"><?php _e('Förnamn', 'club-sales'); ?></label>
                    <input type="text" id="first_name" name="first_name" class="cs-form-control" value="<?php echo esc_attr($first_name); ?>">
                </div>
                
                <div class="cs-form-group">
                    <label for="last_name"><?php _e('Efternamn', 'club-sales'); ?></label>
                    <input type="text" id="last_name" name="last_name" class="cs-form-control" value="<?php echo esc_attr($last_name); ?>">
                </div>
            </div>
            
            <div class="cs-form-group">
                <label for="email"><?php _e('E-post', 'club-sales'); ?></label>
                <div class="cs-input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input type="email" id="email" name="email" class="cs-form-control cs-input-with-icon" value="<?php echo esc_attr($current_user->user_email); ?>">
                </div>
                <small class="cs-form-text"><?php _e('Du kan logga in med både e-post och ditt användarnamn. Om du ändrar e-postadressen kan du logga in med den nya adressen samt ditt användarnamn.', 'club-sales'); ?></small>
            </div>
            
            <div class="cs-form-group">
                <label for="phone"><?php _e('Telefon', 'club-sales'); ?></label>
                <div class="cs-input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <input type="tel" id="phone" name="phone" class="cs-form-control cs-input-with-icon" value="<?php echo esc_attr($phone); ?>">
                </div>
            </div>
            
            <button type="submit" class="cs-btn cs-btn-secondary cs-btn-save">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php _e('Spara profilinformation', 'club-sales'); ?>
            </button>
        </form>
    </div>

    <!-- Organization Information Section -->
    <div class="cs-settings-section">
        <div class="cs-section-header-settings">
            <div class="cs-section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div>
                <h3 class="cs-section-title"><?php _e('Företags-/Föreningsinformation', 'club-sales'); ?></h3>
                <p class="cs-section-description"><?php _e('Organisationsuppgifter', 'club-sales'); ?></p>
            </div>
        </div>
        
        <form id="cs-organization-form" class="cs-settings-form">
            <div class="cs-form-group">
                <label for="organization_name"><?php _e('Namn på förening/företag', 'club-sales'); ?></label>
                <input type="text" id="organization_name" name="organization_name" class="cs-form-control" value="<?php echo esc_attr($organization_name); ?>">
            </div>
            
            <div class="cs-form-group">
                <label for="organization_number"><?php _e('Organisationsnummer', 'club-sales'); ?></label>
                <input type="text" id="organization_number" name="organization_number" class="cs-form-control" value="<?php echo esc_attr($organization_number); ?>">
            </div>
            
            <div class="cs-form-group">
                <label for="swish_number"><?php _e('Swish-nummer', 'club-sales'); ?></label>
                <div class="cs-input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <input type="text" id="swish_number" name="swish_number" class="cs-form-control cs-input-with-icon" value="<?php echo esc_attr($swish_number); ?>">
                </div>
                <small class="cs-form-text"><?php _e('Används för att generera Swish QR-koder för betalningar', 'club-sales'); ?></small>
            </div>
            
            <button type="submit" class="cs-btn cs-btn-secondary cs-btn-save">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php _e('Spara företagsinformation', 'club-sales'); ?>
            </button>
        </form>
    </div>

        <!-- Change Password Section -->
    <div class="cs-settings-section">
        <div class="cs-section-header-settings">
            <div class="cs-section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div>
                <h3 class="cs-section-title"><?php _e('Ändra lösenord', 'club-sales'); ?></h3>
                <p class="cs-section-description"><?php _e('Uppdatera ditt lösenord', 'club-sales'); ?></p>
            </div>
        </div>
        
        <form id="cs-change-password-form" class="cs-settings-form">
            <div class="cs-form-group">
                <label for="current_password"><?php _e('Nuvarande lösenord', 'club-sales'); ?></label>
                <div class="cs-password-wrapper">
                    <input type="password" id="current_password" name="current_password" class="cs-form-control" required>
                    <button type="button" class="cs-password-toggle" data-target="current_password">
                        <svg class="cs-eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="cs-eye-off-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="cs-form-group">
                <label for="new_password"><?php _e('Nytt lösenord', 'club-sales'); ?></label>
                <div class="cs-password-wrapper">
                    <input type="password" id="new_password" name="new_password" class="cs-form-control" required>
                    <button type="button" class="cs-password-toggle" data-target="new_password">
                        <svg class="cs-eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="cs-eye-off-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
                <small class="cs-form-text"><?php _e('Minst 8 tecken långt', 'club-sales'); ?></small>
            </div>
            
            <div class="cs-form-group">
                <label for="confirm_password"><?php _e('Bekräfta nytt lösenord', 'club-sales'); ?></label>
                <div class="cs-password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" class="cs-form-control" required>
                    <button type="button" class="cs-password-toggle" data-target="confirm_password">
                        <svg class="cs-eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="cs-eye-off-icon" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="cs-btn cs-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <?php _e('Ändra lösenord', 'club-sales'); ?>
            </button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // ⚡ DIAGNOSTIC: Check what swish number is actually saved
    console.log('🔧 Checking saved swish number...');
    $.ajax({
        url: csAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'cs_check_swish_number',
            nonce: csAjax.nonce
        },
        success: function(response) {
            if (response.success) {
                console.log('💾 DATABASE VALUES:');
                console.log('   User ID:', response.data.user_id);
                console.log('   Swish Number:', response.data.swish_number);
                console.log('   Organization:', response.data.organization_name);
                console.log('   Org Number:', response.data.organization_number);
                console.log('   Timestamp:', response.data.timestamp);
                
                // Check if it matches the form field
                const formSwish = $('#swish_number').val();
                if (formSwish !== response.data.swish_number) {
                    console.warn('⚠️ MISMATCH: Form shows "' + formSwish + '" but database has "' + response.data.swish_number + '"');
                } else {
                    console.log('✅ Form value matches database value');
                }
            }
        }
    });
    
    // Password visibility toggle
    $('.cs-password-toggle').on('click', function() {
        const $button = $(this);
        const targetId = $button.data('target');
        const $input = $('#' + targetId);
        const $eyeIcon = $button.find('.cs-eye-icon');
        const $eyeOffIcon = $button.find('.cs-eye-off-icon');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $eyeIcon.hide();
            $eyeOffIcon.show();
        } else {
            $input.attr('type', 'password');
            $eyeIcon.show();
            $eyeOffIcon.hide();
        }
    });
    
    // Change Password Form Handler
    $('#cs-change-password-form').on('submit', function(e) {
        e.preventDefault();
        
        const currentPassword = $('#current_password').val();
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();
        
        // Validation
        if (newPassword.length < 8) {
            alert('<?php _e('Lösenordet måste vara minst 8 tecken långt', 'club-sales'); ?>');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            alert('<?php _e('Lösenorden matchar inte', 'club-sales'); ?>');
            return;
        }
        
        const $button = $(this).find('button[type="submit"]');
        $button.prop('disabled', true).addClass('cs-loading');
        
        $.ajax({
            url: csAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cs_change_password',
                nonce: csAjax.nonce,
                current_password: currentPassword,
                new_password: newPassword
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Lösenordet har ändrats!', 'club-sales'); ?>');
                    $('#cs-change-password-form')[0].reset();
                } else {
                    alert(response.data.message || '<?php _e('Något gick fel', 'club-sales'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Något gick fel', 'club-sales'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).removeClass('cs-loading');
            }
        });
    });
    
    // Profile Form Handler
    $('#cs-profile-form').on('submit', function(e) {
        e.preventDefault();
        
        const $button = $(this).find('button[type="submit"]');
        $button.prop('disabled', true).addClass('cs-loading');
        
        $.ajax({
            url: csAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cs_update_profile',
                nonce: csAjax.nonce,
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                email: $('#email').val(),
                phone: $('#phone').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Profilen har uppdaterats!', 'club-sales'); ?>');
                } else {
                    alert(response.data.message || '<?php _e('Något gick fel', 'club-sales'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Något gick fel', 'club-sales'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).removeClass('cs-loading');
            }
        });
    });
    
    // Organization Form Handler
    $('#cs-organization-form').on('submit', function(e) {
        e.preventDefault();
        
        const $button = $(this).find('button[type="submit"]');
        $button.prop('disabled', true).addClass('cs-loading');
        
        const newSwish = $('#swish_number').val();
        console.log('📝 Saving organization info with swish:', newSwish);
        
        $.ajax({
            url: csAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cs_update_organization',
                nonce: csAjax.nonce,
                organization_name: $('#organization_name').val(),
                organization_number: $('#organization_number').val(),
                swish_number: newSwish
            },
            success: function(response) {
                if (response.success) {
                    console.log('✅ Server response:', response.data);
                    console.log('   New swish from server:', response.data.new_swish);
                    alert('<?php _e('Organisationsinformationen har uppdaterats!', 'club-sales'); ?>');
                    
                    // Refresh orders and stats if swish number was updated
                    if (response.data.swish_updated) {
                        console.log('✅ Swish number updated - refreshing orders and stats...');
                        
                        // Force refresh orders with cache busting
                        if (typeof window.loadOrders === 'function') {
                            console.log('🔄 Calling loadOrders()...');
                            setTimeout(function() {
                                window.loadOrders();
                            }, 500);
                        }
                        
                        // Update stats
                        if (typeof window.triggerStatsUpdate === 'function') {
                            console.log('📊 Calling triggerStatsUpdate()...');
                            window.triggerStatsUpdate();
                        }
                        
                        // Show message to user
                        setTimeout(function() {
                            alert('Swish-numret har uppdaterats! Gå till Beställningar-fliken för att se uppdaterade QR-koder.');
                            
                            // RECHECK: Verify the database actually saved it
                            $.ajax({
                                url: csAjax.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'cs_check_swish_number',
                                    nonce: csAjax.nonce
                                },
                                success: function(recheckResponse) {
                                    if (recheckResponse.success) {
                                        console.log('🔍 RECHECK after save:');
                                        console.log('   Database now has:', recheckResponse.data.swish_number);
                                        console.log('   Expected:', newSwish);
                                        if (recheckResponse.data.swish_number === newSwish) {
                                            console.log('✅ Verified: Database matches what we saved!');
                                        } else {
                                            console.error('❌ PROBLEM: Database has different value!');
                                        }
                                    }
                                }
                            });
                        }, 1000);
                    }
                } else {
                    alert(response.data.message || '<?php _e('Något gick fel', 'club-sales'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Något gick fel', 'club-sales'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).removeClass('cs-loading');
            }
        });
    });
});
</script>
