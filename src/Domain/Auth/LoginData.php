<?php
declare(strict_types=1);

namespace Siappos\Domain\Auth;

use InvalidArgumentException;

final class LoginData
{
    public function __construct(
        public readonly string $username,
        public readonly string $pin,
    ) {
        if ($this->username === '') {
            throw new InvalidArgumentException('Username wajib diisi.');
        }

        if (!preg_match('/^\d{4,6}$/', $this->pin)) {
            throw new InvalidArgumentException('PIN harus 4-6 digit angka.');
        }
    }

    /** @param array<string, mixed> $payload */
    public static function fromRequest(array $payload): self
    {
        return new self(
            username: strtolower(trim((string) ($payload['username'] ?? ''))),
            pin: trim((string) ($payload['pin'] ?? '')),
        );
    }
}
