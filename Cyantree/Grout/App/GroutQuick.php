<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\ErrorWrapper\PhpNoticeException;
use Cyantree\Grout\Quick;
use Cyantree\Grout\Tools\AppTools;

class GroutQuick extends Quick
{
    /** @var App */
    protected $_app;

    public $publicAssetUrl;

    public function __construct($app)
    {
        $this->_app = $app;

        parent::__construct();
    }

    public function er($uri, $arguments = null, $parameters = null, $escapeContext = 'html')
    {
        return $this->e($this->p($uri, $arguments, $parameters), $escapeContext);
    }

    public function ea($uri, $parameters = null, $escapeContext = 'html')
    {
        return $this->e($this->a($uri, $parameters), $escapeContext);
    }

    /** @deprecated */
    public function p($uri, $arguments = null, $parameters = null)
    {
        $this->_app->events->trigger('logException', new PhpNoticeException('GroutQuick->p() is deprecated. Use GroutQuick->r() instead.'));

        return $this->r($uri, $arguments, $parameters);
    }

    public function r($uri, $arguments = null, $parameters = null)
    {
        $data = AppTools::decodeUri($uri, $this->_app, $this->_app->currentTask->module, $this->_app->currentTask->plugin);
        if($data[0]){
            /** @var Module $m */
            $m = $data[0];
            return $m->getRouteUrl($data[2], $arguments, true, $parameters);

        }elseif($data[1]){
            /** @var Plugin $p */
            $p = $data[1];
            return $p->getRouteUrl($data[2], $arguments, true, $parameters);
        }
        return null;
    }

    public function a($uri, $parameters = null)
    {
        if($this->publicAssetUrl !== null && strpos($uri, ':') === false){
            return $this->publicAssetUrl . $uri;
        }else{
            $data = AppTools::decodeUri($uri, $this->_app, $this->_app->currentTask->module, $this->_app->currentTask->plugin);
            if($data[0]){
                /** @var Module $m */
                $m = $data[0];
                return $m->getPublicUrl($data[2], true, $parameters);
            }elseif($data[1]){
                /** @var Plugin $p */
                $p = $data[1];
                return $p->getPublicUrl($data[2], true, $parameters);
            }
            return null;
        }
    }
}