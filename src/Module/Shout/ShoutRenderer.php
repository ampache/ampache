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

namespace Ampache\Module\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Shoutbox;

/**
 * Renders a shout within the ui
 */
final readonly class ShoutRenderer implements ShoutRendererInterface
{
    public function __construct(
        private PrivilegeCheckerInterface $privilegeChecker,
        private ConfigContainerInterface $configContainer,
        private ShoutObjectLoaderInterface $shoutObjectLoader
    ) {
    }

    /**
     * Renders the shout and returns the rendered output
     */
    public function render(Shoutbox $shout, bool $details = true, bool $jsbuttons = false): string
    {
        $object          = $this->shoutObjectLoader->loadByShout($shout);
        $shoutObjectId   = $shout->getObjectId();
        $shoutObjectType = $shout->getObjectType()->value;

        if ($object === null) {
            return '';
        }

        $webPath = $this->configContainer->getWebPath('/client');

        $html   = "<div class='shoutbox-item'>";
        $html .= "<div class='shoutbox-data'>";
        if (
            $details &&
            Art::has_db($shoutObjectId, $shoutObjectType)
        ) {
            $html .= "<div class='shoutbox-img'><img class=\"shoutboximage\" height=\"75\" width=\"75\" src=\"" . $webPath . "/image.php?object_id=" . $shoutObjectId . "&object_type=" . $shoutObjectType . "&size=150x150\" /></div>";
        }
        $html .= "<div class='shoutbox-info'>";
        if ($details) {
            $html .= "<div class='shoutbox-object'>" . $object->get_f_link() . "</div>";
            $html .= "<div class='shoutbox-date'>" . get_datetime($shout->getDate()) . "</div>";
        }
        $html .= "<div class='shoutbox-text'>" . preg_replace('/(\r\n|\n|\r)/', '<br />', $shout->getText()) . "</div>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class='shoutbox-footer'>";
        if ($details) {
            $html .= "<div class='shoutbox-actions'>";
            if ($jsbuttons) {
                $html .= Ajax::button(
                    '?page=stream&action=directplay&playtype=' . $shoutObjectType . '&' . $shoutObjectType . '_id=' . $shoutObjectId,
                    'play_circle',
                    T_('Play'),
                    'play_' . $shoutObjectType . '_' . $shoutObjectId
                );
                $html .= Ajax::button(
                    '?action=basket&type=' . $shoutObjectType . '&id=' . $shoutObjectId,
                    'new_window',
                    T_('Add'),
                    'add_' . $shoutObjectType . '_' . $shoutObjectId
                );
            }
            if ($this->privilegeChecker->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
                $html .= "<a href=\"" . $webPath . "/shout.php?action=show_add_shout&type=" . $shoutObjectType . "&id=" . $shoutObjectId . "\">" . Ui::get_material_symbol('comment', T_('Post Shout')) . "</a>";
            }
            $html .= "</div>";
        }
        $html .= "<div class='shoutbox-user'>" . T_('by') . " ";

        $user = $shout->getUser();
        if ($user !== null) {
            if ($details) {
                $html .= $user->get_f_link();
            } else {
                $html .= $user->getUsername();
            }
        } else {
            $html .= T_('Guest');
        }
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }
}
