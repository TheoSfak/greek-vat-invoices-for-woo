=== Greek VAT & Invoices for WooCommerce ===
Contributors: irmaiden
Tags: woocommerce, timologia, timologio, greek, checkout
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Greek VAT, DOY and Invoice/Receipt selection to WooCommerce checkout. Simple and lightweight solution for Greek e-commerce.

== Description ==

🇬🇷 **Simple Greek VAT fields for WooCommerce checkout**

Add essential Greek tax fields (ΑΦΜ, ΔΟΥ, Business Info) and Invoice/Receipt selection to your WooCommerce checkout. Clean, lightweight, and easy to use.

= Key Features =

* **Invoice/Receipt Selection** - Let customers choose between "Τιμολόγιο" or "Απόδειξη"
* **VAT Number (ΑΦΜ)** - Required for invoices, numeric only with 9-digit validation
* **Tax Office (ΔΟΥ)** - Customer's tax office field
* **Company Name** - Business name for invoices
* **Business Activity** - Type of business activity
* **Real-time Validation** - Instant error messages for invalid VAT format
* **Smart Field Visibility** - Fields appear/hide based on invoice/receipt selection
* **Uppercase Conversion** - Auto-convert to CAPITALS (AADE requirement)
* **Mobile Responsive** - Works perfectly on all devices

= WooCommerce Block (Gutenberg) Checkout Support =

Full support for the new WooCommerce Block-based Checkout using the official Additional Checkout Fields API:

* **Block Checkout Compatible** - Works with the Gutenberg-based checkout page
* **Checkout Type Selection** - Choose between Classic or Block checkout in admin settings
* **Field Position Control** - Place invoice fields in the Contact Information or Order section
* **Conditional Visibility** - Invoice fields dynamically show/hide in block checkout just like classic
* **Native Admin & Email Display** - Fields appear automatically in order details and emails via WooCommerce's built-in rendering
* **Server-side Validation** - VAT, DOY, Company, and Activity validated on the server when invoice type is selected

= Admin Settings =

* Enable/Disable invoice selection feature
* Checkout type: Classic checkout or Block (Gutenberg) checkout
* Field position for classic checkout (10 positions available)
* Field position for block checkout (Contact Information or Order section)
* Uppercase conversion toggle
* Beautiful, modern admin interface with gradient styling

= Coming Soon Features =

Future versions will include:

* 🔍 **AADE Integration** - Real-time VAT validation via AADE API with auto-complete
* 🇪🇺 **VIES Validation** - EU VAT number verification
* 📄 **PDF Invoice Generation** - Professional invoice PDFs with email delivery
* 💰 **VAT Exemptions** - Article 39α, VIES-based EU, and non-EU export exemptions
* 📊 **Invoice Numbering** - Automatic invoice numbering with annual counter
* 📧 **Email Integration** - Custom email templates and automatic sending

