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

namespace Ampache\Module\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Plugin;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

final readonly class ShareUiLinkRenderer implements ShareUiLinkRendererInterface
{
    public function __construct(
        private FunctionCheckerInterface $functionChecker,
        private ZipHandlerInterface $zipHandler,
        private ConfigContainerInterface $configContainer
    ) {
    }

    public function render(
        LibraryItemEnum $object_type,
        int $object_id
    ): string {
        $webPath = $this->configContainer->getWebPath('/client');

        $link = '<ul>';
        $link .= sprintf(
            '<li><a onclick="handleShareAction(\'%s/share.php?action=show_create&type=%s&id=%d\')">%s &nbsp;%s</a></li>',
            $webPath,
            $object_type->value,
            $object_id,
            Ui::get_material_symbol(
                'share',
                T_('Advanced Share')
            ),
            T_('Advanced Share')
        );
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DOWNLOAD)) {
            $dllink = '';
            if ($object_type === LibraryItemEnum::SONG || $object_type === LibraryItemEnum::VIDEO) {
                $dllink = sprintf(
                    '%s/play/index.php?action=download&type=%s&oid=%d&uid=-1',
                    $webPath,
                    $object_type->value,
                    $object_id
                );
            } elseif (
                $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) &&
                $this->zipHandler->isZipable($object_type->value)
            ) {
                $dllink = sprintf(
                    '%s/batch.php?action=%s&id=%d',
                    $webPath,
                    $object_type->value,
                    $object_id
                );
            }

            if (!empty($dllink)) {
                if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::REQUIRE_SESSION)) {
                    // Add session information to the link to avoid authentication
                    $dllink .= sprintf(
                        '&ssid=%s',
                        Stream::get_session()
                    );
                }
                $link .= sprintf(
                    "<li><a class=\"nohtml\" href=\"%s\">%s &nbsp;%s</a></li>",
                    $dllink,
                    Ui::get_material_symbol(
                        'download',
                        T_('Temporary direct link')
                    ),
                    T_('Temporary direct link')
                );
            }
        }
        $link .= '<li style=\'padding-top: 8px; text-align: right;\'>';

        $plugins = Plugin::get_plugins(PluginTypeEnum::EXTERNAL_SHARE);
        foreach ($plugins as $plugin_name) {
            $link .= sprintf(
                '<a onclick="handleShareAction(\'%s/share.php?action=external_share&plugin=%s&type=%s&id=%d\')" target="_blank">%s</a>&nbsp;',
                $webPath,
                $plugin_name,
                $object_type->value,
                $object_id,
                Ui::get_icon(
                    'share_' . strtolower($plugin_name),
                    $plugin_name
                )
            );
        }

        $link .= '</li>';
        $link .= '</ul>';

        return $link;
    }
}
