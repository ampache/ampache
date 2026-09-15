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

namespace Ampache\Module\Api\Subsonic\Handler;

use Ampache\Module\Api\Subsonic\MusicFolderResolverInterface;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Api\Subsonic_Api;
use Ampache\Module\Api\Subsonic_Json_Data;
use Ampache\Module\Api\Subsonic_Xml_Data;
use Ampache\Module\Api\SubsonicApiApplication;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;

final class SearchHandler implements SearchHandlerInterface
{
    public function __construct(
        private readonly MusicFolderResolverInterface $musicFolderResolver,
        private readonly SubsonicResponseHandlerInterface $responseHandler,
        private readonly Subsonic_Json_Data $subsonicJsonData,
        private readonly Subsonic_Xml_Data $subsonicXmlData,
    ) {}

    /**
     * search
     *
     * https://www.subsonic.org/pages/api.jsp#search
     * @param array<string, mixed> $input
     */
    public function search(array $input, User $user): void
    {
        $data = [
            'type' => 'song',
            'operator' => 'and'
        ];

        $rule_count = 1;

        $artist = $input['artist'] ?? '';
        if ($artist) {
            $data['rule_' . $rule_count]               = 'artist';
            $data['rule_' . $rule_count . '_operator'] = 2; // starts with
            $data['rule_' . $rule_count . '_input']    = $artist;
            $rule_count++;
        }

        $album = $input['album'] ?? '';
        if ($album) {
            $data['rule_' . $rule_count]               = 'album';
            $data['rule_' . $rule_count . '_operator'] = 2; // starts with
            $data['rule_' . $rule_count . '_input']    = $album;
            $rule_count++;
        }

        $title = $input['title'] ?? '';
        if ($title) {
            $data['rule_' . $rule_count]               = 'title';
            $data['rule_' . $rule_count . '_operator'] = 2; // starts with
            $data['rule_' . $rule_count . '_input']    = $title;
            $rule_count++;
        }

        $anywhere = $input['any'] ?? '';
        if ($anywhere) {
            $data['rule_' . $rule_count]               = 'anywhere';
            $data['rule_' . $rule_count . '_operator'] = 2; // starts with
            $data['rule_' . $rule_count . '_input']    = $anywhere;
            $rule_count++;
        }

        $newerThan = (int) ($input['newerThan'] ?? 0);
        if ($newerThan > 0) {
            $data['rule_' . $rule_count]               = 'added';
            $data['rule_' . $rule_count . '_operator'] = 1; // after
            $data['rule_' . $rule_count . '_input']    = date('Y-m-d\TH:i', (int) ($newerThan / 1000)); // e.g. 2025-08-12T10:15
        }

        $search_sql = Search::prepare($data, $user);
        $query      = Search::query($search_sql);
        $results    = $query['results'];
        $total      = $query['count'];

        $offset  = (int) ($input['offset'] ?? 0);
        $count   = (int) ($input['count'] ?? 20);
        $results = array_slice($results, $offset, $count);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSearchResult($response, $results, $offset, $total);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSearchResult($response, $results, $offset, $total);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * search2
     *
     * Returns a listing of files matching the given search criteria. Supports paging through the result.
     * https://www.subsonic.org/pages/api.jsp#search2
     * @param array<string, mixed> $input
     */
    public function search2(array $input, User $user): void
    {
        $query   = $input['query'] ?? '';
        $results = $this->doSearch($query, $input, $user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSearchResult2($response, $results['artists'], $results['albums'], $results['songs']);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSearchResult2($response, $results['artists'], $results['albums'], $results['songs']);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * search3
     *
     * Returns albums, artists and songs matching the given search criteria. Supports paging through the result.
     * https://www.subsonic.org/pages/api.jsp#search3
     * @param array<string, mixed> $input
     */
    public function search3(array $input, User $user): void
    {
        // query required by Subsonic https://opensubsonic.netlify.app/docs/endpoints/search3/
        if (isset($input['query'])) {
            $query = (string) $input['query'];
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }
        $results = $this->doSearch($query, $input, $user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSearchResult3($response, $results['artists'], $results['albums'], $results['songs']);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSearchResult3($response, $results['artists'], $results['albums'], $results['songs']);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, int[]>
     */
    private function doSearch(string $query, array $input, User $user): array
    {
        $artists = [];
        $albums  = [];
        $songs   = [];

        $artistCount   = $input['artistCount'] ?? 20;
        $artistOffset  = $input['artistOffset'] ?? 0;
        $albumCount    = $input['albumCount'] ?? 20;
        $albumOffset   = $input['albumOffset'] ?? 0;
        $songCount     = $input['songCount'] ?? 20;
        $songOffset    = $input['songOffset'] ?? 0;
        $musicFolderId = $this->musicFolderResolver->musicFolderId($input, $user);

        $original = unhtmlentities($query);
        $query    = SubsonicApiApplication::parseSearchQuery($original);
        if ($artistCount > 0) {
            $data           = [];
            $data['limit']  = $artistCount;
            $data['offset'] = $artistOffset;
            $data['type']   = 'artist';
            $ruleCount      = 1;
            foreach ($query as $token) {
                $data['rule_' . $ruleCount . '_input']    = $token['value'];
                $data['rule_' . $ruleCount . '_operator'] = $token['operator'];
                $data['rule_' . $ruleCount]               = 'title';
                $ruleCount++;
            }
            if ($musicFolderId !== 0) {
                $data['catalog_id'] = $musicFolderId;
            }
            $artists = Search::run($data, $user);
        }

        if ($albumCount > 0) {
            $data           = [];
            $data['limit']  = $albumCount;
            $data['offset'] = $albumOffset;
            $data['type']   = 'album';
            $ruleCount      = 1;
            foreach ($query as $token) {
                $data['rule_' . $ruleCount . '_input']    = $token['value'];
                $data['rule_' . $ruleCount . '_operator'] = $token['operator'];
                $data['rule_' . $ruleCount]               = 'title';
                $ruleCount++;
            }
            if ($musicFolderId !== 0) {
                $data['catalog_id'] = $musicFolderId;
            }
            $albums = Search::run($data, $user);
        }

        if ($songCount > 0) {
            $data           = [];
            $data['limit']  = $songCount;
            $data['offset'] = $songOffset;
            $data['type']   = 'song';
            $ruleCount      = 1;
            foreach ($query as $token) {
                $data['rule_' . $ruleCount . '_input']    = $token['value'];
                $data['rule_' . $ruleCount . '_operator'] = $token['operator'];
                $data['rule_' . $ruleCount]               = 'title';
                $ruleCount++;
            }
            if ($musicFolderId !== 0) {
                $data['catalog_id'] = $musicFolderId;
            }
            $songs = Search::run($data, $user);
        }

        return [
            'artists' => $artists,
            'albums' => $albums,
            'songs' => $songs,
        ];
    }
}
