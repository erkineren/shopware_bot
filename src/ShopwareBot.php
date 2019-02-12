<?php


namespace ShopwareBot;

use Curl\Curl;
use ShopwareBot\Exceptions\BadUrlException;
use ShopwareBot\Exceptions\CsrfException;
use ShopwareBot\Modules\ArticleModule;
use ShopwareBot\Modules\BaseModule;
use ShopwareBot\Modules\CustomerModule;
use ShopwareBot\Modules\DhlModule;
use ShopwareBot\Modules\DpdModule;
use ShopwareBot\Modules\ImportExportModule;
use ShopwareBot\Modules\LogModule;
use ShopwareBot\Modules\OrderModule;
use ShopwareBot\Modules\PickwareModule;
use ShopwareBot\Modules\PluginsModule;

/**
 * Class ShopwareBot
 *
 * @author Erkin EREN
 *
 * @method ImportExportModule getImportExportModule()
 * @method LogModule getLogModule()
 * @method OrderModule getOrderModule()
 * @method DpdModule getDpdModule()
 * @method DhlModule getDhlModule()
 * @method PickwareModule getPickwareModule()
 * @method ArticleModule getArticleModule()
 * @method CustomerModule getCustomerModule()
 * @method PluginsModule getPluginsModule()
 *
 */
class ShopwareBot
{

    /**
     * @var ShopwareBot[]
     */
    private static $_instances;

    /**
     * @var string
     */
    protected $url_schema;
    /**
     * @var string
     */
    protected $url_host;
    /**
     * @var string
     */
    protected $url_path;
    /**
     * @var string
     */
    protected $url_query;
    /**
     * @var string
     */
    protected $url_base;
    /**
     * @var string
     */
    protected $url_backend;

    /**
     * @var string
     */
    protected $csrf_token;

    /**
     * @var Curl
     */
    protected $curl;


    /**
     * @var BaseModule[]
     */
    protected $modules = [];

    /**
     * @param $site_url
     * @return ShopwareBot
     */
    public static function getInstance($site_url)
    {
        if (!isset(self::$_instances[$site_url])) {
            self::$_instances[$site_url] = new self($site_url);
        }
        return self::$_instances[$site_url];
    }

    /**
     * ShopwareBot constructor.
     * @param null $site_url
     * @throws BadUrlException
     * @throws \ErrorException
     */
    public function __construct($site_url = null)
    {
        // Assign the CodeIgniter super-object


        $this->curl = new Curl();

        if ($site_url)
            $this->setUrl($site_url);

    }

    /**
     * @param $name
     * @param array $arguments
     * @return bool
     */
    public function __call($name, $arguments = [])
    {
        if (!preg_match('/^get([a-z]+Module)$/i', $name, $matches)) {
            return false;
        }

        $moduleName = $matches[1];
        $className = '\\ShopwareBot\\Modules\\' . $moduleName;

        if (!class_exists($className)) {
            return false;
        }

        if (!isset($this->modules[$moduleName]))
            $this->modules[$moduleName] = new $className($this);

        return $this->modules[$moduleName];
    }

    /**
     * @param $site_url
     * @throws BadUrlException
     */
    protected function setUrl($site_url)
    {
        if (!filter_var($site_url, FILTER_VALIDATE_URL))
            throw new BadUrlException('Site url (' . $site_url . ') is invalid. Must be started with http schema.');

        $parts = parse_url($site_url);

        if (isset($parts['scheme']))
            $this->url_schema = $parts['scheme'];
        if (isset($parts['host']))
            $this->url_host = $parts['host'];
        if (isset($parts['path']))
            $this->url_path = $parts['path'];
        if (isset($parts['query']))
            $this->url_query = $parts['query'];

        if ($this->url_host == gethostbyname($this->url_host))
            throw new BadUrlException('Site url (' . $site_url . ') is invalid.');

        $this->url_base = $this->url_schema . '://' . $this->url_host;
        $this->url_backend = $this->url_base . '/backend';

        $this->curl->setUrl($this->url_backend);
        $this->setCurlCookieFile();
    }

