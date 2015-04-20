<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\App\Types\Context;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\AppTools;

class Task
{
    public $id;

    public $url;

    /** @var ArrayFilter */
    public $vars;

    /** @var Route */
    public $route;

    /** @var App */
    public $app;

    /** @var Request */
    public $request;

    /** @var Response */
    public $response;

    /** @var Module */
    public $module;

    /** @var Plugin */
    public $plugin;

    /** @var Page */
    public $page;

    public $timeConstructed;

    /** @var ArrayFilter */
    public $data;

    public function __construct()
    {
        $this->data = new ArrayFilter();
        $this->vars = new ArrayFilter();
    }

    public function setRoute($route, $routeVars = null)
    {
        $this->route = $route;
        $this->plugin = $route->plugin;
        $this->module = $route->module;

        if (is_array($route->data->data)) {
            if ($routeVars === null) {
                $routeVars = array();
            }
            $this->vars->setData(array_merge($routeVars, $route->data->data));

        } else {
            $this->vars->setData($routeVars);
        }
    }

    public function setContext(Context $context)
    {
        $this->module = $context->module;
        $this->plugin = $context->plugin;
        $this->app = $context->app;
    }

    public function setPage($p)
    {
        $this->page = $p;
        $this->page->app = $this->app;
        $this->page->task = $this;
        $this->page->module = $this->module;
        $this->page->plugin = $this->plugin;
    }

    public function redirectToUrl($url)
    {
        $this->app->redirectTaskToUrl($this, $url);
    }

    public function redirectToAppUrl($url, $method = 'GET')
    {
        $this->app->redirectTaskToAppUrl($this, $url, $method);
    }

    /**
     * @param $route Route|string
     */
    public function redirectToRoute($route, $vars = null)
    {
        if (!($route instanceof Route)) {
            $data = $this->app->decodeContext($route, $this->module, $this->plugin);

            if ($data->plugin) {
                $route = $data->plugin->getRoute($data->uri);

            } elseif ($data->module) {
                $route = $data->module->getRoute($data->uri);
            }
        }

        $this->app->redirectTaskToRoute($this, $route, $vars);
    }

    /**
     * @param $page Page|string
     */
    public function redirectToPage($page, $action = 'parseTask')
    {
        if (!is_resource($page)) {
            $context = $this->app->decodeContext($page);
            $pageClass = $context->uri;

            if ($context->plugin) {
                $pageClass = $context->plugin->definition->namespace . $pageClass;

            } elseif ($context->module) {
                $pageClass = $context->module->definition->namespace . $pageClass;
            }

            $page = new $pageClass();

        } else {
            /** @var Page $page */
            $context = new Context('', $page->app, $page->module, $page->plugin);
        }

        /** @var $page Page */

        $this->app->redirectTaskToPage($this, $page, $action, $context);
    }
}
