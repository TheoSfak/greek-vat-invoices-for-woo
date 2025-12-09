jQuery(document).ready(function($) {
    'use strict';
    
    var wcgviCheckout = {
        
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
                wcgviCheckout.toggleInvoiceFields();
            });
            
            // VAT validation on blur
            $(document).on('blur', '#billing_vat_number', function() {
                wcgviCheckout.validateVAT();
            });
            
            // Country change - clear validation
            $(document).on('change', '#billing_country', function() {
                wcgviCheckout.clearValidation();
            });
            
            // Uppercase conversion
            if (wcgvi_params.uppercase === 'yes') {
                $(document).on('blur', '#billing_company, #billing_doy, #billing_business_activity', function() {
                    $(this).val($(this).val().toUpperCase());
                });
            }
        },
        
        /**
         * Toggle invoice fields visibility
         */
        toggleInvoiceFields: function() {
            // Get value from radio buttons or select
            var invoiceType = $('input[name="billing_invoice_type"]:checked').val() || $('#billing_invoice_type').val();
            var $invoiceFields = $('.wcgvi-invoice-fields');
            
            if (invoiceType === 'invoice') {
                $invoiceFields.addClass('visible').slideDown(300);
                $invoiceFields.find('input').prop('required', true);
            } else {
                $invoiceFields.removeClass('visible').slideUp(300);
                $invoiceFields.find('input').prop('required', false);
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
            wcgviCheckout.clearValidation();
            
            if (!vatNumber || vatNumber.length < 8) {
                return;
            }
            
            // Show loading
            $vatField.after('<span class="wcgvi-loading">' + wcgvi_params.validating_text + '</span>');
            $vatField.prop('disabled', true);
            
            // AJAX request
            $.ajax({
                url: wcgvi_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcgvi_validate_vat',
                    vat_number: vatNumber,
                    country: country,
                    nonce: wcgvi_params.nonce
                },
                success: function(response) {
                    $vatField.prop('disabled', false);
                    $('.wcgvi-loading').remove();
                    
                    console.log('WCGVI: AJAX Response:', response);
                    console.log('WCGVI: Full response.data:', response.data);
                    console.log('WCGVI: response.data keys:', Object.keys(response.data));
                    
                    if (response.success) {
                        // Valid VAT
                        $vatField.after('<span class="wcgvi-valid">✓ ' + wcgvi_params.valid_text + '</span>');
                        $vatField.addClass('wcgvi-field-valid');
                        
                        console.log('WCGVI: Company Name:', response.data.company_name);
                        console.log('WCGVI: DOY:', response.data.doy);
                        console.log('WCGVI: Activity:', response.data.activity);
                        
                        // Auto-fill company data
                        if (response.data.company_name) {
                            console.log('WCGVI: Filling company field...');
                            $companyField.val(response.data.company_name);
                            if (wcgvi_params.uppercase === 'yes') {
                                $companyField.val($companyField.val().toUpperCase());
                            }
                            $companyField.trigger('change');
                        }
                        
                        if (response.data.doy) {
                            console.log('WCGVI: Filling DOY field...');
                            $doyField.val(response.data.doy);
                            if (wcgvi_params.uppercase === 'yes') {
                                $doyField.val($doyField.val().toUpperCase());
                            }
                            $doyField.trigger('change');
                        }
                        
                        if (response.data.activity) {
                            console.log('WCGVI: Filling activity field...');
                            $activityField.val(response.data.activity);
                            if (wcgvi_params.uppercase === 'yes') {
                                $activityField.val($activityField.val().toUpperCase());
                            }
                            $activityField.trigger('change');
                        }
                        
                        // Show VAT exemption message if applicable
                        if (response.data.vat_exempt) {
                            $('.woocommerce-checkout').before('<div class="woocommerce-info wcgvi-exemption-notice">ℹ️ ' + response.data.exempt_reason + '</div>');
                        }
                        
                    } else {
                        // Invalid VAT
                        $vatField.after('<span class="wcgvi-invalid">✗ ' + response.data.message + '</span>');
                        $vatField.addClass('wcgvi-field-invalid');
                    }
                },
                error: function() {
                    $vatField.prop('disabled', false);
                    $('.wcgvi-loading').remove();
                    $vatField.after('<span class="wcgvi-error">' + wcgvi_params.error_text + '</span>');
                }
            });
        },
        
        /**
         * Clear validation messages
         */
        clearValidation: function() {
            $('.wcgvi-loading, .wcgvi-valid, .wcgvi-invalid, .wcgvi-error').remove();
            $('#billing_vat_number').removeClass('wcgvi-field-valid wcgvi-field-invalid');
            $('.wcgvi-exemption-notice').remove();
        }
    };
    
    // Initialize on document ready
    wcgviCheckout.init();
    
    // Re-initialize after WooCommerce updates checkout
    $(document.body).on('updated_checkout', function() {
        wcgviCheckout.toggleInvoiceFields();
    });
});
