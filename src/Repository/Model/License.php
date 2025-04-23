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

namespace Ampache\Repository\Model;

use Ampache\Repository\LicenseRepository;
use Ampache\Repository\LicenseRepositoryInterface;

/**
 * License item
 *
 * @see LicenseRepository
 */
class License extends BaseModel
{
    private ?string $name = null;

    private ?string $description = null;

    private ?string $external_link = null; // Link to the license page

    private ?int $order = null; // Item order on the license page

    public function __construct(private readonly LicenseRepositoryInterface $licenseRepository)
    {
    }

    /**
     * Set the name
     */
    public function setName(string $value): License
    {
        $this->name = htmlspecialchars($value);

        return $this;
    }

    /**
     * Returns the name
     */
    public function getName(): string
    {
        return (string) $this->name;
    }

    /**
     * Sets the description
     */
    public function setDescription(string $value): License
    {
        $this->description = htmlspecialchars($value);

        return $this;
    }

    /**
     * Returns the description
     */
    public function getDescription(): string
    {
        return (string) $this->description;
    }

    /**
     * Sets the external-link
     */
    public function setExternalLink(string $value): License
    {
        $this->external_link = $value;

        return $this;
    }

    /**
     * Returns the external-link
     */
    public function getExternalLink(): string
    {
        return (string) $this->external_link;
    }

    /**
     * Returns the external-link as html a-tag
     */
    public function getLinkFormatted(): string
    {
        if ((string) $this->external_link !== '') {
            return sprintf(
                '<a href="%s">%s</a>',
                $this->external_link,
                scrub_out($this->name)
            );
        }

        return scrub_out($this->name);
    }

    /**
     * Sets the order
     */
    public function setOrder(?int $value): License
    {
        $this->order = $value;

        return $this;
    }

    /**
     * Returns the order
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * Persists the object
     */
    public function save(): void
    {
        $result = $this->licenseRepository->persist($this);

        if (
            $result !== null &&
            $this->isNew()
        ) {
            $this->id = $result;
        }
    }
}
