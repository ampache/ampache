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
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Index;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Util\Ui;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RandomAlbumsAction implements ActionInterface
{
    private AlbumRepositoryInterface $albumRepository;

    private CatalogRepositoryInterface $catalogRepository;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        CatalogRepositoryInterface $catalogRepository
    ) {
        $this->albumRepository   = $albumRepository;
        $this->catalogRepository = $catalogRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $moment  = (int) AmpConfig::get('of_the_moment');
        if (!$moment > 0) {
            $moment = 6;
        }

        $results = [];

        $albums = $this->albumRepository->getRandom(
            $user->id,
            $moment
        );
        if (count($albums) && is_array($albums)) {
            ob_start();
            require_once Ui::find_template('show_random_albums.inc.php');
            $results['random_selection'] = ob_get_clean();
        } else {
            $results['random_selection'] = '<!-- None found -->';

            if (Access::check('interface', 75)) {
                $catalogs = $this->catalogRepository->getList();
                if (count($catalogs) == 0) {
                    /* HINT: %1 and %2 surround "add a Catalog" to make it into a link */
                    $results['random_selection'] = sprintf(T_('No Catalog configured yet. To start streaming your media, you now need to %1$s add a Catalog %2$s'), '<a href="' . AmpConfig::get('web_path') . '/admin/catalog.php?action=show_add_catalog">', '</a>.<br /><br />');
                }
            }
        }

        return $results;
    }
}
