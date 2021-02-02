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

declare(strict_types=0);

namespace Ampache\Module\Api\Edit;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\database_object;
use Ampache\Model\Tag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\LabelRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class EditObjectAction extends AbstractEditAction
{
    public const REQUEST_KEY = 'edit_object';

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private LabelRepositoryInterface $labelRepository;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        LabelRepositoryInterface $labelRepository
    ) {
        parent::__construct($configContainer, $logger);
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->labelRepository = $labelRepository;
    }

    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        database_object $libitem,
        int $object_id
    ): ?ResponseInterface {
        // Scrub the data, walk recursive through array
        $entities = function (&$data) use (&$entities) {
            foreach ($data as $key => $value) {
                $data[$key] = is_array($value) ? $entities($value) : unhtmlentities((string) scrub_in($value));
            }

            return $data;
        };
        $entities($_POST);

        if (empty($object_type)) {
            $object_type = filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        } else {
            $object_type = implode('_', explode('_', $object_type, -1));
        }

        $class_name = ObjectTypeToClassNameMapper::map($object_type);
        $libitem    = new $class_name($_POST['id']);
        if ($libitem->get_user_owner() == Core::get_global('user')->id && AmpConfig::get('upload_allow_edit') && !Access::check('interface', 50)) {
            // TODO: improve this uniqueless check
            if (filter_has_var(INPUT_POST, 'user')) {
                unset($_POST['user']);
            }
            if (filter_has_var(INPUT_POST, 'artist')) {
                unset($_POST['artist']);
            }
            if (filter_has_var(INPUT_POST, 'artist_name')) {
                unset($_POST['artist_name']);
            }
            if (filter_has_var(INPUT_POST, 'album')) {
                unset($_POST['album']);
            }
            if (filter_has_var(INPUT_POST, 'album_name')) {
                unset($_POST['album_name']);
            }
            if (filter_has_var(INPUT_POST, 'album_artist')) {
                unset($_POST['album_artist']);
            }
            if (filter_has_var(INPUT_POST, 'album_artist_name')) {
                unset($_POST['album_artist_name']);
            }
            if (filter_has_var(INPUT_POST, 'edit_tags')) {
                $_POST['edit_tags'] = Tag::clean_to_existing($_POST['edit_tags']);
            }
            if (filter_has_var(INPUT_POST, 'edit_labels')) {
                $_POST['edit_labels'] = $this->clean_to_existing($_POST['edit_labels']);
            }
            // Check mbid and *_mbid match as it is used as identifier
            if (filter_has_var(INPUT_POST, 'mbid')) {
                $_POST['mbid'] = $libitem->mbid;
            }
            if (filter_has_var(INPUT_POST, 'mbid_group')) {
                $_POST['mbid_group'] = $libitem->mbid_group;
            }
        }

        $libitem->format();
        $new_id     = $libitem->update($_POST);
        $class_name = ObjectTypeToClassNameMapper::map($object_type);
        $libitem    = new $class_name($new_id);
        $libitem->format();

        xoutput_headers();
        $results = array('id' => $new_id);
        echo (string) xoutput_from_array($results);

        return null;
    }

    /**
     * clean_to_existing
     * Clean label list to existing label list only
     * @param array|string $labels
     * @return array|string
     */
    private function clean_to_existing($labels)
    {
        $array = (is_array($labels)) ? $labels : preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $labels);
        $ret   = array();
        foreach ($array as $label) {
            $label = trim((string)$label);
            if (!empty($label)) {
                if ($this->labelRepository->lookup($label) > 0) {
                    $ret[] = $label;
                }
            }
        }

        return (is_array($labels) ? $ret : implode(",", $ret));
    }
}
