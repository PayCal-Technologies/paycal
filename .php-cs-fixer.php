<?php

/**
 * Conservative PHP CS Fixer config for the public PayCal checkout.
 *
 * The historical formatter workflow expects this file to exist, but the public
 * tree currently contains mixed legacy formatting. Keep this config non-mutating
 * until a dedicated formatting migration is made intentionally.
 */

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
  ->in(__DIR__ . '/html/src')
  ->in(__DIR__ . '/html/tests')
  ->name('*.php');

return (new PhpCsFixer\Config())
  ->setIndent('  ')
  ->setLineEnding("\n")
  ->setRiskyAllowed(false)
  ->setRules([])
  ->setFinder($finder);
