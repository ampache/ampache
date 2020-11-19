<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=1);

namespace Ampache\Module\Application\Waveform;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\Waveform;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';
    
    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer,
        StreamFactoryInterface $streamFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->configContainer = $configContainer;
        $this->streamFactory   = $streamFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::WAVEFORM) === false) {
            return null;
        }

        // Prevent user from aborting script
        ignore_user_abort(true);
        set_time_limit(300);

        // Write/close session data to release session lock for this script.
        // This to allow other pages from the same session to be processed
        // Warning: Do not change any session variable after this call
        session_write_close();

        $id       = (int) $_REQUEST['song_id'];
        $waveform = Waveform::get($id);
        
        if (!$waveform) {
            return null;
        }
        
        return $this->responseFactory
            ->createResponse()
            ->withHeader(
                'Content-type',
                'image/png'
            )
            ->withBody($this->streamFactory->createStream($waveform));
    }
}
