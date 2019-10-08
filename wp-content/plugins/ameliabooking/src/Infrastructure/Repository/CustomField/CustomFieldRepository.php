<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\CustomField;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Factory\CustomField\CustomFieldFactory;
use AmeliaBooking\Domain\Repository\CustomField\CustomFieldRepositoryInterface;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class CouponRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Coupon
 */
class CustomFieldRepository extends AbstractRepository implements CustomFieldRepositoryInterface
{

    const FACTORY = CustomFieldFactory::class;

    /** @var string */
    private $customFieldsOptionsTable;

    /** @var string */
    private $customFieldsServicesTable;

    /** @var string */
    private $servicesTable;

    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $customFieldsOptionsTable
     * @param string     $customFieldsServicesTable
     * @param string     $serviceTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $customFieldsOptionsTable,
        $customFieldsServicesTable,
        $serviceTable
    ) {
        parent::__construct($connection, $table);
        $this->customFieldsOptionsTable = $customFieldsOptionsTable;
        $this->customFieldsServicesTable = $customFieldsServicesTable;
        $this->servicesTable = $serviceTable;
    }

    /**
     * @param CustomField $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':label'    => $data['label'],
            ':type'     => $data['type'],
            ':required' => $data['required'] ?: 0,
            ':position' => $data['position'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO
                {$this->table}
                (
                `label`, `type`, `required`, `position`
                ) VALUES (
                :label, :type, :required, :position
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
     * @param int         $id
     * @param CustomField $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':label'    => $data['label'],
            ':type'     => $data['type'],
            ':required' => $data['required'] ?: 0,
            ':position' => $data['position'],
            ':id'       => $id,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `label`    = :label,
                `type`     = :type,
                `required` = :required,
                `position` = :position
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
     * @return Collection|mixed
     * @throws QueryExecutionException
     */
    public function getAll()
    {
        try {
            $statement = $this->connection->query(
                "SELECT
                    cf.id AS cf_id,
                    cf.label AS cf_label,
                    cf.type AS cf_type,
                    cf.required AS cf_required,
                    cf.position AS cf_position,
                    cfo.id AS cfo_id,
                    cfo.customFieldId AS cfo_custom_field_id,
                    cfo.label AS cfo_label,
                    cfo.position AS cfo_position,
                    s.id AS s_id,
                    s.name AS s_name,
                    s.description AS s_description,
                    s.color AS s_color,
                    s.price AS s_price,
                    s.status AS s_status,
                    s.categoryId AS s_categoryId,
                    s.minCapacity AS s_minCapacity,
                    s.maxCapacity AS s_maxCapacity,
                    s.duration AS s_duration
                FROM {$this->table} cf
                LEFT JOIN {$this->customFieldsOptionsTable} cfo ON cfo.customFieldId = cf.id
                LEFT JOIN {$this->customFieldsServicesTable} cfs ON cfs.customFieldId = cf.id
                LEFT JOIN {$this->servicesTable} s ON s.id = cfs.serviceId
                ORDER BY cf.position, cfo.position, cf.position, s.name"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }
}
