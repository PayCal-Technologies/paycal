<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Scoped capture filter — empty fields mean "match all" (global scope).
 */
final class ArgusCaptureScope
{
  /**
   * Create a capture scope; empty fields match any request value.
   */
  public function __construct(
    public readonly string $userUuid = '',
    public readonly string $businessId = '',
    public readonly string $sessionHash = '',
    public readonly string $requestId = '',
    public readonly string $route = '',
  ) {
  }

  /**
   * @param array<string, mixed> $raw
   */
  public static function fromArray(array $raw): self
  {
    return new self(
      ConfigValue::string($raw['user_uuid'] ?? ''),
      ConfigValue::string($raw['business_id'] ?? ''),
      ConfigValue::string($raw['session_hash'] ?? ''),
      ConfigValue::string($raw['request_id'] ?? ''),
      ConfigValue::string($raw['route'] ?? ''),
    );
  }

  /**
   * @return array<string, string>
   */
  public function toArray(): array
  {
    return [
      'user_uuid' => $this->userUuid,
      'business_id' => $this->businessId,
      'session_hash' => $this->sessionHash,
      'request_id' => $this->requestId,
      'route' => $this->route,
    ];
  }

  /**
   * Return whether this scope matches all requests.
   */
  public function isGlobal(): bool
  {
    return $this->userUuid === ''
      && $this->businessId === ''
      && $this->sessionHash === ''
      && $this->requestId === ''
      && $this->route === '';
  }

  /**
   * Return whether this scope restricts capture to specific request fields.
   */
  public function isActive(): bool
  {
    return !$this->isGlobal();
  }

  /**
   * Return whether a request scope satisfies this capture filter.
   */
  public function matches(ArgusCaptureScope $request): bool
  {
    if ($this->isGlobal()) {
      return true;
    }

    if ($this->userUuid !== '' && !hash_equals($this->userUuid, $request->userUuid)) {
      return false;
    }

    if ($this->businessId !== '' && !hash_equals($this->businessId, $request->businessId)) {
      return false;
    }

    if ($this->sessionHash !== '' && !hash_equals($this->sessionHash, $request->sessionHash)) {
      return false;
    }

    if ($this->requestId !== '' && !hash_equals($this->requestId, $request->requestId)) {
      return false;
    }

    if ($this->route !== '' && !$this->routeMatches($this->route, $request->route)) {
      return false;
    }

    return true;
  }

  /**
   * Return whether an actual route matches the configured route prefix.
   */
  private function routeMatches(string $pattern, string $actual): bool
  {
    $normalizedPattern = strtolower(trim($pattern));
    $normalizedActual = strtolower(trim($actual));
    if ($normalizedPattern === '' || $normalizedActual === '') {
      return false;
    }

    if ($normalizedPattern === $normalizedActual) {
      return true;
    }

    return str_starts_with($normalizedActual, rtrim($normalizedPattern, '/'));
  }
}
