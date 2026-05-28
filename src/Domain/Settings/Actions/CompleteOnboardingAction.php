<?php
declare(strict_types=1);

namespace Siappos\Domain\Settings\Actions;

use Siappos\Domain\Settings\DTO\OnboardingData;
use Siappos\Domain\Settings\SettingsRepository;

final class CompleteOnboardingAction
{
    public function __construct(private readonly SettingsRepository $settings)
    {
    }

    public function execute(OnboardingData $data, int $businessId): void
    {
        $this->settings->updateOnboarding(
            businessId: $businessId,
            businessName: $data->businessName,
            outletName: $data->outletName,
            template: $data->template,
        );
    }
}
