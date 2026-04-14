<?php
declare(strict_types=1);

namespace Siappos\Domain\Settings\DTO;

use InvalidArgumentException;
use Siappos\Domain\Settings\BusinessTemplate;

final class OnboardingData
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $outletName,
        public readonly BusinessTemplate $template,
    ) {
        if ($this->businessName === '') {
            throw new InvalidArgumentException('Nama bisnis wajib diisi.');
        }

        if ($this->outletName === '') {
            throw new InvalidArgumentException('Nama outlet wajib diisi.');
        }
    }

    /** @param array<string, mixed> $payload */
    public static function fromRequest(array $payload): self
    {
        $template = BusinessTemplate::tryFrom((string) ($payload['template'] ?? 'retail'));

        if (!$template instanceof BusinessTemplate) {
            throw new InvalidArgumentException('Template bisnis tidak valid.');
        }

        return new self(
            businessName: trim((string) ($payload['business_name'] ?? '')),
            outletName: trim((string) ($payload['outlet_name'] ?? '')),
            template: $template,
        );
    }
}
