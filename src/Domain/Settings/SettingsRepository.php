<?php
declare(strict_types=1);

namespace Siappos\Domain\Settings;

use PDO;

final class SettingsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed> */
    public function get(int $businessId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM settings WHERE business_id = :business_id ORDER BY id ASC LIMIT 1');
        $stmt->execute([':business_id' => $businessId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : [];
    }

    public function updateOnboarding(int $businessId, string $businessName, string $outletName, BusinessTemplate $template): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE settings
             SET business_name = :business_name,
                 outlet_name = :outlet_name,
                 active_template = :active_template,
                 onboarding_completed = 1,
                 updated_at = CURRENT_TIMESTAMP
             WHERE business_id = :business_id'
        );

        $stmt->execute([
            ':business_id' => $businessId,
            ':business_name' => $businessName,
            ':outlet_name' => $outletName,
            ':active_template' => $template->value,
        ]);
    }

    public function isOnboardingCompleted(int $businessId): bool
    {
        $settings = $this->get($businessId);

        return ((int) ($settings['onboarding_completed'] ?? 0)) === 1;
    }

    public function activeTemplate(int $businessId): BusinessTemplate
    {
        $settings = $this->get($businessId);
        $template = BusinessTemplate::tryFrom((string) ($settings['active_template'] ?? 'retail'));

        return $template ?? BusinessTemplate::Retail;
    }
}
