<?php
/**
 * Order Preview
 * Shows invoice/receipt details in the WooCommerce Orders list "Quick Preview" (eye icon) modal
 */

if (!defined('ABSPATH')) {
    exit;
}

class GRVATIN_Order_Preview {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Add our pre-built HTML to the data WooCommerce sends to the preview modal
        add_filter('woocommerce_admin_order_preview_get_order_details', array($this, 'add_invoice_details'), 10, 2);

        // Print the slot for it at the top of the modal (before Billing / Shipping details)
        add_action('woocommerce_admin_order_preview_start', array($this, 'render_slot'));
    }

    /**
     * Attach the invoice block to the order preview data.
     *
     * Read-only and fail-open: if anything goes wrong the preview is returned
     * exactly as WooCommerce built it, so the modal can never be broken by this.
     */
    public function add_invoice_details($order_details, $order) {
        if (!is_array($order_details) || !($order instanceof WC_Order)) {
            return $order_details;
        }

        try {
            $html = $this->build_html($order);
            if ($html !== '') {
                $order_details['grvatin_invoice_html'] = $html;
            }
        } catch (\Throwable $e) {
            return $order_details;
        }

        return $order_details;
    }

    /**
     * Output the (static) placeholder inside WooCommerce's preview JS template.
     * {{{ }}} prints the already-escaped HTML built by build_html().
     */
    public function render_slot() {
        echo '<# if ( data.grvatin_invoice_html ) { #>{{{ data.grvatin_invoice_html }}}<# } #>';
    }

    /**
     * Build the invoice block. Every value is escaped here because the
     * preview template prints it as raw HTML.
     */
    private function build_html($order) {
        $type = $order->get_meta('_billing_invoice_type');

        if ($type !== 'invoice' && $type !== 'receipt') {
            return '';
        }

        $is_invoice = ($type === 'invoice');

        $heading = esc_html($is_invoice
            ? __('Τιμολόγιο', 'greek-vat-invoices-for-woocommerce')
            : __('Απόδειξη', 'greek-vat-invoices-for-woocommerce'));

        $invoice_number = $order->get_meta('_invoice_number');
        if ($invoice_number) {
            $heading .= ' <span class="grvatin-preview-invoice__number">&middot; ' . esc_html($invoice_number);
            $invoice_date = $order->get_meta('_invoice_date');
            if ($invoice_date) {
                $date = mysql2date(get_option('date_format'), $invoice_date);
                if ($date) {
                    $heading .= ' (' . esc_html($date) . ')';
                }
            }
            $heading .= '</span>';
        }

        $html  = '<div class="grvatin-preview-invoice grvatin-preview-invoice--' . ($is_invoice ? 'invoice' : 'receipt') . '">';
        $html .= '<h2>' . $heading . '</h2>';

        if ($is_invoice) {
            $company = $order->get_billing_company();
            if ($company) {
                $html .= '<p>' . $this->field(__('Επωνυμία:', 'greek-vat-invoices-for-woocommerce'), $company) . '</p>';
            }

            $ids = '';
            $vat_number = $order->get_meta('_billing_vat_number');
            if ($vat_number) {
                $ids .= $this->field(__('ΑΦΜ:', 'greek-vat-invoices-for-woocommerce'), $vat_number);
            }
            $doy = $order->get_meta('_billing_doy');
            if ($doy) {
                $ids .= $this->field(__('ΔΟΥ:', 'greek-vat-invoices-for-woocommerce'), $doy);
            }
            if ($ids !== '') {
                $html .= '<p>' . $ids . '</p>';
            }

            $activity = $order->get_meta('_billing_business_activity');
            if ($activity) {
                $html .= '<p>' . $this->field(__('Επάγγελμα:', 'greek-vat-invoices-for-woocommerce'), $activity) . '</p>';
            }
        }

        if ($order->get_meta('_vat_exempted') === 'yes') {
            $html .= '<p><strong>' . esc_html__('VAT Status:', 'greek-vat-invoices-for-woocommerce') . '</strong> ';
            $html .= '<span class="grvatin-preview-invoice__exempt">' . esc_html__('Exempted', 'greek-vat-invoices-for-woocommerce') . '</span>';
            $reason = $order->get_meta('_vat_exempt_reason');
            if ($reason) {
                $html .= ' - ' . esc_html($reason);
            }
            $html .= '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * One "Label: value" pair, escaped. The trailing space keeps adjacent pairs
     * separated when the text is copied (the visual gap itself comes from CSS).
     */
    private function field($label, $value) {
        return '<span class="grvatin-preview-invoice__field"><strong>' . esc_html($label) . '</strong> ' . esc_html($value) . '</span> ';
    }
}
