<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;


require_once 'Sabre/DAVACL/MockPrincipal.php';
require_once 'Sabre/DAVACL/MockACLNode.php';

class SimplePluginTest extends \PHPUnit_Framework_TestCase {

    function testValues() {

        $aclPlugin = new Plugin();
        $this->assertEquals('acl',$aclPlugin->getPluginName());
        $this->assertEquals(
            array('access-control', 'calendarserver-principal-property-search'),
            $aclPlugin->getFeatures()
        );

        $this->assertEquals(
            array(
                '{DAV:}expand-property',
                '{DAV:}principal-property-search',
                '{DAV:}principal-search-property-set'
            ),
            $aclPlugin->getSupportedReportSet(''));

        $this->assertEquals(array('ACL'), $aclPlugin->getMethods(''));

    }

    function testGetFlatPrivilegeSet() {

        $expected = array(
            '{DAV:}all' => array(
                'privilege' => '{DAV:}all',
                'abstract' => true,
                'aggregates' => array(
                    '{DAV:}read',
                    '{DAV:}write',
                ),
                'concrete' => null,
            ),
            '{DAV:}read' => array(
                'privilege' => '{DAV:}read',
                'abstract' => false,
                'aggregates' => array(
                    '{DAV:}read-acl',
                    '{DAV:}read-current-user-privilege-set',
                ),
                'concrete' => '{DAV:}read',
            ),
            '{DAV:}read-acl' => array(
                'privilege' => '{DAV:}read-acl',
                'abstract' => false,
                'aggregates' => array(),
                'concrete' => '{DAV:}read-acl',
            ),
            '{DAV:}read-current-user-privilege-set' => array(
                'privilege' => '{DAV:}read-current-user-privilege-set',
                'abstract' => false,
                'aggregates' => array(),
                'concrete' => '{DAV:}read-current-user-privilege-set',
            ),
            '{DAV:}write' => array(
                'privilege' => '{DAV:}write',
                'abstract' => false,
                'aggregates' => array(
                    '{DAV:}write-acl',
                    '{DAV:}write-properties',
                    '{DAV:}write-content',
                    '{DAV:}bind',
                    '{DAV:}unbind',
                    '{DAV:}unlock',
                ),
                'concrete' => '{DAV:}write',
            ),
            '{DAV:}write-acl' => array(
                'privilege' => '{DAV:}write-acl',
                'abstract' => false,
                'aggregates' => array(),
                'concrete' => '{DAV:}write-acl',
            ),
            '{DAV:}write-properties' => array(
                'privilege' => '{DAV:}write-properties',
                'abstract' => false,
                'aggregates' => array(),
                'concrete' => '{DAV:}write-properties',
            ),
            '{DAV:}write-content' => array(
                'privilege' => '{DAV:}write-content',
                'abstract' => false,
                'aggregates' => array(),
                'concrete' => '{DAV:}write-content',
            ),
            '{DAV:}unlock' => array(
                'privilege' => '{DAV:}unlock',
                'abstract' => false,
                'aggregates' => array(),
                'concrete' => '{DAV:}unlock',
            ),
            '{DAV:}bind' => array(
                'privilege' => '{DAV:}bind',
                'abstract' => false,
                'aggregates' => array(),
                'concrete' => '{DAV:}bind',
            ),
            '{DAV:}unbind' => array(
                'privilege' => '{DAV:}unbind',
                'abstract' => false,
                'aggregates' => array(),
                'concrete' => '{DAV:}unbind',
            ),

        );

        $plugin = new Plugin();
        $server = new DAV\Server();
        $server->addPlugin($plugin);
        $this->assertEquals($expected, $plugin->getFlatPrivilegeSet(''));

    }

    function testCurrentUserPrincipalsNotLoggedIn() {

        $acl = new Plugin();
        $server = new DAV\Server();
        $server->addPlugin($acl);

        $this->assertEquals(array(),$acl->getCurrentUserPrincipals());

    }

