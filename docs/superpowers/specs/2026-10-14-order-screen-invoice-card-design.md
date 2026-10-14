# Invoice Card on the Order Edit Screen

Date: 2026-10-14
Status: Implemented and tested locally (not yet released)
Target release: 1.2.5

## Problem

On the WooCommerce order edit screen the plugin's invoice fields were shown as
plain text: with Block checkout, WooCommerce printed them as bold-label/grey-value
lines under the billing email ("Τύπος Παραστατικού:", "Επωνυμία Επιχείρησης:",
"ΑΦΜ:", "Επάγγελμα:"), and with Classic checkout the plugin printed a plain
"Στοιχεία Τιμολογίου" block. Hard to scan, and visually part of the address.

## Decision

The store owner was shown three mockups and chose **option A**:

- A. One card in the Billing column, replacing the plain lines. (chosen)
- B. The same card in the empty left column under "Customer". (rejected: moves
  invoice data away from the billing details)
- C. Keep the plain lines, only restyle them. (rejected: smallest improvement)

Change requested on option A: do **not** show the VAT exemption line
("VAT exempt - VIES validated - Intra-EU supply") in the card. It is still shown
where it always was (General column, once an invoice number exists).

## Design

New class `GRVATIN_Order_Admin_Card` (`includes/class-order-admin-card.php`),
loaded in both checkout modes.

- Action `woocommerce_admin_order_data_after_billing_address` prints the card at
  the bottom of the Billing column.
- Block checkout only: filters `woocommerce_admin_billing_fields` and
  `woocommerce_admin_shipping_fields` (priority 20, `$context === 'view'` only)
  set `show => false` on the plain lines for our fields, which WooCommerce's
  read-only loop honors. The shipping filter is needed because with the "Order"
  position setting WooCommerce prints these fields in the Shipping column. The
  edit form (`$context === 'edit'`, behind the pencil icon) is not touched, so all
  fields stay editable.
- WooCommerce keys these fields as `grvatin/vat-number` and puts the meta key
  (`_wc_other/grvatin/vat-number`) in each field's `id`; the filter matches either.
- Classic checkout: the card replaces the old `display_invoice_fields_admin()`
  block, which is removed from `GRVATIN_Checkout_Fields` together with its hook.

Card content (same order meta as the Quick Preview, same in both modes):

| `_billing_invoice_type` | Shown |
|---|---|
| `invoice` | "Τιμολόγιο" pill, invoice number and date if present, then Επωνυμία (full width), ΑΦΜ (monospace) and ΔΟΥ side by side, Επάγγελμα (full width). Empty values show a muted dash. |
| `receipt` | "Απόδειξη" pill, invoice number and date if present |
| anything else | nothing |

Safety:

- Every dynamic value goes through `esc_html()`.
- The card is built once per order inside `try/catch (\Throwable)` and cached.
  The plain lines are hidden **only if** that build produced a card, so a failure
  can never remove information: nothing is hidden and nothing is printed.
- Read-only: no writes, no DB or file access, no input read.
- Reuses existing translation strings (Τιμολόγιο, Απόδειξη, Επωνυμία, ΑΦΜ, ΔΟΥ,
  Επάγγελμα); no new settings.

Styling: a rule set appended to `assets/css/admin.css`, scoped to
`.grvatin-order-card`. The header wraps so a long invoice number drops under the
pill in narrow columns.

## Things testing caught

- WooCommerce shows block-checkout additional fields only for orders whose
  `created_via` is `store-api`. Test orders must carry that flag.
- The first version of the hide filter matched on the wrong key shape (see
  Design); the browser tests showed the plain lines were not hidden.
- The first version decided to hide the plain lines independently of whether the
  card could be built; reviewing the fail-open test case led to the cached,
  single build shared by both.

## Testing performed (local XAMPP, WP 7.1, WooCommerce 11.1.0, HPOS)

Headless Chrome against the real order edit screen, seeded test orders plus the
site's real orders:

- Feature off (baseline): plain lines visible, no card; edit forms captured.
- Feature on, Block mode: 91 checks. One card per typed order, in the Billing
  column; exact content per case (processing invoice, completed invoice with
  number and date, receipts, invoice with all fields empty = four dashes, order
  with no plugin data = no card); no VAT-exempt text in the card; plain lines
  hidden; hostile input (`<img onerror>`, `<script>`, `<svg onload>`, `{{ }}`,
  `<# #>`) rendered as inert text; edit form identical to the feature-off form
  (same fields, same values) and our fields still present and editable.
- Classic checkout mode: 83 checks, card present and correct.
- Forced `TypeError` inside the card builder: 13 checks. No card, order screen
  intact, and the plain lines are not hidden.
- Real orders (#384 invoice with empty ΔΟΥ, #385 invoice, #386 and #392
  receipts): card renders; screen loads.
- Clicking Update in the real order screen: all 14 stored rows for the order
  (meta, addresses, order row) identical before and after.
- Viewing orders and opening edit forms wrote nothing except WordPress's own
  edit-lock rows. No PHP error-log lines, no JS console errors, `php -l` clean.

## Known limits

- Legacy (non-HPOS) order storage was not run live. It renders the same meta box
  and fires the same hook and filters (verified from WooCommerce source).
- Tested in Chrome only, on WooCommerce 11.1 only.
- The General column still shows "Invoice Number" and "VAT Status" (existing
  `GRVATIN_Order_Handler::display_invoice_number`), so on completed orders the
  invoice number appears twice. Left unchanged on purpose.
- The Orders list Quick Preview still includes the VAT exemption line; only the
  order-screen card omits it.
- WordPress.org Plugin Check was not run.
