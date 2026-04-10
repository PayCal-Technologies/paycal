<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

.blog_page {
  width: min(74rem, calc(100% - 2.5rem));
  margin: 2rem auto;
  padding: 1.5rem;
  border: 1px solid var(--panel-border);
  background: var(--panel-bg);
}

.blog_header {
  border-bottom: 1px solid var(--panel-border);
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
}

.blog_header h1 {
  margin: 0.5rem 0;
  color: var(--color-primary);
}

.blog_breadcrumbs {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  font-size: 0.9rem;
  color: var(--text-muted);
}

.blog_breadcrumbs a {
  text-decoration: none;
}

.blog_deck {
  margin: 0;
  color: var(--text-muted);
}

.blog_search {
  margin: 1rem 0;
}

.blog_search_form {
  display: flex;
  gap: 0.6rem;
  align-items: center;
  flex-wrap: wrap;
}

.blog_search_form label {
  font-weight: 600;
}

.blog_search_form input[type='search'] {
  flex: 1 1 20rem;
  min-width: 14rem;
  padding: 0.6rem 0.7rem;
  border: 1px solid var(--panel-border);
  background: var(--bg-color);
  color: var(--text-color);
}

.blog_search_form button,
.blog_reset {
  padding: 0.6rem 0.8rem;
  border: 1px solid var(--panel-border);
  background: var(--panel-bg-elevated, var(--panel-bg));
  text-decoration: none;
  color: inherit;
}

.blog_tags {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin: 1rem 0 1.5rem;
}

.blog_tag {
  display: inline-block;
  padding: 0.35rem 0.55rem;
  border: 1px solid var(--panel-border);
  text-decoration: none;
  font-size: 0.85rem;
}

.blog_tag.active {
  background: color-mix(in srgb, var(--color-primary) 14%, var(--panel-bg));
  border-color: var(--color-primary);
}

.blog_results_count {
  margin: 0 0 1rem;
  color: var(--text-muted);
}

.blog_card {
  border-top: 1px solid var(--panel-border);
  padding: 1rem 0;
}

.blog_card h2 {
  margin: 0;
  font-size: 1.35rem;
}

.blog_meta {
  margin: 0.4rem 0;
  color: var(--text-muted);
  font-size: 0.9rem;
}

.blog_snippet {
  margin: 0.4rem 0 0.7rem;
}

.blog_tag_list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  list-style: none;
  margin: 0;
  padding: 0;
}

.blog_tag_list li a {
  display: inline-block;
  border: 1px solid var(--panel-border);
  text-decoration: none;
  padding: 0.2rem 0.5rem;
  font-size: 0.8rem;
}

.blog_pagination,
.blog_article_nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.7rem;
  border-top: 1px solid var(--panel-border);
  margin-top: 1rem;
  padding-top: 1rem;
}

.blog_pagination .disabled,
.blog_article_nav .disabled {
  opacity: 0.6;
}

.blog_page_indicator {
  color: var(--text-muted);
}

.blog_article .blog_content {
  line-height: 1.75;
}

.blog_article .blog_content h1,
.blog_article .blog_content h2,
.blog_article .blog_content h3 {
  margin: 1.5rem 0 0.7rem;
}

.blog_article .blog_content p,
.blog_article .blog_content ul,
.blog_article .blog_content ol {
  margin: 0.6rem 0;
}

.blog_article .blog_content a.external_link {
  text-decoration: underline;
  text-decoration-thickness: 0.09em;
  text-underline-offset: 0.14em;
}

.blog_article .blog_content a.external_link::after {
  content: " |->";
  color: var(--color-primary);
}

.blog_article .blog_content code {
  background: color-mix(in srgb, var(--panel-border) 45%, transparent);
  padding: 0.05rem 0.3rem;
}

.blog_article_nav a {
  display: inline-flex;
  flex-direction: column;
  text-decoration: none;
}

.blog_article_nav .blog_list_link {
  flex-direction: row;
  border: 1px solid var(--panel-border);
  padding: 0.4rem 0.8rem;
}

@media (max-width: 768px) {
  .blog_page {
    width: calc(100% - 1.2rem);
    margin: 0.6rem auto;
    padding: 1rem;
  }

  .blog_pagination,
  .blog_article_nav {
    flex-direction: column;
    align-items: stretch;
  }

  .blog_page_indicator {
    text-align: center;
  }
}
