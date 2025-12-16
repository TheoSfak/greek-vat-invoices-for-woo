<?php
/**
 * Order Handler
 * Manages order meta, invoice numbers, and VAT exemptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCGVI_Order_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Apply VAT exemptions
        add_action('woocommerce_checkout_create_order', array($this, 'apply_vat_exemption'), 10, 2);
        
        // Save VIES validation status
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_vies_status'));
        
        // Generate invoice number
        add_action('woocommerce_order_status_completed', array($this, 'generate_invoice_number'));
        
        // Add search by VAT
        add_filter('woocommerce_shop_order_search_fields', array($this, 'add_vat_search'));
        
        // Display invoice number in admin
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_invoice_number'));
    }
    
    /**
     * Apply VAT exemption if qualified
     */
    public function apply_vat_exemption($order, $data) {
        $country = $data['billing_country'];
        $invoice_type = isset($data['billing_invoice_type']) ? $data['billing_invoice_type'] : 'receipt';
        
        if ($invoice_type !== 'invoice') {
            return;
        }
        
        $vat_number = isset($data['billing_vat_number']) ? $data['billing_vat_number'] : '';
        if (empty($vat_number)) {
            return;
        }
        
        $should_exempt = false;
        
        // Check VIES exemption (EU but not Greece)
        if ($country !== 'GR' && get_option('wcgvi_vat_exempt_eu') === 'yes') {
            // Nonce is verified by WooCommerce checkout process
            $vies_validated = isset($_POST['vies_validated']) && sanitize_text_field(wp_unslash($_POST['vies_validated'])) === 'true'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ($vies_validated) {
                $should_exempt = true;
                $order->add_meta_data('_vat_exempt_reason', 'VIES validated - Intra-EU supply');
            }
        }
        
        // Check non-EU exemption
        $eu_countries = array('AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK');
        if (!in_array($country, $eu_countries) && get_option('wcgvi_vat_exempt_non_eu') === 'yes') {
            $should_exempt = true;
            $order->add_meta_data('_vat_exempt_reason', 'Third country export');
        }
        
        // Check Article 39a exemption (Greek businesses only)
        if ($country === 'GR' && get_option('wcgvi_article_39a') === 'yes') {
            // Nonce is verified by WooCommerce checkout process
            $exempt_39a = isset($_POST['vat_exempt_39a']) && sanitize_text_field(wp_unslash($_POST['vat_exempt_39a'])) === 'true'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ($exempt_39a) {
                // Check if products are in allowed categories
                $allowed_categories = get_option('wcgvi_article_39a_categories', array());
                
                if ($this->check_article_39a_eligible($order, $allowed_categories)) {
                    $should_exempt = true;
                    $order->add_meta_data('_vat_exempt_reason', 'Απαλλαγή Άρθρου 39α (ΠΟΛ.1150/2017)');
                }
            }
        }
        
        if ($should_exempt) {
            // Remove VAT from all items
            foreach ($order->get_items() as $item) {
                $item->set_total_tax(0);
                $item->set_taxes(false);
                $item->save();
            }
            
            // Remove shipping VAT
            foreach ($order->get_items('shipping') as $item) {
                $item->set_total_tax(0);
                $item->set_taxes(false);
                $item->save();
            }
            
            $order->set_cart_tax(0);
            $order->set_shipping_tax(0);
            $order->add_meta_data('_vat_exempted', 'yes');
        }
    }
    
    /**
     * Save VIES validation status
     */
    public function save_vies_status($order_id) {
        // Nonce is verified by WooCommerce checkout process
        if (isset($_POST['vies_validated']) && sanitize_text_field(wp_unslash($_POST['vies_validated'])) === 'true') { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            update_post_meta($order_id, '_vies_validated', 'yes');
        }
        
        if (isset($_POST['vat_exempt_39a']) && sanitize_text_field(wp_unslash($_POST['vat_exempt_39a'])) === 'true') { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            update_post_meta($order_id, '_vat_exempt_39a', 'yes');
        }
    }
    
    /**
     * Generate invoice number for completed orders
     */
    public function generate_invoice_number($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Check if already has invoice number
        if ($order->get_meta('_invoice_number')) {
            return;
        }
        
        $invoice_type = $order->get_meta('_billing_invoice_type');
        if ($invoice_type !== 'invoice' && $invoice_type !== 'receipt') {
            $invoice_type = 'receipt';
        }
        
        // Get next invoice number
        $prefix = get_option('wcgvi_invoice_prefix', 'INV');
        $year = gmdate('Y');
        $counter_key = 'wcgvi_invoice_counter_' . $year;
        
        $counter = get_option($counter_key, 0);
        $counter++;
        update_option($counter_key, $counter);
        
        $invoice_number = sprintf('%s-%s-%04d', $prefix, $year, $counter);
        
        // Save to order
        $order->update_meta_data('_invoice_number', $invoice_number);
        $order->update_meta_data('_invoice_date', current_time('mysql'));
        $order->save();
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'wcgvi_invoices';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom invoice table requires direct query
        $wpdb->insert($table_name, array(
            'order_id' => $order_id,
            'invoice_number' => $invoice_number,
            'invoice_type' => $invoice_type,
            'invoice_date' => current_time('mysql')
        ));
        wp_cache_delete('wcgvi_invoice_' . $order_id, 'wc_greek_vat_invoices');
    }
    
    /**
     * Add VAT number to order search
     */
    public function add_vat_search($search_fields) {
        $search_fields[] = '_billing_vat_number';
        return $search_fields;
    }
    
    /**
     * Display invoice number in admin
     */
    public function display_invoice_number($order) {
        $invoice_number = $order->get_meta('_invoice_number');
        $invoice_date = $order->get_meta('_invoice_date');
        
        if ($invoice_number) {
            echo '<div class="wcgvi-invoice-info">';
            echo '<p class="form-field form-field-wide">';
            echo '<strong>' . esc_html__('Invoice Number:', 'wc-greek-vat-invoices') . '</strong> ';
            echo esc_html($invoice_number);
            if ($invoice_date) {
                echo ' (' . esc_html(date_i18n(get_option('date_format'), strtotime($invoice_date))) . ')';
            }
            echo '</p>';
            
            $vat_exempt = $order->get_meta('_vat_exempted');
            if ($vat_exempt === 'yes') {
                $reason = $order->get_meta('_vat_exempt_reason');
                echo '<p class="form-field form-field-wide">';
                echo '<strong>' . esc_html__('VAT Status:', 'wc-greek-vat-invoices') . '</strong> ';
                echo '<span style="color: #46b450;">' . esc_html__('Exempted', 'wc-greek-vat-invoices') . '</span>';
                if ($reason) {
                    echo ' - ' . esc_html($reason);
                }
                echo '</p>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Check if order is eligible for Article 39a exemption based on product categories
     */
    private function check_article_39a_eligible($order, $allowed_categories) {
        // If no categories are selected, allow all products
        if (empty($allowed_categories)) {
            return true;
        }
        
        // Check each product in the order
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }
            
            // Get product category IDs
            $product_category_ids = $product->get_category_ids();
            
            // Check if product belongs to any allowed category
            $is_allowed = false;
            foreach ($product_category_ids as $cat_id) {
                if (in_array($cat_id, $allowed_categories)) {
                    $is_allowed = true;
                    break;
                }
            }
            
            // If any product is not in allowed categories, reject exemption
            if (!$is_allowed) {
                return false;
            }
        }
        
        return true;
    }
}
