<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;

/**
 * Class GetAppointmentsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetAppointmentsCommandHandler extends CommandHandler
{
    /**
     * @param GetAppointmentsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetAppointmentsCommand $command)
    {
        if ($this->getContainer()->get('logged.in.user') === null ||
            !$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to read appointments');
        }

        $result = new CommandResult();

        $currentUser = $this->container->get('logged.in.user');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');
        /** @var SettingsService $settingsDomainService */
        $settingsDomainService = $this->container->get('domain.settings.service');

        // Get general settings
        $generalSettings = $settingsDomainService->getCategorySettings('general');

        $params = $command->getField('params');
        if (!$this->getContainer()->getPermissionsService()->currentUserCanReadOthers(Entities::APPOINTMENTS)) {
            if ($this->getContainer()->get('logged.in.user')->getId() === null) {
                $params[$currentUser->getType() . 'Id'] = 0;
            } else {
                $params[$currentUser->getType() . 'Id'] =
                    $this->getContainer()->get('logged.in.user')->getId()->getValue();
            }
        }

        if ($params['dates']) {
            $params['dates'][0] ? $params['dates'][0] .= ' 00:00:00' : null;
            $params['dates'][1] ? $params['dates'][1] .= ' 23:59:59' : null;
        }

        $appointments = $appointmentRepository->getFiltered($params);

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                if ($booking->getCustomFields() && json_decode($booking->getCustomFields()->getValue(), true) === null) {
                    $booking->setCustomFields(null);
                }
            }
        }

        if (!$appointments instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get appointments');

            return $result;
        }

        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $groupedAppointments = [];

        foreach ($appointments->keys() as $appointmentKey) {
            /** @var Appointment $appointment */
            $appointment = $appointments->getItem($appointmentKey);

            $minimumCancelTime = DateTimeService::getCustomDateTimeObject(
                $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
            )->modify("-{$generalSettings['minimumTimeRequirementPriorToCanceling']} seconds");

            $date = $appointment->getBookingStart()->getValue()->format('Y-m-d');

            $groupedAppointments[$date]['date'] = $date;
            $groupedAppointments[$date]['appointments'][] = array_merge(
                $appointment->toArray(),
                [
                    'cancelable' => $currentDateTime <= $minimumCancelTime,
                    'past'       => $currentDateTime >= $appointment->getBookingStart()->getValue()
                ]
            );
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved appointments');
        $result->setData([
            Entities::APPOINTMENTS => $groupedAppointments,
        ]);

        return $result;
    }
}
