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
 */

namespace Ampache\Module\Metadata;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\MetadataFieldRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;
use Ampache\Repository\Model\Metadata;
use Ampache\Repository\Model\MetadataField;
use ArrayIterator;
use Traversable;

/**
 * Manages the access to metadata related data
 */
final class MetadataManager implements MetadataManagerInterface
{
    /**
     * Cache variable for disabled metadata field names
     * @var null|list<string>
     */
    private ?array $disabledMetadataFields = null;

    private MetadataRepositoryInterface $metadataRepository;

    private MetadataFieldRepositoryInterface $metadataFieldRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        MetadataRepositoryInterface $metadataRepository,
        MetadataFieldRepositoryInterface $metadataFieldRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->metadataRepository      = $metadataRepository;
        $this->metadataFieldRepository = $metadataFieldRepository;
        $this->configContainer         = $configContainer;
    }

    /**
     * Returns the metadata for the given item
     *
     * Will return an empty iterator if custom metadata is disabled
     *
     * @return Traversable<Metadata>
     */
    public function getMetadata(
        MetadataEnabledInterface $item
    ): Traversable {
        if (!$this->isCustomMetadataEnabled()) {
            return new ArrayIterator();
        }

        return $this->metadataRepository->findByObjectIdAndType($item->getId(), $item->getMetadataItemType());
    }

    /**
     * Deletes a metadata-item
     */
    public function deleteMetadata(Metadata $metadata): void
    {
        $this->metadataRepository->remove($metadata);
    }

    /**
     * Return all disabled Metadata field names
     *
     * @return list<string>
     */
    public function getDisabledMetadataFields(): array
    {
        if ($this->disabledMetadataFields === null) {
            $disabledFieldNames = [];

            // search field-name by database-primary keys
            $ids = explode(',', (string) $this->configContainer->get(ConfigurationKeyEnum::DISABLED_CUSTOM_METADATA_FIELDS));
            foreach ($ids as $fieldId) {
                $field = $this->metadataFieldRepository->findById((int) $fieldId);
                if ($field !== null) {
                    $disabledFieldNames[] = $field->getName();
                }
            }

            // add field-names from config
            $this->disabledMetadataFields = array_merge(
                $disabledFieldNames,
                explode(',', (string) $this->configContainer->get(ConfigurationKeyEnum::DISABLED_CUSTOM_METADATA_FIELDS_INPUT))
            );
        }

        return $this->disabledMetadataFields;
    }

    /**
     * Adds a new metadata item
     */
    public function addMetadata(
        MetadataEnabledInterface $item,
        string $name,
        string $data
    ): void {
        $metadata = $this->metadataRepository->prototype()
            ->setField($this->getOrCreateField($name))
            ->setObjectId($item->getId())
            ->setType($item->getMetadataItemType())
            ->setData($data);

        $metadata->save();
    }

    public function updateOrAddMetadata(
        MetadataEnabledInterface $item,
        string $name,
        string $data
    ): void {
        $field = $this->getOrCreateField($name);

        $metadata = $this->metadataRepository->findByObjectIdAndFieldAndType($item->getId(), $field, $item->getMetadataItemType());
        if ($metadata !== null) {
            $metadata->setData($data);
            $metadata->save();
        } else {
            $this->addMetadata($item, $name, $data);
        }
    }

    private function getOrCreateField(string $name): MetadataField
    {
        $field = $this->metadataFieldRepository->findByName($name);
        if ($field === null) {
            $field = $this->metadataFieldRepository->prototype();
            $field->setName($name);
            $field->save();
        }

        return $field;
    }

    /**
     * Returns `true` if custom metadata is enabled
     */
    public function isCustomMetadataEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ENABLE_CUSTOM_METADATA);
    }

    /**
     * Cleans up metadata-related database tables
     */
    public function collectGarbage(): void
    {
        $this->metadataRepository->collectGarbage();
        $this->metadataFieldRepository->collectGarbage();
    }
}
