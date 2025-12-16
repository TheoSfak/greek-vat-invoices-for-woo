=== Greek VAT & Invoices for WooCommerce ===
Contributors: theodoresfakianakis
Tags: woocommerce, greece, vat, invoices, aade
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete Greek invoicing solution for WooCommerce with AADE & VIES validation, automatic VAT exemptions, and professional invoice generation.

== Description ==

Πλήρης λύση ελληνικής τιμολόγησης για WooCommerce με επικύρωση AADE και VIES.

= Features =

* Επιλογή Παραστατικού: Τιμολόγιο ή Απόδειξη
* Επικύρωση ΑΦΜ via AADE
* VIES Validation για ενδοκοινοτικά ΑΦΜ
* Αυτόματη απαλλαγή ΦΠΑ
* Professional PDF generation
* Email integration

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Configure settings in WooCommerce > Settings > Greek VAT

== Changelog ==

= 1.0.5 =
* Added: Product category selection for Article 39α VAT exemption
* Added: Admin multiselect field to declare eligible product categories
* Added: Backend validation to check all products in cart against allowed categories
* Added: Dynamic frontend notice showing applicable product categories
* Improved: Article 39α exemption now strictly validates entire cart composition

= 1.0.4 =
* Added: Article 39α VAT exemption for small businesses (ΠΟΛ.1150/2017)
* Added: Checkbox with detailed conditions at checkout
* Added: Live notice when Article 39α is selected
* Updated: PDF footer with AAΔΕ compliance notice

= 1.0.3 =
* Fixed: WordPress coding standards compliance
* Fixed: Checkout fatal errors
* Fixed: Greek character encoding in PDFs
* Improved: PDF design with company logo and branding
* Migrated: PDF library from TCPDF to Dompdf v3.0.0

= 1.0.0 =
* Initial release

== Screenshots ==

1. Checkout fields
2. Admin settings
3. Invoice PDF

---

# Greek VAT & Invoices for WooCommerce

