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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Rates a library item
 *
 * The two live api versions only differ in how they name the object id: version 6 reports it as
 * `id` and version 8 as `filter`, each accepting the other as an alias. The version classes supply
 * that pair of names; everything else is shared.
 */
abstract class AbstractRateMethod implements MethodInterface
{
    public const string ACTION = 'rate';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'id';

    // the name the version reports the object id under; overridden per version
    protected const string FILTER_KEY = 'filter';

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer,
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This rates a library item
     *
     * type   = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video'
     * id     = (string) $object_id
     * rating = (integer) 0|1|2|3|4|5 $rating
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type?: string,
     *     rating?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!$this->configContainer->get(ConfigurationKeyEnum::RATINGS)) {
            throw new AccessDeniedException(
                'Enable: ratings'
            );
        }

        $filter = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
            );
        }

        foreach (['type', 'rating'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $type     = (string) $input['type'];
        $objectId = (int) $filter;
        $rating   = (string) $input['rating'];

        // confirm the correct data
        if (!Rating::is_valid(strtolower($type))) {
            return $this->writeTypeError($response, $output, $apiVersion, $type);
        }

        if (!in_array($rating, ['0', '1', '2', '3', '4', '5'])) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $rating),
                    static::ACTION,
                    'rating'
                )
            );

            return $response;
        }

        // searches are playlists but not in the database
        if (
            $type === 'playlist'
            && $objectId === 0
        ) {
            $type     = 'search';
            $objectId = (int) str_replace('smart_', '', (string) $filter);
        }

        $className = ObjectTypeToClassNameMapper::map($type);
        if (!$className || !$objectId) {
            return $this->writeTypeError($response, $output, $apiVersion, $type);
        }

        /** @var library_item $item */
        $item = new $className($objectId);
        if ($item->isNew()) {
            throw new ResultEmptyException(
                (string) $objectId,
                'id'
            );
        }

        $rate = new Rating($objectId, $type);
        $rate->set_rating((int) $rating, $user->getId());

        $response->getBody()->write(
            $output->success($apiVersion, 'rating set to ' . $rating . ' for ' . $objectId)
        );

        return $response;
    }

    private function writeTypeError(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $type,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                sprintf('Bad Request: %s', $type),
                static::ACTION,
                'type'
            )
        );

        return $response;
    }
}
