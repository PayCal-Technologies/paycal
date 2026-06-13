<?php declare(strict_types=1);

return [
  'id' => 'business-surface',
  'name' => 'Business Surface (Public Extension)',
  'version' => '1.0.0-public',
  'description' => 'Enables a minimal business workspace IA shell for public deployments.',
  'author' => 'PayCal Technologies',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'business.surface.enabled' => true,
    'business.page.paths' => [
      '/business/',
      '/business/details/',
      '/business/members/',
      '/business/sites/',
      '/business/payroll/',
      '/business/reports/',
      '/business/audit/',
      '/business/compliance/',
      '/business/governance/',
      '/business/organization/',
    ],
    'business.nav.tabs' => [
      ['page' => 'PAGE_BUSINESS_DASHBOARD', 'href' => '/business/', 'label_key' => 'BUSINESS_NAV_DASHBOARD'],
      ['page' => 'PAGE_BUSINESS_DETAILS', 'href' => '/business/details/', 'label_key' => 'BUSINESS_NAV_DETAILS'],
      ['page' => 'PAGE_BUSINESS_MEMBERS', 'href' => '/business/members/', 'label_key' => 'BUSINESS_NAV_MEMBERS'],
      ['page' => 'PAGE_BUSINESS_SITES', 'href' => '/business/sites/', 'label_key' => 'BUSINESS_NAV_SITES'],
      ['page' => 'PAGE_BUSINESS_PAYROLL', 'href' => '/business/payroll/', 'label_key' => 'BUSINESS_NAV_PAYROLL'],
      ['page' => 'PAGE_BUSINESS_REPORTS', 'href' => '/business/reports/', 'label_key' => 'BUSINESS_NAV_REPORTS'],
      ['page' => 'PAGE_BUSINESS_AUDIT', 'href' => '/business/audit/', 'label_key' => 'BUSINESS_NAV_AUDIT'],
    ],
  ],
  'hooks' => [],
  'bootstrap' => 'bootstrap.php',
];
