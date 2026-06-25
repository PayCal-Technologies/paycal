<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

/**
 * Resolve the WebAuthn relying-party ID used for ceremony verification.
 *
 * Prefers the live request host so local dev can browse mac.paycal.app even when
 * APP_DOMAIN still points at another hostname. Local dev hosts keep their full
 * hostname as rpId so production passkeys are not offered against local Redis.
 */
final class WebAuthnRpId
{
  /** @var array<int, string> */
  private const LOCAL_DEV_RP_ID_HOSTS = [
    'dev.paycal.local',
    'mac.paycal.app',
    'localhost',
    '127.0.0.1',
  ];

  public static function resolve(): string
  {
    $override = Environment::webauthnRpId();
    if ($override !== '') {
      return FederatedAuth::normalizeHost($override);
    }

    $host = self::effectiveHost();
    if ($host === '') {
      return 'localhost';
    }

    if (in_array($host, self::LOCAL_DEV_RP_ID_HOSTS, true)) {
      return $host;
    }

    if (str_ends_with($host, 'paycal.app')) {
      return 'paycal.app';
    }

    return $host;
  }

  /** @return array<int, string> */
  public static function localDevRpIdHosts(): array
  {
    return self::LOCAL_DEV_RP_ID_HOSTS;
  }

  private static function effectiveHost(): string
  {
    $requestHost = FederatedAuth::requestHost();
    if ($requestHost !== '') {
      return $requestHost;
    }

    $configuredHost = parse_url(Environment::appPublicURL(), PHP_URL_HOST);
    if (!is_string($configuredHost) || $configuredHost === '') {
      return '';
    }

    return FederatedAuth::normalizeHost($configuredHost);
  }
}
