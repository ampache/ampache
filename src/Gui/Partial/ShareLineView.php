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

use Ampache\Gui\View\AbstractView;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\Util\Ui;
use Override;

/**
 * One line for song, album and artist pages: any visitor copies or posts the permalink, accounts also share
 */
final class ShareLineView extends AbstractView
{
    public function __construct(
        private readonly string $objectType,
        private readonly int $objectId,
        private readonly string $link,
        private readonly string $webPath,
        private readonly bool $mayShareAdvanced,
    ) {}

    public function getLink(): string
    {
        return $this->link;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * One icon per installed network plugin, keyed by the plugin name the share url takes
     *
     * @return array<string, string>
     */
    public function getPluginIcons(): array
    {
        $icons = [];
        foreach (Plugin::get_plugins(PluginTypeEnum::EXTERNAL_SHARE) as $name) {
            $icons[$name] = Ui::get_icon('share_' . strtolower($name), new Plugin($name)->_plugin?->description);
        }

        return $icons;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function mayShareAdvanced(): bool
    {
        return $this->mayShareAdvanced;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/share_line.phtml');
    }
}
