<?php

namespace Evirma\Bundle\CoreBundle\Service;

final class CurlService
{
    const DISABLE_COOKIE = 'curl_disable_cookie';
    const AUTODETECT_ENCODING = 'curl_autodetect_encoding';
    const ROTATE_IPS = 'rorate_ips';
    const ROTATE_PROXY_IPS = 'rorate_proxy_ips';

    private $_cookieArr = array();
    private $_cookie = null;
    private $_followLocation = true;
    private $_encoding = null;
    private $_autodetectEncoding = false;
    private $_disableCookie = false;
    private $_handler;
    private $_headers;
    private $_data = array('code' => 200, 'header' => null, 'body' => null);
    private $_lastRedirectDestination = '';

    private static ?string $previousIp = null;
    private static ?string $previousProxyIp = null;

    //'66.109.24.55',
    private static array $ips = array();
    private static bool $rotateIps = false;
    private static array $proxyIps = array();

    private bool $rotateUserAgent = true;
    private static bool $rotateProxyIps = false;

    protected array $userAgents = [
        'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.3) Gecko/20100401 SUSE/3.6.3-1.1 Firefox/3.6.3',
        'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.3) Gecko/20100404 Ubuntu/10.04 (lucid) Firefox/3.6.3',
        'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (.NET CLR 3.5.30729)',
        'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3',
        'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB6 (.NET CLR 3.5.30729)',
        'Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (.NET CLR 3.5.30729)',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB6',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 GTB6',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (.NET CLR 2.0.50727; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022; .NET CLR 1.1.4322; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)',
        'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.1.1) Gecko/20090716 Linux Mint/7 (Gloria) Firefox/3.5.1',
        'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.1.1) Gecko/20090716 Firefox/3.5.1',
        'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.1.1) Gecko/20090714 SUSE/3.5.1-1.1 Firefox/3.5.1'
    ];

    public function rotateIps()
    {
        if (!self::$rotateIps || (count(self::$ips) < 2)) {
            return;
        }

        if (self::$previousIp) {
            $selectedIp = self::$previousIp;

            while (self::$previousIp == $selectedIp) {
                $selectedIp = self::$ips[rand(0, count(self::$ips)-1)];
            }
        } else {
            $selectedIp = self::$ips[rand(0, count(self::$ips)-1)];
        }

        self::$previousIp = $selectedIp;
        //echo 'USED IP: ' . $selectedIp . "\n";
        $this->setOpt(CURLOPT_INTERFACE, $selectedIp);
    }

    public function rotateProxyIps()
    {
        if (!self::$rotateProxyIps || count(self::$proxyIps) < 2) {
            return;
        }

        if (self::$proxyIps) {
            $selectedIp = self::$proxyIps;

            while (self::$proxyIps == $selectedIp) {
                $selectedIp = self::$proxyIps[rand(0, count(self::$proxyIps)-1)];
            }
        } else {
            $selectedIp = self::$proxyIps[rand(0, count(self::$proxyIps)-1)];
        }

        $this->setProxy($selectedIp);
    }

    public function setProxy(?string $proxy): CurlService
    {
        if ($proxy) {
            self::$previousProxyIp = $proxy;
            $this->setOpt(CURLOPT_PROXY, $proxy);
        }
        return $this;
    }

    public function getIp()
    {
        return self::$previousIp;
    }

    public static function setRotateIps($flag = true)
    {
        self::$rotateIps = $flag;
    }

    public static function setRotateProxyIps($flag = true)
    {
        self::$rotateProxyIps = $flag;
    }

    public static function getLastProxy()
    {
        return self::$previousProxyIp;
    }

    public function getCode()
    {
        return $this->_data['code'];
    }

    public function disableCookie($flag)
    {
        $this->_disableCookie = (bool)$flag;
    }

    public function setAutodetectEncoding($flag)
    {
        $this->_autodetectEncoding = (bool)$flag;
        $this->_encoding = null;
        return $this;
    }

