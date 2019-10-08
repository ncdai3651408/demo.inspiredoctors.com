<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\CustomField;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\CustomField\CustomFieldOption;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\CustomField\CustomFieldFactory;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldOptionRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldServiceRepository;

/**
 * Class UpdateCustomFieldCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\CustomField
 */
class UpdateCustomFieldCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'label',
        'options',
        'position',
        'required',
        'services',
        'type'
    ];

    /**
     * @param UpdateCustomFieldCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateCustomFieldCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::CUSTOM_FIELDS)) {
            throw new AccessDeniedException('You are not allowed to update custom fields.');
        }

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var array $customFieldOptionsArray */
        $customFieldOptionsArray = $command->getFields()['options'];

        $customField = CustomFieldFactory::create($command->getFields());

        if (!$customField instanceof CustomField) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update custom field.');

            return $result;
        }

        $customFieldRepository->beginTransaction();

        try {
            if (!$customFieldRepository->update($customField->getId()->getValue(), $customField)) {
                $customFieldRepository->rollback();
                return $result;
            }

            $customField = $this->handleCustomFieldOptions($customField, $customFieldOptionsArray);

            $this->handleCustomFieldServices($customField);
        } catch (QueryExecutionException $e) {
            $customFieldRepository->rollback();
            throw $e;
        }

        $customFieldRepository->commit();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Custom field successfully updated.');
        $result->setData([
            'customField' => $customField->toArray(),
        ]);

        return $result;
    }

    /**
     * @param CustomField $customField
     * @param array       $customFieldOptionsArray
     *
     * @return CustomField
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function handleCustomFieldOptions($customField, $customFieldOptionsArray)
    {
        /** @var CustomFieldOptionRepository $customFieldOptionRepository */
        $customFieldOptionRepository = $this->container->get('domain.customFieldOption.repository');

        /** @var CustomFieldOption $customFieldOption */
        foreach ($customField->getOptions()->getItems() as $customFieldOptionKey => $customFieldOption) {
            $customFieldOptionArray = $customFieldOptionsArray[$customFieldOptionKey];

            if ($customFieldOptionArray['new'] && !$customFieldOptionArray['deleted']) {
                $customFieldOptionId = $customFieldOptionRepository->add($customFieldOption);
                $customFieldOption->setId(new Id($customFieldOptionId));
            }

            if ($customFieldOptionArray['deleted'] && !$customFieldOptionArray['new']) {
                $customFieldOptionRepository->delete($customFieldOption->getId()->getValue());
                $customField->getOptions()->deleteItem($customFieldOptionKey);
            }

            if ($customFieldOptionArray['edited'] &&
                !$customFieldOptionArray['deleted'] &&
                !$customFieldOptionArray['new']
            ) {
                $customFieldOptionRepository->update($customFieldOption->getId()->getValue(), $customFieldOption);
            }
        }

        return $customField;
    }

    /**
     * @param CustomField $customField
     *
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function handleCustomFieldServices($customField)
    {
        /** @var CustomFieldServiceRepository $customFieldServiceRepository */
        $customFieldServiceRepository = $this->container->get('domain.customFieldService.repository');

        // Get services for this custom field from database
        $customFieldServices = $customFieldServiceRepository->getByCustomFieldId($customField->getId()->getValue());

        // Get ID's of saved services
        $customFieldServicesIds = array_column($customFieldServices, 'serviceId');

        /** @var Service $service */
        foreach ($customField->getServices()->getItems() as $service) {
            // Add only service that is not saved already.
            // Third parameter needs to be false, because some servers return ID's as string
            if (!in_array($service->getId()->getValue(), $customFieldServicesIds, false)) {
                $customFieldServiceRepository->add($customField->getId()->getValue(), $service->getId()->getValue());
                $customFieldServicesIds[] = $service->getId()->getValue();
            }
        }

        $frontedServicesIds = array_column($customField->getServices()->toArray(), 'id');

        foreach ($customFieldServicesIds as $customFieldServicesId) {
            // Remove services that are saved in the database, but not received from frontend
            // Third parameter needs to be false, because some servers return ID's as string
            if (!in_array($customFieldServicesId, $frontedServicesIds, false)) {
                $customFieldServiceRepository->deleteByCustomFieldIdAndServiceId(
                    $customField->getId()->getValue(),
                    $customFieldServicesId
                );
            }
        }
    }
}
