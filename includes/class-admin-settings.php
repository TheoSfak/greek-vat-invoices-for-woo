<?php
/**
 * Admin Settings
 * WooCommerce settings page for Greek VAT & Invoices
 */

if (!defined('ABSPATH')) {
    exit;
}

class GRVATIN_Admin_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add settings tab to WooCommerce
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_greek_vat_invoices', array($this, 'output_settings'));
        add_action('woocommerce_update_options_greek_vat_invoices', array($this, 'save_settings'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 60);
        
        // Enqueue media uploader
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_uploader'));
    }
    
    /**
     * Enqueue media uploader
     */
    public function enqueue_media_uploader($hook) {
        if ($hook !== 'woocommerce_page_wc-settings') {
            return;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not required for read-only tab check
        if (!isset($_GET['tab']) || $_GET['tab'] !== 'greek_vat_invoices') {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script('wcgvi-media-uploader', plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin-media-uploader.js', array('jquery'), '1.0', true);
    }
    
    /**
     * Add settings tab
     */
    public function add_settings_tab($tabs) {
        $tabs['greek_vat_invoices'] = __('Ελληνικά Τιμολόγια & ΦΠΑ', 'greek-vat-invoices-for-woocommerce');
        return $tabs;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Ελληνικά Τιμολόγια & ΦΠΑ', 'greek-vat-invoices-for-woocommerce'),
            __('Ελληνικά Τιμολόγια', 'greek-vat-invoices-for-woocommerce'),
            'manage_woocommerce',
            admin_url('admin.php?page=wc-settings&tab=greek_vat_invoices')
        );
    }
    
    /**
     * Output settings
     */
    public function output_settings() {
        woocommerce_admin_fields($this->get_settings());
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        woocommerce_update_options($this->get_settings());
    }
    
    /**
     * Get settings array
     */
    public function get_settings() {
        $settings = array(
            // General Section
            array(
                'title' => __('Γενικές Ρυθμίσεις', 'greek-vat-invoices-for-woocommerce'),
                'type' => 'title',
                'desc' => __('Διαμόρφωση γενικών ρυθμίσεων τιμολογίων και αποδείξεων', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_general_settings'
            ),
            
            array(
                'title' => __('Ενεργοποίηση Επιλογής Παραστατικού', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Επιτρέψτε στους πελάτες να επιλέξουν μεταξύ τιμολογίου και απόδειξης', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_enable_selection',
                'default' => 'yes',
                'type' => 'checkbox'
            ),
            
            array(
                'title' => __('Μετατροπή σε Κεφαλαία', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Μετατροπή επωνυμίας και διεύθυνσης σε ΚΕΦΑΛΑΙΑ (απαίτηση AADE)', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_uppercase',
                'default' => 'yes',
                'type' => 'checkbox'
            ),
            
            array(
                'title' => __('Θέση Πεδίου "Τύπος Παραστατικού"', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Επιλέξτε πού θα εμφανίζεται το πεδίο επιλογής τιμολογίου/απόδειξης', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'grvatin_invoice_type_position',
                'type' => 'select',
                'default' => 'after_billing_email',
                'options' => array(
                    'top' => __('Τέρμα Πάνω (πρώτο πεδίο)', 'greek-vat-invoices-for-woocommerce'),
                    'before_billing_first_name' => __('Πριν από το Όνομα', 'greek-vat-invoices-for-woocommerce'),
                    'after_billing_last_name' => __('Μετά το Επίθετο', 'greek-vat-invoices-for-woocommerce'),
                    'after_billing_phone' => __('Μετά τον Αριθμό Τηλεφώνου', 'greek-vat-invoices-for-woocommerce'),
                    'after_billing_email' => __('Μετά το Email (προτεινόμενο)', 'greek-vat-invoices-for-woocommerce'),
                    'after_billing_country' => __('Μετά τη Χώρα', 'greek-vat-invoices-for-woocommerce'),
                    'after_billing_address_1' => __('Μετά τη Διεύθυνση', 'greek-vat-invoices-for-woocommerce'),
                    'after_billing_city' => __('Μετά την Πόλη', 'greek-vat-invoices-for-woocommerce'),
                    'after_billing_postcode' => __('Μετά τον Ταχυδρομικό Κώδικα', 'greek-vat-invoices-for-woocommerce'),
                    'bottom' => __('Τέρμα Κάτω (τελευταίο πεδίο)', 'greek-vat-invoices-for-woocommerce')
                ),
                'desc_tip' => __('Καθορίζει πού θα εμφανίζεται το πεδίο "Τιμολόγιο ή Απόδειξη" στη φόρμα checkout. Προτείνεται μετά το email.', 'greek-vat-invoices-for-woocommerce')
            ),
            
            array(
                'type' => 'sectionend',
                'id' => 'GRVATIN_general_settings'
            ),
        );

        return apply_filters('GRVATIN_settings', $settings);
    }
}
