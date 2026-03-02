# Eva Slideover Cart

A modern, accessible slideover (drawer) cart for WooCommerce. Replaces the theme mini-cart without touching WooCommerce checkout or order logic.

**Requires:** WordPress 6.0+, PHP 8.0+, WooCommerce 7.0+  
**License:** GPL-2.0-or-later

---

## Description

Eva Slideover Cart replaces the default WooCommerce mini-cart with a fast, elegant, slide-in drawer. It is a **UI-only** plugin — all WooCommerce cart, checkout, and order logic is untouched.

### Features

- Right-side slide-in drawer with smooth CSS transition
- Cart trigger button (shortcode + optional hook) — use anywhere in your theme
- Free shipping progress bar with configurable threshold
- Live cart updates via WooCommerce native fragments (no page reload)
- AJAX quantity stepper (+/-) and item removal
- Sticky footer with "View Cart" and "Checkout" CTAs
- Empty cart state with "Continue Shopping" link
- Three-tactic strategy to suppress the theme's own mini-cart:
  - **Tactic A** — Force theme_mod values to `false`
  - **Tactic B** — Remove specific action callbacks by hook + identifier
  - **Tactic C** — CSS `display:none` targeting for custom selectors
- Fully accessible: ARIA dialog, focus trap, keyboard navigation, WCAG 2.5.5 touch targets
- Mobile-first responsive layout (full-width on phones, 90vw on tablets)
- iOS Safari scroll-lock fix; swipe-right to dismiss on touch devices
- No external libraries — vanilla JS + plain CSS
- Theme template overrides supported in `your-theme/eva-slideover-cart/`

---

## Installation

1. Upload the `eva-slideover-cart` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **WooCommerce → Slideover Cart** to configure the plugin.

---

## Quick Start

### Add the trigger button to your header

**Shortcode** (works in any block, Elementor widget, or theme template):

```
[eva_slideover_cart_trigger]
```

**PHP** (in a template file):

```php
<?php if ( function_exists( 'EVA_SC_Plugin' ) ) : ?>
    <?php echo EVA_SC_Plugin::instance()->render->get_trigger_html(); ?>
<?php endif; ?>
```

The drawer shell is automatically injected into the page via `wp_body_open` (or `wp_footer` as a fallback).

### Suppress the theme mini-cart

1. Go to **WooCommerce → Slideover Cart → Disable Theme Mini-Cart**.
2. Choose one or more tactics:
   - **Tactic A** — enter the `theme_mod` keys your theme uses to toggle its cart icon (e.g. `header_cart`).
   - **Tactic B** — enter `hook|ClassName::method` lines for action callbacks to remove (e.g. `wp_footer|Astra_Cart::render_header_cart`).
   - **Tactic C** — enter CSS selectors to hide (e.g. `.site-header-cart`).

### Configure free shipping progress bar

Set a threshold (e.g. `50`) in **WooCommerce → Slideover Cart → Free Shipping Threshold**. Set to `0` to hide the bar.

### Customise appearance

Override CSS custom properties in your theme stylesheet:

```css
:root {
  --eva-sc-color-primary: #e63946;   /* accent / button colour */
  --eva-sc-color-bg:      #fafafa;   /* drawer background */
  --eva-sc-drawer-width:  400px;
}
```

### Override templates

Copy any file from `eva-slideover-cart/templates/` into `your-theme/eva-slideover-cart/` and edit freely.

### Use Font Awesome or Line Awesome for the cart icon

If your theme loads Font Awesome or Line Awesome, use the `eva_sc_trigger_icon_html` filter to replace the default SVG with an icon font:

```php
add_filter( 'eva_sc_trigger_icon_html', function( $icon ) {
    return '<span class="eva-sc-trigger-icon cart-icon" aria-hidden="true"></span>';
});
```

Then add CSS (or use your theme's existing `.cart-icon` styles):

```css
.eva-sc-trigger .eva-sc-trigger-icon::before {
  content: '\f07a';
  font-family: 'Line Awesome Free';
  font-weight: 900;
}
```

For Font Awesome 5 Solid, use `font-family: 'Font Awesome 5 Free'` and `font-weight: 900`.

---

## Developer Hooks

### Filters

| Filter | Type | Description |
|--------|------|-------------|
| `eva_sc_enabled` | bool | Enable/disable plugin |
| `eva_sc_open_on_add_to_cart` | bool | Auto-open drawer on add-to-cart |
| `eva_sc_drawer_position` | string | `right` or `left` |
| `eva_sc_trigger_icon_html` | string | Icon markup (SVG, span, or i for Font Awesome) |
| `eva_sc_trigger_html` | string | Trigger button markup |
| `eva_sc_drawer_header` | string | Drawer header markup |
| `eva_sc_drawer_footer` | string | Drawer footer markup |
| `eva_sc_drawer_classes` | array | CSS classes on the `<aside>` |
| `eva_sc_free_shipping_threshold` | float | Threshold amount |
| `eva_sc_free_shipping_current_amount` | float | Current cart amount |
| `eva_sc_free_shipping_html` | string | Full progress bar HTML |
| `eva_sc_disable_theme_cart_theme_mods` | array | theme_mod keys |
| `eva_sc_disable_theme_cart_remove_actions` | array | hook\|identifier lines |
| `eva_sc_disable_theme_cart_hide_selectors` | array | CSS selectors |

### Actions

- `eva_sc_before_drawer_header`
- `eva_sc_after_drawer_header`
- `eva_sc_after_items`
- `eva_sc_after_drawer_footer`

---

## FAQ

**Does this break WooCommerce checkout?**  
No. The plugin is purely a UI layer. It calls WooCommerce's own cart API for quantity updates and item removal, and uses the native fragment system for live updates.

**My theme mini-cart still shows after enabling the plugin. What do I do?**  
Use one of the three disable tactics in Settings. Tactic C (CSS selectors) is the safest fallback — inspect your theme's HTML, copy the mini-cart wrapper selector, and paste it into the field.

**Can I change the drawer accent colour?**  
Yes — override `--eva-sc-color-primary` in your theme CSS. No plugin code changes needed.

**Does it work with caching plugins?**  
Yes. The plugin uses WooCommerce's own fragment session mechanism which is cache-compatible.

**Is it compatible with WooCommerce HPOS?**  
Yes. The plugin only interacts with the cart and session, not orders. HPOS affects order storage only.

---

## Build

To create a distributable zip:

```bash
./build.sh
```

Output: `eva-slideover-cart-{version}.zip`

---

## Changelog

### 1.0.0
- Initial release.
