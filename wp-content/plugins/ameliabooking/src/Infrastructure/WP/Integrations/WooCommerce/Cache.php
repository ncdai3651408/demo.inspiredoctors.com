<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce;

/**
 * Class Cache
 *
 * @package AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce
 */
class Cache
{
    /** @var array */
    protected static $cache = [];

    /**
     * Add entities to cache.
     *
     * @param array $data
     */
    public static function add($data)
    {
        self::$cache = $data;
    }

    /**
     * Get entity from cache
     *
     * @param int $providerId
     * @param int $serviceId
     *
     * @return mixed
     */
    public static function get($providerId, $serviceId)
    {
        return array_key_exists($providerId, self::$cache) && array_key_exists($serviceId, self::$cache[$providerId]) ?
            self::$cache[$providerId][$serviceId] : null;
    }

    /**
     * Get entity from cache
     *
     * @return mixed
     */
    public static function getAll()
    {
        return self::$cache;
    }
}
