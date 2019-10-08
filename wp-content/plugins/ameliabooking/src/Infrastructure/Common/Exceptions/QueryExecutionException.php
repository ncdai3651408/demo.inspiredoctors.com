<?php

namespace AmeliaBooking\Infrastructure\Common\Exceptions;

use Exception;

/**
 * Class QueryExecutionException
 *
 * @package AmeliaBooking\Infrastructure\Common\Exceptions
 */
class QueryExecutionException extends \Exception
{
    /**
     * QueryExecutionException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct($message = 'Query Execution Error', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
