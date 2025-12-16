<?php
/**
 * VAT Validator
 * Validates Greek VAT (AFM) via AADE and EU VAT via VIES
 */

if (!defined('ABSPATH')) {
    exit;
}

class GRVATIN_VAT_Validator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers for VAT validation
        add_action('wp_ajax_GRVATIN_validate_vat', array($this, 'ajax_validate_vat'));
        add_action('wp_ajax_nopriv_GRVATIN_validate_vat', array($this, 'ajax_validate_vat'));
    }
    
    /**
     * AJAX handler for VAT validation
     */
    public function ajax_validate_vat() {
        check_ajax_referer('GRVATIN_nonce', 'nonce');
        
        $vat_number = isset($_POST['vat_number']) ? sanitize_text_field(wp_unslash($_POST['vat_number'])) : '';
        $country = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : 'GR';
        
        if (empty($vat_number)) {
            wp_send_json_error(array('message' => __('Το ΑΦΜ είναι υποχρεωτικό', 'wc-greek-vat-invoices')));
        }
        
        // Clean VAT number
        $vat_number = preg_replace('/[^0-9]/', '', $vat_number);
        
        if ($country === 'GR') {
            // Check Greek VAT validation method
            $validation_method = get_option('GRVATIN_greek_vat_validation_method', 'basic');
            
            if ($validation_method === 'aade') {
                // Full AADE validation with company data
                $result = $this->validate_greek_vat_aade($vat_number);
            } else {
                // Basic format validation only
                $result = $this->validate_greek_vat_basic($vat_number);
            }
        } elseif ($country !== 'GR' && get_option('GRVATIN_vies_validation') === 'yes') {
            // EU VAT validation via VIES
            $result = $this->validate_eu_vat_vies($country, $vat_number);
        } else {
            // Basic format validation only
            $result = array(
                'valid' => strlen($vat_number) === 9,
                'message' => strlen($vat_number) === 9 ? __('Έγκυρη μορφή', 'wc-greek-vat-invoices') : __('Μη έγκυρη μορφή', 'wc-greek-vat-invoices')
            );
        }
        
        if ($result['valid']) {
            // Prepare response data for frontend auto-fill
            $response_data = array(
                'message' => $result['message']
            );
            
            // Add company data if available (from AADE or VIES)
            if (isset($result['data'])) {
                // Map AADE data format to frontend expected format
                if (isset($result['data']['company'])) {
                    $response_data['company_name'] = $result['data']['company'];
                }
                if (isset($result['data']['doy'])) {
                    $response_data['doy'] = $result['data']['doy'];
                }
                if (isset($result['data']['activity'])) {
                    $response_data['activity'] = $result['data']['activity'];
                }
                if (isset($result['data']['address'])) {
                    $response_data['address'] = $result['data']['address'];
                }
                
                // VIES format (name field)
                if (isset($result['data']['name'])) {
                    $response_data['company_name'] = $result['data']['name'];
                }
                if (isset($result['data']['country'])) {
                    $response_data['country'] = $result['data']['country'];
                }
            }
            
            // Check for VAT exemption (intra-EU)
            if (isset($result['data']['vat_exempt']) && $result['data']['vat_exempt']) {
                $response_data['vat_exempt'] = true;
                $response_data['exempt_reason'] = isset($result['data']['exempt_reason']) 
                    ? $result['data']['exempt_reason'] 
                    : __('Ενδοκοινοτική παράδοση - Απαλλαγή ΦΠΑ', 'wc-greek-vat-invoices');
            }
            
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Basic Greek VAT validation (format only)
     */
    public function validate_greek_vat_basic($vat_number) {
        // Remove any non-numeric characters
        $vat_number = preg_replace('/[^0-9]/', '', $vat_number);
        
        // Check if it's 9 digits
        if (strlen($vat_number) !== 9) {
            return array(
                'valid' => false,
                'message' => __('Το ΑΦΜ πρέπει να αποτελείται από 9 ψηφία', 'wc-greek-vat-invoices')
            );
        }
        
        // All checks passed
        return array(
            'valid' => true,
            'message' => __('Έγκυρη μορφή ΑΦΜ (9 ψηφία)', 'wc-greek-vat-invoices'),
            'data' => array()
        );
    }
    
    /**
     * Validate Greek VAT via AADE
     */
    public function validate_greek_vat_aade($vat_number) {
        // Clean VAT number - remove EL prefix and any non-numeric characters
        $vat_number = preg_replace('/^EL/i', '', $vat_number); // Remove EL prefix (case insensitive)
        $vat_number = preg_replace('/[^0-9]/', '', $vat_number); // Keep only digits
        
        // Validate format
        if (strlen($vat_number) !== 9) {
            return array(
                'valid' => false,
                'message' => __('Το ΑΦΜ πρέπει να είναι 9 ψηφία', 'wc-greek-vat-invoices'),
                'debug' => 'Invalid VAT format after cleaning: ' . $vat_number
            );
        }
        
        // Get AADE credentials from settings
        $username = get_option('GRVATIN_aade_username');
        $password = get_option('GRVATIN_aade_password');
        
        if (empty($username) || empty($password)) {
            return array(
                'valid' => false,
                'message' => __('Δεν έχουν οριστεί τα διαπιστευτήρια AADE (Username/Password)', 'wc-greek-vat-invoices'),
                'debug' => 'Missing AADE credentials'
            );
        }
        
        // AADE Registry API endpoint - Try the wspublicreg service
        // This is the service behind the web interface at:
        // https://www1.gsis.gr/webtax/wspublicreg/faces/pages/wspublicreg/menu.xhtml
        // Possible SOAP endpoints:
        $urls_to_try = array(
            'https://www1.gsis.gr/webtax/wspublicreg',
            'https://www1.gsis.gr/wspublicreg',
            'https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2',
            'https://test.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2'
        );
        
        $url = $urls_to_try[2]; // Start with RgWsPublic2 for now
        
        // SOAP 1.2 request matching EXACTLY the official AADE example
        // From: RgWsPublic2DevelopersInfoV1.1/Soap_Request_Response_Examples/rgWsPublic2AfmMethod_WithOUTAsOnDate_Request.xml
        $soap_body = '<?xml version="1.0" encoding="UTF-8"?>
<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" 
              xmlns:ns1="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" 
              xmlns:ns2="http://rgwspublic2/RgWsPublic2Service" 
              xmlns:ns3="http://rgwspublic2/RgWsPublic2">
   <env:Header>
      <ns1:Security>
         <ns1:UsernameToken>
            <ns1:Username>' . esc_xml($username) . '</ns1:Username>
            <ns1:Password>' . esc_xml($password) . '</ns1:Password>
         </ns1:UsernameToken>
      </ns1:Security>
   </env:Header>
   <env:Body>
      <ns2:rgWsPublic2AfmMethod>
         <ns2:INPUT_REC>
            <ns3:afm_called_by/>
            <ns3:afm_called_for>' . esc_xml($vat_number) . '</ns3:afm_called_for>
         </ns2:INPUT_REC>
      </ns2:rgWsPublic2AfmMethod>
   </env:Body>
</env:Envelope>';
        
        // SOAP 1.2 requires application/soap+xml Content-Type
        $content_types = array(
            'application/soap+xml; charset=utf-8',     // SOAP 1.2 standard
            'application/soap+xml; charset=UTF-8',     // SOAP 1.2 uppercase
            'application/soap+xml',                    // SOAP 1.2 no charset
            'text/xml; charset=utf-8',                 // Fallback
            'application/xml; charset=utf-8'           // Alternative
        );
        
        $last_error = null;
        
        foreach ($content_types as $content_type) {
            $response = wp_remote_post($url, array(
                'timeout' => 30,
                'httpversion' => '1.1',
                'headers' => array(
                    'Content-Type' => $content_type,
                    'SOAPAction' => ''  // Empty SOAPAction for SOAP 1.2
                ),
                'body' => $soap_body,
                'sslverify' => true
            ));
            
            if (is_wp_error($response)) {
                $last_error = array(
                    'valid' => false,
                    'message' => __('Δεν ήταν δυνατή η σύνδεση με το AADE', 'wc-greek-vat-invoices'),
                    'error' => $response->get_error_message(),
                    'debug' => 'WP_Error with Content-Type: ' . $content_type
                );
                continue;
            }
            
            $http_code = wp_remote_retrieve_response_code($response);
            
            // If not 415, we found a working Content-Type
            if ($http_code !== 415) {
                break;
            }
            
            $last_error = array(
                'valid' => false,
                'message' => __('AADE δεν δέχεται το format του αιτήματος', 'wc-greek-vat-invoices'),
                'debug' => 'HTTP ' . $http_code . ' with Content-Type: ' . $content_type
            );
        }
        
        // If all Content-Types failed with 415, return last error
        if (!isset($response) || is_wp_error($response)) {
            return $last_error ? $last_error : array(
                'valid' => false,
                'message' => __('Δεν ήταν δυνατή η σύνδεση με το AADE', 'wc-greek-vat-invoices'),
                'debug' => 'All Content-Type variations failed'
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);
        
        // Check for HTTP errors
        if ($http_code !== 200) {
            return array(
                'valid' => false,
                'message' => __('Σφάλμα σύνδεσης με AADE', 'wc-greek-vat-invoices') . ' (HTTP ' . $http_code . ')',
                'debug' => 'HTTP Code: ' . $http_code,
                'raw_response' => substr($body, 0, 1000)
            );
        }
        
        // Check for empty response
        if (empty($body)) {
            return array(
                'valid' => false,
                'message' => __('Κενή απάντηση από το AADE', 'wc-greek-vat-invoices'),
                'debug' => 'Empty response body',
                'http_code' => $http_code
            );
        }
        
        // Parse SOAP response
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            return array(
                'valid' => false,
                'message' => __('Μη έγκυρη απάντηση από το AADE', 'wc-greek-vat-invoices'),
                'debug' => 'XML Parse Error: ' . (!empty($errors) ? $errors[0]->message : 'Unknown'),
                'raw_response' => substr($body, 0, 500)
            );
        }
        
        // Register namespaces - SOAP 1.2 uses different namespace
        $xml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
        $xml->registerXPathNamespace('soap11', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('rg', 'http://rgwspublic2/RgWsPublic2/RgWsPublic2');
        $xml->registerXPathNamespace('rg2', 'http://rgwspublic2/RgWsPublic2Service/RgWsPublic2');
        
        // Check for SOAP fault - try both SOAP 1.1 and 1.2 namespaces
        $fault = $xml->xpath('//soap:Fault | //soap11:Fault');
        if (!empty($fault)) {
            $faultstring = $xml->xpath('//faultstring | //soap:Reason/soap:Text | //soap11:Reason/soap11:Text');
            return array(
                'valid' => false,
                'message' => __('Σφάλμα AADE', 'wc-greek-vat-invoices') . ': ' . (!empty($faultstring) ? (string)$faultstring[0] : 'Unknown')
            );
        }
        
        // Check for error in response
        $error_rec = $xml->xpath('//rg:error_rec | //rg2:error_rec');
        if (!empty($error_rec)) {
            $error_code = $xml->xpath('//rg:error_code | //rg2:error_code');
            $error_descr = $xml->xpath('//rg:error_descr | //rg2:error_descr');
            
            $error_code_str = !empty($error_code) ? (string)$error_code[0] : '';
            $error_message = !empty($error_descr) ? (string)$error_descr[0] : __('Μη έγκυρο ΑΦΜ', 'wc-greek-vat-invoices');
            
            // Enhanced error messages for specific cases
            if ($error_code_str === 'RG_WS_PUBLIC_TOKEN_TAXPAYER_NOT_AUTHENTICATED') {
                $error_message = __('Σφάλμα AADE: Ο Ειδικός Κωδικός που χρησιμοποιείτε δεν έχει εκδοθεί από επιχειρηματικό ΑΦΜ. Παρακαλώ δημιουργήστε νέο Ειδικό Κωδικό από το TAXISnet της επιχείρησής σας.', 'wc-greek-vat-invoices');
            } elseif ($error_code_str === 'RG_WS_PUBLIC_TOKEN_USERNAME_NOT_AUTHENTICATED') {
                $error_message = __('Σφάλμα AADE: Μη έγκυροι κωδικοί πρόσβασης. Ελέγξτε το Όνομα Χρήστη και τον Κωδικό στις ρυθμίσεις.', 'wc-greek-vat-invoices');
            }
            
            return array(
                'valid' => false,
                'message' => $error_message,
                'error_code' => $error_code_str
            );
        }
        
        // Extract company data from basic_rec - try both namespaces
        $onomasia = $xml->xpath('//rg:onomasia | //rg2:onomasia');
        $doy_descr = $xml->xpath('//rg:doy_descr | //rg2:doy_descr');
        $postal_address = $xml->xpath('//rg:postal_address | //rg2:postal_address');
        $postal_area = $xml->xpath('//rg:postal_area_description | //rg2:postal_area_description');
        $postal_zip = $xml->xpath('//rg:postal_zip_code | //rg2:postal_zip_code');
        
        // Get firm activity
        $firm_acts = $xml->xpath('//rg:firm_act_tab/rg:item | //rg2:firm_act_tab/rg2:item');
        $activity = '';
        if (!empty($firm_acts)) {
            $act_descr = $firm_acts[0]->xpath('rg:firm_act_descr | rg2:firm_act_descr');
            if (!empty($act_descr)) {
                $activity = (string)$act_descr[0];
            }
        }
        
        $company_name = !empty($onomasia) ? (string)$onomasia[0] : '';
        $doy = !empty($doy_descr) ? (string)$doy_descr[0] : '';
        $address_str = '';
        
        if (!empty($postal_address)) {
            $address_str = (string)$postal_address[0];
        }
        if (!empty($postal_zip)) {
            $address_str .= ', ' . (string)$postal_zip[0];
        }
        if (!empty($postal_area)) {
            $address_str .= ' ' . (string)$postal_area[0];
        }
        
        return array(
            'valid' => true,
            'message' => __('Έγκυρο ΑΦΜ', 'wc-greek-vat-invoices'),
            'data' => array(
                'company' => $company_name,
                'doy' => $doy,
                'activity' => $activity,
                'address' => trim($address_str)
            )
        );
    }
    
    /**
     * Validate EU VAT via VIES
     */
    public function validate_eu_vat_vies($country_code, $vat_number) {
        // VIES API endpoint
        $url = 'http://ec.europa.eu/taxation_customs/vies/services/checkVatService';
        
        // SOAP request body
        $soap_body = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" 
               xmlns:tns1="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
    <soap:Body>
        <tns1:checkVat>
            <tns1:countryCode>' . esc_html($country_code) . '</tns1:countryCode>
            <tns1:vatNumber>' . esc_html($vat_number) . '</tns1:vatNumber>
        </tns1:checkVat>
    </soap:Body>
</soap:Envelope>';
        
        $response = wp_remote_post($url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => ''
            ),
            'body' => $soap_body
        ));
        
        if (is_wp_error($response)) {
            return array(
                'valid' => false,
                'message' => __('Δεν ήταν δυνατή η σύνδεση με το VIES', 'wc-greek-vat-invoices'),
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Parse SOAP response
        $xml = simplexml_load_string($body);
        if ($xml === false) {
            return array(
                'valid' => false,
                'message' => __('Μη έγκυρη απάντηση από το VIES', 'wc-greek-vat-invoices')
            );
        }
        
        // Register namespaces
        $xml->registerXPathNamespace('ns', 'urn:ec.europa.eu:taxud:vies:services:checkVat:types');
        
        // Check validity
        $valid = $xml->xpath('//ns:valid');
        $is_valid = !empty($valid) && strtolower((string)$valid[0]) === 'true';
        
        if (!$is_valid) {
            return array(
                'valid' => false,
                'message' => __('Μη έγκυρο ενδοκοινοτικό ΑΦΜ', 'wc-greek-vat-invoices')
            );
        }
        
        // Extract company data
        $name = $xml->xpath('//ns:name');
        $address = $xml->xpath('//ns:address');
        
        return array(
            'valid' => true,
            'message' => __('Έγκυρο ενδοκοινοτικό ΑΦΜ', 'wc-greek-vat-invoices'),
            'vies_validated' => true,
            'data' => array(
                'company_name' => !empty($name) ? (string)$name[0] : '',
                'address' => !empty($address) ? (string)$address[0] : ''
            )
        );
    }
    
    /**
     * Check if order qualifies for VAT exemption
     */
    public function check_vat_exemption($order) {
        $country = $order->get_billing_country();
        $vat_number = $order->get_meta('_billing_vat_number');
        $vies_validated = $order->get_meta('_vies_validated');
        
        // VIES VAT exemption
        if (get_option('GRVATIN_vat_exempt_eu') === 'yes' && $vies_validated && $country !== 'GR') {
            return true;
        }
        
        // Non-EU VAT exemption
        $eu_countries = array('AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK');
        if (get_option('GRVATIN_vat_exempt_non_eu') === 'yes' && !in_array($country, $eu_countries)) {
            return true;
        }
        
        return false;
    }
}
