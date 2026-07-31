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

namespace Ampache\Module\Application\Login;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authentication\Oidc\Exception\OidcException;
use Ampache\Module\Authentication\Oidc\OidcAuthenticationService;
use Ampache\Module\Authentication\Oidc\OidcAuthenticationServiceInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Preference;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class OidcAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'oidc';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private LoggerInterface $logger,
        private NetworkCheckerInterface $networkChecker,
        private OidcAuthenticationServiceInterface $oidcAuthenticationService,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $authMethods = $this->configContainer->get(ConfigurationKeyEnum::AUTH_METHODS);
        if (!is_array($authMethods) || !in_array(OidcAuthenticationService::AUTH_TYPE, $authMethods, true)) {
            throw new AccessDeniedException('Access denied: OpenID Connect authentication is not enabled');
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ACCESS_CONTROL) && !$this->networkChecker->check(AccessTypeEnum::INTERFACE, null, AccessLevelEnum::GUEST)) {
            throw new AccessDeniedException(
                sprintf(
                    'Access denied: %s is not in the Interface Access list',
                    Core::get_user_ip()
                )
            );
        }

        Session::create_cookie();
        Preference::init();

        Session::create(['type' => 'oidc_pending']);

        try {
            // sends a Location header and terminates the request
            $this->oidcAuthenticationService->redirectToProvider(
                (string) ($request->getQueryParams()['referrer'] ?? Core::get_server('HTTP_REFERER'))
            );
        } catch (OidcException $error) {
            $this->logger->error(
                $error->getMessage(),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            AmpError::add('general', T_('OpenID Connect is not configured correctly'));

            $this->ui->show('show_login_form.inc.php');
        }

        return null;
    }
}
