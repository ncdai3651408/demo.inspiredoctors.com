<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Notification;

use AmeliaBooking\Domain\Services\Notification\AbstractMailService;
use AmeliaBooking\Domain\Services\Notification\MailServiceInterface;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class SMTPService
 */
class SMTPService extends AbstractMailService implements MailServiceInterface
{
    /** @var string */
    private $host;

    /** @var string */
    private $port;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $secure;

    /**
     * SMTPService constructor.
     *
     * @param        $from
     * @param        $fromName
     * @param string $host
     * @param string $port
     * @param string $secure
     * @param string $username
     * @param string $password
     */
    public function __construct($from, $fromName, $host, $port, $secure, $username, $password)
    {
        parent::__construct($from, $fromName);
        $this->host = $host;
        $this->port = $port;
        $this->secure = $secure;
        $this->username = $username;
        $this->password = $password;
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
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = $this->secure;
            $mail->Host = $this->host;
            $mail->Port = $this->port;
            $mail->Username = $this->username;
            $mail->Password = $this->password;

            //Recipients
            $mail->setFrom($this->from, $this->fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($this->from);
            if ($bcc) {
                $mail->addBCC($bcc);
            }

            //Attachments
            if ($attachment) {
                $mail->addAttachment($attachment);
            }

            //Content
            $mail->CharSet = 'UTF-8';
            $mail->isHTML();
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
        } catch (Exception $e) {
            throw $e;
        }
    }
}
