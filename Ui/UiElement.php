<?php
namespace Cyantree\Grout\Ui;

use Cyantree\Grout\Tools\StringTools;

class UiElement
{
    public $tag;
    public $attributes;
    public $contents;

    public $escapeContent = false;
    public $quickClose = true;
    public $html5 = false;

    public $type;

    public $metadata;

    public function __construct(
        $tag = null,
        $attributes = null,
        $contents = null,
        $escapeContent = false,
        $quickClose = true,
        $metadata = array()
    ) {
        $this->tag = $tag;
        $this->attributes = $attributes;
        if ($contents && !is_array($contents)) {
            $this->contents = array($contents);

        } else {
            $this->contents = $contents;
        }
        $this->quickClose = $quickClose;
        $this->escapeContent = $escapeContent;
        $this->metadata = $metadata;
    }

    public static function html5($tag, $attributes = null, $contents = null, $escapeContent = true, $quickClose = true)
    {
        $e = new UiElement($tag, $attributes, $contents, $escapeContent, $quickClose);
        $e->html5 = true;

        return $e;
    }

    public function addClass($class)
    {
        if (isset($this->attributes['class'])) {
            $this->attributes['class'] .= ' ' . $class;

        } else {
            $this->attributes['class'] = $class;
        }
    }

    public function showOpen()
    {
        $cnt = '<' . $this->tag;

        if ($this->attributes) {
            foreach ($this->attributes as $atbName => $atbValue) {
                if ($atbValue !== null) {
                    $cnt .= ' ' . $atbName . '="' . StringTools::escapeHtml($atbValue) . '"';
                }
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
                    if ($atbValue === true) {
                        if ($this->html5) {
                            $cnt .= ' ' . $atbName;

                        } else {
                            $cnt .= ' ' . $atbName . '="' . $atbName . '"';
                        }

                    } elseif ($atbValue !== null) {
                        $cnt .= ' ' . $atbName . '="' . StringTools::escapeHtml($atbValue) . '"';
                    }
                }
            }
        }

        if ($this->contents !== null) {
            if ($this->tag) {
                $cnt .= '>';
            }
            if (is_string($this->contents)) {
                $cnt .= $this->escapeContent ? StringTools::escapeHtml($this->contents) : $this->contents;

            } elseif (is_array($this->contents)) {
                foreach ($this->contents as $content) {
                    if ($content instanceof UiElement) {
                        $cnt .= $content;

                    } else {
                        $cnt .= $this->escapeContent ? StringTools::escapeHtml($content) : $content;
                    }
                }
            }

            if ($this->tag) {
                $cnt .= '</' . $this->tag . '>';
            }

        } elseif ($this->tag) {
            if ($this->quickClose) {
                if ($this->html5) {
                    $cnt .= '>';

                } else {
                    $cnt .= ' />';
                }

            } else {
                $cnt .= '></' . $this->tag . '>';
            }
        }

        return $cnt;
    }
}
