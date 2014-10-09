<?php
namespace Cyantree\Grout\Tools;

class MailTools
{
    public static function encodeString($text)
    {
        return mb_encode_mimeheader($text, 'UTF-8', 'Q');
    }
}
