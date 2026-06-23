<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Database;
use PayCal\Domain\FederatedAuth;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract tests for local-only federated sign-in discovery.
 */
#[Group('unit')]
final class FederatedAuthFeatureGateTest extends TestCase
{
  /** @var array<string, string> */
  private array $originalEnv;

  protected function setUp(): void
  {
    parent::setUp();
    require_once __DIR__ . '/../../bootstrap/Classes.php';
    $this->originalEnv = $_ENV;
    Environment::bootstrap($this->envDefaults());
  }

  protected function tearDown(): void
  {
    Environment::bootstrap($this->originalEnv);
    parent::tearDown();
  }

  public function testDisabledFlagHidesAllProviders(): void
  {
    Environment::bootstrap($this->envDefaults([
      'PAYCAL_AUTH_FEDERATED_SIGNIN_ENABLED' => 'false',
      'PAYCAL_AUTH_PROVIDER_GOOGLE_ENABLED' => 'true',
      'PAYCAL_AUTH_GOOGLE_CLIENT_ID' => 'google-client-id',
    ]));

    $this->assertFalse(FederatedAuth::featureGatePassesForHost('dev.paycal.local'));
    $this->assertSame([], FederatedAuth::availableProvidersForHost('dev.paycal.local'));
  }

  public function testEnabledFlagStillBlockedOnProductionHost(): void
  {
    Environment::bootstrap($this->enabledGoogleEnv());

    $this->assertFalse(FederatedAuth::featureGatePassesForHost('paycal.app'));
    $this->assertSame([], FederatedAuth::availableProvidersForHost('paycal.app'));
  }

  public function testEnabledFlagStillBlockedOnRemoteDevHost(): void
  {
    Environment::bootstrap($this->enabledGoogleEnv());

    $this->assertFalse(FederatedAuth::featureGatePassesForHost('dev.paycal.app'));
    $this->assertSame([], FederatedAuth::availableProvidersForHost('dev.paycal.app'));
  }

  public function testEnabledFlagWorksOnDevPaycalLocal(): void
  {
    Environment::bootstrap($this->enabledGoogleEnv());

    $providers = FederatedAuth::availableProvidersForHost('dev.paycal.local');

    $this->assertTrue(FederatedAuth::featureGatePassesForHost('dev.paycal.local'));
    $this->assertCount(1, $providers);
    $this->assertSame('google', $providers[0]['id']);
    $this->assertTrue($providers[0]['supports_fedcm']);
    $this->assertTrue($providers[0]['fedcm_enabled']);
  }

  public function testProviderSpecificDisabledFlagHidesProvider(): void
  {
    Environment::bootstrap($this->enabledGoogleEnv([
      'PAYCAL_AUTH_PROVIDER_GOOGLE_ENABLED' => 'false',
    ]));

    $this->assertSame([], FederatedAuth::availableProvidersForHost('dev.paycal.local'));
  }

  public function testMissingClientIdHidesProvider(): void
  {
    Environment::bootstrap($this->enabledGoogleEnv([
      'PAYCAL_AUTH_GOOGLE_CLIENT_ID' => '',
    ]));

    $this->assertSame([], FederatedAuth::availableProvidersForHost('dev.paycal.local'));
  }

  public function testLocalGateUsesExactHostMatching(): void
  {
    Environment::bootstrap($this->enabledGoogleEnv());

    $this->assertTrue(FederatedAuth::localGatePassesForHost('dev.paycal.local'));
    $this->assertTrue(FederatedAuth::localGatePassesForHost('dev.paycal.local:443'));
    $this->assertTrue(FederatedAuth::localGatePassesForHost('https://dev.paycal.local/api/v1/auth/providers'));
    $this->assertTrue(FederatedAuth::localGatePassesForHost('localhost'));
    $this->assertTrue(FederatedAuth::localGatePassesForHost('127.0.0.1'));

    $this->assertFalse(FederatedAuth::localGatePassesForHost('paycal.app'));
    $this->assertFalse(FederatedAuth::localGatePassesForHost('www.paycal.app'));
    $this->assertFalse(FederatedAuth::localGatePassesForHost('dev.paycal.app'));
    $this->assertFalse(FederatedAuth::localGatePassesForHost('paycal.ca'));
    $this->assertFalse(FederatedAuth::localGatePassesForHost('paycaltech.com'));
    $this->assertFalse(FederatedAuth::localGatePassesForHost('paycaltechnologies.com'));
    $this->assertFalse(FederatedAuth::localGatePassesForHost('mac.paycal.local'));
    $this->assertFalse(FederatedAuth::localGatePassesForHost('lab.paycal.local'));
    $this->assertFalse(FederatedAuth::localGatePassesForHost('anything.dev.paycal.local'));
  }

