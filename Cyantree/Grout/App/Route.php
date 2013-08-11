<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\AppTools;
use Cyantree\Grout\Tools\StringTools;

class Route
{
    public $id;
    public $enabled = true;
    public $matchUrl;
    public $permaUrl;

    private $_matchData;

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

    function __construct($url, $data = null, $priority = 0)
    {
        $this->matchUrl = $this->permaUrl = $url;
        $this->priority = $priority;
        $this->data = new ArrayFilter($data);
    }

    public function init()
    {
        if($this->page){
            $data = AppTools::decodeUri($this->matchUrl, $this->module->app, $this->module, $this->plugin);

            $url = '';
            if($data[1]){
                $url .= $data[1]->urlPrefix;
            }elseif($data[0]){
                $url .= $data[0]->urlPrefix;
            }
            $matchUrl = $url.$data[2];

            if($this->permaUrl === null || $this->permaUrl === $this->matchUrl){
                $permaUrl = $matchUrl;
            }else{
                $data = AppTools::decodeUri($this->permaUrl, $this->module->app, $this->module, $this->plugin);

                $url = '';
                if($data[1]){
                    $url .= $data[1]->urlPrefix;
                }elseif($data[0]){
                    $url .= $data[0]->urlPrefix;
                }
                $permaUrl = $url.$data[2];
            }

//            if(substr($matchUrl, strlen($matchUrl) - 1, 1) !== '/'){
//                $matchUrl .= '/';
//            }

            $this->matchUrl = $matchUrl;
            $this->permaUrl = $permaUrl;
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
        $data = AppTools::decodeUri($url, $this->module->app, $this->module, $this->plugin);

        $url = '';
        if($data[1]){
            $url .= $data[1]->urlPrefix;
        }elseif($data[0]){
            $url .= $data[0]->urlPrefix;
        }
        $this->permaUrl = $url.$data[2];
    }

    public function setMatchUrl($url)
    {
        $data = AppTools::decodeUri($url, $this->module->app, $this->module, $this->plugin);

        $url = '';
        if($data[1]){
            $url .= $data[1]->urlPrefix;
        }elseif($data[0]){
            $url .= $data[0]->urlPrefix;
        }
        $this->matchUrl = $url.$data[2];
        $this->_matchData = null;
    }

    public function getMatchData()
    {
        if(!$this->_matchData){
            $this->_matchData = AppTools::decodePageUrlString($this->matchUrl);
        }

        return $this->_matchData;
    }

    public function matches($url)
    {
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
        } else if ($data['expression'] == $url) {
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

        if ($absoluteURL) $url = $this->module->app->url . $url;

        return $url;
    }
}