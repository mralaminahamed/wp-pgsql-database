# AGENTS.md - WP PostgreSQL Database

Agent-specific documentation for the WP PostgreSQL Database WordPress plugin.

## Overview

PostgreSQL database driver for WordPress via a db.php drop-in. Enables WordPress to run on PostgreSQL without code changes to core, plugins, or themes. Includes MySQL to PostgreSQL query translation.

- **PHP**: 7.4+ | **WordPress**: 6.0+ | **PostgreSQL**: 12+
- **Namespace**: `WP_PgSQL_Database` | **Text Domain**: `wp-pgsql-database`

---

## 1. Build / Lint / Test Commands

### PHP Code Sniffer
```bash
# Full plugin with custom ruleset
./vendor/bin/phpcs --standard=phpcs.xml.dist includes/ wp-pgsql-database.php db.copy

# Auto-fix
./vendor/bin/phpcbf includes/ wp-pgsql-database.php db.copy
```

### PHPStan
```bash
./vendor/bin/phpstan analyse
```

### PHPUnit
```bash
# All tests
./vendor/bin/phpunit

# Single test file (PSR-4 style)
./vendor/bin/phpunit tests/php/src/Unit/Translator/TranslatorTest.php

# Specific test method
./vendor/bin/phpunit --filter test_translate_select_returns_postgresql

# Integration tests (requires live PostgreSQL)
export DB_ENGINE=pgsql DB_HOST=localhost DB_NAME=wp_test DB_USER=wp_user DB_PASSWORD=secret
./vendor/bin/phpunit --testsuite Integration
```

### Other
```bash
composer makepot     # Generate .pot file
composer release     # Create release zip
```

---

## 2. Code Style Guidelines

### General
- Always use `declare( strict_types=1 );` at the top of PHP files
- Follow WordPress Coding Standards (WPCS)
- Use PHP 7.4+ syntax (typed properties, null coalescing)
- Use dependency injection via constructors; avoid globals where possible

### Namespaces & Class Files
```
WP_PgSQL_Database\
├── Admin\
├── Compat\
├── Database\
├── Driver\
├── Migration\
├── Schema\
├── Translator\
└── Filesystem\
```

Class file format: `class-wp-pgsql-*.php`

### Naming Conventions
- Classes: `PascalCase` (e.g., `WP_PgSQL_Db`, `WP_PgSQL_Translator`)
- Methods/Properties: `snake_case` (e.g., `translate_query`, `$driver`)
- Constants: `UPPER_SNAKE_CASE` (e.g., `WP_PGSQL_DB_VERSION`)
- Hooks: lowercase with underscores

### Imports
Use explicit class imports. Avoid fully qualified names in code:

```php
// Good
use WP_PgSQL_Database\Driver\WP_PgSQL_Driver;
use WP_PgSQL_Database\Translator\WP_PgSQL_Translator;

// Bad
$driver = \WP_PgSQL_Database\Driver\WP_PgSQL_Driver::get_instance();
```

### PHPDoc
Document all public methods with `@param`, `@return`:

```php
/**
 * Translate MySQL query to PostgreSQL syntax.
 *
 * @param string $query MySQL query string.
 * @return string Translated PostgreSQL query.
 */
public function translate( string $query ): string {}
```

### Error Handling
- Throw domain-specific exceptions
- Catch at appropriate levels
- Use `\WP_Error` for WordPress-specific errors

---

## 3. Security

- **Escape output**: `esc_html__()`, `esc_attr__()`, `esc_url()`, `esc_js()`
- **Sanitize input**: `sanitize_text_field()`, `absint()`, `wp_kses()`
- **Nonces**: `wp_create_nonce()`, `check_admin_referer()`
- **Capabilities**: `current_user_can( 'manage_options' )`
- **Database**: `$wpdb->prepare()` with placeholders (`%s`, `%d`)

```php
// Always escape
echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Link', 'wp-pgsql-database' ) . '</a>';

// Always sanitize
$order_id = absint( $_POST['order_id'] );
```

---

## 4. Internationalization (i18n)

- Wrap all user-facing strings: `__( 'Text', 'wp-pgsql-database' )`
- Use escape variants: `esc_html__()`, `esc_html_e()`, `esc_attr__()`
- Never concatenate translatable strings; use `sprintf()`:

```php
// Bad
$msg = __( 'Error in ' . $table, 'wp-pgsql-database' );

// Good
$msg = sprintf( __( 'Error in %s', 'wp-pgsql-database' ), $table );
```

