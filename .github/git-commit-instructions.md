# Git Commit Instructions for WP PostgreSQL Database

Consistent commit messages improve readability, changelog generation, and release automation.

## 1. Format (Conventional Style)

```
<type>(<optional-scope>): <short imperative summary>

<optional body>

<optional footer>
```

- Summary: ≤ 72 chars, imperative, no trailing period.
- Wrap body lines at ~100 chars.
- Separate sections with blank lines.

## 2. Allowed Types

| Type | Purpose | Examples |
|------|---------|----------|
| feat | New user-facing feature | feat(driver): add prepared statement support |
| fix | Bug fix | fix(lexer): correct quote handling in strings |
| perf | Performance improvement | perf(translator): cache query translations |
| refactor | Code change w/o feature/bug impact | refactor(schema): extract table mapper |
| docs | Documentation only | docs(readme): add installation steps |
| test | Tests added/updated | test(db): add integration test for insert |
| chore | Repo maintenance (no src impact) | chore: update .gitignore |
| build | Build system / tooling | build: add phpcs config |
| ci | Continuous integration config | ci: add php 8.4 to matrix |
| security | Security-related fix | security(db): sanitize prepared statement |

(Use one primary type; secondary concerns go in body.)

## 3. Scopes (Optional)

Common scopes: `driver`, `translator`, `lexer`, `schema`, `installer`, `health`, `compat`, `admin`, `migration`.
Use lowercase; add new scopes sparingly.

## 4. Breaking Changes

- Start a body line with `BREAKING CHANGE:` followed by explanation & migration steps.
- Optionally append `!` after type/scope (e.g., `feat(driver)!:`) – still include the body note.

Example:
```
feat(driver)!: change query result format

BREAKING CHANGE: results now return associative arrays by default.
Update existing code accordingly.
```

## 5. Referencing Issues & PRs

Footer lines:
- `Closes #123`
- `Refs #456`
One reference per line.

## 6. Body Content Guidelines

Explain:
- Motivation (why)
- Approach (how) if non-trivial
- Side effects / trade-offs
- Performance or security considerations
- Testing notes ("Adds regression test", "Covered by existing tests")

## 7. Examples

```
feat(driver): add PostgreSQL prepared statement support

Adds $wpdb->prepare() implementation for PostgreSQL.
Closes #10

fix(lexer): handle escaped quotes in string literals

Prevents parse errors with escaped quotes.

perf(translator): cache translated queries

Adds query cache to avoid re-translation.

refactor(schema): extract Table_Mapper class

No behavior change; improves testability.

security(db): validate prepared statement placeholders

Prevents SQL injection via malformed placeholders.
Closes #25

docs(readme): document compatibility matrix

test(translator): add regression test for LIKE queries

```

## 8. Security / Sensitive Fixes

- Use `security:` type.
- Keep exploit details minimal until release; share full context privately.

## 9. Translation & Escaping Notes

If adding user-facing strings: mention i18n + escaping (e.g., "All new strings wrapped in `__()`; output escaped with `esc_html`").
Text domain: `wp-pgsql-database`

## 10. Tests Reference

When logic changes: add/adjust tests. If deferred (rare), justify in body.

## 11. Commit Hygiene Checklist

- PHPCS / linters pass.
- No debug output (`var_dump`, `console.log`).
- Inputs validated & output escaped.
- i18n applied (text domain: `wp-pgsql-database`).
- No obvious performance regressions (N+1 queries, etc.).
- Tests updated/added.

## 12. Squashing & History

- Squash trivial fixup commits before merge.
- Do not squash security fix commits with unrelated changes.

## 13. Changelog Compatibility

Accurate types enable automated categorization (feat/fix/perf/security). Choose carefully.

## 14. Anti-Patterns

Avoid: `fix stuff`, `update code`, past tense (Added/Fixes), ticket-only messages, multi-unrelated changes.

## 15. When Unsure

Default: feat (new behavior), fix (defect), refactor (internal), chore (maintenance). Ask in PR if edge.

---

Following these conventions keeps WP PostgreSQL Database history clean, searchable, and automatable.

Thank you for contributing!
