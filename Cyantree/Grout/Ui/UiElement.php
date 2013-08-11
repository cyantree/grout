<?php
namespace Cyantree\Grout\Ui;

use Cyantree\Grout\Tools\StringTools;

class UiElement
{
    public $tag;
    public $attributes;
    public $contents;

    public $escapeContent;
    public $quickClose;

    public $type;

    public function __construct($tag = null, $attributes = null, $contents = null, $escapeContent = false, $quickClose = true)
    {
        $this->tag = $tag;
        $this->attributes = $attributes;
        if ($contents && !is_array($contents)) $this->contents = array($contents);
        else {
            $this->contents = $contents;
        }
        $this->quickClose = $quickClose;
        $this->escapeContent = $escapeContent;
    }

    public function addClass($class)
    {
        if (isset($this->attributes['class'])) $this->attributes['class'] .= ' ' . $class;
        else $this->attributes['class'] = $class;
    }

    public function showOpen()
    {
        $cnt = '<' . $this->tag;

        if ($this->attributes) {
            foreach ($this->attributes as $atbName => $atbValue) {
                if ($atbValue !== null)
                    $cnt .= ' ' . $atbName . '="' . StringTools::escapeHtml($atbValue) . '"';
            }

        }

        return $cnt . '>';
    }

    public function showClose()
    {
        return '</' . $this->tag . '>';
    }

    public function getOpenClose()
    {
        return explode('[[__CONTENT__]]', $this->__toString(), 2);
    }

    public function __toString()
    {
        $cnt = '';

        if ($this->tag) {
            $cnt = '<' . $this->tag;

            if ($this->attributes) {
                foreach ($this->attributes as $atbName => $atbValue) {
                    if ($atbValue !== null)
                        $cnt .= ' ' . $atbName . '="' . StringTools::escapeHtml($atbValue) . '"';
                }
            }
        }

        if ($this->contents !== null) {
            if ($this->tag)
                $cnt .= '>';
            if (is_string($this->contents)) $cnt .= $this->escapeContent ? StringTools::escapeHtml($this->contents) : $this->contents;
            else if (is_array($this->contents)) {
                $c = '';
                foreach ($this->contents as $content)
                    $c .= $content;

                $cnt .= $this->escapeContent ? StringTools::escapeHtml($c) : $c;
            }

            if ($this->tag)
                $cnt .= '</' . $this->tag . '>';
        } else if ($this->tag) {
            if ($this->quickClose) $cnt .= ' />';
            else $cnt .= '></' . $this->tag . '>';
        }

        return $cnt;
    }
}