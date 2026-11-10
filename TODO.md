# Aksujet outcomes

## 1. Laravel and MySQL foundation — Complete
- The backend is PHP and Laravel, with MySQL/MariaDB as the production database target.
- Keep this application separate from the static client-presentation site; deliver a project that can be configured for the owner's PHP hosting account.
- Do not deploy or publish the application as part of the project scaffold.
- Delivery evidence: Laravel 13 source, MySQL-oriented migrations, SQLite-backed automated tests, `.env.example`, setup guide, and source ZIP are prepared. No production deployment was made.

## 2. Secure accounts and role-gated access — Complete in code; owner must set the private environment value
- Editorial staff must log in before creating, editing or publishing journal content; the public must not have upload access.
- Authors need accounts to submit manuscripts and track progress.
- Editors need access to editorial management and decisions.
- Reviewers should only access manuscripts assigned to them and their own review submissions.
- Keep manuscript and review files private; only approved publication files may be public.
- The editorial studio is restricted server-side to the configured email allowlist. The intended owner address is `justiceuyompcs@gmail.com`; the host must set `AKSUJET_EDITORIAL_ALLOWLIST=justiceuyompcs@gmail.com` privately in `.env`. An empty allowlist fails closed.
- Delivery evidence: owner-only route checks, registration role provisioning, reviewer assignment checks and private-file access are covered by feature tests.

## 3. Online manuscript submission and tracking — Complete
- Provide online manuscript submission and tracking.
- Keep submission records separate from published articles, with a reference, authors, revisions, files and status history.
- Delivery evidence: the author-to-publication feature test submits a manuscript, confirms its status history, checks author ownership and verifies that another author cannot access it.

## 4. Editorial processing and peer review — Complete
- Provide efficient editorial management and manuscript processing.
- Provide peer-review management and communication with reviewers.
- Support editorial decision-making and correspondence with authors.
- Delivery evidence: the lifecycle feature test screens a submission, assigns a reviewer, enforces assignment-scoped file access, records review comments and an editorial decision letter, and displays the decision to the author.

## 5. Publication and archive — Import workflow complete; initial archive backfill remains
- Provide publication of articles and management of journal issues.
- Provide proper article archiving and accessibility.
- Do not automatically publish imported PDFs; an editor must approve and publish a final article.
- Delivery evidence: journal and issue creation, article draft import, publication checks, public article pages, PDF streaming and sitemap inclusion are implemented and covered by feature tests. All nine supplied PDFs are privately staged and listed in the editor-only import desk. They have not yet been imported as article records or made public; the owner/editor must verify each PDF's citation metadata, select the correct issue and explicitly release the article.

## 6. Security, stability, presentation and discoverability — Implementation complete
- Provide improved website security, stability and reliability.
- Provide a professional presentation of the journal and its publications.
- Improve search engine visibility and accessibility of published research.
- Support the journal's future growth and publishing requirements.
- Delivery evidence: server-rendered public pages, article citation metadata, Open Graph/Twitter tags, canonical URLs, structured data, dynamic robots and sitemap, noindex on private/error pages, security headers, private file routes and the approved interactive Aksujet visual system are in place. Raw HTTP checks confirmed the homepage, robots, sitemap and a real 404 response. Actual Google indexing cannot be confirmed before a production domain is reachable.

## 7. Hosting and production configuration — Open until the owner selects a host/domain
- Keep MySQL credentials, mail configuration, production administrator identity, domain and storage settings out of source control.
- Configure final canonical URLs, sitemap, Search Console verification and production backup procedures only after the owner confirms the hosting account and public domain.
- The README and `.env.example` describe independent hosting, the private owner allowlist, HTTPS, mail, database, file-storage and backup requirements. The production host, domain, credentials and mail service have not been supplied, so they are not configured here.
