=== Greek VAT & Invoices for WooCommerce ===
Contributors: irmaiden
Tags: woocommerce, greek, vat, invoices, greece
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.0
Requires Plugins: woocommerce
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete Greek invoicing solution for WooCommerce with AADE & VIES validation, automatic VAT exemptions, and professional PDF invoice generation.

== Description ==

Greek VAT & Invoices for WooCommerce is a comprehensive plugin that adds Greek tax compliance features to your WooCommerce store. It provides professional invoice generation, real-time VAT validation through AADE (Greek Tax Authority) and VIES (EU VAT Information Exchange System), and automatic VAT exemption handling.

**Key Features:**

* **Professional PDF Invoices** - Generate beautiful, compliant Greek invoices and receipts
* **Real-time VAT Validation** - Validate Greek VAT numbers (ΑΦΜ) through AADE
* **EU VIES Integration** - Validate EU VAT numbers for intra-community transactions
* **Automatic VAT Exemptions** - Smart handling of EU and non-EU VAT exemptions
* **Customizable Fields** - Add Greek-specific billing fields (ΑΦΜ, ΔΟΥ, Business Activity)
* **Invoice/Receipt Selection** - Let customers choose between invoice or receipt
* **Email Integration** - Automatically attach invoices to order emails
* **Uppercase Fields** - Optional automatic uppercase conversion for Greek characters
* **Article 39a Support** - Special handling for intra-community supply exemptions
* **Admin Management** - Complete invoice management from WooCommerce order pages

**Perfect for Greek Businesses:**

Whether you're running a B2B or B2C e-commerce store in Greece, this plugin handles all the tax compliance requirements, from VAT validation to professional invoice generation that meets Greek tax authority standards.

**AADE Integration:**

Connect directly to the Greek Tax Authority (AADE) services to validate VAT numbers in real-time, retrieve company information automatically, and ensure compliance with Greek tax regulations.

**EU VAT Compliance:**

