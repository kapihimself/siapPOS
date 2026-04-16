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

    public function execute(OnboardingData $data): void
    {
        $this->settings->updateOnboarding(
            businessName: $data->businessName,
            outletName: $data->outletName,
            template: $data->template,
        );
    }
}
