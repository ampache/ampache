<?php

namespace Sabre\CardDAV;

use
    Sabre\DAV,
    Sabre\VObject,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface;

/**
 * VCF Exporter
 *
 * This plugin adds the ability to export entire address books as .vcf files.
 * This is useful for clients that don't support CardDAV yet. They often do
 * support vcf files.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @author Thomas Tanghus (http://tanghus.net/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VCFExportPlugin extends DAV\ServerPlugin {

    /**
     * Reference to Server class
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     * Initializes the plugin and registers event handlers
     *
     * @param DAV\Server $server
     * @return void
     */
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $this->server->on('method:GET', [$this,'httpGet'], 90);

    }

    /**
     * Intercepts GET requests on addressbook urls ending with ?export.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    function httpGet(RequestInterface $request, ResponseInterface $response) {

        $queryParams = $request->getQueryParameters();
        if (!array_key_exists('export', $queryParams)) return;

        $path = $request->getPath();

        $node = $this->server->tree->getNodeForPath($path);

        if (!($node instanceof IAddressBook)) return;

        $this->server->transactionType = 'get-addressbook-export';

        // Checking ACL, if available.
        if ($aclPlugin = $this->server->getPlugin('acl')) {
            $aclPlugin->checkPrivileges($path, '{DAV:}read');
        }

        $response->setHeader('Content-Type','text/directory');
        $response->setStatus(200);

        $nodes = $this->server->getPropertiesForPath($path, [
            '{' . Plugin::NS_CARDDAV . '}address-data',
        ],1);

        $response->setBody($this->generateVCF($nodes));

        // Returning false to break the event chain
        return false;

    }

    /**
     * Merges all vcard objects, and builds one big vcf export
     *
     * @param array $nodes
     * @return string
     */
    function generateVCF(array $nodes) {

        $output = "";

        foreach($nodes as $node) {

            if (!isset($node[200]['{' . Plugin::NS_CARDDAV . '}address-data'])) {
                continue;
            }
            $nodeData = $node[200]['{' . Plugin::NS_CARDDAV . '}address-data'];

            // Parsing this node so VObject can clean up the output.
            $output .=
               VObject\Reader::read($nodeData)->serialize();

        }

        return $output;

    }

}
