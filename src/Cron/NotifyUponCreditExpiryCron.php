<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license LGPL-3.0+
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

namespace Markocupic\CloudconvertBundle\Cron;

use CloudConvert\CloudConvert;
use CloudConvert\Models\User;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Email;
use Contao\System;
use Contao\Validator;
use Http\Client\Exception;
use Psr\Log\LoggerInterface;
use Twig\Environment;

#[AsCronJob('minutely')]
class NotifyUponCreditExpiryCron
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Environment $twig,
        private readonly string $cloudConvertApiKey,
        private readonly LoggerInterface $contaoErrorLogger,
    ) {
    }

    public function __invoke(): void
    {
        $this->framework->initialize();

        $enabled = System::getContainer()->getParameter('markocupic_cloudconvert.credit_expiration_notification.enabled');
        $limit = System::getContainer()->getParameter('markocupic_cloudconvert.credit_expiration_notification.limit');
        $arrRecipientEmail = System::getContainer()->getParameter('markocupic_cloudconvert.credit_expiration_notification.email');

        if (true === $enabled && $limit >= 0 && !empty($arrRecipientEmail)) {
            $arrRecipientEmail = array_map(
                function ($email): string {
                    if (!Validator::isEmail($email)) {
                        $this->contaoErrorLogger->error(sprintf('Invalid email "%s" set for CloudConvert credit expiration notification.', $email));
                        $email = '';
                    }

                    return $email;
                },
                $arrRecipientEmail
            );

            $arrRecipientEmail = array_filter(array_unique($arrRecipientEmail));

            $cloudConvUser = $this->getCloudConvertUser();

            if (null === $cloudConvUser) {
                $this->contaoErrorLogger->error('Could not establish connection to CloudConvert User API.');
            }

            $credits = $cloudConvUser->getCredits();

            if ($credits < $limit) {
                if (!$this->notify($cloudConvUser, $arrRecipientEmail)) {
                    $this->contaoErrorLogger->error(sprintf('Could not send CloudConvert credit expiration notification to %s.', implode(', ', $arrRecipientEmail)));
                }
            }
        }
    }

    private function notify(User $cloudConvUser, array $arrRecipientEmail): bool
    {
        $email = new Email();

        $email->subject = 'CloudConvert credits have reached expiration limit';

        $email->text = $this->renderNotification($cloudConvUser);

        try {
            return $email->sendTo(...$arrRecipientEmail);
        } catch (\Exception) {
            return false;
        }
    }

    private function getCloudConvertUser(): User|null
    {
        try {
            return (new CloudConvert([
                'api_key' => $this->cloudConvertApiKey,
            ]))->users()->me();
        } catch (Exception) {
            return null;
        }
    }

    private function renderNotification(User $cloudConvUser): string
    {
        return $this->twig->render(
            '@MarkocupicCloudconvert/expiry_notification.txt.twig',
            [
                'credits' => $cloudConvUser->getCredits(),
                'username' => $cloudConvUser->getUsername(),
                'email' => $cloudConvUser->getEmail(),
            ]
        );
    }
}
