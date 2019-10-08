<?php

namespace AmeliaBooking\Domain\Factory\Schedule;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriod;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

class SpecialDayPeriodFactory
{
    /**
     * @param array $data
     *
     * @return SpecialDayPeriod
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $period = new SpecialDayPeriod(
            new DateTimeValue(\DateTime::createFromFormat('H:i:s', $data['startTime'])),
            new DateTimeValue(\DateTime::createFromFormat('H:i:s', $data['endTime'])),
            new Collection($data['periodServiceList'])
        );

        if (isset($data['id'])) {
            $period->setId(new Id($data['id']));
        }

        return $period;
    }
}
