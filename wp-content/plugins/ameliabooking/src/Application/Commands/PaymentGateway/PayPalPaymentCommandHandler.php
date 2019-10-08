<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Services\Payment\PayPalService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class PayPalPaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class PayPalPaymentCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'amount',
        'serviceId',
        'providerId',
        'couponCode',
        'bookings'
    ];

    /**
     * @param PayPalPaymentCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(PayPalPaymentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var CustomerBooking $booking */
        $booking = CustomerBookingFactory::create($command->getField('bookings')[0]);

        $service = $serviceRepository->getProviderServiceWithExtras(
            $command->getField('serviceId'),
            $command->getField('providerId')
        );

        if ($command->getField('couponCode')) {
            /** @var CouponApplicationService $couponAS */
            $couponAS = $this->container->get('application.coupon.service');

            /** @var Coupon $coupon */
            $coupon = $couponAS->processCoupon(
                $command->getField('couponCode'),
                $command->getField('serviceId'),
                true,
                $result
            );

            if ($result->getResult() === CommandResult::RESULT_ERROR) {
                return $result;
            }

            $booking->setCoupon($coupon);
        }

        $paymentAmount = $appointmentAS->getPaymentAmount($booking, $service);

        /** @var CustomerApplicationService $customerAS */
        $customerAS = $this->container->get('application.user.customer.service');

        $customerAS->getNewOrExistingCustomer($command->getField('bookings')[0]['customer'], $result);

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }


        /** @var PayPalService $paymentService */
        $paymentService = $this->container->get('infrastructure.payment.payPal.service');

        $response = $paymentService->execute(
            [
                'returnUrl' => AMELIA_ACTION_URL . '/payment/payPal/callback&status=true',
                'cancelUrl' => AMELIA_ACTION_URL . '/payment/payPal/callback&status=false',
                'amount'    => $paymentAmount,
            ]
        );

        if (!$response->isSuccessful()) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData([
                'paymentSuccessful' => false
            ]);

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setData([
            'paymentID'            => $response->getData()['id'],
            'transactionReference' => $response->getTransactionReference(),
        ]);

        return $result;
    }
}
