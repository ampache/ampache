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

namespace Ampache\Repository\Model;

use Ampache\Module\Database\BaseModel;
use Ampache\Repository\LicenseRepository;
use Ampache\Repository\LicenseRepositoryInterface;

/**
 * License item
 *
 * @see LicenseRepository
 */
class License extends BaseModel
{
    private ?string $description   = null;
    private ?string $external_link = null; // Link to the license page
    private ?string $name          = null;
    private ?int $order            = null; // Item order on the license page

    public function __construct(private readonly LicenseRepositoryInterface $licenseRepository) {}

    /**
     * Returns the description
     */
    public function getDescription(): string
    {
        return (string) $this->description;
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
     *
     * A tag value can carry any scheme at all, and this reaches the browser as a raw href: linking a
     * `javascript:` or `data:` value would run it in the viewer's session the moment they click, so only
     * http(s) is ever rendered as a link. Escaping still runs regardless, since a scheme check alone does
     * not stop a value from breaking out of the attribute it sits in.
     */
    public function getLinkFormatted(): string
    {
        if ($this->hasLinkableExternalLink()) {
            return sprintf(
                '<a href="%s">%s</a>',
                scrub_out($this->external_link),
                scrub_out($this->name)
            );
        }

        return scrub_out($this->name);
    }

    /**
     * Returns the name
     */
    public function getName(): string
    {
        return (string) $this->name;
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
            $result !== null
            && $this->isNew()
        ) {
            $this->id = $result;
        }
    }

    /**
     * Sets the description
     */
    public function setDescription(string $value): License
    {
        $this->description = $value;

        return $this;
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
     * Set the name
     *
     * Stored as it was given: every reader escapes on the way out, and a name held escaped never matches the tag
     * it came from, so a scan would create the same licence again on every pass.
     */
    public function setName(string $value): License
    {
        $this->name = $value;

        return $this;
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
     * http(s) only: every other scheme is a way to run script in the viewer's session rather than a place
     * to send them, and no license anyone would type or tag a file with legitimately needs one.
     */
    private function hasLinkableExternalLink(): bool
    {
        if ((string) $this->external_link === '') {
            return false;
        }

        $scheme = parse_url((string) $this->external_link, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https'], true);
    }
}
