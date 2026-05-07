<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Browser.php
 *
 * Purpose: Browser and device introspection helper for remote IP, user agent,
 * platform, and browser-version detection.
 *
 * Developer notes:
 * - Detection heuristics should remain conservative because downstream logging
 *   and UX labels may rely on these normalized values.
 * - Keep this focused on client-environment parsing rather than request-policy
 *   enforcement.
 *
 * Architectural role:
 * - Reusable domain helper for browser and device detection consumed by
 *   security, activity, and presentation flows.
 * - Encapsulates client-environment parsing outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */



/**
 * Class used to retrieve remote browser information, including IP address,
 * operating system, browser name and version, and device information.
 */
class Browser
{
  /**
   * Get client IP address from various source headers.
   * Checks multiple possible headers (X-Forwarded-For, Client-IP, etc.) for IP address.
   * Sanitizes the resulting IP before returning.
   *
   * @return string Client IP address or empty string if unable to determine
   */
  public static function getIp(): string
  {
    $ipAddress = '';
    $possibleHeaders = [
        'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR',
    ];
    foreach ($possibleHeaders as $header) {
      if (array_key_exists($header, $_SERVER)) {
        $env = getenv($header);
        if (is_string($env) && '' !== $env) {
          $ipAddress = $env;

          break;
        }
      }
    }

    return InputSanitizer::sanitizeIPAddress($ipAddress);
  }

  /**
   * Detect client operating system from User-Agent string.
   * Parses User-Agent and matches against known OS patterns.
   *
   * @return string Detected OS name (e.g., "Windows 10", "Mac OS X", "Linux") or "Unknown OS Platform"
   */
  public static function getOs(): string
  {
    $userAgent = self::getUserAgent();
    $osPlatform = 'Unknown OS Platform';
    $osArray = [
        '/windows nt 10/i' => 'Windows 10',
        '/windows nt 6.3/i' => 'Windows 8.1',
        '/windows nt 6.2/i' => 'Windows 8',
        '/windows nt 6.1/i' => 'Windows 7',
        '/windows nt 6.0/i' => 'Windows Vista',
        '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i' => 'Windows XP',
        '/windows xp/i' => 'Windows XP',
        '/windows nt 5.0/i' => 'Windows 2000',
        '/windows me/i' => 'Windows ME',
        '/win98/i' => 'Windows 98',
        '/win95/i' => 'Windows 95',
        '/win16/i' => 'Windows 3.11',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i' => 'Mac OS 9',
        '/linux/i' => 'Linux',
        '/ubuntu/i' => 'Ubuntu',
        '/iphone/i' => 'iPhone',
        '/ipod/i' => 'iPod',
        '/ipad/i' => 'iPad',
        '/android/i' => 'Android',
        '/blackberry/i' => 'BlackBerry',
        '/webos/i' => 'Mobile',
    ];
    foreach ($osArray as $regex => $value) {
      if (preg_match($regex, $userAgent)) {
        $osPlatform = $value;
      }
    }

    return $osPlatform;
  }

  /**
   * Detect client web browser from User-Agent string.
   * Matches User-Agent against known browser patterns.
   *
   * @return string Detected browser name (e.g., "Chrome", "Firefox", "Safari") or "Unknown Browser"
   */
  public static function getBrowser(): string
  {
    $userAgent = self::getUserAgent();
    $browser = 'Unknown Browser';
    $browserArray = [
        '/msie/i' => 'Internet Explorer',
        '/Trident/i' => 'Internet Explorer',
        '/firefox/i' => 'Firefox',
        '/safari/i' => 'Safari',
        '/chrome/i' => 'Chrome',
        '/edge/i' => 'Edge',
        '/opera/i' => 'Opera',
        '/netscape/i' => 'Netscape',
        '/maxthon/i' => 'Maxthon',
        '/konqueror/i' => 'Konqueror',
        '/ubrowser/i' => 'UC Browser',
        '/mobile/i' => 'Handheld Browser',
    ];
    foreach ($browserArray as $regex => $value) {
      if (preg_match($regex, $userAgent)) {
        $browser = $value;
      }
    }

    return $browser;
  }

  /**
   * Detect client device type from User-Agent string.
   * Identifies mobile, tablet, or desktop devices.
   *
   * @return string Device type: "Mobile", "Tablet", or "Desktop"
   */
  public static function getDevice(): string
  {
    $tabletBrowser = 0;
    $mobileBrowser = 0;

    /** Declare and cast explicitly to adhere to phpstan 6+ strict rules */
    $userAgent = is_string($_SERVER['HTTP_USER_AGENT'] ?? null)
      ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
    $profile = is_string($_SERVER['HTTP_PROFILE'] ?? null)
      ? strtolower($_SERVER['HTTP_PROFILE']) : '';
    $xWAPProfile = is_string($_SERVER['HTTP_X_WAP_PROFILE'] ?? null)
      ? strtolower($_SERVER['HTTP_X_WAP_PROFILE']) : '';
    $hTTPAccept = is_string($_SERVER['HTTP_ACCEPT'] ?? null)
      ? strtolower($_SERVER['HTTP_ACCEPT']) : '';
    $operaMiniPhoneUA = is_string($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'] ?? null)
      ? strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) : '';
    $deviceStockUA = is_string($_SERVER['HTTP_DEVICE_STOCK_UA'] ?? null)
      ? strtolower($_SERVER['HTTP_DEVICE_STOCK_UA']) : '';

    $tabletRegex = '/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i';
    $mobileRegex = '/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i';

    if (preg_match($tabletRegex, $userAgent)) {
      ++$tabletBrowser;
    }
    if (preg_match($mobileRegex, $userAgent)) {
      ++$mobileBrowser;
    }

    if (str_contains($hTTPAccept, 'application/vnd.wap.xhtml+xml')
      || '' !== $profile
      || '' !== $xWAPProfile) {
      ++$mobileBrowser;
    }

    $mobileUserAgent = substr($userAgent, 0, 4);
    $mobileAgents = [
        'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
        'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
        'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
        'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
        'newt', 'noki', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox', 'qwap',
        'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-',
        'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-', 'tosh',
        'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp', 'wapr',
        'webc', 'winw', 'xda ', 'xda-',
    ];

    if (in_array($mobileUserAgent, $mobileAgents, true)) {
      ++$mobileBrowser;
    }

    if (str_contains($userAgent, 'opera mini')) {
      ++$mobileBrowser;
      $stockUserAgent = '' !== $operaMiniPhoneUA ? $operaMiniPhoneUA : $deviceStockUA;
      if (preg_match($tabletRegex, $stockUserAgent)) {
        ++$tabletBrowser;
      }
    }

    if ($tabletBrowser > 0) {
      return 'Tablet';
    }

    if ($mobileBrowser > 0) {
      return 'Mobile';
    }

    return 'Computer';
  }

  /**
   * Handles getUserAgent operation.
   */
  private static function getUserAgent(): string
  {
    /** Declare and cast explictly to adhere to phpstan 6+ strict rules */
    $userAgent = is_string($_SERVER['HTTP_USER_AGENT'] ?? null)
      ? $_SERVER['HTTP_USER_AGENT'] : '';

    return $userAgent;
  }
}

