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
use Ampache\Module\Art\ArtCleanupInterface;

final class ArtCleanupCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private ArtCleanupInterface $artCleanup;

    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on([$this, 'showHelp']);

        $this->onExit(static fn ($exitCode = 0) => exit($exitCode));

        return $this;
    }

    public function __construct(
        ConfigContainerInterface $configContainer,
        ArtCleanupInterface $artCleanup
    ) {
        parent::__construct('cleanup:art', T_('Remove art which does not fit to the settings'));

        $this->configContainer = $configContainer;
        $this->artCleanup      = $artCleanup;

        $this
            ->option('-c|--cleanup', T_('Removes missing files from the database'), 'boolval', false)
            ->option('-t|--thumbnails', T_('Delete all thumbnails'), 'boolval', false)
            ->option('-x|--execute', T_('Disables dry-run'), 'boolval', false);
    }

    public function execute(): void
    {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();
        $cleanup    = $this->values()['cleanup'] === true;
        $delete     = $this->values()['execute'] === true;
        $thumbnails = $this->values()['thumbnails'] === true;

        if (!$thumbnails && !$cleanup) {
            $interactor->info(
                'This file cleans the image table for items that don\'t fit into set dimensions',
                true
            );

            $runable = (
                (
                    !$this->configContainer->get('album_art_min_width') &&
                    $this->configContainer->get('album_art_min_height')
                ) ||
                (
                    !$this->configContainer->get('album_art_max_width') &&
                    !$this->configContainer->get('album_art_max_height')
                )
            );

            if ($runable === false) {
                $interactor->error(
                    T_('Error: A minimum OR maximum height/width must be specified in the config'),
                    true
                );
                $interactor->error(
                    T_('Minimum Dimensions: album_art_min_width AND album_art_min_height'),
                    true
                );
                $interactor->error(
                    T_('Maximum Dimensions: album_art_max_width AND album_art_max_height')
                );

                return;
            }

            if ($delete === true) {
                $interactor->info(
                    T_('Running in Test Mode. Use -x to execute'),
                    true
                );

                return;
            }

            $this->artCleanup->cleanup();
        } else {
            if ($delete === true) {
                $interactor->info(
                    T_('Running in Test Mode. Use -x to execute'),
                    true
                );
            } else {
                $interactor->warn(
                    "***" . T_("WARNING") . "*** " . T_('Running in Write Mode. Make sure you\'ve tested first!'),
                    true
                );
            }
            if ($thumbnails) {
                $interactor->info(
                    'Delete art thumbnails keeping the original images',
                    true
                );

                $this->artCleanup->deleteThumbnails($interactor, $delete);
            }
            if ($cleanup) {
                $interactor->info(
                    'Delete art thumbnails keeping the original images',
                    true
                );

                $this->artCleanup->migrateThumbnails($interactor, $delete);
            }
        }

        $interactor->ok(
            'Clean Completed',
            true
        );
    }
}
