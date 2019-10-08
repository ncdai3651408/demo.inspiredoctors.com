<?php

namespace AmeliaBooking\Infrastructure\Repository\Booking\Appointment;

use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingExtraFactory;
use AmeliaBooking\Domain\Repository\Booking\Appointment\CustomerBookingExtraRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class CustomerBookingExtraRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Booking\Appointment
 */
class CustomerBookingExtraRepository extends AbstractRepository implements CustomerBookingExtraRepositoryInterface
{

    const FACTORY = CustomerBookingExtraFactory::class;

    /**
     * @param CustomerBookingExtra $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customerBookingId' => $data['customerBookingId'],
            ':extraId'           => $data['extraId'],
            ':quantity'          => $data['quantity'],
            ':price'             => $data['price'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `customerBookingId`,
                `extraId`,
                `quantity`,
                `price`
                )
                VALUES (
                :customerBookingId, 
                :extraId, 
                :quantity,
                :price
                )"
            );

            $res = $statement->execute($params);
            if (!$res) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }

            return $this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }
    }

    /**
     * @param int                  $id
     * @param CustomerBookingExtra $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':id'                => $id,
            ':customerBookingId' => $data['customerBookingId'],
            ':extraId'           => $data['extraId'],
            ':quantity'          => $data['quantity'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `customerBookingId` = :customerBookingId,
                `extraId` = :extraId,
                `quantity` = :quantity
                WHERE id = :id"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $res;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }
    }
}
