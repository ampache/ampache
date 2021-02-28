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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PlaylistGenerateMethod implements MethodInterface
{
    public const ACTION = 'playlist_generate';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory = $streamFactory;
        $this->modelFactory  = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=400002
     *
     * Get a list of song xml, indexes or id's based on some simple search criteria
     * 'recent' will search for tracks played after 'Statistics Day Threshold' days
     * 'forgotten' will search for tracks played before 'Statistics Day Threshold' days
     * 'unplayed' added in 400002 for searching unplayed tracks.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * mode   = (string)  'recent', 'forgotten', 'unplayed', 'random' //optional, default = 'random'
     * filter = (string)  $filter                       //optional, LIKE matched to song title
     * album  = (integer) $album_id                     //optional
     * artist = (integer) $artist_id                    //optional
     * flag   = (integer) 0,1                           //optional, default = 0
     * format = (string)  'song', 'index', 'id'         //optional, default = 'song'
     * offset = (integer)                               //optional
     * limit  = (integer)                               //optional
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        // parameter defaults
        $mode   = (in_array($input['mode'], array('forgotten', 'recent', 'unplayed', 'random'), true)) ? $input['mode'] : 'random';
        $format = (in_array($input['format'], array('song', 'index', 'id'), true)) ? $input['format'] : 'song';

        // confirm the correct data
        if (!in_array($format, array('song', 'index', 'id'))) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $format)
            );
        }
        $user  = $gatekeeper->getUser();
        $array = array();

        // count for search rules
        $rule_count = 1;

        $array['type'] = 'song';
        if (in_array($mode, array('forgotten', 'recent'), true)) {
            debug_event(self::class, 'playlist_generate ' . $mode, 5);
            // played songs
            $array['rule_' . $rule_count]               = 'myplayed';
            $array['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;

            // not played for a while or played recently
            $array['rule_' . $rule_count]               = 'last_play';
            $array['rule_' . $rule_count . '_input']    = AmpConfig::get('stats_threshold');
            $array['rule_' . $rule_count . '_operator'] = ($mode == 'recent') ? 0 : 1;
            $rule_count++;
        } elseif ($mode == 'unplayed') {
            debug_event(self::class, 'playlist_generate unplayed', 5);
            // unplayed songs
            $array['rule_' . $rule_count]               = 'myplayed';
            $array['rule_' . $rule_count . '_operator'] = 1;
            $rule_count++;
        } else {
            debug_event(self::class, 'playlist_generate random', 5);
            // random / anywhere
            $array['rule_' . $rule_count]               = 'anywhere';
            $array['rule_' . $rule_count . '_input']    = '%';
            $array['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;
        }
        // additional rules
        if ((int) $input['flag'] == 1) {
            debug_event(self::class, 'playlist_generate flagged', 5);
            $array['rule_' . $rule_count]               = 'favorite';
            $array['rule_' . $rule_count . '_input']    = '%';
            $array['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;
        }
        if (array_key_exists('filter', $input)) {
            $array['rule_' . $rule_count]               = 'title';
            $array['rule_' . $rule_count . '_input']    = (string) $input['filter'];
            $array['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;
        }
        $album = new Album((int) $input['album']);
        if ((array_key_exists('album', $input)) && ($album->id == $input['album'])) {
            // set rule
            $array['rule_' . $rule_count]               = 'album';
            $array['rule_' . $rule_count . '_input']    = $album->full_name;
            $array['rule_' . $rule_count . '_operator'] = 4;
            $rule_count++;
        }
        $artist = new Artist((int) $input['artist']);
        if ((array_key_exists('artist', $input)) && ($artist->id == $input['artist'])) {
            // set rule
            $array['rule_' . $rule_count]               = 'artist';
            $array['rule_' . $rule_count . '_input']    = trim(trim((string) $artist->prefix) . ' ' . trim((string) $artist->name));
            $array['rule_' . $rule_count . '_operator'] = 4;
        }

        // get db data
        $song_ids = $this->modelFactory->createSearch()->runSearch($array, $user);
        shuffle($song_ids);

        //slice the array if there is a limit
        if ((int) $input['limit'] > 0) {
            $song_ids = array_slice($song_ids, 0, (int) $input['limit']);
        }
        if ($song_ids === []) {
            $result = $output->emptyResult($format);
        } else {
            // output formatted XML
            switch ($format) {
                case 'id':
                    $result = $output->dict(
                        $song_ids,
                        true,
                        'id'
                    );
                    break;
                case 'index':
                    $result = $output->indexes(
                        $song_ids,
                        'song',
                        $user->getId(),
                        false,
                        false,
                        (int) ($input['limit'] ?? 0),
                        (int) ($input['offset'] ?? 0)
                    );
                    break;
                case 'song':
                default:
                    $result = $output->songs(
                        $song_ids,
                        $user->getId(),
                        true,
                        true,
                        true,
                        (int) ($input['limit'] ?? 0),
                        (int) ($input['offset'] ?? 0)
                    );
            }
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
