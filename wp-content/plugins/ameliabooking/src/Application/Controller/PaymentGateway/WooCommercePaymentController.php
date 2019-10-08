<?php

namespace AmeliaBooking\Application\Controller\PaymentGateway;

use AmeliaBooking\Application\Commands\PaymentGateway\WooCommercePaymentCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class WooCommercePaymentController
 *
 * @package AmeliaBooking\Application\Controller\PaymentGateway
 */
class WooCommercePaymentController extends Controller
{
    /**
     * Fields for WooCommerce payment that can be received from API
     *
     * @var array
     */
    protected $allowedFields = [
        'bookings',
        'bookingStart',
        'notifyParticipants',
        'serviceId',
        'providerId',
        'couponCode',
        'payment'
    ];

    /**
     * Instantiates the WooCommerce Payment Callback command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return WooCommercePaymentCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new WooCommercePaymentCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
