<?php declare(strict_types=1);

return [
  'id' => 'admin-surface',
  'name' => 'Admin Surface (Public Override)',
  'version' => '1.0.0-public',
  'description' => 'Enables the Core admin surface for public deployments.',
  'author' => 'PayCal Technologies',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'admin.surface.enabled' => true,
    'admin.page.paths' => [
      '/admin/',
      '/admin/metrics/',
      '/admin/language-editor/',
      '/admin/stripe/',
      '/admin/redis/',
      '/admin/ast/',
      '/admin/documentation/',
      '/admin/tax-brackets/',
    ],
    'admin.nav.links' => [
      ['href' => '/admin/', 'label_key' => 'ADMIN', 'icon' => 'admin', 'match_prefix' => '/admin'],
      ['href' => '/admin/metrics/', 'label_key' => 'METRICS', 'icon' => 'metrics', 'match_prefix' => '/admin/metrics'],
      ['href' => '/admin/language-editor/?v=' . bin2hex(random_bytes(4)), 'label_key' => 'ADMIN_DASHBOARD_LANGUAGES', 'icon' => 'settings', 'match_prefix' => '/admin/language-editor'],
      ['href' => '/admin/stripe/', 'label_key' => 'STRIPE', 'icon' => 'metrics', 'match_prefix' => '/admin/stripe'],
      ['href' => '/admin/redis/', 'label_key' => 'REDIS', 'icon' => 'redis', 'match_prefix' => '/admin/redis'],
      ['href' => '/admin/ast/', 'label_key' => 'AST', 'icon' => 'ast', 'match_prefix' => '/admin/ast'],
      ['href' => '/tests/', 'label_key' => 'TESTS', 'icon' => 'tests', 'match_prefix' => '/tests'],
    ],
  ],
  'hooks' => [],
  'bootstrap' => 'bootstrap.php',
];
