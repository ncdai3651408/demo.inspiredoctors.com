<?php

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\Entity\Bookable\AbstractExtra;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Duration;

/**
 * Class Extra
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class Extra extends AbstractExtra
{

    /** @var Duration */
    private $duration;

    /** @var Id */
    private $serviceId;

    /**
     * @return Duration
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param Duration $duration
     */
    public function setDuration(Duration $duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return Id
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param Id $serviceId
     */
    public function setServiceId(Id $serviceId)
    {
        $this->serviceId = $serviceId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'duration'  => $this->getDuration() ? $this->getDuration()->getValue() : null,
                'serviceId' => $this->getServiceId() ? $this->getServiceId()->getValue() : null
            ]
        );
    }
}
