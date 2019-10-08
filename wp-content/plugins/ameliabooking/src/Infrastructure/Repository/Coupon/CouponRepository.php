<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Coupon;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Domain\Repository\Coupon\CouponRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class CouponRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Coupon
 */
class CouponRepository extends AbstractRepository implements CouponRepositoryInterface
{

    const FACTORY = CouponFactory::class;

    /** @var string */
    protected $servicesTable;

    /** @var string */
    protected $couponToServicesTable;

    /** @var string */
    protected $bookingsTable;

    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $servicesTable
     * @param string     $couponToServicesTable
     * @param string     $bookingsTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $servicesTable,
        $couponToServicesTable,
        $bookingsTable
    ) {
        parent::__construct($connection, $table);

        $this->servicesTable = $servicesTable;
        $this->couponToServicesTable = $couponToServicesTable;
        $this->bookingsTable = $bookingsTable;
    }

    /**
     * @param Coupon $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':code'      => $data['code'],
            ':discount'  => $data['discount'],
            ':deduction' => $data['deduction'],
            ':limit'     => $data['limit'],
            ':status'    => $data['status'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO
                {$this->table} 
                (
                `code`, `discount`, `deduction`, `limit`, `status`  
                ) VALUES (
                :code, :discount, :deduction, :limit, :status 
                )"
            );


            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param int    $id
     * @param Coupon $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':code'      => $data['code'],
            ':discount'  => $data['discount'],
            ':deduction' => $data['deduction'],
            ':limit'     => $data['limit'],
            ':status'    => $data['status'],
            ':id'        => $id,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `code`            = :code,
                `discount`        = :discount,
                `deduction`       = :deduction,
                `limit`           = :limit,
                `status`          = :status
                WHERE
                id = :id"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__ . $e->getMessage());
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }

        return $response;
    }

    /**
     * @param int $id
     *
     * @return Coupon
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function getById($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.status AS coupon_status,
                    s.id AS service_id,
                    s.price AS service_price,
                    s.minCapacity AS service_minCapacity,
                    s.maxCapacity AS service_maxCapacity,
                    s.name AS service_name,
                    s.description AS service_description,
                    s.color AS service_color,
                    s.status AS service_status,
                    s.categoryId AS service_categoryId,
                    s.duration AS service_duration
                FROM {$this->table} c
                LEFT JOIN {$this->couponToServicesTable} cs ON cs.couponId = c.id
                LEFT JOIN {$this->servicesTable} s ON cs.serviceId = s.id
                WHERE c.id = :couponId"
            );

            $statement->bindParam(':couponId', $id);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$rows) {
            throw new NotFoundException('Data not found in ' . __CLASS__);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows)->getItem($id);
    }

    /**
     * @param array $criteria
     * @param int   $itemsPerPage
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getFiltered($criteria, $itemsPerPage)
    {
        try {
            $params = [];

            if ($itemsPerPage) {
                $params[':startingLimit'] = ($criteria['page'] - 1) * $itemsPerPage;
                $params[':itemsPerPage'] = $itemsPerPage;
            }

            $where = [];

            if (!empty($criteria['search'])) {
                $params[':search'] = "%{$criteria['search']}%";

                $where[] = 'c.code LIKE :search';
            }

            if (!empty($criteria['services'])) {
                $queryServices = [];

                foreach ((array)$criteria['services'] as $index => $value) {
                    $param = ':service' . $index;
                    $queryServices[] = $param;
                    $params[$param] = $value;
                }

                $where[] = "c.id IN (
                    SELECT couponId FROM {$this->couponToServicesTable} 
                    WHERE serviceId IN (" . implode(', ', $queryServices) . ')
                )';
            }


            $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $limit = $itemsPerPage ? ' LIMIT :startingLimit, :itemsPerPage' : '';

            $statement = $this->connection->prepare(
                "SELECT
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.status AS coupon_status
                FROM {$this->table} c
                $where
                $limit"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param array $criteria
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function getCount($criteria)
    {
        try {
            $params = [];

            $where = [];

            if (!empty($criteria['search'])) {
                $params[':search'] = "%{$criteria['search']}%";

                $where[] = 'c.code LIKE :search';
            }

            if (!empty($criteria['services'])) {
                $queryServices = [];

                foreach ((array)$criteria['services'] as $index => $value) {
                    $param = ':service' . $index;
                    $queryServices[] = $param;
                    $params[$param] = $value;
                }

                $where[] = "c.id IN (SELECT couponId FROM {$this->couponToServicesTable}
                WHERE serviceId IN (" . implode(', ', $queryServices) . '))';
            }

            $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $statement = $this->connection->prepare(
                "SELECT COUNT(*) AS count
                FROM {$this->table} c
                $where"
            );

            $statement->execute($params);

            $row = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $row;
    }

    /**
     * @param int    $id
     * @param string $status
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function updateStatusById($id, $status)
    {
        $params = [
            ':id'     => $id,
            ':status' => $status
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `status` = :status
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

    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getAllByCriteria($criteria)
    {
        try {
            $params = [];
            $where = [];

            if (!empty($criteria['serviceId'])) {
                $where[] = 'serviceId = :serviceId';
                $params[':serviceId'] = $criteria['serviceId'];
            }

            if (!empty($criteria['code'])) {
                $where[] = 'code = :code';
                $params[':code'] = $criteria['code'];
            }

            if (!empty($criteria['couponIds'])) {
                $couponIdsParams = [];

                foreach ((array)$criteria['couponIds'] as $key => $id) {
                    $couponIdsParams[":id$key"] = $id;
                }

                $where[] = '(c.id IN ( ' . implode(array_keys($couponIdsParams), ', ') . '))';

                $params = array_merge($params, $couponIdsParams);
            }

            $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $statement = $this->connection->prepare(
                "SELECT
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.status AS coupon_status,
                    s.id AS service_id,
                    s.price AS service_price,
                    s.minCapacity AS service_minCapacity,
                    s.maxCapacity AS service_maxCapacity,
                    s.name AS service_name,
                    s.description AS service_description,
                    s.color AS service_color,
                    s.status AS service_status,
                    s.categoryId AS service_categoryId,
                    s.duration AS service_duration,
                    cb.id AS booking_id
                FROM {$this->table} c
                LEFT JOIN {$this->bookingsTable} cb ON cb.couponId = c.id
                LEFT JOIN {$this->couponToServicesTable} cs ON cs.couponId = c.id
                LEFT JOIN {$this->servicesTable} s ON cs.serviceId = s.id
                $where"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }
}
