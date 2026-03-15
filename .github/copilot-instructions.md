# Copilot Instructions for WP PostgreSQL Database

Guidance for using GitHub Copilot (or similar AI assistants) while contributing to this WordPress PostgreSQL database driver plugin.

## 1. Scope of Acceptable AI Assistance

Use Copilot for accelerating repetitive or boilerplate tasks:
- WordPress database abstraction hooks, filters, db.php drop-in interface.
- PHP class scaffolding under `includes/` (respect namespace: `WP_PgSQL_Database`).
- PHPUnit test stubs (`tests/php/`).
- PHPDoc blocks, inline comments.
- Refactors: extracting methods, reducing duplication.

Avoid (require human authored or thorough review):
- Licensing, legal, business logic, data privacy decisions.
- Security-critical SQL generation, authentication/authorization logic, nonce / capability checks (must be verified).
- Large unreviewed generated files (delete & redo smaller chunks if produced).
- Query translation logic and PostgreSQL-specific handling.

## 2. Coding Standards & Tooling

- Run PHPCS with WordPress Coding Standards before committing:
  ```bash
  ./vendor/bin/phpcs --standard=WordPress --runtime-set testVersion 8.0- includes/ wp-pgsql-database.php
  ```
- Follow WordPress escaping/sanitizing conventions: `esc_html__`, `esc_attr__`, `esc_url`, `sanitize_text_field`, `wp_kses`, `wp_create_nonce`, `check_admin_referer`.
- Use dependency injection via constructors; avoid globals where possible.
- Keep functions small & single responsibility.
- Always use `declare( strict_types=1 );` at top of PHP files.

## 3. File / Architectural Conventions

- Main plugin file (`wp-pgsql-database.php`) defines constants and bootstraps.
- `includes/` contains core logic:
  - `database/` - Core database class
  - `driver/` - Database driver interface and implementation
  - `translator/` - SQL to PostgreSQL translation
  - `lexer/` - SQL query tokenizer
  - `schema/` - Schema mapper for WordPress tables to PostgreSQL
  - `migration/` - Installation and migration logic
  - `admin/` - Admin UI and health checks
  - `compat/` - Compatibility layer
- `class-wp-pgsql-database.php` - Main plugin class
- Assets are plain CSS/JS in `assets/` — no build step required.
- Tests in `tests/php/src/` with `unit/` and `integration/` subdirectories.

## 4. Security Checklist (AI suggestions must be manually validated)

- All DB queries: use `$wpdb->prepare` with placeholders (`%s`, `%d`), never string concatenation.
- Escape on output, sanitize on input, validate business rules.
- Nonces for state-changing actions (AJAX, form submissions) & proper capability checks (`current_user_can`).
- REST endpoints: explicit `permission_callback`; never return raw user data without filtering.
- Avoid exposing internal IDs or secrets; use hashed tokens.
- Never hardcode database credentials; use WordPress constants.

## 5. Performance & Reliability

- Cache expensive operations via transients when needed.
- Avoid N+1 queries inside loops – prefetch where possible.
- Prefer lazy-loading assets only on pages that need them (admin enqueue checks current screen hook).
- Use WordPress HTTP API (`wp_remote_get`, `wp_remote_post`) for external API calls.

## 6. Internationalization (i18n)

- All user-facing strings must be wrapped: `__( 'Text', 'wp-pgsql-database' )` or `esc_html__()`.
- Do not concatenate translatable strings with variables; use placeholders (sprintf).
- Text domain: `wp-pgsql-database`

## 7. Testing

- PHP: PHPUnit; place tests in `tests/php` mirroring class path.
- Write regression tests for any bug fix.
- Test query translation, schema mapping, driver operations.

## 8. Documentation & Comments

- Every public method: concise PHPDoc with `@param` types, `@return`, `@since`.
- Complex queries / algorithms: add rationale comments (why, not just what).
- Update readme.txt if user-facing change (new endpoint, hook, setting).

## 9. Commit Messages

Format: `<type>(<scope>): <short imperative summary>`
Types: `feat`, `fix`, `perf`, `refactor`, `docs`, `test`, `chore`, `build`, `ci`, `security`.
Optional scope: `driver`, `translator`, `lexer`, `schema`, `admin`, `migration`.

Example:
```
feat(driver): add prepared statement support

Adds $wpdb->prepare() implementation for PostgreSQL.
Closes #42
```

## 10. Pull Requests

- Ensure: coding standards pass, tests green, no debug var_dump / console.log, updated docs.
- AI-generated code must be marked in PR description with verification note.

## 11. Versioning

- Bump version in `wp-pgsql-database.php` only when preparing a release.
- Document notable changes in readme.txt.

## 12. Handling Sensitive / Proprietary Logic

- Do not paste database credentials, user PII, or undisclosed endpoints into prompts.
- Abstract secrets via WordPress options or constants; never hard-code.

## 13. Review Checklist Before Committing AI-Suggested Code

- [ ] Namespaced correctly (`WP_PgSQL_Database\*`).
- [ ] Escaping / sanitizing applied where needed.
- [ ] No raw input trust (`$_REQUEST`, `$_GET`, `$_POST`) without validation.
- [ ] Translation functions used for user text with 'wp-pgsql-database' domain.
- [ ] Memory / query usage reasonable.
- [ ] Tests added/updated.
- [ ] No dead or commented-out large blocks.
- [ ] Follows commit message spec.

## 14. Example Good Uses

PHP Hook:
```php
add_action( 'plugins_loaded', function(): void {
    // Initialize the database driver.
} );
```

PHPDoc:
```php
/**
 * Translate MySQL query to PostgreSQL syntax.
 *
 * @param string $query MySQL query string.
 * @return string Translated PostgreSQL query.
 */
public function translate( string $query ): string {
    // ...
}
```

Test Stub:
```php
class Translator_Test extends \PHPUnit\Framework\TestCase {
    public function test_translate_select_returns_postgresql() {
        $translator = new Translator();
        $result     = $translator->translate( 'SELECT * FROM wp_posts' );
        $this->assertStringContainsString( 'wp_posts', $result );
    }
}
```

## 15. When to Escalate Instead of Using Copilot

- Ambiguous product requirement – clarify with maintainer first.
- Potential security exploit or vulnerability – open private issue.
- Query translation logic changes.
- Schema mapping changes.

---

Thank you for contributing to WP PostgreSQL Database! Use Copilot responsibly — human judgment remains essential.
