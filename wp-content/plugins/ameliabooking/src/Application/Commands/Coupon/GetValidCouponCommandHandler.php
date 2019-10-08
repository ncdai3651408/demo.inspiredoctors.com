<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Coupon;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;

/**
 * Class GetValidCouponCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Coupon
 */
class GetValidCouponCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'code',
        'serviceId'
    ];

    /**
     * @param GetValidCouponCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     */
    public function handle(GetValidCouponCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var CouponApplicationService $couponAS */
        $couponAS = $this->container->get('application.coupon.service');

        /** @var Coupon $coupon */
        $coupon = $couponAS->processCoupon(
            $command->getField('code'),
            $command->getField('serviceId'),
            true,
            $result
        );

        if ($coupon) {
            $coupon = $coupon->toArray();
            unset($coupon['serviceList']);
        }

        if ($result->getResult() !== CommandResult::RESULT_ERROR) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully retrieved coupon.');
            $result->setData(
                [
                    Entities::COUPON => $coupon,
                ]
            );
        }

        return $result;
    }
}
