# WP PostgreSQL Database

A production-grade WordPress plugin that adds PostgreSQL database driver support via a `db.php` drop-in. Enables WordPress to run on PostgreSQL without any code changes to core, plugins, or themes.

---

## Architecture

```
wp-pgsql-database/
├── wp-pgsql-database.php              # MU-plugin entry point & autoloader
├── db.copy                            # Drop-in template → wp-content/db.php
├── composer.json
├── phpstan.neon
├── phpcs.xml
├── phpunit.xml
├── includes/
│   ├── class-wp-pgsql-database.php    # Singleton orchestrator
│   ├── driver/
│   │   ├── class-wp-pgsql-driver-interface.php
│   │   └── class-wp-pgsql-driver.php
│   ├── translator/
│   │   ├── class-wp-pgsql-token.php
│   │   ├── class-wp-pgsql-lexer.php
│   │   └── class-wp-pgsql-translator.php
│   ├── database/
│   │   └── class-wp-pgsql-db.php
│   ├── schema/
│   │   └── class-wp-pgsql-schema-mapper.php
│   ├── migration/
│   │   ├── class-wp-pgsql-installer.php
│   │   └── class-wp-pgsql-migrator.php
│   ├── admin/
│   │   ├── class-wp-pgsql-admin.php
│   │   └── class-wp-pgsql-health-check.php
│   └── compat/
│       ├── class-wp-pgsql-query-logger.php
│       └── class-wp-pgsql-diagnostics.php
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
├── languages/
│   └── wp-pgsql-database.pot
└── tests/
    ├── bootstrap.php
    ├── bootstrap-phpstan.php
    ├── unit/
    │   ├── class-wp-pgsql-test-case.php
    │   ├── test-wp-pgsql-lexer.php
    │   ├── test-wp-pgsql-translator.php
    │   ├── test-wp-pgsql-schema-mapper.php
    │   └── test-wp-pgsql-installer.php
    └── integration/
        └── test-wp-pgsql-db-integration.php
```

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.0+ |
| WordPress | 6.0+ |
| PostgreSQL | 12+ |
| PHP extensions | `pdo`, `pdo_pgsql` |

---

## Installation

### 1. Install as a standard plugin or MU-plugin

```bash
# Via Composer
composer require your-username/wp-pgsql-database

# Or clone directly
git clone https://github.com/your-username/wp-pgsql-database.git \
  wp-content/plugins/wp-pgsql-database
```

### 2. Add constants to `wp-config.php`

```php
define( 'DB_ENGINE',   'pgsql' );
define( 'DB_HOST',     'localhost' );      // or 'localhost:5432'
define( 'DB_NAME',     'wordpress' );
define( 'DB_USER',     'wp_user' );
define( 'DB_PASSWORD', 'secret' );
```

### 3. Install the drop-in

Navigate to **Tools › PostgreSQL DB** in the WordPress admin and click **Install Drop-in**. Alternatively, activate the plugin — the drop-in is installed automatically on activation.

### 4. Verify

Visit **Tools › Site Health** to confirm both the drop-in and connection health checks pass.

---

## How It Works

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
        │           ├── WP_PgSQL_Lexer    (tokenise)
        │           └── WP_PgSQL_Translator (rewrite)
        │
        └──► WP_PgSQL_Driver      ← PDO pgsql execution
```

The drop-in is loaded at WordPress bootstrap time — before `wp-settings.php` processes plugins and themes. It replaces `$wpdb` with `WP_PgSQL_Db`, which routes every query through the translation pipeline before executing it against PostgreSQL via PDO.

---

## MySQL → PostgreSQL Translation Reference

| MySQL construct | PostgreSQL equivalent |
|---|---|
| Backtick identifiers `` `col` `` | Double-quoted `"col"` |
| `AUTO_INCREMENT` (INT) | `SERIAL` |
| `AUTO_INCREMENT` (BIGINT) | `BIGSERIAL` |
| `UNSIGNED` | *(stripped)* |
| `TINYINT(1)` | `BOOLEAN` |
| `DATETIME` | `TIMESTAMP` |
| `LONGTEXT` / `MEDIUMTEXT` / `TINYTEXT` | `TEXT` |
| `JSON` | `JSONB` |
| `INSERT IGNORE` | `INSERT … ON CONFLICT DO NOTHING` |
| `ON DUPLICATE KEY UPDATE` | `ON CONFLICT DO UPDATE SET` |
| `LIMIT x,y` | `LIMIT y OFFSET x` |
| `IFNULL(a, b)` | `COALESCE(a, b)` |
| `REGEXP` / `RLIKE` | `~` |
| `SHOW TABLES` | `information_schema.tables` query |
| `SHOW COLUMNS FROM t` | `information_schema.columns` query |
| `ENGINE=InnoDB` | *(stripped)* |
| `DEFAULT CHARSET=utf8mb4` | *(stripped)* |

---

## Development

```bash
# Install dependencies
composer install

# Run PHPCS
composer phpcs

# Run PHPStan (level 8)
composer phpstan

# Run unit tests
composer test

# Run all linters
composer lint
```

### Integration tests

Integration tests require a live PostgreSQL instance:

```bash
export DB_ENGINE=pgsql
export DB_HOST=localhost
export DB_NAME=wp_test
export DB_USER=wp_user
export DB_PASSWORD=secret

composer test -- --testsuite Integration
```

---

## Contributing

Pull requests are welcome. Please ensure all linters pass (`composer lint`) and add tests for any new translation rules.

---

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for details.
