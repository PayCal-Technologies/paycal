<?php
/**
 * calendar/index.php
 *
 * Purpose: Provide a stable `/calendar/` route that renders the same
 * calendar shell as the root (`/`) endpoint.
 *
 * Why this exists:
 * - Client-side month navigation and external links may target
 *   `/calendar/?month=YYYY-MM`.
 * - Without this file, nginx returns 404 because no calendar directory route exists.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/index.php';
