<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Report;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Report\ReportServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;

/**
 * Class GetCustomersCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Report
 */
class GetAppointmentsCommandHandler extends CommandHandler
{
    /**
     * @param GetAppointmentsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetAppointmentsCommand $command)
    {
        $currentUser = $this->getContainer()->get('logged.in.user');

        if (!$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to read appointments.');
        }

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var ReportServiceInterface $reportService */
        $reportService = $this->container->get('infrastructure.report.csv.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $params = $command->getField('params');

        if ($params['dates']) {
            $params['dates'][0] .= ' 00:00:00';
            $params['dates'][1] .= ' 23:59:59';
        }

        switch ($currentUser->getType()) {
            case 'customer':
                $params['customerId'] = $currentUser->getId()->getValue();
                break;
            case 'provider':
                $params['providers'] = [$currentUser->getId()->getValue()];
                break;
        }

        $appointments = $appointmentRepo->getFiltered($params);

        $rows = [];

        $dateFormat = $settingsDS->getSetting('wordpress', 'dateFormat');
        $timeFormat = $settingsDS->getSetting('wordpress', 'timeFormat');

        foreach ($appointments->toArray() as $appointment) {
            $row = [];

            $customers = [];

            foreach ((array)$appointment['bookings'] as $booking) {
                $customers[] = $booking['customer']['firstName'] . ' ' . $booking['customer']['lastName'];
            }

            if (in_array('customers', $params['fields'], true)) {
                $row['Customers'] = implode(', ', $customers);
            }

            if (in_array('employee', $params['fields'], true)) {
                $row['Employee'] = $appointment['provider']['firstName'] . ' ' . $appointment['provider']['lastName'];
            }

            if (in_array('service', $params['fields'], true)) {
                $row['Service'] = $appointment['service']['name'];
            }

            if (in_array('startTime', $params['fields'], true)) {
                $row['Start Time'] = DateTimeService::getCustomDateTimeObject($appointment['bookingStart'])
                    ->format($dateFormat . ' ' . $timeFormat);
            }

            if (in_array('endTime', $params['fields'], true)) {
                $row['End Time'] = DateTimeService::getCustomDateTimeObject($appointment['bookingEnd'])
                    ->format($dateFormat . ' ' . $timeFormat);
            }

            if (in_array('note', $params['fields'], true)) {
                $row['Note'] = $appointment['internalNotes'];
            }

            if (in_array('status', $params['fields'], true)) {
                $row['Status'] = ucfirst($appointment['status']);
            }

            $rows[] = $row;
        }

        $reportService->generateReport($rows, Entities::APPOINTMENT . 's', $params['delimiter']);

        $result->setAttachment(true);

        return $result;
    }
}
