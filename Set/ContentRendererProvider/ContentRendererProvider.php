<?php
namespace Cyantree\Grout\Set\ContentRendererProvider;

use Cyantree\Grout\Set\Set;

abstract class ContentRendererProvider
{
    /** @var Set */
    public $set;

    abstract function getContentRenderer($contentClass);
}
