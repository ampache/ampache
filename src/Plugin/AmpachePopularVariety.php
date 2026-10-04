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

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Override;

/**
 * A play count ranking on a deep catalogue is won by whichever record someone listened through, so the top of
 * the list is the same album over and over. This one keeps the ranking and shows the first track of each album
 * and of each artist, which turns a wall of one cover into a wall of the library.
 */
class AmpachePopularVariety extends AmpachePlugin implements PluginDisplayHomeInterface
{
    /**
     * Enough candidates that a handful of prolific albums cannot empty the panel on their own.
     */
    private const int OVERSAMPLE = 8;

    #[Override]
    public string $categories = 'home';

    #[Override]
    public string $description = 'Popular songs on homepage, one per album and artist';

    #[Override]
    public string $max_ampache = '999999';

    #[Override]
    public string $min_ampache = '370021';

    #[Override]
    public string $name = 'Popular Variety';

    #[Override]
    public string $url = '';

    #[Override]
    public string $version = '000001';

    private int $maxitems = 10;
    private int $order    = 0;

    public function __construct()
    {
        $this->description = T_('Popular songs on homepage, one per album and artist');
    }

    /**
     * Whether this record and this performer are both new to the panel, which is what keeps it varied.
     *
     * @param array<int, true> $albums records already shown
     * @param array<int, true> $artists performers already shown
     */
    private static function isFirstOfBoth(int $album, ?int $artist, array $albums, array $artists): bool
    {
        return !isset($albums[$album]) && ($artist === null || !isset($artists[$artist]));
    }

    #[Override]
    public function display_home(): void
    {
        if ($this->maxitems < 1) {
            return;
        }

        $songs = $this->pickVaried();
        if ($songs === []) {
            return;
        }

        echo ($this->order > 0)
            ? '<div class="popularvariety" style="--order: ' . $this->order . '">'
            : '<div class="popularvariety">';
        Ui::show_box_top(T_('Popular'));
        echo '<table class="tabledata striped-rows gridview">';
        foreach ($songs as $song) {
            $songId = $song->getId();
            echo '<tr id="song_' . $songId . '" class="libitem_menu" data-object-type="song" data-object-id="' . $songId . '">';
            echo '<td class="grid_cover">';
            $song->display_art(['width' => 150, 'height' => 150], true);
            echo '</td>';
            echo '<td>' . $song->get_f_link() . '</td>';
            echo '<td class="cel_add"><span class="cel_item_add">';
            if (AmpConfig::get('directplay')) {
                echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $songId, 'play_circle', T_('Play'), 'play_song_' . $songId);
                if (Stream_Playlist::check_autoplay_next()) {
                    echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $songId . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_song_' . $songId);
                }
            }

            echo Ajax::button('?action=basket&type=song&id=' . $songId, 'new_window', T_('Add to Temporary Playlist'), 'add_song_' . $songId);
            echo '</span></td></tr>';
        }

        echo '</table>';
        Ui::show_box_bottom();
        echo '</div>';
    }

    #[Override]
    public function install(): bool
    {
        if (!Preference::insert('popularvariety_max_items', T_('Popular variety max items'), 10, AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('popularvariety_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name);
    }

    #[Override]
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->maxitems = (int) ($data['popularvariety_max_items'] ?? 10);
        if ($this->maxitems < 1) {
            $this->maxitems = 10;
        }

        $this->order = (int) ($data['popularvariety_order'] ?? 0);

        return true;
    }

    #[Override]
    public function uninstall(): bool
    {
        return (
            Preference::delete('popularvariety_max_items')
            && Preference::delete('popularvariety_order')
        );
    }

    #[Override]
    public function upgrade(): bool
    {
        return Plugin::get_plugin_version($this->name) > 0;
    }

    /**
     * The ranking is read in order and an album or an artist already shown is skipped, so the panel keeps the
     * most played track of each rather than the most played tracks overall.
     *
     * @return list<Song>
     */
    private function pickVaried(): array
    {
        // the window the dashboard already reads, which the statistics cache is always built for
        $ids = Stats::get_top('song', $this->maxitems * self::OVERSAMPLE, AmpConfig::get_int('stats_threshold', 7));
        if ($ids === []) {
            return [];
        }

        Song::build_cache($ids);
        $songs   = [];
        $albums  = [];
        $artists = [];
        foreach ($ids as $id) {
            $song = new Song((int) $id);
            if ($song->isNew() || !self::isFirstOfBoth($song->album, $song->artist, $albums, $artists)) {
                continue;
            }

            $albums[$song->album] = true;
            if ($song->artist !== null) {
                $artists[$song->artist] = true;
            }

            $songs[] = $song;
            if (count($songs) >= $this->maxitems) {
                break;
            }
        }

        return $songs;
    }
}
