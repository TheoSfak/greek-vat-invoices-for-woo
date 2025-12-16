<?php
/**
 * Email Handler
 * Auto-send invoices via email
 */

if (!defined('ABSPATH')) {
    exit;
}

class GRVATIN_Email_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Auto-send invoice on order completion
        add_action('woocommerce_order_status_completed', array($this, 'auto_send_invoice'), 20);
        
        // Add invoice attachment to WooCommerce emails
        add_filter('woocommerce_email_attachments', array($this, 'attach_invoice_to_email'), 10, 4);
        
        // Add invoice info to email template
        add_action('woocommerce_email_order_meta', array($this, 'add_invoice_info_to_email'), 10, 4);
    }
    
    /**
     * Auto-send invoice when order is completed
     */
    public function auto_send_invoice($order_id) {
        $auto_send = get_option('GRVATIN_auto_send_email', 'yes');
        
        if ($auto_send !== 'yes') {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Check if invoice already generated
        $file_path = $order->get_meta('_invoice_file_path');
        
        if (!$file_path) {
            // Generate invoice first
            grvatin_invoice_Generator::get_instance()->generate_pdf($order);
            $file_path = $order->get_meta('_invoice_file_path');
        }
        
        if (!$file_path) {
            return;
        }
        
        // Send email
        $this->send_invoice_email($order, $file_path);
    }
    
    /**
     * Send invoice email to customer
     */
    public function send_invoice_email($order, $file_path) {
        $upload_dir = wp_upload_dir();
        $full_path = $upload_dir['basedir'] . '/wcgvi-invoices/' . $file_path;
        
        if (!file_exists($full_path)) {
            return false;
        }
        
        $to = $order->get_billing_email();
        $invoice_type = $order->get_meta('_billing_invoice_type') ?: 'receipt';
        $invoice_number = $order->get_meta('_invoice_number');
        
        // Email subject
        $subject = sprintf(
            /* translators: 1: Site name, 2: Order number, 3: Document type (Invoice/Receipt), 4: Invoice number */
            __('%1$s - Order #%2$s - %3$s %4$s', 'greek-vat-invoices-for-woocommerce'),
            get_bloginfo('name'),
            $order->get_order_number(),
            $invoice_type === 'invoice' ? __('Invoice', 'greek-vat-invoices-for-woocommerce') : __('Receipt', 'greek-vat-invoices-for-woocommerce'),
            $invoice_number
        );
        
        // Email body
        $message = $this->get_email_template($order, $invoice_type, $invoice_number);
        
        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Attachments
        $attachments = array($full_path);
        
        // Send email
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        if ($sent) {
            // Add order note
            $order->add_order_note(
                sprintf(
                    /* translators: %s: Invoice number */
                    __('Invoice/Receipt %s sent to customer via email.', 'greek-vat-invoices-for-woocommerce'),
                    $invoice_number
                )
            );
        }
        
        return $sent;
    }
    
    /**
     * Get email template
     */
    private function get_email_template($order, $invoice_type, $invoice_number) {
        $doc_type = $invoice_type === 'invoice' ? 'Τιμολόγιο' : 'Απόδειξη';
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <?php
            // Note: Inline styles are required for email HTML templates.
            // Email clients don't support external stylesheets, so inline CSS is necessary.
            // These styles are not for web pages and cannot use wp_enqueue_style().
            ?>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #f5f5f5;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px;
                }
                .content {
                    padding: 20px 0;
                }
                .footer {
                    background-color: #f5f5f5;
                    padding: 15px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                    border-radius: 5px;
                    margin-top: 20px;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #0071a1;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 3px;
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
                    <p><?php echo esc_html($doc_type . ' ' . $invoice_number); ?></p>
                </div>
                
                <div class="content">
                    <p>Αγαπητέ/ή <?php echo esc_html($order->get_billing_first_name()); ?>,</p>
                    
                    <p>Σας ευχαριστούμε για την παραγγελία σας!</p>
                    
                    <p>Παρακαλούμε βρείτε συνημμένα <?php echo $invoice_type === 'invoice' ? 'το τιμολόγιο' : 'την απόδειξη'; ?> για την παραγγελία σας #<?php echo esc_html($order->get_order_number()); ?>.</p>
                    
                    <p><strong>Αριθμός Παραστατικού:</strong> <?php echo esc_html($invoice_number); ?><br>
                    <strong>Ημερομηνία:</strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($order->get_date_created()))); ?><br>
                    <strong>Σύνολο:</strong> <?php echo wp_kses_post(wc_price($order->get_total())); ?></p>
                    
                    <p style="text-align: center;">
                        <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="button">
                            Προβολή Παραγγελίας
                        </a>
                    </p>
                    
                    <p>Εάν έχετε οποιαδήποτε ερώτηση, μη διστάσετε να επικοινωνήσετε μαζί μας.</p>
                    
                    <p>Με εκτίμηση,<br>
                    Η ομάδα του <?php echo esc_html(get_bloginfo('name')); ?></p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html(get_bloginfo('name')); ?></p>
                    <p><?php echo esc_html(get_option('GRVATIN_company_address', '')); ?></p>
                    <p>Email: <?php echo esc_html(get_option('admin_email')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Attach invoice to WooCommerce emails
     */
    public function attach_invoice_to_email($attachments, $email_id, $order, $email) {
        // Only attach to customer completed order email
        if ($email_id !== 'customer_completed_order') {
            return $attachments;
        }
        
        if (!$order) {
            return $attachments;
        }
        
        $file_path = $order->get_meta('_invoice_file_path');
        
        if (!$file_path) {
            return $attachments;
        }
        
        $upload_dir = wp_upload_dir();
        $full_path = $upload_dir['basedir'] . '/wcgvi-invoices/' . $file_path;
        
        if (file_exists($full_path)) {
            $attachments[] = $full_path;
        }
        
        return $attachments;
    }
    
    /**
     * Add invoice info to email
     */
    public function add_invoice_info_to_email($order, $sent_to_admin, $plain_text, $email) {
        if ($sent_to_admin) {
            return;
        }
        
        $invoice_number = $order->get_meta('_invoice_number');
        
        if (!$invoice_number) {
            return;
        }
        
        $invoice_type = $order->get_meta('_billing_invoice_type') ?: 'receipt';
        $doc_type = $invoice_type === 'invoice' ? 'Τιμολόγιο' : 'Απόδειξη';
        
        if ($plain_text) {
            echo "\n\n" . esc_html($doc_type) . ': ' . esc_html($invoice_number) . "\n\n";
        } else {
            echo '<h3>' . esc_html($doc_type) . ': ' . esc_html($invoice_number) . '</h3>';
        }
    }
}
