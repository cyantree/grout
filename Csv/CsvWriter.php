<?php
namespace Cyantree\Grout\Csv;

class CsvWriter
{
    private $_csv;

    public $delimiter = ';';
    public $enclosure = '"';

    public $useUtf8Encoding = true;

    public function open($file = 'php://memory', $openMode = 'w')
    {
        $this->_csv = fopen($file, $openMode);
    }

    public function append($fields)
    {
        if(!$this->useUtf8Encoding){
            foreach($fields as $key => $value){
                $fields[$key] = utf8_decode($value);
            }
        }

        fputcsv($this->_csv, $fields, $this->delimiter, $this->enclosure);
    }

    public function getContents()
    {
        $c = '';
        fseek($this->_csv, 0);
        while (!feof($this->_csv)) {
            $c .= fgets($this->_csv, 5000);
        }

        return $c;
    }

    public function close()
    {
        fclose($this->_csv);

        $this->_csv = null;
    }
}