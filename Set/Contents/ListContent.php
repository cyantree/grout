<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\ListContentRenderer;

class ListContent extends Content
{
    public $options = array();

    protected function getDefaultErrorMessage($code)
    {
        static $errors = null;

        if ($errors === null) {
            $errors = new ArrayFilter(array(
                    'invalid' => _('Im Feld „%name%“ wurde keine Option gewählt.')
            ));
        }

        return $errors->get($code);
    }

    public function check()
    {
        if (!isset($this->options[$this->data])) {
            $this->postError('invalid');
        }
    }

    protected function getDefaultRenderer()
    {
        return new ListContentRenderer();
    }
}
