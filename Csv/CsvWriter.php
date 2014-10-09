<?php
namespace Cyantree\Grout\Csv;

class CsvWriter
{
    private $csv;

    public $delimiter = ';';
    public $enclosure = '"';

    public $useUtf8Encoding = true;

    public function open($file = 'php://memory', $openMode = 'w')
    {
        $this->csv = fopen($file, $openMode);
    }

    public function append($fields)
    {
        if (!$this->useUtf8Encoding) {
            foreach ($fields as $key => $value) {
                $fields[$key] = utf8_decode($value);
            }
        }

        fputcsv($this->csv, $fields, $this->delimiter, $this->enclosure);
    }

    public function getContents()
    {
        $c = '';
        fseek($this->csv, 0);
        while (!feof($this->csv)) {
            $c .= fgets($this->csv, 5000);
        }

        return $c;
    }

    public function close()
    {
        fclose($this->csv);

        $this->csv = null;
    }
}
