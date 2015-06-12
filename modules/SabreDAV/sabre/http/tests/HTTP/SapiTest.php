<?php

namespace Sabre\HTTP;

class SapiTest extends \PHPUnit_Framework_TestCase {

    function testConstructFromServerArray() {

        $request = Sapi::createFromServerArray(array(
            'REQUEST_URI'     => '/foo',
            'REQUEST_METHOD'  => 'GET',
            'HTTP_USER_AGENT' => 'Evert',
            'CONTENT_TYPE'    => 'text/xml',
            'CONTENT_LENGTH'  => '400',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
        ));

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'User-Agent' => ['Evert'],
            'Content-Type' => ['text/xml'],
            'Content-Length' => ['400'],
        ), $request->getHeaders());

        $this->assertEquals('1.0', $request->getHttpVersion());

        $this->assertEquals('400', $request->getRawServerValue('CONTENT_LENGTH'));
        $this->assertNull($request->getRawServerValue('FOO'));

    }

    function testConstructPHPAuth() {

        $request = Sapi::createFromServerArray(array(
            'REQUEST_URI'     => '/foo',
            'REQUEST_METHOD'  => 'GET',
            'PHP_AUTH_USER'   => 'user',
            'PHP_AUTH_PW'     => 'pass',
        ));

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'Authorization' => ['Basic ' . base64_encode('user:pass')],
        ), $request->getHeaders());

    }

    function testConstructPHPAuthDigest() {

        $request = Sapi::createFromServerArray(array(
            'REQUEST_URI'     => '/foo',
            'REQUEST_METHOD'  => 'GET',
            'PHP_AUTH_DIGEST' => 'blabla',
        ));

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'Authorization' => ['Digest blabla'],
        ), $request->getHeaders());

    }

    function testConstructRedirectAuth() {

        $request = Sapi::createFromServerArray(array(
            'REQUEST_URI'                 => '/foo',
            'REQUEST_METHOD'              => 'GET',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Basic bla',
        ));

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals(array(
            'Authorization' => ['Basic bla'],
        ), $request->getHeaders());

    }

    /**
     * @runInSeparateProcess
     *
     * Unfortunately we have no way of testing if the HTTP response code got
     * changed.
     */
    function testSend() {

        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('XDebug needs to be installed for this test to run');
        }

        $response = new Response(204, ['Content-Type' => 'text/xml;charset=UTF-8']);

        // Second Content-Type header. Normally this doesn't make sense.
        $response->addHeader('Content-Type', 'application/xml');
        $response->setBody('foo');

        ob_start();

        Sapi::sendResponse($response);
        $headers = xdebug_get_headers();

        $result = ob_get_clean();
        header_remove();

        $this->assertEquals(
            [
                "Content-Type: text/xml;charset=UTF-8",
                "Content-Type: application/xml",
            ],
            $headers
        );

        $this->assertEquals('foo', $result);

    }

}
