<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Waveform;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private RequestParserInterface $requestParser;

    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        RequestParserInterface $requestParser,
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer,
        StreamFactoryInterface $streamFactory
    ) {
        $this->requestParser   = $requestParser;
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
        if (array_key_exists('podcast_episode', $_REQUEST)) {
            $object_id   = (int)$this->requestParser->getFromRequest('podcast_episode');
            $object_type = 'podcast_episode';
            $object      = new Podcast_Episode($object_id);
        } else {
            $object_id   = (int)$this->requestParser->getFromRequest('song_id');
            $object_type = 'song';
            $object      = new Song($object_id);
        }
        $waveform = Waveform::get($object, $object_type);

        if (!$waveform) {
            return null;
        }

        return $this->responseFactory
            ->createResponse()
            ->withHeader('Cache-Control', 'public, max-age=864000') // 10 days
            ->withHeader('ETag', '"waveform-' . $object_type . '_' . $object_id . '"')
            ->withHeader(
                'Content-type',
                'image/png'
            )
            ->withBody($this->streamFactory->createStream($waveform));
    }
}
