# AGENTS.md
Guidance for coding agents working in this repository.

## Scope and repository layout
- Root repo: `eva-cart-plugin`
- Main plugin: `eva-slideover-cart/`
- Bootstrap file: `eva-slideover-cart/eva-slideover-cart.php`
- PHP logic: `eva-slideover-cart/includes/`
- Templates: `eva-slideover-cart/templates/`
- Frontend assets: `eva-slideover-cart/assets/js/` and `eva-slideover-cart/assets/css/`
- Packaging script: `eva-slideover-cart/build.sh`

## Cursor and Copilot rules
At the time this file was updated, no agent rule files exist:
- `.cursorrules`: not present
- `.cursor/rules/`: not present
- `.github/copilot-instructions.md`: not present

If any of these are added later, treat them as higher-priority instructions and reflect them here.

## Tooling reality
- WordPress + WooCommerce plugin, PHP 8+
- No `composer.json`, `package.json`, `phpunit.xml`, or CI config in repo
- No first-class automated lint/test workflow is committed yet
- Validation is currently syntax checks + manual WooCommerce flow checks

## Build commands
Run from `eva-slideover-cart/` unless noted.

1) Build distributable zip
```bash
./build.sh
```

- Generates `../eva-slideover-cart-<version>.zip`
- Version is parsed from plugin header in `eva-slideover-cart.php`

2) Make script executable (if needed)
```bash
chmod +x build.sh
```

3) Inspect zip contents
```bash
unzip -l ../eva-slideover-cart-*.zip
```

## Lint and static checks
No committed linter config; use these safe defaults.

1) PHP syntax check all plugin PHP files
```bash
find . -name "*.php" -print0 | xargs -0 -n1 php -l
```

2) JavaScript syntax check
```bash
node --check assets/js/drawer.js
```

3) Optional PHPCS (if globally available)
```bash
phpcs --standard=WordPress eva-slideover-cart.php includes templates uninstall.php
```

4) Optional PHPCBF autofix
```bash
phpcbf --standard=WordPress eva-slideover-cart.php includes templates uninstall.php
```

## Test commands
Automated tests are not configured in this repository right now.

### Practical validation flow
1) Build zip and install plugin in local WP + WooCommerce
2) Activate plugin and review `WooCommerce -> Slideover Cart` settings
3) Validate core behavior:
   - Trigger opens/closes drawer
   - Woo fragments refresh count/items/subtotal
   - AJAX quantity updates work
   - AJAX remove item works
   - Free shipping progress updates correctly
   - Theme mini-cart suppression tactics A/B/C behave as configured

### Single test command (important)
- There is currently no in-repo single-test command because no test framework is wired.
- If PHPUnit is introduced later, use patterns like:
```bash
phpunit tests/ClassAjaxTest.php
phpunit --filter test_update_qty
```

## PHP style and architecture guidelines
- Use ABSPATH guard in runtime files: `defined( 'ABSPATH' ) || exit;`
- Keep bootstrap explicit with `require_once` includes; no autoloader today
- Do not introduce namespaces unless doing a broad repo-wide refactor
- Prefer typed properties and return types when touching class code
- Use `mixed` only at WP/WC boundaries (settings input, option values)
- Use early returns for guard/failure paths
- Keep arrays in short syntax `[]`
- Prefer strict comparisons and explicit casts for WP/WC values
- Keep docblocks for public methods and non-obvious private helpers
- Keep user-facing strings translatable (`__`, `_e`, `esc_html__`, `esc_attr__`)

### Naming conventions
- Class names: `EVA_SC_*` (example: `EVA_SC_Ajax`)
- Function names: `eva_sc_*` (example: `eva_sc_get_option`)
- Constants: uppercase `EVA_SC_*`
- Hook names and option keys: lowercase snake_case with `eva_sc_` prefix
- CSS class namespace: `eva-sc-`

### Imports and dependencies
- No `use` imports currently (no namespaces)
- Use WordPress/WooCommerce functions directly
- Add new PHP files via explicit `require_once` in bootstrap

### Sanitization and escaping
- Sanitize input on ingress (`sanitize_text_field`, `sanitize_key`, `wc_clean`, `absint`, etc.)
- Escape on output (`esc_html`, `esc_attr`, `esc_url`, `esc_textarea`, `wp_kses_post`)
- If output is intentionally pre-escaped HTML, include brief inline rationale

### Error handling and security
- Verify nonce first in AJAX handlers (`check_ajax_referer`)
- Return structured JSON errors with status codes (`wp_send_json_error`)
- Validate cart-item existence before cart mutation
- Fail gracefully when WooCommerce is unavailable; show admin notice

## JavaScript guidelines
- Keep IIFE wrapper and `'use strict'`
- Match current style: ES5-compatible, non-module, `var` + function declarations
- Prefer delegated handlers for fragment-replaced DOM
- Preserve Woo fragment bridge (`wc_fragment_refresh`)
- Keep accessibility intact (focus trap, Escape close, aria attributes)
- Use defensive null checks before DOM operations

## CSS guidelines
- Keep selectors under `.eva-sc-` prefix to avoid theme collisions
- Use CSS custom properties in `:root` for theme tokens
- Preserve responsive breakpoints and >=44x44 touch targets
- Prefer scoped changes over broad resets
- Maintain accessible contrast and focus-visible behavior

## Templates and rendering
- Preserve override mechanism via `eva_sc_locate_template()`
- Keep existing filters/actions around drawer header/body/footer
- Keep fragment selector keys aligned with actual DOM wrappers
- Avoid changing wrapper class names unless JS/fragments are updated together

## Change management expectations
- Keep changes minimal and targeted; avoid unrelated refactors
- Maintain backward compatibility for hooks, option keys, and template structure
- When adding settings: add defaults, sanitize on save, escape on render, add safe fallback behavior
- Update `README.md` and `eva-slideover-cart/readme.txt` for user-facing behavior changes
- If new tooling is introduced (Composer/NPM/PHPUnit), update this file with exact commands