  public function testFedcmIsCapabilityNotProvider(): void
  {
    Environment::bootstrap($this->enabledGoogleEnv());

    $registry = FederatedAuth::providerRegistry();
    $providers = FederatedAuth::availableProvidersForHost('dev.paycal.local');

    $this->assertArrayHasKey('google', $registry);
    $this->assertArrayHasKey('apple', $registry);
    $this->assertArrayHasKey('microsoft', $registry);
    $this->assertArrayNotHasKey('fedcm', $registry);
    $this->assertSame(['google'], array_column($providers, 'id'));
  }

  public function testOauthStateIsSingleUseAndRejectsMismatch(): void
  {
    Environment::bootstrap($this->enabledGoogleEnv());

    $state = FederatedAuth::createOAuthState('google', 'signin', '/');

    $mismatch = FederatedAuth::consumeOAuthState($state['state'], 'google', 'link');
    $this->assertSame([], $mismatch);

    $state2 = FederatedAuth::createOAuthState('google', 'signin', '/');
    $consumed = FederatedAuth::consumeOAuthState($state2['state'], 'google', 'signin');
    $this->assertSame('google', $consumed['provider']);
    $this->assertSame('signin', $consumed['mode']);
    $this->assertSame($state2['nonce'], $consumed['nonce']);

    $this->assertSame([], FederatedAuth::consumeOAuthState($state2['state'], 'google', 'signin'));
  }

  public function testProviderSubjectHashDoesNotExposeRawSubject(): void
  {
    $subject = 'google-subject-123';
    $hash = FederatedAuth::subjectHash('google', $subject);

    $this->assertNotSame($subject, $hash);
    $this->assertDoesNotMatchRegularExpression('/google-subject-123/', $hash);
    $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
  }

  public function testEmailMatchWithoutExistingLinkDoesNotResolveUser(): void
  {
    $claims = [
      'sub' => 'unlinked-subject',
      'email' => 'existing@example.test',
      'email_verified' => 'true',
    ];

    $this->assertSame('', FederatedAuth::resolveLinkedUserUUID('google', $claims));
  }

  public function testLinkedGoogleIdentityResolvesUserWithoutRawSubjectStorage(): void
  {
    $userUUID = 'test-fed-user-' . bin2hex(random_bytes(4));
    $claims = [
      'sub' => 'linked-subject-' . bin2hex(random_bytes(4)),
      'email' => 'linked@example.test',
      'email_verified' => 'true',
    ];

    try {
      FederatedAuth::linkProviderIdentity($userUUID, 'google', $claims);
      $this->assertSame($userUUID, FederatedAuth::resolveLinkedUserUUID('google', $claims));

      $linked = FederatedAuth::linkedProvider($userUUID, 'google');
      $this->assertSame('google', $linked['provider']);
      $this->assertSame('linked@example.test', $linked['email']);
      $this->assertArrayHasKey('subject_hash', $linked);
      $this->assertNotSame($claims['sub'], $linked['subject_hash']);
    } finally {
      FederatedAuth::unlinkProviderIdentity($userUUID, 'google');
    }
  }

