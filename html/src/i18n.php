<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * i18n.php
 *
 * Purpose: Provide runtime translation lookup and localization helpers.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

final class I18n {
	public const ABOUT_US = '<em>A</em>bout';
	public const ACCOUNT = '<em>A</em>ccount';
	public const ADMIN = 'Ad<em>m</em>in';
	public const CALENDAR_SVG = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g fill="currentColor"><path fill-rule="evenodd" d="M6.5 2.5a.75.75 0 0 1 .75.75V4h9.5v-.75a.75.75 0 0 1 1.5 0V4h.25A3.25 3.25 0 0 1 21.75 7.25v11A3.25 3.25 0 0 1 18.5 21.5h-13A3.25 3.25 0 0 1 2.25 18.25v-11A3.25 3.25 0 0 1 5.5 4h.25v-.75a.75.75 0 0 1 .75-.75ZM5.5 5.5A1.75 1.75 0 0 0 3.75 7.25v.25h16.5v-.25A1.75 1.75 0 0 0 18.5 5.5h-.25v1a.75.75 0 0 1-1.5 0v-1h-9.5v1a.75.75 0 0 1-1.5 0v-1H5.5Zm14.75 3.5H3.75v9.25c0 .966.784 1.75 1.75 1.75h13c.966 0 1.75-.784 1.75-1.75V9Z" clip-rule="evenodd"/><rect x="5.25" y="13.75" width="3.75" height="1.5" rx=".75"/><rect x="15" y="13.75" width="3.75" height="1.5" rx=".75"/><rect x="5.25" y="11" width="1.5" height="7" rx=".75"/><rect x="17.25" y="11" width="1.5" height="7" rx=".75"/><path d="M11.25 10.75a.75.75 0 0 1 1.5 0v.3c1.094.178 1.75.847 1.75 1.825a.75.75 0 0 1-1.5 0c0-.316-.324-.625-1-.625-.731 0-1 .278-1 .5 0 .16 0 .5 1.25.5 1.985 0 2.75.988 2.75 2a2.078 2.078 0 0 1-1.75 1.95v.445a.75.75 0 0 1-1.5 0v-.445A2.184 2.184 0 0 1 9.5 15a.75.75 0 0 1 1.5 0c0 .316.324.625 1 .625.731 0 1-.278 1-.5 0-.16 0-.5-1.25-.5-1.985 0-2.75-.988-2.75-2 0-.885.68-1.676 1.75-1.9v-.3Z"/></g></svg>';
	public const EARNINGS = 'Ea<em>r</em>nings';
	public const HELP = '<em>H</em>elp';
	public const HELP_PAGE_TEASER = 'Check out our <a href="/__v20251116__/help/" accesskey="h"><em>H</em>elp  Page</a> for videos and more!';
	public const MEDIA = '<em>M</em>edia';
	public const MEDIA_HTML = '<em>M</em>edia';
	public const MONEY_SVG = '<svg class=\'svg-icon\' xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 860 860"><path d="M 481 -120 q -17 0 -28.5 -11.5 T 441 -160 v -46 q -45 -10 -79 -35 t -55 -70 q -7 -14 -0.5 -29.5 T 330 -363 q 14 -6 29 0.5 t 23 21.5 q 17 30 43 45.5 t 64 15.5 q 41 0 69.5 -18.5 T 587 -356 q 0 -35 -22 -55.5 T 463 -458 q -86 -27 -118 -64.5 T 313 -614 q 0 -65 42 -101 t 86 -41 v -44 q 0 -17 11.5 -28.5 T 481 -840 q 17 0 28.5 11.5 T 521 -800 v 44 q 38 6 66 24.5 t 46 45.5 q 9 13 3.5 29 T 614 -634 q -14 6 -29 0.5 T 557 -653 q -13 -14 -30.5 -21.5 T 483 -682 q -44 0 -67 19.5 T 393 -614 q 0 33 30 52 t 104 40 q 69 20 104.5 63.5 T 667 -358 q 0 71 -42 108 t -104 46 v 44 q 0 17 -11.5 28.5 T 481 -120 Z"/></svg>';
	public const NEXT_SVG = '<svg class=\'svg-icon\' xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -900 600 600"><path d="M 504 -480 L 348 -636 q -11 -11 -11 -28 t 11 -28 q 11 -11 28 -11 t 28 11 l 184 184 q 6 6 8.5 13 t 2.5 15 q 0 8 -2.5 15 t -8.5 13 L 404 -268 q -11 11 -28 11 t -28 -11 q -11 -11 -11 -28 t 11 -28 l 156 -156 Z"/></svg>';
	public const PAYCAL = 'Pay<em>C</em>al';
	public const PROFILE = 'Pro<em>f</em>ile';
	public const ORGANIZATIONS = 'Organizations';
	public const POLICIES = 'Po<em>l</em>icies';
	public const POLICIES_HTML = 'Po<em>l</em>icies';
	public const PREVIOUS_SVG = '<svg class=\'svg-icon\' xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -900 600 600" width="24"><path d="m 432 -480 l 156 156 q 11 11 11 28 t -11 28 q -11 11 -28 11 t -28 -11 L 348 -452 q -6 -6 -8.5 -13 t -2.5 -15 q 0 -8 2.5 -15 t 8.5 -13 l 184 -184 q 11 -11 28 -11 t 28 11 q 11 11 11 28 t -11 28 L 432 -480 Z"/></svg>';
	public const SETTINGS = 'S<em>e</em>ttings';
	public const SOC2 = 'SOC2';
	public const SETTINGS_SVG = '<svg class=\'svg-icon\' xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -1100 960 960" width="24"><path d="M 433 -80 q -27 0 -46.5 -18 T 363 -142 l -9 -66 q -13 -5 -24.5 -12 T 307 -235 l -62 26 q -25 11 -50 2 t -39 -32 l -47 -82 q -14 -23 -8 -49 t 27 -43 l 53 -40 q -1 -7 -1 -13.5 v -27 q 0 -6.5 1 -13.5 l -53 -40 q -21 -17 -27 -43 t 8 -49 l 47 -82 q 14 -23 39 -32 t 50 2 l 62 26 q 11 -8 23 -15 t 24 -12 l 9 -66 q 4 -26 23.5 -44 t 46.5 -18 h 94 q 27 0 46.5 18 t 23.5 44 l 9 66 q 13 5 24.5 12 t 22.5 15 l 62 -26 q 25 -11 50 -2 t 39 32 l 47 82 q 14 23 8 49 t -27 43 l -53 40 q 1 7 1 13.5 v 27 q 0 6.5 -2 13.5 l 53 40 q 21 17 27 43 t -8 49 l -48 82 q -14 23 -39 32 t -50 -2 l -60 -26 q -11 8 -23 15 t -24 12 l -9 66 q -4 26 -23.5 44 T 527 -80 h -94 Z m 7 -80 h 79 l 14 -106 q 31 -8 57.5 -23.5 T 639 -327 l 99 41 39 -68 l -86 -65 q 5 -14 7 -29.5 t 2 -31.5 q 0 -16 -2 -31.5 t -7 -29.5 l 86 -65 l -39 -68 l -99 42 q -22 -23 -48.5 -38.5 T 533 -694 l -13 -106 h -79 l -14 106 q -31 8 -57.5 -23.5 T 321 -633 l -99 -41 l -39 68 l 86 64 q -5 15 -7 30 t -2 32 q 0 16 2 31 t 7 30 l -86 65 l 39 68 l 99 -42 q 22 23 48.5 38.5 T 427 -266 l 13 106 Z m 42 -180 q 58 0 99 -41 t 41 -99 q 0 -58 -41 -99 t -99 -41 q -59 0 -99.5 41 T 342 -480 q 0 58 40.5 99 t 99.5 41 Z m -2 -140 Z"/></svg>';
	public const SITE_NAME = 'Pay<em>C</em>al';
	public const SITES = '<em>S</em>ites';
	public const SITES_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z"/></svg>';
	public const ORGANIZATIONS_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="currentColor"><circle cx="8" cy="8" r="3.5"/><path d="M 8 14 c -2.33 0 -7 1.17 -7 3.5 V 22 h 14 v -4.5 c 0 -2.33 -4.67 -3.5 -7 -3.5 Z"/><circle cx="16" cy="7.5" r="3"/><path d="M 16 13.5 c -1.67 0 -5 0.83 -5 2.5 v 3.5 h 10 v -3.5 c 0 -1.67 -3.33 -2.5 -5 -2.5 Z" opacity="0.9"/></g></svg>';
	public const ORGANIZATIONS_HTML = '<em>O</em>rganizations';
	public const PROFILE_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M12 14c-5 0-8 2.24-8 4v1h16v-1c0-1.76-3-4-8-4z"/></svg>';
	public const PROFILE_HTML = 'Pro<em>f</em>ile';
}
