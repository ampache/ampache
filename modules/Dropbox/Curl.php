<?php
namespace Dropbox;

/**
 * A minimal wrapper around a cURL handle.
 *
 * @internal
 */
final class Curl
{
    /** @var resource */
    public $handle;

    /** @var string[] */
    private $headers = array();

    /**
     * @param string $url
     */
    function __construct($url)
    {
        // Make sure there aren't any spaces in the URL (i.e. the caller forgot to URL-encode).
        if (strpos($url, ' ') !== false) {
            throw new \InvalidArgumentException("Found space in \$url; it should be encoded");
        }

        $this->handle = curl_init($url);

        // NOTE: Though we turn on all the correct SSL settings, many PHP installations
        // don't respect these settings.  Run "examples/test-ssl.php" to run some basic
        // SSL tests to see how well your PHP implementation behaves.

        // Use our own certificate list.
        $this->set(CURLOPT_SSL_VERIFYPEER, true);   // Enforce certificate validation
        $this->set(CURLOPT_SSL_VERIFYHOST, 2);      // Enforce hostname validation

        // Force the use of TLS (SSL v2 and v3 are not secure).
        // TODO: Use "CURL_SSLVERSION_TLSv1" instead of "1" once we can rely on PHP 5.5+.
        $this->set(CURLOPT_SSLVERSION, 1);

        // Limit the set of ciphersuites used.
        global $sslCiphersuiteList;
        if ($sslCiphersuiteList !== null) {
            $this->set(CURLOPT_SSL_CIPHER_LIST, $sslCiphersuiteList);
        }

        // Certificate file.
        $this->set(CURLOPT_CAINFO, __DIR__.'/certs/trusted-certs.crt');
        // Certificate folder.  If not specified, some PHP installations will use
        // the system default, even when CURLOPT_CAINFO is specified.
        $this->set(CURLOPT_CAPATH, __DIR__.'/certs/');

        // Limit vulnerability surface area.  Supported in cURL 7.19.4+
        if (defined('CURLOPT_PROTOCOLS')) $this->set(CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        if (defined('CURLOPT_REDIR_PROTOCOLS')) $this->set(CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
    }

    /**
     * @param string $header
     */
    function addHeader($header)
    {
        $this->headers[] = $header;
    }

    function exec()
    {
        $this->set(CURLOPT_HTTPHEADER, $this->headers);

        $body = curl_exec($this->handle);
        if ($body === false) {
            throw new Exception_NetworkIO("Error executing HTTP request: " . curl_error($this->handle));
        }

        $statusCode = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

        return new HttpResponse($statusCode, $body);
    }

    /**
     * @param int $option
     * @param mixed $value
     */
    function set($option, $value)
    {
        curl_setopt($this->handle, $option, $value);
    }

    function __destruct()
    {
        curl_close($this->handle);
    }
}

// Different cURL SSL backends use different names for ciphersuites.
$curlVersion = \curl_version();
$curlSslBackend = $curlVersion['ssl_version'];
if (\substr_compare($curlSslBackend, "NSS/", 0, strlen("NSS/")) === 0) {
    // Can't figure out how to reliably set ciphersuites for NSS.
    $sslCiphersuiteList = null;
}
else {
    // Use the OpenSSL names for all other backends.  We may have to
    // refine this if users report errors.
    $sslCiphersuiteList =
        'ECDHE-RSA-AES256-GCM-SHA384:'.
        'ECDHE-RSA-AES128-GCM-SHA256:'.
        'ECDHE-RSA-AES256-SHA384:'.
        'ECDHE-RSA-AES128-SHA256:'.
        'ECDHE-RSA-AES256-SHA:'.
        'ECDHE-RSA-AES128-SHA:'.
        'ECDHE-RSA-RC4-SHA:'.
        'DHE-RSA-AES256-GCM-SHA384:'.
        'DHE-RSA-AES128-GCM-SHA256:'.
        'DHE-RSA-AES256-SHA256:'.
        'DHE-RSA-AES128-SHA256:'.
        'DHE-RSA-AES256-SHA:'.
        'DHE-RSA-AES128-SHA:'.
        'AES256-GCM-SHA384:'.
        'AES128-GCM-SHA256:'.
        'AES256-SHA256:'.
        'AES128-SHA256:'.
        'AES256-SHA:'.
        'AES128-SHA';
}
