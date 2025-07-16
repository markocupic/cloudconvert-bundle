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

namespace Markocupic\CloudconvertBundle\EventListener\ContaoHook;

use CloudConvert\CloudConvert;
use CloudConvert\Exceptions\HttpClientException;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Bundle\SecurityBundle\Security;

#[AsHook('getSystemMessages')]
class GetSystemMessagesListener
{
    public function __construct(
        private readonly Security $security,
        private readonly string $cloudConvertApiKey,
        private readonly int $cloudConvertBackendAlertCreditLimit,
    ) {
    }

    public function __invoke(): string
    {
        if (0 === $this->cloudConvertBackendAlertCreditLimit) {
            return '';
        }

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return '';
        }

        try {
            $cloudConvert = new CloudConvert([
                'api_key' => $this->cloudConvertApiKey,
            ]);

            $credits = $cloudConvert->users()->me()->getCredits();
            $user = $cloudConvert->users()->me()->getUsername();
        } catch (HttpClientException) {
            return '<p class="tl_error">Could not authenticate against the CloudConvert api firewall. Please check your API token in your config/config.yaml.</p>';
        } catch (\Exception) {
            return '<p class="tl_error">An unexpected error occurred while trying to authenticate against the CloudConvert API firewall.</p>';
        }

        if ($credits < $this->cloudConvertBackendAlertCreditLimit) {
            return \sprintf(
                '<p class="tl_info">Remaining <a href="https://cloudconvert.com/dashboard" title="CloudConvert"><u>CloudConvert</u></a> credits for user "%s": %s</p>',
                $user,
                $credits,
            );
        }

        return '';
    }
}
