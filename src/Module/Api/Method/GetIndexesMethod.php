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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SearchRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class GetIndexesMethod implements MethodInterface
{
    public const ACTION = 'get_indexes';

    private const TYPE_REQUIRE_FEATURE = [
        'video' => ConfigurationKeyEnum::ALLOW_VIDEO,
        'podcast' => ConfigurationKeyEnum::PODCAST,
        'podcast_episode' => ConfigurationKeyEnum::PODCAST,
        'live_stream' => ConfigurationKeyEnum::LIVE_STREAM,
    ];

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private SearchRepositoryInterface $searchRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        SearchRepositoryInterface $searchRepository,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory    = $streamFactory;
        $this->configContainer  = $configContainer;
        $this->searchRepository = $searchRepository;
        $this->modelFactory     = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * This takes a collection of inputs and returns ID + name for the object type
     * Added 'include' to allow indexing all song tracks (enabled for xml by default)
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * type        = (string) 'song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'share' 'video', 'live_stream'
     * filter      = (string) //optional
     * exact       = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add         = self::set_filter(date) //optional
     * update      = self::set_filter(date) //optional
     * include     = (integer) 0,1 include songs if available for that object //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     * @throws FunctionDisabledException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $type = $input['type'] ?? null;

        if ($type === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'type')
            );
        }

        $type = ($type === 'album_artist') ? 'artist' : $type;

        // confirm the correct data
        if (!in_array($type, ['song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'live_stream'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $type)
            );
        }

        $requiredFeature = static::TYPE_REQUIRE_FEATURE[$type] ?? null;
        if ($requiredFeature !== null && $this->configContainer->isFeatureEnabled($requiredFeature) === false) {
            throw new FunctionDisabledException(sprintf(T_('Enable: %s'), $type));
        }

        $include = (int) ($input['include'] ?? 0) == 1;
        $hide    = ((int) ($input['hide_search'] ?? 0) == 1) || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::HIDE_SEARCH);

        $userId = $gatekeeper->getUser()->getId();

        $browse = $this->modelFactory->createBrowse();
        $browse->reset_filters();
        $browse->set_type($type);
        $browse->set_sort('name', 'ASC');

        $method = ($input['exact'] ?? '') ? 'exact_match' : 'alpha_match';

        Api::set_filter($method, ($input['filter'] ?? ''), $browse);
        Api::set_filter('add', ($input['add'] ?? ''), $browse);
        Api::set_filter('update', ($input['update'] ?? ''), $browse);
        // set the album_artist filter (if enabled)
        if ((string) $input['type'] == 'album_artist') {
            Api::set_filter('album_artist', true, $browse);
        }

        if ($type == 'playlist') {
            $browse->set_filter('playlist_type', $userId);
            if (!$hide) {
                $objects = array_merge(
                    $browse->get_objects(),
                    $this->searchRepository->getSmartlists($userId)
                );
            } else {
                $objects = $browse->get_objects();
            }
        } else {
            $objects = $browse->get_objects();
        }

        if ($objects === []) {
            $result = $output->emptyResult($type);
        } else {
            $result = $output->indexes(
                array_map('intval', $objects),
                $type,
                $userId,
                $include,
                true,
                (int) ($input['limit'] ?? 0),
                (int) ($input['offset'] ?? 0)
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
