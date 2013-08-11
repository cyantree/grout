<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class CustomContent extends Content
{
    public $content;
    public $contentAdd;
    public $contentEdit;
    public $contentDelete;
    public $contentList;

    public function __construct()
    {
        parent::__construct();

        $this->name = StringTools::random(32);
    }

    public function render($mode, $namespace = null)
    {
        if ($mode == Set::MODE_ADD) {
            if ($this->contentAdd !== null) {
                return $this->contentAdd;
            }
            return $this->content;
        }

        if ($mode == Set::MODE_EDIT) {
            if ($this->contentEdit !== null) {
                return $this->contentEdit;
            }
            return $this->content;
        }

        if ($mode == Set::MODE_DELETE) {
            if ($this->contentDelete !== null) {
                return $this->contentDelete;
            }
            return $this->content;
        }

        if ($mode == Set::MODE_LIST) {
            if ($this->contentList !== null) {
                return $this->contentList;
            }

            return $this->content;
        }

        return $this->content;
    }
}