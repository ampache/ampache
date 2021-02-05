<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=1);

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\Exception\HandshakeException;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Authentication\HandshakeInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class HandshakeMethod implements MethodInterface
{
    public const ACTION = 'handshake';

    private StreamFactoryInterface $streamFactory;

    private HandshakeInterface $handshake;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        HandshakeInterface $handshake
    ) {
        $this->streamFactory = $streamFactory;
        $this->handshake     = $handshake;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This is the function that handles verifying a new handshake
     * Takes a timestamp, auth key, and username.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * auth      = (string) $passphrase
     * user      = (string) $username //optional
     * timestamp = (integer) UNIXTIME() //Required if login/password authentication
     * version   = (string) $version //optional
     *
     * @return ResponseInterface
     *
     * @throws Exception\HandshakeFailedException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $passphrase = $input['auth'] ?? '';
        if ($passphrase === '') {
            $passphrase = Core::get_post('auth');
        }
        $version   = (isset($input['version'])) ? $input['version'] : Api::$version;
        $timestamp = (int) preg_replace('/[^0-9]/', '', $input['timestamp'] ?? time());

        try {
            $user = $this->handshake->handshake(
                trim((string) $input['user']),
                trim($passphrase),
                $timestamp,
                $version,
                Core::get_user_ip()
            );
        } catch (HandshakeException $e) {
            throw new Exception\HandshakeFailedException($e->getMessage());
        }

        // Create the session
        $data             = [];
        $data['username'] = $user->username;
        $data['type']     = 'api';
        $data['apikey']   = $user->apikey;
        $data['value']    = $timestamp;
        if (isset($input['client'])) {
            $data['agent'] = $input['client'];
        }
        if (isset($input['geo_latitude'])) {
            $data['geo_latitude'] = $input['geo_latitude'];
        }
        if (isset($input['geo_longitude'])) {
            $data['geo_longitude'] = $input['geo_longitude'];
        }
        if (isset($input['geo_name'])) {
            $data['geo_name'] = $input['geo_name'];
        }
        //Session might not exist or has expired
        if (!Session::read($data['apikey'])) {
            Session::destroy($data['apikey']);
            $token = Session::create($data);
        } else {
            Session::extend($data['apikey']);
            $token = $data['apikey'];
        }

        $outarray = Api::server_details($token);

        switch ($input['api_format']) {
            case 'json':
                $result = json_encode($outarray, JSON_PRETTY_PRINT);
                break;
            default:
                $result = Xml_Data::keyed_array($outarray);
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
