<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Repository\Model;

use Ampache\Repository\LicenseRepositoryInterface;

final class License implements LicenseInterface
{
    private LicenseRepositoryInterface $licenseRepository;

    private int $id;

    private ?array $data = null;

    /**
     * This pulls the license information from the database
     */
    public function __construct(
        LicenseRepositoryInterface $licenseRepository,
        int $id
    ) {
        $this->licenseRepository = $licenseRepository;
        $this->id                = $id;
    }

    public function getId(): int
    {
        return (int) ($this->getData()['id'] ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    public function getLinkFormatted(): string
    {
        $name         = $this->getName();
        $externalLink = $this->getLink();

        if ($externalLink !== '') {
            return sprintf(
                '<a href="%s">%s</a>',
                $externalLink,
                $name
            );
        }

        return $name;
    }

    public function getName(): string
    {
        return (string) ($this->getData()['name'] ?? '');
    }

    public function getLink(): string
    {
        return (string) ($this->getData()['external_link'] ?? '');
    }

    public function getDescription(): string
    {
        return (string) ($this->getData()['description'] ?? '');
    }

    private function getData(): array
    {
        if ($this->data === null) {
            $this->data = $this->licenseRepository->getDataById($this->id);
        }

        return $this->data;
    }
}
