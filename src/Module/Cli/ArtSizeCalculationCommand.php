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

declare(strict_types=1);

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Art;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;

final class ArtSizeCalculationCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct('run:calculateArtSize', 'Runs the art size calculation');

        $this->configContainer = $configContainer;
    }

    public function execute(): void
    {
        $io = $this->app()->io();

        $io->white(T_('Started art size calculation'), true);

        $inDisk   = $this->configContainer->get('album_art_store_disk');
        $localDir = $this->configContainer->get('local_metadata_dir');

        $sql        = "SELECT `image`, `id`, `object_id`, `object_type`, `size` FROM `image`";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $folder = Art::get_dir_on_disk($row['object_type'], $row['object_id'], 'default');
            if ($inDisk && $localDir && $folder) {
                $source = Art::get_from_source(array('file' => $folder . 'art-' . $row['size'] . '.jpg'), $row['object_type']);
            } else {
                $source = $row['image'];
            }

            $art_id     = $row['id'];
            $dimensions = Core::image_dimensions($source);
            if (!empty($dimensions) && ((int) $dimensions['width'] > 0 && (int) $dimensions['height'] > 0)) {
                $width  = (int) $dimensions['width'];
                $height = (int) $dimensions['height'];
                $sql    = "UPDATE `image` SET `width`=" . $width . ", `height`=" . $height . " WHERE `id`='" . $art_id . "'";
                Dba::write($sql);
            }
        }

        $io->white(T_('Finished art size calculation'), true);
    }
}
