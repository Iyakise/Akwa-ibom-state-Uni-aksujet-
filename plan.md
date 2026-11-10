# Aksujet Laravel implementation plan

## Product scope

Create a separate, portable Laravel application for the AKSU Journal of Engineering and Technology (Aksujet). The backend is PHP/Laravel with MySQL/MariaDB for production and a separate SQLite database for automated tests. The application is intended for a PHP hosting account chosen by the owner. It is distinct from the static presentation ZIP and the earlier Manus-managed TypeScript prototype. Do not deploy it or publish content as part of this scaffold task.

## Implementation approach

- Use Laravel 13 on PHP 8.3 or later, with Composer and Laravel's standard web middleware, CSRF protection, password hashing, sessions and validation.
- Use MySQL/MariaDB as the configured production database. Keep local tests independent of owner credentials; use SQLite for automated schema and authorization tests.
- Use server-rendered Blade templates and a small, framework-free browser layer so the project remains straightforward to run on conventional PHP hosting. Reuse the Aksujet pine/copper style, crest and research artwork from the existing static demo.
- Build role-based access for administrators, editors, reviewers and authors. Sensitive operations require authentication and role/ownership checks. Editorial-studio routes additionally require the server-side `AKSUJET_EDITORIAL_ALLOWLIST`; the owner-specified address is `justiceuyompcs@gmail.com` and is configured privately in `.env`.
- Model the complete approved workflow with migrations: submissions, authors, revisions, private files, status history, editorial assignments, review rounds and assignments, reviews, decisions, correspondence, published journals/issues/articles, article files, notifications and audit logs.
- Keep pre-publication manuscripts separate from public archive articles. The nine supplied PDFs remain staged privately; an editor imports each only after entering and checking its bibliographic metadata, and each import is a private draft. Publication requires an explicit editor action after the issue is public.
- Include a placeholder-only `.env.example`, independent-hosting instructions, seed data limited to roles and a starter journal/issues, and feature coverage for migrations, login/role gating, file privacy and public archive access.

## Project structure

- `app/Models`: Laravel Eloquent models and relationships for users, roles, journals, issues, manuscripts, reviews and articles.
- `app/Http/Controllers/{Public,Author,Editor,Reviewer}`: route handlers separated by actor and access boundary.
- `app/Http/Middleware` and `app/Http/Requests`: role/allowlist checks, security headers and validated request handling.
- `app/Services`, `app/Enums`: manuscript workflow transitions, reference generation, secure file handling and status vocabularies.
- `database/migrations`, `database/seeders`, `database/factories`: MySQL-compatible schema, minimal baseline seeds and test factories.
- `resources/views/{public,author,editor,reviewer,auth}`: accessible Blade interfaces using Aksujet visual tokens.
- `public/assets`, `public/styles.css`, `public/script.js`, `public/build`: crest, decorative brand artwork, browser interactions and compiled frontend assets.
- `storage/app/private`: manuscripts, revisions, reviewer files, correspondence attachments, staged legacy PDFs and article PDFs. Article PDFs are streamed only through authorization/status-checked routes; no public storage symlink is used for them.
- `routes/web.php`: public routes and authenticated route groups with role/ownership middleware.
- `tests/Feature` and `tests/Unit`: authorization, author submission, editorial review, archive import and publication coverage.

## Design direction

Reuse the already approved Aksujet visual language rather than reselecting a style: editorial modernism informed by university research publishing. Pine green conveys institutional trust; warm cream keeps long-form reading calm; copper is reserved for emphasis and action. Retain the supplied crest, sculptural folio artwork, serif display headings paired with clear sans-serif interface text, and accessible reduced-motion behavior. Public pages should feel scholarly; private dashboards should prioritize clarity and task status over decoration.

## Boundaries

The owner specified the editorial administrator email, which must be placed privately in the server-side allowlist. The production hosting provider, MySQL credentials, mail service, final public domain and backup destination are not yet supplied. They must remain environment configuration, not committed source. Do not create production accounts, claim live indexing, or deploy until the owner selects the host and approves production setup.
