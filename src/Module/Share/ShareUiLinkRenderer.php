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
 */

declare(strict_types=1);

namespace Ampache\Module\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Plugin;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

final class ShareUiLinkRenderer implements ShareUiLinkRendererInterface
{
    private FunctionCheckerInterface $functionChecker;

    private ZipHandlerInterface $zipHandler;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        FunctionCheckerInterface $functionChecker,
        ZipHandlerInterface $zipHandler,
        ConfigContainerInterface $configContainer
    ) {
        $this->functionChecker = $functionChecker;
        $this->zipHandler      = $zipHandler;
        $this->configContainer = $configContainer;
    }

    public function render(
        string $object_type,
        int $object_id
    ): string {
        $webPath = $this->configContainer->getWebPath();

        $link = '<ul>';
        $link .= sprintf(
            '<li><a onclick="handleShareAction(\'%s/share.php?action=show_create&type=%s&id=%d\')">%s &nbsp;%s</a></li>',
            $webPath,
            $object_type,
            $object_id,
            Ui::get_icon(
                'share',
                T_('Advanced Share')
            ),
            T_('Advanced Share')
        );
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DOWNLOAD)) {
            $dllink = '';
            if ($object_type == 'song' || $object_type == 'video') {
                $dllink = sprintf(
                    '%s/play/index.php?action=download&type=%s&oid=%d&uid=-1',
                    $webPath,
                    $object_type,
                    $object_id
                );
            } else {
                if (
                    $this->functionChecker->check(AccessLevelEnum::FUNCTION_BATCH_DOWNLOAD) &&
                    $this->zipHandler->isZipable($object_type)
                ) {
                    $dllink = sprintf(
                        '%s/batch.php?action=%s&id=%d',
                        $webPath,
                        $object_type,
                        $object_id
                    );
                }
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
                    Ui::get_icon(
                        'download',
                        T_('Temporary direct link')
                    ),
                    T_('Temporary direct link')
                );
            }
        }
        $link .= '<li style=\'padding-top: 8px; text-align: right;\'>';

        $plugins = Plugin::get_plugins('external_share');
        foreach ($plugins as $plugin_name) {
            $link .= sprintf(
                '<a onclick="handleShareAction(\'%s/share.php?action=external_share&plugin=%s&type=%s&id=%d\')" target="_blank">%s</a>&nbsp;',
                $webPath,
                $plugin_name,
                $object_type,
                $object_id,
                Ui::get_icon(
                    'share_' . strtolower((string)$plugin_name),
                    $plugin_name
                )
            );
        }

        $link .= '</li>';
        $link .= '</ul>';

        return $link;
    }
}
