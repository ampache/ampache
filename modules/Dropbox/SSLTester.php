<?php

namespace Dropbox;

/**
 * Call the <code>test()</code> method.
 */
class SSLTester
{
    /**
     * Peforms a few basic tests of your PHP installation's SSL implementation to see
     * if it insecure in an obvious way.  Results are written with "echo" and the output
     * is HTML-safe.
     *
     * @return bool
     *    Returns <code>true</code> if all the tests passed.
     */
    static function test()
    {
        $hostOs = php_uname('s').' '.php_uname('r');
        $phpVersion = phpversion();
        $curlVersionInfo = \curl_version();
        $curlVersion = $curlVersionInfo['version'];
        $curlSslBackend = $curlVersionInfo['ssl_version'];

        echo "-----------------------------------------------------------------------------\n";
        echo "Testing your PHP installation's SSL implementation for a few obvious problems...\n";
        echo "-----------------------------------------------------------------------------\n";
        echo "- Host OS: $hostOs\n";
        echo "- PHP version: $phpVersion\n";
        echo "- cURL version: $curlVersion\n";
        echo "- cURL SSL backend: $curlSslBackend\n";

        echo "Basic SSL tests\n";
        $basicFailures = self::testMulti(array(
            array("www.dropbox.com", 'testAllowed'),
            array("www.digicert.com", 'testAllowed'),
            array("www.v.dropbox.com", 'testHostnameMismatch'),
            array("testssl-expire.disig.sk", 'testUntrustedCert'),
        ));

        echo "Pinned certificate tests\n";
        $pinnedCertFailures = self::testMulti(array(
            array("www.verisign.com", 'testUntrustedCert'),
            array("www.globalsign.fr", 'testUntrustedCert'),
        ));

        if ($basicFailures) {
            echo "-----------------------------------------------------------------------------\n";
            echo "WARNING: Your PHP installation's SSL support is COMPLETELY INSECURE.\n";
            echo "Your app's communication with the Dropbox API servers can be viewed and\n";
            echo "manipulated by others.  Try upgrading your version of PHP.\n";
            echo "-----------------------------------------------------------------------------\n";
            return false;
        }
        else if ($pinnedCertFailures) {
            echo "-----------------------------------------------------------------------------\n";
            echo "WARNING: Your PHP installation's cURL module doesn't support SSL certificate\n";
            echo "pinning, which is an important security feature of the Dropbox SDK.\n";
            echo "\n";
            echo "This SDK uses CURLOPT_CAINFO and CURLOPT_CAPATH to tell PHP cURL to only trust\n";
            echo "our custom certificate list.  But your PHP installation's cURL module seems to\n";
            echo "trust certificates that aren't on that list.\n";
            echo "\n";
            echo "More information on SSL certificate pinning:\n";
            echo "https://www.owasp.org/index.php/Certificate_and_Public_Key_Pinning#What_Is_Pinning.3F\n";
            echo "-----------------------------------------------------------------------------\n";
            return false;
        }
        else {
            return true;
        }
    }

    private static function testMulti($tests)
    {
        $anyFailed = false;
        foreach ($tests as $test) {
            list($host, $testType) = $test;

            echo " - ".str_pad("$testType ($host) ", 50, ".");
            $url = "https://$host/";
            $passed = self::$testType($url);
            if ($passed) {
                echo " ok\n";
            } else {
                echo " FAILED\n";
                $anyFailed = true;
            }
        }
        return $anyFailed;
    }

    private static function testPinnedCert()
    {
    }

    private static function testAllowed($url)
    {
        $curl = RequestUtil::mkCurl("test-ssl", $url);
        $curl->set(CURLOPT_RETURNTRANSFER, true);
        $curl->exec();
        return true;
    }

    private static function testUntrustedCert($url)
    {
        return self::testDisallowed($url, 'Error executing HTTP request: SSL certificate problem, verify that the CA cert is OK');
    }

    private static function testHostnameMismatch($url)
    {
        return self::testDisallowed($url, 'Error executing HTTP request: SSL certificate problem: Invalid certificate chain');
    }

    private static function testDisallowed($url, $expectedExceptionMessage)
    {
        $curl = RequestUtil::mkCurl("test-ssl", $url);
        $curl->set(CURLOPT_RETURNTRANSFER, true);
        try {
            $curl->exec();
        }
        catch (Exception_NetworkIO $ex) {
            if (strpos($ex->getMessage(), $expectedExceptionMessage) == 0) {
                return true;
            } else {
                throw $ex;
            }
        }
        return false;
    }
}
