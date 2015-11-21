<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

namespace lib\Metadata;

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
        $this->metadataRepository      = new \lib\Metadata\Repository\Metadata();
        $this->metadataFieldRepository = new \lib\Metadata\Repository\MetadataField();
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
     * @param \lib\Metadata\Model\MetadataField $field
     * @param type $data
     */
    public function addMetadata(\lib\Metadata\Model\MetadataField $field, $data)
    {
        $metadata = new \lib\Metadata\Model\Metadata();
        $metadata->setField($field);
        $metadata->setObjectId($this->id);
        $metadata->setType(get_class($this));
        $metadata->setData($data);
        $this->metadataRepository->add($metadata);
    }

    public function updateOrInsertMetadata(\lib\Metadata\Model\MetadataField $field, $data)
    {
        /* @var $metadata Model\Metadata */
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
     * @param type $name
     * @param type $public
     * @return \lib\Metadata\Model\MetadataField
     */
    protected function createField($name, $public)
    {
        $field = new \lib\Metadata\Model\MetadataField();
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
     * @return Model\MetadataField
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
        return (boolean) \AmpConfig::get('enable_custom_metadata');
    }

    /**
     * Get all disabled Metadata field names
     * @return array
     */
    public function getDisabledMetadataFields()
    {
        if (!$this->disabledMetadataFields) {
            $fields = array();
            $ids    = explode(',', \AmpConfig::get('disabled_custom_metadata_fields'));
            foreach ($ids as $id) {
                $field = $this->metadataFieldRepository->findById($id);
                if ($field) {
                    $fields[] = $field->getName();
                }
            }
            $this->disabledMetadataFields = array_merge(
                    $fields, explode(',', \AmpConfig::get('disabled_custom_metadata_fields_input'))
            );
        }
        return $this->disabledMetadataFields;
    }
}
