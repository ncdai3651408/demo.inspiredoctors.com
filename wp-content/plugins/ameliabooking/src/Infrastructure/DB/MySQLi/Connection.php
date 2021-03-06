<?php

namespace AmeliaBooking\Infrastructure\DB\MySQLi;

use mysqli;

/**
 * Class Connection
 *
 * @package AmeliaBooking\Infrastructure\DB\MySQLi
 */
class Connection extends \AmeliaBooking\Infrastructure\Connection
{
    /** @var Statement $statement */
    public $statement;

    /** @var Result $result */
    private $result;

    /** @var Query $query */
    private $query;

    /** @var mysqli $mysqli */
    private $mysqli;

    /**
     * Connection constructor.
     *
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int    $port
     * @param string $charset
     */
    public function __construct(
        $host,
        $database,
        $username,
        $password,
        $charset = 'utf8',
        $port = 3306
    ) {
        parent::__construct(
            $host,
            $database,
            $username,
            $password,
            $charset,
            $port
        );

        $this->socketHandler();

        $this->result = new Result();
        $this->query = new Query();
        $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database, $this->port);

        $this->mysqli->set_charset($this->charset);

        $this->statement = new Statement($this->mysqli, $this->result, $this->query);

        $this->handler = $this;
    }

    /**
     * @param string $query
     *
     * @return mixed
     */
    public function query($query)
    {
        $this->result->setValue($this->mysqli->query($query));

        return $this->statement;
    }

    /**
     * @param string $query
     *
     * @return mixed
     */
    public function prepare($query)
    {
        $this->query->setValue($query);

        return $this->statement;
    }

    /**
     *
     * @return mixed
     */
    public function lastInsertId()
    {
        return $this->mysqli->insert_id;
    }

    /**
     *
     * @return mixed
     */
    public function beginTransaction()
    {
        return $this->mysqli->begin_transaction();
    }

    /**
     * @return bool
     */
    public function commit()
    {
        return $this->mysqli->commit();
    }

    /**
     * @return bool
     */
    public function rollback()
    {
        return $this->mysqli->rollback();
    }
}
