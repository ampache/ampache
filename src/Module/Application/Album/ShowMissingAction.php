<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Album;

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Art\Collector\ArtCollectorInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\WantedRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowMissingAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_missing';

    private RequestParserInterface $requestParser;

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    private ArtCollectorInterface $artCollector;

    private WantedRepositoryInterface $wantedRepository;

    public function __construct(
        RequestParserInterface $requestParser,
        ModelFactoryInterface $modelFactory,
        UiInterface $ui,
        ArtCollectorInterface $artCollector,
        WantedRepositoryInterface $wantedRepository
    ) {
        $this->requestParser    = $requestParser;
        $this->modelFactory     = $modelFactory;
        $this->ui               = $ui;
        $this->artCollector     = $artCollector;
        $this->wantedRepository = $wantedRepository;
    }

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
                $artist_id           = (int)$this->requestParser->getFromRequest('artist');
                $artist              = $this->modelFactory->createArtist($artist_id);
                $walbum->artist      = $artist->id;
                $walbum->artist_mbid = $artist->mbid;
            } elseif (array_key_exists('artist_mbid', $_REQUEST)) {
                $walbum->artist_mbid = $this->requestParser->getFromRequest('artist_mbid');
            }
        }
        $walbum->load_all();
        $walbum->format();

        // Title for this album
        $this->ui->showBoxTop(
            sprintf(
                '%s&nbsp;(%d)&nbsp;-&nbsp;%s',
                scrub_out($walbum->name),
                $walbum->year,
                $walbum->f_artist_link
            ),
            'info-box missing'
        );

        // you might not send an artist name
        $options = (isset($artist))
            ? array('artist' => $artist->get_fullname(), 'album_name' => $walbum->name, 'keyword' => $artist->get_fullname() . " " . $walbum->name)
            : array('album_name' => $walbum->name, 'keyword' => $walbum->name);

        // Attempt to find the art.
        $art    = $this->modelFactory->createArt((int) $walbum->mbid);
        $images = $this->artCollector->collect(
            $art,
            $options,
            1
        );

        $imageList = '';

        if (count($images) > 0 && !empty($images[0]['url'])) {
            $name = (isset($artist))
                ? '[' . $artist->get_fullname() . '] ' . scrub_out($walbum->name)
                : scrub_out($walbum->name);

            $image = $images[0]['url'];

            $imageList = sprintf(
                '<a href="%1$s" rel="prettyPhoto"><img src="%1$s" alt="%2$s" alt="%2$s" height="128" width="128" /></a>',
                $image,
                $name
            );
        }

        printf(
            '<div class="item_art">%s</div>',
            $imageList
        );

        printf(
            '<div id="information_actions"><h3>%1$s:</h3><ul><li>%1$s:<div id="wanted_action_%2$d">',
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
