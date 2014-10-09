<?php
namespace Cyantree\Grout\Database\Entity\Fields;

use Cyantree\Grout\Database\Entity\EntityField;

class AutoIncrementField extends EntityField
{
    public $queryType = 'i';
    public $ignoreOnInsert = true;
}
