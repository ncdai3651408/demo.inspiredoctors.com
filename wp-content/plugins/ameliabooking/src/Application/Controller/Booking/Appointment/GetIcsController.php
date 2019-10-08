<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\GetIcsCommand;
use AmeliaBooking\Application\Commands\Booking\Appointment\UpdateBookingStatusCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class GetIcsController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class GetIcsController extends Controller
{
    /**
     * Instantiates the Update Appointment command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetIcsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetIcsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
    }
}
