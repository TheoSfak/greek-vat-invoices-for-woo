<?php
/**
 * Invoice Generator
 * Generates PDF invoices and receipts with TCPDF
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCGVI_Invoice_Generator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add generate invoice button to admin order page
        add_action('woocommerce_order_actions', array($this, 'add_order_action'));
        add_action('woocommerce_order_action_wcgvi_generate_invoice', array($this, 'generate_invoice_action'));
        
        // Add download link in order admin
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'add_download_link'));
        
        // Add download link in customer account
        add_filter('woocommerce_my_account_my_orders_actions', array($this, 'add_customer_download_link'), 10, 2);
        
        // AJAX handlers for admin
        add_action('wp_ajax_wcgvi_regenerate_invoice', array($this, 'ajax_regenerate_invoice'));
        add_action('wp_ajax_wcgvi_upload_invoice', array($this, 'ajax_upload_invoice'));
    }
    
    /**
     * AJAX handler for regenerating invoice
     */
    public function ajax_regenerate_invoice() {
        check_ajax_referer('wcgvi_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('Δεν έχετε δικαίωμα πρόσβασης', 'wc-greek-vat-invoices')));
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Μη έγκυρο ID παραγγελίας', 'wc-greek-vat-invoices')));
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => __('Η παραγγελία δεν βρέθηκε', 'wc-greek-vat-invoices')));
        }
        
        // Delete old invoice file if exists
        $old_file = $order->get_meta('_invoice_file_path');
        if ($old_file) {
            $upload_dir = wp_upload_dir();
            $old_file_path = $upload_dir['basedir'] . '/wcgvi-invoices/' . $old_file;
            if (file_exists($old_file_path)) {
                wp_delete_file($old_file_path);
            }
        }
        
        // Generate new invoice
        $result = $this->generate_pdf($order);
        
        if ($result) {
            $invoice_number = $order->get_meta('_invoice_number');
            wp_send_json_success(array(
                'message' => __('Το παραστατικό αναδημιουργήθηκε επιτυχώς', 'wc-greek-vat-invoices'),
                'invoice_number' => $invoice_number
            ));
        } else {
            wp_send_json_error(array('message' => __('Αποτυχία αναδημιουργίας παραστατικού', 'wc-greek-vat-invoices')));
        }
    }
    
    /**
     * AJAX handler for uploading custom invoice
     */
    public function ajax_upload_invoice() {
        check_ajax_referer('wcgvi_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('Δεν έχετε δικαίωμα πρόσβασης', 'wc-greek-vat-invoices')));
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Μη έγκυρο ID παραγγελίας', 'wc-greek-vat-invoices')));
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => __('Η παραγγελία δεν βρέθηκε', 'wc-greek-vat-invoices')));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['invoice_file']) || !isset($_FILES['invoice_file']['error']) || $_FILES['invoice_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Δεν επιλέχθηκε αρχείο ή υπήρξε σφάλμα κατά το ανέβασμα', 'wc-greek-vat-invoices')));
        }
        
        $file = $_FILES['invoice_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        
        // Validate file type
        $allowed_types = array('application/pdf');
        $file_type = wp_check_filetype($file['name'], array('pdf' => 'application/pdf'));
        
        if (!in_array($file['type'], $allowed_types) && $file_type['ext'] !== 'pdf') {
            wp_send_json_error(array('message' => __('Μόνο PDF αρχεία επιτρέπονται', 'wc-greek-vat-invoices')));
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $invoices_dir = $upload_dir['basedir'] . '/wcgvi-invoices';
        
        if (!file_exists($invoices_dir)) {
            wp_mkdir_p($invoices_dir);
            file_put_contents($invoices_dir . '/.htaccess', 'deny from all');
            file_put_contents($invoices_dir . '/index.php', '<?php // Silence is golden');
        }
        
        // Delete old invoice file if exists
        $old_file = $order->get_meta('_invoice_file_path');
        if ($old_file) {
            $old_file_path = $invoices_dir . '/' . $old_file;
            if (file_exists($old_file_path)) {
                wp_delete_file($old_file_path);
            }
        }
        
        // Generate unique filename
        $filename = 'invoice-' . $order_id . '-' . time() . '.pdf';
        $file_path = $invoices_dir . '/' . $filename;
        
        // Move uploaded file using WordPress filesystem API
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload_result = wp_handle_upload(
            $file,
            array(
                'test_form' => false,
                'mimes' => array('pdf' => 'application/pdf')
            )
        );
        
        if (isset($upload_result['error'])) {
            wp_send_json_error(array('message' => $upload_result['error']));
        }
        
        // Move to our custom directory
        if (isset($upload_result['file'])) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
            if (@rename($upload_result['file'], $file_path)) {
                // Update order meta
                $order->update_meta_data('_invoice_file_path', $filename);
                $order->update_meta_data('_invoice_uploaded', 'yes');
                $order->update_meta_data('_invoice_upload_date', current_time('mysql'));
                $order->save();
            
                // Update database
                global $wpdb;
                $table_name = $wpdb->prefix . 'wcgvi_invoices';
                
                $invoice_number = $order->get_meta('_invoice_number');
                $invoice_type = $order->get_meta('_billing_invoice_type') ?: 'receipt';
                
                $wpdb->replace(
                    $table_name,
                    array(
                        'order_id' => $order_id,
                        'invoice_number' => $invoice_number,
                        'invoice_type' => $invoice_type,
                        'invoice_date' => current_time('mysql'),
                        'file_path' => $filename
                    ),
                    array('%d', '%s', '%s', '%s', '%s')
                );
                
                wp_send_json_success(array(
                    'message' => __('Το παραστατικό ανέβηκε επιτυχώς', 'wc-greek-vat-invoices'),
                    'filename' => $filename
                ));
            } else {
                wp_send_json_error(array('message' => __('Αποτυχία μεταφοράς αρχείου', 'wc-greek-vat-invoices')));
            }
        } else {
            wp_send_json_error(array('message' => __('Σφάλμα κατά το ανέβασμα', 'wc-greek-vat-invoices')));
        }
    }
    
    /**
     * Add generate invoice action
     */
    public function add_order_action($actions) {
        $actions['wcgvi_generate_invoice'] = __('Δημιουργία Παραστατικού (PDF)', 'wc-greek-vat-invoices');
        return $actions;
    }
    
    /**
     * Generate invoice action handler
     */
    public function generate_invoice_action($order) {
        $this->generate_pdf($order);
    }
    
    /**
     * Generate PDF invoice
     */
    public function generate_pdf($order) {
        if (!$order) {
            return false;
        }
        
        $invoice_type = $order->get_meta('_billing_invoice_type') ?: 'receipt';
        $invoice_number = $order->get_meta('_invoice_number');
        
        if (!$invoice_number) {
            // Generate invoice number if not exists
            WCGVI_Order_Handler::get_instance()->generate_invoice_number($order->get_id());
            $invoice_number = $order->get_meta('_invoice_number');
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $invoices_dir = $upload_dir['basedir'] . '/wcgvi-invoices';
        
        if (!file_exists($invoices_dir)) {
            wp_mkdir_p($invoices_dir);
        }
        
        // Load Dompdf
        require_once(plugin_dir_path(dirname(__FILE__)) . 'lib/dompdf/autoload.inc.php');
        
        // Generate HTML content
        $html = $this->get_invoice_html($order, $invoice_type, $invoice_number);
        
        // Create PDF with Dompdf
        try {
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('chroot', $invoices_dir);
            
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Save PDF
            $filename = 'invoice-' . $order->get_id() . '-' . time() . '.pdf';
            $file_path = $invoices_dir . '/' . $filename;
            file_put_contents($file_path, $dompdf->output());
        } catch (Exception $e) {
            error_log('Dompdf Error: ' . $e->getMessage());
            return false;
        }
        
        // Save file path to order
        $order->update_meta_data('_invoice_file_path', $filename);
        $order->save();
        
        // Update database
        global $wpdb;
        $table_name = $wpdb->prefix . 'wcgvi_invoices';
        $wpdb->update(
            $table_name,
            array('file_path' => $filename),
            array('order_id' => $order->get_id())
        );
        
        return $file_path;
    }
    
    /**
     * Generate HTML invoice content
     */
    private function get_invoice_html($order, $invoice_type, $invoice_number) {
        $company_name = get_option('wcgvi_company_name', get_bloginfo('name'));
        $company_address = get_option('wcgvi_company_address', '');
        $company_vat = get_option('wcgvi_company_vat', '');
        $company_doy = get_option('wcgvi_company_doy', '');
        $company_phone = get_option('wcgvi_company_phone', '');
        $company_email = get_option('wcgvi_company_email', '');
        $company_website = get_option('wcgvi_company_website', '');
        $company_logo = get_option('wcgvi_company_logo', '');
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 10px; 
            color: #333;
        }
        .header { 
            margin-bottom: 20px; 
            background: #667eea;
            padding: 0;
            border-radius: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            background: #667eea;
        }
        .header-table td {
            padding: 15px;
            vertical-align: top;
        }
        .header-logo {
            width: 180px;
            text-align: left;
        }
        .header-logo img {
            max-width: 160px;
            height: auto;
            display: block;
        }
        .header-info {
            text-align: left;
            color: #ffffff;
        }
        .header-info h1 { 
            margin: 0 0 8px 0; 
            font-size: 18px; 
            font-weight: bold;
            color: #ffffff;
        }
        .header-info p { 
            margin: 3px 0; 
            font-size: 9px;
            line-height: 1.4;
            color: #ffffff;
        }
        .header-info strong {
            color: #ffffff;
            font-weight: bold;
        }
        .document-type {
            text-align: center;
            margin: 20px 0 15px 0;
            padding: 15px;
            background: #764ba2;
            color: #ffffff;
        }
        .document-type h2 { 
            margin: 0; 
            font-size: 18px; 
            font-weight: bold;
            color: #ffffff;
        }
        .invoice-meta {
            text-align: center;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        .invoice-meta strong {
            font-size: 13px;
            color: #667eea;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            background-color: #667eea;
            color: white;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .info-box { 
            margin: 15px 0; 
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            font-weight: bold;
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #e9ecef;
            font-size: 9px;
        }
        .info-table td:first-child {
            color: #666;
            font-weight: 600;
            width: 28%;
        }
        .items-section {
            margin: 20px 0;
        }
        .items-section h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            font-weight: bold;
            color: #667eea;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            background: #667eea;
            color: #ffffff;
            padding: 10px 8px;
            font-weight: bold;
            font-size: 10px;
            text-align: left;
            border: 1px solid #5568d3;
        }
        .items-table td {
            border: 1px solid #dee2e6;
            padding: 7px 6px;
            background-color: white;
            font-size: 9px;
        }
        .items-table tbody tr:nth-child(even) td {
            background-color: #f8f9fa;
        }
        .right { text-align: right; }
        .center { text-align: center; }
        .totals {
            margin: 20px 0;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        .totals-table {
            width: 100%;
        }
        .totals-table td {
            padding: 5px 10px;
            text-align: right;
            border-bottom: 1px solid #e9ecef;
            font-size: 10px;
        }
        .totals-table td:first-child {
            font-weight: 600;
            color: #666;
        }
        .total-row td {
            font-weight: bold;
            font-size: 14px;
            padding-top: 10px;
            border-top: 2px solid #667eea !important;
            border-bottom: none !important;
            color: #667eea;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            font-size: 8px;
            color: #6c757d;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <?php if ($company_logo): ?>
                    <td class="header-logo">
                        <img src="<?php echo esc_url($company_logo); ?>" alt="Logo">
                    </td>
                <?php endif; ?>
                <td class="header-info">
                    <h1><?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php if ($company_address): ?>
                        <p><?php echo nl2br(htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8')); ?></p>
                    <?php endif; ?>
                    <?php if ($company_vat): ?>
                        <p><strong>ΑΦΜ:</strong> <?php echo htmlspecialchars($company_vat, ENT_QUOTES, 'UTF-8'); ?><?php if ($company_doy): ?> | <strong>ΔΟΥ:</strong> <?php echo htmlspecialchars($company_doy, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></p>
                    <?php endif; ?>
                    <?php if ($company_phone || $company_email): ?>
                        <p>
                            <?php if ($company_phone): ?><strong>Τηλ:</strong> <?php echo htmlspecialchars($company_phone, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            <?php if ($company_phone && $company_email): ?> | <?php endif; ?>
                            <?php if ($company_email): ?><strong>Email:</strong> <?php echo htmlspecialchars($company_email, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($company_website): ?>
                        <p><strong>Web:</strong> <?php echo htmlspecialchars($company_website, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="document-type">
        <h2><?php echo $invoice_type === 'invoice' ? 'ΤΙΜΟΛΟΓΙΟ' : 'ΑΠΟΔΕΙΞΗ'; ?></h2>
    </div>
    
    <div class="invoice-meta">
        <strong><?php echo htmlspecialchars($invoice_number, ENT_QUOTES, 'UTF-8'); ?></strong><br>
        <span class="badge"><?php echo gmdate('d/m/Y', strtotime($order->get_date_created())); ?></span>
    </div>
    
    <div class="info-box">
        <h3>Στοιχεία Πελάτη</h3>
        <table class="info-table">
            <tr>
                <td>Όνομα:</td>
                <td><?php echo htmlspecialchars($order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php if ($invoice_type === 'invoice' && $order->get_billing_company()): ?>
                <tr>
                    <td>Επωνυμία:</td>
                    <td><?php echo htmlspecialchars($order->get_billing_company(), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($invoice_type === 'invoice'): ?>
                <tr>
                    <td>ΑΦΜ:</td>
                    <td><?php echo htmlspecialchars($order->get_meta('_billing_vat_number'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <td>ΔΟΥ:</td>
                    <td><?php echo htmlspecialchars($order->get_meta('_billing_doy'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <td>Δραστηριότητα:</td>
                    <td><?php echo htmlspecialchars($order->get_meta('_billing_business_activity'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>Διεύθυνση:</td>
                <td><?php echo htmlspecialchars(trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
                <td>Πόλη:</td>
                <td><?php echo htmlspecialchars($order->get_billing_city() . ' ' . $order->get_billing_postcode(), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php if ($order->get_billing_phone()): ?>
                <tr>
                    <td>Τηλέφωνο:</td>
                    <td><?php echo htmlspecialchars($order->get_billing_phone(), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($order->get_billing_email()): ?>
                <tr>
                    <td>Email:</td>
                    <td><?php echo htmlspecialchars($order->get_billing_email(), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="items-section">
        <h3>Προϊόντα / Υπηρεσίες</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="48%">Προϊόν</th>
                    <th width="14%" class="center">Ποσότητα</th>
                    <th width="19%" class="right">Τιμή Μον.</th>
                    <th width="19%" class="right">Σύνολο</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item->get_name(), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="center"><?php echo $item->get_quantity(); ?></td>
                        <td class="right"><?php echo number_format($item->get_total() / $item->get_quantity(), 2, ',', '.') . ' €'; ?></td>
                        <td class="right"><?php echo number_format($item->get_total(), 2, ',', '.') . ' €'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="totals">
        <table class="totals-table">
            <tr>
                <td width="72%">Υποσύνολο:</td>
                <td width="28%"><?php echo number_format($order->get_subtotal(), 2, ',', '.') . ' €'; ?></td>
            </tr>
            <?php if ($order->get_shipping_total() > 0): ?>
                <tr>
                    <td>Μεταφορικά:</td>
                    <td><?php echo number_format($order->get_shipping_total(), 2, ',', '.') . ' €'; ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($order->get_total_tax() > 0): ?>
                <tr>
                    <td>ΦΠΑ 24%:</td>
                    <td><?php echo number_format($order->get_total_tax(), 2, ',', '.') . ' €'; ?></td>
                </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td>ΣΥΝΟΛΟ:</td>
                <td><?php echo number_format($order->get_total(), 2, ',', '.') . ' €'; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <p><strong>Σας ευχαριστούμε για την προτίμησή σας!</strong></p>
        <p>Παραγγελία #<?php echo htmlspecialchars($order->get_order_number(), ENT_QUOTES, 'UTF-8'); ?> | Ημερομηνία Έκδοσης: <?php echo gmdate('d/m/Y H:i', current_time('timestamp')); ?></p>
        <?php if ($company_website || $company_email): ?>
            <p style="margin-top: 8px;">
                <?php if ($company_website): ?><?php echo htmlspecialchars($company_website, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                <?php if ($company_website && $company_email): ?> | <?php endif; ?>
                <?php if ($company_email): ?><?php echo htmlspecialchars($company_email, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            </p>
        <?php endif; ?>
        <p style="margin-top: 10px; font-size: 7px; line-height: 1.4;">
            <strong>ΣΗΜΕΙΩΣΗ:</strong> Το παρόν παραστατικό έχει δημιουργηθεί για πληροφοριακούς σκοπούς.<br>
            Δεν αποτελεί επίσημο φορολογικό παραστατικό με ψηφιακή υπογραφή από την ΑΑΔΕ.
        </p>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate simple HTML invoice (fallback)
     */
    private function generate_html_invoice($order) {
        return $this->generate_pdf($order);
    }
    
    /**
     * Add download link in admin
     */
    public function add_download_link($order) {
        $invoice_number = $order->get_meta('_invoice_number');
        $file_path = $order->get_meta('_invoice_file_path');
        $order_id = $order->get_id();
        
        echo '<div class="wcgvi-admin-invoice-section" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">';
        echo '<h4>' . esc_html__('Παραστατικό', 'wc-greek-vat-invoices') . '</h4>';
        
        if ($invoice_number) {
            echo '<p><strong>' . esc_html__('Αριθμός:', 'wc-greek-vat-invoices') . '</strong> ' . esc_html($invoice_number) . '</p>';
        }
        
        echo '<p class="wcgvi-admin-buttons">';
        
        // Download button
        if ($file_path) {
            $upload_dir = wp_upload_dir();
            $file_url = $upload_dir['baseurl'] . '/wcgvi-invoices/' . $file_path;
            echo '<a href="' . esc_url($file_url) . '" class="button button-primary" target="_blank" style="margin-right: 10px;">';
            echo '<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: 3px;"></span> ';
            echo esc_html__('Λήψη Παραστατικού', 'wc-greek-vat-invoices') . '</a>';
        }
        
        // Regenerate button
        echo '<button type="button" class="button wcgvi-regenerate-invoice" data-order-id="' . esc_attr($order_id) . '" style="margin-right: 10px;">';
        echo '<span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: 3px;"></span> ';
        echo esc_html__('Αναδημιουργία', 'wc-greek-vat-invoices') . '</button>';
        
        // Upload button
        echo '<button type="button" class="button wcgvi-upload-invoice-btn" data-order-id="' . esc_attr($order_id) . '">';
        echo '<span class="dashicons dashicons-upload" style="vertical-align: middle; margin-top: 3px;"></span> ';
        echo esc_html__('Ανέβασμα PDF', 'wc-greek-vat-invoices') . '</button>';;
        
        echo '</p>';
        
        // Hidden file input for upload
        echo '<input type="file" id="wcgvi-invoice-upload-' . esc_attr($order_id) . '" accept=".pdf" style="display:none;" />';
        
        echo '</div>';
    }
    
    /**
     * Add download link in customer account
     */
    public function add_customer_download_link($actions, $order) {
        $file_path = $order->get_meta('_invoice_file_path');
        
        if ($file_path) {
            $upload_dir = wp_upload_dir();
            $file_url = $upload_dir['baseurl'] . '/wcgvi-invoices/' . $file_path;
            
            $actions['download_invoice'] = array(
                'url' => $file_url,
                'name' => __('Λήψη Παραστατικού', 'wc-greek-vat-invoices')
            );
        }
        
        return $actions;
    }
}
