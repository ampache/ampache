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

namespace Ampache\Gui\Form;

use Override;

/**
 * The create/rename-a-playlist-folder form; a null folder id means create.
 */
final class PlaylistFolderFormView extends AbstractFormView
{
    /**
     * @param list<array{id: int, name: string, depth: int}> $parentOptions
     */
    public function __construct(
        string $webPath,
        private readonly ?int $folderId,
        private readonly string $name,
        private readonly int $parentId,
        private readonly array $parentOptions,
    ) {
        parent::__construct($webPath);
    }

    public function getFolderId(): ?int
    {
        return $this->folderId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    /**
     * @return list<array{id: int, name: string, depth: int}>
     */
    public function getParentOptions(): array
    {
        return $this->parentOptions;
    }

    public function isEdit(): bool
    {
        return $this->folderId !== null;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('form/playlist_folder.phtml');
    }
}
