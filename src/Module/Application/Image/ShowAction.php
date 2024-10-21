<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Image;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Art;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Horde_Browser;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_ACTION = 'show';

    private RequestParserInterface $requestParser;

    private AuthenticationManagerInterface $authenticationManager;

    private ConfigContainerInterface $configContainer;

    private Horde_Browser $horde_browser;

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    public function __construct(
        RequestParserInterface $requestParser,
        AuthenticationManagerInterface $authenticationManager,
        ConfigContainerInterface $configContainer,
        Horde_Browser $horde_browser,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger
    ) {
        $this->requestParser         = $requestParser;
        $this->authenticationManager = $authenticationManager;
        $this->configContainer       = $configContainer;
        $this->horde_browser         = $horde_browser;
        $this->responseFactory       = $responseFactory;
        $this->streamFactory         = $streamFactory;
        $this->logger                = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $response = $this->responseFactory->createResponse();

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USE_AUTH) === true &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::REQUIRE_SESSION) === true
        ) {
            $auth  = $this->requestParser->getFromRequest('auth');
            $user  = $this->requestParser->getFromRequest('u');
            $token = $this->requestParser->getFromRequest('t');
            $salt  = $this->requestParser->getFromRequest('s');
            // Check to see if they've got an interface session or a valid API session
            $token_check = $this->authenticationManager->tokenLogin(
                $user,
                $token,
                $salt
            );

            $cookie = $_COOKIE[AmpConfig::get('session_name')] ?? '';

            if (
                !Session::exists('interface', $cookie) &&
                !Session::exists('api', $auth) &&
                !empty($token_check)
            ) {
                $auth = ($auth !== '') ? $auth : $token;
                $this->logger->warning(
                    sprintf('Access denied, checked cookie session:%s and auth:%s', $cookie, $auth),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                return $response;
            }
        }

        // If we aren't resizing just trash thumb
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RESIZE_IMAGES) === false) {
            $_GET['thumb'] = null;
        }

        /**
         * @deprecated FIXME: Legacy stuff - should be removed after a version or so
         */
        if (!array_key_exists('object_type', $_GET)) {
            $_GET['object_type'] = (AmpConfig::get('show_song_art')) ? 'song' : 'album';
        }

        $type = Core::get_get('object_type');
        if (!Art::is_valid_type($type)) {
            $this->logger->notice(
                sprintf('INVALID TYPE: %d', $type),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return $response;
        }

        /* Decide what size this image is */
        $thumb = (int)filter_input(INPUT_GET, 'thumb', FILTER_SANITIZE_NUMBER_INT);
        $size  = Art::get_thumb_size($thumb);
        $kind  = (array_key_exists('kind', $_GET) && $_GET['kind'] == 'preview')
            ? 'preview'
            : 'default';

        $image       = '';
        $mime        = '';
        $filename    = '';
        $etag        = '';
        $typeManaged = false;
        if (array_key_exists('type', $_GET)) {
            switch ($_GET['type']) {
                case 'popup':
                    $typeManaged = true;
                    require_once Ui::find_template('show_big_art.inc.php');
                    break;
                case 'session':
                    Session::check();
                    // If we need to pull the data out of the session
                    if (array_key_exists('form', $_SESSION)) {
                        $filename    = $this->requestParser->getFromRequest('image_index');
                        $image       = Art::get_from_source($_SESSION['form']['images'][$filename], 'album');
                        $mime        = $_SESSION['form']['images'][$filename]['mime'];
                        $typeManaged = true;
                    }
                    break;
            }
        }
        if (!$typeManaged) {
            $object_id = (int)filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
            $className = ObjectTypeToClassNameMapper::map($type);
            $item      = new $className($object_id);
            if ($item instanceof Song || $item instanceof Video || $item instanceof Podcast_Episode) {
                $filename = $item->title;
            } elseif ($item instanceof Podcast) {
                $filename = $item->getTitle();
            } elseif ($item instanceof User) {
                $filename = $item->username;
            } else {
                // Album || Artist || Broadcast || Label || License || Live_Stream || Wanted
                $filename = $item->name ?? '';
            }
            if ($item instanceof Podcast_Episode) {
                $object_id = $item->podcast;
                $type      = 'podcast';
            }

            $art = new Art($object_id, $type, $kind);
            $art->has_db_info();

            $etag = $art->id;
            if (!$art->raw_mime) {
                $rootimg = sprintf(
                    '%s/../../../../public/%s/images/',
                    __DIR__,
                    $this->configContainer->getThemePath()
                );
                switch ($type) {
                    case 'video':
                    case 'tvshow':
                    case 'tvshow_season':
                        $mime       = 'image/png';
                        $defaultimg = $this->configContainer->get('custom_blankmovie');
                        if (empty($defaultimg) || (strpos($defaultimg, "http://") !== 0 && strpos($defaultimg, "https://") !== 0)) {
                            $defaultimg = $rootimg . "blankmovie.png";
                        }
                        $etag = "EmptyMediaMovie";
                        break;
                    default:
                        $mime       = 'image/png';
                        $defaultimg = $this->configContainer->get('custom_blankalbum');
                        if (empty($defaultimg) || (strpos($defaultimg, "http://") !== 0 && strpos($defaultimg, "https://") !== 0)) {
                            $defaultimg = $rootimg . "blankalbum.png";
                        }
                        $etag = "EmptyMediaAlbum";
                        break;
                }
                $image = file_get_contents($defaultimg);
            } else {
                $thumb_data = [];
                if (array_key_exists('thumb', $_GET)) {
                    $thumb_data = $art->get_thumb($size);
                    $etag .= '-' . $thumb;
                }

                $mime  = array_key_exists('thumb_mime', $thumb_data) ? $thumb_data['thumb_mime'] : $art->raw_mime;
                $image = array_key_exists('thumb', $thumb_data) ? $thumb_data['thumb'] : $art->raw;
            }
        }

        if (!empty($image)) {
            $extension = Art::extension((string)$mime);
            $filename  = scrub_out($filename . '.' . $extension);

            // Send the headers and output the image
            if (!empty($etag)) {
                $response = $response->withHeader(
                    'ETag',
                    '"' . $etag . '"'
                )->withHeader(
                    'Expires',
                    gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 7)) // 7 day
                )->withHeader(
                    'Cache-Control',
                    'private'
                )->withHeader(
                    'Last-Modified',
                    gmdate('D, d M Y H:i:s \G\M\T', time())
                );
            }

            // That means the client has a cached version of the image
            $reqheaders = getallheaders();
            if (is_array($reqheaders) && array_key_exists('If-Modified-Since', $reqheaders) && array_key_exists('If-None-Match', $reqheaders)) {
                if (!array_key_exists('Cache-Control', $reqheaders) || (array_key_exists('Cache-Control', $reqheaders) && $reqheaders['Cache-Control'] != 'no-cache')) {
                    $cetag = str_replace('"', '', $reqheaders['If-None-Match']);
                    // Same image than the cached one? Use the cache.
                    if ($cetag == $etag) {
                        return $response->withStatus(304);
                    }
                }
            }

            $headers = $this->horde_browser->getDownloadHeaders($filename, $mime, true);

            foreach ($headers as $headerName => $value) {
                $response = $response->withHeader($headerName, $value);
            }

            $response = $response->withHeader(
                'Access-Control-Allow-Origin',
                '*'
            )->withBody(
                $this->streamFactory->createStream($image)
            );
        }

        return $response;
    }
}
