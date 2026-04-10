<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * ClientCapabilities.php
 *
 * Purpose: Define the ClientCapabilities class for PayCal\Domain.
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



/**
 * Class ClientCapabilities
 *
 * Static service for managing client cryptographic capability data.
 */
final class ClientCapabilities
{
    /** @var array<string, array<string,mixed>> */
    private static array $reports = [];

    /**
     * @return array<string, mixed>
     */
    public static function getMinimalReport(): array
    {
        return [
            'webCryptoSupported' => true,
            'aesGcmSupported' => true,
            'pbkdf2Supported' => true,
            'userAgent' => 'unknown',
            'timestamp' => (int) floor(microtime(true) * 1000),
        ];
    }

    /** @param array<string, mixed> $report */
    public static function isValidReport(array $report): bool
    {
        $required = [
            'webCryptoSupported',
            'aesGcmSupported',
            'pbkdf2Supported',
            'userAgent',
            'timestamp',
        ];

        foreach ($required as $field) {
            if (!array_key_exists($field, $report)) {
                return false;
            }
        }

        return is_bool($report['webCryptoSupported'])
            && is_bool($report['aesGcmSupported'])
            && is_bool($report['pbkdf2Supported'])
            && is_string($report['userAgent'])
            && is_int($report['timestamp']);
    }

    /** @param array<string, mixed> $report */
    public static function store(string $userId, array $report): void
    {
        if (!self::isValidReport($report)) {
            throw new \InvalidArgumentException('Invalid client capability report');
        }

        self::$reports[$userId] = $report;
    }

    /**
     * Handles supportsEncryption operation.
     */
    public static function supportsEncryption(string $userId): bool
    {
        $report = self::$reports[$userId] ?? null;

        if (!is_array($report) || !self::isValidReport($report)) {
            return false;
        }

        return (bool) ($report['webCryptoSupported'] ?? false)
            && (bool) ($report['aesGcmSupported'] ?? false)
            && (bool) ($report['pbkdf2Supported'] ?? false);
    }
}

