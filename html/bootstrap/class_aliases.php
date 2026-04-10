<?php declare(strict_types=1);

$aliasClass = static function (string $from, string $to): void {
  if ((class_exists($to, false) || interface_exists($to, false)) && (class_exists($from) || interface_exists($from))) {
    return; // Already aliased or target already exists
  }
  
  if (class_exists($from) || interface_exists($from)) {
    class_alias($from, $to);
  }
};

$domainAliases = [
  'Database',
  'Sites',
  'WorkEntry',
  'Taxes',
  'TaxBracket',
  'TaxBracketCollection',
  'Money',
  'Earnings',
  'Work',
  'PayPeriods',
  'Security',
  'RequestPath',
  'ArrayPager',
  'Pager',
  'PagerInterface',
  'DataGrid',
  'Calendar',
  'InputSanitizer',
  'Strings',
  'SitesService',
  'Authentication',
  'ClientCapabilities',
  'CryptoVersions',
  'EncryptionConfig',
  'DateTimeImmutable',
  'DateTimeZone',
];

foreach ($domainAliases as $className) {
  // Force load the source class/interface first
  $sourceClass = "PayCal\\Domain\\{$className}";
  if (!class_exists($sourceClass) && !interface_exists($sourceClass)) {
    continue; // Skip if class/interface doesn't exist
  }
  
  $aliasClass($sourceClass, "PayCal\\{$className}");
  $aliasClass($sourceClass, $className);
}

// Refactored classes in new namespaces (v1.031.000+)
$aliasClass('PayCal\\Domain\\Config\\SystemConfig', 'PayCal\\Domain\\SystemConfig');
$aliasClass('PayCal\\Domain\\Config\\SystemConfig', 'PayCal\\SystemConfig');
$aliasClass('PayCal\\Domain\\Config\\SystemConfig', 'SystemConfig');
$aliasClass('PayCal\\Domain\\Config\\Environment', 'PayCal\\Domain\\Environment');
$aliasClass('PayCal\\Domain\\Config\\Environment', 'PayCal\\Environment');
$aliasClass('PayCal\\Domain\\Config\\Environment', 'Environment');
$aliasClass('PayCal\\Domain\\Constants\\Keys', 'PayCal\\Domain\\Keys');
$aliasClass('PayCal\\Domain\\Constants\\Keys', 'PayCal\\Keys');
$aliasClass('PayCal\\Domain\\Constants\\Keys', 'Keys');
$aliasClass('PayCal\\Domain\\Enums\\PayFrequency', 'PayCal\\Domain\\PayFrequency');
$aliasClass('PayCal\\Domain\\Enums\\PayFrequency', 'PayCal\\PayFrequency');
$aliasClass('PayCal\\Domain\\Enums\\PayFrequency', 'PayFrequency');
$aliasClass('PayCal\\Domain\\Enums\\AuthLevel', 'PayCal\\Domain\\AuthLevel');
$aliasClass('PayCal\\Domain\\Enums\\AuthLevel', 'PayCal\\AuthLevel');
$aliasClass('PayCal\\Domain\\Enums\\AuthLevel', 'AuthLevel');
$aliasClass('PayCal\\Domain\\Enums\\FormTTL', 'PayCal\\Domain\\FormTTL');
$aliasClass('PayCal\\Domain\\Enums\\FormTTL', 'PayCal\\FormTTL');
$aliasClass('PayCal\\Domain\\Enums\\FormTTL', 'FormTTL');
$aliasClass('PayCal\\Domain\\Enums\\HttpStatus', 'PayCal\\Domain\\HttpStatus');
$aliasClass('PayCal\\Domain\\Enums\\HttpStatus', 'PayCal\\HttpStatus');
$aliasClass('PayCal\\Domain\\Enums\\HttpStatus', 'HttpStatus');
$aliasClass('PayCal\\Domain\\Enums\\SiteStatus', 'PayCal\\Domain\\SiteStatus');
$aliasClass('PayCal\\Domain\\Enums\\SiteStatus', 'PayCal\\SiteStatus');
$aliasClass('PayCal\\Domain\\Enums\\SiteStatus', 'SiteStatus');
$aliasClass('PayCal\\Domain\\Enums\\SessionTimeout', 'PayCal\\Domain\\SessionTimeout');
$aliasClass('PayCal\\Domain\\Enums\\SessionTimeout', 'PayCal\\SessionTimeout');
$aliasClass('PayCal\\Domain\\Enums\\SessionTimeout', 'SessionTimeout');

$aliasClass('PayCal\\Domain\\Crypto\\ChainVerifier', 'PayCal\\Crypto\\ChainVerifier');
$aliasClass('PayCal\\Domain\\Encryption\\EnvelopeFormat', 'PayCal\\Encryption\\EnvelopeFormat');
$aliasClass('PayCal\\Domain\\Encryption\\EnvelopeFormat', 'EnvelopeFormat');
$aliasClass('PayCal\\Domain\\Encryption\\EncryptionConfig', 'PayCal\\Encryption\\EncryptionConfig');
$aliasClass('PayCal\\Domain\\Encryption\\EncryptionConfig', 'EncryptionConfig');
$aliasClass('PayCal\\Domain\\Encryption\\CryptoVersions', 'PayCal\\Encryption\\CryptoVersions');
$aliasClass('PayCal\\Domain\\Encryption\\CryptoVersions', 'CryptoVersions');
$aliasClass('PayCal\\Domain\\Encryption\\ClientCapabilities', 'PayCal\\Encryption\\ClientCapabilities');
$aliasClass('PayCal\\Domain\\Encryption\\ClientCapabilities', 'ClientCapabilities');
$aliasClass('PayCal\\Controllers\\KekController', 'PayCal\\KekController');
$aliasClass('PayCal\\Controllers\\KekController', 'KekController');

// Register global runtime fault handling as early as autoload bootstrap.
\PayCal\Domain\ShadowTalon::register();
