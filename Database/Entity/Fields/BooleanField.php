<?php
namespace Cyantree\Grout\Database\Entity\Fields;

use Cyantree\Grout\Database\Entity\EntityField;

class BooleanField extends EntityField
{
    public $queryType = 'b';

    public function decodeFromQuery($value)
    {
        return $value ? true : false;
    }
}