<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Payment;

use AmeliaBooking\Domain\Services\Payment\AbstractPaymentService;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Omnipay;
use Omnipay\Stripe\Gateway;

/**
 * Class StripeService
 */
class StripeService extends AbstractPaymentService implements PaymentServiceInterface
{
    /**
     *
     * @return mixed
     * @throws \Exception
     */
    private function getGateway()
    {
        /** @var Gateway $gateway */
        $gateway = Omnipay::create('Stripe');

        $stripeSettings = $this->settingsService->getSetting('payments', 'stripe');

        $gateway->initialize(
            [
                'apiKey' => $stripeSettings['testMode'] === true ?
                    $stripeSettings['testSecretKey'] : $stripeSettings['liveSecretKey']
            ]
        );

        return $gateway;
    }

    /**
     * @param array $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function execute($data)
    {
        try {
            /** @var AbstractResponse $response */
            $response = $this->getGateway()->purchase([
                'amount'   => $data['amount'],
                'currency' => $this->settingsService->getCategorySettings('payments')['currency'],
                'token'    => $data['token']
            ])->send();

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
