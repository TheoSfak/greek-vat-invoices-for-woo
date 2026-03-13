/**
 * Greek VAT Invoices — Block Checkout conditional field visibility
 * Shows/hides VAT, DOY, and Business Activity fields based on invoice type selection.
 * Fields may appear in 'contact' (top) or 'order' (bottom) section depending on settings.
 *
 * DOM structure:
 *   Select wrapper: .wc-block-components-select-input-grvatin-invoice-type
 *   Text wrappers:  .wc-block-components-address-form__grvatin-vat-number etc.
 */
(function () {
    'use strict';

    var SELECT_WRAPPER = 'wc-block-components-select-input-grvatin-invoice-type';
    var DEPENDENT_WRAPPERS = [
        'wc-block-components-address-form__grvatin-company-name',
        'wc-block-components-address-form__grvatin-vat-number',
        'wc-block-components-address-form__grvatin-doy',
        'wc-block-components-address-form__grvatin-business-activity'
    ];

    function toggleFields() {
        var selectWrapper = document.querySelector('.' + SELECT_WRAPPER);
        if (!selectWrapper) return;

        var select = selectWrapper.querySelector('select');
        if (!select) return;

        var isInvoice = select.value === 'invoice';

        for (var i = 0; i < DEPENDENT_WRAPPERS.length; i++) {
            var el = document.querySelector('.' + DEPENDENT_WRAPPERS[i]);
            if (el) {
                el.style.display = isInvoice ? '' : 'none';
            }
        }
    }

    function init() {
        var checkoutForm = document.querySelector('.wc-block-checkout');
        if (!checkoutForm) return;

        toggleFields();

        checkoutForm.addEventListener('change', function (e) {
            if (e.target && e.target.tagName === 'SELECT') {
                var wrapper = e.target.closest('.' + SELECT_WRAPPER);
                if (wrapper) {
                    toggleFields();
                }
            }
        });

        var debounceTimer;
        new MutationObserver(function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(toggleFields, 50);
        }).observe(checkoutForm, {
            childList: true,
            subtree: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
