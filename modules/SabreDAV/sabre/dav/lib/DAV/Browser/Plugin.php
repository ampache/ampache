<?php

namespace Sabre\DAV\Browser;

use
    Sabre\DAV,
    Sabre\HTTP\URLUtil,
    Sabre\HTTP\RequestInterface,
    Sabre\HTTP\ResponseInterface;

/**
 * Browser Plugin
 *
 * This plugin provides a html representation, so that a WebDAV server may be accessed
 * using a browser.
 *
 * The class intercepts GET requests to collection resources and generates a simple
 * html index.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin {

    /**
     * reference to server class
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     * enablePost turns on the 'actions' panel, which allows people to create
     * folders and upload files straight from a browser.
     *
     * @var bool
     */
    protected $enablePost = true;

    /**
     * Creates the object.
     *
     * By default it will allow file creation and uploads.
     * Specify the first argument as false to disable this
     *
     * @param bool $enablePost
     */
    function __construct($enablePost=true) {

        $this->enablePost = $enablePost;

    }

    /**
     * Initializes the plugin and subscribes to events
     *
     * @param DAV\Server $server
     * @return void
     */
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $this->server->on('method:GET', [$this,'httpGet'], 200);
        $this->server->on('onHTMLActionsPanel', [$this, 'htmlActionsPanel'],200);
        if ($this->enablePost) $this->server->on('method:POST', [$this,'httpPOST']);
    }

    /**
     * This method intercepts GET requests to collections and returns the html
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    function httpGet(RequestInterface $request, ResponseInterface $response) {

        // We're not using straight-up $_GET, because we want everything to be
        // unit testable.
        $getVars = $request->getQueryParameters();

        $sabreAction = isset($getVars['sabreAction'])?$getVars['sabreAction']:null;

        // Asset handling, such as images
        if ($sabreAction === 'asset' && isset($getVars['assetName'])) {
            $this->serveAsset($getVars['assetName']);
            return false;
        }

        try {
            $this->server->tree->getNodeForPath($request->getPath());
        } catch (DAV\Exception\NotFound $e) {
            // We're simply stopping when the file isn't found to not interfere
            // with other plugins.
            return;
        }

        $response->setStatus(200);
        $response->setHeader('Content-Type','text/html; charset=utf-8');

        $response->setBody(
            $this->generateDirectoryIndex($request->getPath())
        );

        return false;

    }

    /**
     * Handles POST requests for tree operations.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    function httpPOST(RequestInterface $request, ResponseInterface $response) {

        $contentType = $request->getHeader('Content-Type');
        list($contentType) = explode(';', $contentType);
        if ($contentType !== 'application/x-www-form-urlencoded' &&
            $contentType !== 'multipart/form-data') {
                return;
        }
        $postVars = $request->getPostData();

        if (!isset($postVars['sabreAction']))
            return;

        $uri = $request->getPath();

        if ($this->server->emit('onBrowserPostAction', [$uri, $postVars['sabreAction'], $postVars])) {

            switch($postVars['sabreAction']) {

                case 'mkcol' :
                    if (isset($postVars['name']) && trim($postVars['name'])) {
                        // Using basename() because we won't allow slashes
                        list(, $folderName) = URLUtil::splitPath(trim($postVars['name']));
                        $this->server->createDirectory($uri . '/' . $folderName);
                    }
                    break;

                // @codeCoverageIgnoreStart
                case 'put' :

                    if ($_FILES) $file = current($_FILES);
                    else break;

                    list(, $newName) = URLUtil::splitPath(trim($file['name']));
                    if (isset($postVars['name']) && trim($postVars['name']))
                        $newName = trim($postVars['name']);

                    // Making sure we only have a 'basename' component
                    list(, $newName) = URLUtil::splitPath($newName);

                    if (is_uploaded_file($file['tmp_name'])) {
                        $this->server->createFile($uri . '/' . $newName, fopen($file['tmp_name'],'r'));
                    }
                    break;
                // @codeCoverageIgnoreEnd

            }

        }
        $response->setHeader('Location', $request->getUrl());
        $response->setStatus(302);
        return false;

    }

    /**
     * Escapes a string for html.
     *
     * @param string $value
     * @return string
     */
    function escapeHTML($value) {

        return htmlspecialchars($value,ENT_QUOTES,'UTF-8');

    }

    /**
     * Generates the html directory index for a given url
     *
     * @param string $path
     * @return string
     */
    function generateDirectoryIndex($path) {

        $version = '';
        if (DAV\Server::$exposeVersion) {
            $version = DAV\Version::VERSION;
        }

        $vars = [
            'path'      => $this->escapeHTML($path),
            'favicon'   => $this->escapeHTML($this->getAssetUrl('favicon.ico')),
            'style'     => $this->escapeHTML($this->getAssetUrl('sabredav.css')),
            'iconstyle' => $this->escapeHTML($this->getAssetUrl('openiconic/open-iconic.css')),
            'logo'      => $this->escapeHTML($this->getAssetUrl('sabredav.png')),
            'baseUrl'   => $this->server->getBaseUri(),
        ];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>$vars[path]/ - sabre/dav $version</title>
    <link rel="shortcut icon" href="$vars[favicon]"   type="image/vnd.microsoft.icon" />
    <link rel="stylesheet"    href="$vars[style]"     type="text/css" />
    <link rel="stylesheet"    href="$vars[iconstyle]" type="text/css" />

</head>
<body>
    <header>
        <div class="logo">
            <a href="$vars[baseUrl]"><img src="$vars[logo]" alt="sabre/dav" /> $vars[path]/</a>
        </div>
    </header>

    <nav>
HTML;

        // If the path is empty, there's no parent.
        if ($path)  {
            list($parentUri) = URLUtil::splitPath($path);
            $fullPath = URLUtil::encodePath($this->server->getBaseUri() . $parentUri);
            $html.='<a href="' . $fullPath . '" class="btn">⇤ Go to parent</a>';
        } else {
            $html.='<span class="btn disabled">⇤ Go to parent</span>';
        }

        $html.="</nav>";

        $node = $this->server->tree->getNodeForPath($path);
        if ($node instanceof DAV\ICollection) {

            $html.="<section><h1>Nodes</h1>\n";
            $html.="<table class=\"nodeTable\">";

            $subNodes = $this->server->getPropertiesForChildren($path, [
                '{DAV:}displayname',
                '{DAV:}resourcetype',
                '{DAV:}getcontenttype',
                '{DAV:}getcontentlength',
                '{DAV:}getlastmodified',
            ]);

            foreach($subNodes as $subPath=>$subProps) {

                $subNode = $this->server->tree->getNodeForPath($subPath);
                $fullPath = URLUtil::encodePath($this->server->getBaseUri() . $subPath);
                list(, $displayPath) = URLUtil::splitPath($subPath);

                $subNodes[$subPath]['subNode'] = $subNode;
                $subNodes[$subPath]['fullPath'] = $fullPath;
                $subNodes[$subPath]['displayPath'] = $displayPath;
            }
            uasort($subNodes, [$this, 'compareNodes']);

            foreach($subNodes as $subProps) {
                $type = [
                    'string' => 'Unknown',
                    'icon'   => 'cog',
                ];
                if (isset($subProps['{DAV:}resourcetype'])) {
                    $type = $this->mapResourceType($subProps['{DAV:}resourcetype']->getValue(), $subProps['subNode']);
                }

                $html.= '<tr>';
                $html.= '<td class="nameColumn"><a href="' . $this->escapeHTML($subProps['fullPath']) . '"><span class="oi" data-glyph="'.$this->escapeHTML($type['icon']).'"></span> ' . $this->escapeHTML($subProps['displayPath']) . '</a></td>';
                $html.= '<td class="typeColumn">' . $this->escapeHTML($type['string']) . '</td>';
                $html.= '<td>';
                if (isset($subProps['{DAV:}getcontentlength'])) {
                    $html.=$this->escapeHTML($subProps['{DAV:}getcontentlength'] . ' bytes');
                }
                $html.= '</td><td>';
                if (isset($subProps['{DAV:}getlastmodified'])) {
                    $lastMod = $subProps['{DAV:}getlastmodified']->getTime();
                    $html.=$this->escapeHTML($lastMod->format('F j, Y, g:i a'));
                }
                $html.= '</td></tr>';
            }

            $html.= '</table>';

        }

        $html.="</section>";
        $html.="<section><h1>Properties</h1>";
        $html.="<table class=\"propTable\">";

        // Allprops request
        $propFind = new PropFindAll($path);
        $properties = $this->server->getPropertiesByNode($propFind, $node);

        $properties = $propFind->getResultForMultiStatus()[200];

        foreach($properties as $propName => $propValue) {
            $html.=$this->drawPropertyRow($propName, $propValue);

        }


        $html.="</table>";
        $html.="</section>";

        /* Start of generating actions */

        $output = '';
        if ($this->enablePost) {
            $this->server->emit('onHTMLActionsPanel', [$node, &$output]);
        }

        if ($output) {

            $html.="<section><h1>Actions</h1>";
            $html.="<div class=\"actions\">\n";
            $html.=$output;
            $html.="</div>\n";
            $html.="</section>\n";
        }

        $html.= "
        <footer>Generated by SabreDAV " . $version . " (c)2007-2014 <a href=\"http://sabre.io/\">http://sabre.io/</a></footer>
        </body>
        </html>";

        $this->server->httpResponse->setHeader('Content-Security-Policy', "img-src 'self'; style-src 'self';");

        return $html;

    }

    /**
     * This method is used to generate the 'actions panel' output for
     * collections.
     *
     * This specifically generates the interfaces for creating new files, and
     * creating new directories.
     *
     * @param DAV\INode $node
     * @param mixed $output
     * @return void
     */
    function htmlActionsPanel(DAV\INode $node, &$output) {

        if (!$node instanceof DAV\ICollection)
            return;

        // We also know fairly certain that if an object is a non-extended
        // SimpleCollection, we won't need to show the panel either.
        if (get_class($node)==='Sabre\\DAV\\SimpleCollection')
            return;

        ob_start();
        echo '<form method="post" action="">
        <h3>Create new folder</h3>
        <input type="hidden" name="sabreAction" value="mkcol" />
        <label>Name:</label> <input type="text" name="name" /><br />
        <input type="submit" value="create" />
        </form>
        <form method="post" action="" enctype="multipart/form-data">
        <h3>Upload file</h3>
        <input type="hidden" name="sabreAction" value="put" />
        <label>Name (optional):</label> <input type="text" name="name" /><br />
        <label>File:</label> <input type="file" name="file" /><br />
        <input type="submit" value="upload" />
        </form>
        ';

        $output.=ob_get_clean();

    }

    /**
     * This method takes a path/name of an asset and turns it into url
     * suiteable for http access.
     *
     * @param string $assetName
     * @return string
     */
    protected function getAssetUrl($assetName) {

        return $this->server->getBaseUri() . '?sabreAction=asset&assetName=' . urlencode($assetName);

    }

    /**
     * This method returns a local pathname to an asset.
     *
     * @param string $assetName
     * @return string
     * @throws DAV\Exception\NotFound
     */
    protected function getLocalAssetPath($assetName) {

        $assetDir = __DIR__ . '/assets/';
        $path = $assetDir . $assetName;

        // Making sure people aren't trying to escape from the base path.
        $path = str_replace('\\', '/', $path);
        if (strpos($path, '/../') !== false || strrchr($path, '/') === '/..') {
            throw new DAV\Exception\NotFound('Path does not exist, or escaping from the base path was detected');
        }
        if (strpos(realpath($path), realpath($assetDir)) === 0 && file_exists($path)) {
            return $path;
        }
        throw new DAV\Exception\NotFound('Path does not exist, or escaping from the base path was detected');
    }

    /**
     * This method reads an asset from disk and generates a full http response.
     *
     * @param string $assetName
     * @return void
     */
    protected function serveAsset($assetName) {

        $assetPath = $this->getLocalAssetPath($assetName);

        // Rudimentary mime type detection
        $mime = 'application/octet-stream';
        $map = [
            'ico'  => 'image/vnd.microsoft.icon',
            'png'  => 'image/png',
            'css'  =>  'text/css',
        ];

        $ext = substr($assetName, strrpos($assetName, '.')+1);
        if (isset($map[$ext])) {
            $mime = $map[$ext];
        }

        $this->server->httpResponse->setHeader('Content-Type', $mime);
        $this->server->httpResponse->setHeader('Content-Length', filesize($assetPath));
        $this->server->httpResponse->setHeader('Cache-Control', 'public, max-age=1209600');
        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setBody(fopen($assetPath,'r'));

    }

    /**
     * Sort helper function: compares two directory entries based on type and
     * display name. Collections sort above other types.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function compareNodes($a, $b) {

        $typeA = (isset($a['{DAV:}resourcetype']))
            ? (in_array('{DAV:}collection', $a['{DAV:}resourcetype']->getValue()))
            : false;

        $typeB = (isset($b['{DAV:}resourcetype']))
            ? (in_array('{DAV:}collection', $b['{DAV:}resourcetype']->getValue()))
            : false;

        // If same type, sort alphabetically by filename:
        if ($typeA === $typeB) {
            return strnatcasecmp($a['displayPath'], $b['displayPath']);
        }
        return (($typeA < $typeB) ? 1 : -1);

    }

    /**
     * Maps a resource type to a human-readable string and icon.
     *
     * @param array $resourceTypes
     * @param INode $node
     * @return array
     */
    private function mapResourceType(array $resourceTypes, $node) {

        if (!$resourceTypes) {
            if ($node instanceof DAV\IFile) {
                return [
                    'string' => 'File',
                    'icon'   => 'file',
                ];
            } else {
                return [
                    'string' => 'Unknown',
                    'icon'   => 'cog',
                ];
            }
        }

        $types = [
            '{http://calendarserver.org/ns/}calendar-proxy-write' => [
                'string' => 'Proxy-Write',
                'icon'   => 'people',
            ],
            '{http://calendarserver.org/ns/}calendar-proxy-read' => [
                'string' => 'Proxy-Read',
                'icon'   => 'people',
            ],
            '{urn:ietf:params:xml:ns:caldav}schedule-outbox' => [
                'string' => 'Outbox',
                'icon'   => 'inbox',
            ],
            '{urn:ietf:params:xml:ns:caldav}schedule-inbox' => [
                'string' => 'Inbox',
                'icon'   => 'inbox',
            ],
            '{urn:ietf:params:xml:ns:caldav}calendar' => [
                'string' => 'Calendar',
                'icon'   => 'calendar',
            ],
            '{http://calendarserver.org/ns/}shared-owner' => [
                'string' => 'Shared',
                'icon'   => 'calendar',
            ],
            '{http://calendarserver.org/ns/}subscribed' => [
                'string' => 'Subscription',
                'icon'   => 'calendar',
            ],
            '{urn:ietf:params:xml:ns:carddav}directory' => [
                'string' => 'Directory',
                'icon'   => 'globe',
            ],
            '{urn:ietf:params:xml:ns:carddav}addressbook' => [
                'string' => 'Address book',
                'icon'   => 'book',
            ],
            '{DAV:}principal' => [
                'string' => 'Principal',
                'icon'   => 'person',
            ],
            '{DAV:}collection' => [
                'string' => 'Collection',
                'icon'   => 'folder',
            ],
        ];

        $info = [
            'string' => [],
            'icon' => 'cog',
        ];
        foreach($resourceTypes as $k=> $resourceType) {
            if (isset($types[$resourceType])) {
                $info['string'][] = $types[$resourceType]['string'];
            } else {
                $info['string'][] = $resourceType;
            }
        }
        foreach($types as $key=>$resourceInfo) {
            if (in_array($key, $resourceTypes)) {
                $info['icon'] = $resourceInfo['icon'];
                break;
            }
        }
        $info['string'] = implode(', ', $info['string']);

        return $info;

    }

    /**
     * Draws a table row for a property
     *
     * @param string $name
     * @param mixed $value
     * @return string
     */
    private function drawPropertyRow($name, $value) {

        $view = 'unknown';
        if (is_scalar($value)) {
            $view = 'string';
        } elseif($value instanceof DAV\Property) {

            $mapping = [
                'Sabre\\DAV\\Property\\IHref' => 'href',
                'Sabre\\DAV\\Property\\HrefList' => 'hreflist',
                'Sabre\\DAV\\Property\\SupportedMethodSet' => 'valuelist',
                'Sabre\\DAV\\Property\\ResourceType' => 'xmlvaluelist',
                'Sabre\\DAV\\Property\\SupportedReportSet' => 'xmlvaluelist',
                'Sabre\\DAVACL\\Property\\CurrentUserPrivilegeSet' => 'xmlvaluelist',
                'Sabre\\DAVACL\\Property\\SupportedPrivilegeSet' => 'supported-privilege-set',
            ];

            $view = 'complex';
            foreach($mapping as $class=>$val) {
                if ($value instanceof $class) {
                    $view = $val;
                    break;
                }
            }
        }

        list($ns, $localName) = DAV\XMLUtil::parseClarkNotation($name);

        $realName = $name;
        if (isset($this->server->xmlNamespaces[$ns])) {
            $name = $this->server->xmlNamespaces[$ns] . ':' . $localName;
        }

        ob_start();

        $xmlValueDisplay = function($propName) {
            $realPropName = $propName;
            list($ns, $localName) = DAV\XMLUtil::parseClarkNotation($propName);
            if (isset($this->server->xmlNamespaces[$ns])) {
                $propName = $this->server->xmlNamespaces[$ns] . ':' . $localName;
            }
            return "<span title=\"" . $this->escapeHTML($realPropName) . "\">" . $this->escapeHTML($propName) . "</span>";
        };

        echo "<tr><th><span title=\"", $this->escapeHTML($realName), "\">", $this->escapeHTML($name), "</span></th><td>";

        switch($view) {

            case 'href' :
                echo "<a href=\"" . $this->server->getBaseUri() . $value->getHref() . '">' . $this->server->getBaseUri() . $value->getHref() . '</a>';
                break;
            case 'hreflist' :
                echo implode('<br />', array_map(function($href) {
                    if (stripos($href,'mailto:')===0 || stripos($href,'/')===0 || stripos($href,'http:')===0 || stripos($href,'https:') === 0) {
                        return "<a href=\"" . $this->escapeHTML($href) . '">' . $this->escapeHTML($href) . '</a>';
                    } else {
                        return "<a href=\"" . $this->escapeHTML($this->server->getBaseUri() . $href) . '">' . $this->escapeHTML($this->server->getBaseUri() . $href) . '</a>';
                    }
                }, $value->getHrefs()));
                break;
            case 'xmlvaluelist' :
                echo implode(', ', array_map($xmlValueDisplay, $value->getValue()));
                break;
            case 'valuelist' :
                echo $this->escapeHTML(implode(', ', $value->getValue()));
                break;
            case 'supported-privilege-set' :
                $traverse = function($priv) use (&$traverse, $xmlValueDisplay) {
                    echo "<li>";
                    echo $xmlValueDisplay($priv['privilege']);
                    if (isset($priv['abstract']) && $priv['abstract']) {
                        echo " <i>(abstract)</i>";
                    }
                    if (isset($priv['description'])) {
                        echo " " . $this->escapeHTML($priv['description']);
                    }
                    if (isset($priv['aggregates'])) {
                        echo "\n<ul>\n";
                        foreach($priv['aggregates'] as $subPriv) {
                            $traverse($subPriv);
                        }
                        echo "</ul>";
                    }
                    echo "</li>\n";
                };
                echo "<ul class=\"tree\">";
                $traverse($value->getValue(), '');
                echo "</ul>\n";
                break;
            case 'string' :
                echo $this->escapeHTML($value);
                break;
            case 'complex' :
                echo '<em title="' . $this->escapeHTML(get_class($value)) . '">complex</em>';
                break;
            default :
                echo '<em>unknown</em>';
                break;

        }

        return ob_get_clean();

    }

}
