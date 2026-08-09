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
 * The confirm-an-action page with a Continue button and an optional Cancel.
 */
final class ConfirmationView extends AbstractFormView
{
    public function __construct(
        string $webPath,
        private readonly string $title,
        private readonly string $text,
        private readonly string $path,
        private readonly string $formName,
        private readonly ?string $cancelUrl,
    ) {
        parent::__construct($webPath);
    }

    public function getCancelUrl(): ?string
    {
        return $this->cancelUrl;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('form/confirmation.phtml');
    }
}
