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

namespace Ampache\Module\Api\OpenSubsonic;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use DOMDocument;
use SimpleXMLElement;

final class OpenSubsonicResponseHandler implements OpenSubsonicResponseHandlerInterface
{
    public function __construct(
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
    ) {}

    /**
     * @return array{'subsonic-response': array{'status': string, 'version': string, 'type': string, 'serverVersion': string, 'openSubsonic': bool}}
     */
    public function addJsonResponse(string $function): array
    {
        return $this->openSubsonicJsonData->addResponse($function);
    }

    public function addXmlResponse(string $function): SimpleXMLElement
    {
        return $this->openSubsonicXmlData->addResponse($function);
    }

    public function checkParameter(array $input, string $parameter, string $function): mixed
    {
        if (!array_key_exists($parameter, $input) || $input[$parameter] === '') {
            ob_end_clean();
            $this->errorOutput($input, OpenSubsonic_Api::SSERROR_MISSINGPARAM, $function);

            return false;
        }

        return $input[$parameter];
    }

    public function errorOutput(array $input, int $errorCode, string $function): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        switch ($format) {
            case 'json':
                $this->jsonOutput($this->openSubsonicJsonData->addError($errorCode, $function));
                break;
            case 'jsonp':
                $callback = (string) ($input['callback'] ?? 'jsonp');
                $this->jsonpOutput($this->openSubsonicJsonData->addError($errorCode, $function), $callback);
                break;
            default:
                $this->xmlOutput($this->openSubsonicXmlData->addError($errorCode, $function));
                break;
        }
    }

    /**
     * @param array{'subsonic-response': array<string, mixed>} $json
     */
    public function jsonOutput(array $json): void
    {
        $output = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!$output) {
            $output = json_encode($this->openSubsonicJsonData->addError(OpenSubsonic_Api::SSERROR_GENERIC, 'system'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
        }

        header("Content-type: application/json; charset=" . AmpConfig::get('site_charset', 'UTF-8'));
        header("Access-Control-Allow-Origin: *");
        echo $output;
    }

    public function jsonpOutput(array $json, string $callback): void
    {
        $output = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($output === false) {
            $output = json_encode($this->openSubsonicJsonData->addError(OpenSubsonic_Api::SSERROR_GENERIC, 'system'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
        }

        header("Content-type: text/javascript; charset=" . AmpConfig::get('site_charset', 'UTF-8'));
        header("Access-Control-Allow-Origin: *");
        echo $callback . '(' . $output . ')';
    }

    /**
     * @param array<string, mixed> $input
     * @param array{'subsonic-response': array<string, mixed>}|SimpleXMLElement|null $response
     */
    public function responseOutput(array $input, string $function, array|SimpleXMLElement|null $response = null): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        switch ($format) {
            case 'json':
                $response = (is_array($response))
                    ? $response
                    : $this->addJsonResponse($function);
                $this->jsonOutput($response);
                break;
            case 'jsonp':
                $response = (is_array($response))
                    ? $response
                    : $this->addJsonResponse($function);
                $callback = (string) ($input['callback'] ?? 'jsonp');
                $this->jsonpOutput($response, $callback);
                break;
            default:
                $response = ($response instanceof SimpleXMLElement)
                    ? $response
                    : $this->addXmlResponse($function);
                $this->xmlOutput($response);
                break;
        }
    }

    public function xmlOutput(SimpleXMLElement $xml): void
    {
        $output = false;
        $xmlstr = $xml->asXML();
        if (is_string($xmlstr)) {
            // clean illegal XML characters.
            $clean_xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '_', $xmlstr);
            if (is_string($clean_xml)) {
                $dom = new DOMDocument();
                $dom->loadXML($clean_xml, LIBXML_PARSEHUGE);
                $dom->formatOutput = true;
                $output            = $dom->saveXML();
            }
        }

        // saving xml can fail
        if (!$output) {
            $output = "<subsonic-response status=\"failed\" " . "version=\"1.16.1\" " . "type=\"ampache\" " . "serverVersion=\"" . AmpConfig::get('version') . "\" " . "openSubsonic=\"1\" " . ">"
                . "<error code=\"" . OpenSubsonic_Api::SSERROR_GENERIC . "\" message=\"Error creating response.\" helpUrl=\"https://ampache.org/api/subsonic\"/>"
                . "</subsonic-response>";
        }

        header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset', 'UTF-8'));
        header("Access-Control-Allow-Origin: *");
        echo $output;
    }
}
