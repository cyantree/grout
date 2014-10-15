<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\InternalContentRenderer;

class InternalContent extends Content
{
    public $content;

    public function __construct()
    {
        parent::__construct();

        $this->config->set('visible', false);
    }

    public function setData($data)
    {
        $this->content = $data;
    }

    public function getData()
    {
        return $this->content;
    }

    protected function getDefaultRenderer()
    {
        return new InternalContentRenderer();
    }
}
