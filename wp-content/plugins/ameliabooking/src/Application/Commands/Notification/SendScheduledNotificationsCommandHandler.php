<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;

/**
 * Class SendScheduledNotificationsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class SendScheduledNotificationsCommandHandler extends CommandHandler
{
    /**
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function handle()
    {
        $result = new CommandResult();

        /** @var EmailNotificationService $notificationService */
        $notificationService = $this->getContainer()->get('application.emailNotification.service');
        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $this->getContainer()->get('application.smsNotification.service');

        $notificationService->sendAppointmentNextDayReminderNotifications();
        $smsNotificationService->sendAppointmentNextDayReminderNotifications();

        $notificationService->sendAppointmentFollowUpNotifications();
        $smsNotificationService->sendAppointmentFollowUpNotifications();

        $notificationService->sendBirthdayGreetingNotifications();
        $smsNotificationService->sendBirthdayGreetingNotifications();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Scheduled email notifications successfully sent');

        return $result;
    }
}
