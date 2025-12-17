<?php
/**
 * Plugin Name: Greek VAT & Invoices for WooCommerce
 * Plugin URI: https://github.com/TheoSfak/greek-vat-invoices-for-woo
 * Description: Add Greek VAT, DOY and Invoice/Receipt selection to WooCommerce checkout. Simple and lightweight solution for Greek e-commerce.
 * Version: 1.0.8
 * Author: Theodore Sfakianakis (irmaiden)
 * Author URI: https://www.paypal.com/donate?business=theodore.sfakianakis@gmail.com
 * Text Domain: greek-vat-invoices-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce
 * WC requires at least: 3.0
 * WC tested up to: 9.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GRVATIN_VERSION', '1.0.8');
define('GRVATIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GRVATIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GRVATIN_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('GRVATIN_active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>' . esc_html__('Greek VAT & Invoices for WooCommerce', 'greek-vat-invoices-for-woocommerce') . '</strong> ' . esc_html__('απαιτεί το WooCommerce να είναι εγκατεστημένο και ενεργοποιημένο.', 'greek-vat-invoices-for-woocommerce') . '</p></div>';
    });
    return;
}

// WooCommerce HPOS compatibility declaration
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Main plugin class
 */
class GRVATIN_Greek_VAT_Invoices {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once GRVATIN_PLUGIN_DIR . 'includes/class-checkout-fields.php';
        require_once GRVATIN_PLUGIN_DIR . 'includes/class-vat-validator.php';
        require_once GRVATIN_PLUGIN_DIR . 'includes/class-invoice-generator.php';
        require_once GRVATIN_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once GRVATIN_PLUGIN_DIR . 'includes/class-email-handler.php';
        require_once GRVATIN_PLUGIN_DIR . 'includes/class-order-handler.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('woocommerce_admin_field_GRVATIN_test_button', array($this, 'output_test_button_field'));
        
