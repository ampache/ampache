<?php

namespace Sabre\DAV;
use Sabre\HTTP;

class ServerUpdatePropertiesTest extends \PHPUnit_Framework_TestCase {

    function testUpdatePropertiesFail() {

        $tree = array(
            new SimpleCollection('foo'),
        );
        $server = new Server($tree);

        $result = $server->updateProperties('foo', array(
            '{DAV:}foo' => 'bar'
        ));

        $expected = array(
            '{DAV:}foo' => 403,
        );
        $this->assertEquals($expected, $result);

    }

    function testUpdatePropertiesProtected() {

        $tree = array(
            new SimpleCollection('foo'),
        );
        $server = new Server($tree);

        $server->on('propPatch', function($path, PropPatch $propPatch) {
            $propPatch->handleRemaining(function() { return true; });
        });
        $result = $server->updateProperties('foo', array(
            '{DAV:}getetag' => 'bla',
            '{DAV:}foo' => 'bar'
        ));

        $expected = array(
            '{DAV:}getetag' => 403,
            '{DAV:}foo' => 424,
        );
        $this->assertEquals($expected, $result);

    }

    function testUpdatePropertiesEventFail() {

        $tree = array(
            new SimpleCollection('foo'),
        );
        $server = new Server($tree);
        $server->on('propPatch', function($path, PropPatch $propPatch) {
            $propPatch->setResultCode('{DAV:}foo', 404);
            $propPatch->handleRemaining(function() { return true; });
        });

        $result = $server->updateProperties('foo', array(
            '{DAV:}foo' => 'bar',
            '{DAV:}foo2' => 'bla',
        ));

        $expected = array(
            '{DAV:}foo' => 404,
            '{DAV:}foo2' => 424,
        );
        $this->assertEquals($expected, $result);

    }

    function updatePropFail(&$propertyDelta, &$result, $node) {

        $result[404] = array(
            '{DAV:}foo' => null,
        );
        unset($propertyDelta['{DAV:}foo']);
        return false;

    }


    function testUpdatePropertiesEventSuccess() {

        $tree = array(
            new SimpleCollection('foo'),
        );
        $server = new Server($tree);
        $server->on('propPatch', function($path, PropPatch $propPatch) {

            $propPatch->handle(['{DAV:}foo', '{DAV:}foo2'], function() {
                return [
                    '{DAV:}foo' => 200,
                    '{DAV:}foo2' => 201,
                ];
            });

        });

        $result = $server->updateProperties('foo', array(
            '{DAV:}foo' => 'bar',
            '{DAV:}foo2' => 'bla',
        ));

        $expected = array(
            '{DAV:}foo' => 200,
            '{DAV:}foo2' => 201,
        );
        $this->assertEquals($expected, $result);

    }

    function updatePropSuccess(&$propertyDelta, &$result, $node) {

        $result[200] = array(
            '{DAV:}foo' => null,
        );
        $result[201] = array(
            '{DAV:}foo2' => null,
        );
        unset($propertyDelta['{DAV:}foo']);
        unset($propertyDelta['{DAV:}foo2']);
        return;

    }
}
