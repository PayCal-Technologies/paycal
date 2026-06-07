<?php declare(strict_types=1);

namespace PayCal\Domain\Config;

/**
 * SiteColorPalette.php
 *
 * Purpose: Canonical 32-color palette for site identification across the PayCal UI.
 *          Colors are used for calendar badges, datagrid row accents, earnings
 *          charts, organization planning indicators, and PDF/export labels.
 *
 * Why this exists:
 * - Centralizes palette definition so the admin panel, swatch picker, and any
 *   future API/export consumers all reference the same source of truth.
 * - Avoids native <input type="color"> which is not themeable and varies by OS.
 * - 32 curated colors keep choices intentional without overwhelming users.
 *
 * Palette organization — 4 rows × 8 colors:
 *   Row 1: Blues & Cyans
 *   Row 2: Greens
 *   Row 3: Yellows, Oranges & Reds
 *   Row 4: Pinks, Purples, Indigos & Neutrals
 *
 * PHP version 8.4.16
 *
 * @category   Domain\Config
 * @package    PayCal\Domain\Config
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
final class SiteColorPalette
{
    /**
     * Returns the full 32-color palette as an ordered array.
     * Each entry: ['hex' => '#rrggbb', 'label' => 'Human Name'].
     *
     * @return list<array{hex: string, label: string}>
     */
    public static function palette(): array
    {
        return [
            // ── Row 1: Blues & Cyans ──────────────────────────────────
            ['hex' => '#1565C0', 'label' => 'Navy Blue'],
            ['hex' => '#1976D2', 'label' => 'Royal Blue'],
            ['hex' => '#42A5F5', 'label' => 'Sky Blue'],
            ['hex' => '#90CAF9', 'label' => 'Powder Blue'],
            ['hex' => '#006064', 'label' => 'Deep Teal'],
            ['hex' => '#00838F', 'label' => 'Teal'],
            ['hex' => '#00ACC1', 'label' => 'Cyan'],
            ['hex' => '#4DD0E1', 'label' => 'Light Cyan'],

            // ── Row 2: Greens ─────────────────────────────────────────
            ['hex' => '#1B5E20', 'label' => 'Forest Green'],
            ['hex' => '#2E7D32', 'label' => 'Dark Green'],
            ['hex' => '#43A047', 'label' => 'Medium Green'],
            ['hex' => '#A5D6A7', 'label' => 'Mint Green'],
            ['hex' => '#33691E', 'label' => 'Olive'],
            ['hex' => '#558B2F', 'label' => 'Fern'],
            ['hex' => '#8BC34A', 'label' => 'Lime'],
            ['hex' => '#C5E1A5', 'label' => 'Light Lime'],

            // ── Row 3: Yellows, Oranges & Reds ───────────────────────
            ['hex' => '#F9A825', 'label' => 'Amber'],
            ['hex' => '#F57F17', 'label' => 'Dark Amber'],
            ['hex' => '#E65100', 'label' => 'Burnt Orange'],
            ['hex' => '#EF6C00', 'label' => 'Orange'],
            ['hex' => '#FF7043', 'label' => 'Deep Orange'],
            ['hex' => '#D84315', 'label' => 'Rust'],
            ['hex' => '#C62828', 'label' => 'Dark Red'],
            ['hex' => '#E53935', 'label' => 'Red'],

            // ── Row 4: Pinks, Purples, Indigos & Neutrals ────────────
            ['hex' => '#AD1457', 'label' => 'Dark Pink'],
            ['hex' => '#E91E63', 'label' => 'Pink'],
            ['hex' => '#7B1FA2', 'label' => 'Dark Purple'],
            ['hex' => '#9C27B0', 'label' => 'Purple'],
            ['hex' => '#4527A0', 'label' => 'Deep Indigo'],
            ['hex' => '#5C6BC0', 'label' => 'Indigo'],
            ['hex' => '#455A64', 'label' => 'Slate'],
            ['hex' => '#78909C', 'label' => 'Steel Blue'],
        ];
    }

    /**
     * Returns the 20-color subset used in the swatch picker (5 × 4 grid).
     * Each entry: ['hex' => '#rrggbb', 'label' => 'Human Name'].
     *
     * @return list<array{hex: string, label: string}>
     */
    public static function pickerPalette(): array
    {
        return [
            // Row 1 – Arctic & Deep Water
            ['hex' => '#6AA6FF', 'label' => 'Glacier Blue'],
            ['hex' => '#2F6BFF', 'label' => 'Deep Arctic'],
            ['hex' => '#224B6B', 'label' => 'Petroleum Blue'],
            ['hex' => '#00A7C2', 'label' => 'North Sea Cyan'],
            ['hex' => '#6FCFC6', 'label' => 'Glacier Mint'],
            // Row 2 – Field & Forest
            ['hex' => '#4F8A3B', 'label' => 'Boreal Green'],
            ['hex' => '#355E3B', 'label' => 'Railway Green'],
            ['hex' => '#476D3B', 'label' => 'Forest Service'],
            ['hex' => '#698F45', 'label' => 'Moss Green'],
            ['hex' => '#9BC53D', 'label' => 'Safety Lime'],
            // Row 3 – Heat & Hazard
            ['hex' => '#FFC247', 'label' => 'Safety Amber'],
            ['hex' => '#FF8A24', 'label' => 'Survey Orange'],
            ['hex' => '#C66A2B', 'label' => 'Copper Ore'],
            ['hex' => '#B44D3A', 'label' => 'Iron Oxide'],
            ['hex' => '#D63B3B', 'label' => 'Rig Red'],
            // Row 4 – Industrial & Blueprint
            ['hex' => '#8F2C45', 'label' => 'Pipeline Burgundy'],
            ['hex' => '#7357FF', 'label' => 'Survey Purple'],
            ['hex' => '#4556C8', 'label' => 'Blueprint Indigo'],
            ['hex' => '#55646E', 'label' => 'Asphalt Gray'],
            ['hex' => '#8FA3B0', 'label' => 'Titanium'],
        ];
    }

    /**
     *
     * @return list<string>
     */
    public static function hexValues(): array
    {
        return array_column(self::palette(), 'hex');
    }

    /**
     * Returns true if the given hex string is in the palette.
     */
    public static function isValid(string $hex): bool
    {
        return in_array(strtoupper($hex), array_map('strtoupper', self::hexValues()), true);
    }

    /**
     * Returns the label for a given hex, or null if not found.
     */
    public static function labelFor(string $hex): ?string
    {
        foreach (self::palette() as $entry) {
            if (strtoupper($entry['hex']) === strtoupper($hex)) {
                return $entry['label'];
            }
        }
        return null;
    }

    /**
     * Default color when none is set.
     */
    public static function default(): string
    {
        return '#6AA6FF'; // Glacier Blue
    }
}