---

## 5. JavaScript

- Plain JS in `assets/js/` — no build step
- Use IIFE pattern with jQuery:

```javascript
( function ( $ ) {
    'use strict';
    $( document ).ready( function () {} );
}( jQuery ) );
```

- Prefer `const` over `let`, avoid `var`

---

## 6. File Organization

```
includes/
├── class-wp-pgsql-database.php   # Main plugin class
├── class-wp-pgsql-filesystem.php # Filesystem wrapper
├── database/                     # Core database class (extends wpdb)
├── driver/                      # PDO PostgreSQL driver
├── translator/                  # MySQL to PostgreSQL translation
│   ├── class-wp-pgsql-lexer.php
│   ├── class-wp-pgsql-token.php
│   └── class-wp-pgsql-translator.php
├── schema/                      # Schema mapper
├── migration/                   # Installation/migration
├── admin/                       # Admin UI and health checks
└── compat/                      # Compatibility layer
```

---

## 7. Testing

- Tests in `tests/php/src/` mirroring class path
- Use PHPUnit with Brain Monkey for WP mocking
- Test naming: PSR-4 style with `Test.php` suffix (e.g., `DbTest.php`, `DriverTest.php`)
- Namespace: `WP_PgSQL_Database\Tests\Unit\<Namespace>`

```php
namespace WP_PgSQL_Database\Tests\Unit\Translator;

use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase {
    public function test_translate_select_returns_postgresql() {
        $translator = new WP_PgSQL_Translator( new WP_PgSQL_Lexer() );
        $result     = $translator->translate( 'SELECT * FROM wp_posts' );
        $this->assertStringContainsString( 'wp_posts', $result );
    }
}
```

### Test Commands
```bash
# All tests
./vendor/bin/phpunit

# Single test file (PSR-4 style)
./vendor/bin/phpunit tests/php/src/Unit/Translator/TranslatorTest.php

# Specific test method
./vendor/bin/phpunit --filter test_translate_select_returns_postgresql

# Unit tests only
./vendor/bin/phpunit --testsuite Unit

# Integration tests (requires live PostgreSQL)
export DB_ENGINE=pgsql DB_HOST=localhost DB_NAME=wp_test DB_USER=wp_user DB_PASSWORD=secret
./vendor/bin/phpunit --testsuite Integration
```

---

## 8. Commit Messages

Format: `<type>(<scope>): <short imperative summary>`

Types: `feat`, `fix`, `perf`, `refactor`, `docs`, `test`, `chore`, `build`, `ci`, `security`

Scopes: `driver`, `translator`, `lexer`, `schema`, `admin`, `migration`, `compat`

---

## 9. Architecture Overview

```
WordPress core / plugins
         │
         ▼  (all $wpdb calls)
   wp-content/db.php          ← drop-in: loaded before any plugin
         │
         ▼
   WP_PgSQL_Db extends wpdb
         │
         ├──► WP_PgSQL_Translator  ← MySQL → PostgreSQL SQL rewrite
         │           │
         │           ├── WP_PgSQL_Lexer    (tokenize)
         │           └── WP_PgSQL_Translator (rewrite)
         │
         └──► WP_PgSQL_Driver      ← PDO pgsql execution
```

---

## 10. Key Security Considerations

1. Never expose database credentials
2. Validate all SQL placeholders in `$wpdb->prepare()`
3. Use nonces for all AJAX/admin form submissions
4. Check capabilities before privileged operations
5. Sanitize all input — never trust `$_GET`, `$_POST`, `$_REQUEST`
6. Escape all output
7. The drop-in (`db.copy`) should never be edited directly
8. Never commit `tests/php/phpunit-wp-config.php` — it contains database credentials

---

## 11. MySQL to PostgreSQL Translation

Key translation rules to follow:

| MySQL | PostgreSQL |
|-------|------------|
| Backtick `` `col` `` | Double-quoted `"col"` |
| `AUTO_INCREMENT` | `SERIAL` / `BIGSERIAL` |
| `UNSIGNED` | (stripped) |
| `TINYINT(1)` | `BOOLEAN` |
| `DATETIME` | `TIMESTAMP` |
| `JSON` | `JSONB` |
| `INSERT IGNORE` | `INSERT ... ON CONFLICT DO NOTHING` |
| `LIMIT x,y` | `LIMIT y OFFSET x` |
| `IFNULL(a, b)` | `COALESCE(a, b)` |

---

This file is used by AI agents to understand project conventions.
