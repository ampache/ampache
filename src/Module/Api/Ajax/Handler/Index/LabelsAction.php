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
use Ampache\Module\Util\Ui;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LabelsAction implements ActionInterface
{
    private LabelRepositoryInterface $labelRepository;

    public function __construct(
        LabelRepositoryInterface $labelRepository
    ) {
        $this->labelRepository = $labelRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];

        if (AmpConfig::get('label') && isset($_REQUEST['artist'])) {
            $labels     = $this->labelRepository->getByArtist((int) $_REQUEST['artist']);
            $object_ids = array();
            if (count($labels) > 0) {
                foreach ($labels as $labelid => $label) {
                    $object_ids[] = $labelid;
                }
            }
            $browse = new Browse();
            $browse->set_type('label');
            $browse->set_simple_browse(false);
            $browse->save_objects($object_ids);
            $browse->store();
            ob_start();
            require_once Ui::find_template('show_labels.inc.php');
            $results['labels'] = ob_get_clean();
        }

        return $results;
    }
}
