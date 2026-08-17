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

namespace Ampache\Module\Application\Art;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Art\ArtSelectionView;
use Ampache\Gui\Art\GetArtView;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Art\Art;
use Ampache\Module\Art\Collector\ArtCollectorInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FindArtAction extends AbstractArtAction
{
    public const string REQUEST_KEY = 'find_art';

    public function __construct(
        private readonly ArtCollectorInterface $artCollector,
        private readonly ModelFactoryInterface $modelFactory,
        private readonly UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $object_type = (string) filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_SPECIAL_CHARS);
        $item        = $this->getItem($gatekeeper);

        if ($item === null) {
            throw new AccessDeniedException();
        }

        $object_id = $item->getId();

        $burl = '';
        if (isset($_GET['burl'])) {
            $burl = base64_decode(Core::get_get('burl'));
        }

        $keywords = $item->get_keywords();
        $keyword  = '';
        $options  = [];
        foreach ($keywords as $key => $word) {
            if (array_key_exists('option_' . $key, $_REQUEST)) {
                $word['value'] = $_REQUEST['option_' . $key];
            }

            $options[$key] = $word['value'];
            if ($word['important'] && !empty($word['value'])) {
                $keyword .= ' ' . $word['value'];
            }
        }

        $options['keyword'] = trim($keyword);

        // Prevent the script from timing out
        set_time_limit(0);

        $art       = $this->modelFactory->createArt($object_id, $object_type);
        $cover_url = [];
        $limit     = 0;

        if (array_key_exists('artist_filter', $_REQUEST)) {
            $options['artist_filter'] = true;
        }

        if (array_key_exists('search_limit', $_REQUEST)) {
            $options['search_limit'] = $limit = (int) $_REQUEST['search_limit'];
        }

        if (array_key_exists('year_filter', $_REQUEST) && !empty($_REQUEST['year_filter'])) {
            $options['year_filter'] = 'year:' . $_REQUEST['year_filter'];
        }

        $this->ui->showHeader();

        // If we've got an upload ignore the rest and just insert it
        if (!empty($_FILES['file']['tmp_name'])) {
            $upload         = [];
            $upload['file'] = $_FILES['file']['tmp_name'];
            $image_data     = Art::get_from_source($upload, $object_type);

            if ($image_data !== '') {
                // no mime passed: the uploaded filename is user supplied and says nothing reliable
                // about the bytes, so let insert() read the real type out of the image itself
                if ($art->insert($image_data)) {
                    $this->ui->showContinue(
                        T_('No Problem'),
                        T_('Art has been added'),
                        $item->get_link()
                    );
                } else {
                    $this->ui->showContinue(
                        T_('There Was a Problem'),
                        T_('Art file failed to insert, check the dimensions are correct.'),
                        $item->get_link()
                    );
                }

                $this->ui->showQueryStats();
                $this->ui->showFooter();

                return null;
            } // if image data
        } // if it's an upload

        // Attempt to find the art.
        $images = $this->artCollector->collect($art, $options, $limit);

        if (!empty($_REQUEST['cover'])) {
            $path_info            = pathinfo((string) $_REQUEST['cover']);
            $cover_url[0]['url']  = scrub_in((string) $_REQUEST['cover']);
            $cover_url[0]['mime'] = 'image/' . ($path_info['extension'] ?? 'jpg');
        }

        $images = array_merge($cover_url, $images);

        // If we've found anything then go for it!
        if ($images !== []) {
            // The session is a utf8mb4 text column, so raw image bytes can't go in it as they are; the
            // whole session write fails on the first non-utf8 byte. Anything that can be read back from
            // somewhere else (a url, an image row) keeps only that reference, and the rest, like a
            // generated mosaic or an id3 tag picture, is encoded so it stays selectable.
            foreach ($images as $index => $image) {
                if (!array_key_exists('raw', $image)) {
                    continue;
                }

                if (
                    empty($image['url'])
                    && empty($image['db'])
                ) {
                    $images[$index]['raw_base64'] = base64_encode($image['raw']);
                }

                unset($images[$index]['raw']);
            }

            // Store the results for further use
            $_SESSION['form']['images'] = $images;
            echo new ArtSelectionView(
                AmpConfig::get_web_path(),
                $object_id,
                $object_type,
                $burl,
                $images
            )->render();
        }

        echo new GetArtView(
            $item,
            $object_id,
            $object_type,
            $burl,
            AmpConfig::get_web_path()
        )->render();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
