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
 * The edit-a-localplay-instance form.
 */
final class LocalplayEditInstanceView extends AbstractFormView
{
    /**
     * @param array<string, int|string> $instance
     * @param array<string, array{description: string, type: string}> $fields
     */
    public function __construct(
        string $webPath,
        private readonly int $instanceId,
        private readonly array $instance,
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
     * @return array<string, int|string>
     */
    public function getInstance(): array
    {
        return $this->instance;
    }

    public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('form/localplay_edit_instance.phtml');
    }
}