Support development to help prioritize these features! [Donate via PayPal](https://www.paypal.com/donate?business=theodore.sfakianakis@gmail.com)

= Fully Translated =

* Greek (Ελληνικά) - Native language support
* English - Full English translation

= Privacy & Security =

* No external API calls in current version
* Data stored locally in WooCommerce order meta
* GDPR compliant
* No third-party tracking
* Proper input sanitization and validation

= ⚠️ Important Notice =

**This is a free, open-source plugin provided "as is" without any warranty of any kind.** By installing or updating this plugin you accept full responsibility for any outcome.

**Before installing or upgrading on a live/production site:**

1. **Always test first on a staging or clone environment** — Never install or update directly on your production shop without testing first.
2. **Create a full backup** — Back up your database and files before any plugin installation or update.
3. **Verify checkout functionality** — After updating, place a test order to confirm everything works correctly.

The author(s) assume no liability for any issues, data loss, lost revenue, or disruptions caused by this plugin. Use at your own risk.

== Installation ==

= Method 1: WordPress Admin (Recommended) =

1. Go to Plugins → Add New
2. Click "Upload Plugin"
3. Choose the downloaded ZIP file
4. Click "Install Now"
5. Activate the plugin

= Method 2: FTP Upload =

1. Extract the ZIP file
2. Upload `greek-vat-invoices-for-woocommerce` folder to `/wp-content/plugins/`
3. Go to Plugins menu and activate

= After Activation =

1. Go to WooCommerce → Settings → Ελληνικά Τιμολόγια
2. Enable Invoice Selection
3. Select your Checkout Type (Classic or Block)
4. Configure field position based on your checkout type
5. Enable Uppercase Conversion (recommended for AADE compliance)

== Frequently Asked Questions ==

= Do I need AADE credentials? =

No, the current version (1.1.0) does not require AADE credentials. AADE integration is planned for future versions.

= Does it validate VAT numbers in real-time? =

The plugin validates VAT format (9 digits, numeric only) in real-time at checkout. Full AADE/VIES validation is coming in future versions.

= Can I customize which fields are shown? =

Yes, fields automatically show/hide based on customer's invoice/receipt selection. You can also control field positioning in admin settings.

= Is it compatible with my theme? =

The plugin uses standard WooCommerce hooks and styling, making it compatible with most themes. If you experience styling issues, please report them.

= Does it support HPOS (High-Performance Order Storage)? =

Yes, the plugin is fully compatible with WooCommerce HPOS.

= Does it work with the WooCommerce Block (Gutenberg) Checkout? =

Yes! Since version 1.1.0, the plugin fully supports the WooCommerce Block Checkout using the official Additional Checkout Fields API. Go to WooCommerce → Settings → Ελληνικά Τιμολόγια and select "Block (Checkout Block)" as checkout type.

= Can customers download invoices? =

PDF invoice generation is planned for future versions. Currently, VAT and company information is stored in order meta and visible in My Account.

= How do I see customer VAT information? =

Go to WooCommerce → Orders, open any order, and view the VAT and business details in the order meta section.

= Why are some features missing? =

Version 1.1.0 added WooCommerce Block Checkout support. Core functionality remains simple and lightweight. Advanced features (AADE, VIES, PDFs) are being reengineered and will be added in future updates.

= How can I support development? =

You can donate via PayPal to help fund future features: [https://www.paypal.com/donate?business=theodore.sfakianakis@gmail.com](https://www.paypal.com/donate?business=theodore.sfakianakis@gmail.com)

== Screenshots ==

1. Checkout page with Invoice/Receipt selection
2. Invoice fields (ΑΦΜ, ΔΟΥ, Company Name, Activity)
3. Real-time VAT validation
4. Admin settings page
5. Order meta with VAT information

== Changelog ==

= 1.1.0 (2026-03-13) =
* Added WooCommerce Block Checkout support via Additional Checkout Fields API
* Added checkout type setting (Classic / Block) in admin
* Added block checkout position setting (Contact / Order section)
* Added conditional field visibility in block checkout (show/hide based on invoice type)
* Added company name field for block checkout
* Made VAT number (ΑΦΜ) mandatory
* Block checkout fields display natively in admin orders and emails
* Improved code quality and validation

= 1.0.8 (2025-01-17) =
* Simplified plugin for WordPress.org release
* Added real-time VAT validation (9 digits)
* Added numeric-only input filter for VAT field
* Beautified admin settings page with gradient styling
* Added author information and donate button
* Improved checkout JavaScript for better field toggle
* Enhanced mobile responsiveness
* Removed advanced features temporarily (moved to roadmap)
* Updated documentation

= 1.0.7 =
* Fixed checkout field toggle functionality
* Improved CSS styling
* Bug fixes and performance improvements

= 1.0.0 =
* Initial release
* Basic invoice/receipt selection
* Greek VAT fields (ΑΦΜ, ΔΟΥ)
* Company information fields

== Upgrade Notice ==

= 1.1.0 =
New: WooCommerce Block Checkout support! Choose between Classic and Block checkout in settings. VAT number is now mandatory for invoices.

= 1.0.8 =
Simplified version with improved validation and beautiful admin interface. Advanced features (AADE, VIES, PDFs) coming in future updates.

== Developer ==

**Theodore Sfakianakis (irmaiden)**

* GitHub: [https://github.com/TheoSfak](https://github.com/TheoSfak)
* Email: theodore.sfakianakis@gmail.com
* Donate: [https://www.paypal.com/donate?business=theodore.sfakianakis@gmail.com](https://www.paypal.com/donate?business=theodore.sfakianakis@gmail.com)

== Support ==

* Bug Reports: [GitHub Issues](https://github.com/TheoSfak/greek-vat-invoices-for-woo/issues)
* Questions: [GitHub Discussions](https://github.com/TheoSfak/greek-vat-invoices-for-woo/discussions)
* Email: theodore.sfakianakis@gmail.com

Made with ❤️ in Greece 🇬🇷
