<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\Translations\NotificationsStrings;

/**
 * Class NotificationsTableInsertRows
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification
 */
class NotificationsTableInsertRows extends AbstractDatabaseTable
{

    const TABLE = 'notifications';

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        global $wpdb;

        $table = self::getTableName();
        $rows = [];

        $addEmail = !(int)$wpdb->get_row("SELECT COUNT(*) AS count FROM {$table} WHERE type = 'email'")->count;

        if ($addEmail) {
            $rows = array_merge($rows, NotificationsStrings::getCustomerNonTimeBasedEmailNotifications());
            $rows = array_merge($rows, NotificationsStrings::getCustomerTimeBasedEmailNotifications());
            $rows = array_merge($rows, NotificationsStrings::getProviderNonTimeBasedEmailNotifications());
            $rows = array_merge($rows, NotificationsStrings::getProviderTimeBasedEmailNotifications());
        }

        $addSMS = !(int)$wpdb->get_row("SELECT COUNT(*) AS count FROM {$table} WHERE type = 'sms'")->count;

        if ($addSMS) {
            $rows = array_merge($rows, NotificationsStrings::getCustomerNonTimeBasedSMSNotifications());
            $rows = array_merge($rows, NotificationsStrings::getCustomerTimeBasedSMSNotifications());
            $rows = array_merge($rows, NotificationsStrings::getProviderNonTimeBasedSMSNotifications());
            $rows = array_merge($rows, NotificationsStrings::getProviderTimeBasedSMSNotifications());
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = "INSERT INTO {$table} 
                        (
                            `name`,
                            `type`,
                            `time`,
                            `timeBefore`,
                            `timeAfter`,
                            `sendTo`,
                            `subject`,
                            `content`
                        ) 
                        VALUES
                        (
                            '{$row['name']}',
                            '{$row['type']}',
                             {$row['time']},
                             {$row['timeBefore']},
                             {$row['timeAfter']},
                            '{$row['sendTo']}',
                            '{$row['subject']}',
                            '{$row['content']}'
                        )";
        }

        return $result;
    }
}
