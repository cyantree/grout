<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;

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

    function __construct()
    {
        $this->data = new ArrayFilter();
    }


    public function setRoute($route, $routeVars = null)
    {
        if(!$this->vars){
            $this->vars = new ArrayFilter();
        }

        $this->route = $route;
        $this->plugin = $route->plugin;
        $this->module = $route->module;

        if (is_array($route->data->data)) {
            if($routeVars === null){
                $routeVars = array();
            }
            $this->vars->setData(array_merge($routeVars, $route->data->data));
        }else{
            $this->vars->setData($routeVars);
        }
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

    /**
     * @param $route Route
     */
    public function redirectToRoute($route)
    {
        $this->app->redirectTaskToRoute($this, $route);
    }

    /**
     * @param $page Page
     */
    public function redirectToPage($page, $action = 'parseTask')
    {
        $this->app->redirectTaskToPage($this, $page, $action);
    }
}