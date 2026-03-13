# Greek VAT Invoices for WooCommerce — Copilot Instructions

## Project Overview

WooCommerce plugin for Greek tax compliance (AADE). Adds invoice/receipt selection to checkout, collects VAT number (ΑΦΜ), DOY, company name, and business activity. Validates against the Greek AADE SOAP API. Targets Greek WooCommerce shops; PHP 7.0+, WP 5.0+, WC 3.0+.

## Architecture

Singleton entry point: `GRVATIN_Greek_VAT_Invoices::get_instance()` in [greek-vat-invoices-for-woocommerce.php](../greek-vat-invoices-for-woocommerce.php).

| Class | File | Role |
|-------|------|------|
| `GRVATIN_Checkout_Fields` | `includes/class-checkout-fields.php` | Checkout field registration, validation, saving |
| `GRVATIN_VAT_Validator` | `includes/class-vat-validator.php` | AJAX handler; AADE SOAP real-time VAT validation |
| `GRVATIN_Admin_Settings` | `includes/class-admin-settings.php` | WooCommerce settings tab UI |
| `GRVATIN_Order_Handler` | `includes/class-order-handler.php` | VAT exemptions, invoice numbering, order search |
| `GRVATIN_Invoice_Generator` | `includes/class-invoice-generator.php` | **DISABLED — not loaded** (planned PDF generation) |
| `GRVATIN_Email_Handler` | `includes/class-email-handler.php` | **DISABLED — not loaded** (planned invoice emails) |

## Conventions

- **Prefix**: `GRVATIN_` on all classes, hooks, option keys, and meta keys.
- **Text domain**: `greek-vat-invoices-for-woocommerce` — every user-facing string must use `__('...', 'greek-vat-invoices-for-woocommerce')` or `_e(...)`.
- **Security**: Always `wp_unslash()` + `sanitize_text_field()` on input; `esc_html()` / `wp_kses_post()` on output. WooCommerce handles checkout nonce verification — do not add redundant nonce checks inside `woocommerce_checkout_*` hooks (PHPCS `NonceVerification.Missing` suppressed intentionally).
- **Option keys**: uppercase `GRVATIN_` prefix for plugin settings (e.g., `GRVATIN_uppercase`); lowercase `grvatin_` for user/order data (e.g., `grvatin_invoice_type_position`).
- **HPOS**: Compatibility declared via `FeaturesUtil::declare_compatibility('custom_order_tables', ...)`.

## Build & Test

No build tooling or automated tests exist. Manual QA only.

```bash
# Basic PHP syntax check
php -l includes/class-checkout-fields.php
```

## Key Pitfalls

1. **Disabled classes**: `class-invoice-generator.php` and `class-email-handler.php` are present but **not instantiated**. Don't treat them as active code paths.

2. **AADE SOAP API**: Uses SOAP 1.2 (`application/soap+xml`). XML responses require namespace-aware parsing. Credentials (username/password) stored in plugin settings. See `class-vat-validator.php` for the full request/parse pattern.

3. **Checkout field ordering**: Fields use integer priority (1–999). Changing priorities can conflict with other plugins. Always check `woocommerce_checkout_fields` hook at priority 1000.

4. **Frontend visibility logic**: Field show/hide is driven by JS (`.grvatin-invoice-fields` class) and CSS with `!important` overrides in [assets/css/checkout.css](../assets/css/checkout.css). Keep this behavior intact when changing field structure.

5. **VAT format**: Always 9-digit numeric string. Stored as uppercase if the `GRVATIN_uppercase` option is enabled — do not add leading-zero handling.

6. **Email handler**: Uses inline CSS only — email clients don't support external stylesheets. Never move email styles to a file.

7. **Invoice number table** (`$wpdb->grvatin_invoices`): Referenced in code but no activation hook creates it. Treat this as a known technical debt item; don't assume the table exists.

## External Services

- **AADE SOAP**: `https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2` — Greek tax authority VAT lookup (active).
- **VIES**: EU VAT validation — stubs exist, not yet implemented.
