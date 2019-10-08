<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Notification;

use AmeliaBooking\Domain\Services\Notification\AbstractMailService;
use AmeliaBooking\Domain\Services\Notification\MailServiceInterface;
use Exception;

/**
 * Class WpMailService
 */
class WpMailService extends AbstractMailService implements MailServiceInterface
{

    /**
     * WpMailService constructor.
     *
     * @param        $from
     * @param        $fromName
     */
    public function __construct($from, $fromName)
    {
        parent::__construct($from, $fromName);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param      $to
     * @param      $subject
     * @param      $body
     * @param bool $attachment
     * @param bool $bcc
     *
     * @return mixed|void
     * @throws Exception
     * @SuppressWarnings(PHPMD)
     */
    public function send($to, $subject, $body, $attachment = false, $bcc = false)
    {
        wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }
}
