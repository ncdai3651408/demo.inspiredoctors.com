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
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;

/**
 * Class GetPaymentsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Report
 */
class GetPaymentsCommandHandler extends CommandHandler
{
    /**
     * @param GetPaymentsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetPaymentsCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::FINANCE)) {
            throw new AccessDeniedException('You are not allowed to read payments.');
        }

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');
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

        $payments = $paymentRepository->getFiltered($params, 0);

        $rows = [];

        $dateFormat = $settingsDS->getSetting('wordpress', 'dateFormat');
        $timeFormat = $settingsDS->getSetting('wordpress', 'timeFormat');

        foreach ($payments as $payment) {
            $row = [];

            if (in_array('service', $params['fields'], true)) {
                $row['Service'] = $payment['serviceName'];
            }

            if (in_array('bookingStart', $params['fields'], true)) {
                $row['Booking Start'] = DateTimeService::getCustomDateTimeObject($payment['bookingStart'])
                    ->format($dateFormat . ' ' . $timeFormat);
            }

            if (in_array('customer', $params['fields'], true)) {
                $row['Customer'] = $payment['customerFirstName'] . ' ' . $payment['customerLastName'];
            }

            if (in_array('customerEmail', $params['fields'], true)) {
                $row['Customer Email'] = $payment['customerEmail'];
            }

            if (in_array('employee', $params['fields'], true)) {
                $row['Employee'] = $payment['providerFirstName'] . ' ' . $payment['providerLastName'];
            }

            if (in_array('employeeEmail', $params['fields'], true)) {
                $row['Employee Email'] = $payment['providerEmail'];
            }

            if (in_array('amount', $params['fields'], true)) {
                $row['Amount'] = $payment['amount'];
            }

            if (in_array('type', $params['fields'], true)) {
                $row['Type'] = $payment['gateway'];
            }

            if (in_array('status', $params['fields'], true)) {
                $row['Status'] = $payment['status'];
            }

            if (in_array('paymentDate', $params['fields'], true)) {
                $row['Payment Date'] = DateTimeService::getCustomDateTimeObject($payment['dateTime'])
                    ->format($dateFormat . ' ' . $timeFormat);
            }

            $rows[] = $row;
        }

        $reportService->generateReport($rows, Entities::PAYMENTS, $params['delimiter']);

        $result->setAttachment(true);

        return $result;
    }
}
