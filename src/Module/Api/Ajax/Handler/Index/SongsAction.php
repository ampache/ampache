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

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SongsAction implements ActionInterface
{
    private ModelFactoryInterface $modelFactory;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        SongRepositoryInterface $songRepository
    ) {
        $this->modelFactory   = $modelFactory;
        $this->songRepository = $songRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];

        $label_id = (int) ($_REQUEST['label']);

        ob_start();
        if ($label_id > 0) {
            $label      = $this->modelFactory->createLabel($label_id);
            $object_ids = $this->songRepository->getByLabel($label->getName());

            $browse = new Browse();
            $browse->set_type('song');
            $browse->set_simple_browse(false);
            $browse->save_objects($object_ids);
            $browse->store();

            $hide_columns = [];

            Ui::show_box_top(T_('Songs'), 'info-box');
            require_once Ui::find_template('show_songs.inc.php');
            Ui::show_box_bottom();
        }

        $results['songs'] = ob_get_contents();
        ob_end_clean();

        return $results;
    }
}
