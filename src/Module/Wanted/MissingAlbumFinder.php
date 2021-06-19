<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Wanted;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Wanted\Gui\MusicBrainzResultWantedGuiItem;
use Ampache\Module\Wanted\Gui\WantedUiItem;
use Ampache\Module\Wanted\Gui\WantedUiItemInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\WantedRepositoryInterface;
use Exception;
use MusicBrainz\MusicBrainz;
use Psr\Log\LoggerInterface;

final class MissingAlbumFinder implements MissingAlbumFinderInterface
{
    private MusicBrainz $musicBrainz;

    private AlbumRepositoryInterface $albumRepository;

    private MissingArtistLookupInterface $missingArtistLookup;

    private ModelFactoryInterface $modelFactory;

    private WantedRepositoryInterface $wantedRepository;

    private LoggerInterface $logger;

    public function __construct(
        MusicBrainz $musicBrainz,
        AlbumRepositoryInterface $albumRepository,
        MissingArtistLookupInterface $missingArtistLookup,
        ModelFactoryInterface $modelFactory,
        WantedRepositoryInterface $wantedRepository,
        LoggerInterface $logger
    ) {
        $this->musicBrainz         = $musicBrainz;
        $this->albumRepository     = $albumRepository;
        $this->missingArtistLookup = $missingArtistLookup;
        $this->modelFactory        = $modelFactory;
        $this->wantedRepository    = $wantedRepository;
        $this->logger              = $logger;
    }

    /**
     * Get list of library's missing albums from MusicBrainz
     *
     * @return array<WantedUiItemInterface>
     *
     * @throws \MusicBrainz\Exception
     */
    public function find(
        User $user,
        ?Artist $artist,
        string $mbid = ''
    ): ?array {
        $includes = array('release-groups');
        $types    = explode(',', AmpConfig::get('wanted_types'));

        try {
            $martist = $this->musicBrainz->lookup('artist', $artist ? $artist->mbid : $mbid, $includes);
        } catch (Exception $error) {
            $this->logger->warning(
                'get_missing_albums ERROR: ' . $error,
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return null;
        }

        $owngroups = array();
        $wartist   = array();
        if ($artist) {
            $albums = $this->albumRepository->getByArtist($artist->id);
            foreach ($albums as $albumid) {
                $album = $this->modelFactory->createAlbum($albumid);
                if (trim((string)$album->mbid_group)) {
                    $owngroups[] = $album->mbid_group;
                } else {
                    if (trim((string)$album->mbid)) {
                        $malbum = $this->musicBrainz->lookup('release', $album->mbid, array('release-groups'));
                        if ($malbum->{'release-group'}) {
                            if (!in_array($malbum->{'release-group'}->id, $owngroups)) {
                                $owngroups[] = $malbum->{'release-group'}->id;
                            }
                        }
                    }
                }
            }
        } else {
            $wartist['mbid'] = $mbid;
            $wartist['name'] = $martist->name;

            $wartist = $this->missingArtistLookup->lookup($mbid);
        }

        $results = array();
        foreach ($martist->{'release-groups'} as $group) {
            if (in_array(strtolower((string)$group->{'primary-type'}), $types)) {
                $add     = true;
                $g_count = count($group->{'secondary-types'});

                for ($i = 0; $i < $g_count && $add; ++$i) {
                    $add = in_array(strtolower((string)$group->{'secondary-types'}[$i]), $types);
                }

                if ($add) {
                    $this->logger->debug(
                        'get_missing_albums ADDING: ' . $group->title,
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    if (!in_array($group->id, $owngroups)) {
                        $wantedid = $this->wantedRepository->getByMusicbrainzId($group->id);
                        $wanted   = $this->modelFactory->createWanted($wantedid);
                        if ($wanted->getId()) {
                            $results[] = new WantedUiItem(
                                $this->wantedRepository,
                                $user,
                                $wanted
                            );
                        } else {
                            $results[] = new MusicBrainzResultWantedGuiItem(
                                $artist,
                                $group,
                                $mbid,
                                $wartist['link']
                            );
                        }
                    }
                }
            }
        }

        return $results;
    }
}
