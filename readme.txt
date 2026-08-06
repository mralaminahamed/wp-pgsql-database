=== WP PostgreSQL Database ===
Contributors:      mralaminahamed
Tags:              database, postgresql, pgsql, db driver, wpdb
Requires at least: 6.0
Tested up to:      6.9
Requires PHP:      8.0
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Adds PostgreSQL database driver support to WordPress via a db.php drop-in,
enabling WordPress to run on PostgreSQL without any code changes to core,
plugins, or themes.

== Description ==

WP PostgreSQL Database replaces the default MySQL driver with a
PostgreSQL-aware alternative, using the native WordPress `db.php` drop-in
mechanism. All queries issued by WordPress core, plugins, and themes through
the standard `$wpdb` API are transparently intercepted, translated from
MySQL dialect to PostgreSQL-compatible SQL, and executed via PDO.

No modifications to WordPress core, plugins, or themes are required.

**How it works**

The plugin installs a `db.php` drop-in to `wp-content/db.php`. This file
is loaded by WordPress at the very start of its bootstrap sequence — before
any plugin or theme — and replaces the global `$wpdb` instance with a
PostgreSQL-aware subclass (`WP_PgSQL_Db`). Every query passes through a
pure-PHP MySQL lexer and translator before being executed against PostgreSQL
via PHP's PDO extension.

**Translation pipeline**

The translation pipeline converts MySQL-dialect SQL to PostgreSQL-compatible
SQL automatically. Key rewrites include:

* Backtick identifiers → double-quoted identifiers
* `AUTO_INCREMENT` (INT) → `SERIAL`
* `AUTO_INCREMENT` (BIGINT) → `BIGSERIAL`
* `UNSIGNED` modifier → stripped
* `TINYINT(1)` → `BOOLEAN`
* `DATETIME` → `TIMESTAMP`
* `LONGTEXT` / `MEDIUMTEXT` / `TINYTEXT` → `TEXT`
* `JSON` → `JSONB`
* `INSERT IGNORE` → `INSERT … ON CONFLICT DO NOTHING`
* `ON DUPLICATE KEY UPDATE` → `ON CONFLICT DO UPDATE SET`
* `LIMIT x,y` offset syntax → `LIMIT y OFFSET x`
* `IFNULL(a, b)` → `COALESCE(a, b)`
* `REGEXP` / `RLIKE` → `~` operator
* `SHOW TABLES` → `information_schema.tables` query
* `SHOW COLUMNS FROM t` → `information_schema.columns` query
* `ENGINE=InnoDB`, `DEFAULT CHARSET=utf8mb4` → stripped

**Admin integration**

* **Tools › PostgreSQL DB** — drop-in status dashboard, one-click install /
  remove, and `wp-config.php` configuration instructions.
* **Tools › Site Health** — two dedicated health checks: drop-in status and
  live connection test.
* **Admin toolbar** — query count indicator when `WP_DEBUG` is active.

**Developer tooling**

* PHPStan static analysis at level 8 with the `szepeviktor/phpstan-wordpress`
  extension.
* PHPCS against `WordPress-Extra` and `WordPress-Docs` rulesets.
* PHPUnit test suite with unit tests (Brain\Monkey) and integration tests
  targeting a live PostgreSQL instance.
* PSR-4 autoloader via Composer, with WordPress Coding Standards-compliant
  file and directory naming throughout.

== Installation ==

= Minimum requirements =

* PHP 8.0 or higher
* WordPress 6.0 or higher
* PostgreSQL 12 or higher
* PHP extensions: `pdo`, `pdo_pgsql`

= Step 1 — Install the plugin =

Upload the `wp-pgsql-database` directory to `wp-content/plugins/` and
activate it through the **Plugins** screen, or install it directly from the
WordPress plugin repository.

The plugin can also be placed in `wp-content/mu-plugins/` to ensure it loads
before all other plugins.

= Step 2 — Configure wp-config.php =

Add the following constants to your `wp-config.php` file **before** the
`/* That's all, stop editing! */` line:

    define( 'DB_ENGINE',   'pgsql' );
    define( 'DB_HOST',     'localhost' );
    define( 'DB_NAME',     'your_database' );
    define( 'DB_USER',     'your_username' );
    define( 'DB_PASSWORD', 'your_password' );

The `DB_HOST` value may include a custom port, for example `localhost:5432`.

= Step 3 — Install the drop-in =

