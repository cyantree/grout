<?php
namespace Cyantree\Grout\Tools;

class HtmlTools
{
    public static function createAttribute($name, $value, $show = true, $leadingSpace = true)
    {
        if (!$show) {
            return '';
        }

        $q = $leadingSpace ? ' ' : '';
        $q .= $name . '="' . StringTools::escapeHtml($value) . '"';

        return $q;
    }

    public static function createTag($tagName, $attributes = null, $content = null, $quickClose = false, $escapeContent = true)
    {
        $tag = '<' . $tagName;

        if ($attributes) {
            foreach (array_keys($attributes) as $atb) {
                $tag .= ' ' . $atb . '="' . StringTools::escapeHtml($attributes[$atb]) . '"';
            }
        }

        if (!$content && $quickClose) {
            $tag .= ' />';

            return $tag;
        }

        $tag .= '>';
        if ($content) {
            $tag .= $escapeContent ? StringTools::escapeHtml($content) : $content;
        }

        $tag .= '</' . $tagName . '>';

        return $tag;
    }

    public static function addDelayedForwarding($url, $delaySeconds, $echoScriptTag = true, $windowTarget = '')
    {
        if ($windowTarget) {
            $windowTarget .= '.';
        }
        $content = 'setTimeout(function(){window.' . $windowTarget . 'location.href="' . $url . '";}, ' . ($delaySeconds * 1000) . ');';
        if ($echoScriptTag) {
            return '<script type="text/javascript">' . $content . '</script>' . chr(10);
        }

        return $content;
    }
}
