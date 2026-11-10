# Aksujet SEO sources and launch notes

The public archive uses server-rendered HTML, stable canonical article URLs, article-level metadata, Scholar citation tags, `ScholarlyArticle` JSON-LD, a public `robots.txt`, an XML sitemap, and a directly crawlable published-PDF endpoint. These help search engines discover and understand pages; they do **not** guarantee indexing or ranking.

## Official Google references reviewed

Reviewed 31 October 2026 while preparing the prior Aksujet indexing setup.

- [Build and submit a sitemap](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap) — a sitemap can help Google discover canonical public URLs; submit it through Search Console after the site is live.
- [Ask Google to recrawl a page](https://developers.google.com/search/docs/crawling-indexing/ask-google-to-recrawl) — URL Inspection may be used after deployment, but recrawl requests do not guarantee inclusion or a particular indexing time.
- [Introduction to robots.txt](https://developers.google.com/search/docs/crawling-indexing/robots/intro) — robots rules control crawling, not authorization; private author/editor/reviewer content must stay protected by login and server-side authorization.
- [Inspect a URL in Search Console](https://support.google.com/webmasters/answer/9012289?hl=en) — use URL Inspection on the real HTTPS domain after DNS, TLS, and Google Search Console verification are complete.
- [Google Search indexable file types](https://developers.google.com/search/docs/crawling-indexing/indexable-file-types) — PDF is a supported file type; a PDF must be publicly fetchable and not blocked by robots or access controls to be considered for crawling.

## Before asking Google to index the live site

1. Set `APP_URL` to the final canonical HTTPS domain and configure the hosting proxy only with its trusted proxy addresses.
2. Deploy a valid TLS certificate and make the chosen host consistent (including whether `www` is canonical).
3. In Google Search Console, verify the domain or URL-prefix property, inspect the homepage, an article page and a published PDF, then submit `/sitemap.xml`.
4. Keep author, editor and reviewer pages authenticated and `noindex`; `robots.txt` is not a security barrier.
5. Recheck sitemap and robots responses on the real public domain. Preview and localhost URLs are not indexing targets.

The project has no production domain or Search Console property configured yet, so Google indexing cannot be requested from this build environment.