    function testCurrentUserPrincipalsSimple() {

        $tree = array(

            new DAV\SimpleCollection('principals', array(
                new MockPrincipal('admin','principals/admin'),
            ))

        );

        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->addPlugin($acl);

        $auth = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock(),'SabreDAV');
        $server->addPlugin($auth);

        //forcing login
        $auth->beforeMethod(new HTTP\Request(), new HTTP\Response());

        $this->assertEquals(array('principals/admin'),$acl->getCurrentUserPrincipals());

    }

    function testCurrentUserPrincipalsGroups() {

        $tree = array(

            new DAV\SimpleCollection('principals', array(
                new MockPrincipal('admin','principals/admin',array('principals/administrators', 'principals/everyone')),
                new MockPrincipal('administrators','principals/administrators',array('principals/groups'), array('principals/admin')),
                new MockPrincipal('everyone','principals/everyone',array(), array('principals/admin')),
                new MockPrincipal('groups','principals/groups',array(), array('principals/administrators')),
            ))

        );

        $acl = new Plugin();
        $server = new DAV\Server($tree);
        $server->addPlugin($acl);

        $auth = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock(),'SabreDAV');
        $server->addPlugin($auth);

        //forcing login
        $auth->beforeMethod(new HTTP\Request(), new HTTP\Response());

        $expected = array(
            'principals/admin',
            'principals/administrators',
            'principals/everyone',
            'principals/groups',
        );

        $this->assertEquals($expected,$acl->getCurrentUserPrincipals());

        // The second one should trigger the cache and be identical
        $this->assertEquals($expected,$acl->getCurrentUserPrincipals());

    }

    function testGetACL() {

        $acl = array(
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}read',
            ),
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}write',
            ),
        );


        $tree = array(
            new MockACLNode('foo',$acl),
        );

        $server = new DAV\Server($tree);
        $aclPlugin = new Plugin();
        $server->addPlugin($aclPlugin);

        $this->assertEquals($acl,$aclPlugin->getACL('foo'));

    }

    function testGetCurrentUserPrivilegeSet() {

        $acl = array(
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}read',
            ),
            array(
                'principal' => 'principals/user1',
                'privilege' => '{DAV:}read',
            ),
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}write',
            ),
        );


        $tree = array(
            new MockACLNode('foo',$acl),

            new DAV\SimpleCollection('principals', array(
                new MockPrincipal('admin','principals/admin'),
            )),

        );

        $server = new DAV\Server($tree);
        $aclPlugin = new Plugin();
        $server->addPlugin($aclPlugin);

        $auth = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock(),'SabreDAV');
        $server->addPlugin($auth);

        //forcing login
        $auth->beforeMethod(new HTTP\Request(), new HTTP\Response());

        $expected = array(
            '{DAV:}write',
            '{DAV:}write-acl',
            '{DAV:}write-properties',
            '{DAV:}write-content',
            '{DAV:}bind',
            '{DAV:}unbind',
            '{DAV:}unlock',
            '{DAV:}read',
            '{DAV:}read-acl',
            '{DAV:}read-current-user-privilege-set',
        );

        $this->assertEquals($expected,$aclPlugin->getCurrentUserPrivilegeSet('foo'));

    }

    function testCheckPrivileges() {

        $acl = array(
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}read',
            ),
            array(
                'principal' => 'principals/user1',
                'privilege' => '{DAV:}read',
            ),
            array(
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}write',
            ),
        );


        $tree = array(
            new MockACLNode('foo',$acl),

            new DAV\SimpleCollection('principals', array(
                new MockPrincipal('admin','principals/admin'),
            )),

        );

        $server = new DAV\Server($tree);
        $aclPlugin = new Plugin();
        $server->addPlugin($aclPlugin);

        $auth = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock(),'SabreDAV');
        $server->addPlugin($auth);

        //forcing login
        //$auth->beforeMethod('GET','/');

        $this->assertFalse($aclPlugin->checkPrivileges('foo', array('{DAV:}read'), Plugin::R_PARENT, false));

    }
}




