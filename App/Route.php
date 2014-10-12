<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\AppTools;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class Route
{
    public $id;
    public $enabled = true;
    public $matchUrl;
    public $permaUrl;

    public $methods;

    private $matchData;

//    public $callback;

    /** @var ArrayFilter */
    public $data;

    public $priority;

    public $registeredInApp;

    /** @var Plugin */
    public $plugin;

    /** @var Module */
    public $module;

    public $page;

    public function __construct($url, $data = null, $priority = 0)
    {
        $this->matchUrl = $this->permaUrl = $url;
        $this->priority = $priority;
        $this->data = new ArrayFilter($data);
    }

    public function init()
    {
        if ($this->page) {
            $this->setMatchUrl($this->matchUrl);
            $this->setPermaUrl($this->permaUrl);

//            $data = AppTools::decodeUri($this->matchUrl, $this->module->app, $this->module, $this->plugin);
//
//            $url = '';
//            if($data[1]){
//                $url .= $data[1]->urlPrefix;
//            }elseif($data[0]){
//                $url .= $data[0]->urlPrefix;
//            }
//            $matchUrl = $url.$data[2];
//
//            if($this->permaUrl === null || $this->permaUrl === $this->matchUrl){
//                $permaUrl = $matchUrl;
//            }else{
//                $data = AppTools::decodeUri($this->permaUrl, $this->module->app, $this->module, $this->plugin);
//
//                $url = '';
//                if($data[1]){
//                    $url .= $data[1]->urlPrefix;
//                }elseif($data[0]){
//                    $url .= $data[0]->urlPrefix;
//                }
//                $permaUrl = $url.$data[2];
//            }

//            if(substr($matchUrl, strlen($matchUrl) - 1, 1) !== '/'){
//                $matchUrl .= '/';
//            }

//            $this->matchUrl = $matchUrl;
//            $this->permaUrl = $permaUrl;
        }

        // TODO: Weg >>
//        $this->matchUrl = str_replace('prefix://', $this->module->urlPrefix, $this->matchUrl);
//        $this->permaUrl = str_replace('prefix://', $this->module->urlPrefix, $this->permaUrl);
//
//        if (is_array($this->data)) {
//            if (isset($this->data['_callback'])) {
//                $this->callback = $this->data['_callback'];
//                unset($this->data['_callback']);
//            }
//        }
    }

    public function setPermaUrl($url)
    {
        if ($url === null || $url === $this->matchUrl) {
            $this->permaUrl = $this->matchUrl;
            return;
        }

        $context = AppTools::decodeContext($url, $this->module->app, $this->module, $this->plugin);

        $this->permaUrl = '';

        if ($context->plugin) {
            // TODO: Plugins should also have urlPrefix
            $this->permaUrl .= $context->module->urlPrefix;

        } elseif ($context->module) {
            $this->permaUrl .= $context->module->urlPrefix;
        }

        $this->permaUrl .= $context->uri;
    }

    public function setMatchUrl($url)
    {
        if (preg_match('!^([a-zA-Z,]+)@!', $url, $urlData)) {
            $url = substr($url, strlen($urlData[0]));

            $this->methods = ArrayTools::convertToKeyArray(explode(',', strtoupper($urlData[1])));
        }

        $context = AppTools::decodeContext($url, $this->module->app, $this->module, $this->plugin);

        $this->matchUrl = '';

        if ($context->plugin) {
            // TODO: Plugins should also have urlPrefix
            $this->matchUrl .= $context->module->urlPrefix;

        } elseif ($context->module) {
            $this->matchUrl .= $context->module->urlPrefix;
        }

        $this->matchUrl .= $context->uri;
        $this->matchData = null;
    }

    public function getMatchData()
    {
        if (!$this->matchData) {
            $this->matchData = AppTools::decodePageUrlString($this->matchUrl);
        }

        return $this->matchData;
    }

    public function matches($url, $method = null)
    {
        if ($method && $this->methods && !isset($this->methods[$method])) {
            return array('matches' => false, 'vars' => null);
        }

        $data = $this->getMatchData();

        if ($data['eReg']) {
            if (preg_match($data['expression'], $url, $results)) {
                $pageVars = array();

                foreach ($data['mappings'] as $index => $key) {
                    $pageVars[$key] = $results[$index + 1];
                }

                return array('matches' => true, 'vars' => $pageVars);
            }

            return array('matches' => false, 'vars' => null);

        } elseif ($data['expression'] == $url) {
            return array('matches' => true, 'vars' => array());
        }

        return array('matches' => false, 'vars' => null);
    }

    public function getUrl($arguments = null, $absoluteURL = true, $parameters = null, $escapeArguments = true)
    {
        $url = $this->permaUrl;

        $url = AppTools::encodePageUrlString($url, $arguments, $escapeArguments);
        if ($parameters != null) {
            $url .= StringTools::getQueryString($parameters);
        }

        if ($absoluteURL) {
            $url = $this->module->app->url . $url;
        }

        return $url;
    }
}