    public function __construct(string $url = null, $type = 'GET')
    {
        $this->init();
        if ($url) {
            if ($type == 'POST') {
                $this->post($url);
            } else {
                $this->get($url);
            }
        }
    }

    public function setEncoding($encoding)
    {
        if (strtolower($encoding) == 'utf-8') {
            $encoding = null;
        }

        $this->_encoding = $encoding;
        $this->_autodetectEncoding = false;
    }

    public function init()
    {
        $this->setOpt(CURLOPT_HEADER, 1);
        $this->setOpt(CURLOPT_RETURNTRANSFER, 1);
        $this->setOpt(CURLOPT_FOLLOWLOCATION, 0);
        $this->setOpt(CURLOPT_TIMEOUT, 30);
        //$this->setOpt(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.5) Gecko/2008120122 Firefox/3.1.5');
        $this->rotateUserAgent();
    }

    public function rotateUserAgent()
    {
        if (!$this->rotateUserAgent) {
            $this->setOpt(CURLOPT_USERAGENT, $this->userAgents[0]);
            return;
        }

        $this->setOpt(CURLOPT_USERAGENT, $this->userAgents[rand(0, count($this->userAgents)-1)]);
    }


    public function close()
    {
        curl_close($this->getHandler());
    }

    /**
     * @param       $url
     * @param array $data
     * @param null  $encoding
     *
     * @return CurlService
     */
    public function put($url, $data = array(), $encoding = null)
    {
        $this->_lastRedirectDestination = '';
        $this->setOpt(CURLOPT_CUSTOMREQUEST, "PUT");
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
        return $this->_load($url, $encoding);
    }

    /**
     * @param       $url
     * @param array $data
     * @param null  $encoding
     *
     * @return CurlService
     */
    public function post($url, $data = array(), $encoding = null)
    {
        $this->_lastRedirectDestination = '';
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
        return $this->_load($url, $encoding);
    }

    /**
     * @param      $url
     * @param null $encoding
     *
     * @return CurlService
     */
    public function get($url, $encoding = null)
    {
        $this->_lastRedirectDestination = '';
        return $this->_load($url, $encoding);
    }

    /**
     * @param      $url
     * @param null $encoding
     * @param int  $try
     *
     * @return CurlService
     */
    protected function _load($url, $encoding = null, $try = 1)
    {
        $this->setOpt(CURLOPT_URL, $url);

        if ($this->_disableCookie === false) {
            $this->setOpt(CURLOPT_COOKIE, $this->_cookie);
        }

        $this->rotateIps();
        $this->rotateProxyIps();
        $this->rotateUserAgent();

        $contents = curl_exec($this->getHandler());
        $this->parseResult($contents, $encoding);

        if ($this->isRedirect() && $try < 5) {
            $try++;

            $location = $this->getLocation();
            $_loc = parse_url($location);
            if (!array_key_exists('host', $_loc) || empty($_loc['host'])) {
                $delim = '';
                if (substr($location, 0, 1) != '/') {
                    $delim = '/';
                }

                /** @noinspection HttpUrlsUsage */
                $location = 'http://' . parse_url($this->getLastUrl(), PHP_URL_HOST) . $delim . $location;
            }

            $this->_lastRedirectDestination = $location;

            $this->_load($location, $encoding, $try);
        }

        return $this;
    }

    /**
     * Получить адрес куда ведет редирект
     *
     * @return string
     */
    public function getLastRedirectDestination()
    {
        return $this->_lastRedirectDestination;
    }

    public function setUrl($url)
    {
        $this->setOpt(CURLOPT_URL, $url);
    }

