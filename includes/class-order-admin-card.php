<?php
/**
 * Order Admin Card
 * Shows the invoice/receipt details as one tidy card in the Billing column of the order edit screen
 */

if (!defined('ABSPATH')) {
    exit;
}

class GRVATIN_Order_Admin_Card {

    private static $instance = null;

    /** Card HTML per order id, built at most once per request */
    private $html_cache = array();

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Print the card at the bottom of the Billing column (works for Classic and Block checkout)
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'render_card'));

        // Block checkout: WooCommerce prints our fields as plain label/value lines in the read-only view.
        // The card replaces them, so hide them there. The edit form (pencil icon) is left untouched.
        add_filter('woocommerce_admin_billing_fields', array($this, 'hide_plain_lines'), 20, 3);
        add_filter('woocommerce_admin_shipping_fields', array($this, 'hide_plain_lines'), 20, 3);
    }

    /**
     * Hide our additional checkout fields in the read-only order view, but only for
     * orders where the card was built successfully, so no information can disappear.
     */
    public function hide_plain_lines($fields, $order = null, $context = 'edit') {
        if ($context !== 'view' || !is_array($fields) || !($order instanceof WC_Order)) {
            return $fields;
        }

        if ($this->get_card_html($order) === '') {
            return $fields;
        }

        // WooCommerce keys these fields as 'grvatin/vat-number' and puts the meta key
        // ('_wc_other/grvatin/vat-number') in each field's 'id', so match on either.
        foreach ($fields as $key => $field) {
            if (!is_array($field)) {
                continue;
            }

            $id = isset($field['id']) ? (string) $field['id'] : '';

            if (strpos((string) $key, 'grvatin/') === 0 || strpos($id, '_wc_other/grvatin/') === 0) {
                $fields[$key]['show'] = false;
            }
        }

        return $fields;
    }

    /**
     * Print the card. Read-only and fail-open: on any error nothing is printed and
     * the order screen is left exactly as WooCommerce built it.
     */
    public function render_card($order) {
        if (!($order instanceof WC_Order)) {
            return;
        }

        // Built entirely from escaped values in build_html()
        echo $this->get_card_html($order); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * The card HTML, or '' when there is nothing to show or building it failed.
     * Cached per order so hiding the plain lines and printing the card always agree.
     */
    private function get_card_html($order) {
        $id = $order->get_id();

        if (!isset($this->html_cache[$id])) {
            try {
                $this->html_cache[$id] = $this->build_html($order);
            } catch (\Throwable $e) {
                $this->html_cache[$id] = '';
            }
        }

        return $this->html_cache[$id];
    }

    /**
     * 'invoice', 'receipt', or '' when the order has no invoice/receipt choice.
     */
    private function get_type($order) {
        $type = $order->get_meta('_billing_invoice_type');

        return ($type === 'invoice' || $type === 'receipt') ? $type : '';
    }

    /**
     * Build the card. Every value is escaped here.
     */
    private function build_html($order) {
        $type = $this->get_type($order);

        if ($type === '') {
            return '';
        }

        $is_invoice = ($type === 'invoice');

        $head = '<span class="grvatin-order-card__pill">' . esc_html($is_invoice
            ? __('Τιμολόγιο', 'greek-vat-invoices-for-woocommerce')
            : __('Απόδειξη', 'greek-vat-invoices-for-woocommerce')) . '</span>';

        $invoice_number = $order->get_meta('_invoice_number');
        if ($invoice_number) {
            $number = esc_html($invoice_number);
            $invoice_date = $order->get_meta('_invoice_date');
            if ($invoice_date) {
                $date = mysql2date(get_option('date_format'), $invoice_date);
                if ($date) {
                    $number .= ' &middot; ' . esc_html($date);
                }
            }
            $head .= '<span class="grvatin-order-card__number">' . $number . '</span>';
        }

        $html  = '<div class="grvatin-order-card grvatin-order-card--' . ($is_invoice ? 'invoice' : 'receipt') . '">';
        $html .= '<div class="grvatin-order-card__head">' . $head . '</div>';

        if ($is_invoice) {
            $html .= '<dl class="grvatin-order-card__grid">';
            $html .= $this->item(__('Επωνυμία', 'greek-vat-invoices-for-woocommerce'), $order->get_billing_company(), true);
            $html .= $this->item(__('ΑΦΜ', 'greek-vat-invoices-for-woocommerce'), $order->get_meta('_billing_vat_number'), false, true);
            $html .= $this->item(__('ΔΟΥ', 'greek-vat-invoices-for-woocommerce'), $order->get_meta('_billing_doy'));
            $html .= $this->item(__('Επάγγελμα', 'greek-vat-invoices-for-woocommerce'), $order->get_meta('_billing_business_activity'), true);
            $html .= '</dl>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * One label/value cell, escaped. Empty values show a muted dash.
     */
    private function item($label, $value, $full = false, $mono = false) {
        $classes = 'grvatin-order-card__item' . ($full ? ' grvatin-order-card__item--full' : '');
        $content = ($value !== '' && $value !== null && $value !== false)
            ? esc_html($value)
            : '<span class="grvatin-order-card__empty">&mdash;</span>';

        return '<div class="' . $classes . '"><dt>' . esc_html($label) . '</dt><dd' . ($mono ? ' class="grvatin-order-card__mono"' : '') . '>' . $content . '</dd></div>';
    }
}
