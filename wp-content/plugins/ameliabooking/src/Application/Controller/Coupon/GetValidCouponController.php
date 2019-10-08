<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Coupon;

use AmeliaBooking\Application\Commands\Coupon\GetValidCouponCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetValidCouponController
 *
 * @package AmeliaBooking\Application\Controller\Coupon
 */
class GetValidCouponController extends Controller
{
    /**
     * Fields for coupon that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'code',
        'serviceId',
    ];

    /**
     * Instantiates the Get Coupon command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetValidCouponCommand($args);

        $params = $request->getQueryParams('params');

        if ($params['code']) {
            $command->setField('code', $params['code']);
        }

        if ($params['serviceId']) {
            $command->setField('serviceId', $params['serviceId']);
        }

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
