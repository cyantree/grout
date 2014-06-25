<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\ServerTools;

class Bootstrap
{
    /** @var App */
    public $app;

    /** @var ArrayFilter */
    protected $_get;

    /** @var ArrayFilter */
    protected $_post;

    /** @var ArrayFilter */
    protected $_server;

    /** @var ArrayFilter */
    protected $_files;

    /** @var ArrayFilter */
    protected $_cookies;

    public $frameworkPath;

    public $usesModRewrite;

    public $checkForMagicQuotes;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function init()
    {
        if ($this->checkForMagicQuotes && ini_get('magic_quotes_gpc')) {
            $this->_get = new ArrayFilter(ServerTools::decodeMagicQuotes($_GET));
            $this->_post = new ArrayFilter(ServerTools::decodeMagicQuotes($_POST));
            $this->_cookies = new ArrayFilter(ServerTools::decodeMagicQuotes($_COOKIE));

        } else {
            $this->_get = new ArrayFilter($_GET);
            $this->_post = new ArrayFilter($_POST);
            $this->_cookies = new ArrayFilter($_COOKIE);
        }

        $this->_server = new ArrayFilter($_SERVER);
        $this->_files = new ArrayFilter($_FILES);

        $this->_setBasePaths();
        $this->_setBaseUrls();

        $r = new Request($this->_retrieveUrl(), $this->_get, $this->_post);
        $r->files = $this->_files;
        $r->cookies = $this->_cookies;
        $r->server = $this->_server;
        $r->method = strtoupper($this->_server->get('REQUEST_METHOD'));

        // Removed GET config parameters (Grout_*) and move them to config array
        $get = $this->_get->getData();
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

        return $r;
    }

    protected function _setBaseUrls()
    {
        if ($this->_server->get('HTTPS') == 'on') {
            $this->app->url = 'https://' . $this->_server->needs('HTTP_HOST');
        } else {
            $this->app->url = 'http://' . $this->_server->needs('HTTP_HOST');
        }

        if ($this->usesModRewrite) {
            $this->app->url .= $this->_server->get('SCRIPT_NAME');
            $this->app->publicUrl = $this->app->url = substr($this->app->url, 0, strrpos($this->app->url, '/') + 1);

        } else {
            $scriptName = substr($this->_server->get('SCRIPT_FILENAME'), strlen($this->_server->get('DOCUMENT_ROOT')));
            if ($scriptName[0] != '/') {
                $scriptName = '/' . $scriptName;
            }
            $this->app->publicUrl = $this->app->url.substr($scriptName, 0, strlen($scriptName) - strlen(basename($scriptName)));
            $this->app->url .= $scriptName.'/';
        }
    }

    protected function _setBasePaths()
    {
        // Set server base paths
        $this->app->path = str_replace('\\', '/', realpath($this->frameworkPath)).'/';
        $this->app->publicPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])).'/';
    }

    protected function _retrieveUrl()
    {
        if($this->usesModRewrite){
            $self = $this->_server->needs('PHP_SELF');

//            if(($pathInfo = $this->_server->get('PATH_INFO')) || ($pathInfo = $this->_server->get('ORIG_PATH_INFO'))){
//                $self = substr($self, 0, strlen($self) - strlen($pathInfo));
//            }

            if(($pos = strrpos($self, '/')) !== false){
                $self = substr($self, 0, $pos);
            }else{
                $self = substr($self, 0, strrpos($self, '\\'));
            }
            $url = substr($this->_server->needs('REQUEST_URI'), strlen($self));
            if($url === false){
                $url = '';

            }elseif($url !== ''){
                $posQueryString = strpos($url, '?');
                if($posQueryString !== false){
                    $url = substr($url, 0, $posQueryString);
                }
            }
            $url = substr($url, 1);

        }else{
            if($this->_server->has('PATH_INFO')){
                $url = substr($this->_server->get('PATH_INFO'), 1);
            }else{
                $url =  substr($this->_server->get('ORIG_PATH_INFO'), 1);
            }
        }

        if (substr($url, -1, 1) != '/') {
            $url .= '/';
        }

        return $url;
    }
}