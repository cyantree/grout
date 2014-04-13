<?php
namespace Cyantree\Grout\Csv;

class CsvReader
{
    private $_file;
    private $_keys;

    public $delimiter = ';';
    public $enclosure = '"';

    public $usesUtf8Encoding = false;

    public $containsKeys = true;

    public function open($file)
    {
        $this->_file = fopen($file, 'r');
    }

    public function close()
    {
        fclose($this->_file);
        $this->_file = $this->_keys = null;
    }

    public function getRow()
    {
        $data = fgetcsv($this->_file, null, $this->delimiter, $this->enclosure);

        if ($data === null || $data === false) {
            return null;
        }

        if ($this->usesUtf8Encoding) {
            foreach ($data as $key => $value) {
                $data[$key] = utf8_encode($value);
            }
        }

        if ($this->containsKeys) {
            if ($this->_keys === null) {
                $this->_keys = $data;

                $data = $this->getRow();

                if (!$data) {
                    return null;
                }
            }

            return array_combine($this->_keys, $data);

        } else {
            return $data;
        }
    }

    public function getAllRows()
    {
        $rows = array();

        while($row = $this->getRow()) {
            $rows[] = $row;
        }

        return $rows;
    }
}