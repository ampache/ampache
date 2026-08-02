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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\Upload;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Uploads a media file into the upload catalog
 */
final class Upload8Method implements MethodInterface
{
    public const string ACTION = 'upload';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * upload
     * MINIMUM_API_VERSION=800000
     *
     * Add a media file to the catalog named by the `upload_catalog` preference.
     *
     * The file is sent either as a multipart form field named `upl`, or as the raw request body, in which case
     * `filename` names it. Uploading requires the `allow_upload` preference and the `upload_access_level` it sets.
     *
     * filename    = (string) file name, required when the file is sent as the request body
     * license     = (integer) license id //optional, required when `licensing` is enabled
     * artist_id   = (integer) artist id //optional
     * artist_name = (string) create or reuse an artist you own //optional
     * album_id    = (integer) album id //optional
     * album_name  = (string) create or reuse an album you own //optional
     *
     * @param array{
     *     filename?: string,
     *     license?: string,
     *     artist_id?: string,
     *     artist_name?: string,
     *     album_id?: string,
     *     album_name?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     *
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!AmpConfig::get('allow_upload')) {
            $response->getBody()->write(
                $output->error($apiVersion, ErrorCodeEnum::ACCESS_DENIED, 'Access Denied', self::ACTION, 'system')
            );

            return $response;
        }

        $accessLevel = (int) AmpConfig::get(ConfigurationKeyEnum::UPLOAD_ACCESS_LEVEL, AccessLevelEnum::USER->value);
        if ($user->access < $accessLevel) {
            $response->getBody()->write(
                $output->error($apiVersion, ErrorCodeEnum::ACCESS_DENIED, 'Access Denied', self::ACTION, 'account')
            );

            return $response;
        }

        // a multipart field names itself; a raw body cannot, so `filename` carries the name and the extension check
        $uploaded = $_FILES['upl'] ?? null;
        $filename = (is_array($uploaded))
            ? (string) $uploaded['name']
            : (string) ($input['filename'] ?? '');

        // the name is joined onto the catalog directory, so any path the caller put in it is stripped first
        $filename = Upload::clean_filename($filename);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filename')
            );
        }

        // Upload::check() reads the name from $_FILES, so a raw body has to look the same to it
        if (!is_array($uploaded)) {
            $_FILES['upl'] = [
                'name' => $filename,
                'error' => 0,
            ];
        }

        $catalog = Upload::check((int) AmpConfig::get('upload_catalog', 0));
        if (!$catalog instanceof Catalog_local) {
            $response->getBody()->write(
                $output->error($apiVersion, ErrorCodeEnum::BAD_REQUEST, 'Bad Request: upload_catalog', self::ACTION, 'system')
            );

            return $response;
        }

        $rootdir   = Upload::get_root($catalog, $user->username);
        $targetdir = ($rootdir !== null)
            ? Upload::check_target_dir($rootdir)
            : null;
        $targetfile = ($targetdir !== null)
            ? Upload::check_target_path($targetdir . DIRECTORY_SEPARATOR . $filename)
            : null;

        if ($targetfile === null) {
            $response->getBody()->write(
                $output->error($apiVersion, ErrorCodeEnum::BAD_REQUEST, 'Bad Request: filename', self::ACTION, 'filename')
            );

            return $response;
        }

        if (!$this->store($uploaded, $targetfile)) {
            $response->getBody()->write(
                $output->error($apiVersion, ErrorCodeEnum::BAD_REQUEST, 'Bad Request: file', self::ACTION, 'file')
            );

            return $response;
        }

        if (
            !Upload::add_to_catalog(
                $catalog,
                (string) $targetdir,
                $targetfile,
                [
                    'license' => $input['license'] ?? '',
                    'artist_id' => $input['artist_id'] ?? null,
                    'artist_name' => $input['artist_name'] ?? null,
                    'album_id' => $input['album_id'] ?? null,
                    'album_name' => $input['album_name'] ?? null,
                ],
                $user->getId()
            )
        ) {
            // the file reached the catalog directory, so a failure has to take it back out again
            if (is_file($targetfile)) {
                unlink($targetfile);
            }

            $response->getBody()->write(
                $output->error($apiVersion, ErrorCodeEnum::BAD_REQUEST, 'Bad Request', self::ACTION, 'input')
            );

            return $response;
        }

        $response->getBody()->write(
            $output->success($apiVersion, 'file uploaded')
        );

        return $response;
    }

    /**
     * Places the sent file into the catalog, from a multipart field or from the raw request body.
     *
     * @param array<string, mixed>|null $uploaded
     */
    private function store(?array $uploaded, string $targetfile): bool
    {
        if (is_array($uploaded) && !empty($uploaded['tmp_name'])) {
            return move_uploaded_file((string) $uploaded['tmp_name'], $targetfile);
        }

        $body = fopen('php://input', 'rb');
        if ($body === false) {
            return false;
        }

        $target = fopen($targetfile, 'wb');
        if ($target === false) {
            fclose($body);

            return false;
        }

        // a body is not held to the multipart limits php applies, so it is read one byte past them to catch an overrun
        $limit   = Upload::max_upload_bytes();
        $written = ($limit > 0)
            ? stream_copy_to_stream($body, $target, $limit + 1)
            : stream_copy_to_stream($body, $target);

        fclose($body);
        fclose($target);

        if ($written === false || $written === 0 || ($limit > 0 && $written > $limit)) {
            if ($written !== false && $written > $limit && $limit > 0) {
                $this->logger->warning(
                    sprintf('Upload rejected: body of %d bytes exceeds the %d byte limit', $written, $limit),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }

            if (is_file($targetfile)) {
                unlink($targetfile);
            }

            return false;
        }

        return true;
    }
}
