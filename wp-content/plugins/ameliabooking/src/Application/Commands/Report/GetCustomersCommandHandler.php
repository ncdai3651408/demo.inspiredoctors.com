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
use AmeliaBooking\Domain\Services\Report\AbstractReportService;
use AmeliaBooking\Domain\Services\Report\ReportDomainService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;

/**
 * Class GetCustomersCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Report
 */
class GetCustomersCommandHandler extends CommandHandler
{
    /**
     * @param GetCustomersCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetCustomersCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::CUSTOMERS)) {
            throw new AccessDeniedException('You are not allowed to read customers.');
        }

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');
        /** @var AbstractReportService $reportService */
        $reportService = $this->container->get('infrastructure.report.csv.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $customers = $customerRepository->getFiltered($command->getField('params'), null);

        $rows = [];

        $fields = $command->getField('params')['fields'];
        $delimiter = $command->getField('params')['delimiter'];

        $dateFormat = $settingsDS->getSetting('wordpress', 'dateFormat');
        $timeFormat = $settingsDS->getSetting('wordpress', 'timeFormat');

        foreach ($customers as $customer) {
            $row = [];

            if (in_array('firstName', $fields, true)) {
                $row['First Name'] = $customer['firstName'];
            }

            if (in_array('lastName', $fields, true)) {
                $row['Last Name'] = $customer['lastName'];
            }

            if (in_array('email', $fields, true)) {
                $row['Email'] = $customer['email'];
            }

            if (in_array('phone', $fields, true)) {
                $row['Phone'] = $customer['phone'];
            }

            if (in_array('gender', $fields, true)) {
                $row['Gender'] = $customer['gender'];
            }

            if (in_array('birthday', $fields, true)) {
                $row['Date of Birth'] = DateTimeService::getCustomDateTimeObject($customer['birthday'])
                    ->format($dateFormat);
            }

            if (in_array('note', $fields, true)) {
                $row['Note'] = $customer['note'];
            }

            if (in_array('lastAppointment', $fields, true)) {
                $row['Last Appointment'] = DateTimeService::getCustomDateTimeObject($customer['lastAppointment'])
                    ->format($dateFormat . ' ' . $timeFormat);
            }

            if (in_array('totalAppointments', $fields, true)) {
                $row['Total Appointments'] = $customer['totalAppointments'];
            }

            if (in_array('pendingAppointments', $fields, true)) {
                $row['Pending Appointments'] = $customer['countPendingAppointments'];
            }

            $rows[] = $row;
        }

        $reportService->generateReport($rows, Entities::CUSTOMERS, $delimiter);

        $result->setAttachment(true);

        return $result;
    }
}
