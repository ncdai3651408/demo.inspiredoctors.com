<?php

namespace AmeliaBooking\Application\Services\Coupon;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponServiceRepository;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class CouponApplicationService
 *
 * @package AmeliaBooking\Application\Services\Coupon
 */
class CouponApplicationService
{
    private $container;

    /**
     * CouponApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }


    /**
     * @param Coupon $coupon
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function add($coupon)
    {
        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');

        /** @var CouponServiceRepository $couponServiceRepository */
        $couponServiceRepo = $this->container->get('domain.coupon.service.repository');

        $couponId = $couponRepository->add($coupon);

        $coupon->setId(new Id($couponId));

        /**
         * Add coupon services
         */
        foreach ((array)$coupon->getServiceList()->keys() as $key) {
            if (!($service = $coupon->getServiceList()->getItem($key)) instanceof Service) {
                throw new InvalidArgumentException('Unknown type');
            }

            $couponServiceRepo->add($coupon, $service);
        }

        return $couponId;
    }

    /**
     * @param Coupon $oldCoupon
     * @param Coupon $newCoupon
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function update($oldCoupon, $newCoupon)
    {
        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');

        /** @var CouponServiceRepository $couponServiceRepository */
        $couponServiceRepo = $this->container->get('domain.coupon.service.repository');

        /**
         * Update coupon
         */
        $couponRepository->update($oldCoupon->getId()->getValue(), $newCoupon);


        $oldServiceIds = [];
        $newServiceIds = [];

        foreach ((array)$newCoupon->getServiceList()->keys() as $serviceKey) {
            if (!($newService = $newCoupon->getServiceList()->getItem($serviceKey)) instanceof Service) {
                throw new InvalidArgumentException('Unknown type');
            }

            $newServiceIds[] = $newService->getId()->getValue();
        }

        foreach ((array)$oldCoupon->getServiceList()->keys() as $serviceKey) {
            if (!($oldService = $oldCoupon->getServiceList()->getItem($serviceKey)) instanceof Service) {
                throw new InvalidArgumentException('Unknown type');
            }

            $oldServiceIds[] = $oldService->getId()->getValue();
        }


        /**
         * Manage coupon services
         */
        foreach ((array)$newCoupon->getServiceList()->keys() as $key) {
            if (!($newService = $newCoupon->getServiceList()->getItem($key)) instanceof Service) {
                throw new InvalidArgumentException('Unknown type');
            }

            if (!in_array($newService->getId()->getValue(), $oldServiceIds, true)) {
                $couponServiceRepo->add($newCoupon, $newService);
            }
        }

        foreach ((array)$oldCoupon->getServiceList()->keys() as $key) {
            if (!($oldService = $oldCoupon->getServiceList()->getItem($key)) instanceof Service) {
                throw new InvalidArgumentException('Unknown type');
            }

            if (!in_array($oldCoupon->getServiceList()->getItem($key)->getId()->getValue(), $newServiceIds, true)) {
                $couponServiceRepo->deleteForService($oldCoupon->getId()->getValue(), $oldService->getId()->getValue());
            }
        }

        return true;
    }

    /**
     * @param string        $couponCode
     * @param int           $serviceId
     * @param bool          $inspectCoupon
     * @param CommandResult $result
     *
     * @return Coupon
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function processCoupon($couponCode, $serviceId, $inspectCoupon, $result)
    {
        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');

        $coupons = $couponRepository->getAllByCriteria([
            'code' => $couponCode
        ]);

        $coupon = $coupons->length() ? $coupons->getItem($coupons->keys()[0]) : null;

        if (!$coupon || !$coupon->getServiceList()->keyExists($serviceId)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['coupon_unknown']);
            $result->setData([
                'couponUnknown' => true
            ]);

            return $coupon;
        }

        if ($inspectCoupon && ($coupon->getStatus()->getValue() === 'hidden' ||
                ($coupon && $coupon->getUsed()->getValue() >= $coupon->getLimit()->getValue())
            )) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['coupon_invalid']);
            $result->setData([
                'couponInvalid' => true
            ]);
        }

        return $coupon;
    }
}
