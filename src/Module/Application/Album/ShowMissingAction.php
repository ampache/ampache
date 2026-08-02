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

namespace Ampache\Module\Application\Album;

use Ampache\Config\AmpConfig;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowMissingAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_missing';

    public function __construct(
        private RequestParserInterface $requestParser,
        private ModelFactoryInterface $modelFactory,
        private UiInterface $ui,
        private WantedRepositoryInterface $wantedRepository,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        set_time_limit(600);
        $mbid   = $this->requestParser->getFromRequest('mbid');
        $walbum = $this->wantedRepository->findByMusicBrainzId($mbid);

        if ($walbum === null) {
            $walbum       = $this->wantedRepository->prototype();
            $walbum->mbid = $mbid;
            if (array_key_exists('artist', $_REQUEST)) {
                $artist_id           = (int) $this->requestParser->getFromRequest('artist');
                $artist              = $this->modelFactory->createArtist($artist_id);
                $walbum->artist      = $artist->id;
                $walbum->artist_mbid = $artist->mbid;
            } elseif (array_key_exists('artist_mbid', $_REQUEST)) {
                $walbum->artist_mbid = $this->requestParser->getFromRequest('artist_mbid');
            }
        }

        $walbum->load_all();

        // the prefix marks the box as an album Ampache does not hold, so it is not mistaken for one in the library
        $this->ui->showBoxTop(
            sprintf(
                '%s:&nbsp;%s&nbsp;(%d)&nbsp;-&nbsp;%s',
                T_('Wanted'),
                scrub_out($walbum->name),
                $walbum->year,
                $walbum->get_f_parent_link()
            ),
            'info-box missing'
        );

        // the same links a regular album carries, with Deezer and iTunes in place of DuckDuckGo and Wikipedia
        $artist_name = rawurlencode($walbum->get_parent_fullname());
        $album_name  = rawurlencode((string) $walbum->name);

        print('<div class="item_right_info"><div class="external_links">');
        if (AmpConfig::get('external_links_google')) {
            printf('<a href="https://www.google.com/search?q=%%22%s%%22+%%22%s%%22" target="_blank">%s</a>', $artist_name, $album_name, Ui::get_icon('google', sprintf(T_('Search on %s ...'), 'Google')));
        }

        printf('<a href="https://www.deezer.com/search/%s%%20%s" target="_blank">%s</a>', $artist_name, $album_name, Ui::get_icon('deezer', sprintf(T_('Search on %s ...'), 'Deezer')));
        printf('<a href="https://music.apple.com/search?term=%s%%20%s" target="_blank">%s</a>', $artist_name, $album_name, Ui::get_icon('itunes', sprintf(T_('Search on %s ...'), 'iTunes')));

        if (AmpConfig::get('external_links_lastfm')) {
            printf('<a href="https://www.last.fm/search?q=%%22%s%%22+%%22%s%%22&type=album" target="_blank">%s</a>', $artist_name, $album_name, Ui::get_icon('lastfm', sprintf(T_('Search on %s ...'), 'Last.fm')));
        }

        if (AmpConfig::get('external_links_bandcamp')) {
            printf('<a href="https://bandcamp.com/search?q=%s+%s&item_type=a" target="_blank">%s</a>', $artist_name, $album_name, Ui::get_icon('bandcamp', sprintf(T_('Search on %s ...'), 'Bandcamp')));
        }

        if (AmpConfig::get('external_links_discogs')) {
            $discogs_artist = ($walbum->get_parent_fullname() === 'Various Artists')
                ? rawurlencode('Various')
                : $artist_name;
            printf('<a href="https://www.discogs.com/search/?q=%s+%s&type=master" target="_blank">%s</a>', $discogs_artist, $album_name, Ui::get_icon('discogs', sprintf(T_('Search on %s ...'), 'Discogs')));
        }

        if (AmpConfig::get('external_links_musicbrainz')) {
            $musicbrainz = ($walbum->mbid)
                ? 'https://musicbrainz.org/release-group/' . $walbum->mbid
                : 'https://musicbrainz.org/search?query=%22' . $album_name . '%22&type=release';
            printf('<a href="%s" target="_blank">%s</a>', $musicbrainz, Ui::get_icon('musicbrainz', sprintf(T_('Search on %s ...'), 'Musicbrainz')));
        }

        print('</div></div>');

        printf(
            '<div id="information_actions"><h3>%1$s</h3><ul><li><div id="wanted_action_%2$d">',
            T_('Actions'),
            $walbum->mbid
        );

        echo $walbum->show_action_buttons();

        print('</div></li></ul></div>');

        $this->ui->showBoxBottom();

        print('<div id="additional_information">&nbsp;</div><div>');

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type('song_preview');
        $browse->set_static_content(true);
        $browse->show_objects($walbum->songs);

        print('</div>');

        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
