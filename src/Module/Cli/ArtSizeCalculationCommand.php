<?php

declare(strict_types=1);

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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Art;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;

final class ArtSizeCalculationCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on([$this, 'showHelp']);

        $this->onExit(static fn ($exitCode = 0) => exit($exitCode));

        return $this;
    }

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct('run:calculateArtSize', T_('Run art size calculation'));

        $this->configContainer = $configContainer;

        $this
            ->option('-f|--fix', T_('Fix database issues'), 'boolval', false)
            ->usage('<bold>  run:calculateArtSize</end> <comment> ## ' . T_('Run art size calculation') . '<eol/>');
    }

    public function execute(): void
    {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();
        $fix        = $this->values()['fix'] === true;
        $interactor->ok(
            T_('Started art size calculation'),
            true
        );

        $inDisk   = $this->configContainer->get('album_art_store_disk');
        $localDir = $this->configContainer->get('local_metadata_dir');

        $sql = ($fix)
            ? "SELECT `id`, `image`, `mime`, `size`, `object_id`, `object_type` FROM `image` WHERE (`height` = 0 AND `width` = 0) OR (`width` IS NULL AND `height` IS NULL)"
            : "SELECT `id`, `image`, `mime`, `size`, `object_id`, `object_type` FROM `image`";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $folder = Art::get_dir_on_disk($row['object_type'], (int)$row['object_id'], 'default');
            if ($inDisk && $localDir && $folder) {
                $ext    = (str_replace("image/", "", $row['mime']) ?? 'jpg');
                $path   = $folder . 'art-' . $row['size']. '.' . $ext;
                $source = Art::get_from_source(
                    ['file' => $path],
                    $row['object_type']
                );
                // try jpg on jpeg as well just in case we had weirdness with the insert
                if ($source === '' && $ext === 'jpeg') {
                    $source = Art::get_from_source(
                        ['file' => $folder . 'art-' . $row['size']. '.jpg'],
                        $row['object_type']
                    );
                }

                if ($source === '') {
                    $interactor->warn(
                        sprintf(T_('Missing: %s'), $path),
                        true
                    );
                }
            } else {
                $source = $row['image'];
            }

            $art_id     = $row['id'];
            $dimensions = Core::image_dimensions($source);
            if ($dimensions['width'] > 0 && $dimensions['height'] > 0) {
                $sql = "UPDATE `image` SET `width` = ?, `height` = ? WHERE `id` = ?";
                Dba::write($sql, [$dimensions['width'], $dimensions['height'], $art_id]);
            }
        }

        $interactor->ok(
            "\n" . T_('Finished art size calculation'),
            true
        );
    }
}
