<?php
/**
 * Checkout Fields Handler
 * Manages invoice/receipt selection and billing fields
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCGVI_Checkout_Fields {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add invoice/receipt selection field - Run AFTER Smart Checkout Fields Manager (999)
        add_filter('woocommerce_checkout_fields', array($this, 'add_invoice_fields'), 1000, 1);
        
        // Validate fields
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_invoice_fields'), 10, 2);
        
        // Save custom fields
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_invoice_fields'));
        add_action('woocommerce_checkout_update_customer', array($this, 'save_customer_fields'), 10, 2);
        
        // Display fields in admin order
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_invoice_fields_admin'));
        
        // Display fields in emails
        add_filter('woocommerce_email_order_meta_fields', array($this, 'display_invoice_fields_email'), 10, 3);
        
        // Display fields in customer account
        add_filter('woocommerce_order_formatted_billing_address', array($this, 'add_invoice_to_formatted_address'), 10, 2);
        
        // Remove "(optional)" text from fields
        add_filter('woocommerce_form_field', array($this, 'remove_optional_text'), 10, 4);
    }
    
    /**
     * Add invoice/receipt fields to checkout
     */
    public function add_invoice_fields($fields) {
        // Get the position setting
        $position = get_option('wcgvi_invoice_type_position', 'after_billing_email');
        
        // Map position to priority
        $priority_map = array(
            'top' => 5,
            'before_billing_first_name' => 9,
            'after_billing_last_name' => 21,
            'after_billing_phone' => 101,
            'after_billing_email' => 111,
            'after_billing_country' => 41,
            'after_billing_address_1' => 51,
            'after_billing_city' => 71,
            'after_billing_postcode' => 91,
            'bottom' => 999
        );
        
        $invoice_type_priority = isset($priority_map[$position]) ? $priority_map[$position] : 31;
        
        // Add document type selection
        $fields['billing']['billing_invoice_type'] = array(
            'type' => 'radio',
            'label' => __('Τύπος Παραστατικού', 'wc-greek-vat-invoices'),
            'required' => true,
            'class' => array('form-row-wide', 'wcgvi-invoice-type-field'),
            'priority' => $invoice_type_priority,
            'options' => array(
                'receipt' => __('Απόδειξη', 'wc-greek-vat-invoices'),
                'invoice' => __('Τιμολόγιο', 'wc-greek-vat-invoices')
            ),
            'default' => 'receipt'
        );
        
        // Company name - configure existing WooCommerce field
        $fields['billing']['billing_company']['label'] = __('Επωνυμία Επιχείρησης', 'wc-greek-vat-invoices');
        $fields['billing']['billing_company']['placeholder'] = __('π.χ. ΚΩΝΣΤΑΝΤΙΝΟΣ ΠΑΠΑΔΟΠΟΥΛΟΣ & ΣΙΑ ΟΕ', 'wc-greek-vat-invoices');
        if (!isset($fields['billing']['billing_company']['class'])) {
            $fields['billing']['billing_company']['class'] = array();
        }
        $fields['billing']['billing_company']['class'][] = 'form-row-wide';
        $fields['billing']['billing_company']['class'][] = 'wcgvi-invoice-fields';
        $fields['billing']['billing_company']['class'][] = 'hidden-by-default';
        $fields['billing']['billing_company']['required'] = false;
        $fields['billing']['billing_company']['priority'] = $invoice_type_priority + 1;
        $fields['billing']['billing_company']['custom_attributes'] = array(
            'data-invoice-field' => 'true'
        );
        
        // VAT Number (AFM)
        $fields['billing']['billing_vat_number'] = array(
            'type' => 'text',
            'label' => __('ΑΦΜ', 'wc-greek-vat-invoices'),
            'placeholder' => __('π.χ. 123456789', 'wc-greek-vat-invoices'),
            'required' => false,
            'class' => array('form-row-first', 'wcgvi-invoice-fields', 'wcgvi-vat-number'),
            'priority' => $invoice_type_priority + 2,
            'maxlength' => 9,
            'custom_attributes' => array(
                'pattern' => '[0-9]{9}',
                'data-validate' => 'vat'
            )
        );
        
        // Tax Office (DOY)
        $fields['billing']['billing_doy'] = array(
            'type' => 'text',
            'label' => __('ΔΟΥ', 'wc-greek-vat-invoices'),
            'placeholder' => __('π.χ. Α\' ΑΘΗΝΩΝ', 'wc-greek-vat-invoices'),
            'required' => false,
            'class' => array('form-row-last', 'wcgvi-invoice-fields'),
            'priority' => $invoice_type_priority + 3
        );
        
        // Business Activity
        $fields['billing']['billing_business_activity'] = array(
            'type' => 'text',
            'label' => __('Επάγγελμα', 'wc-greek-vat-invoices'),
            'placeholder' => __('π.χ. ΛΙΑΝΙΚΟ ΕΜΠΟΡΙΟ', 'wc-greek-vat-invoices'),
            'required' => false,
            'class' => array('form-row-wide', 'wcgvi-invoice-fields'),
            'priority' => $invoice_type_priority + 4
        );
        
        return $fields;
    }
    
    /**
     * Validate invoice fields
     */
    public function validate_invoice_fields($data, $errors) {
        // Nonce is verified by WooCommerce checkout process
        $invoice_type = isset($_POST['billing_invoice_type']) ? sanitize_text_field(wp_unslash($_POST['billing_invoice_type'])) : 'receipt'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        
        if ($invoice_type === 'invoice') {
            // Validate required fields for invoice
            if (empty($_POST['billing_company'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $errors->add('billing_company', __('Η επωνυμία είναι υποχρεωτική για την έκδοση τιμολογίου.', 'wc-greek-vat-invoices'));
            }
            
            if (empty($_POST['billing_vat_number'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $errors->add('billing_vat_number', __('Το ΑΦΜ είναι υποχρεωτικό για την έκδοση τιμολογίου.', 'wc-greek-vat-invoices'));
            } elseif (isset($_POST['billing_vat_number']) && !preg_match('/^[0-9]{9}$/', sanitize_text_field(wp_unslash($_POST['billing_vat_number'])))) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $errors->add('billing_vat_number', __('Το ΑΦΜ πρέπει να είναι 9 ψηφία.', 'wc-greek-vat-invoices'));
            }
            
            if (empty($_POST['billing_doy'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $errors->add('billing_doy', __('Η ΔΟΥ είναι υποχρεωτική για την έκδοση τιμολογίου.', 'wc-greek-vat-invoices'));
            }
            
            if (empty($_POST['billing_business_activity'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $errors->add('billing_business_activity', __('Το επάγγελμα είναι υποχρεωτικό για την έκδοση τιμολογίου.', 'wc-greek-vat-invoices'));
            }
        }
    }
    
    /**
     * Save invoice fields to order
     */
    public function save_invoice_fields($order_id) {
        // Nonce is verified by WooCommerce checkout process
        if (isset($_POST['billing_invoice_type'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            update_post_meta($order_id, '_billing_invoice_type', sanitize_text_field(wp_unslash($_POST['billing_invoice_type']))); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }
        
        if (isset($_POST['billing_vat_number'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $vat = sanitize_text_field(wp_unslash($_POST['billing_vat_number'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (get_option('wcgvi_uppercase_fields') === 'yes') {
                $vat = strtoupper($vat);
            }
            update_post_meta($order_id, '_billing_vat_number', $vat);
        }
        
        if (isset($_POST['billing_doy'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $doy = sanitize_text_field(wp_unslash($_POST['billing_doy'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (get_option('wcgvi_uppercase_fields') === 'yes') {
                $doy = strtoupper($doy);
            }
            update_post_meta($order_id, '_billing_doy', $doy);
        }
        
        if (isset($_POST['billing_business_activity'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $activity = sanitize_text_field(wp_unslash($_POST['billing_business_activity'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (get_option('wcgvi_uppercase_fields') === 'yes') {
                $activity = strtoupper($activity);
            }
            update_post_meta($order_id, '_billing_business_activity', $activity);
        }
    }
    
    /**
     * Save fields to customer profile
     */
    public function save_customer_fields($customer, $data) {
        if (isset($data['billing_invoice_type'])) {
            $customer->update_meta_data('billing_invoice_type', sanitize_text_field($data['billing_invoice_type']));
        }
        
        if (isset($data['billing_vat_number']) && !empty($data['billing_vat_number'])) {
            $vat = sanitize_text_field($data['billing_vat_number']);
            if (get_option('wcgvi_uppercase_fields') === 'yes') {
                $vat = strtoupper($vat);
            }
            $customer->update_meta_data('billing_vat_number', $vat);
        }
        
        if (isset($data['billing_doy']) && !empty($data['billing_doy'])) {
            $doy = sanitize_text_field($data['billing_doy']);
            if (get_option('wcgvi_uppercase_fields') === 'yes') {
                $doy = strtoupper($doy);
            }
            $customer->update_meta_data('billing_doy', $doy);
        }
        
        if (isset($data['billing_business_activity']) && !empty($data['billing_business_activity'])) {
            $activity = sanitize_text_field($data['billing_business_activity']);
            if (get_option('wcgvi_uppercase_fields') === 'yes') {
                $activity = strtoupper($activity);
            }
            $customer->update_meta_data('billing_business_activity', $activity);
        }
    }
    
    /**
     * Display invoice fields in admin order page
     */
    public function display_invoice_fields_admin($order) {
        $invoice_type = $order->get_meta('_billing_invoice_type');
        $vat_number = $order->get_meta('_billing_vat_number');
        $doy = $order->get_meta('_billing_doy');
        $activity = $order->get_meta('_billing_business_activity');
        $company = $order->get_billing_company();
        
        if ($invoice_type === 'invoice') {
            echo '<div class="wcgvi-admin-invoice-fields">';
            echo '<h3>' . esc_html__('Στοιχεία Τιμολογίου', 'wc-greek-vat-invoices') . '</h3>';
            
            if ($company) {
                echo '<p><strong>' . esc_html__('Επωνυμία:', 'wc-greek-vat-invoices') . '</strong> ' . esc_html($company) . '</p>';
            }
            
            if ($vat_number) {
                echo '<p><strong>' . esc_html__('ΑΦΜ:', 'wc-greek-vat-invoices') . '</strong> ' . esc_html($vat_number) . '</p>';
            }
            
            if ($doy) {
                echo '<p><strong>' . esc_html__('ΔΟΥ:', 'wc-greek-vat-invoices') . '</strong> ' . esc_html($doy) . '</p>';
            }
            
            if ($activity) {
                echo '<p><strong>' . esc_html__('Επάγγελμα:', 'wc-greek-vat-invoices') . '</strong> ' . esc_html($activity) . '</p>';
            }
            
            echo '</div>';
        } else {
            echo '<p><strong>' . esc_html__('Document Type:', 'wc-greek-vat-invoices') . '</strong> ' . esc_html__('Receipt', 'wc-greek-vat-invoices') . '</p>';
        }
    }
    
    /**
     * Display invoice fields in emails
     */
    public function display_invoice_fields_email($fields, $sent_to_admin, $order) {
        $invoice_type = $order->get_meta('_billing_invoice_type');
        
        if ($invoice_type === 'invoice') {
            $company = $order->get_billing_company();
            if ($company) {
                $fields['billing_company'] = array(
                    'label' => __('Επωνυμία', 'wc-greek-vat-invoices'),
                    'value' => $company
                );
            }
            
            $fields['billing_vat_number'] = array(
                'label' => __('ΑΦΜ', 'wc-greek-vat-invoices'),
                'value' => $order->get_meta('_billing_vat_number')
            );
            
            $fields['billing_doy'] = array(
                'label' => __('ΔΟΥ', 'wc-greek-vat-invoices'),
                'value' => $order->get_meta('_billing_doy')
            );
            
            $fields['billing_business_activity'] = array(
                'label' => __('Επάγγελμα', 'wc-greek-vat-invoices'),
                'value' => $order->get_meta('_billing_business_activity')
            );
        }
        
        return $fields;
    }
    
    /**
     * Add invoice info to formatted billing address
     */
    public function add_invoice_to_formatted_address($address, $order) {
        $invoice_type = $order->get_meta('_billing_invoice_type');
        
        if ($invoice_type === 'invoice') {
            $vat_number = $order->get_meta('_billing_vat_number');
            $doy = $order->get_meta('_billing_doy');
            
            if ($vat_number) {
                $address['vat_number'] = $vat_number;
            }
            
            if ($doy) {
                $address['doy'] = $doy;
            }
        }
        
        return $address;
    }
    
    /**
     * Remove "(optional)" text from invoice fields
     */
    public function remove_optional_text($field, $key, $args, $value) {
        // Only apply to our invoice fields
        $invoice_fields = array('billing_company', 'billing_vat_number', 'billing_doy', 'billing_business_activity');
        
        if (in_array($key, $invoice_fields)) {
            // Remove optional text
            $field = str_replace('<span class="optional">(προαιρετικό)</span>', '', $field);
            $field = str_replace('<span class="optional">(optional)</span>', '', $field);
        }
        
        return $field;
    }
}
