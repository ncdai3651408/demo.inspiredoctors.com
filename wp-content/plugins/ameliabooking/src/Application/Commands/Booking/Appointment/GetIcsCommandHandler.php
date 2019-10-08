<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;

/**
 * Class GetIcsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetIcsCommandHandler extends CommandHandler
{
    /**
     * @param GetIcsCommand $command
     *
     * @return CommandResult
     * @throws \UnexpectedValueException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     */
    public function handle(GetIcsCommand $command)
    {
        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        $appointment = $appointmentRepo->getById((int)$command->getField('id'));

        $service = $serviceRepository
            ->getByCriteria(['services' => [$appointment->getServiceId()->getValue()]])
            ->getItem($appointment->getServiceId()->getValue());

        if (!$appointment instanceof Appointment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get appointment');

            return $result;
        }

        $vCalendar = new Calendar(AMELIA_URL);

        $vEvent = new Event();

        $vEvent
            ->setDtStart(
                DateTimeService::getCustomDateTimeObjectInUtc(
                    $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                )
            )
            ->setDtEnd(
                DateTimeService::getCustomDateTimeObjectInUtc(
                    $appointment->getBookingEnd()->getValue()->format('Y-m-d H:i:s')
                )
            )
            ->setSummary($service->getName()->getValue());

        $vCalendar->addComponent($vEvent);

        $result->setAttachment(true);

        $result->setIcs(true);

        echo $vCalendar->render();

        return $result;
    }
}
