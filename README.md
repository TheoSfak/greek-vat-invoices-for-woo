# Greek VAT & Invoices for WooCommerce

![Version](https://img.shields.io/badge/version-1.0.8-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)
![WooCommerce](https://img.shields.io/badge/woocommerce-3.0%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)

🇬🇷 **Simple Greek VAT fields for WooCommerce checkout**

Add essential Greek tax fields (ΑΦΜ, ΔΟΥ, Business Info) and Invoice/Receipt selection to your WooCommerce checkout. Clean, lightweight, and easy to use.

---

## ✨ Features

### 🛒 Checkout Fields
- ✅ **Invoice/Receipt Selection** - Let customers choose between "Τιμολόγιο" or "Απόδειξη"
- ✅ **VAT Number (ΑΦΜ)** - Required for invoices, numeric only (9 digits)
- ✅ **Tax Office (ΔΟΥ)** - Customer's tax office
- ✅ **Company Name** - Business name for invoices
- ✅ **Business Activity** - Type of business activity
- ✅ **Real-time Validation** - Instant error messages for invalid VAT format

### ⚙️ Admin Settings
- ✅ **Enable/Disable** - Toggle invoice selection feature
- ✅ **Uppercase Conversion** - Auto-convert to CAPITAL LETTERS (AADE requirement)
- ✅ **Field Position** - Choose where invoice type field appears in checkout
- ✅ **Beautiful Settings Page** - Clean, modern admin interface

### 🎨 User Experience
- ✅ **Smart Field Visibility** - Fields appear/hide based on selection
- ✅ **Input Validation** - Only numbers allowed in VAT field
- ✅ **Greek Language** - Fully translated interface
- ✅ **Mobile Responsive** - Works perfectly on all devices

---

## 📋 Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.0+

---

## 🚀 Installation

### Method 1: WordPress Admin (Recommended)
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" then "Activate"

### Method 2: FTP Upload
1. Download and extract the plugin
2. Upload `greek-vat-invoices-for-woocommerce` folder to `/wp-content/plugins/`
3. Activate the plugin in WordPress Admin → Plugins

### Method 3: Git Clone
```bash
cd wp-content/plugins
git clone https://github.com/TheoSfak/greek-vat-invoices-for-woo.git greek-vat-invoices-for-woocommerce
```

---

## ⚠️ Important Notice

**This is a free, open-source plugin provided "as is" without any warranty of any kind.** By installing or updating this plugin you accept full responsibility for any outcome.

**Before installing or upgrading on a live/production site:**

1. **Always test first on a staging or clone environment** — Never install or update directly on your production shop without testing first.
2. **Create a full backup** — Back up your database and files before any plugin installation or update.
3. **Verify checkout functionality** — After updating, place a test order to confirm everything works correctly.

The author(s) assume no liability for any issues, data loss, lost revenue, or disruptions caused by this plugin. Use at your own risk.

---

## ⚙️ Configuration

Navigate to **WooCommerce → Settings → Ελληνικά Τιμολόγια**

### General Settings

1. **Enable Invoice Selection**
   - Toggle on/off the invoice/receipt selection feature
   - Default: Enabled

2. **Uppercase Conversion**
   - Automatically convert company names to CAPITALS
   - Recommended: ON (AADE requirement)

3. **Field Position**
   - Choose where "Invoice Type" field appears
   - Options: After email (recommended), after phone, before name, etc.

---

## 📖 Usage

### For Customers

**At Checkout:**
1. Select "Τιμολόγιο" (Invoice) or "Απόδειξη" (Receipt)
2. If Invoice is selected:
   - Fill in ΑΦΜ (9 digits, numbers only)
   - Fill in ΔΟΥ (Tax Office)
   - Fill in Company Name
   - Fill in Business Activity
3. Complete order normally

**Field Validation:**
- VAT field accepts only numbers
- Real-time error appears if VAT is not 9 digits
- All invoice fields are required when Invoice is selected

### For Store Owners

**View Order Information:**
1. Go to WooCommerce → Orders
2. Open any order
3. View customer's VAT and business details in order meta

---

## 🎯 Coming Soon Features

### 🚀 Planned for Future Versions

- 🔍 **AADE Integration** - Real-time VAT validation via AADE API
  - Auto-complete company details from tax registry
  - Verify VAT numbers against official database
  
- 🇪🇺 **VIES Validation** - EU VAT number verification
  - Validate intra-community VAT numbers
  - Auto-complete EU business information

- �� **PDF Invoice Generation** - Professional invoice PDFs
  - Customizable invoice templates
  - Automatic PDF generation on order completion
  - Email delivery with PDF attachment

- 📧 **Email Integration** - Enhanced email features
  - Custom email templates for invoices
  - Automatic sending on order status change

- 💰 **VAT Exemption** - Automatic tax exemptions
  - Article 39α support for small businesses
  - VIES-based EU exemptions
  - Non-EU export exemptions

- 📊 **Advanced Features**
  - Invoice numbering system with annual counter
  - Multi-company support
  - Custom invoice prefixes
  - Order search by VAT number

*Want these features now? [Support development](#-support-development) to help prioritize!*

---

## 🐛 Troubleshooting

### Fields Not Showing
- Check if WooCommerce is active
- Verify plugin is activated
- Check settings: Enable Invoice Selection must be ON

### VAT Field Not Validating
- Ensure JavaScript is enabled in browser
- Clear browser cache
- Check browser console for errors

### Styling Issues
- Clear site cache (if using caching plugin)
- Check theme compatibility
- Disable other checkout customization plugins temporarily

---

## 🔒 Privacy & Security

- ✅ No external API calls (in current version)
- ✅ Data stored locally in WooCommerce order meta
- ✅ GDPR compliant
- ✅ No third-party tracking
- ✅ Input sanitization and validation

---

## 📝 Changelog

### Version 1.0.8 (2025-01-17)
- ✅ Simplified plugin for WordPress.org release
- ✅ Added real-time VAT validation (9 digits)
- ✅ Added numeric-only input for VAT field
- ✅ Beautified admin settings page
- ✅ Added author info and donate button
- ✅ Removed advanced features (moved to coming soon)
- ✅ Improved checkout JavaScript
- ✅ Enhanced mobile responsiveness

### Version 1.0.7
- Fixed checkout field toggle functionality
- Improved CSS styling
- Bug fixes and performance improvements

### Version 1.0.0
- Initial release
- Basic invoice/receipt selection
- Greek VAT fields

---

## 👨‍💻 Developer

**Theodore Sfakianakis (irmaiden)**

- 🌐 GitHub: [@TheoSfak](https://github.com/TheoSfak)
- 📧 Email: theodore.sfakianakis@gmail.com
- 💰 Support: [PayPal Donate](https://www.paypal.com/donate?business=theodore.sfakianakis@gmail.com)

---

## 💝 Support Development

If this plugin helped your Greek WooCommerce store, consider supporting its development:

[![Donate with PayPal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/donate?business=theodore.sfakianakis@gmail.com)

**Why donate?**
- ☕ Buy me a coffee
- 🚀 Fund future features (AADE, VIES, PDF invoices)
- 🐛 Faster bug fixes
- 📚 Better documentation
- ❤️ Show appreciation

Every contribution, no matter how small, is greatly appreciated and helps keep this plugin free and updated!

---

## 🤝 Contributing

Contributions are welcome!

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This plugin is licensed under GPL v2 or later.

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

---

## 📞 Support

- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/TheoSfak/greek-vat-invoices-for-woo/issues)
- 💬 **Questions**: [GitHub Discussions](https://github.com/TheoSfak/greek-vat-invoices-for-woo/discussions)
- 📧 **Email**: theodore.sfakianakis@gmail.com
- 💰 **Donate**: [PayPal](https://www.paypal.com/donate?business=theodore.sfakianakis@gmail.com)

---

Made with ❤️ in Greece 🇬🇷 by **Theodore Sfakianakis (irmaiden)**
