<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Application\Share;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Share;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\User\PasswordGeneratorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class ExternalShareAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'external_share';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private PasswordGeneratorInterface $passwordGenerator;

    private ResponseFactoryInterface $responseFactory;

    private FunctionCheckerInterface $functionChecker;

    private ShareCreatorInterface $shareCreator;

    public function __construct(
        RequestParserInterface $requestParser,
        ConfigContainerInterface $configContainer,
        PasswordGeneratorInterface $passwordGenerator,
        ResponseFactoryInterface $responseFactory,
        FunctionCheckerInterface $functionChecker,
        ShareCreatorInterface $shareCreator
    ) {
        $this->requestParser     = $requestParser;
        $this->configContainer   = $configContainer;
        $this->passwordGenerator = $passwordGenerator;
        $this->responseFactory   = $responseFactory;
        $this->functionChecker   = $functionChecker;
        $this->shareCreator      = $shareCreator;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }

        $user = $gatekeeper->getUser();

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) ||
            $user === null
        ) {
            throw new AccessDeniedException();
        }

        $plugin = new Plugin(Core::get_get('plugin'));
        if ($plugin->_plugin === null) {
            throw new AccessDeniedException('Access Denied - Unknown external share plugin');
        }
        $plugin->load($user);

        $type           = LibraryItemEnum::from($this->requestParser->getFromRequest('type'));
        $share_id       = $this->requestParser->getFromRequest('id');
        $secret         = $this->passwordGenerator->generate_token();
        $allow_download = ($type === LibraryItemEnum::SONG && $this->functionChecker->check(AccessFunctionEnum::FUNCTION_DOWNLOAD)) ||
            $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD);

        $share_id = $this->shareCreator->create(
            $user,
            $type,
            (int)$share_id,
            true,
            $allow_download,
            AmpConfig::get('share_expire', 7),
            $secret
        );

        $share = new Share($share_id);

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                $plugin->_plugin->external_share($share->public_url, $share->getObjectName())
            );
    }
}
