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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;
use Override;

final class AdminClearCacheCommand extends Command
{
    public function __construct()
    {
        parent::__construct('admin:clearCache', T_('Clear a cache. Only `perpetual_api_session` persists; object caches live for one process'));

        $this
            ->argument('[type]', T_('Type') . " ('perpetual_api_session', 'song', 'artist', 'album')", 'perpetual_api_session')
            ->usage('<bold>  admin:clearCache perpetual_api_session</end> <comment> ## ' . T_('Destroy stored perpetual API sessions') . '</end><eol/>');
    }

    public function execute(
        string $type,
    ): void {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();

        switch ($type) {
            case 'perpetual_api_session':
                Session::destroy_perpetual();
                break;
            case 'song':
                Song::clear_cache();
                break;
            case 'artist':
                Artist::clear_cache();
                break;
            case 'album':
                Album::clear_cache();
                break;
            default:
                $interactor->error(sprintf(T_('Unknown cache type: %s'), $type), true);

                return;
        }

        $interactor->ok(sprintf(T_('Cleared cache: %s'), $type), true);
    }

    #[Override]
    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on($this->showHelp(...));

        $this->onExit(static fn($exitCode = 0) => exit($exitCode));

        return $this;
    }
}
