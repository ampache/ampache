<?php

namespace GuzzleHttp\Tests\CookieJar;

use GuzzleHttp\Cookie\SetCookie;

/**
 * @covers GuzzleHttp\Cookie\SetCookie
 */
class SetCookieTest extends \PHPUnit_Framework_TestCase
{
    public function testInitializesDefaultValues()
    {
        $cookie = new SetCookie();
        $this->assertEquals('/', $cookie->getPath());
    }

    public function testConvertsDateTimeMaxAgeToUnixTimestamp()
    {
        $cookie = new SetCookie(['Expires' => 'November 20, 1984']);
        $this->assertInternalType('integer', $cookie->getExpires());
    }

    public function testAddsExpiresBasedOnMaxAge()
    {
        $t = time();
        $cookie = new SetCookie(['Max-Age' => 100]);
        $this->assertEquals($t + 100, $cookie->getExpires());
    }

    public function testHoldsValues()
    {
        $t = time();
        $data = array(
            'Name'     => 'foo',
            'Value'    => 'baz',
            'Path'     => '/bar',
            'Domain'   => 'baz.com',
            'Expires'  => $t,
            'Max-Age'  => 100,
            'Secure'   => true,
            'Discard'  => true,
            'HttpOnly' => true,
            'foo'      => 'baz',
            'bar'      => 'bam'
        );

        $cookie = new SetCookie($data);
        $this->assertEquals($data, $cookie->toArray());

        $this->assertEquals('foo', $cookie->getName());
        $this->assertEquals('baz', $cookie->getValue());
        $this->assertEquals('baz.com', $cookie->getDomain());
        $this->assertEquals('/bar', $cookie->getPath());
        $this->assertEquals($t, $cookie->getExpires());
        $this->assertEquals(100, $cookie->getMaxAge());
        $this->assertTrue($cookie->getSecure());
        $this->assertTrue($cookie->getDiscard());
        $this->assertTrue($cookie->getHttpOnly());
        $this->assertEquals('baz', $cookie->toArray()['foo']);
        $this->assertEquals('bam', $cookie->toArray()['bar']);

        $cookie->setName('a');
        $cookie->setValue('b');
        $cookie->setPath('c');
        $cookie->setDomain('bar.com');
        $cookie->setExpires(10);
        $cookie->setMaxAge(200);
        $cookie->setSecure(false);
        $cookie->setHttpOnly(false);
        $cookie->setDiscard(false);

        $this->assertEquals('a', $cookie->getName());
        $this->assertEquals('b', $cookie->getValue());
        $this->assertEquals('c', $cookie->getPath());
        $this->assertEquals('bar.com', $cookie->getDomain());
        $this->assertEquals(10, $cookie->getExpires());
        $this->assertEquals(200, $cookie->getMaxAge());
        $this->assertFalse($cookie->getSecure());
        $this->assertFalse($cookie->getDiscard());
        $this->assertFalse($cookie->getHttpOnly());
    }

    public function testDeterminesIfExpired()
    {
        $c = new SetCookie();
        $c->setExpires(10);
        $this->assertTrue($c->isExpired());
        $c->setExpires(time() + 10000);
        $this->assertFalse($c->isExpired());
    }

    public function testMatchesDomain()
    {
        $cookie = new SetCookie();
        $this->assertTrue($cookie->matchesDomain('baz.com'));

        $cookie->setDomain('baz.com');
        $this->assertTrue($cookie->matchesDomain('baz.com'));
        $this->assertFalse($cookie->matchesDomain('bar.com'));

        $cookie->setDomain('.baz.com');
        $this->assertTrue($cookie->matchesDomain('.baz.com'));
        $this->assertTrue($cookie->matchesDomain('foo.baz.com'));
        $this->assertFalse($cookie->matchesDomain('baz.bar.com'));
        $this->assertTrue($cookie->matchesDomain('baz.com'));

        $cookie->setDomain('.127.0.0.1');
        $this->assertTrue($cookie->matchesDomain('127.0.0.1'));

        $cookie->setDomain('127.0.0.1');
        $this->assertTrue($cookie->matchesDomain('127.0.0.1'));

        $cookie->setDomain('.com.');
        $this->assertFalse($cookie->matchesDomain('baz.com'));

        $cookie->setDomain('.local');
        $this->assertTrue($cookie->matchesDomain('example.local'));
    }