    /**
     * @param      $contents
     * @param null $encoding
     * @return CurlService
     */
    public function parseResult($contents, $encoding = null)
    {
        $contents = trim($contents);
        if (false !== stripos($contents, "HTTP/1.0 200 Connection established\r\n\r\n")) {
            $contents = str_ireplace("HTTP/1.0 200 Connection established\r\n\r\n", '', $contents);
        }

        $splitArray = preg_split("#\r?\n\r?\n#", trim($contents), 2);
        if (count($splitArray) == 2) {
            $this->_data['header'] = $splitArray[0];
            $this->_data['body'] = $splitArray[1];
        } else {
            $this->_data['header'] = $splitArray[0];
            $this->_data['body'] = null;
        }
        $this->_data['code'] = curl_getinfo($this->getHandler(), CURLINFO_HTTP_CODE);
        $this->parseHeader();
        $this->bodyDecode($encoding);
        $this->parseCookie();

        $location = $this->getLocation();
        if ($location && $this->_followLocation) {
            return $this->_load($location);
        }

        return $this;
    }

    public function getTotalTime()
    {
        return curl_getinfo($this->getHandler(), CURLINFO_TOTAL_TIME);
    }

    public function getConnectTime()
    {
        return curl_getinfo($this->getHandler(), CURLINFO_CONNECT_TIME);
    }

    public function getLastUrl()
    {
        return curl_getinfo($this->getHandler(), CURLINFO_EFFECTIVE_URL);
    }

    public function getLocation(): string
    {
        if (!$this->isRedirect()) {
            return '';
        } else if ($this->getHeader('location')) {
            return $this->getHeader('location');
        } else {
            return '';
        }
    }

    public function isRedirect(): bool
    {
        $code = $this->getCode();

        return (300 <= $code) && (307 >= $code);
    }

    public function getHeader(?string $key = null): string|array
    {
        if ($key) {
            if (array_key_exists($key, $this->_headers)) {
                return (string)$this->_headers[$key];
            } else {
                return '';
            }
        } else {
            return $this->_data['header'];
        }
    }

    public function getBody()
    {
        return $this->_data['body'];
    }

    public function setOpt(int|string $opt, mixed $val = false)
    {
        if (is_array($opt)) {
            foreach ($opt as $k => $v) {
                $this->setOpt($k, $v);
            }
        } elseif ($opt == self::ROTATE_IPS) {
            if (is_array($val)) {
                self::$ips = $val;
                self::$rotateIps = true;
            }
        } elseif ($opt == self::ROTATE_PROXY_IPS) {
            if (is_array($val)) {
                self::$proxyIps = $val;
                self::$rotateProxyIps = true;
            }
        } elseif ($opt == CURLOPT_USERAGENT) {
            if (is_array($val)) {
                $this->userAgents = $val;
                $this->rotateUserAgent = true;
            } else {
                $this->userAgents = array($val);
                $this->rotateUserAgent = false;
                curl_setopt($this->getHandler(), $opt, $val);
            }
        } elseif ($opt == self::DISABLE_COOKIE) {
            $this->disableCookie($val);
        } elseif ($opt == self::AUTODETECT_ENCODING) {
            $this->autodetectEncoding();
        } elseif ($opt == CURLOPT_FOLLOWLOCATION) {
            $this->_followLocation = $val;
        } elseif ($opt == CURLOPT_POSTFIELDS && is_array($val)) {
            $newVal = array_map(function($k, $v){return $k."=".$v;}, array_keys($val), array_values($val));
            $newVal =  implode('&', $newVal);
            curl_setopt($this->getHandler(), $opt, $newVal);
        } else {
            curl_setopt($this->getHandler(), $opt, $val);
        }

        return $this;
    }

    public function getHandler()
    {
        if (!$this->_handler) {
            $this->_handler = curl_init();
        }

        return $this->_handler;
    }

    public function parseHeader()
    {
        $lines = array_map('trim', explode("\n", $this->getHeader()));
        unset($lines[0]);

        $this->_headers = array();
        foreach ($lines as $line)
        {
            [$k, $v] = explode(':', $line, 2);
            $this->_headers[strtolower($k)] = trim($v);
        }

        return $this;
    }

