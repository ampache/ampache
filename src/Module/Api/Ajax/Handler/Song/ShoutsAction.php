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

namespace Ampache\Module\Api\Ajax\Handler\Song;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShoutsAction implements ActionInterface
{
    private ShoutRepositoryInterface $shoutRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ShoutRepositoryInterface $shoutRepository,
        ModelFactoryInterface $modelFactory
    ) {
        $this->shoutRepository = $shoutRepository;
        $this->modelFactory    = $modelFactory;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];
        ob_start();
        $type   = Core::get_request('object_type');
        $songid = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);

        if ($type == "song" && $songid > 0) {
            $media  = new Song($songid);
            $shouts = $this->shoutRepository->getBy($type, $songid);
            echo "<script>\r\n";
            echo "shouts = {};\r\n";
            foreach ($shouts as $shoutsid) {
                $shout = $this->modelFactory->createShoutbox($shoutsid);
                $key   = (int) ($shout->getData());
                echo "if (shouts['" . $key . "'] == undefined) { shouts['" . $key . "'] = new Array(); }\r\n";
                echo "shouts['" . $key . "'].push('" . addslashes(Ui::getShoutboxDisplay($shout, false)) . "');\r\n";
                echo "$('.waveform-shouts').append('<div style=\'position:absolute; width: 3px; height: 3px; background-color: #2E2EFE; top: 15px; left: " . ((($shout->getData() / $media->time) * 400) - 1) . "px;\' />');\r\n";
            }
            echo "</script>\r\n";
        }
        $results['shouts_data'] = ob_get_clean();

        return $results;
    }
}
