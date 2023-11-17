<?php

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
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Application\Share;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Share;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class CreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private LoggerInterface $logger;

    private PasswordGeneratorInterface $passwordGenerator;

    private ZipHandlerInterface $zipHandler;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        LoggerInterface $logger,
        PasswordGeneratorInterface $passwordGenerator,
        ZipHandlerInterface $zipHandler
    ) {
        $this->configContainer   = $configContainer;
        $this->ui                = $ui;
        $this->logger            = $logger;
        $this->passwordGenerator = $passwordGenerator;
        $this->zipHandler        = $zipHandler;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) ||
            !Core::form_verify('add_share')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $share_id = Share::create_share(
            Core::get_global('user')->id,
            $_REQUEST['type'] ?? '',
            (int)($_REQUEST['id'] ?? 0),
            (int)($_REQUEST['allow_stream'] ?? 0),
            (int)($_REQUEST['allow_download'] ?? 0),
            (int) $_REQUEST['expire'],
            $_REQUEST['secret'],
            (int) $_REQUEST['max_counter']
        );

        if (!$share_id) {
            $this->logger->error(
                'Share failed: ' . (int)($_REQUEST['id'] ?? 0),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            $object_type = $_REQUEST['type'] ?? '';
            $className   = ObjectTypeToClassNameMapper::map($object_type);
            /** @var Song|Album|AlbumDisk|Playlist|Video $object */
            $object = new $className((int)$_REQUEST['id']);
            if ($object->id) {
                $object->format();
                $message   = T_('Failed to create share');
                $token     = $this->passwordGenerator->generate_token();
                $isZipable = $this->zipHandler->isZipable($object_type);
                $this->ui->show(
                    'show_add_share.inc.php',
                    [
                        'has_failed' => true,
                        'message' => $message,
                        'object' => $object,
                        'object_type' => $object_type,
                        'token' => $token,
                        'isZipable' => $isZipable
                    ]
                );
            } else {
                $this->ui->showContinue(
                    T_('There Was a Problem'),
                    T_('Failed to create share'),
                    AmpConfig::get('web_path') . '/stats.php?action=share'
                );
            }
        } else {
            $share = new Share($share_id);
            $body  = T_('Share created') . '<br />' .
                T_('You can now start sharing the following URL:') . '<br />' .
                '<a href="' . $share->public_url . '" target="_blank">' . $share->public_url . '</a><br />' .
                '<div id="share_qrcode" style="text-align: center"></div>' .
                '<script>$(\'#share_qrcode\').qrcode({text: "' . $share->public_url . '", width: 128, height: 128});</script>' .
                '<br /><br />' .
                T_('You can also embed this share as a web player into your website, with the following HTML code:') . '<br />' .
                '<i>' . htmlentities('<iframe style="width: 630px; height: 75px;" src="' . Share::get_url($share->id, $share->secret) . '&embed=true"></iframe>') . '</i><br />';

            $title = T_('No Problem');
            $this->ui->showConfirmation(
                $title,
                $body,
                AmpConfig::get('web_path') . '/stats.php?action=share'
            );
        }
        $this->ui->showFooter();

        return null;
    }
}
