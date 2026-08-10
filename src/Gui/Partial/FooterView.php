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

namespace Ampache\Gui\Partial;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\User;
use Override;

/**
 * The page footer: the closing chrome, the player mount and the rightbar's click handlers.
 */
final class FooterView extends AbstractView
{
    public function getCustomText(): string
    {
        return (string) AmpConfig::get('custom_text_footer');
    }

    public function getFooterClass(): string
    {
        return ($this->hasTemporaryPlaylist() || AmpConfig::get('play_type') === 'localplay') ? '' : 'footer-wild';
    }

    public function getSprite(): string
    {
        return Ui::material_symbol_sprite();
    }

    public function getVersion(): string
    {
        return T_('Ampache') . ' ' . AmpConfig::get('version');
    }

    public function getVisualizer(): string
    {
        return (new VisualizerView())->render();
    }

    /**
     * The login page renders this footer without a session, and has no player to mount.
     */
    public function isLoggedIn(): bool
    {
        return !isset($_SESSION['login']) || !$_SESSION['login'];
    }

    public function showCustomText(): bool
    {
        return (bool) AmpConfig::get('custom_text_footer');
    }

    public function showDonate(): bool
    {
        return (bool) AmpConfig::get('show_donate');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/footer.phtml');
    }

    private function hasTemporaryPlaylist(): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $user = Core::get_global('user');

        return $user instanceof User && $user->playlist && $user->playlist->has_items();
    }
}