![Version](https://img.shields.io/badge/version-1.0.5-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)
![WooCommerce](https://img.shields.io/badge/woocommerce-3.0%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)

Πλήρης λύση ελληνικής τιμολόγησης για WooCommerce με AADE & VIES επικύρωση, αυτόματες απαλλαγές ΦΠΑ και επαγγελματική δημιουργία παραστατικών.

## ✨ Χαρακτηριστικά / Features

### 🇬🇷 Ελληνική Τιμολόγηση
- ✅ **Επιλογή Παραστατικού**: Τιμολόγιο ή Απόδειξη στο checkout
- ✅ **Επικύρωση ΑΦΜ via AADE**: Real-time επικύρωση Ελληνικών ΑΦΜ
- ✅ **Αυτόματη Συμπλήρωση**: Αυτόματη συμπλήρωση στοιχείων επιχείρησης από AADE
- ✅ **Κεφαλαία Γράμματα**: Αυτόματη μετατροπή σε ΚΕΦΑΛΑΙΑ (απαίτηση AADE)
- ✅ **Αριθμοί Παραστατικών**: Σειριακή αρίθμηση με ετήσιο μετρητή

### 🇪🇺 EU VAT Integration
- ✅ **VIES Validation**: Επικύρωση ενδοκοινοτικών ΑΦΜ
- ✅ **Auto VAT Exemption**: Αυτόματη απαλλαγή ΦΠΑ για validated EU VAT
- ✅ **Non-EU Export**: Απαλλαγή ΦΠΑ για εξαγωγές εκτός ΕΕ
- ✅ **Article 39a**: Υποστήριξη άρθρου 39α για μικρές επιχειρήσεις (ΠΟΛ.1150/2017)
  - Checkbox στο checkout για Ελληνικές επιχειρήσεις
  - **Επιλογή Κατηγοριών Προϊόντων**: Ο διαχειριστής δηλώνει ποιες κατηγορίες προϊόντων υπάγονται στην απαλλαγή
  - Αυτόματος έλεγχος όλων των προϊόντων στο καλάθι
  - Αυτόματη αφαίρεση ΦΠΑ μόνο αν όλα τα προϊόντα ανήκουν σε επιλεγμένες κατηγορίες
  - Σημείωση στο παραστατικό

### 📄 PDF Generation
- ✅ **Professional Invoices**: Επαγγελματικά PDF τιμολόγια/αποδείξεις
- ✅ **Greek Language**: Πλήρης υποστήριξη Ελληνικών
- ✅ **Company Branding**: Προσαρμόσιμα στοιχεία επιχείρησης
- ✅ **Download Links**: Links λήψης σε admin & customer account

### 📧 Email Integration
- ✅ **Auto-Send**: Αυτόματη αποστολή παραστατικού κατά την ολοκλήρωση
- ✅ **Email Attachments**: Αυτόματη επισύναψη PDF
- ✅ **Customizable Templates**: Προσαρμόσιμα email templates

### ⚙️ Admin Features
- ✅ **WooCommerce Settings**: Ενσωμάτωση στις ρυθμίσεις WooCommerce
- ✅ **Manual Validation**: Χειροκίνητη επικύρωση ΑΦΜ από admin
- ✅ **Invoice Regeneration**: Αναδημιουργία παραστατικών
- ✅ **Order Search**: Αναζήτηση παραγγελιών με ΑΦΜ
- ✅ **HPOS Compatible**: Συμβατό με WooCommerce HPOS

## 📋 Απαιτήσεις / Requirements

- WordPress 5.0 ή νεότερο
- WooCommerce 3.0 ή νεότερο
- PHP 7.0 ή νεότερο
- SOAP PHP Extension (για AADE/VIES)

## 🚀 Εγκατάσταση / Installation

### Μέθοδος 1: WordPress Admin
1. Κατεβάστε το plugin ως ZIP
2. Πηγαίνετε στο WordPress Admin → Plugins → Add New
3. Κάντε κλικ "Upload Plugin" και επιλέξτε το ZIP
4. Κάντε κλικ "Install Now" και στη συνέχεια "Activate"

### Μέθοδος 2: FTP
1. Κατεβάστε και αποσυμπιέστε το plugin
2. Ανεβάστε τον φάκελο `greek-vat-invoices-for-woocommerce` στο `/wp-content/plugins/`
3. Ενεργοποιήστε το plugin από το WordPress Admin → Plugins

### Μέθοδος 3: Git
```bash
cd wp-content/plugins
git clone https://github.com/TheoSfak/wc-greek-vat-invoices.git
```

## ⚙️ Ρυθμίσεις / Configuration

### 1. Βασικές Ρυθμίσεις
Πηγαίνετε στο **WooCommerce → Settings → Greek VAT & Invoices**

#### General Settings
- ✅ **Enable Invoice/Receipt Selection**: Ενεργοποίηση επιλογής παραστατικού
- ✅ **Uppercase Conversion**: Μετατροπή σε ΚΕΦΑΛΑΙΑ (προτείνεται: ΝΑΙ)

#### VAT Validation Settings
- ✅ **Enable AADE Validation**: Ενεργοποίηση επικύρωσης AADE
- 📝 **AADE Username**: Username AADE Web Service (αν απαιτείται)
- 🔒 **AADE Password**: Password AADE Web Service (αν απαιτείται)
- ✅ **Enable VIES Validation**: Ενεργοποίηση επικύρωσης VIES

#### VAT Exemption Settings
- ✅ **Enable VIES Exemption**: Απαλλαγή ΦΠΑ για validated EU businesses
- ✅ **Enable Non-EU Exemption**: Απαλλαγή ΦΠΑ για εξαγωγές εκτός ΕΕ
- ✅ **Enable Article 39a**: Απαλλαγή άρθρου 39α
- 📦 **Article 39a Product Categories**: Επιλογή κατηγοριών προϊόντων που υπάγονται στην απαλλαγή
  - Αφήστε κενό για όλες τις κατηγορίες (προεπιλογή)
  - Επιλέξτε συγκεκριμένες κατηγορίες για περιορισμό
  - Όλα τα προϊόντα στο καλάθι πρέπει να ανήκουν σε επιλεγμένες κατηγορίες

#### Invoice Numbering
- 📝 **Invoice Prefix**: Πρόθεμα τιμολογίων (π.χ. INV, TIM)
- 📝 **Receipt Prefix**: Πρόθεμα αποδείξεων (π.χ. REC, APO)
- 🔢 **Starting Number**: Αρχικός αριθμός (προεπιλογή: 1)
- 🔢 **Number Padding**: Πλήθος ψηφίων (π.χ. 4 = 0001)

#### Email Settings
- ✅ **Auto-send Invoice**: Αυτόματη αποστολή email
- 📝 **Email From Name**: Όνομα αποστολέα (προεπιλογή: όνομα site)
- 📧 **Email From Address**: Email αποστολέα (προεπιλογή: admin email)

### 2. Στοιχεία Επιχείρησης

#### Company Information
- 📝 **Company Name**: Νομική επωνυμία
- 📝 **Company Address**: Διεύθυνση επιχείρησης
- 📝 **Company VAT Number**: ΑΦΜ επιχείρησης
- 📝 **Company DOY**: ΔΟΥ επιχείρησης
- 📞 **Company Phone**: Τηλέφωνο
- 📧 **Company Email**: Email επικοινωνίας

## 📖 Χρήση / Usage

### Για τον Πελάτη / For Customers

1. **Επιλογή Παραστατικού στο Checkout**:
   - Επιλέξτε "Απόδειξη" ή "Τιμολόγιο"
   - Για τιμολόγιο: συμπληρώστε ΑΦΜ, ΔΟΥ, Επωνυμία, Δραστηριότητα

2. **Αυτόματη Επικύρωση**:
   - Εισάγετε ΑΦΜ και πατήστε Tab
   - Το σύστημα επικυρώνει αυτόματα via AADE ή VIES
   - Τα στοιχεία συμπληρώνονται αυτόματα

3. **Λήψη Παραστατικού**:
   - Μετά την ολοκλήρωση της παραγγελίας
   - Λάβετε email με συνημμένο PDF
   - Κατεβάστε από My Account → Orders

### Για τον Διαχειριστή / For Administrators

1. **Προβολή Παραστατικού**:
   - Πηγαίνετε στο WooCommerce → Orders
   - Ανοίξτε παραγγελία
   - Δείτε στοιχεία παραστατικού και link λήψης

2. **Χειροκίνητη Επικύρωση ΑΦΜ**:
   - Κάντε κλικ "Validate VAT"
   - Τα στοιχεία ενημερώνονται αυτόματα

3. **Αναδημιουργία Παραστατικού**:
   - Κάντε κλικ "Regenerate Invoice"
   - Δημιουργείται νέο PDF

4. **Αναζήτηση με ΑΦΜ**:
   - Χρησιμοποιήστε το search box
   - Αναζητήστε παραγγελίες με ΑΦΜ

## 🔧 AADE API Setup

### Βήμα 1: Εγγραφή στο AADE
1. Επισκεφτείτε την πύλη AADE
2. Εγγραφείτε για πρόσβαση στο Web Service
3. Λάβετε username/password (αν απαιτείται)

### Βήμα 2: Ρύθμιση Plugin
1. Πηγαίνετε στο WooCommerce → Settings → Greek VAT & Invoices
2. Ενεργοποιήστε "Enable AADE Validation"
3. Εισάγετε username/password (αν έχετε)

### Endpoint
```
https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2
```

## 🇪🇺 VIES API Setup

### Βήμα 1: Ενεργοποίηση
1. Πηγαίνετε στο WooCommerce → Settings → Greek VAT & Invoices
2. Ενεργοποιήστε "Enable VIES Validation"
3. Ενεργοποιήστε "Enable VIES Exemption" για αυτόματη απαλλαγή ΦΠΑ

### Endpoint
```
http://ec.europa.eu/taxation_customs/vies/services/checkVatService
```

### Supported Countries
AT, BE, BG, CY, CZ, DE, DK, EE, EL, ES, FI, FR, HR, HU, IE, IT, LT, LU, LV, MT, NL, PL, PT, RO, SE, SI, SK

## 🗄️ Database Schema

### Table: `wp_wcgvi_invoices`
```sql
CREATE TABLE wp_wcgvi_invoices (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    order_id bigint(20) NOT NULL,
    invoice_number varchar(50) NOT NULL,
    invoice_type varchar(20) NOT NULL,
    invoice_date datetime NOT NULL,
    file_path varchar(255),
    PRIMARY KEY (id),
    KEY order_id (order_id),
    KEY invoice_number (invoice_number)
);
```

### Order Meta Keys
- `_billing_invoice_type`: receipt | invoice
- `_billing_vat_number`: ΑΦΜ πελάτη
- `_billing_doy`: ΔΟΥ πελάτη
- `_billing_business_activity`: Δραστηριότητα
- `_invoice_number`: Αριθμός παραστατικού
- `_invoice_file_path`: Path αρχείου PDF
- `_vat_exempt_reason`: Λόγος απαλλαγής ΦΠΑ

## 🔌 Hooks & Filters

### Actions
```php
// After invoice generation
do_action('wcgvi_invoice_generated', $order_id, $file_path);

// After VAT validation
do_action('wcgvi_vat_validated', $vat_number, $country, $result);

// After VAT exemption applied
do_action('wcgvi_vat_exemption_applied', $order_id, $reason);
```

### Filters
```php
// Modify invoice HTML
add_filter('wcgvi_invoice_html', function($html, $order) {
    // Your modifications
    return $html;
}, 10, 2);

// Modify email template
add_filter('wcgvi_email_template', function($template, $order) {
    // Your modifications
    return $template;
}, 10, 2);

// Modify settings
add_filter('wcgvi_settings', function($settings) {
    // Add/modify settings
    return $settings;
});
```

## 🐛 Troubleshooting

### SOAP Errors
**Problem**: "SOAP extension not installed"
**Solution**: 
```bash
# Ubuntu/Debian
sudo apt-get install php-soap
sudo service apache2 restart

# CentOS/RHEL
sudo yum install php-soap
sudo service httpd restart
```

### AADE Validation Fails
**Problem**: "Invalid VAT number" για έγκυρο ΑΦΜ
**Solution**:
- Ελέγξτε αν το ΑΦΜ είναι 9 ψηφία
- Ελέγξτε αν υπάρχει πρόσβαση στο AADE API
- Δοκιμάστε με username/password αν απαιτείται

### VIES Validation Fails
**Problem**: VIES timeout ή αργή απόκριση
**Solution**:
- Το VIES API μερικές φορές είναι αργό/κάτω
- Αυξήστε PHP timeout: `max_execution_time = 60`
- Δοκιμάστε αργότερα

### PDF Generation Issues
**Problem**: PDF δεν δημιουργείται
**Solution**:
- Ελέγξτε permissions: `wp-content/uploads/wcgvi-invoices/`
- Βεβαιωθείτε ότι ο φάκελος είναι writable
```bash
chmod 755 wp-content/uploads/wcgvi-invoices/
```

### Email Not Sending
**Problem**: Το email δεν αποστέλλεται
**Solution**:
- Ελέγξτε WooCommerce email settings
- Ελέγξτε spam folder
- Χρησιμοποιήστε SMTP plugin (WP Mail SMTP)

## 🔒 Security

- ✅ AJAX requests protected with nonces
- ✅ Capability checks for admin functions
- ✅ Input sanitization & validation
- ✅ SQL injection protection
- ✅ XSS protection
- ✅ Invoice files protected (.htaccess)

## 🌐 Translation

Plugin is translation-ready with `greek-vat-invoices-for-woocommerce` text domain.

### Available Languages
- 🇬🇷 Greek (Ελληνικά) - Built-in
- 🇬🇧 English - Built-in

### Add Your Language
1. Copy `languages/greek-vat-invoices-for-woocommerce.pot`
2. Translate with Poedit
3. Save as `greek-vat-invoices-for-woocommerce-{locale}.mo`
4. Place in `wp-content/languages/plugins/`

## 📝 Changelog

### Version 1.0.0 (2025-01-XX)
- 🎉 Initial release
- ✅ AADE VAT validation
- ✅ VIES EU VAT validation
- ✅ Automatic VAT exemptions
- ✅ PDF invoice generation
- ✅ Email integration
- ✅ WooCommerce HPOS compatibility

## 👨‍💻 Developer

**Theodore Sfakianakis**
- GitHub: [@TheoSfak](https://github.com/TheoSfak)
- Support: [PayPal.me/TheodoreSfakianakis](https://www.paypal.com/paypalme/TheodoreSfakianakis)

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## ☕ Support Development

If you find this plugin helpful, consider supporting its development:

[![PayPal](https://img.shields.io/badge/Donate-PayPal-blue.svg)](https://www.paypal.com/paypalme/TheodoreSfakianakis)

## 📞 Support

- 👨‍💻 Author: **Theodore Sfakianakis**
- 🐛 Issues: [GitHub Issues](https://github.com/TheoSfak/wc-greek-vat-invoices/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/TheoSfak/wc-greek-vat-invoices/discussions)
- 💰 Donate: [PayPal.me/TheodoreSfakianakis](https://www.paypal.com/paypalme/TheodoreSfakianakis)

---

Made with ❤️ by **Theodore Sfakianakis** | Greece 🇬🇷
