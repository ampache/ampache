<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Horde_Browser;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Art;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

abstract readonly class AbstractShowAction implements ApplicationActionInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private AuthenticationManagerInterface $authenticationManager,
        private ConfigContainerInterface $configContainer,
        private Horde_Browser $horde_browser,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private LoggerInterface $logger
    ) {
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
                !Session::exists(AccessTypeEnum::INTERFACE->value, $cookie) &&
                !Session::exists(AccessTypeEnum::API->value, $auth) &&
                !empty($token_check)
            ) {
                $auth = ($auth !== '') ? $auth : $token;
                $this->logger->warning(
                    sprintf('Access denied, checked cookie session:%s and auth:%s', $cookie, $auth),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return $response;
            }
        }

        // If we aren't resizing just trash thumb
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RESIZE_IMAGES) === false) {
            $_GET['thumb'] = null;
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
            $itemConfig = $this->getFileName($request);

            if ($itemConfig === null) {
                return $response;
            }

            [$filename, $objectId, $type] = $itemConfig;


            $art = new Art($objectId, $type, $kind);
            $art->has_db_info();

            $etag = $art->id;
            if (!$art->raw_mime) {
                $rootimg = sprintf(
                    '%s/../../../../public/%s/images/',
                    __DIR__,
                    $this->configContainer->getThemePath()
                );

                $mime       = 'image/png';
                $defaultimg = $this->configContainer->get('custom_blankalbum');
                if (empty($defaultimg) || (strpos($defaultimg, "http://") !== 0 && strpos($defaultimg, "https://") !== 0)) {
                    $defaultimg = $rootimg . "blankalbum.png";
                }
                $etag = "EmptyMediaAlbum";
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

    /**
     * @return null|array{
     *  0: string,
     *  1: int,
     *  2: string
     * }
     */
    abstract protected function getFileName(
        ServerRequestInterface $request,
    ): ?array;
}
