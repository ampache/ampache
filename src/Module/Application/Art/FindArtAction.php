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

namespace Ampache\Module\Application\Art;

use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Art\Collector\ArtCollectorInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FindArtAction extends AbstractArtAction
{
    public const REQUEST_KEY = 'find_art';

    private ArtCollectorInterface $artCollector;

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    public function __construct(
        ArtCollectorInterface $artCollector,
        ModelFactoryInterface $modelFactory,
        UiInterface $ui
    ) {
        $this->artCollector = $artCollector;
        $this->modelFactory = $modelFactory;
        $this->ui           = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $object_type = filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $item        = $this->getItem($gatekeeper);

        if ($item === null) {
            throw new AccessDeniedException();
        }

        $object_id = $item->id;

        $burl = '';
        if (filter_has_var(INPUT_GET, 'burl')) {
            $burl = base64_decode(Core::get_get('burl'));
        }

        $keywords = $item->get_keywords();
        $keyword  = '';
        $options  = [];
        foreach ($keywords as $key => $word) {
            if (isset($_REQUEST['option_' . $key])) {
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

        if (isset($_REQUEST['artist_filter'])) {
            $options['artist_filter'] = true;
        }
        if (isset($_REQUEST['search_limit'])) {
            $options['search_limit'] = $limit = (int)$_REQUEST['search_limit'];
        }
        if (isset($_REQUEST['year_filter']) && !empty($_REQUEST['year_filter'])) {
            $options['year_filter'] = 'year:' . $_REQUEST['year_filter'];
        }

        $burl = '';
        if (filter_has_var(INPUT_GET, 'burl')) {
            $burl = base64_decode(Core::get_get('burl'));
        }

        $this->ui->showHeader();

        // If we've got an upload ignore the rest and just insert it
        if (!empty($_FILES['file']['tmp_name'])) {
            $path_info      = pathinfo($_FILES['file']['name']);
            $upload['file'] = $_FILES['file']['tmp_name'];
            $upload['mime'] = 'image/' . $path_info['extension'];
            $image_data     = Art::get_from_source($upload, $object_type);

            if ($image_data != '') {
                if ($art->insert($image_data['raw'], $image_data['mime'])) {
                    $this->ui->showConfirmation(
                        T_('No Problem'),
                        T_('Art has been added'),
                        $burl
                    );
                } else {
                    $this->ui->showConfirmation(
                        T_("There Was a Problem"),
                        T_('Art file failed to insert, check the dimensions are correct.'),
                        $burl
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
            $path_info            = pathinfo($_REQUEST['cover']);
            $cover_url[0]['url']  = scrub_in($_REQUEST['cover']);
            $cover_url[0]['mime'] = 'image/' . $path_info['extension'];
        }
        $images = array_merge($cover_url, $images);

        // If we've found anything then go for it!
        if (count($images)) {
            // We don't want to store raw's in here so we need to strip them out into a separate array
            foreach ($images as $index => $image) {
                if ($image['raw']) {
                    unset($images[$index]['raw']);
                }
            } // end foreach
            // Store the results for further use
            $_SESSION['form']['images'] = $images;
            require_once Ui::find_template('show_arts.inc.php');
        }

        require_once Ui::find_template('show_get_art.inc.php');

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
