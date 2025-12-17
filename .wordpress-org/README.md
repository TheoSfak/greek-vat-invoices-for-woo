# WordPress.org Plugin Assets

This folder contains assets for the WordPress.org plugin directory.

## Required Assets

### Icons (REQUIRED)
- **icon-128x128.png** - 128x128px PNG, square icon
- **icon-256x256.png** - 256x256px PNG, square icon (retina)

**Design suggestions for icon:**
- Greek flag colors (blue #0D5EAF, white)
- ΑΦΜ text or document icon
- Shopping cart with Greek flag
- Simple, recognizable at small sizes

### Banners (Recommended)
- **banner-772x250.png** - 772x250px PNG, header banner
- **banner-1544x500.png** - 1544x500px PNG, retina header banner

**Design suggestions for banner:**
- Greek flag background
- Plugin name: "Greek VAT & Invoices for WooCommerce"
- Tagline: "Simple VAT fields for Greek e-commerce"
- Greek architectural elements (columns, patterns)

### Screenshots (Recommended)
- **screenshot-1.png** - Checkout page showing invoice/receipt selection and VAT fields
- **screenshot-2.png** - Admin settings page with gradient header and donate button
- **screenshot-3.png** - Order details page showing VAT information (Στοιχεία Τιμολογίου)

**Screenshot tips:**
- Take full browser screenshots
- Show the plugin in action
- Use actual Greek text for authenticity
- Crop to focus on relevant areas

## Design Tools

### Free Options:
- **Canva** - https://www.canva.com (templates available)
- **Photopea** - https://www.photopea.com (free Photoshop alternative)
- **GIMP** - https://www.gimp.org (free image editor)

### Screenshot Tools:
- **Windows Snipping Tool** (Win + Shift + S)
- **ShareX** - https://getsharex.com (free screenshot tool)
- **Greenshot** - https://getgreenshot.org (free screenshot tool)

## Color Palette

### Greek Flag Colors:
- Blue: #0D5EAF
- White: #FFFFFF

### Plugin Branding:
- Primary: #667eea (gradient purple from admin)
- Secondary: #764ba2 (gradient purple from admin)
- Accent: #0D5EAF (Greek blue)

## WordPress.org Guidelines

Assets must be:
- PNG format (no JPG)
- Exact dimensions (no scaling)
- Under 2MB file size
- Professional quality
- Represent actual plugin functionality

## SVN Asset Upload

After creating assets, upload to SVN:

```bash
svn co https://plugins.svn.wordpress.org/greek-vat-invoices-for-woocommerce/assets
# Copy your assets to the assets folder
svn add icon-*.png banner-*.png screenshot-*.png
svn ci -m "Add plugin assets"
```

## Current Status

- [ ] icon-128x128.png
- [ ] icon-256x256.png
- [ ] banner-772x250.png
- [ ] banner-1544x500.png
- [ ] screenshot-1.png (Checkout page)
- [ ] screenshot-2.png (Admin settings)
- [ ] screenshot-3.png (Order details)

---

**Need help?** Contact Theodore Sfakianakis (irmaiden)
- Email: theodore.sfakianakis@gmail.com
- GitHub: https://github.com/TheoSfak
