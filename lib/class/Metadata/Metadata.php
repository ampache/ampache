<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

namespace Lib\Metadata;

use AmpConfig;
use Lib\Metadata\Model\MetadataField;

/**
 * Description of metadata
 *
 * @author raziel
 */
trait Metadata
{
    /**
     *
     * @var Repository\Metadata
     */
    protected $metadataRepository;

    /**
     *
     * @var Repository\MetadataField
     */
    protected $metadataFieldRepository;

    /**
     * Determines if the functionality is enabled or not.
     * @var boolean
     */
    protected $enableCustomMetadata;

    /**
     * Cache variable for disabled metadata field names
     * @var array
     */
    protected $disabledMetadataFields = array();

    /**
     * Initialize the repository variables. Needs to be called first if the trait should do something.
     */
    protected function initializeMetadata()
    {
        $this->metadataRepository      = new Repository\Metadata();
        $this->metadataFieldRepository = new Repository\MetadataField();
    }

    /**
     *
     * @return Model\Metadata
     */
    public function getMetadata()
    {
        return $this->metadataRepository->findByObjectIdAndType($this->id, get_class($this));
    }

    /**
     *
     * @param Model\Metadata $metadata
     */
    public function deleteMetadata(Model\Metadata $metadata)
    {
        $this->metadataRepository->remove($metadata);
    }

    /**
     *
     * @param MetadataField $field
     * @param string $data
     * @throws \ReflectionException
     */
    public function addMetadata(MetadataField $field, $data)
    {
        $metadata = new Model\Metadata();
        $metadata->setField($field);
        $metadata->setObjectId($this->id);
        $metadata->setType(get_class($this));
        $metadata->setData($data);
        $this->metadataRepository->add($metadata);
    }

    /**
     * @param Model\MetadataField $field
     * @param $data
     * @throws \ReflectionException
     */
    public function updateOrInsertMetadata(MetadataField $field, $data)
    {
        /* @var Model\Metadata $metadata */
        $metadata = $this->metadataRepository->findByObjectIdAndFieldAndType($this->id, $field, get_class($this));
        if ($metadata) {
            $object = reset($metadata);
            $object->setData($data);
            $this->metadataRepository->update($object);
        } else {
            $this->addMetadata($field, $data);
        }
    }

    /**
     *
     * @param string $name
     * @param boolean $public
     * @return MetadataField
     * @throws \ReflectionException
     */
    protected function createField($name, $public)
    {
        $field = new MetadataField();
        $field->setName($name);
        if (!$public) {
            $field->hide();
        }
        $this->metadataFieldRepository->add($field);

        return $field;
    }

    /**
     *
     * @param string $propertie
     * @param boolean $public
     * @return MetadataField
     * @throws \ReflectionException
     */
    public function getField($propertie, $public = true)
    {
        $fields = $this->metadataFieldRepository->findByName($propertie);
        if (count($fields)) {
            $field = reset($fields);
        } else {
            $field = $this->createField($propertie, $public);
        }

        return $field;
    }

    /**
     *
     * @return boolean
     */
    public static function isCustomMetadataEnabled()
    {
        return (boolean) AmpConfig::get('enable_custom_metadata');
    }

    /**
     * Get all disabled Metadata field names
     * @return array
     */
    public function getDisabledMetadataFields()
    {
        if (empty($this->disabledMetadataFields)) {
            $fields = array();
            $ids    = explode(',', AmpConfig::get('disabled_custom_metadata_fields'));
            foreach ($ids as $metaid) {
                $field = $this->metadataFieldRepository->findById($metaid);
                if ($field) {
                    $fields[] = $field->getName();
                }
            }
            $this->disabledMetadataFields = array_merge(
                    $fields, explode(',', AmpConfig::get('disabled_custom_metadata_fields_input'))
            );
        }

        return $this->disabledMetadataFields;
    }
}