    /**
     *
     */
    protected function setCurlCookieFile()
    {
        $filename = __DIR__ . '/Cookies/' . str_replace('.', '_', $this->url_host) . '.txt';
        if (!file_exists($filename))
            touch($filename);
        $this->curl->setCookieFile($filename);
        $this->curl->setCookieJar($filename);
    }

    /**
     * @param $username
     * @param $password
     * @return mixed
     * @throws CsrfException
     */
    public function login($username, $password)
    {
        $csrf = $this->refreshCsrfToken();

        if (!$csrf)
            throw new CsrfException('Could not fetch csrf token.');

        $this->csrf_token = $csrf;
        //$this->curl = new Curl();

        $this->curl->setHeaders([
            //'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Origin', $this->url_base,
            'Referer', $this->url_backend,
            'User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36',
            'x-csrf-token', $csrf,
            'x-requested-with', 'XMLHttpRequest'
        ]);


        $fields = [
            '__csrf_token' => $csrf,
            'username' => $username,
            'password' => $password,
        ];

        return $this->curl->post($this->url_backend . '/Login/login', $fields);
    }

    /**
     * @return |null
     */
    public function refreshCsrfToken()
    {
        $this->curl->get($this->url_backend . '/CSRFToken/generate');
        $headers = $this->curl->getResponseHeaders();

        if (isset($headers['x-csrf-token'])) {
            $this->csrf_token = $headers['x-csrf-token'];
            $this->curl->setHeader('x-csrf-token', $this->csrf_token);
            return $this->csrf_token;
        }


        return null;
    }

    /**
     * @return mixed
     */
    public function getLoginStatus()
    {
        $res = $this->curl->get($this->url_backend . '/login/getLoginStatus');
        return $res->success;
    }

    /**
     * @param $path
     * @param array $data
     * @return mixed
     */
    public function get($path, $data = [])
    {
//        _yaz($this->url_backend . $path);
        $this->refreshCsrfToken();
        return $this->curl->get($this->url_backend . $path, $data);
    }

    /**
     * @param $path
     * @param $data
     * @return mixed
     */
    public function post($path, $data)
    {
        $this->refreshCsrfToken();
        return $this->curl->post($this->url_backend . $path, $data);
    }

    /**
     * @param $path
     * @param $filename
     * @return bool
     */
    public function download($path, $filename)
    {
        $this->curl->setOpt(CURLOPT_ENCODING, '');
        return $this->curl->download($this->url_backend . $path, $filename);
    }

//    public function upload($path, $filename)
//    {
//        $boundary = uniqid();
//        $delimiter = '-------------' . $boundary;
//    }

    /**
     * @param $boundary
     * @param $fields
     * @param $files
     * @return string
     */
    public function build_data_files($boundary, $fields, $files)
    {
        $data = '';
        $eol = "\r\n";

        $delimiter = '-------------WebKitFormBoundary' . $boundary;

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
                . $content . $eol;
        }


        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="fileId"; filename="' . basename($name) . '"' . $eol
                //. 'Content-Type: image/png'.$eol
                . 'Content-Transfer-Encoding: application/vnd.ms-excel' . $eol;

            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--" . $eol;
//        _yaz($data);

        return $data;
    }

    /**
     * @return Curl
     */
    public function getCurl()
    {
        return $this->curl;
    }

    /**
     * @return mixed
     */
    public function getUrlSchema()
    {
        return $this->url_schema;
    }

    /**
     * @return mixed
     */
    public function getUrlHost()
    {
        return $this->url_host;
    }

    /**
     * @return mixed
     */
    public function getUrlPath()
    {
        return $this->url_path;
    }

    /**
     * @return mixed
     */
    public function getUrlQuery()
    {
        return $this->url_query;
    }

    /**
     * @return mixed
     */
    public function getUrlBase()
    {
        return $this->url_base;
    }

    /**
     * @return mixed
     */
    public function getUrlBackend()
    {
        return $this->url_backend;
    }

    /**
     * @return mixed
     */
    public function getCsrfToken()
    {
        return $this->csrf_token;
    }


}