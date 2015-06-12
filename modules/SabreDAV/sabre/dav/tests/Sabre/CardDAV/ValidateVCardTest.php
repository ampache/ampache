<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\HTTP;
use Sabre\DAVACL;

require_once 'Sabre/HTTP/ResponseMock.php';

class ValidateVCardTest extends \PHPUnit_Framework_TestCase {

    protected $server;
    protected $cardBackend;

    function setUp() {

        $addressbooks = array(
            array(
                'id' => 'addressbook1',
                'principaluri' => 'principals/admin',
                'uri' => 'addressbook1',
            )
        );

        $this->cardBackend = new Backend\Mock($addressbooks,array());
        $principalBackend = new DAVACL\PrincipalBackend\Mock();

        $tree = array(
            new AddressBookRoot($principalBackend, $this->cardBackend),
        );

        $this->server = new DAV\Server($tree);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->debugExceptions = true;

        $plugin = new Plugin();
        $this->server->addPlugin($plugin);

        $response = new HTTP\ResponseMock();
        $this->server->httpResponse = $response;

    }

    function request(HTTP\Request $request) {

        $this->server->httpRequest = $request;
        $this->server->exec();

        return $this->server->httpResponse;

    }

    function testCreateFile() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));

        $response = $this->request($request);

        $this->assertEquals(415, $response->status);

    }

    function testCreateFileValid() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $request->setBody("BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n");

        $response = $this->request($request);

        $this->assertEquals(201, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);
        $expected = array(
            'uri'          => 'blabla.vcf',
            'carddata' => "BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n",
        );

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1','blabla.vcf'));

    }

    function testCreateFileNoUID() {

        $request = new HTTP\Request(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf'
        );
        $request->setBody("BEGIN:VCARD\r\nEND:VCARD\r\n");

        $response = $this->request($request);

        $this->assertEquals(201, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

        $foo = $this->cardBackend->getCard('addressbook1','blabla.vcf');
        $this->assertTrue(strpos($foo['carddata'],'UID')!==false);
    }

    function testCreateFileJson() {

        $request = new HTTP\Request(
            'PUT',
            '/addressbooks/admin/addressbook1/blabla.vcf'
        );
        $request->setBody('[ "vcard" , [ [ "UID" , {}, "text", "foo" ] ] ]');

        $response = $this->request($request);

        $this->assertEquals(201, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

        $foo = $this->cardBackend->getCard('addressbook1','blabla.vcf');
        $this->assertEquals("BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n", $foo['carddata']);

    }

    function testCreateFileVCalendar() {

        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $request->setBody("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals(415, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testUpdateFile() {

        $this->cardBackend->createCard('addressbook1','blabla.vcf','foo');
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));

        $response = $this->request($request);

        $this->assertEquals(415, $response->status);

    }

    function testUpdateFileParsableBody() {

        $this->cardBackend->createCard('addressbook1','blabla.vcf','foo');
        $request = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/addressbooks/admin/addressbook1/blabla.vcf',
        ));
        $body = "BEGIN:VCARD\r\nUID:foo\r\nEND:VCARD\r\n";
        $request->setBody($body);

        $response = $this->request($request);

        $this->assertEquals(204, $response->status);

        $expected = array(
            'uri'          => 'blabla.vcf',
            'carddata' => $body,
        );

        $this->assertEquals($expected, $this->cardBackend->getCard('addressbook1','blabla.vcf'));

    }
}

?>
