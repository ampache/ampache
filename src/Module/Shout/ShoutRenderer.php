<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Shoutbox;

/**
 * Renders a shout within the ui
 */
final class ShoutRenderer implements ShoutRendererInterface
{
    private PrivilegeCheckerInterface $privilegeChecker;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->privilegeChecker = $privilegeChecker;
        $this->configContainer  = $configContainer;
        $this->modelFactory     = $modelFactory;
    }

    /**
     * Renders the shout and returns the rendered output
     */
    public function render(Shoutbox $shout, bool $details = true, bool $jsbuttons = false): string
    {
        $object = Shoutbox::get_object((string)$shout->object_type, $shout->object_id);
        if ($object === null) {
            return '';
        }

        $webPath = $this->configContainer->getWebPath();

        $html   = "<div class='shoutbox-item'>";
        $html .= "<div class='shoutbox-data'>";
        if (
            $details
            && Art::has_db($shout->object_id, (string)$shout->object_type)
        ) {
            $html .= "<div class='shoutbox-img'><img class=\"shoutboximage\" height=\"75\" width=\"75\" src=\"" . $webPath . "/image.php?object_id=" . $shout->object_id . "&object_type=" . $shout->object_type . "&thumb=1\" /></div>";
        }
        $html .= "<div class='shoutbox-info'>";
        if ($details) {
            $html .= "<div class='shoutbox-object'>" . $object->get_f_link() . "</div>";
            $html .= "<div class='shoutbox-date'>" . get_datetime($shout->date) . "</div>";
        }
        $html .= "<div class='shoutbox-text'>" . $shout->getTextFormatted() . "</div>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class='shoutbox-footer'>";
        if ($details) {
            $html .= "<div class='shoutbox-actions'>";
            if ($jsbuttons) {
                $html .= Ajax::button(
                    '?page=stream&action=directplay&playtype=' . $shout->object_type . '&' . $shout->object_type . '_id=' . $shout->object_id,
                    'play',
                    T_('Play'),
                    'play_' . $shout->object_type . '_' . $shout->object_id
                );
                $html .= Ajax::button(
                    '?action=basket&type=' . $shout->object_type . '&id=' . $shout->object_id,
                    'add',
                    T_('Add'),
                    'add_' . $shout->object_type . '_' . $shout->object_id
                );
            }
            if ($this->privilegeChecker->check(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)) {
                $html .= "<a href=\"" . $webPath . "/shout.php?action=show_add_shout&type=" . $shout->object_type . "&id=" . $shout->object_id . "\">" . Ui::get_icon('comment', T_('Post Shout')) . "</a>";
            }
            $html .= "</div>";
        }
        $html .= "<div class='shoutbox-user'>" . T_('by') . " ";

        if ($shout->user > 0) {
            $user = $this->modelFactory->createUser($shout->user);
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
