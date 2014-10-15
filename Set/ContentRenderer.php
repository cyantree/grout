<?php
namespace Cyantree\Grout\Set;

abstract class ContentRenderer
{
    abstract public function render(Content $content, $mode);
}
