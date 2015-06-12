<?php

namespace Sabre\HTTP;

class ClientTest extends \PHPUnit_Framework_TestCase {

    protected $client;

    function testCreateCurlSettingsArrayGET() {

        $client = new ClientMock();
        $client->addCurlSetting(CURLOPT_POSTREDIR, 0);

        $request = new Request('GET','http://example.org/', ['X-Foo' => 'bar']);

        $settings = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_POSTREDIR => 0,
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POSTFIELDS => null,
                CURLOPT_PUT => false,
            ];

        // FIXME: CURLOPT_PROTOCOLS and CURLOPT_REDIR_PROTOCOLS are currently unsupported by HHVM
        // at least if this unit test fails in the future we know it is :)
        if(defined('HHVM_VERSION') === false) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }


        $this->assertEquals($settings, $client->createCurlSettingsArray($request));

    }

    function testCreateCurlSettingsArrayHEAD() {

        $client = new ClientMock();
        $request = new Request('HEAD','http://example.org/', ['X-Foo' => 'bar']);


        $settings = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => true,
                CURLOPT_CUSTOMREQUEST => 'HEAD',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
                CURLOPT_POSTFIELDS => null,
                CURLOPT_PUT => false,
            ];

        // FIXME: CURLOPT_PROTOCOLS and CURLOPT_REDIR_PROTOCOLS are currently unsupported by HHVM
        // at least if this unit test fails in the future we know it is :)
        if(defined('HHVM_VERSION') === false) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));

    }

    function testCreateCurlSettingsArrayPUTStream() {

        $client = new ClientMock();

        $h = fopen('php://memory', 'r+');
        fwrite($h, 'booh');
        $request = new Request('PUT','http://example.org/', ['X-Foo' => 'bar'], $h);

        $settings = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_PUT => true,
                CURLOPT_INFILE => $h,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
            ];

        // FIXME: CURLOPT_PROTOCOLS and CURLOPT_REDIR_PROTOCOLS are currently unsupported by HHVM
        // at least if this unit test fails in the future we know it is :)
        if(defined('HHVM_VERSION') === false) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));

    }

    function testCreateCurlSettingsArrayPUTString() {

        $client = new ClientMock();
        $request = new Request('PUT','http://example.org/', ['X-Foo' => 'bar'], 'boo');

        $settings = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_POSTFIELDS => 'boo',
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
            ];

        // FIXME: CURLOPT_PROTOCOLS and CURLOPT_REDIR_PROTOCOLS are currently unsupported by HHVM
        // at least if this unit test fails in the future we know it is :)
        if(defined('HHVM_VERSION') === false) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));

    }

    function testSend() {

        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function($request, &$response) {
            $response = new Response(200);
        });

        $response = $client->send($request);

        $this->assertEquals(200, $response->getStatus());

    }

    function testSendClientError() {

        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function($request, &$response) {
            throw new ClientException('aaah',1);
        });
        $called = false;
        $client->on('exception', function() use (&$called) {
            $called = true;
        });

        try {
            $client->send($request);
            $this->fail('send() should have thrown an exception');
        } catch (ClientException $e) {

        }
        $this->assertTrue($called);

    }

    function testSendHttpError() {

        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function($request, &$response) {
            $response = new Response(404);
        });
        $called = 0;
        $client->on('error', function() use (&$called) {
            $called++;
        });
        $client->on('error:404', function() use (&$called) {
            $called++;
        });

        $client->send($request);
        $this->assertEquals(2,$called);

    }

    function testSendRetry() {

        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $called = 0;
        $client->on('doRequest', function($request, &$response) use (&$called) {
            $called++;
            if ($called < 3) {
                $response = new Response(404);
            } else {
                $response = new Response(200);
            }
        });

        $errorCalled = 0;
        $client->on('error', function($request, $response, &$retry, $retryCount) use (&$errorCalled) {

            $errorCalled++;
            $retry = true;

        });

        $response = $client->send($request);
        $this->assertEquals(3,$called);
        $this->assertEquals(2,$errorCalled);
        $this->assertEquals(200, $response->getStatus());

    }

    function testHttpErrorException() {

        $client = new ClientMock();
        $client->setThrowExceptions(true);
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function($request, &$response) {
            $response = new Response(404);
        });

        try {
            $client->send($request);
            $this->fail('An exception should have been thrown');
        } catch (ClientHttpException $e) {
            $this->assertEquals(404, $e->getHttpStatus());
            $this->assertInstanceOf('Sabre\HTTP\Response', $e->getResponse());
        }

    }

    function testParseCurlResult() {

        $client = new ClientMock();
        $client->on('curlStuff', function(&$return) {

            $return = [
                [
                    'header_size' => 33,
                    'http_code' => 200,
                ],
                0,
                '',
            ];

        });

        $body = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\nFoo";
        $result = $client->parseCurlResult($body, 'foobar');

        $this->assertEquals(Client::STATUS_SUCCESS, $result['status']);
        $this->assertEquals(200, $result['http_code']);
        $this->assertEquals(200, $result['response']->getStatus());
        $this->assertEquals(['Header1' => ['Val1']], $result['response']->getHeaders());
        $this->assertEquals('Foo', $result['response']->getBodyAsString());

    }

    function testParseCurlError() {

        $client = new ClientMock();
        $client->on('curlStuff', function(&$return) {

            $return = [
                [],
                1,
                'Curl error',
            ];

        });

        $body = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\nFoo";
        $result = $client->parseCurlResult($body, 'foobar');

        $this->assertEquals(Client::STATUS_CURLERROR, $result['status']);
        $this->assertEquals(1, $result['curl_errno']);
        $this->assertEquals('Curl error', $result['curl_errmsg']);

    }

    function testDoRequest() {

        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');
        $client->on('curlExec', function(&$return) {

            $return = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\nFoo";

        });
        $client->on('curlStuff', function(&$return) {

            $return = [
                [
                    'header_size' => 33,
                    'http_code' => 200,
                ],
                0,
                '',
            ];

        });
        $response = $client->doRequest($request);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['Header1' => ['Val1']], $response->getHeaders());
        $this->assertEquals('Foo', $response->getBodyAsString());

    }

    function testDoRequestCurlError() {

        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');
        $client->on('curlExec', function(&$return) {

            $return = "";

        });
        $client->on('curlStuff', function(&$return) {

            $return = [
                [],
                1,
                'Curl error',
            ];

        });

        try {
            $response = $client->doRequest($request);
            $this->fail('This should have thrown an exception');
        } catch (ClientException $e) {
            $this->assertEquals(1, $e->getCode());
            $this->assertEquals('Curl error', $e->getMessage());
        }

    }

}

