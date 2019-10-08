<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Search;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\TimeSlot\TimeSlotService;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;

/**
 * Class GetSearchCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Search
 */
class GetSearchCommandHandler extends CommandHandler
{
    /**
     * @param GetSearchCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function handle(GetSearchCommand $command)
    {
        $result = new CommandResult();

        $resultData = [];

        $params = $command->getField('params');

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var BookableApplicationService $bookableService */
        $bookableService = $this->container->get('application.bookable.service');
        /** @var \AmeliaBooking\Domain\Services\Settings\SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var \AmeliaBooking\Application\Services\Settings\SettingsService $settingsAS */
        $settingsAS = $this->container->get('application.settings.service');
        /** @var TimeSlotService $timeSlotService */
        $timeSlotService = $this->container->get('domain.timeSlot.service');
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var ProviderApplicationService $providerAS */
        $providerAS = $this->container->get('application.user.provider.service');

        if (isset($params['startOffset'], $params['endOffset'])
            && $settingsDS->getSetting('general', 'showClientTimeZone')) {
            $searchStartDateTimeString = DateTimeService::getCustomDateTimeFromUtc(
                DateTimeService::getClientUtcCustomDateTime(
                    $params['date'] . ' ' . (isset($params['timeFrom']) ? $params['timeFrom'] : '00:00:00'),
                    -$params['startOffset']
                )
            );

            $searchEndDateTimeString = DateTimeService::getCustomDateTimeFromUtc(
                DateTimeService::getClientUtcCustomDateTime(
                    $params['date'] . ' ' . (isset($params['timeTo']) ? $params['timeTo'] : '23:59:00'),
                    -$params['endOffset']
                )
            );

            $searchStartDateString = explode(' ', $searchStartDateTimeString)[0];
            $searchEndDateString = explode(' ', $searchEndDateTimeString)[0];

            $searchTimeFrom = explode(' ', $searchStartDateTimeString)[1];
            $searchTimeTo = explode(' ', $searchEndDateTimeString)[1];
        } else {
            $searchStartDateString = $params['date'];
            $searchEndDateString = $params['date'];
            $searchTimeFrom = isset($params['timeFrom']) ? $params['timeFrom'] : null;
            $searchTimeTo = isset($params['timeTo']) ? $params['timeTo'] : null;
        }

        // Get future appointments
        $appointments = $appointmentRepo->getFutureAppointments();

        $providers = $providerRepository->getByCriteria($params);

        // Add future appointments to provider's appointment list
        $providerAS->addAppointmentsToAppointmentList($providers, $appointments);

        // Find services for providers and add providers to services
        $servicesCriteria = $this->buildServicesSearchCriteria($providers, $params);

        $services = $serviceRepository->getByCriteria($servicesCriteria);

        // Get time slot setting
        $timeSlotLength = $settingsDS->getSetting('general', 'timeSlotLength');

        // Get global days off
        $globalDaysOff = $settingsAS->getGlobalDaysOff();

        $bookIfPending = $settingsDS->getSetting('general', 'allowBookingIfPending');

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            $bookableService->checkServiceTimes($service);

            // get start DateTime based on minimum time prior to booking
            $offset = DateTimeService::getNowDateTimeObject()
                ->modify("+{$settingsDS->getSetting('general', 'minimumTimeRequirementPriorToBooking')} seconds");

            $startDateTime = DateTimeService::getCustomDateTimeObject($searchStartDateString);
            $startDateTime = $offset > $startDateTime ? $offset : $startDateTime;

            $endDateTime = DateTimeService::getCustomDateTimeObject($searchEndDateString);

            $providersCopy = unserialize(serialize($providers));

            $providersList = $bookableService->getServiceProviders($service, $providersCopy);

            // modify provider schedule for given service
            /** @var Provider $provider */
            foreach ($providersList->getItems() as $provider) {
                $providerAS->setServicePeriodSchedule($provider, $service->getId()->getValue());
            }

