<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\CustomField;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;

/**
 * Class GetCustomFieldsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\CustomField
 */
class GetCustomFieldsCommandHandler extends CommandHandler
{
    /**
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle()
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::CUSTOM_FIELDS)) {
            throw new AccessDeniedException('You are not allowed to read custom fields.');
        }

        $result = new CommandResult();

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        $customFields = $customFieldRepository->getAll();

        if (!$customFields instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get custom fields.');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved custom fields.');
        $result->setData([
            'customFields' => $customFields->toArray(),
        ]);

        return $result;
    }
}