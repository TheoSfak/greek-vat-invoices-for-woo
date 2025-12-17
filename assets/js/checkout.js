jQuery(document).ready(function($) {
    'use strict';
    
    var grvatinCheckout = {
        
        /**
         * Initialize
         */
        init: function() {
            this.toggleInvoiceFields();
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Toggle invoice fields on selection change (for both select and radio)
            $(document).on('change', '#billing_invoice_type, input[name="billing_invoice_type"]', function() {
                grvatinCheckout.toggleInvoiceFields();
            });
            
            // VAT validation on blur
            $(document).on('blur', '#billing_vat_number', function() {
                grvatinCheckout.validateVAT();
            });
            
            // Country change - clear validation
            $(document).on('change', '#billing_country', function() {
                grvatinCheckout.clearValidation();
            });
            
            // Uppercase conversion
            if (grvatin_params.uppercase === 'yes') {
                $(document).on('blur', '#billing_company, #billing_doy, #billing_business_activity', function() {
                    $(this).val($(this).val().toUpperCase());
                });
            }
            
            // Article 39a checkbox toggle
            $(document).on('change', '#grvatin_article_39a_checkbox', function() {
                grvatinCheckout.toggleArticle39a();
            });
        },
        
        /**
         * Toggle invoice fields visibility
         */
        toggleInvoiceFields: function() {
            // Get value from radio buttons or select
            var invoiceType = $('input[name="billing_invoice_type"]:checked').val() || $('#billing_invoice_type').val();
            var $invoiceFields = $('.grvatin-invoice-fields');
            var $companyField = $('#billing_company_field');
            var $article39aWrapper = $('.grvatin-article-39a-wrapper');
            
            if (invoiceType === 'invoice') {
                $invoiceFields.addClass('visible').slideDown(300);
                $companyField.addClass('visible').slideDown(300);
                
                // Show Article 39a checkbox only for Greek businesses
                var country = $('#billing_country').val();
                if (country === 'GR' && $article39aWrapper.length) {
                    $article39aWrapper.slideDown(300);
                }
                
                $invoiceFields.find('input').prop('required', true);
                $companyField.find('input').prop('required', true);
            } else {
                $invoiceFields.removeClass('visible').slideUp(300);
                $companyField.removeClass('visible').slideUp(300);
                $invoiceFields.find('input').prop('required', false).val('');
                $companyField.find('input').prop('required', false).val('');
                $article39aWrapper.slideUp(300);
                // Uncheck and clear Article 39a
                $('#grvatin_article_39a_checkbox').prop('checked', false);
                $('#vat_exempt_39a').val('false');
                $('.grvatin-article-39a-live-notice').remove();
            }
        },
        
        /**
         * Validate VAT number
         */
        validateVAT: function() {
            var vatNumber = $('#billing_vat_number').val().trim();
            var country = $('#billing_country').val();
            var $vatField = $('#billing_vat_number');
            var $companyField = $('#billing_company');
            var $doyField = $('#billing_doy');
            var $activityField = $('#billing_business_activity');
            
            // Clear previous validation
            grvatinCheckout.clearValidation();
            
            if (!vatNumber || vatNumber.length < 8) {
                return;
            }
            
            // Show loading
            $vatField.after('<span class="grvatin-loading">' + grvatin_params.validating_text + '</span>');
            $vatField.prop('disabled', true);
            
            // AJAX request
            $.ajax({
                url: grvatin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'GRVATIN_validate_vat',
                    vat_number: vatNumber,
                    country: country,
                    nonce: grvatin_params.nonce
                },
                success: function(response) {
                    $vatField.prop('disabled', false);
                    $('.grvatin-loading').remove();
                    
                    console.log('grvatin: AJAX Response:', response);
                    console.log('grvatin: Full response.data:', response.data);
                    console.log('grvatin: response.data keys:', Object.keys(response.data));
                    
                    if (response.success) {
                        // Valid VAT
                        $vatField.after('<span class="grvatin-valid">✓ ' + grvatin_params.valid_text + '</span>');
                        $vatField.addClass('grvatin-field-valid');
                        
                        console.log('grvatin: Company Name:', response.data.company_name);
                        console.log('grvatin: DOY:', response.data.doy);
                        console.log('grvatin: Activity:', response.data.activity);
                        
                        // Auto-fill company data
                        if (response.data.company_name) {
                            console.log('grvatin: Filling company field...');
                            $companyField.val(response.data.company_name);
                            if (grvatin_params.uppercase === 'yes') {
                                $companyField.val($companyField.val().toUpperCase());
                            }
                            $companyField.trigger('change');
                        }
                        
                        if (response.data.doy) {
                            console.log('grvatin: Filling DOY field...');
                            $doyField.val(response.data.doy);
                            if (grvatin_params.uppercase === 'yes') {
                                $doyField.val($doyField.val().toUpperCase());
                            }
                            $doyField.trigger('change');
                        }
                        
                        if (response.data.activity) {
                            console.log('grvatin: Filling activity field...');
                            $activityField.val(response.data.activity);
                            if (grvatin_params.uppercase === 'yes') {
                                $activityField.val($activityField.val().toUpperCase());
                            }
                            $activityField.trigger('change');
                        }
                        
                        // Show VAT exemption message if applicable
                        if (response.data.vat_exempt) {
                            $('.woocommerce-checkout').before('<div class="woocommerce-info grvatin-exemption-notice">ℹ️ ' + response.data.exempt_reason + '</div>');
                        }
                        
                    } else {
                        // Invalid VAT
                        $vatField.after('<span class="grvatin-invalid">✗ ' + response.data.message + '</span>');
                        $vatField.addClass('grvatin-field-invalid');
                    }
                },
                error: function() {
                    $vatField.prop('disabled', false);
                    $('.grvatin-loading').remove();
                    $vatField.after('<span class="grvatin-error">' + grvatin_params.error_text + '</span>');
                }
            });
        },
        
        /**
         * Clear validation messages
         */
        clearValidation: function() {
            $('.grvatin-loading, .grvatin-valid, .grvatin-invalid, .grvatin-error').remove();
            $('#billing_vat_number').removeClass('grvatin-field-valid grvatin-field-invalid');
            $('.grvatin-exemption-notice, .grvatin-article-39a-notice').remove();
        },
        
        /**
         * Toggle Article 39a VAT exemption
         */
        toggleArticle39a: function() {
            var $checkbox = $('#grvatin_article_39a_checkbox');
            var $hiddenField = $('#vat_exempt_39a');
            var $liveNotice = $('.grvatin-article-39a-live-notice');
            
            if ($checkbox.is(':checked')) {
                // Set hidden field
                $hiddenField.val('true');
                
                // Show live exemption notice above order review
                if ($liveNotice.length === 0) {
                    $('.woocommerce-checkout-review-order').before(
                        '<div class="woocommerce-info grvatin-article-39a-live-notice">' +
                        '<strong>✓ Απαλλαγή Άρθρου 39α Ενεργή:</strong> Η παραγγελία σας θα τιμολογηθεί χωρίς ΦΠΑ σύμφωνα με την ΠΟΛ.1150/2017. ' +
                        'Το ΦΠΑ θα αφαιρεθεί αυτόματα από τα σύνολα.' +
                        '</div>'
                    );
                }
                
                // Trigger checkout update to recalculate totals
                $('body').trigger('update_checkout');
            } else {
                // Clear hidden field
                $hiddenField.val('false');
                
                // Remove live notice
                $liveNotice.remove();
                
                // Trigger checkout update
                $('body').trigger('update_checkout');
            }
        }
    };
    
    // Initialize on document ready
    grvatinCheckout.init();
    
    // Re-initialize after WooCommerce updates checkout
    $(document.body).on('updated_checkout', function() {
        grvatinCheckout.toggleInvoiceFields();
    });
});

