<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * CryptoVersions.php
 *
 * Purpose: Define the CryptoVersions class for PayCal\Domain.
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
 * Class CryptoVersions
 *
 * Static provider of cryptographic algorithm versions and configurations.
 */
final class CryptoVersions
{
    /** @return array<string, array<string, int|string>> */
    public static function getVersionInfo(): array
    {
        // Dummy implementation for PHPStan satisfaction
        return [
            'algorithm' => [
                'version' => 1,
                'name' => 'AES-GCM',
            ],
            'kdf' => [
                'version' => 1,
                'name' => 'PBKDF2',
                'iterations' => 100000,
                'hashAlgo' => 'SHA-256',
            ],
            'envelope' => [
                'version' => 1,
            ],
        ];
    }

    /**
     * Handles getAlgorithmVersion operation.
     */
    public static function getAlgorithmVersion(): int
    {
        return 1;
    }

    /**
     * Handles getAlgorithmName operation.
     */
    public static function getAlgorithmName(): string
    {
        return 'AES-256-GCM';
    }

    /**
     * Handles getKdfVersion operation.
     */
    public static function getKdfVersion(): int
    {
        return 1;
    }

    /**
     * Handles getKdfName operation.
     */
    public static function getKdfName(): string
    {
        return 'PBKDF2-SHA256';
    }

    /**
     * Handles getEnvelopeVersion operation.
     */
    public static function getEnvelopeVersion(): int
    {
        return 1;
    }

    /**
     * Handles isSupportedAlgorithmVersion operation.
     */
    public static function isSupportedAlgorithmVersion(int $version): bool
    {
        return $version === 1;
    }

    /**
     * Handles isSupportedKdfVersion operation.
     */
    public static function isSupportedKdfVersion(int $version): bool
    {
        return $version === 1;
    }

    /**
     * Handles isSupportedEnvelopeVersion operation.
     */
    public static function isSupportedEnvelopeVersion(int $version): bool
    {
        return $version === 1;
    }
}

