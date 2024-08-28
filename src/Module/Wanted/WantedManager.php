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

namespace Ampache\Module\Wanted;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Plugin\PluginRetrieverInterface;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Repository\Model\database_object;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Wanted;
use Ampache\Repository\WantedRepositoryInterface;
use MusicBrainz\MusicBrainz;

final readonly class WantedManager implements WantedManagerInterface
{
    public function __construct(
        private WantedRepositoryInterface $wantedRepository,
        private MusicBrainz $musicBrainz,
        private PluginRetrieverInterface $pluginRetriever
    ) {
    }

    /**
     * Delete a wanted release by mbid.
     * @throws \MusicBrainz\Exception
     */
    public function delete(
        string $mbid,
        ?User $user = null
    ): void {
        if ($this->wantedRepository->getAcceptedCount() > 0) {
            /** @var object{error?: string, release-group: string} $album */
            $album = $this->musicBrainz->lookup('release', $mbid, ['release-groups']);

            if ($album !== null && $album->{'release-group'}) {
                $this->wantedRepository->deleteByMusicbrainzId(
                    print_r($album->{'release-group'}, true),
                    $user
                );
            }
        }
    }

    /**
     * Add a new wanted release.
     */
    public function add(
        User $user,
        string $mbid,
        ?int $artist,
        ?string $artist_mbid,
        string $name,
        int $year
    ): void {
        Dba::write(
            "INSERT INTO `wanted` (`user`, `artist`, `artist_mbid`, `mbid`, `name`, `year`, `date`, `accepted`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$user->getId(), $artist, $artist_mbid, $mbid, $name, $year, time(), '0']
        );

        if (AmpConfig::get('wanted_auto_accept', false)) {
            $wanted_id = (int)Dba::insert_id();
            $wanted    = new Wanted($wanted_id);

            $this->accept($wanted, $user);

            database_object::remove_from_cache('wanted', $wanted_id);
        }
    }

    /**
     * Accept a wanted request.
     */
    public function accept(
        Wanted $wanted,
        User $user
    ): void {
        if ($user->has_access(AccessLevelEnum::MANAGER)) {
            $sql = "UPDATE `wanted` SET `accepted` = '1' WHERE `mbid` = ?";
            Dba::write($sql, [$wanted->getMusicBrainzId()]);
            $wanted->accepted = 1;

            foreach ($this->pluginRetriever->retrieveByType(PluginTypeEnum::WANTED_LOOKUP, $user) as $plugin) {
                debug_event(self::class, 'Using Wanted Process plugin: ' . $plugin::class, 5);

                $plugin->_plugin->process_wanted($this);
            }
        }
    }
}
