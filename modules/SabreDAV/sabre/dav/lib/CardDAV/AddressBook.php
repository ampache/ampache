<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAVACL;

/**
 * The AddressBook class represents a CardDAV addressbook, owned by a specific user
 *
 * The AddressBook can contain multiple vcards
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class AddressBook extends DAV\Collection implements IAddressBook, DAV\IProperties, DAVACL\IACL, DAV\Sync\ISyncCollection, DAV\IMultiGet {

    /**
     * This is an array with addressbook information
     *
     * @var array
     */
    protected $addressBookInfo;

    /**
     * CardDAV backend
     *
     * @var Backend\BackendInterface
     */
    protected $carddavBackend;

    /**
     * Constructor
     *
     * @param Backend\BackendInterface $carddavBackend
     * @param array $addressBookInfo
     */
    function __construct(Backend\BackendInterface $carddavBackend, array $addressBookInfo) {

        $this->carddavBackend = $carddavBackend;
        $this->addressBookInfo = $addressBookInfo;

    }

    /**
     * Returns the name of the addressbook
     *
     * @return string
     */
    function getName() {

        return $this->addressBookInfo['uri'];

    }

    /**
     * Returns a card
     *
     * @param string $name
     * @return \ICard
     */
    function getChild($name) {

        $obj = $this->carddavBackend->getCard($this->addressBookInfo['id'],$name);
        if (!$obj) throw new DAV\Exception\NotFound('Card not found');
        return new Card($this->carddavBackend,$this->addressBookInfo,$obj);

    }

    /**
     * Returns the full list of cards
     *
     * @return array
     */
    function getChildren() {

        $objs = $this->carddavBackend->getCards($this->addressBookInfo['id']);
        $children = [];
        foreach($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new Card($this->carddavBackend,$this->addressBookInfo,$obj);
        }
        return $children;

    }

    /**
     * This method receives a list of paths in it's first argument.
     * It must return an array with Node objects.
     *
     * If any children are not found, you do not have to return them.
     *
     * @return array
     */
    function getMultipleChildren(array $paths) {

        $objs = $this->carddavBackend->getMultipleCards($this->addressBookInfo['id'], $paths);
        $children = [];
        foreach($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new Card($this->carddavBackend,$this->addressBookInfo,$obj);
        }
        return $children;

    }

    /**
     * Creates a new directory
     *
     * We actually block this, as subdirectories are not allowed in addressbooks.
     *
     * @param string $name
     * @return void
     */
    function createDirectory($name) {

        throw new DAV\Exception\MethodNotAllowed('Creating collections in addressbooks is not allowed');

    }

    /**
     * Creates a new file
     *
     * The contents of the new file must be a valid VCARD.
     *
     * This method may return an ETag.
     *
     * @param string $name
     * @param resource $vcardData
     * @return string|null
     */
    function createFile($name,$vcardData = null) {

        if (is_resource($vcardData)) {
            $vcardData = stream_get_contents($vcardData);
        }
        // Converting to UTF-8, if needed
        $vcardData = DAV\StringUtil::ensureUTF8($vcardData);

        return $this->carddavBackend->createCard($this->addressBookInfo['id'],$name,$vcardData);

    }

    /**
     * Deletes the entire addressbook.
     *
     * @return void
     */
    function delete() {

        $this->carddavBackend->deleteAddressBook($this->addressBookInfo['id']);

    }

    /**
     * Renames the addressbook
     *
     * @param string $newName
     * @return void
     */
    function setName($newName) {

        throw new DAV\Exception\MethodNotAllowed('Renaming addressbooks is not yet supported');

    }

    /**
     * Returns the last modification date as a unix timestamp.
     *
     * @return void
     */
    function getLastModified() {

        return null;

    }

    /**
     * Updates properties on this node.
     *
     * This method received a PropPatch object, which contains all the
     * information about the update.
     *
     * To update specific properties, call the 'handle' method on this object.
     * Read the PropPatch documentation for more information.
     *
     * @param DAV\PropPatch $propPatch
     * @return void
     */
    function propPatch(DAV\PropPatch $propPatch) {

        return $this->carddavBackend->updateAddressBook($this->addressBookInfo['id'], $propPatch);

    }

    /**
     * Returns a list of properties for this nodes.
     *
     * The properties list is a list of propertynames the client requested,
     * encoded in clark-notation {xmlnamespace}tagname
     *
     * If the array is empty, it means 'all properties' were requested.
     *
     * @param array $properties
     * @return array
     */
    function getProperties($properties) {

        $response = [];
        foreach($properties as $propertyName) {

            if (isset($this->addressBookInfo[$propertyName])) {

                $response[$propertyName] = $this->addressBookInfo[$propertyName];

            }

        }

        return $response;

    }

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    function getOwner() {

        return $this->addressBookInfo['principaluri'];

    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    function getGroup() {

        return null;

    }

    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    function getACL() {

        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],

        ];

    }

    /**
     * This method returns the ACL's for card nodes in this address book.
     * The result of this method automatically gets passed to the
     * card nodes in this address book.
     *
     * @return array
     */
    function getChildACL() {

        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
        ];

    }

    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    function setACL(array $acl) {

        throw new DAV\Exception\MethodNotAllowed('Changing ACL is not yet supported');

    }

    /**
     * Returns the list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See Sabre\DAVACL\Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
    function getSupportedPrivilegeSet() {

        return null;

    }

    /**
     * This method returns the current sync-token for this collection.
     * This can be any string.
     *
     * If null is returned from this function, the plugin assumes there's no
     * sync information available.
     *
     * @return string|null
     */
    function getSyncToken() {

        if (
            $this->carddavBackend instanceof Backend\SyncSupport &&
            isset($this->addressBookInfo['{DAV:}sync-token'])
        ) {
            return $this->addressBookInfo['{DAV:}sync-token'];
        }
        if (
            $this->carddavBackend instanceof Backend\SyncSupport &&
            isset($this->addressBookInfo['{http://sabredav.org/ns}sync-token'])
        ) {
            return $this->addressBookInfo['{http://sabredav.org/ns}sync-token'];
        }

    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken and the current collection.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * ];
     *
     * The syncToken property should reflect the *current* syncToken of the
     * collection, as reported getSyncToken(). This is needed here too, to
     * ensure the operation is atomic.
     *
     * If the syncToken is specified as null, this is an initial sync, and all
     * members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The second argument is basically the 'depth' of the report. If it's 1,
     * you only have to report changes that happened only directly in immediate
     * descendants. If it's 2, it should also include changes from the nodes
     * below the child collections. (grandchildren)
     *
     * The third (optional) argument allows a client to specify how many
     * results should be returned at most. If the limit is not specified, it
     * should be treated as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param string $syncToken
     * @param int $syncLevel
     * @param int $limit
     * @return array
     */
    function getChanges($syncToken, $syncLevel, $limit = null) {

        if (!$this->carddavBackend instanceof Backend\SyncSupport) {
            return null;
        }

        return $this->carddavBackend->getChangesForAddressBook(
            $this->addressBookInfo['id'],
            $syncToken,
            $syncLevel,
            $limit
        );

    }
}
