<?php declare(strict_types=1);

namespace PayCal\Domain;

/** Compare verified mailboxes without changing legacy account lookup indexes. */
final class FederatedEmailIdentity
{
  /** Return a validated identity, preserving custom domain mailbox distinctions. */
  public static function normalize(string $email): string
  {
    $email = strtolower(trim($email));
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
      return '';
    }
    [$local, $domain] = explode('@', $email, 2);
    if ($domain === 'gmail.com' || $domain === 'googlemail.com') {
      $local = explode('+', $local, 2)[0];
      $local = str_replace('.', '', $local);
      $domain = 'gmail.com';
    }
    return $local !== '' ? $local . '@' . $domain : '';
  }

  /** A verified provider claim must identify the stored account's actual inbox. */
  public static function matches(string $storedEmail, string $claimedEmail): bool
  {
    $stored = self::normalize($storedEmail);
    $claimed = self::normalize($claimedEmail);
    return $stored !== '' && $claimed !== '' && hash_equals($stored, $claimed);
  }
}
