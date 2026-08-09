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

namespace Ampache\Gui\Edit\Renderer;

use Ampache\Gui\Edit\AbstractEditFormRenderer;
use Ampache\Repository\Model\Tag;
use Override;

/**
 * The genre edit dialog.
 */
final class TagEditFormRenderer extends AbstractEditFormRenderer
{
    public function getMergedTags(): string
    {
        return Tag::get_display($this->getItem()->get_merged_tags());
    }

    public function getName(): string
    {
        return (string) $this->getItem()->name;
    }

    public function getTagId(): int
    {
        return $this->getItem()->getId();
    }

    public function isHidden(): bool
    {
        return (bool) $this->getItem()->is_hidden;
    }

    /**
     * The hidden-genre page opens its own dialog, which defaults to a persistent merge and never offers
     * "Keep Existing". That is a property of the dialog, not of the genre's current hidden flag.
     */
    public function isHiddenVariant(): bool
    {
        return $this->getContext()->objectType === 'tag_hidden_row';
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/tag.phtml');
    }

    private function getItem(): Tag
    {
        /** @var Tag $item */
        $item = $this->getContext()->item;

        return $item;
    }
}
