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

namespace Ampache\Gui\Shout;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Shout\ShoutRendererInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Shoutbox;
use Override;

/**
 * The post-a-shout form and the shoutbox beneath it.
 *
 * Its comment heading cell never closed, and the offset it carries reached a `value=` unescaped.
 */
final class AddShoutView extends AbstractView
{
    /**
     * @param array<Shoutbox> $shouts
     */
    public function __construct(
        private readonly library_item $object,
        private readonly LibraryItemEnum $objectType,
        private readonly string $data,
        private readonly array $shouts,
        private readonly ShoutRendererInterface $shoutRenderer,
        private readonly string $webPath,
        private readonly bool $mayPost,
        private readonly bool $mayStick,
    ) {}

    public function getBoxTitle(): string
    {
        $title = T_('Post to Shoutbox');

        return ($this->data === '') ? $title : $title . ' (' . $this->data . ')';
    }

    /**
     * A shout may be pinned to a position in the item, which is what this offset records.
     */
    public function getData(): string
    {
        return $this->data;
    }

    public function getFormAction(): string
    {
        return $this->webPath . '/shout.php?action=add_shout';
    }

    public function getObjectId(): int
    {
        return $this->object->getId();
    }

    public function getObjectType(): string
    {
        return $this->objectType->value;
    }

    public function getShoutbox(): string
    {
        return ($this->shouts === []) ? '' : new ShoutboxView($this->shoutRenderer, $this->shouts)->render();
    }

    public function getShoutboxTitle(): string
    {
        return $this->object->get_fullname() . ' ' . T_('Shoutbox');
    }

    public function mayPost(): bool
    {
        return $this->mayPost;
    }

    public function mayStick(): bool
    {
        return $this->mayStick;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('shout/add_shout.phtml');
    }
}
