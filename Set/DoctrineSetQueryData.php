<?php
namespace Cyantree\Grout\Set;

class DoctrineSetQueryData
{
    public $selectClause = '{e}';
    public $fromClause = '{entity}';
    public $whereClause = '{where}';
    public $orderClause = '{order}';
    public $searchClauses = array();

    public $selectQuery = 'SELECT {selectClause} FROM {fromClause} WHERE {whereClause} ORDER BY {orderClause}';

    public $defaultOrder;

    public $parameters = array();
}
