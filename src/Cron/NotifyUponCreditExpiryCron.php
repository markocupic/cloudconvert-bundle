<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license LGPL-3.0+
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

namespace Markocupic\CloudconvertBundle\Cron;

use CloudConvert\CloudConvert;
use CloudConvert\Models\User;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Validator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCronJob('daily')]
class NotifyUponCreditExpiryCron
{
    /**
     * @param array<string> $cloudConvertCreditExpirationNotificationEmail
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Environment $twig,
        private readonly MailerInterface $mailer,
        private readonly string $cloudConvertApiKey,
        private readonly bool $cloudConvertCreditExpirationNotificationEnabled,
        private readonly int $cloudConvertCreditExpirationNotificationLimit,
        private readonly array $cloudConvertCreditExpirationNotificationEmail,
        private readonly LoggerInterface $contaoErrorLogger,
    ) {
    }

    public function __invoke(): void
    {
        if (!$this->cloudConvertCreditExpirationNotificationEnabled || $this->cloudConvertCreditExpirationNotificationLimit < 0) {
            return;
        }

        $this->framework->initialize();

        $recipients = $this->getValidRecipients();

        if (empty($recipients)) {
            return;
        }

        $cloudConvertUser = $this->getCloudConvertUser();

        if (null === $cloudConvertUser) {
            $this->contaoErrorLogger->error('Could not establish connection to CloudConvert User API.');

            return;
        }

        if ($cloudConvertUser->getCredits() >= $this->cloudConvertCreditExpirationNotificationLimit) {
            return;
        }

        if (!$this->notify($cloudConvertUser, $recipients)) {
            $this->contaoErrorLogger->error(\sprintf('Could not send CloudConvert credit expiration notification to %s.', implode(', ', $recipients)));
        }
    }

    /**
     * @return array<string>
     */
    private function getValidRecipients(): array
    {
        $validator = $this->framework->getAdapter(Validator::class);
        $recipients = [];

        foreach (array_unique($this->cloudConvertCreditExpirationNotificationEmail) as $email) {
            if (!$validator->isEmail($email)) {
                $this->contaoErrorLogger->error(\sprintf('Invalid email "%s" set for CloudConvert credit expiration notification.', $email));

                continue;
            }

            $recipients[] = $email;
        }

        return $recipients;
    }

    /**
     * @param array<string> $recipients
     */
    private function notify(User $cloudConvertUser, array $recipients): bool
    {
        $email = (new Email())
            ->to(...$recipients)
            ->subject('CloudConvert credits have reached expiration limit')
            ->text($this->renderNotification($cloudConvertUser))
        ;

        $senderEmail = (string) $this->framework->getAdapter(Config::class)->get('adminEmail');

        if ('' !== $senderEmail) {
            $email->from($senderEmail);
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    private function getCloudConvertUser(): User|null
    {
        try {
            return (new CloudConvert([
                'api_key' => $this->cloudConvertApiKey,
            ]))->users()->me();
        } catch (\Throwable) {
            return null;
        }
    }

    private function renderNotification(User $cloudConvertUser): string
    {
        return $this->twig->render('@MarkocupicCloudconvert/expiry_notification.txt.twig', [
            'credits' => $cloudConvertUser->getCredits(),
            'username' => $cloudConvertUser->getUsername(),
            'email' => $cloudConvertUser->getEmail(),
        ]);
    }
}
