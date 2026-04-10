# Blog Routing Action Plan

## Goal
Isolate and productionize blog route handling without impacting core app routes.

## Current State
- Route snippet is documented in [docs/nginx/paycal-blog-route.conf](docs/nginx/paycal-blog-route.conf).
- Current rule:

```nginx
location = /blog {
  return 301 /blog/;
}

location = /blog/ {
  include snippets/fastcgi-php.conf;
  fastcgi_pass unix:/run/php/php8.4-fpm-paycal_dev.sock;
  fastcgi_param SCRIPT_FILENAME $document_root/blog/index.php;
  fastcgi_param SCRIPT_NAME /blog/index.php;
}

location /blog/ {
  try_files $uri @blog_router;
}

location @blog_router {
  include snippets/fastcgi-php.conf;
  fastcgi_pass unix:/run/php/php8.4-fpm-paycal_dev.sock;
  fastcgi_param SCRIPT_FILENAME $document_root/blog/index.php;
  fastcgi_param SCRIPT_NAME /blog/index.php;
}
```

## Plan
1. Validate server block ordering
- Ensure blog location is above generic location / and any broad PHP matcher.

2. Add canonical slash handling
- Add redirect for /blog -> /blog/ to avoid duplicate content and mismatched relative paths.

3. Tighten static asset passthrough
- Ensure /blog/*.css, /blog/*.js, images, and feed assets resolve directly when present.

4. Confirm PHP front controller handoff
- Keep fallback to /blog/index.php?$query_string for non-file pretty URLs.

5. Add localized route policy decision
- Decide whether /fr/blog/... is supported.
- If yes, add explicit language-prefix rewrite rules.
- If no, return canonical redirect to /blog/... (or 404 by policy).

6. Add route tests
- Add curl or Playwright smoke checks for:
  - /blog/
  - /blog/post-slug
  - /blog/category/news
  - /blog (redirect)
  - missing post returns expected 404 page

7. Add rollout checklist
- Validate nginx -t
- Reload service
- Verify app and blog routes coexist
- Capture before/after response matrix

## Success Criteria
- Pretty URLs resolve via /blog/index.php fallback.
- Static assets under /blog/ are not routed to PHP unnecessarily.
- Canonical behavior for /blog and optional localized prefixes is explicit and tested.