Navigate to **Tools › PostgreSQL DB** in the WordPress admin and click
**Install Drop-in**. This copies the `db.copy` template to
`wp-content/db.php`.

Alternatively, the drop-in is installed automatically when the plugin is
activated for the first time.

= Step 4 — Verify =

Visit **Tools › Site Health**. Both the **PostgreSQL drop-in** and
**PostgreSQL connection** checks should report a passing status.

== Frequently Asked Questions ==

= Does this plugin require changes to WordPress core? =

No. The plugin uses the official WordPress `db.php` drop-in mechanism, which
is a supported extension point built into WordPress core.

= Will my existing plugins work with PostgreSQL? =

Most plugins that use only the standard `$wpdb` API and do not rely on
MySQL-specific raw SQL constructs will work without modification. Plugins
that bypass `$wpdb` or issue heavily MySQL-specific queries may require
manual review.

= Can I switch back to MySQL? =

Yes. Navigate to **Tools › PostgreSQL DB** and click **Remove Drop-in**, or
deactivate the plugin. WordPress will revert to the default MySQL driver
immediately. You do not need to modify `wp-config.php` unless you also wish
to remove the `DB_ENGINE` constant.

= What PostgreSQL version is required? =

PostgreSQL 12 or higher is required. PostgreSQL 14 or higher is recommended
for optimal `JSONB` support and `ON CONFLICT` clause behaviour.

= Is the `pdo_pgsql` PHP extension required? =

Yes. The plugin uses PHP's PDO abstraction layer with the `pdo_pgsql` driver.
Both `ext-pdo` and `ext-pdo_pgsql` must be enabled in your PHP configuration.
Most managed hosting environments and PHP packages for Debian/Ubuntu include
these extensions. On Ubuntu: `sudo apt install php-pgsql`.

= Where is the database file stored? =

Unlike SQLite, PostgreSQL is a network-accessible server — no local file is
involved. The database is stored on the PostgreSQL server you configure via
the `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` constants.

= Can I use this as an MU-plugin? =

Yes. Placing the plugin in `wp-content/mu-plugins/` is the recommended
deployment strategy for production environments. The drop-in itself
(`wp-content/db.php`) loads before MU-plugins, so the database interception
always occurs at the correct bootstrap stage regardless of plugin location.

= What happens if the drop-in is out of date after a plugin update? =

The **Tools › Site Health** screen will report a "recommended" status
indicating that an update is available. Navigate to **Tools › PostgreSQL DB**
and click **Update Drop-in** to bring it in sync with the current plugin
version.

= Is WooCommerce supported? =

WooCommerce compatibility is a development goal. WooCommerce's usage of
`$wpdb` is broadly standard, but its HPOS (High-Performance Order Storage)
feature and some payment gateway plugins issue complex SQL that may require
targeted translation rules. Compatibility testing against WooCommerce is
included in the project roadmap.

= How do I run the test suite? =

Install development dependencies with `composer install`, then:

    # Unit tests (no database required)
    composer test

    # Integration tests (requires live PostgreSQL)
    DB_ENGINE=pgsql DB_HOST=localhost DB_NAME=wp_test \
    DB_USER=wp_user DB_PASSWORD=secret \
    composer test -- --testsuite Integration

== Changelog ==

= 1.0.0 =
* Initial release.
* Pure-PHP MySQL lexer and AST-based query translator.
* PostgreSQL PDO driver implementing the `WP_PgSQL_Driver_Interface` contract.
* `WP_PgSQL_Db` — `wpdb` subclass with transparent query routing.
* MySQL-to-PostgreSQL schema mapper for `CREATE TABLE` / `ALTER TABLE` DDL.
* Drop-in installer with activation / deactivation lifecycle management.
* Admin settings page under **Tools › PostgreSQL DB**.
* WordPress Site Health integration with two dedicated checks.
* Admin toolbar query counter (WP_DEBUG only).
* Query logger and diagnostics class (WP_DEBUG only).
* PHPStan level 8 configuration with `szepeviktor/phpstan-wordpress`.
* PHPCS ruleset against `WordPress-Extra` and `WordPress-Docs`.
* PHPUnit suite: unit tests (Brain\Monkey) and integration tests.
* Full i18n support with POT file.

== Upgrade Notice ==

= 1.0.0 =
Initial release. After upgrading, please visit Tools › PostgreSQL DB to
confirm the drop-in is current and re-run the Site Health checks.
