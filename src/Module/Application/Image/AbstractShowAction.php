<?php

declare(strict_types=1);

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
use Teapot\StatusCode;

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
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PUBLIC_IMAGES) === false &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USE_AUTH) === true &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::REQUIRE_SESSION) === true
        ) {
            // regular auth
            $auth = $this->requestParser->getFromRequest('auth');
            // subsonic apiKey auth
            $apiKey = $this->requestParser->getFromRequest('apiKey');
            // subsonic token and salt auth
            $token_check = $this->authenticationManager->tokenLogin(
                $this->requestParser->getFromRequest('u'),
                $this->requestParser->getFromRequest('t'),
                $this->requestParser->getFromRequest('s')
            );

            $cookie = $_COOKIE[AmpConfig::get('session_name', 'ampache')] ?? null;

            // Check to see if they've got an interface session or a valid API session
            if (
                Session::exists(AccessTypeEnum::INTERFACE->value, $cookie ?? $auth) ||
                Session::exists(AccessTypeEnum::API->value, (empty($auth)) ? $apiKey : $auth) ||
                (isset($token_check['success']) && $token_check['success'] === true)
            ) {
                // authentication succeeded
            } else {
                $this->logger->warning(
                    'Access denied: No valid session found',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return $response->withStatus(
                    StatusCode\RFC\RFC7231::FORBIDDEN,
                    'Access denied: No valid session found'
                );
            }

        }

        // If we aren't resizing just trash thumb
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RESIZE_IMAGES) === false) {
            $_GET['thumb'] = null;
            $_GET['size']  = null;
        }

        $thumb = (int)filter_input(INPUT_GET, 'thumb', FILTER_SANITIZE_NUMBER_INT);
        $size  = ($thumb === 0)
            ? filter_input(INPUT_GET, 'size', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE) ?? 'original'
            : 'original';
        $kind = (array_key_exists('kind', $_GET) && $_GET['kind'] == 'preview')
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
                        $object_type = $this->requestParser->getFromRequest('object_type');
                        $image       = Art::get_from_source($_SESSION['form']['images'][$filename], $object_type);
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

            $art      = new Art($objectId, $type, $kind);
            $has_info = $art->has_db_info($size ?: 'original');
            $has_size = $size && preg_match('/^[0-9]+x[0-9]+$/', $size);
            if (!$has_info) {
                // show a fallback image
                $rootimg = sprintf(
                    '%s/../../../../public/images/',
                    __DIR__
                );

                $mime       = 'image/png';
                $defaultimg = $this->configContainer->get('custom_blankalbum');
                if (empty($defaultimg) || (strpos($defaultimg, "http://") !== 0 && strpos($defaultimg, "https://") !== 0)) {
                    $defaultimg = ($has_size && in_array($size, ['128x128', '256x256', '384x384', '768x768']))
                        ? $rootimg . "blankalbum_" . $size . ".png"
                        : $rootimg . "blankalbum.png";
                }
                $etag = ($has_size && in_array($size, ['128x128', '256x256', '384x384', '768x768']))
                    ? "EmptyMediaAlbum" . $size
                    : "EmptyMediaAlbum";
                $image = file_get_contents($defaultimg);
            } else {
                // show the original image or thumbnail
                $etag       = $type . '_' . $art->id . '_' . $size;
                $thumb_data = [];
                if ($has_size) {
                    if ($art->thumb && $art->thumb_mime) {
                        // found the thumb by looking up the size
                        $art->raw_mime = $art->thumb_mime;
                        $art->raw      = $art->thumb;
                    }
                } elseif (array_key_exists('thumb', $_GET) && $thumb > 0) {
                    // thumbs should be avoided but can still be used
                    $size_array = Art::get_thumb_size($thumb);
                    $thumb_data = $art->get_thumb($size_array);
                    $etag       = $type . '_' . $art->id . '_thumb_' . $thumb;
                }

                $mime = (array_key_exists('thumb_mime', $thumb_data))
                    ? $thumb_data['thumb_mime']
                    : $art->raw_mime;
                $image = (array_key_exists('thumb', $thumb_data))
                    ? $thumb_data['thumb']
                    : $art->raw;
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
                if (!array_key_exists('Cache-Control', $reqheaders) || ($reqheaders['Cache-Control'] != 'no-cache')) {
                    $cetag = str_replace('"', '', $reqheaders['If-None-Match']);
                    // Same image than the cached one? Use the cache.
                    if (
                        !is_array($cetag) &&
                        $cetag == $etag
                    ) {
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
