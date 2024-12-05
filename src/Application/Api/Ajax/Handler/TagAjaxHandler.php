<?php

declare(strict_types=0);

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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;

final readonly class TagAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private LabelRepositoryInterface $labelRepository,
        private PrivilegeCheckerInterface $privilegeChecker,
    ) {
    }

    public function handle(User $user): void
    {
        $results   = [];
        $action    = $this->requestParser->getFromRequest('action');
        $type      = $this->requestParser->getFromRequest('type');

        // Switch on the actions
        switch ($action) {
            case 'get_tag_map':
                $tags = (in_array($type, ['album_disk_row', 'album_row', 'artist_row', 'song_row', 'video_row']))
                    ? Tag::get_tags()
                    : [];
                $results['tags'] = Tag::get_display($tags);
                break;
            case 'get_labels':
                $labels = ($type == 'artist_row')
                    ? $this->labelRepository->getAll()
                    : [];
                $results['labels'] = Label::get_display($labels);
                break;
            case 'delete':
                if (!$this->privilegeChecker->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
                    debug_event('tag.ajax', $user->getUsername() . ' attempted to delete tag', 1);

                    return;
                }

                debug_event('tag.ajax', 'Deleting tag...', 5);
                $tag = new Tag($_GET['tag_id']);
                $tag->delete();
                header('Location: ' . AmpConfig::get_web_path() . '/browse.php?action=tag&type=artist');

                return;
        } // switch on action;

        // We always do this
        echo xoutput_from_array($results);
    }
}
