<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Event\Events;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\AppTools;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class Route
{
    public $id;
    public $enabled = true;
    private $matchUrl;
    private $matchUrlContextString;
    private $permaUrl;
    private $permaUrlContextString;

    public $methods;

    private $matchData;

    /** @var ArrayFilter */
    public $data;

    public $priority;

    public $registeredInApp;

    /** @var Plugin */
    public $plugin;

    /** @var Module */
    public $module;

    public $page;

    /** @var Events */
    public $events;

    public function __construct($url, $data = null, $priority = 0)
    {
        $this->matchUrlContextString = $this->permaUrlContextString = $url;
        $this->priority = $priority;
        $this->data = new ArrayFilter($data);
        $this->events = new Events();
    }

    public function getPermaUrl()
    {
        if ($this->permaUrl === null) {
            $this->permaUrl = $this->decodeUrlContextString($this->permaUrlContextString);
        }

        return $this->permaUrl;
    }

    public function getMatchUrl()
    {
        if ($this->matchUrl === null) {
            $this->matchUrl = $this->decodeUrlContextString($this->matchUrlContextString);
        }

        return $this->matchUrl;
    }

    public function setPermaUrl($url)
    {
        if ($url === null || $url === $this->matchUrlContextString) {
            $this->permaUrl = $this->matchUrl;
            $this->permaUrlContextString = $this->matchUrlContextString;

            return;
        }

        $this->permaUrl = null;
        $this->permaUrlContextString = $url;
    }

    private function decodeUrlContextString($contextString)
    {
        $url = '';

        $context = $this->module->app->decodeContext($contextString, $this->module, $this->plugin);

        if ($context->plugin) {
            // TODO: Plugins should also have urlPrefix
            $url .= $context->module->urlPrefix;

        } elseif ($context->module) {
            $url .= $context->module->urlPrefix;
        }

        $url .= $context->uri;

        return $url;
    }

    public function setMatchUrl($url)
    {
        if (preg_match('!^([a-zA-Z,]+)@!', $url, $urlData)) {
            $url = substr($url, strlen($urlData[0]));

            $this->methods = ArrayTools::convertToKeyArray(explode(',', strtoupper($urlData[1])));
        }

        $this->matchUrlContextString = $this->decodeUrlContextString($url);
        $this->matchUrl = null;
        $this->matchData = null;
    }

    public function getMatchData()
    {
        if ($this->matchData === null) {
            $this->matchData = AppTools::decodePageUrlString($this->getMatchUrl());
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

    public function getUrl($arguments = null, $absoluteUrl = true, $parameters = null, $escapeArguments = true)
    {
        $url = $this->getPermaUrl();

        $url = AppTools::encodePageUrlString($url, $arguments, $escapeArguments);
        if ($parameters != null) {
            $url .= StringTools::getQueryString($parameters);
        }

        if ($absoluteUrl) {
            $url = $this->module->app->url . $url;
        }

        return $url;
    }
}