    public function bodyDecode($encoding)
    {
        if ($encoding && strtolower($encoding) != 'utf-8') {
            $this->_data['body'] = iconv($encoding, 'utf-8', $this->_data['body']);
        }

        if ($this->_encoding) {
            $this->_data['body'] = iconv($this->_encoding, 'utf-8', $this->_data['body']);
        } elseif ($this->_autodetectEncoding) {
            $encoding = $this->autodetectEncoding();
            if ($encoding && strtolower($encoding) != 'utf-8') {
                $this->_data['body'] = iconv($encoding, 'utf-8', $this->_data['body']);
            }
        }

        return $this;
    }

    public function autodetectEncoding()
    {
        if (!$this->_autodetectEncoding) {
            return null;
        }

        $bodyCharset = null;
        $headerCharset = null;

        if (preg_match('#charset\s*=\s*([^\s\r\n]+)#si', $this->getHeader('content-type'), $m)) {
            $headerCharset = $m[1];
        }

        $body = $this->getBody();

        if (preg_match("#<meta.*?Content-Type.*?>#i", $body, $m)) {
            if (preg_match('#charset\s*=\s*([^\t \r\n\'\"]+)#', $m[0], $m2)) {
                $bodyCharset = $m2[1];
            }
        } else if (preg_match("#<meta.*?Content-Type.*?>#si", $body, $m)) {
            if (preg_match('#charset\s*=\s*([^\t \r\n\'\"]+)#', $m[0], $m2)) {
                $bodyCharset = $m2[1];
            }
        }

        if ($bodyCharset) {
            $result = $bodyCharset;
        } else if ($headerCharset) {
            $result = $headerCharset;
        } else {
            $result = null;
        }

        $assoc = array(
            '#^1251$#'=> 'windows-1251'
        );

        return preg_replace(array_keys($assoc), array_values($assoc), $result);
    }


    /**
     * получение cookie
     *
     * @return string с параметрами
     */
    function parseCookie()
    {
        if ($this->_disableCookie !== false) {
            return '';
        }

        preg_match_all('#Set-Cookie:([^\n]*)([\n]|)$#Umi', $this->getHeader(), $matches);

        foreach ($matches[1] as $k => $v) {
            $cookiestr = trim($v);

            $cookie = explode(';', $cookiestr);
            $cookie = explode('=', $cookie[0]);
            $cookiename = trim(array_shift($cookie));

            if (preg_match('#Expires=([^;]+);#usi', $cookiestr, $m)) {
                if (strtotime($m[1]) < time()) {
                    unset($this->_cookieArr[$cookiename]);
                    continue;
                }
            }

            $this->_cookieArr[$cookiename] = trim(implode('=', $cookie));
        }

        foreach ($this->_cookieArr as $key=>$value)
        {
            if ($value == 'deleted') {
                unset($this->_cookieArr[$key]);
            }
        }

        $this->_cookie = $this->cookieToString();
        return $this->_cookie;
    }

    public function cookieToString()
    {
        $cookie = "";
        foreach ($this->_cookieArr as $key=>$value)
        {
            $cookie .= "$key=$value; ";
        }

        return $cookie;
    }

    public function setCookie($name, $val)
    {
        if (empty($val)) {
            unset($this->_cookieArr[$name]);
        } else {
            $this->_cookieArr[$name] = $val;
        }
        $this->_cookie = $this->cookieToString();

        return $this;
    }

//    /**
//     * Установить прокси
//     *
//     * @param $host
//     * @param $port
//     * @param null $login
//     * @param null $password
//     * @return CurlService
//     */
//    public function setProxy($host, $port, $login = null, $password = null)
//    {
//        $proxy = '';
//        if ($login && $password) {
//            $proxy = $login . ':' . $password . '@';
//        }
//        $proxy .= $host . ':' . $port;
//
//        $this->setOpt(CURLOPT_HTTPPROXYTUNNEL, true);
//        $this->setOpt(CURLOPT_PROXY, $proxy);
//
//        return $this;
//    }
}