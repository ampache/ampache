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

namespace Ampache\Module\Api\OpenSubsonic\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use Ampache\Repository\Model\User;
use Ampache\Repository\PrivateMessageRepositoryInterface;

final class ChatHandler implements ChatHandlerInterface
{
    public function __construct(
        private readonly PrivateMessageRepositoryInterface $privateMessageRepository,
        private readonly OpenSubsonicResponseHandlerInterface $responseHandler,
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
    ) {}

    /**
     * addChatMessage
     *
     * Adds a message to the chat log.
     * https://opensubsonic.netlify.app/docs/endpoints/addchatmessage/
     * @param array<string, mixed> $input
     */
    public function addchatmessage(array $input, User $user): void
    {
        $message = $this->responseHandler->checkParameter($input, 'message', __FUNCTION__);
        if ($message === false) {
            return;
        }

        if (!AmpConfig::get('sociable')) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_GENERIC, __FUNCTION__);

            return;
        }

        $this->privateMessageRepository->create(null, $user, '', trim($message));

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * getChatMessages
     *
     * Returns the current visible (non-expired) chat messages.
     * https://opensubsonic.netlify.app/docs/endpoints/getchatmessages/
     * @param array<string, mixed> $input
     */
    public function getchatmessages(array $input, User $user): void
    {
        unset($user);
        $since        = (int) ($input['since'] ?? 0);
        $pmRepository = $this->privateMessageRepository;

        $pmRepository->cleanChatMessages();

        if (!AmpConfig::get('sociable')) {
            $messages = [];
        } else {
            $messages = $pmRepository->getChatMessages($since);
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addChatMessages($response, $messages);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addChatMessages($response, $messages);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }
}
