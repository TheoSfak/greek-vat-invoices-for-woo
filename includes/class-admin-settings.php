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
            
            // VAT Validation Section
            array(
                'title' => __('Ρυθμίσεις Επικύρωσης ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
                'type' => 'title',
                'desc' => __('Διαμόρφωση επικύρωσης ΑΦΜ μέσω AADE και VIES', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_validation_settings'
            ),
            

            
            array(
                'type' => 'sectionend',
                'id' => 'GRVATIN_exemption_settings'
            ),
            
            // Invoice Numbering Section
            array(
                'title' => __('Ρυθμίσεις Αρίθμησης Παραστατικών', 'greek-vat-invoices-for-woocommerce'),
                'type' => 'title',
                'desc' => __('Διαμόρφωση μορφής και ακολουθίας αριθμών παραστατικών', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_numbering_settings'
            ),
            
            array(
                'title' => __('Πρόθεμα Τιμολογίου', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Πρόθεμα για αριθμούς τιμολογίων (π.χ. INV, TIM)', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'grvatin_invoice_prefix',
                'type' => 'text',
                'default' => 'INV'
            ),
            
            array(
                'title' => __('Πρόθεμα Απόδειξης', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Πρόθεμα για αριθμούς αποδείξεων (π.χ. REC, APO)', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_receipt_prefix',
                'type' => 'text',
                'default' => 'REC'
            ),
            
            array(
                'title' => __('Αρχικός Αριθμός', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Αρχικός αριθμός για το τρέχον έτος (εφαρμόζεται μόνο κατά την επαναφορά)', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_starting_number',
                'type' => 'number',
                'default' => '1',
                'custom_attributes' => array('min' => '1')
            ),
            
            array(
                'title' => __('Πλήθος Ψηφίων', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Αριθμός ψηφίων (π.χ. 4 = 0001)', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_number_padding',
                'type' => 'number',
                'default' => '4',
                'custom_attributes' => array('min' => '1', 'max' => '10')
            ),
            
            array(
                'type' => 'sectionend',
                'id' => 'GRVATIN_numbering_settings'
            ),
            
            // Email Section
            array(
                'title' => __('Ρυθμίσεις Email', 'greek-vat-invoices-for-woocommerce'),
                'type' => 'title',
                'desc' => __('Διαμόρφωση αυτόματης αποστολής email', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_email_settings'
            ),
            
            array(
                'title' => __('Αυτόματη Αποστολή Παραστατικού', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Αυτόματη αποστολή παραστατικού όταν ολοκληρωθεί η παραγγελία', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_auto_send_email',
                'default' => 'no',
                'type' => 'checkbox'
            ),
            
            array(
                'title' => __('Όνομα Αποστολέα Email', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Αφήστε κενό για χρήση του ονόματος του site', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_email_from_name',
                'type' => 'text',
                'default' => ''
            ),
            
            array(
                'title' => __('Διεύθυνση Email Αποστολέα', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Αφήστε κενό για χρήση του admin email', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_email_from_address',
                'type' => 'email',
                'default' => ''
            ),
            
            array(
                'type' => 'sectionend',
                'id' => 'GRVATIN_email_settings'
            ),
            
            // Company Information Section
            array(
                'title' => __('Στοιχεία Επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
                'type' => 'title',
                'desc' => __('Τα στοιχεία της επιχείρησής σας για τα παραστατικά', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_company_settings'
            ),
            
            array(
                'title' => __('Λογότυπο Επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Ανεβάστε το λογότυπο της επιχείρησής σας για τα παραστατικά (προτεινόμενο μέγεθος: 200x80px)', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_company_logo',
                'type' => 'text',
                'default' => '',
                'css' => 'min-width:300px;',
                'desc_tip' => __('Επιλέξτε μια εικόνα από τη Βιβλιοθήκη Πολυμέσων ή ανεβάστε μία νέα', 'greek-vat-invoices-for-woocommerce'),
            ),
            
            array(
                'title' => __('Επωνυμία Επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Η νομική επωνυμία της επιχείρησής σας', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_company_name',
                'type' => 'text',
                'default' => get_bloginfo('name')
            ),
            
            array(
                'title' => __('Διεύθυνση Επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Πλήρης διεύθυνση επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_company_address',
                'type' => 'textarea',
                'default' => ''
            ),
            
            array(
                'title' => __('ΑΦΜ Επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Το ΑΦΜ της επιχείρησής σας', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_company_vat',
                'type' => 'text',
                'default' => ''
            ),
            
            array(
                'title' => __('ΔΟΥ Επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Η ΔΟΥ της επιχείρησής σας', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_company_doy',
                'type' => 'text',
                'default' => ''
            ),
            
            array(
                'title' => __('Τηλέφωνο Επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Τηλέφωνο επικοινωνίας', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_company_phone',
                'type' => 'text',
                'default' => ''
            ),
            
            array(
                'title' => __('Email Επιχείρησης', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Διεύθυνση email επικοινωνίας', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_company_email',
                'type' => 'email',
                'default' => get_option('admin_email')
            ),
            
            array(
                'title' => __('Website', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Η διεύθυνση του website σας', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_company_website',
                'type' => 'text',
                'default' => get_site_url()
            ),
            
            array(
                'type' => 'sectionend',
                'id' => 'GRVATIN_company_settings'
            )
        );

        return apply_filters('GRVATIN_settings', $settings);
    }
}
