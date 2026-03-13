<?php
/**
 * Block Checkout Support
 * Registers additional checkout fields for the WooCommerce Block Checkout
 */

if (!defined('ABSPATH')) {
    exit;
}

class GRVATIN_Block_Checkout {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('woocommerce_blocks_loaded', array($this, 'register_fields'));
        add_action('woocommerce_blocks_enqueue_checkout_block_scripts_after', array($this, 'enqueue_scripts'));
        add_action('woocommerce_set_additional_field_value', array($this, 'save_field_to_order_meta'), 10, 4);
        add_filter('woocommerce_get_default_value_for_grvatin/invoice-type', array($this, 'default_invoice_type'), 10, 3);
    }

    /**
     * Set default value for invoice type select
     */
    public function default_invoice_type($value, $group, $wc_object) {
        return 'receipt';
    }

    /**
     * Map the classic checkout position setting to block checkout index values.
     * Block checkout core field indices: first_name=0, last_name=1, company=10,
     * address_1=20, address_2=30, city=40, postcode=50, country=70, state=80, phone=90, email=100.
     */
    private function get_field_index() {
        $position = get_option('grvatin_invoice_type_position', 'after_billing_email');

        $index_map = array(
            'top'                       => -1,
            'before_billing_first_name' => -1,
            'after_billing_last_name'   => 2,
            'after_billing_country'     => 71,
            'after_billing_address_1'   => 21,
            'after_billing_city'        => 41,
            'after_billing_postcode'    => 51,
            'after_billing_phone'       => 91,
            'after_billing_email'       => 101,
            'bottom'                    => 200,
        );

        return isset($index_map[$position]) ? $index_map[$position] : 101;
    }

    /**
     * Get the block checkout location from the dedicated block position setting.
     * Returns 'contact' (top) or 'order' (bottom).
     */
    private function get_block_location() {
        $location = get_option('grvatin_block_position', 'contact');

        return in_array($location, array('contact', 'order'), true) ? $location : 'contact';
    }

    /**
     * Register additional checkout fields for block checkout
     */
    public function register_fields() {
        if (!function_exists('woocommerce_register_additional_checkout_field')) {
            return;
        }

        $base_index = $this->get_field_index();
        $location   = $this->get_block_location();

        woocommerce_register_additional_checkout_field(array(
            'id'               => 'grvatin/invoice-type',
            'label'            => __('Τύπος Παραστατικού', 'greek-vat-invoices-for-woocommerce'),
            'location'         => $location,
            'type'             => 'select',
            'index'            => $base_index,
            'options'          => array(
                array(
                    'value' => 'receipt',
                    'label' => __('Απόδειξη', 'greek-vat-invoices-for-woocommerce'),
                ),
                array(
                    'value' => 'invoice',
                    'label' => __('Τιμολόγιο', 'greek-vat-invoices-for-woocommerce'),
                ),
            ),
            'required'         => true,
            'sanitize_callback' => array($this, 'sanitize_invoice_type'),
            'validate_callback' => array($this, 'validate_invoice_type'),
        ));

        woocommerce_register_additional_checkout_field(array(
            'id'               => 'grvatin/company-name',
            'label'            => __('Επωνυμία Επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
            'location'         => $location,
            'type'             => 'text',
            'index'            => $base_index + 1,
            'required'         => false,
            'sanitize_callback' => array($this, 'sanitize_text_upper'),
            'validate_callback' => array($this, 'validate_invoice_required_field'),
        ));

        woocommerce_register_additional_checkout_field(array(
            'id'               => 'grvatin/vat-number',
            'label'            => __('ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
            'location'         => $location,
            'type'             => 'text',
            'index'            => $base_index + 2,
            'required'         => true,
            'attributes'       => array(
                'maxLength'    => 9,
                'pattern'      => '[0-9]{9}',
                'title'        => __('Το ΑΦΜ πρέπει να είναι 9 ψηφία', 'greek-vat-invoices-for-woocommerce'),
            ),
            'sanitize_callback' => array($this, 'sanitize_text_upper'),
            'validate_callback' => array($this, 'validate_vat_number'),
        ));

        woocommerce_register_additional_checkout_field(array(
            'id'               => 'grvatin/doy',
            'label'            => __('ΔΟΥ', 'greek-vat-invoices-for-woocommerce'),
            'location'         => $location,
            'type'             => 'text',
            'index'            => $base_index + 3,
            'required'         => false,
            'sanitize_callback' => array($this, 'sanitize_text_upper'),
            'validate_callback' => array($this, 'validate_invoice_required_field'),
        ));

        woocommerce_register_additional_checkout_field(array(
            'id'               => 'grvatin/business-activity',
            'label'            => __('Επάγγελμα', 'greek-vat-invoices-for-woocommerce'),
            'location'         => $location,
            'type'             => 'text',
            'index'            => $base_index + 4,
            'required'         => false,
            'sanitize_callback' => array($this, 'sanitize_text_upper'),
            'validate_callback' => array($this, 'validate_invoice_required_field'),
        ));
    }

    /**
     * Sanitize invoice type value
     */
    public function sanitize_invoice_type($value) {
        return in_array($value, array('receipt', 'invoice'), true) ? $value : 'receipt';
    }

    /**
     * Validate invoice type
     */
    public function validate_invoice_type($value) {
        if (!in_array($value, array('receipt', 'invoice'), true)) {
            return new WP_Error('invalid_invoice_type', __('Μη έγκυρος τύπος παραστατικού.', 'greek-vat-invoices-for-woocommerce'));
        }
    }

    /**
     * Sanitize text with optional uppercase conversion
     */
    public function sanitize_text_upper($value) {
        $value = sanitize_text_field($value);
        if (get_option('GRVATIN_uppercase_fields', 'yes') === 'yes') {
            $value = mb_strtoupper($value, 'UTF-8');
        }
        return $value;
    }

    /**
     * Validate VAT number — required only when invoice type is selected
     */
    public function validate_vat_number($value) {
        $invoice_type = $this->get_submitted_invoice_type();

        if ($invoice_type !== 'invoice') {
            return;
        }

        if (empty($value)) {
            return new WP_Error('required_vat', __('Το ΑΦΜ είναι υποχρεωτικό για την έκδοση τιμολογίου.', 'greek-vat-invoices-for-woocommerce'));
        }

        if (!preg_match('/^[0-9]{9}$/', $value)) {
            return new WP_Error('invalid_vat', __('Το ΑΦΜ πρέπει να είναι 9 ψηφία.', 'greek-vat-invoices-for-woocommerce'));
        }
    }

    /**
     * Validate fields required only for invoice type
     */
    public function validate_invoice_required_field($value) {
        $invoice_type = $this->get_submitted_invoice_type();

        if ($invoice_type !== 'invoice') {
            return;
        }

        if (empty($value)) {
            return new WP_Error('required_field', __('Αυτό το πεδίο είναι υποχρεωτικό για την έκδοση τιμολογίου.', 'greek-vat-invoices-for-woocommerce'));
        }
    }

    /**
     * Get the invoice type from the current Store API request
     */
    private function get_submitted_invoice_type() {
        $request_body = file_get_contents('php://input');
        if ($request_body) {
            $data = json_decode($request_body, true);
            if (isset($data['additional_fields']['grvatin/invoice-type'])) {
                return sanitize_text_field($data['additional_fields']['grvatin/invoice-type']);
            }
        }
        return 'receipt';
    }

    /**
     * Map block checkout field values to classic meta keys for compatibility
     */
    public function save_field_to_order_meta($key, $value, $group, $wc_object) {
        $meta_map = array(
            'grvatin/invoice-type'      => '_billing_invoice_type',
            'grvatin/company-name'      => '_billing_company',
            'grvatin/vat-number'        => '_billing_vat_number',
            'grvatin/doy'               => '_billing_doy',
            'grvatin/business-activity' => '_billing_business_activity',
        );

        if (!isset($meta_map[$key]) || !is_a($wc_object, 'WC_Order')) {
            return;
        }

        $sanitized = sanitize_text_field($value);

        if ($key === 'grvatin/company-name') {
            $wc_object->set_billing_company($sanitized);
        } else {
            $wc_object->update_meta_data($meta_map[$key], $sanitized);
        }
    }

    /**
     * Enqueue block checkout JS for conditional field visibility
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'grvatin-block-checkout',
            GRVATIN_PLUGIN_URL . 'assets/js/block-checkout.js',
            array(),
            GRVATIN_VERSION,
            true
        );
    }
}
