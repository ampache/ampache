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

use Ampache\Module\Playback\Localplay\LocalPlay;
use Override;

/**
 * The list of configured localplay instances.
 */
final class LocalplayInstancesView extends AbstractFormView
{
    /**
     * @param string[] $instances
     * @param array<string, array{description: string, type: string}> $fields
     */
    public function __construct(
        string $webPath,
        private readonly LocalPlay $localplay,
        private readonly array $instances,
        private readonly array $fields,
    ) {
        parent::__construct($webPath);
    }

    /**
     * @return array<string, array{description: string, type: string}>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return string[]
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    public function getLocalplay(): LocalPlay
    {
        return $this->localplay;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('form/localplay_instances.phtml');
    }
}
