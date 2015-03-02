<?php
namespace Cyantree\Grout\App;

abstract class Bootstrap
{
    /** @var App */
    public $app;

    public $applicationPath = './';
    public $entryFilePath;

    public $assetDirectory = 'assets/';
    public $dataDirectory = 'data/';

    public function initApp()
    {
        $this->app->path = realpath($this->applicationPath) . '/';
        $this->app->dataPath = realpath($this->app->path . $this->dataDirectory) . '/';

        $this->app->publicPath = realpath(dirname($this->entryFilePath)) . '/';
        $this->app->publicAssetPath = realpath($this->app->publicPath . $this->assetDirectory) . '/';
    }

    abstract public function createRequest();
}
