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

namespace Ampache\Module\Application\Share;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\Util\QrCodeGeneratorInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
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

final class CreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ShareCreatorInterface $shareCreator;

    private QrCodeGeneratorInterface $qrCodeGenerator;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ShareCreatorInterface $shareCreator,
        QrCodeGeneratorInterface $qrCodeGenerator,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->shareCreator    = $shareCreator;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->modelFactory    = $modelFactory;
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

        $body = $request->getParsedBody();

        $secret    = $_REQUEST['secret'] ?? '';
        $type      = $body['type'] ?? '';
        $object_id = $body['id'] ?? null;

        if ($object_id === null) {
            return null;
        }

        /** @var ?Song|Album|Playlist|Video $object */
        $object = $this->modelFactory->mapObjectType($type, (int) $object_id);
        $object->format();

        $share_id = $this->shareCreator->create(
            $_REQUEST['type'],
            (int) $_REQUEST['id'],
            make_bool($_REQUEST['allow_stream']),
            make_bool($_REQUEST['allow_download']),
            (int) $_REQUEST['expire'],
            $secret,
            (int) $_REQUEST['max_counter']
        );

        $this->ui->showHeader();

        if (!$share_id) {
            $this->ui->show(
                'show_add_share.inc.php',
                [
                    'objectLink' => $object->f_link,
                    'secret' => $secret
                ]
            );
        } else {
            $share = new Share($share_id);
            $body  = T_('Share created') . '<br />' .
                T_('You can now start sharing the following URL:') . '<br />' .
                '<a href="' . $share->public_url . '" target="_blank">' . $share->public_url . '</a><br />' .
                '<img src="' . $this->qrCodeGenerator->generate($share->public_url, 128) . '" />' .
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
