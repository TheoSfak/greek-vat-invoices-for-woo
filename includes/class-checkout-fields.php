<?php
/**
 * Checkout Fields Handler
 * Manages invoice/receipt selection and billing fields
 */

if (!defined('ABSPATH')) {
    exit;
}

class GRVATIN_Checkout_Fields {
    
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
        
        // Add Article 39a checkbox after business activity field
        add_action('woocommerce_after_checkout_billing_form', array($this, 'add_article_39a_checkbox'));
        
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
        $position = get_option('grvatin_invoice_type_position', 'after_billing_email');
        
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
            'label' => __('Τύπος Παραστατικού', 'greek-vat-invoices-for-woocommerce'),
            'required' => true,
            'class' => array('form-row-wide', 'wcgvi-invoice-type-field'),
            'priority' => $invoice_type_priority,
            'options' => array(
                'receipt' => __('Απόδειξη', 'greek-vat-invoices-for-woocommerce'),
                'invoice' => __('Τιμολόγιο', 'greek-vat-invoices-for-woocommerce')
            ),
            'default' => 'receipt'
        );
        
        // Company name - configure existing WooCommerce field
        $fields['billing']['billing_company']['label'] = __('Επωνυμία Επιχείρησης', 'greek-vat-invoices-for-woocommerce');
        $fields['billing']['billing_company']['placeholder'] = __('π.χ. ΚΩΝΣΤΑΝΤΙΝΟΣ ΠΑΠΑΔΟΠΟΥΛΟΣ & ΣΙΑ ΟΕ', 'greek-vat-invoices-for-woocommerce');
        if (!isset($fields['billing']['billing_company']['class'])) {
            $fields['billing']['billing_company']['class'] = array();
        }
        $fields['billing']['billing_company']['class'][] = 'form-row-wide';
        $fields['billing']['billing_company']['class'][] = 'grvatin-invoice-fields';
        $fields['billing']['billing_company']['class'][] = 'hidden-by-default';
        $fields['billing']['billing_company']['required'] = false;
        $fields['billing']['billing_company']['priority'] = $invoice_type_priority + 1;
        $fields['billing']['billing_company']['custom_attributes'] = array(
            'data-invoice-field' => 'true'
        );
        
        // VAT Number (AFM)
        $fields['billing']['billing_vat_number'] = array(
            'type' => 'text',
            'label' => __('ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
            'placeholder' => __('π.χ. 123456789', 'greek-vat-invoices-for-woocommerce'),
            'required' => false,
            'class' => array('form-row-first', 'grvatin-invoice-fields', 'wcgvi-vat-number'),
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
            'label' => __('ΔΟΥ', 'greek-vat-invoices-for-woocommerce'),
            'placeholder' => __('π.χ. Α\' ΑΘΗΝΩΝ', 'greek-vat-invoices-for-woocommerce'),
            'required' => false,
            'class' => array('form-row-last', 'grvatin-invoice-fields'),
            'priority' => $invoice_type_priority + 3
        );
        
        // Business Activity
        $fields['billing']['billing_business_activity'] = array(
            'type' => 'text',
            'label' => __('Επάγγελμα', 'greek-vat-invoices-for-woocommerce'),
            'placeholder' => __('π.χ. ΛΙΑΝΙΚΟ ΕΜΠΟΡΙΟ', 'greek-vat-invoices-for-woocommerce'),
            'required' => false,
            'class' => array('form-row-wide', 'grvatin-invoice-fields'),
            'priority' => $invoice_type_priority + 4
        );
        
        // Hidden field for Article 39a (will be controlled by custom checkbox)
        if (get_option('GRVATIN_article_39a') === 'yes') {
            $fields['billing']['vat_exempt_39a'] = array(
                'type' => 'hidden',
                'default' => 'false',
                'class' => array('wcgvi-hidden-field')
            );
        }
        
        return $fields;
    }
    
    /**
     * Add Article 39a checkbox after billing form
     */
    public function add_article_39a_checkbox($checkout) {
        if (get_option('GRVATIN_article_39a') !== 'yes') {
            return;
        }
        
        // Get allowed categories
        $allowed_categories = get_option('GRVATIN_article_39a_categories', array());
        $categories_text = '';
        
        if (!empty($allowed_categories)) {
            $category_names = array();
            foreach ($allowed_categories as $cat_id) {
                $term = get_term($cat_id, 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $category_names[] = $term->name;
                }
            }
            if (!empty($category_names)) {
                $categories_text = '<li>📦 ' . esc_html__('Ισχύει για τις κατηγορίες:', 'greek-vat-invoices-for-woocommerce') . ' <strong>' . esc_html(implode(', ', $category_names)) . '</strong></li>';
            }
        } else {
            $categories_text = '<li>✓ ' . esc_html__('Ισχύει για όλες τις κατηγορίες προϊόντων/υπηρεσιών', 'greek-vat-invoices-for-woocommerce') . '</li>';
        }
        
        echo '<div class="wcgvi-article-39a-wrapper grvatin-invoice-fields" style="display:none;">';
        echo '<div class="wcgvi-article-39a-checkbox-field">';
        echo '<label class="wcgvi-article-39a-label">';
        echo '<input type="checkbox" id="GRVATIN_article_39a_checkbox" name="GRVATIN_article_39a_checkbox" value="1" />';
        echo '<span class="wcgvi-article-39a-text">' . esc_html__('Απαλλαγή Άρθρου 39α (ΠΟΛ.1150/2017)', 'greek-vat-invoices-for-woocommerce') . '</span>';
        echo '</label>';
        echo '<div class="wcgvi-article-39a-notice">';
        echo '<p><strong>' . esc_html__('Προϋποθέσεις Απαλλαγής:', 'greek-vat-invoices-for-woocommerce') . '</strong></p>';
        echo '<ul>';
        echo '<li>✓ ' . esc_html__('Ελληνική επιχείρηση με έδρα στην Ελλάδα', 'greek-vat-invoices-for-woocommerce') . '</li>';
        echo '<li>✓ ' . esc_html__('Ετήσιος τζίρος μικρότερος των 10.000€', 'greek-vat-invoices-for-woocommerce') . '</li>';
        echo '<li>✓ ' . esc_html__('Μη υπέρβαση ορίου κατά το τρέχον έτος', 'greek-vat-invoices-for-woocommerce') . '</li>';
        echo wp_kses_post($categories_text);
        echo '</ul>';
        echo '<p class="wcgvi-article-39a-warning">';
        echo '<em>' . esc_html__('⚠️ Η επιλογή αυτής της απαλλαγής είναι ευθύνη της επιχείρησης. Βεβαιωθείτε ότι πληροίτε τις προϋποθέσεις πριν την επιλέξετε.', 'greek-vat-invoices-for-woocommerce') . '</em>';
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
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
                $errors->add('billing_company', __('Η επωνυμία είναι υποχρεωτική για την έκδοση τιμολογίου.', 'greek-vat-invoices-for-woocommerce'));
            }
            
            if (empty($_POST['billing_vat_number'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $errors->add('billing_vat_number', __('Το ΑΦΜ είναι υποχρεωτικό για την έκδοση τιμολογίου.', 'greek-vat-invoices-for-woocommerce'));
            } elseif (isset($_POST['billing_vat_number']) && !preg_match('/^[0-9]{9}$/', sanitize_text_field(wp_unslash($_POST['billing_vat_number'])))) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $errors->add('billing_vat_number', __('Το ΑΦΜ πρέπει να είναι 9 ψηφία.', 'greek-vat-invoices-for-woocommerce'));
            }
            
            if (empty($_POST['billing_doy'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $errors->add('billing_doy', __('Η ΔΟΥ είναι υποχρεωτική για την έκδοση τιμολογίου.', 'greek-vat-invoices-for-woocommerce'));
            }
            
            if (empty($_POST['billing_business_activity'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $errors->add('billing_business_activity', __('Το επάγγελμα είναι υποχρεωτικό για την έκδοση τιμολογίου.', 'greek-vat-invoices-for-woocommerce'));
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
            if (get_option('GRVATIN_uppercase_fields') === 'yes') {
                $vat = strtoupper($vat);
            }
            update_post_meta($order_id, '_billing_vat_number', $vat);
        }
        
        if (isset($_POST['billing_doy'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $doy = sanitize_text_field(wp_unslash($_POST['billing_doy'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (get_option('GRVATIN_uppercase_fields') === 'yes') {
                $doy = strtoupper($doy);
            }
            update_post_meta($order_id, '_billing_doy', $doy);
        }
        
        if (isset($_POST['billing_business_activity'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $activity = sanitize_text_field(wp_unslash($_POST['billing_business_activity'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (get_option('GRVATIN_uppercase_fields') === 'yes') {
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
            if (get_option('GRVATIN_uppercase_fields') === 'yes') {
                $vat = strtoupper($vat);
            }
            $customer->update_meta_data('billing_vat_number', $vat);
        }
        
        if (isset($data['billing_doy']) && !empty($data['billing_doy'])) {
            $doy = sanitize_text_field($data['billing_doy']);
            if (get_option('GRVATIN_uppercase_fields') === 'yes') {
                $doy = strtoupper($doy);
            }
            $customer->update_meta_data('billing_doy', $doy);
        }
        
        if (isset($data['billing_business_activity']) && !empty($data['billing_business_activity'])) {
            $activity = sanitize_text_field($data['billing_business_activity']);
            if (get_option('GRVATIN_uppercase_fields') === 'yes') {
                $activity = strtoupper($activity);
            }
            $customer->update_meta_data('billing_business_activity', $activity);
        }
    }
    
    /**
     * Display invoice fields in admin order page
     */
    public function display_invoice_fields_admin($order) {
        // Block checkout: WooCommerce displays additional fields automatically
        if (get_option('grvatin_checkout_type', 'classic') === 'block') {
            return;
        }

        $invoice_type = $order->get_meta('_billing_invoice_type');
        $vat_number = $order->get_meta('_billing_vat_number');
        $doy = $order->get_meta('_billing_doy');
        $activity = $order->get_meta('_billing_business_activity');
        $company = $order->get_billing_company();
        
        if ($invoice_type === 'invoice') {
            echo '<div class="wcgvi-admin-invoice-fields">';
            echo '<h3>' . esc_html__('Στοιχεία Τιμολογίου', 'greek-vat-invoices-for-woocommerce') . '</h3>';
            
            if ($company) {
                echo '<p><strong>' . esc_html__('Επωνυμία:', 'greek-vat-invoices-for-woocommerce') . '</strong> ' . esc_html($company) . '</p>';
            }
            
            if ($vat_number) {
                echo '<p><strong>' . esc_html__('ΑΦΜ:', 'greek-vat-invoices-for-woocommerce') . '</strong> ' . esc_html($vat_number) . '</p>';
            }
            
            if ($doy) {
                echo '<p><strong>' . esc_html__('ΔΟΥ:', 'greek-vat-invoices-for-woocommerce') . '</strong> ' . esc_html($doy) . '</p>';
            }
            
            if ($activity) {
                echo '<p><strong>' . esc_html__('Επάγγελμα:', 'greek-vat-invoices-for-woocommerce') . '</strong> ' . esc_html($activity) . '</p>';
            }
            
            echo '</div>';
        } else {
            echo '<p><strong>' . esc_html__('Document Type:', 'greek-vat-invoices-for-woocommerce') . '</strong> ' . esc_html__('Receipt', 'greek-vat-invoices-for-woocommerce') . '</p>';
        }
    }
    
    /**
     * Display invoice fields in emails
     */
    public function display_invoice_fields_email($fields, $sent_to_admin, $order) {
        // Block checkout: WooCommerce displays additional fields in emails automatically
        if (get_option('grvatin_checkout_type', 'classic') === 'block') {
            return $fields;
        }

        $invoice_type = $order->get_meta('_billing_invoice_type');
        
        if ($invoice_type === 'invoice') {
            $company = $order->get_billing_company();
            if ($company) {
                $fields['billing_company'] = array(
                    'label' => __('Επωνυμία', 'greek-vat-invoices-for-woocommerce'),
                    'value' => $company
                );
            }
            
            $fields['billing_vat_number'] = array(
                'label' => __('ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
                'value' => $order->get_meta('_billing_vat_number')
            );
            
            $fields['billing_doy'] = array(
                'label' => __('ΔΟΥ', 'greek-vat-invoices-for-woocommerce'),
                'value' => $order->get_meta('_billing_doy')
            );
            
            $fields['billing_business_activity'] = array(
                'label' => __('Επάγγελμα', 'greek-vat-invoices-for-woocommerce'),
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
