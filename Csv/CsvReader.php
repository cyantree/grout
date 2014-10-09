<?php
namespace Cyantree\Grout\Csv;

class CsvReader
{
    private $file;
    private $keys;

    public $delimiter = ';';
    public $enclosure = '"';

    public $usesUtf8Encoding = false;

    public $containsKeys = true;

    public function open($file)
    {
        $this->file = fopen($file, 'r');
    }

    public function close()
    {
        fclose($this->file);
        $this->file = $this->keys = null;
    }

    public function getRow()
    {
        $data = fgetcsv($this->file, null, $this->delimiter, $this->enclosure);

        if ($data === null || $data === false) {
            return null;
        }

        if ($this->usesUtf8Encoding) {
            foreach ($data as $key => $value) {
                $data[$key] = utf8_encode($value);
            }
        }

        if ($this->containsKeys) {
            if ($this->keys === null) {
                $this->keys = $data;

                $data = $this->getRow();

                if (!$data) {
                    return null;
                }
            }

            return array_combine($this->keys, $data);

        } else {
            return $data;
        }
    }

    public function getAllRows()
    {
        $rows = array();

        while ($row = $this->getRow()) {
            $rows[] = $row;
        }

        return $rows;
    }
}