class ClientMock extends Client {

    /**
     * Making this method public.
     */
    public function createCurlSettingsArray(RequestInterface $request) {

        return parent::createCurlSettingsArray($request);

    }
    /**
     * Making this method public.
     */
    public function parseCurlResult($response, $curlHandle) {

        return parent::parseCurlResult($response, $curlHandle);

    }

    /**
     * This method is responsible for performing a single request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function doRequest(RequestInterface $request) {

        $response = null;
        $this->emit('doRequest', [$request, &$response]);

        // If nothing modified $response, we're using the default behavior.
        if (is_null($response)) {
            return parent::doRequest($request);
        } else {
            return $response;
        }

    }

    /**
     * Returns a bunch of information about a curl request.
     *
     * This method exists so it can easily be overridden and mocked.
     *
     * @param resource $curlHandle
     * @return array
     */
    protected function curlStuff($curlHandle) {

        $return = null;
        $this->emit('curlStuff', [&$return]);

        // If nothing modified $return, we're using the default behavior.
        if (is_null($return)) {
            return parent::curlStuff($curlHandle);
        } else {
            return $return;
        }

    }

    /**
     * Calls curl_exec
     *
     * This method exists so it can easily be overridden and mocked.
     *
     * @param resource $curlHandle
     * @return string
     */
    protected function curlExec($curlHandle) {

        $return = null;
        $this->emit('curlExec', [&$return]);

        // If nothing modified $return, we're using the default behavior.
        if (is_null($return)) {
            return parent::curlExec($curlHandle);
        } else {
            return $return;
        }

    }

}