    public function testMatchesPath()
    {
        $cookie = new SetCookie();
        $this->assertTrue($cookie->matchesPath('/foo'));

        $cookie->setPath('/foo');
        $this->assertTrue($cookie->matchesPath('/foo'));
        $this->assertTrue($cookie->matchesPath('/foo/bar'));
        $this->assertFalse($cookie->matchesPath('/bar'));
    }

    public function cookieValidateProvider()
    {
        return array(
            array('foo', 'baz', 'bar', true),
            array('0', '0', '0', true),
            array('', 'baz', 'bar', 'The cookie name must not be empty'),
            array('foo', '', 'bar', 'The cookie value must not be empty'),
            array('foo', 'baz', '', 'The cookie domain must not be empty'),
            array("foo\r", 'baz', '0', 'Cookie name must not cannot invalid characters: =,; \t\r\n\013\014'),
        );
    }

    /**
     * @dataProvider cookieValidateProvider
     */
    public function testValidatesCookies($name, $value, $domain, $result)
    {
        $cookie = new SetCookie(array(
            'Name'   => $name,
            'Value'  => $value,
            'Domain' => $domain
        ));
        $this->assertSame($result, $cookie->validate());
    }

    public function testDoesNotMatchIp()
    {
        $cookie = new SetCookie(['Domain' => '192.168.16.']);
        $this->assertFalse($cookie->matchesDomain('192.168.16.121'));
    }

    public function testConvertsToString()
    {
        $t = 1382916008;
        $cookie = new SetCookie([
            'Name' => 'test',
            'Value' => '123',
            'Domain' => 'foo.com',
            'Expires' => $t,
            'Path' => '/abc',
            'HttpOnly' => true,
            'Secure' => true
        ]);
        $this->assertEquals(
            'test=123; Domain=foo.com; Path=/abc; Expires=Sun, 27 Oct 2013 23:20:08 GMT; Secure; HttpOnly',
            (string) $cookie
        );
    }

