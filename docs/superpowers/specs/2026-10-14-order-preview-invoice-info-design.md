# Invoice Info in the Orders List "Quick Preview"

Date: 2026-10-14
Status: Implemented and tested locally (not yet released)
Target release: 1.2.4

## Problem

Shop staff processing many orders mostly use the WooCommerce Orders list
"Quick Preview" (the eye icon) instead of opening each order. The preview
showed nothing from this plugin, so staff could not see whether an order needs
an invoice (Τιμολόγιο) or a receipt (Απόδειξη), nor the customer's business
details (ΑΦΜ, ΔΟΥ, ...), without opening the full order.

## Goals

- Show the plugin's invoice/receipt information inside the Quick Preview.
- Place it at the top of the modal, visible without scrolling.
- Work for Classic and Block checkout, and for HPOS and legacy order storage.
- Display only: no writes, no new settings, no migration.
- Fail safe: a problem in this feature must never break the standard preview.

## Non-goals

- No new column on the Orders list, no filters.
- No changes to invoice numbering, VAT exemption logic, emails or checkout.
- No new translatable strings (existing msgids are reused).

## Design

New class `GRVATIN_Order_Preview` (`includes/class-order-preview.php`),
instantiated from the main plugin file next to `GRVATIN_Order_Handler`, in
both checkout modes.

Two WooCommerce hooks:

- Filter `woocommerce_admin_order_preview_get_order_details`: adds the key
  `grvatin_invoice_html` (pre-built, pre-escaped HTML) to the order data
  WooCommerce sends to the modal over AJAX. WooCommerce only strips keys
  inside `data`, so a top-level key survives.
- Action `woocommerce_admin_order_preview_start`: prints
  `{{{ data.grvatin_invoice_html }}}` (guarded by `<# if #>`) inside the
  preview JS template, i.e. above the Billing/Shipping columns. Both the HPOS
  list table and the legacy list table render the same template.

Content rules (read from existing order meta, same keys in both checkout modes):

| `_billing_invoice_type` | Shown |
|---|---|
| `invoice` | "Τιμολόγιο", invoice number and date if present, company, ΑΦΜ and ΔΟΥ, business activity, VAT-exemption status and reason if `_vat_exempted` is `yes` |
| `receipt` | "Απόδειξη", invoice number and date if present, VAT-exemption line if present |
| anything else / missing | nothing (no claim is made about orders that never had the choice) |

Empty fields are skipped. Invoice numbers only exist once an order is
Completed (`GRVATIN_Order_Handler::generate_invoice_number`), so for
Processing/On-hold orders the block shows the type and business details only.

Safety:

- Every dynamic value goes through `esc_html()` because the template prints
  the HTML raw (`{{{ }}}`). Customer-typed text (company name etc.) is
  therefore always inert.
- The builder is wrapped in `try/catch (\Throwable)`; on any error the
  original preview data is returned unchanged.
- No input is read, nothing is written, no DB or file access.

Styling: a small rule set appended to `assets/css/admin.css` (scoped under
`.wc-order-preview`), loaded on the Orders list by the existing
`enqueue_admin_scripts()` condition (`woocommerce` in the hook name, or the
`shop_order` post type).

## Alternatives considered

- Appending the lines inside the "Billing details" column by modifying
  `formatted_billing_address`: reads naturally but depends on the shape of a
  WooCommerce-generated string; rejected as fragile.
- Placing the block after the item table (`woocommerce_admin_order_preview_end`):
  requires scrolling on orders with many items; rejected.
- Skipping the receipt line: rejected, a visible "Απόδειξη" tells staff at a
  glance that no invoice is needed.

## Testing performed (local XAMPP, WP 7.1, WooCommerce 11.1.0, HPOS on)

Automated headless-Chrome run against the real eye icon, 70 checks, repeated
in Classic checkout mode (the site default is Block):

- Seeded orders: invoice/receipt, with/without invoice number, VAT-exempt,
  no plugin data, invoice with no details, and a hostile-input order.
- Hostile input (`<img onerror>`, `<script>`, `<svg onload>`, `{{ }}`,
  `<# #>`, `</script>`) rendered as literal text: 0 injected elements, no
  script execution, no dialogs.
- Real Block-checkout orders from the site (#385 invoice, #386/#392 receipts)
  display exactly what is stored.
- A forced `TypeError` inside the feature: the preview still opens fully
  for every order (fail-open).
- Order tables checksum identical before and after all previews (no writes).
- AJAX payload is valid JSON for every order; Apache/PHP error log gained no
  lines; no JS console errors; `php -l` clean on all files.
- Narrow (760px) window renders correctly.
- No measurable latency cost (median 1316 ms with the feature vs 1448 ms
  without, within noise; the baseline is slow because of the dev site).

## Known limits

- Legacy (non-HPOS) order storage was verified against WooCommerce source
  (same template and same filter) but not exercised live.
- Tested in Chrome only, and only on WooCommerce 11.1. The two hooks are
  long-standing WooCommerce hooks; if a hook is absent the block simply does
  not appear.
- Plugins that replace the preview modal will not show the block.
- WordPress.org Plugin Check was not run.
