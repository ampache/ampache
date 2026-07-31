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
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Generates a playlist of songs from a mode and optional filters
 */
final class PlaylistGenerateMethod implements MethodInterface
{
    public const string ACTION = 'playlist_generate';

    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Get a list of song XML, indexes or id's based on some simple search criteria.
     *
     * mode   = (string) 'forgotten', 'recent', 'unplayed', 'random' //optional, default = 'random'
     * filter = (string) string LIKE matched to song title //optional
     * album  = (integer) $album_id //optional
     * artist = (integer) $artist_id //optional
     * flag   = (integer) 0,1 //optional, default = 0
     * format = (string) 'song', 'index', 'id' //optional, default = 'song'
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     mode?: string,
     *     filter?: string,
     *     album?: string,
     *     artist?: string,
     *     flag?: int,
     *     format?: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        // parameter defaults; an unknown mode or format simply falls back
        $mode = (array_key_exists('mode', $input) && in_array($input['mode'], ['forgotten', 'recent', 'unplayed', 'random'], true))
            ? $input['mode']
            : 'random';

        $format = (array_key_exists('format', $input) && in_array($input['format'], ['song', 'index', 'id'], true))
            ? $input['format']
            : 'song';

        $offset = (int) ($input['offset'] ?? 0);
        $limit  = (int) ($input['limit'] ?? 0);

        debug_event(self::class, 'playlist_generate ' . $mode, 5);

        $data = $this->buildRules($mode, $input);

        // get db data
        $results = Search::query(Search::prepare($data, $user))['results'];
        shuffle($results);

        // slice the array if there is a limit
        if ($limit > 0) {
            $results = array_slice($results, 0, $limit);
        }

        if (empty($results)) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, $format)
            );

            return $response;
        }

        $output->setOffset($apiVersion, $offset);
        $output->setLimit($apiVersion, $limit);

        $response->getBody()->write(
            match ($format) {
                'id' => $output->keyedArray($apiVersion, ($apiVersion >= 8) ? array_map('strval', $results) : $results, false, 'id'),
                'index' => $output->indexes($apiVersion, $results, 'song', $user, $input['auth']),
                default => $output->songs($apiVersion, $results, $user, $input['auth']),
            }
        );

        return $response;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function buildRules(string $mode, array $input): array
    {
        $ruleCount = 1;
        $data      = ['type' => 'song'];

        if (in_array($mode, ['forgotten', 'recent'], true)) {
            // played songs
            $data['rule_' . $ruleCount]               = 'myplayed';
            $data['rule_' . $ruleCount . '_operator'] = 0;
            $ruleCount++;

            // not played for a while or played recently
            $data['rule_' . $ruleCount]               = 'last_play';
            $data['rule_' . $ruleCount . '_input']    = $this->configContainer->get(ConfigurationKeyEnum::STATS_THRESHOLD);
            $data['rule_' . $ruleCount . '_operator'] = ($mode === 'recent') ? 0 : 1;
            $ruleCount++;
        } elseif ($mode === 'unplayed') {
            // unplayed songs
            $data['rule_' . $ruleCount]               = 'myplayed';
            $data['rule_' . $ruleCount . '_operator'] = 1;
            $ruleCount++;
        } else {
            // random / anywhere
            $data['rule_' . $ruleCount]               = 'anywhere';
            $data['rule_' . $ruleCount . '_input']    = '%';
            $data['rule_' . $ruleCount . '_operator'] = 0;
            $ruleCount++;
        }

        // additional rules
        if (array_key_exists('flag', $input) && (int) $input['flag'] === 1) {
            $data['rule_' . $ruleCount]               = 'favorite';
            $data['rule_' . $ruleCount . '_input']    = '%';
            $data['rule_' . $ruleCount . '_operator'] = 0;
            $ruleCount++;
        }

        if (array_key_exists('filter', $input)) {
            $data['rule_' . $ruleCount]               = 'title';
            $data['rule_' . $ruleCount . '_input']    = (string) $input['filter'];
            $data['rule_' . $ruleCount . '_operator'] = 0;
            $ruleCount++;
        }

        $album = $this->modelFactory->createAlbum((int) ($input['album'] ?? 0));
        if (array_key_exists('album', $input) && $album->id == $input['album']) {
            $data['rule_' . $ruleCount]               = 'album';
            $data['rule_' . $ruleCount . '_input']    = $album->get_fullname(true);
            $data['rule_' . $ruleCount . '_operator'] = 4;
            $ruleCount++;
        }

        $artist = $this->modelFactory->createArtist((int) ($input['artist'] ?? 0));
        if (array_key_exists('artist', $input) && $artist->id == $input['artist']) {
            $data['rule_' . $ruleCount]               = 'artist';
            $data['rule_' . $ruleCount . '_input']    = $artist->get_fullname();
            $data['rule_' . $ruleCount . '_operator'] = 4;
        }

        return $data;
    }
}