Full support for EU VAT rules including VIES validation, intra-community supplies, and automatic VAT exemption when applicable.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/greek-vat-invoices-for-woocommerce/` or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Make sure WooCommerce is installed and activated
4. Go to WooCommerce > Settings > Greek VAT & Invoices to configure the plugin
5. Enter your company details and configure your preferences
6. (Optional) Configure AADE credentials for real-time VAT validation
7. (Optional) Enable VIES validation for EU VAT numbers

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes, this plugin is specifically designed for WooCommerce and requires it to be installed and activated.

= Can I validate Greek VAT numbers automatically? =

Yes! The plugin supports real-time VAT validation through AADE (Greek Tax Authority). You can configure your AADE credentials in the settings.

= Does it support EU VAT validation? =

Yes, the plugin includes VIES integration for validating EU VAT numbers for intra-community transactions.

= Can customers choose between invoice and receipt? =

Yes, customers can select their preference during checkout. You can configure where this option appears in the checkout form.

= Are the invoices compliant with Greek tax regulations? =

Yes, the generated PDF invoices follow Greek tax authority requirements and include all necessary information (ΑΦΜ, ΔΟΥ, etc.).

= Can I customize the invoice template? =

The plugin generates professional invoices with your company logo and details. Advanced customization can be done through WordPress filters and actions.

= Does it automatically apply VAT exemptions? =

Yes, the plugin can automatically apply VAT exemptions for EU and non-EU customers based on your settings and validated VAT numbers.

= What about Article 39a exemptions? =

The plugin includes support for Article 39a of the Greek VAT Code for intra-community supply exemptions with appropriate checkbox and notices.

= Can I regenerate invoices? =

Yes, you can regenerate invoices from the WooCommerce order page at any time.

= Does it work with Greek language? =

Absolutely! The plugin is fully translatable and includes Greek language support throughout the interface and generated documents.

== Screenshots ==

1. Checkout page with Greek VAT fields and invoice/receipt selection
2. Real-time VAT validation during checkout
3. Professional PDF invoice with Greek tax compliance
4. Admin settings page for company configuration
5. Order management with invoice actions
6. AADE and VIES validation settings

== Changelog ==

= 1.0.6 =
* Major refactoring for WordPress.org compliance
* Changed all prefixes from WCGVI/wcgvi to GRVATIN/grvatin for uniqueness
* Updated text domain to match plugin slug: greek-vat-invoices-for-woocommerce
* Added "Requires Plugins: woocommerce" header
* Updated Dompdf library to version 3.1.4
* Added composer.json for dependency management
* Fixed all WordPress coding standards issues
* Improved output escaping security
* Added proper cache handling for database queries

= 1.0.5 =
* Fixed menu slug consistency issue
* Improved security with proper nonce verification
* Enhanced error handling for PDF generation
* Updated coding standards compliance

= 1.0.0 =
* Initial release
* PDF invoice and receipt generation
* AADE VAT validation integration
* VIES EU VAT validation
* Customizable checkout fields
* Automatic VAT exemption handling
* Email integration
* Article 39a support

== Upgrade Notice ==

= 1.0.6 =
Major update with improved WordPress.org compliance, updated dependencies, and enhanced security. Recommended for all users.

= 1.0.5 =
Bug fixes and security improvements. Update recommended.

== External Services ==

This plugin connects to external third-party services to validate VAT numbers and retrieve company information. Below is a detailed disclosure of all external services used:

**1. VIES (VAT Information Exchange System) - EU Commission**

* **Service**: EU VAT Number Validation Service
* **Provider**: European Commission
* **When Used**: When customers enter EU VAT numbers during checkout or when validating EU VAT numbers in admin
* **Data Sent**: 
  - EU country code (e.g., DE, FR, IT)
  - VAT number
  - IP address (automatically by the service)
* **Purpose**: Validates EU VAT numbers for intra-community transactions and determines VAT exemption eligibility
* **Service URL**: http://ec.europa.eu/taxation_customs/vies/services/checkVatService
* **Terms of Service**: https://ec.europa.eu/taxation_customs/vies/
* **Privacy Policy**: https://ec.europa.eu/info/privacy-policy_en
* **Data Retention**: The EU Commission may log requests for operational purposes

**2. AADE (Independent Authority for Public Revenue) - Greek Tax Authority**

* **Service**: Greek VAT (ΑΦΜ) Validation and Company Information Retrieval
* **Provider**: Greek Tax Authority (AADE - Ανεξάρτητη Αρχή Δημοσίων Εσόδων)
* **When Used**: When customers enter Greek VAT numbers (ΑΦΜ) during checkout or when validating in admin
* **Data Sent**:
  - Greek VAT number (ΑΦΜ - Αριθμός Φορολογικού Μητρώου)
  - AADE credentials (username/password configured by store owner)
  - IP address (automatically by the service)
* **Purpose**: Validates Greek VAT numbers and retrieves official company information (name, address, DOY, business activity)
* **Service URLs**:
  - Production: https://www1.gsis.gr/webtax/wspublicreg and https://www1.gsis.gr/wspublicreg
  - Alternative: https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2
  - Test: https://test.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2
* **Terms of Service**: https://www.aade.gr/oroi-xrisis
* **Privacy Policy**: https://www.aade.gr/politiki-aporritou
* **Authentication**: Requires AADE credentials (optional - can be configured by store owner)
* **Data Retention**: AADE may log all API requests for security and audit purposes

**Important Notes:**

* VIES validation is automatic for all EU VAT numbers and cannot be disabled
* AADE validation requires optional configuration of AADE credentials
* Both services are official government services for tax compliance
* No customer data beyond VAT numbers and company information is transmitted
* All connections use secure HTTPS (except VIES SOAP endpoint which uses HTTP)
* You should include information about these services in your store's privacy policy

== Privacy Policy ==

This plugin validates VAT numbers through external services (AADE and VIES) which may process customer data including VAT numbers and company information. Please ensure your privacy policy reflects this data processing.

== Support ==

For support, feature requests, or bug reports, please visit:
https://github.com/TheoSfak/greek-vat-invoices-for-woo/issues

== Donations ==

If you find this plugin helpful, consider supporting development:
https://www.paypal.com/paypalme/TheodoreSfakianakis
