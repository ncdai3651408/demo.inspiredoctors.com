<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class WooCommercePaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class WooCommercePaymentCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'bookings',
        'bookingStart',
        'notifyParticipants',
        'serviceId',
        'providerId',
        'couponCode',
        'payment'
    ];

    /**
     * @param WooCommercePaymentCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function handle(WooCommercePaymentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        WooCommerceService::setContainer($this->container);

        $appointmentData = $bookingAS->getAppointmentData($command->getFields());

        $bookingData = $bookingAS->processBooking($result, $appointmentRepo, $appointmentData, true, true, false);

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }

        /** @var Appointment $appointment */
        $appointment = $bookingData['appointment'];

        /** @var CustomerBooking $booking */
        $booking = $bookingData['booking'];

        /** @var Service $service */
        $service = $bookingData['service'];

        $amelia = [
            'serviceId'          => $service->getId()->getValue(),
            'providerId'         => $appointment->getProviderId()->getValue(),
            'couponId'           => $booking->getCoupon() ? $booking->getCoupon()->getId()->getValue() : '',
            'bookingStart'       => $appointment->getBookingStart()->getValue()->format('Y-m-d H:i'),
            'notifyParticipants' => $appointment->isNotifyParticipants(),
            'bookings'           => [
                [
                    'customerId'   => $booking->getCustomer()->getId() ?
                        $booking->getCustomer()->getId()->getValue() : null,
                    'customer'     => [
                        'email'      => $booking->getCustomer()->getEmail()->getValue(),
                        'externalId' => $booking->getCustomer()->getExternalId() ?
                            $booking->getCustomer()->getExternalId()->getValue() : null,
                        'firstName'  => $booking->getCustomer()->getFirstName()->getValue(),
                        'id'         => $booking->getCustomer()->getId()
                            ? $booking->getCustomer()->getId()->getValue() : null,
                        'lastName'   => $booking->getCustomer()->getLastName()->getValue(),
                        'phone'      => $booking->getCustomer()->getPhone()->getValue()
                    ],
                    'persons'      => $booking->getPersons()->getValue(),
                    'extras'       => [],
                    'status'       => $booking->getStatus()->getValue(),
                    'utcOffset'    => $booking->getUtcOffest() ? $booking->getUtcOffest()->getValue() : null,
                    'customFields' => $booking->getCustomFields() ? $booking->getCustomFields()->getValue() : null
                ]
            ],
            'payment'            => [
                'gateway' => $command->getFields()['payment']['gateway']
            ],
            'serviceName'        => $service->getName()->getValue(),
            'couponCode'         => $booking->getCoupon() ? $booking->getCoupon()->getCode()->getValue() : ''
        ];

        foreach ($booking->getExtras()->keys() as $extraKey) {
            /** @var CustomerBookingExtra $bookingExtra */
            $bookingExtra = $booking->getExtras()->getItem($extraKey);

            $amelia['bookings'][0]['extras'][] = [
                'extraId'  => $bookingExtra->getExtraId()->getValue(),
                'quantity' => $bookingExtra->getQuantity()->getValue()
            ];
        }

        try {
            WooCommerceService::addToCart($amelia);
        } catch (\Exception $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['wc_error']);
            $result->setData([
                'wooCommerceError' => true
            ]);

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Proceed to WooCommerce Cart');
        $result->setData([
            'cartUrl' => WooCommerceService::getCartUrl()
        ]);

        return $result;
    }
}