  public function testGoogleIdTokenValidationRequiresIssuerAudienceAndNonce(): void
  {
    Environment::bootstrap($this->enabledGoogleEnv([
      'PAYCAL_AUTH_GOOGLE_CLIENT_ID' => 'paycal-google-client',
    ]));

    $key = openssl_pkey_new([
      'private_key_type' => OPENSSL_KEYTYPE_RSA,
      'private_key_bits' => 2048,
    ]);
    $this->assertNotFalse($key);

    $csr = openssl_csr_new(['commonName' => 'PayCal Test'], $key);
    $this->assertNotFalse($csr);
    $cert = openssl_csr_sign($csr, null, $key, 1);
    $this->assertNotFalse($cert);

    openssl_x509_export($cert, $certPem);
    $certBody = trim(str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"], '', $certPem));
    $jwks = ['keys' => [['kid' => 'test-kid', 'x5c' => [$certBody]]]];

    $jwt = $this->signedJwt($key, [
      'iss' => 'https://accounts.google.com',
      'aud' => 'paycal-google-client',
      'exp' => time() + 300,
      'iat' => time() - 10,
      'nonce' => 'nonce-123',
      'sub' => 'google-subject',
      'email' => 'person@example.test',
      'email_verified' => true,
    ]);

    $claims = FederatedAuth::validateGoogleIdToken($jwt, $jwks, 'nonce-123');
    $this->assertSame('google-subject', $claims['sub']);
    $this->assertSame('person@example.test', $claims['email']);

    $jwtWithBareIssuer = $this->signedJwt($key, [
      'iss' => 'accounts.google.com',
      'aud' => 'paycal-google-client',
      'exp' => time() + 300,
      'iat' => time() - 10,
      'nonce' => 'nonce-123',
      'sub' => 'google-subject',
      'email' => 'person@example.test',
      'email_verified' => true,
    ]);
    $bareIssuerClaims = FederatedAuth::validateGoogleIdToken($jwtWithBareIssuer, $jwks, 'nonce-123');
    $this->assertSame('google-subject', $bareIssuerClaims['sub']);

    $details = openssl_pkey_get_details($key);
    $this->assertIsArray($details);
    $this->assertIsArray($details['rsa'] ?? null);
    $rsaJwks = ['keys' => [[
      'kid' => 'test-kid',
      'kty' => 'RSA',
      'alg' => 'RS256',
      'use' => 'sig',
      'n' => $this->base64Url((string) $details['rsa']['n']),
      'e' => $this->base64Url((string) $details['rsa']['e']),
    ]]];
    $jwkClaims = FederatedAuth::validateGoogleIdToken($jwt, $rsaJwks, 'nonce-123');
    $this->assertSame('google-subject', $jwkClaims['sub']);

    $this->assertSame([], FederatedAuth::validateGoogleIdToken($jwt, $jwks, 'wrong-nonce'));
  }

  /**
   * @param array<string, string> $overrides
   * @return array<string, string>
   */
  private function enabledGoogleEnv(array $overrides = []): array
  {
    return $this->envDefaults(array_merge([
      'PAYCAL_AUTH_FEDERATED_SIGNIN_ENABLED' => 'true',
      'PAYCAL_AUTH_FEDERATED_SIGNIN_LOCAL_ONLY' => 'true',
      'PAYCAL_AUTH_PROVIDER_GOOGLE_ENABLED' => 'true',
      'PAYCAL_AUTH_PROVIDER_APPLE_ENABLED' => 'false',
      'PAYCAL_AUTH_PROVIDER_MICROSOFT_ENABLED' => 'false',
      'PAYCAL_AUTH_PROVIDER_FEDCM_ENABLED' => 'true',
      'PAYCAL_AUTH_GOOGLE_CLIENT_ID' => 'google-client-id',
      'PAYCAL_AUTH_GOOGLE_CLIENT_SECRET' => 'google-client-secret',
    ], $overrides));
  }

  /**
   * @param array<string, string> $overrides
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'mac',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'localhost',
      'APP_HOME' => '/private/var/www/paycal/dev/html/',
      'API_VERSION' => 'v1',
      'REDIS_SERVER' => 'localhost',
      'REDIS_PORT' => '6379',
      'REDIS_READ_PORT' => '6379',
      'REDIS_WRITE_PORT' => '6379',
      'REDIS_DB' => '0',
      'REDIS_USER' => '',
      'REDIS_PASSWORD' => '',
      'REDIS_NEW_SESSION_TTL' => '3600',
      'PC_EMAIL_SMTP_SERVER' => 'localhost',
      'PC_EMAIL_SMTP_PORT' => '25',
      'PC_EMAIL_CONTACT' => 'noreply@example.com',
      'PC_EMAIL_DEBUG' => 'debug@example.com',
      'PC_EMAIL_REPLYTO' => 'reply@example.com',
      'PC_EMAIL_PASSWORD' => 'x',
      'PC_INVITE_CODE' => 'invite',
      'PAYROLL_SIGNING_PRIVATE_KEY' => '',
      'PAYROLL_SIGNING_PUBLIC_KEY' => '',
      'DEV_ALLOW_INLINE_SCRIPTS' => 'true',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'false',
      'PAYCAL_AUTH_FEDERATED_SIGNIN_ENABLED' => 'false',
      'PAYCAL_AUTH_FEDERATED_SIGNIN_LOCAL_ONLY' => 'true',
      'PAYCAL_AUTH_PROVIDER_GOOGLE_ENABLED' => 'false',
      'PAYCAL_AUTH_PROVIDER_APPLE_ENABLED' => 'false',
      'PAYCAL_AUTH_PROVIDER_MICROSOFT_ENABLED' => 'false',
      'PAYCAL_AUTH_PROVIDER_FEDCM_ENABLED' => 'false',
      'PAYCAL_AUTH_FEDERATED_AUTO_CREATE_LOCAL' => 'false',
      'PAYCAL_AUTH_GOOGLE_CLIENT_ID' => '',
      'PAYCAL_AUTH_GOOGLE_CLIENT_SECRET' => '',
    ];

    return array_merge($defaults, $overrides);
  }

  /**
   * @param \OpenSSLAsymmetricKey $key
   * @param array<string, mixed> $payload
   */
  private function signedJwt(\OpenSSLAsymmetricKey $key, array $payload): string
  {
    $header = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'test-kid'];
    $segments = [
      $this->base64Url(json_encode($header, JSON_UNESCAPED_SLASHES) ?: '{}'),
      $this->base64Url(json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}'),
    ];
    $signingInput = implode('.', $segments);
    openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);
    $segments[] = $this->base64Url($signature);
    return implode('.', $segments);
  }

  private function base64Url(string $input): string
  {
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
  }
}
