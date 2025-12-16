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
        
        // AJAX handlers for test connections
        add_action('wp_ajax_GRVATIN_test_aade', array($this, 'ajax_test_aade'));
        add_action('wp_ajax_GRVATIN_test_vies', array($this, 'ajax_test_vies'));
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
                'title' => __('Τρόπος Επικύρωσης Ελληνικού ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Επιλέξτε πώς θα επικυρώνεται το Ελληνικό ΑΦΜ. <strong>Σημείωση:</strong> Τα credentials του myDATA/Pylon ΔΕΝ λειτουργούν για το AADE RgWsPublic2 API.', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_greek_vat_validation_method',
                'type' => 'select',
                'default' => 'basic',
                'options' => array(
                    'basic' => __('Απλή Επικύρωση (Έλεγχος μορφής 9 ψηφίων) - Προτεινόμενο', 'greek-vat-invoices-for-woocommerce'),
                    'aade' => __('Επικύρωση μέσω AADE (Απαιτεί ειδικά credentials RgWsPublic2)', 'greek-vat-invoices-for-woocommerce')
                ),
                'desc_tip' => __('Απλή Επικύρωση: Ελέγχει μόνο αν το ΑΦΜ έχει σωστή μορφή (9 ψηφία). Επικύρωση AADE: Συνδέεται με το AADE RgWsPublic2 API και αντλεί αυτόματα επωνυμία, διεύθυνση, ΔΟΥ κλπ (απαιτεί ειδικά credentials - ΟΧΙ τα credentials του myDATA).', 'greek-vat-invoices-for-woocommerce')
            ),
            
            array(
                'title' => __('Username (Κωδικός Εισόδου)', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Ο Κωδικός Εισόδου του Ειδικού Κωδικού από το AADE (π.χ. SFAK...)', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_aade_username',
                'type' => 'text',
                'default' => '',
                'class' => 'wcgvi-aade-credentials'
            ),
            
            array(
                'title' => __('Password (Συνθηματικό)', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Το Συνθηματικό Χρήστη του Ειδικού Κωδικού', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_aade_password',
                'type' => 'password',
                'default' => '',
                'class' => 'wcgvi-aade-credentials'
            ),
            
            array(
                'title' => __('Ενεργοποίηση Επικύρωσης VIES', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Επικύρωση ενδοκοινοτικών ΑΦΜ μέσω VIES API', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_vies_validation',
                'default' => 'no',
                'type' => 'checkbox'
            ),
            
            array(
                'type' => 'sectionend',
                'id' => 'GRVATIN_validation_settings'
            ),
            
            // VAT Exemption Section
            array(
                'title' => __('Ρυθμίσεις Απαλλαγής ΦΠΑ', 'greek-vat-invoices-for-woocommerce'),
                'type' => 'title',
                'desc' => __('Διαμόρφωση αυτόματων κανόνων απαλλαγής ΦΠΑ', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_exemption_settings'
            ),
            
            array(
                'title' => __('Ενεργοποίηση Απαλλαγής VIES', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Απαλλαγή ΦΠΑ για επικυρωμένες ενδοκοινοτικές επιχειρήσεις', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_vies_exemption',
                'default' => 'yes',
                'type' => 'checkbox'
            ),
            
            array(
                'title' => __('Ενεργοποίηση Απαλλαγής Εκτός ΕΕ', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Απαλλαγή ΦΠΑ για εξαγωγές σε χώρες εκτός ΕΕ', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_non_eu_exemption',
                'default' => 'yes',
                'type' => 'checkbox'
            ),
            
            array(
                'title' => __('Ενεργοποίηση Απαλλαγής Άρθρου 39α', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Εφαρμογή απαλλαγής άρθρου 39α για επιλέξιμες Ελληνικές επιχειρήσεις', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_article_39a',
                'default' => 'no',
                'type' => 'checkbox'
            ),
            
            array(
                'title' => __('Κατηγορίες Προϊόντων με Απαλλαγή 39α', 'greek-vat-invoices-for-woocommerce'),
                'desc' => __('Επιλέξτε τις κατηγορίες προϊόντων για τις οποίες ισχύει η απαλλαγή άρθρου 39α. Αν δεν επιλέξετε τίποτα, θα ισχύει για όλες τις κατηγορίες.', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_article_39a_categories',
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width:300px;',
                'default' => array(),
                'options' => $this->get_product_categories(),
                'custom_attributes' => array(
                    'data-placeholder' => __('Όλες οι κατηγορίες (προεπιλογή)', 'greek-vat-invoices-for-woocommerce')
                )
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
                'default' => 'yes',
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
            ),
            
            // Tools Section
            array(
                'title' => __('Εργαλεία Δοκιμής', 'greek-vat-invoices-for-woocommerce'),
                'type' => 'title',
                'desc' => __('Δοκιμάστε τις συνδέσεις με AADE και VIES', 'greek-vat-invoices-for-woocommerce'),
                'id' => 'GRVATIN_tools_settings'
            ),
            
            array(
                'title' => __('Δοκιμή Σύνδεσης AADE', 'greek-vat-invoices-for-woocommerce'),
                'type' => 'GRVATIN_test_button',
                'id' => 'GRVATIN_test_aade_button',
                'desc' => __('Δοκιμάστε τη σύνδεση με το AADE API', 'greek-vat-invoices-for-woocommerce'),
                'button_text' => __('Δοκιμή AADE', 'greek-vat-invoices-for-woocommerce'),
                'action' => 'GRVATIN_test_aade'
            ),
            
            array(
                'title' => __('Δοκιμή Σύνδεσης VIES', 'greek-vat-invoices-for-woocommerce'),
                'type' => 'GRVATIN_test_button',
                'id' => 'GRVATIN_test_vies_button',
                'desc' => __('Δοκιμάστε τη σύνδεση με το VIES API', 'greek-vat-invoices-for-woocommerce'),
                'button_text' => __('Δοκιμή VIES', 'greek-vat-invoices-for-woocommerce'),
                'action' => 'GRVATIN_test_vies'
            ),
            
            array(
                'type' => 'sectionend',
                'id' => 'GRVATIN_tools_settings'
            )
        );
        
        return apply_filters('GRVATIN_settings', $settings);
    }
    
    /**
     * Test AADE connection
     */
    public function ajax_test_aade() {
        check_ajax_referer('GRVATIN_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Δεν έχετε δικαίωμα πρόσβασης', 'greek-vat-invoices-for-woocommerce')));
        }
        
        // Check if credentials are set
        $username = get_option('GRVATIN_aade_username');
        $password = get_option('GRVATIN_aade_password');
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(array(
                'message' => __('Παρακαλώ ορίστε πρώτα το Username και Password για το AADE', 'greek-vat-invoices-for-woocommerce')
            ));
            return;
        }
        
        // Get company VAT from settings or use test VAT
        $company_vat = get_option('GRVATIN_company_vat');
        if (empty($company_vat)) {
            wp_send_json_error(array(
                'message' => __('Παρακαλώ ορίστε πρώτα το ΑΦΜ της εταιρείας στις γενικές ρυθμίσεις', 'greek-vat-invoices-for-woocommerce')
            ));
            return;
        }
        
        // Remove any spaces or special chars
        $company_vat = preg_replace('/[^0-9]/', '', $company_vat);
        
        // Validate format first
        if (strlen($company_vat) !== 9) {
            wp_send_json_error(array(
                'message' => __('Το ΑΦΜ πρέπει να είναι 9 ψηφία', 'greek-vat-invoices-for-woocommerce')
            ));
            return;
        }
        
        $validator = GRVATIN_VAT_Validator::get_instance();
        $result = $validator->validate_greek_vat_aade($company_vat);
        
        if ($result['valid']) {
            $company_name = isset($result['data']['company']) ? $result['data']['company'] : '';
            $address = isset($result['data']['address']) ? $result['data']['address'] : '';
            
            wp_send_json_success(array(
                'message' => __('✓ Επιτυχής σύνδεση με το AADE!', 'greek-vat-invoices-for-woocommerce'),
                'company' => $company_name,
                'address' => $address
            ));
        } else {
            // Check for specific error codes
            $error_code = isset($result['error_code']) ? $result['error_code'] : '';
            $message = isset($result['message']) ? $result['message'] : __('Αποτυχία σύνδεσης με το AADE', 'greek-vat-invoices-for-woocommerce');
            
            // Add helpful hints for common errors
            if ($error_code === 'RG_WS_PUBLIC_AFM_CALLED_BY_NOT_ALLOWED') {
                $message .= '<br><br><strong>' . __('Συμβουλή:', 'greek-vat-invoices-for-woocommerce') . '</strong> ' . 
                            __('Ο Ειδικός Κωδικός πρέπει να δημιουργηθεί από το TAXISnet χρησιμοποιώντας τους κωδικούς της επιχείρησής σας (όχι προσωπικούς κωδικούς).', 'greek-vat-invoices-for-woocommerce');
            } elseif ($error_code === 'RG_WS_PUBLIC_TOKEN_USERNAME_NOT_AUTHENTICATED') {
                $message .= '<br><br><strong>' . __('Συμβουλή:', 'greek-vat-invoices-for-woocommerce') . '</strong> ' . 
                            __('Βεβαιωθείτε ότι αντιγράψατε σωστά τον Κωδικό Εισόδου και το Συνθηματικό Χρήστη από το TAXISnet.', 'greek-vat-invoices-for-woocommerce');
            }
            
            // If AADE fails but VAT format is valid, show warning but not complete failure
            if (strlen($company_vat) === 9) {
                $warning_msg = __('⚠️ Το AADE API δεν είναι διαθέσιμο αυτή τη στιγμή, αλλά η μορφή του ΑΦΜ είναι έγκυρη (9 ψηφία)', 'greek-vat-invoices-for-woocommerce');
                
                // Add technical details
                $technical_details = '';
                if (isset($result['debug'])) {
                    $technical_details .= 'Debug: ' . $result['debug'] . ' | ';
                }
                if (isset($result['raw_response']) && !empty($result['raw_response'])) {
                    $technical_details .= 'AADE Response: ' . substr($result['raw_response'], 0, 500);
                } else {
                    $technical_details .= 'AADE Response: Κενή απάντηση από το server';
                }
                if (isset($result['message'])) {
                    $technical_details .= ' | Error: ' . $result['message'];
                }
                
                wp_send_json_success(array(
                    'message' => $warning_msg,
                    'company' => 'ΑΦΜ: ' . $company_vat . ' (Μορφή OK)',
                    'address' => $technical_details
                ));
            } else {
                $error_msg = __('✗ Αποτυχία σύνδεσης με το AADE', 'greek-vat-invoices-for-woocommerce') . ': ' . $result['message'];
                
                // Add debug info if available
                if (isset($result['debug'])) {
                    $error_msg .= ' | Debug: ' . $result['debug'];
                }
                if (isset($result['raw_response'])) {
                    if (!empty($result['raw_response'])) {
                        $error_msg .= ' | Response: ' . substr($result['raw_response'], 0, 500);
                    } else {
                        $error_msg .= ' | Response: Κενή απάντηση';
                    }
                }
                
                wp_send_json_error(array(
                    'message' => $error_msg
                ));
            }
        }
    }
    
    /**
     * Get all product categories for settings
     */
    private function get_product_categories() {
        $categories = array();
        
        $terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $categories[$term->term_id] = $term->name;
            }
        }
        
        return $categories;
    }
    
    /**
     * AJAX handler for testing VIES connection
     */
    public function ajax_test_vies() {
        check_ajax_referer('GRVATIN_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Δεν έχετε δικαίωμα πρόσβασης', 'greek-vat-invoices-for-woocommerce')));
        }
        
        // Test VIES connection with multiple known valid VAT numbers
        $test_vats = array(
            array('country' => 'DE', 'vat' => '266398478', 'name' => 'Google Germany GmbH'), // Google DE
            array('country' => 'IE', 'vat' => '6388047V', 'name' => 'Apple Operations Europe'), // Apple IE
            array('country' => 'NL', 'vat' => '820646660B01', 'name' => 'Booking.com BV'), // Booking.com NL
        );
        
        $validator = GRVATIN_VAT_Validator::get_instance();
        $success = false;
        $last_error = '';
        
        // Try each test VAT until one works
        foreach ($test_vats as $test_vat) {
            $result = $validator->validate_eu_vat_vies($test_vat['country'], $test_vat['vat']);
            
            if ($result['valid']) {
                $success = true;
                $company_name = isset($result['data']['name']) ? $result['data']['name'] : $test_vat['name'];
                $test_info = sprintf('%s%s (Δοκιμαστικό)', $test_vat['country'], $test_vat['vat']);
                
                wp_send_json_success(array(
                    'message' => __('✓ Επιτυχής σύνδεση με το VIES!', 'greek-vat-invoices-for-woocommerce'),
                    'company' => $company_name,
                    'address' => $test_info
                ));
                return;
            }
            
            $last_error = $result['message'];
        }
        
        // If all tests failed
        wp_send_json_error(array(
            'message' => __('✗ Αποτυχία σύνδεσης με το VIES', 'greek-vat-invoices-for-woocommerce') . ': ' . $last_error . ' (Το VIES μπορεί να είναι προσωρινά μη διαθέσιμο)'
        ));
    }
}