            $freeIntervals = $timeSlotService->getFreeTime(
                $service,
                $providersList,
                $globalDaysOff,
                $startDateTime,
                $endDateTime->modify('+1 day'),
                1,
                $bookIfPending
            );

            $requiredTime = $appointmentAS->getAppointmentRequiredTime($service);

            $freeSlots = $timeSlotService->getAppointmentFreeSlots(
                $service,
                $requiredTime,
                $freeIntervals,
                $timeSlotLength,
                $startDateTime,
                $settingsDS->getSetting('general', 'serviceDurationAsSlot')
            );

            if ($searchTimeFrom) {
                $freeSlots = $this->filterByTimeFrom($searchStartDateString, $searchTimeFrom, $freeSlots);
            }

            if ($searchTimeTo) {
                $freeSlots = $this->filterByTimeTo($searchEndDateString, $searchTimeTo, $freeSlots);
            }

            $providersIds = !empty($freeSlots) ? array_values(
                array_unique(array_reduce(array_values($freeSlots)[0], 'array_merge', []))
            ) : [];

            foreach ($providersIds as $providersId) {
                $resultData[] = [
                    $service->getId()->getValue() => $providersId,
                    'price'                       => $providersList
                        ->getItem($providersId)
                        ->getServiceList()
                        ->getItem($service->getId()->getValue())
                        ->getPrice()
                        ->getValue()
                ];
            }
        }

        // Sort results by price
        if (strpos($params['sort'], 'price') !== false) {
            usort($resultData, function ($service1, $service2) {
                return $service1['price'] > $service2['price'];
            });

            if ($params['sort'] === '-price') {
                $resultData = array_reverse($resultData);
            }
        }

        // Pagination
        $resultDataPaginated = $this->paginateData($resultData, $params['page']);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved searched services.');
        $result->setData([
            'providersServices' => $resultDataPaginated,
            'total'             => count($resultData)
        ]);

        return $result;
    }

    /**
     * @param $date
     * @param $time
     * @param $freeSlots
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function filterByTimeFrom($date, $time, $freeSlots)
    {
        foreach (array_keys($freeSlots[$date]) as $freeSlotKey) {
            if (DateTimeService::getCustomDateTimeObject($freeSlotKey) >=
                DateTimeService::getCustomDateTimeObject($time)) {
                break;
            }

            unset($freeSlots[$date][$freeSlotKey]);

            if (empty($freeSlots[$date])) {
                unset($freeSlots[$date]);
            }
        }

        return $freeSlots;
    }

    /**
     * @param $date
     * @param $time
     * @param $freeSlots
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function filterByTimeTo($date, $time, $freeSlots)
    {
        foreach (array_reverse(array_keys($freeSlots[$date])) as $freeSlotKey) {
            if (DateTimeService::getCustomDateTimeObject($freeSlotKey) <=
                DateTimeService::getCustomDateTimeObject($time)) {
                break;
            }

            unset($freeSlots[$date][$freeSlotKey]);

            if (empty($freeSlots[$date])) {
                unset($freeSlots[$date]);
            }
        }

        return $freeSlots;
    }

    /**
     * @param Collection $providers
     * @param array      $params
     *
     * @return array
     */
    private function buildServicesSearchCriteria($providers, $params)
    {
        return [
            'providers' => array_column($providers->toArray(), 'id'),
            'search'    => !empty($params['search']) ? $params['search'] : null,
            'services'  => $params['services'],
            'sort'      => $params['sort'],
            'status'    => 'visible',
        ];
    }

    /**
     * @param array $data
     * @param int   $page
     *
     * @return array
     * @throws \Interop\Container\Exception\ContainerException
     */
    private function paginateData($data, $page)
    {
        /** @var \AmeliaBooking\Domain\Services\Settings\SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $itemsPerPage = $settingsDS->getSetting('general', 'itemsPerPage');
        $offset = ($page - 1) * $itemsPerPage;

        return array_slice($data, $offset, $itemsPerPage);
    }
}
