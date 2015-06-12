<?php

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;
use Sabre\HTTP;


abstract class AbstractPDOTest extends \PHPUnit_Framework_TestCase {

    abstract function getPDO();

    function testConstruct() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertTrue($backend instanceof PDO);

    }

    /**
     * @depends testConstruct
     */
    function testGetPrincipalsByPrefix() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $expected = array(
            array(
                'uri' => 'principals/user',
                '{http://sabredav.org/ns}email-address' => 'user@example.org',
                '{DAV:}displayname' => 'User',
            ),
            array(
                'uri' => 'principals/group',
                '{http://sabredav.org/ns}email-address' => 'group@example.org',
                '{DAV:}displayname' => 'Group',
            ),
        );

        $this->assertEquals($expected, $backend->getPrincipalsByPrefix('principals'));
        $this->assertEquals(array(), $backend->getPrincipalsByPrefix('foo'));

    }

    /**
     * @depends testConstruct
     */
    function testGetPrincipalByPath() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $expected = array(
            'id' => 1,
            'uri' => 'principals/user',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
            '{DAV:}displayname' => 'User',
        );

        $this->assertEquals($expected, $backend->getPrincipalByPath('principals/user'));
        $this->assertEquals(null, $backend->getPrincipalByPath('foo'));

    }

    function testGetGroupMemberSet() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $expected = array('principals/user');

        $this->assertEquals($expected,$backend->getGroupMemberSet('principals/group'));

    }

    function testGetGroupMembership() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $expected = array('principals/group');

        $this->assertEquals($expected,$backend->getGroupMembership('principals/user'));

    }

    function testSetGroupMemberSet() {

        $pdo = $this->getPDO();

        // Start situation
        $backend = new PDO($pdo);
        $this->assertEquals(array('principals/user'), $backend->getGroupMemberSet('principals/group'));

        // Removing all principals
        $backend->setGroupMemberSet('principals/group', array());
        $this->assertEquals(array(), $backend->getGroupMemberSet('principals/group'));

        // Adding principals again
        $backend->setGroupMemberSet('principals/group', array('principals/user'));
        $this->assertEquals(array('principals/user'), $backend->getGroupMemberSet('principals/group'));


    }

    function testSearchPrincipals() {

        $pdo = $this->getPDO();

        $backend = new PDO($pdo);

        $result = $backend->searchPrincipals('principals', array('{DAV:}blabla' => 'foo'));
        $this->assertEquals(array(), $result);

        $result = $backend->searchPrincipals('principals', array('{DAV:}displayname' => 'ou'));
        $this->assertEquals(array('principals/group'), $result);

        $result = $backend->searchPrincipals('principals', array('{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE'));
        $this->assertEquals(array('principals/user'), $result);

        $result = $backend->searchPrincipals('mom', array('{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE'));
        $this->assertEquals(array(), $result);

    }

    function testUpdatePrincipal() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
            '{http://sabredav.org/ns}vcard-url' => 'blabla',
        ]);

        $backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        $this->assertTrue($result);

        $this->assertEquals(array(
            'id' => 1,
            'uri' => 'principals/user',
            '{DAV:}displayname' => 'pietje',
            '{http://sabredav.org/ns}vcard-url' => 'blabla',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ), $backend->getPrincipalByPath('principals/user'));

    }

    function testUpdatePrincipalUnknownField() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
            '{http://sabredav.org/ns}vcard-url' => 'blabla',
            '{DAV:}unknown' => 'foo',
        ]);

        $backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        $this->assertFalse($result);

        $this->assertEquals(array(
            '{DAV:}displayname' => 424,
            '{http://sabredav.org/ns}vcard-url' => 424,
            '{DAV:}unknown' => 403
        ), $propPatch->getResult());

        $this->assertEquals(array(
            'id' => '1',
            'uri' => 'principals/user',
            '{DAV:}displayname' => 'User',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ), $backend->getPrincipalByPath('principals/user'));

    }

}
