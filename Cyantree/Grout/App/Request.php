<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;

class Request
{
    /** @var string */
    public $url;

    public $method;

    /** @var ArrayFilter */
    public $get;

    /** @var ArrayFilter */
    public $post;

    /** @var ArrayFilter */
    public $files;

    /** @var ArrayFilter */
    public $server;

    /** @var ArrayFilter */
    public $cookies;

    /** @var ArrayFilter */
    public $urlParts;

    /** @var ArrayFilter */
    public $config;

    function __construct($url = null, ArrayFilter $get = null, ArrayFilter $post = null, ArrayFilter $config = null)
    {
        if($get === null){
            $get = new ArrayFilter();
        }

        if($post === null){
            $get = new ArrayFilter();
        }

        if($config === null){
            $config = new ArrayFilter();
        }

        $this->url = $url;
        $this->get = $get;
        $this->post = $post;
        $this->config = $config;

        $this->files = new ArrayFilter();
        $this->server = new ArrayFilter();
        $this->cookies = new ArrayFilter();
        $this->urlParts = new ArrayFilter();
    }

    public function prepare()
    {
        // Normalize URL and create URL parts
        if (substr($this->url, 0, 1) == '/') {
            $this->url = substr($this->url, 1);
        }

        $len = strlen($this->url);
        if ($len && substr($this->url, $len - 1, 1) != '/') {
            $this->url .= '/';
        }

        $urlParts = explode('/', $this->url);
        array_pop($urlParts);

//        if (!count($urlParts)) {
//            $urlParts[] = '';
//        }

        $this->urlParts = new ArrayFilter($urlParts);
    }
}