        // Initialize components
        GRVATIN_Checkout_Fields::get_instance();
        GRVATIN_VAT_Validator::get_instance();
        grvatin_invoice_Generator::get_instance();
        GRVATIN_Admin_Settings::get_instance();
        GRVATIN_Email_Handler::get_instance();
        GRVATIN_Order_Handler::get_instance();
    }
    
    /**
     * Load plugin textdomain
     * Note: load_plugin_textdomain() is automatically handled by WordPress.org for hosted plugins
     */
    public function load_textdomain() {
        // WordPress automatically loads translations for plugins hosted on WordPress.org
        // This function is kept for backwards compatibility
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (is_checkout()) {
            wp_enqueue_style('wcgvi-checkout', GRVATIN_PLUGIN_URL . 'assets/css/checkout.css', array(), GRVATIN_VERSION);
            wp_enqueue_script('wcgvi-checkout', GRVATIN_PLUGIN_URL . 'assets/js/checkout.js', array('jquery'), GRVATIN_VERSION, true);
            
            wp_localize_script('wcgvi-checkout', 'grvatin_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('GRVATIN_nonce'),
                'uppercase' => get_option('GRVATIN_uppercase_fields', 'yes'),
                'validating_text' => __('Επικύρωση...', 'greek-vat-invoices-for-woocommerce'),
                'valid_text' => __('Έγκυρο', 'greek-vat-invoices-for-woocommerce'),
                'invalid_text' => __('Μη έγκυρο ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
                'error_text' => __('Σφάλμα επικύρωσης ΑΦΜ', 'greek-vat-invoices-for-woocommerce')
            ));
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wc-settings') !== false || get_post_type() === 'shop_order' || strpos($hook, 'woocommerce') !== false) {
            wp_enqueue_style('wcgvi-admin', GRVATIN_PLUGIN_URL . 'assets/css/admin.css', array(), GRVATIN_VERSION);
            wp_enqueue_script('wcgvi-admin', GRVATIN_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), GRVATIN_VERSION, true);
            
            wp_localize_script('wcgvi-admin', 'grvatin_admin_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('GRVATIN_admin_nonce'),
                'no_vat_text' => __('Παρακαλώ εισάγετε ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
                'validating_text' => __('Επικύρωση...', 'greek-vat-invoices-for-woocommerce'),
                'validate_text' => __('Επικύρωση ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
                'valid_text' => __('Έγκυρο ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
                'invalid_text' => __('Μη έγκυρο ΑΦΜ', 'greek-vat-invoices-for-woocommerce'),
                'error_text' => __('Παρουσιάστηκε σφάλμα', 'greek-vat-invoices-for-woocommerce'),
                'company_text' => __('Επωνυμία', 'greek-vat-invoices-for-woocommerce'),
                'doy_text' => __('ΔΟΥ', 'greek-vat-invoices-for-woocommerce'),
                'activity_text' => __('Επάγγελμα', 'greek-vat-invoices-for-woocommerce'),
                'regenerate_text' => __('Αναδημιουργία Παραστατικού', 'greek-vat-invoices-for-woocommerce'),
                'regenerate_confirm' => __('Είστε σίγουροι ότι θέλετε να αναδημιουργήσετε το παραστατικό;', 'greek-vat-invoices-for-woocommerce'),
                'generating_text' => __('Δημιουργία...', 'greek-vat-invoices-for-woocommerce'),
                'success_text' => __('Το παραστατικό αναδημιουργήθηκε επιτυχώς', 'greek-vat-invoices-for-woocommerce'),
                'upload_text' => __('Ανέβασμα Παραστατικού', 'greek-vat-invoices-for-woocommerce'),
                'uploading_text' => __('Ανέβασμα...', 'greek-vat-invoices-for-woocommerce'),
                'upload_success_text' => __('Το παραστατικό ανέβηκε επιτυχώς', 'greek-vat-invoices-for-woocommerce'),
                'pdf_only_text' => __('Παρακαλώ ανεβάστε μόνο αρχείο PDF', 'greek-vat-invoices-for-woocommerce'),
                'testing_text' => __('Δοκιμή...', 'greek-vat-invoices-for-woocommerce'),
                'test_aade_text' => __('Δοκιμή AADE', 'greek-vat-invoices-for-woocommerce'),
                'test_vies_text' => __('Δοκιμή VIES', 'greek-vat-invoices-for-woocommerce')
            ));
        }
    }
    
    /**
     * Output custom setting field types
     */
    public function output_custom_field_types() {
        add_action('woocommerce_admin_field_GRVATIN_test_button', array($this, 'output_test_button_field'));
    }
    
    /**
     * Output test button field
     */
    public function output_test_button_field($value) {
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
            </th>
            <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                <button type="button" 
                        class="button button-secondary wcgvi-test-connection" 
                        data-action="<?php echo esc_attr($value['action']); ?>"
                        id="<?php echo esc_attr($value['id']); ?>">
                    <?php echo esc_html($value['button_text']); ?>
                </button>
                <span class="wcgvi-test-result" style="margin-left: 10px;"></span>
                <p class="description"><?php echo esc_html($value['desc']); ?></p>
            </td>
        </tr>
        <?php
    }
}

// Initialize plugin
function GRVATIN_init() {
    return GRVATIN_Greek_VAT_Invoices::get_instance();
}

add_action('plugins_loaded', 'GRVATIN_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create necessary database tables if needed
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'grvatin_invoices';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        invoice_number varchar(50) NOT NULL,
        invoice_type varchar(20) NOT NULL,
        invoice_date datetime NOT NULL,
        file_path varchar(255),
        PRIMARY KEY (id),
        KEY order_id (order_id),
        KEY invoice_number (invoice_number)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Set default options
    if (get_option('GRVATIN_version') === false) {
        add_option('GRVATIN_version', GRVATIN_VERSION);
        add_option('GRVATIN_enable_aade_validation', 'yes');
        add_option('GRVATIN_enable_vies_validation', 'no');
        add_option('GRVATIN_auto_send_invoice', 'yes');
        add_option('GRVATIN_uppercase_fields', 'yes');
        add_option('GRVATIN_vat_exempt_eu', 'no');
        add_option('GRVATIN_vat_exempt_non_eu', 'no');
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
});
