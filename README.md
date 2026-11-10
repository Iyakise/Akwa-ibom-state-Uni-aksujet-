# Aksujet — AKSU journal publishing system

A portable Laravel application for the Akwa Ibom State University journal programme. It is designed for a conventional PHP host with MySQL or MariaDB and is separate from the earlier static design-preview website. It is not deployed by this repository task.

## Included capabilities

- Public journal, issue and article pages with server-rendered metadata, canonical URLs, citation metadata, robots rules and an XML sitemap.
- Email-verified author registration, online manuscript submission, private file storage, submission tracking and revision uploads.
- An owner-restricted editorial studio for screening, editor assignment, reviewer invitations, review rounds, decisions, author correspondence and issue/article publication.
- Reviewer accounts and assignment-scoped access to confidential manuscript files.
- A database-backed journal and issue setup workflow.
- A legacy-PDF import desk that reads the nine supplied PDFs from private staging. Imports start as unpublished drafts; an editor must verify the bibliographic metadata, publish the issue and explicitly release the article before the PDF becomes public.
- Audit records for editorial decisions, role grants, journal creation and publication events.

## Requirements

Use PHP 8.3 or later with the extensions required by Laravel 13, Composer, and MySQL 8/MariaDB supported by your host. The web server document root must point to the project's `public/` directory. For local development, PHP's built-in server can be used; production should use the host's supported PHP-FPM/Apache or Nginx configuration.

The downloadable source bundle includes the compiled `public/build` assets, so a production host does not need Node.js for a clean install. If you change frontend source files, install Node.js dependencies and rebuild with `npm install && npm run build` before uploading.

The migrations keep indexed text columns within the 1000-byte key limit used by some older Wampserver MySQL/MariaDB installations. If a migration previously failed after creating a partial table, do not simply rerun it against that partial database. Back up anything important, then point `.env` to a new empty Aksujet database before running migrations again.

## Local installation

1. Create a MySQL database and a database user with access only to that database.
2. Copy `.env.example` to `.env` and set `APP_URL`, database credentials, the mail transport, and the other host-specific values. The example defaults to a non-secure session cookie for local HTTP development; set `SESSION_SECURE_COOKIE=true` on the production HTTPS site.
3. Set the editorial allowlist in the private `.env` file. The intended owner is `justiceuyompcs@gmail.com`; set `AKSUJET_EDITORIAL_ALLOWLIST=justiceuyompcs@gmail.com`. Do not commit `.env` or put the real value in public source control. A blank allowlist denies all editorial-studio access.
4. Install dependencies and initialise the database:

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force --seed
   ```

5. Configure a real SMTP or host mail transport before production. Account registration requires email verification. Do not use the `log` mailer in production.
6. Register the owner using the allowlisted email address. That account receives the administrator role at registration. If that account already exists, rerun `php artisan db:seed` after setting the allowlist; the seeder grants administrator access to the matching account. Then verify the email address before entering the studio.
7. The seeded starter journal and two draft issue examples are placeholders for setup/testing. Check the real title, issue, year and identifiers before publishing; never publish the seeded drafts as real issues without verifying them.

For development, use the normal Laravel serve command after setting the local environment. Automated tests use an in-memory SQLite database and do not need production credentials:

```bash
php artisan test
```

## Editorial workflow

- **Set up a journal:** Sign in with the allowlisted owner account, open the Editorial desk, and create a journal. Add its actual ISSN/eISSN, scope and contact details.
- **Create an issue:** Create an issue draft for that journal, check its volume, issue number and year, then publish the issue when its metadata is correct. Articles cannot be released under a draft issue.
- **Import a legacy PDF:** Open **Article archive** in the workspace. Choose one of the staged PDFs, select its journal issue, verify the title and enter the abstract, authors, keywords and any DOI/date/pages/license printed in the PDF. The record is saved privately as a draft.
- **Release an article:** Confirm the journal/issue and article metadata, then use **Release article**. The article page and its full-text PDF become public only after this action. Imported PDFs are not automatically published.
- **Assign a reviewer:** Users register normally and verify their email. From **Manage accounts**, the owner can grant a verified account reviewer access. The reviewer then sees only assignments attached to their account.
- **Process submissions:** Authors submit and track manuscripts from **Submissions**. The editor screens the submission, opens review, invites reviewers, records a decision and correspondence, and creates a publication draft after acceptance.

The editorial studio itself is restricted by the server-side email allowlist, in addition to role checks. Only the allowlisted account can access the editor/administrator routes. Reviewers do not receive editorial-studio access.

## Private files and backups

Manuscripts, revisions, reviews and unpublished article PDFs are kept on Laravel's private local disk under `storage/app/private`; they are not exposed through public storage URLs. Download controllers check the authenticated role or public publication status before streaming a file. The supplied legacy PDFs are staged in `storage/app/private/imported-articles` and remain private until a public article record is released.

Back up the MySQL database and private storage together, encrypt backups at rest, restrict backup access, and regularly test restoration. Do not rely on a public `storage:link` for private files. If moving to S3-compatible storage, configure a private bucket and preserve the application's authorization-controlled download routes.

## Production checklist

- Use a production domain and set `APP_URL` to its HTTPS origin.
- Set `APP_ENV=production`, `APP_DEBUG=false`, a strong unique `APP_KEY`, `SESSION_SECURE_COOKIE=true`, production MySQL credentials, and a working mail transport.
- Put `.env` outside version control; keep the allowlist and database credentials in the host's protected environment/secret configuration.
- Point the web root to `public/`, enable HTTPS, use least-privilege database credentials, and configure log rotation and backups.
- Run migrations during deployment, then use Laravel's supported production optimization commands. Do not use `migrate:fresh` against production.
- Confirm actual journal and issue metadata, contact addresses, rights/licensing and publication permissions before releasing imported or new articles.
- Set the production canonical domain, confirm `/robots.txt` and `/sitemap.xml`, then submit the sitemap to Google Search Console. Search indexing is not guaranteed and can only be verified after the site is publicly reachable.

## Project map

- `app/Http/Controllers`: public, author, editor and reviewer workflows.
- `app/Http/Middleware`: role and editorial-allowlist enforcement.
- `app/Models`, `app/Enums`, `app/Services`: data relationships and manuscript workflow rules.
- `database/migrations`, `database/seeders`: MySQL-compatible schema and minimal setup records.
- `resources/views`: server-rendered public pages and role-specific workspaces.
- `public/assets`, `public/styles.css`, `public/script.js`: approved design assets and browser interactions.
- `storage/app/private/imported-articles`: private staging for the supplied legacy PDFs.
- `tests/Feature`, `tests/Unit`: automated workflow and security coverage.

## Important boundary

This project is a source application, not a managed hosting subscription or configured production deployment. The owner still chooses and pays for the host/domain and supplies protected production database, mail, storage and backup settings. Do not enable public indexing until the final domain, institutional content and permissions have been checked.
# Akwa-ibom-state-Uni-aksujet-
