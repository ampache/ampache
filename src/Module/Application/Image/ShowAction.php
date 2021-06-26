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

declare(strict_types=0);

namespace Ampache\Module\Application\Image;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_ACTION = 'show';

    private AuthenticationManagerInterface $authenticationManager;

    private ConfigContainerInterface $configContainer;

    private Horde_Browser $horde_browser;

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    public function __construct(
        AuthenticationManagerInterface $authenticationManager,
        ConfigContainerInterface $configContainer,
        Horde_Browser $horde_browser,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger
    ) {
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
            // Check to see if they've got an interface session or a valid API session
            $token_check = $this->authenticationManager->tokenLogin(
                Core::get_request('u'),
                Core::get_request('t'),
                Core::get_request('s')
            );

            $cookie = $_COOKIE[AmpConfig::get('session_name')];

            if (
                !Session::exists('interface', $cookie) &&
                !Session::exists('api', Core::get_request('auth')) &&
                !empty($token_check)
            ) {
                $auth = (Core::get_request('auth') !== '') ? Core::get_request('auth') : Core::get_request('t');
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
        if (!filter_has_var(INPUT_GET, 'object_type')) {
            $_GET['object_type'] = (AmpConfig::get('show_song_art')) ? 'song' : 'album';
        }

        $type = Core::get_get('object_type');
        if (!Art::is_valid_type($type)) {
            debug_event('image', 'INVALID TYPE: ' . $type, 4);

            return $response;
        }

        /* Decide what size this image is */
        $size = Art::get_thumb_size(
            filter_input(INPUT_GET, 'thumb', FILTER_SANITIZE_NUMBER_INT)
        );
        $kind = filter_has_var(INPUT_GET, 'kind') ? filter_input(INPUT_GET, 'kind', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) : 'default';

        $image       = '';
        $mime        = '';
        $filename    = '';
        $etag        = '';
        $typeManaged = false;
        if (filter_has_var(INPUT_GET, 'type')) {
            switch (filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) {
                case 'popup':
                    $typeManaged = true;
                    require_once Ui::find_template('show_big_art.inc.php');
                    break;
                case 'session':
                    // If we need to pull the data out of the session
                    Session::check();
                    $filename    = scrub_in($_REQUEST['image_index']);
                    $image       = Art::get_from_source($_SESSION['form']['images'][$filename], 'album');
                    $mime        = $_SESSION['form']['images'][$filename]['mime'];
                    $typeManaged = true;
                    break;
            }
        }
        if (!$typeManaged) {
            $class_name = ObjectTypeToClassNameMapper::map($type);

            $item     = new $class_name(
                filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT)
            );
            $filename = $item->name ?: $item->title;

            $art = new Art($item->id, $type, $kind);
            $art->has_db_info();
            $etag = $art->id;

            // That means the client has a cached version of the image
            $reqheaders = getallheaders();
            if (isset($reqheaders['If-Modified-Since']) && isset($reqheaders['If-None-Match'])) {
                $ccontrol = $reqheaders['Cache-Control'];
                if ($ccontrol != 'no-cache') {
                    $cetagf = explode('-', $reqheaders['If-None-Match']);
                    $cetag  = $cetagf[0];
                    // Same image than the cached one? Use the cache.
                    if ($cetag == $etag) {
                        return $response->withStatus(304);
                    }
                }
            }

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
                        break;
                    default:
                        $mime       = 'image/png';
                        $defaultimg = $this->configContainer->get('custom_blankalbum');
                        if (empty($defaultimg) || (strpos($defaultimg, "http://") !== 0 && strpos($defaultimg, "https://") !== 0)) {
                            $defaultimg = $rootimg . "blankalbum.png";
                        }
                        break;
                }
                $image = file_get_contents($defaultimg);
            } else {
                if (filter_has_var(INPUT_GET, 'thumb')) {
                    $thumb_data = $art->get_thumb($size);
                    $etag .= '-' . filter_input(INPUT_GET, 'thumb', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                }

                $mime  = isset($thumb_data['thumb_mime']) ? $thumb_data['thumb_mime'] : $art->raw_mime;
                $image = isset($thumb_data['thumb']) ? $thumb_data['thumb'] : $art->raw;
            }
        }

        if (!empty($image)) {
            $extension = Art::extension($mime);
            $filename  = scrub_out($filename . '.' . $extension);

            // Send the headers and output the image
            if (!empty($etag)) {
                $response = $response->withHeader(
                    'ETag',
                    $etag
                )->withHeader(
                    'Cache-Control',
                    'private'
                )->withHeader(
                    'Last-Modified',
                    gmdate('D, d M Y H:i:s \G\M\T', time())
                );
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
