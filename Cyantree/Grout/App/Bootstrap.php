<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;

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

    protected $_frameworkPath;

    protected $_usesModRewrite;

    public function __construct(App $app)
    {
        $this->app = $app;

        $this->_get = new ArrayFilter($_GET);
        $this->_post = new ArrayFilter($_POST);
        $this->_server = new ArrayFilter($_SERVER);
        $this->_files = new ArrayFilter($_FILES);
        $this->_cookies = new ArrayFilter($_COOKIE);
    }

    public function init($frameworkPath, $usesModRewrite)
    {
        $this->_usesModRewrite = $usesModRewrite;
        $this->_frameworkPath = $frameworkPath;

        $this->_setBasePaths();
        $this->_setBaseUrls();

        $r = new Request($this->_retrieveUrl(), $this->_get, $this->_post);
        $r->files = $this->_files;
        $r->cookies = $this->_cookies;
        $r->server = $this->_server;
        $r->method = strtoupper($this->_server->get('REQUEST_METHOD'));

        return $r;
    }

    protected function _setBaseUrls()
    {
        if ($this->_server->get('HTTPS') == 'on') {
            $this->app->url = 'https://' . $this->_server->needs('SERVER_NAME');
        } else {
            $this->app->url = 'http://' . $this->_server->needs('SERVER_NAME');
            if ($this->_server->get('SERVER_PORT') != 80){
                $this->app->url .= ':' . $this->_server->get('SERVER_PORT');
            }
        }

        $scriptName = substr($this->_server->get('SCRIPT_FILENAME'), strlen($this->_server->get('DOCUMENT_ROOT')));

        if ($this->_usesModRewrite) {
            $this->app->url .= $this->_server->get('SCRIPT_NAME');
            $this->app->publicUrl = $this->app->url = substr($this->app->url, 0, strrpos($this->app->url, '/') + 1);

        } else {
            $this->app->publicUrl = $this->app->url.substr($scriptName, 0, strlen($scriptName) - strlen(basename($scriptName)));
            $this->app->url .= $scriptName.'/';
        }
    }

    protected function _setBasePaths()
    {
        // Set server base paths
        $this->app->path = str_replace('\\', '/', realpath($this->_frameworkPath)).'/';
        $this->app->publicPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])).'/';
    }

    protected function _retrieveUrl()
    {
        if($this->_usesModRewrite){
            $self = $this->_server->needs('PHP_SELF');

            if(($pathInfo = $this->_server->get('PATH_INFO')) || ($pathInfo = $this->_server->get('ORIG_PATH_INFO'))){
                $self = substr($self, 0, strlen($self) - strlen($pathInfo));
            }

            if(($pos = strrpos($self, '/')) !== false){
                $self = substr($self, 0, $pos);
            }else{
                $self = substr($self, 0, strrpos($self, '\\'));
            }
            $url = substr($this->_server->needs('REQUEST_URI'), strlen($self));
            if($url === false){
                $url = '';
            }else if($url !== ''){
                $posQueryString = strpos($url, '?');
                if($posQueryString !== false){
                    $url = substr($url, 0, $posQueryString);
                }
            }

            return $url;
        }else{
            if($this->_server->has('PATH_INFO')){
                return substr($this->_server->get('PATH_INFO'), 1);
            }else{
                return substr($this->_server->get('ORIG_PATH_INFO'), 1);
            }
        }
    }
}