    /**
     * Provides the parsed information from a cookie
     *
     * @return array
     */
    public function cookieParserDataProvider()
    {
        return array(
            array(
                'ASIHTTPRequestTestCookie=This+is+the+value; expires=Sat, 26-Jul-2008 17:00:42 GMT; path=/tests; domain=allseeing-i.com; PHPSESSID=6c951590e7a9359bcedde25cda73e43c; path=/";',
                array(
                    'Domain' => 'allseeing-i.com',
                    'Path' => '/',
                    'PHPSESSID' => '6c951590e7a9359bcedde25cda73e43c',
                    'Max-Age' => NULL,
                    'Expires' => 'Sat, 26-Jul-2008 17:00:42 GMT',
                    'Secure' => NULL,
                    'Discard' => NULL,
                    'Name' => 'ASIHTTPRequestTestCookie',
                    'Value' => 'This+is+the+value',
                    'HttpOnly' => false
                )
            ),
            array('', []),
            array('foo', []),
            // Test setting a blank value for a cookie
            array(array(
                'foo=', 'foo =', 'foo =;', 'foo= ;', 'foo =', 'foo= '),
                array(
                    'Name' => 'foo',
                    'Value' => '',
                    'Discard' => null,
                    'Domain' => null,
                    'Expires' => null,
                    'Max-Age' => null,
                    'Path' => '/',
                    'Secure' => null,
                    'HttpOnly' => false
                )
            ),
            // Test setting a value and removing quotes
            array(array(
                'foo=1', 'foo =1', 'foo =1;', 'foo=1 ;', 'foo =1', 'foo= 1', 'foo = 1 ;', 'foo="1"', 'foo="1";', 'foo= "1";'),
                array(
                    'Name' => 'foo',
                    'Value' => '1',
                    'Discard' => null,
                    'Domain' => null,
                    'Expires' => null,
                    'Max-Age' => null,
                    'Path' => '/',
                    'Secure' => null,
                    'HttpOnly' => false
                )
            ),
            // Some of the following tests are based on http://framework.zend.com/svn/framework/standard/trunk/tests/Zend/Http/CookieTest.php
            array(
                'justacookie=foo; domain=example.com',
                array(
                    'Name' => 'justacookie',
                    'Value' => 'foo',
                    'Domain' => 'example.com',
                    'Discard' => null,
                    'Expires' => null,
                    'Max-Age' => null,
                    'Path' => '/',
                    'Secure' => null,
                    'HttpOnly' => false
                )
            ),
            array(
                'expires=tomorrow; secure; path=/Space Out/; expires=Tue, 21-Nov-2006 08:33:44 GMT; domain=.example.com',
                array(
                    'Name' => 'expires',
                    'Value' => 'tomorrow',
                    'Domain' => '.example.com',
                    'Path' => '/Space Out/',
                    'Expires' => 'Tue, 21-Nov-2006 08:33:44 GMT',
                    'Discard' => null,
                    'Secure' => true,
                    'Max-Age' => null,
                    'HttpOnly' => false
                )
            ),
            array(
                'domain=unittests; expires=Tue, 21-Nov-2006 08:33:44 GMT; domain=example.com; path=/some value/',
                array(
                    'Name' => 'domain',
                    'Value' => 'unittests',
                    'Domain' => 'example.com',
                    'Path' => '/some value/',
                    'Expires' => 'Tue, 21-Nov-2006 08:33:44 GMT',
                    'Secure' => false,
                    'Discard' => null,
                    'Max-Age' => null,
                    'HttpOnly' => false
                )
            ),
            array(
                'path=indexAction; path=/; domain=.foo.com; expires=Tue, 21-Nov-2006 08:33:44 GMT',
                array(
                    'Name' => 'path',
                    'Value' => 'indexAction',
                    'Domain' => '.foo.com',
                    'Path' => '/',
                    'Expires' => 'Tue, 21-Nov-2006 08:33:44 GMT',
                    'Secure' => false,
                    'Discard' => null,
                    'Max-Age' => null,
                    'HttpOnly' => false
                )
            ),
            array(
                'secure=sha1; secure; SECURE; domain=some.really.deep.domain.com; version=1; Max-Age=86400',
                array(
                    'Name' => 'secure',
                    'Value' => 'sha1',
                    'Domain' => 'some.really.deep.domain.com',
                    'Path' => '/',
                    'Secure' => true,
                    'Discard' => null,
                    'Expires' => time() + 86400,
                    'Max-Age' => 86400,
                    'HttpOnly' => false,
                    'version' => '1'
                )
            ),
            array(
                'PHPSESSID=123456789+abcd%2Cef; secure; discard; domain=.localdomain; path=/foo/baz; expires=Tue, 21-Nov-2006 08:33:44 GMT;',
                array(
                    'Name' => 'PHPSESSID',
                    'Value' => '123456789+abcd%2Cef',
                    'Domain' => '.localdomain',
                    'Path' => '/foo/baz',
                    'Expires' => 'Tue, 21-Nov-2006 08:33:44 GMT',
                    'Secure' => true,
                    'Discard' => true,
                    'Max-Age' => null,
                    'HttpOnly' => false
                )
            ),
        );
    }

    /**
     * @dataProvider cookieParserDataProvider
     */
    public function testParseCookie($cookie, $parsed)
    {
        foreach ((array) $cookie as $v) {
            $c = SetCookie::fromString($v);
            $p = $c->toArray();

            if (isset($p['Expires'])) {
                // Remove expires values from the assertion if they are relatively equal
                if (abs($p['Expires'] != strtotime($parsed['Expires'])) < 40) {
                    unset($p['Expires']);
                    unset($parsed['Expires']);
                }
            }

            if (!empty($parsed)) {
                foreach ($parsed as $key => $value) {
                    $this->assertEquals($parsed[$key], $p[$key], 'Comparing ' . $key . ' ' . var_export($value, true) . ' : ' . var_export($parsed, true) . ' | ' . var_export($p, true));
                }
                foreach ($p as $key => $value) {
                    $this->assertEquals($p[$key], $parsed[$key], 'Comparing ' . $key . ' ' . var_export($value, true) . ' : ' . var_export($parsed, true) . ' | ' . var_export($p, true));
                }
            } else {
                $this->assertEquals([
                    'Name' => null,
                    'Value' => null,
                    'Domain' => null,
                    'Path' => '/',
                    'Max-Age' => null,
                    'Expires' => null,
                    'Secure' => false,
                    'Discard' => false,
                    'HttpOnly' => false,
                ], $p);
            }
        }
    }
}
