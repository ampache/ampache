<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Application\LostPassword;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Form\LostPasswordFormView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private ConfigContainerInterface $configContainer,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $allowLostPassword = $this->configContainer->get(ConfigurationKeyEnum::ALLOW_LOST_PASSWORD);
        if (
            !Mailer::is_mail_enabled()
            || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)
            || ($allowLostPassword !== null && !make_bool($allowLostPassword))
        ) {
            throw new AccessDeniedException();
        }

        $_SESSION['login'] = true;
        $language          = (string) AmpConfig::get('lang', 'en_US');
        $userAgent         = Core::get_server('HTTP_USER_AGENT');
        $logoUrl           = (string) AmpConfig::get('custom_login_logo');

        echo new LostPasswordFormView(
            AmpConfig::get_web_path(),
            str_replace('_', '-', $language),
            is_rtl($language) ? 'rtl' : 'ltr',
            (string) AmpConfig::get('site_charset', 'UTF-8'),
            (string) AmpConfig::get('site_title'),
            ($logoUrl !== '') ? $logoUrl : Ui::get_logo_url(),
            str_contains($userAgent, 'Mobile') && (str_contains($userAgent, 'Android') || str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad'))
        )->render();

        return null;
    }
}
