<?php

namespace AmeliaBooking\Application\Commands\Settings;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Services\Settings\SettingsService;

/**
 * Class GetSettingsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Settings
 */
class GetSettingsCommandHandler extends CommandHandler
{
    /**
     * @return CommandResult
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle()
    {
        $result = new CommandResult();

        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get('domain.settings.service');

        $settings = $settingsService->getAllSettingsCategorized();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved settings.');
        $result->setData([
            'settings' => $settings
        ]);

        return $result;
    }
}
