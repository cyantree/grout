<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\ServerTools;

class Bootstrap
{
    /** @var App */
    public $app;

    /** @var ArrayFilter */
    protected $get;

    /** @var ArrayFilter */
    protected $post;

    /** @var ArrayFilter */
    protected $server;

    /** @var ArrayFilter */
    protected $files;

    /** @var ArrayFilter */
    protected $cookies;

    public $applicationPath;

    public $assetDirectory = 'assets/';

    public $usesModRewrite;

    public $checkForMagicQuotes;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function init()
    {
        if ($this->checkForMagicQuotes && ini_get('magic_quotes_gpc')) {
            $this->get = new ArrayFilter(ServerTools::decodeMagicQuotes($_GET));
            $this->post = new ArrayFilter(ServerTools::decodeMagicQuotes($_POST));
            $this->cookies = new ArrayFilter(ServerTools::decodeMagicQuotes($_COOKIE));

        } else {
            $this->get = new ArrayFilter($_GET);
            $this->post = new ArrayFilter($_POST);
            $this->cookies = new ArrayFilter($_COOKIE);
        }

        $this->server = new ArrayFilter($_SERVER);
        $this->files = new ArrayFilter($_FILES);

        $this->setBasePaths();
        $this->setBaseUrls();

        $r = new Request($this->retrieveUrl(), $this->get, $this->post);
        $r->files = $this->files;
        $r->cookies = $this->cookies;
        $r->server = $this->server;
        $r->method = strtoupper($this->server->get('REQUEST_METHOD'));

        // Removed GET config parameters (Grout_*) and move them to config array
        $get = $this->get->getData();
        $config = array();
        foreach ($get as $key => $value) {
            if (substr($key, 0, 6) === 'Grout_') {
                $config[$key] = $value;
            }
        }

        foreach ($config as $key => $value) {
            unset($get[$key]);
        }
        $r->get->setData($get);
        $r->config->setData($config);

        if ($urlPrefix = $r->config->get('Grout_UrlPrefix')) {
            $this->app->url .= $urlPrefix;

            $r->url = substr($r->url, strlen($urlPrefix));
        }

        $r->url = str_replace('%2F', '', urldecode($r->url));

        return $r;
    }

    protected function setBaseUrls()
    {
        if ($this->server->get('HTTPS') == 'on') {
            $this->app->url = 'https://' . $this->server->needs('HTTP_HOST');
        } else {
            $this->app->url = 'http://' . $this->server->needs('HTTP_HOST');
        }

        if ($this->usesModRewrite) {
            $this->app->url .= $this->server->get('SCRIPT_NAME');
            $this->app->publicUrl = $this->app->url = substr($this->app->url, 0, strrpos($this->app->url, '/') + 1);

        } else {
            $scriptName = substr($this->server->get('SCRIPT_FILENAME'), strlen($this->server->get('DOCUMENT_ROOT')));
            if ($scriptName[0] != '/') {
                $scriptName = '/' . $scriptName;
            }
            $this->app->publicUrl = $this->app->url
                . substr($scriptName, 0, strlen($scriptName) - strlen(basename($scriptName)));
            $this->app->url .= $scriptName . '/';
        }

        $this->app->publicAssetUrl = $this->assetDirectory;

        if (!$this->usesModRewrite) {
            $this->app->publicAssetUrl = $this->app->publicUrl . $this->app->publicAssetUrl;
            $this->app->publicAssetUrlIsAbsolute = true;
        }
    }

    protected function setBasePaths()
    {
        // Set server base paths
        $this->app->path = str_replace('\\', '/', realpath($this->applicationPath)) . '/';
        $this->app->publicPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])) . '/';
        $this->app->publicAssetPath = $this->app->publicPath . $this->assetDirectory;
    }

    protected function retrieveUrl()
    {
        if ($this->usesModRewrite) {
            $self = $this->server->needs('PHP_SELF');

//            if(($pathInfo = $this->_server->get('PATH_INFO')) || ($pathInfo = $this->_server->get('ORIG_PATH_INFO'))){
//                $self = substr($self, 0, strlen($self) - strlen($pathInfo));
//            }

            if (($pos = strrpos($self, '/')) !== false) {
                $self = substr($self, 0, $pos);

            } else {
                $self = substr($self, 0, strrpos($self, '\\'));
            }
            $url = substr($this->server->needs('REQUEST_URI'), strlen($self));
            if ($url === false) {
                $url = '';

            } elseif ($url !== '') {
                $posQueryString = strpos($url, '?');
                if ($posQueryString !== false) {
                    $url = substr($url, 0, $posQueryString);
                }
            }
            $url = substr($url, 1);

        } else {
            if ($this->server->has('PATH_INFO')) {
                $url = substr($this->server->get('PATH_INFO'), 1);

            } else {
                $url = substr($this->server->get('ORIG_PATH_INFO'), 1);
            }
        }

        if (substr($url, -1, 1) != '/') {
            $url .= '/';
        }

        return $url;
    }
}
