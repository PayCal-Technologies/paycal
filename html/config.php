<?php declare(strict_types=1);

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Extensions\Bridges\ExtensionBootstrapBridge;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = \PayCal\Infrastructure\Env\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(['PC_EMAIL_PASSWORD']);

Environment::bootstrap($_ENV);

// Initialize extension runtime for public/basic + private override packages.
ExtensionBootstrapBridge::initialize();

if (in_array(Environment::appEnv(), ['mac', 'dev'], true) && 'cli' !== PHP_SAPI) {
	// Keep dev behavior deterministic while files change frequently.
	// OPcache timestamps + zero revalidate_freq handles all file watching needs.
	@ini_set('opcache.validate_timestamps', '1');
	@ini_set('opcache.revalidate_freq', '0');
	// Do NOT disable realpath_cache or call clearstatcache(true) per request.
	// Each call forces expensive filesystem lookups on every autoload and include.
	// OPcache already watches for changes; these only destroy performance on macOS.
}

// -----------------------------------------------------------------------------
// Legacy constant compatibility shim
// -----------------------------------------------------------------------------
// Several older classes still reference historical global constants.
// Define safe fallbacks here so runtime does not fatal while those call sites
// are incrementally migrated to typed class constants.

if (!defined('HTML')) {
	define('HTML', rtrim(Environment::appHome(), '/') . '/html');
}

if (!defined('D_WORK')) {
	define('D_WORK', \PayCal\Domain\Constants\Keys::WORK);
}

if (!defined('SITE')) {
	define('SITE', \PayCal\Domain\Constants\Keys::SITE);
}

if (!defined('USER_UUID')) {
	define('USER_UUID', (string) (\PayCal\Domain\User::currentUUID() ?? ''));
}

if (!defined('USER_LANGUAGE')) {
	define('USER_LANGUAGE', (string) (\PayCal\Domain\User::current()?->language ?? 'en'));
}

if (!defined('CSP_NONCE')) {
	$existingNonce = $_SERVER['CSP_NONCE'] ?? '';
	if ((string) $existingNonce === '' && PHP_SAPI !== 'cli') {
		try {
			$_SERVER['CSP_NONCE'] = \PayCal\Domain\User::nonce();
		} catch (\Throwable) {
			$_SERVER['CSP_NONCE'] = '';
		}
	}

	define('CSP_NONCE', (string) ($_SERVER['CSP_NONCE'] ?? ''));
}

if (!defined('PC_CLI_MODE')) {
	define('PC_CLI_MODE', 'cli' === PHP_SAPI);
}

if (!defined('SIGNING_KEY_VERSION')) {
	define('SIGNING_KEY_VERSION', (int) ($_ENV['SIGNING_KEY_VERSION'] ?? 1));
}

if (!defined('ENCRYPTION_MIN_SUCCESS_RATE')) {
	define('ENCRYPTION_MIN_SUCCESS_RATE', (float) ($_ENV['ENCRYPTION_MIN_SUCCESS_RATE'] ?? 0.95));
}

if (!defined('HTTP_OK')) {
	define('HTTP_OK', \PayCal\Domain\Enums\HttpStatus::HTTP_OK);
}
if (!defined('HTTP_CREATED')) {
	define('HTTP_CREATED', \PayCal\Domain\Enums\HttpStatus::HTTP_CREATED);
}
if (!defined('HTTP_BAD_REQUEST')) {
	define('HTTP_BAD_REQUEST', \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);
}
if (!defined('HTTP_UNAUTHORIZED')) {
	define('HTTP_UNAUTHORIZED', \PayCal\Domain\Enums\HttpStatus::HTTP_UNAUTHORIZED);
}
if (!defined('HTTP_FORBIDDEN')) {
	define('HTTP_FORBIDDEN', \PayCal\Domain\Enums\HttpStatus::HTTP_FORBIDDEN);
}
if (!defined('HTTP_NOT_FOUND')) {
	define('HTTP_NOT_FOUND', \PayCal\Domain\Enums\HttpStatus::HTTP_NOT_FOUND);
}
if (!defined('HTTP_METHOD_NOT_ALLOWED')) {
	define('HTTP_METHOD_NOT_ALLOWED', \PayCal\Domain\Enums\HttpStatus::HTTP_METHOD_NOT_ALLOWED);
}
if (!defined('HTTP_UNPROCESSABLE')) {
	define('HTTP_UNPROCESSABLE', \PayCal\Domain\Enums\HttpStatus::HTTP_UNPROCESSABLE);
}
if (!defined('HTTP_INTERNAL_SERVER_ERROR')) {
	define('HTTP_INTERNAL_SERVER_ERROR', \PayCal\Domain\Enums\HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
}

// Email and application configuration constants from environment
if (!defined('PC_NAME')) {
	define('PC_NAME', (string) ($_ENV['PC_NAME'] ?? 'PayCal'));
}

if (!defined('PC_EMAIL_SMTP_SERVER')) {
	define('PC_EMAIL_SMTP_SERVER', (string) ($_ENV['PC_EMAIL_SMTP_SERVER'] ?? 'smtp.titan.email'));
}

if (!defined('PC_EMAIL_SMTP_PORT')) {
	define('PC_EMAIL_SMTP_PORT', (int) ($_ENV['PC_EMAIL_SMTP_PORT'] ?? 587));
}

if (!defined('PC_EMAIL_REPLYTO')) {
	define('PC_EMAIL_REPLYTO', (string) ($_ENV['PC_EMAIL_REPLYTO'] ?? 'info@paycal.app'));
}

if (!defined('PC_EMAIL_PASSWORD')) {
	define('PC_EMAIL_PASSWORD', (string) ($_ENV['PC_EMAIL_PASSWORD'] ?? ''));
}

if (!defined('PC_EMAIL_DEBUG')) {
	define('PC_EMAIL_DEBUG', (string) ($_ENV['PC_EMAIL_DEBUG'] ?? ''));
}

if (!defined('PC_EMAIL_CONTACT')) {
	define('PC_EMAIL_CONTACT', (string) ($_ENV['PC_EMAIL_CONTACT'] ?? 'info@paycal.app'));
}

if (!defined('PC_REGISTRATION_CODE')) {
	define('PC_REGISTRATION_CODE', (string) ($_ENV['PC_REGISTRATION_CODE'] ?? ''));
}

if (!defined('MIN_CHARACTER_LENGTH')) {
	define('MIN_CHARACTER_LENGTH', (int) ($_ENV['MIN_CHARACTER_LENGTH'] ?? 3));
}


/*
+-------------------+----------------------+---------------------------+-----------------------------+
| If it is...       | Use This             | Example                   | Notes                       |
+-------------------+----------------------+---------------------------+-----------------------------+
| Server / deploy   | Environment          | Environment::redisPort()  | Comes from .env             |
| DB connection     | Environment          | redisServer(), redisDb()  | Infra only                  |
| UI default        | AppConfig            | AppConfig::TEXT_BASE      | Static app defaults         |
| Redis prefix      | Keys                 | Keys::USER                | Prefix only, no data        |
| User runtime      | User / Session       | User::uuid()              | Never constant              |
| Visible text      | i18n                 | i18n("earnings.title")    | Surface layer only          |
| Rendering         | Render               | Render::template(...)     | No business logic           |
| Request input     | Controller / Input   | InputSanitizer::...       | Validate early              |
+-------------------+----------------------+---------------------------+-----------------------------+
*/
