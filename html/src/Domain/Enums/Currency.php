<?php declare(strict_types=1);

namespace PayCal\Domain\Enums;

/**
 * Currency.php
 *
 * Purpose: ISO 4217 currency catalog for PayCal — Americas and Europe scope.
 *          Supranational / precious-metal codes are included for completeness.
 *          African, Asian, and Oceanian currencies are deferred for a future update.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Enums
 * @package    PayCal\Domain\Enums
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
enum Currency: string
{
  // ── Americas ────────────────────────────────────────────────────────────────
  case ARS = 'ARS';
  case AWG = 'AWG';
  case BBD = 'BBD';
  case BMD = 'BMD';
  case BOB = 'BOB';
  case BOV = 'BOV';
  case BRL = 'BRL';
  case BSD = 'BSD';
  case BZD = 'BZD';
  case CAD = 'CAD';
  case CLF = 'CLF';
  case CLP = 'CLP';
  case COP = 'COP';
  case COU = 'COU';
  case CRC = 'CRC';
  case CUP = 'CUP';
  case DOP = 'DOP';
  case FKP = 'FKP';
  case GTQ = 'GTQ';
  case GYD = 'GYD';
  case HNL = 'HNL';
  case HTG = 'HTG';
  case JMD = 'JMD';
  case KYD = 'KYD';
  case MXN = 'MXN';
  case MXV = 'MXV';
  case NIO = 'NIO';
  case PAB = 'PAB';
  case PEN = 'PEN';
  case PYG = 'PYG';
  case SRD = 'SRD';
  case SVC = 'SVC';
  case TTD = 'TTD';
  case USD = 'USD';
  case USN = 'USN';
  case UYI = 'UYI';
  case UYU = 'UYU';
  case UYW = 'UYW';
  case VED = 'VED';
  case VES = 'VES';
  case XCD = 'XCD';
  case XCG = 'XCG';
  case XSU = 'XSU';

  // ── Europe ──────────────────────────────────────────────────────────────────
  case ALL = 'ALL';
  case BAM = 'BAM';
  case BYN = 'BYN';
  case CHE = 'CHE';
  case CHF = 'CHF';
  case CHW = 'CHW';
  case CZK = 'CZK';
  case DKK = 'DKK';
  case EUR = 'EUR';
  case GBP = 'GBP';
  case GIP = 'GIP';
  case HUF = 'HUF';
  case ISK = 'ISK';
  case MDL = 'MDL';
  case MKD = 'MKD';
  case NOK = 'NOK';
  case PLN = 'PLN';
  case RON = 'RON';
  case RSD = 'RSD';
  case RUB = 'RUB';
  case SEK = 'SEK';
  case UAH = 'UAH';

  // ── Supranational / Special ──────────────────────────────────────────────────
  case XAG = 'XAG';
  case XAU = 'XAU';
  case XBA = 'XBA';
  case XBB = 'XBB';
  case XBC = 'XBC';
  case XBD = 'XBD';
  case XDR = 'XDR';
  case XPD = 'XPD';
  case XPT = 'XPT';

  // ─────────────────────────────────────────────────────────────────────────────

  /**
   * Handles label operation.
   */
  public function label(): string
  {
    return match ($this) {
      // Americas
      self::ARS => 'Argentine Peso',
      self::AWG => 'Aruban Florin',
      self::BBD => 'Barbadian Dollar',
      self::BMD => 'Bermudian Dollar',
      self::BOB => 'Bolivian Boliviano',
      self::BOV => 'Bolivian Mvdol',
      self::BRL => 'Brazilian Real',
      self::BSD => 'Bahamian Dollar',
      self::BZD => 'Belize Dollar',
      self::CAD => 'Canadian Dollar',
      self::CLF => 'Chilean Unidad de Fomento',
      self::CLP => 'Chilean Peso',
      self::COP => 'Colombian Peso',
      self::COU => 'Unidad de Valor Real',
      self::CRC => 'Costa Rican Colón',
      self::CUP => 'Cuban Peso',
      self::DOP => 'Dominican Peso',
      self::FKP => 'Falkland Islands Pound',
      self::GTQ => 'Guatemalan Quetzal',
      self::GYD => 'Guyanese Dollar',
      self::HNL => 'Honduran Lempira',
      self::HTG => 'Haitian Gourde',
      self::JMD => 'Jamaican Dollar',
      self::KYD => 'Cayman Islands Dollar',
      self::MXN => 'Mexican Peso',
      self::MXV => 'Mexican Unidad de Inversion',
      self::NIO => 'Nicaraguan Córdoba',
      self::PAB => 'Panamanian Balboa',
      self::PEN => 'Peruvian Sol',
      self::PYG => 'Paraguayan Guaraní',
      self::SRD => 'Surinamese Dollar',
      self::SVC => 'El Salvadoran Colón',
      self::TTD => 'Trinidad and Tobago Dollar',
      self::USD => 'United States Dollar',
      self::USN => 'United States Dollar (Next Day)',
      self::UYI => 'Uruguayan Peso en Unidades Indexadas',
      self::UYU => 'Uruguayan Peso',
      self::UYW => 'Unidad Previsional',
      self::VED => 'Bolívar Soberano (digital)',
      self::VES => 'Bolívar Soberano',
      self::XCD => 'East Caribbean Dollar',
      self::XCG => 'Caribbean Guilder',
      self::XSU => 'SUCRE',
      // Europe
      self::ALL => 'Albanian Lek',
      self::BAM => 'Bosnia-Herzegovina Convertible Mark',
      self::BYN => 'Belarusian Ruble',
      self::CHE => 'WIR Euro',
      self::CHF => 'Swiss Franc',
      self::CHW => 'WIR Franc',
      self::CZK => 'Czech Koruna',
      self::DKK => 'Danish Krone',
      self::EUR => 'Euro',
      self::GBP => 'Pound Sterling',
      self::GIP => 'Gibraltar Pound',
      self::HUF => 'Hungarian Forint',
      self::ISK => 'Icelandic Króna',
      self::MDL => 'Moldovan Leu',
      self::MKD => 'Macedonian Denar',
      self::NOK => 'Norwegian Krone',
      self::PLN => 'Polish Złoty',
      self::RON => 'Romanian Leu',
      self::RSD => 'Serbian Dinar',
      self::RUB => 'Russian Ruble',
      self::SEK => 'Swedish Krona',
      self::UAH => 'Ukrainian Hryvnia',
      // Supranational
      self::XAG => 'Silver',
      self::XAU => 'Gold',
      self::XBA => 'European Composite Unit (EURCO)',
      self::XBB => 'European Monetary Unit',
      self::XBC => 'European Unit of Account 9',
      self::XBD => 'European Unit of Account 17',
      self::XDR => 'Special Drawing Right',
      self::XPD => 'Palladium',
      self::XPT => 'Platinum',
    };
  }

  /**
   * Handles symbol operation.
   */
  public function symbol(): string
  {
    return match ($this) {
      // Americas
      self::ARS => '$',
      self::AWG => 'ƒ',
      self::BBD => '$',
      self::BMD => '$',
      self::BOB => 'Bs.',
      self::BOV => 'BOV',
      self::BRL => 'R$',
      self::BSD => '$',
      self::BZD => 'BZ$',
      self::CAD => '$',
      self::CLF => 'UF',
      self::CLP => '$',
      self::COP => '$',
      self::COU => 'COU',
      self::CRC => '₡',
      self::CUP => '$',
      self::DOP => 'RD$',
      self::FKP => '£',
      self::GTQ => 'Q',
      self::GYD => '$',
      self::HNL => 'L',
      self::HTG => 'G',
      self::JMD => 'J$',
      self::KYD => '$',
      self::MXN => '$',
      self::MXV => 'MXV',
      self::NIO => 'C$',
      self::PAB => 'B/.',
      self::PEN => 'S/',
      self::PYG => '₲',
      self::SRD => '$',
      self::SVC => '₡',
      self::TTD => 'TT$',
      self::USD => '$',
      self::USN => '$',
      self::UYI => 'UYI',
      self::UYU => '$U',
      self::UYW => 'UYW',
      self::VED => 'Bs.D',
      self::VES => 'Bs.S',
      self::XCD => '$',
      self::XCG => 'ƒ',
      self::XSU => 'XSU',
      // Europe
      self::ALL => 'L',
      self::BAM => 'KM',
      self::BYN => 'Br',
      self::CHE => 'CHE',
      self::CHF => 'Fr',
      self::CHW => 'CHW',
      self::CZK => 'Kč',
      self::DKK => 'kr',
      self::EUR => '€',
      self::GBP => '£',
      self::GIP => '£',
      self::HUF => 'Ft',
      self::ISK => 'kr',
      self::MDL => 'L',
      self::MKD => 'ден',
      self::NOK => 'kr',
      self::PLN => 'zł',
      self::RON => 'lei',
      self::RSD => 'din.',
      self::RUB => '₽',
      self::SEK => 'kr',
      self::UAH => '₴',
      // Supranational
      self::XAG => 'oz t',
      self::XAU => 'oz t',
      self::XBA => 'XBA',
      self::XBB => 'XBB',
      self::XBC => 'XBC',
      self::XBD => 'XBD',
      self::XDR => 'SDR',
      self::XPD => 'oz t',
      self::XPT => 'oz t',
    };
  }

  /**
   * Handles countries operation.
   */
  public function countries(): string
  {
    return match ($this) {
      // Americas
      self::ARS => 'Argentina',
      self::AWG => 'Aruba',
      self::BBD => 'Barbados',
      self::BMD => 'Bermuda',
      self::BOB => 'Bolivia',
      self::BOV => 'Bolivia',
      self::BRL => 'Brazil',
      self::BSD => 'Bahamas',
      self::BZD => 'Belize',
      self::CAD => 'Canada',
      self::CLF => 'Chile',
      self::CLP => 'Chile',
      self::COP => 'Colombia',
      self::COU => 'Colombia',
      self::CRC => 'Costa Rica',
      self::CUP => 'Cuba',
      self::DOP => 'Dominican Republic',
      self::FKP => 'Falkland Islands',
      self::GTQ => 'Guatemala',
      self::GYD => 'Guyana',
      self::HNL => 'Honduras',
      self::HTG => 'Haiti',
      self::JMD => 'Jamaica',
      self::KYD => 'Cayman Islands',
      self::MXN => 'Mexico',
      self::MXV => 'Mexico',
      self::NIO => 'Nicaragua',
      self::PAB => 'Panama',
      self::PEN => 'Peru',
      self::PYG => 'Paraguay',
      self::SRD => 'Suriname',
      self::SVC => 'El Salvador',
      self::TTD => 'Trinidad and Tobago',
      self::USD => 'United States',
      self::USN => 'United States',
      self::UYI => 'Uruguay',
      self::UYU => 'Uruguay',
      self::UYW => 'Uruguay',
      self::VED => 'Venezuela',
      self::VES => 'Venezuela',
      self::XCD => 'Antigua and Barbuda, Dominica, Grenada, Saint Kitts and Nevis, Saint Lucia, Saint Vincent and the Grenadines, Anguilla, Montserrat',
      self::XCG => 'Curaçao, Sint Maarten',
      self::XSU => 'ALBA member states',
      // Europe
      self::ALL => 'Albania',
      self::BAM => 'Bosnia and Herzegovina',
      self::BYN => 'Belarus',
      self::CHE => 'Switzerland',
      self::CHF => 'Switzerland, Liechtenstein',
      self::CHW => 'Switzerland',
      self::CZK => 'Czech Republic',
      self::DKK => 'Denmark, Faroe Islands, Greenland',
      self::EUR => 'Austria, Belgium, Croatia, Cyprus, Estonia, Finland, France, Germany, Greece, Ireland, Italy, Latvia, Lithuania, Luxembourg, Malta, Netherlands, Portugal, Slovakia, Slovenia, Spain',
      self::GBP => 'United Kingdom',
      self::GIP => 'Gibraltar',
      self::HUF => 'Hungary',
      self::ISK => 'Iceland',
      self::MDL => 'Moldova',
      self::MKD => 'North Macedonia',
      self::NOK => 'Norway',
      self::PLN => 'Poland',
      self::RON => 'Romania',
      self::RSD => 'Serbia',
      self::RUB => 'Russia',
      self::SEK => 'Sweden',
      self::UAH => 'Ukraine',
      // Supranational
      self::XAG => 'International',
      self::XAU => 'International',
      self::XBA => 'International',
      self::XBB => 'International',
      self::XBC => 'International',
      self::XBD => 'International',
      self::XDR => 'International (IMF)',
      self::XPD => 'International',
      self::XPT => 'International',
    };
  }

  /**
   * Handles isValid operation.
   */
  public static function isValid(string $code): bool
  {
    return self::tryFrom(strtoupper($code)) !== null;
  }

  /**
   * Returns a flat array keyed by ISO code, suitable for JSON-encoding into JS.
   *
   * @return array<string, array{code: string, symbol: string, name: string, countries: string}>
   */
  public static function toArray(): array
  {
    $result = [];
    foreach (self::cases() as $case) {
      $result[$case->value] = [
        'code'      => $case->value,
        'symbol'    => $case->symbol(),
        'name'      => $case->label(),
        'countries' => $case->countries(),
      ];
    }
    return $result;
  }
}


