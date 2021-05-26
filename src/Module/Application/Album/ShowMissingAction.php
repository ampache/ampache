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

declare(strict_types=0);

namespace Ampache\Module\Application\Album;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Wanted;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Art\Collector\ArtCollectorInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowMissingAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_missing';

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    private ArtCollectorInterface $artCollector;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        UiInterface $ui,
        ArtCollectorInterface $artCollector
    ) {
        $this->modelFactory = $modelFactory;
        $this->ui           = $ui;
        $this->artCollector = $artCollector;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        require_once Ui::find_template('header.inc.php');

        set_time_limit(600);
        $mbid   = $_REQUEST['mbid'];
        $walbum = $this->modelFactory->createWanted(Wanted::get_wanted($mbid));

        if (!$walbum->id) {
            $walbum->mbid = $mbid;
            if (isset($_REQUEST['artist'])) {
                $artist              = $this->modelFactory->createArtist((int) $_REQUEST['artist']);
                $walbum->artist      = $artist->id;
                $walbum->artist_mbid = $artist->mbid;
            } elseif (isset($_REQUEST['artist_mbid'])) {
                $walbum->artist_mbid = $_REQUEST['artist_mbid'];
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

        // Attempt to find the art.
        $art = $this->modelFactory->createArt((int) $walbum->mbid);

        $images = $this->artCollector->collect(
            $art,
            [
                'artist' => $artist->f_name,
                'album_name' => $walbum->name,
                'keyword' => $artist->f_name . " " . $walbum->name,
            ],
            1
        );

        $imageList = '';

        if (count($images) > 0 && !empty($images[0]['url'])) {
            $name = '[' . $artist->f_name . '] ' . scrub_out($walbum->name);

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

        printf('<div id="information_actions"><h3>%1$s:</h3><ul><li>%1$s:<div id="wanted_action_%2$d">',
            T_('Actions'),
            $walbum->mbid
        );

        $walbum->show_action_buttons();

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
