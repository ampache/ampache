<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\PrivateMessage\PrivateMessageRowView;
use Ampache\Repository\Model\PrivateMsg;
use Override;

/**
 * The private message browse.
 *
 * The table this replaced was labelled `data-objecttype="label"` and spanned its empty state across five
 * of six columns, both from being copied off the label browse.
 */
final class PrivateMessageListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
    ) {}

    /**
     * @return list<array{class: string, label: string, sort: null|string}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_select essential persist', 'label' => '', 'sort' => null],
            ['class' => 'cel_subject essential persist', 'label' => T_('Subject'), 'sort' => 'subject'],
            ['class' => 'cel_from_user essential', 'label' => T_('Sender'), 'sort' => 'from_user'],
            ['class' => 'cel_to_user essential', 'label' => T_('Recipient'), 'sort' => 'to_user'],
            ['class' => 'cel_creation_date essential', 'label' => T_('Date'), 'sort' => 'creation_date'],
            ['class' => 'cel_action essential', 'label' => T_('Action'), 'sort' => null],
        ];
    }

    /**
     * @return list<PrivateMsg>
     */
    public function getMessages(): array
    {
        $messages = [];
        foreach ($this->getObjectIds() as $objectId) {
            $message = new PrivateMsg($objectId);
            if (!$message->isNew()) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    public function getWebPath(): string
    {
        return $this->configContainer->getWebPath('/client');
    }

    public function renderRow(PrivateMsg $message): string
    {
        return new PrivateMessageRowView($this->getWebPath('/client'), $message)->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/private_messages.phtml');
    }
}
