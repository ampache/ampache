<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Module\Application\Share;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Share;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\User\PasswordGenerator;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class ExternalShareAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'external_share';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private PasswordGeneratorInterface $passwordGenerator;

    private ResponseFactoryInterface $responseFactory;

    private FunctionCheckerInterface $functionChecker;

    public function __construct(
        RequestParserInterface $requestParser,
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        PasswordGeneratorInterface $passwordGenerator,
        ResponseFactoryInterface $responseFactory,
        FunctionCheckerInterface $functionChecker
    ) {
        $this->requestParser     = $requestParser;
        $this->configContainer   = $configContainer;
        $this->ui                = $ui;
        $this->passwordGenerator = $passwordGenerator;
        $this->responseFactory   = $responseFactory;
        $this->functionChecker   = $functionChecker;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            throw new AccessDeniedException();
        }

        $plugin = new Plugin(Core::get_get('plugin'));
        if (!$plugin) {
            throw new AccessDeniedException('Access Denied - Unknown external share plugin');
        }
        $plugin->load(Core::get_global('user'));

        $type           = $this->requestParser->getFromRequest('type');
        $share_id       = $this->requestParser->getFromRequest('id');
        $secret         = $this->passwordGenerator->generate(PasswordGenerator::DEFAULT_LENGTH);
        $allow_download = ($type == 'song' && $this->functionChecker->check(AccessLevelEnum::FUNCTION_DOWNLOAD)) ||
            $this->functionChecker->check(AccessLevelEnum::FUNCTION_BATCH_DOWNLOAD);

        $share_id = Share::create_share($type, $share_id, true, $allow_download, AmpConfig::get('share_expire'), $secret);
        $share    = new Share($share_id);

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                $plugin->_plugin->external_share($share->public_url, $share->getObjectName())
            );
    }
}
