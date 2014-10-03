<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\App\App;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class FileSet extends Set
{
    public $setId;

    /** @var SetTypeCapabilities */
    public static $capabilities;

    private $_saveData;
    private $_id;

    protected function _collectData()
    {
        foreach($this->contents as $name => $content)
        {
            if($content->storeInSet){
                $this->_saveData[$name] = $content->getData();
            }
        }
    }

    abstract protected function _getDirectory();

    protected function _getExtension()
    {
        return '.set';
    }

    protected function _getPath($id)
    {
        return $this->_getDirectory() . $id . $this->_getExtension();
    }

    public function idExists($id)
    {
        return is_file($this->_getPath($id));
    }

    public function loadById($id)
    {
        if (!$this->idExists($id)) {
            return false;
        }
        $file = $this->_getPath($id);

        $this->_saveData = unserialize(file_get_contents($file));

        foreach($this->contents as $name => $content)
        {
            if($content->storeInSet){
                $content->setData($this->_saveData[$name]);
            }
        }

        $this->setId($id);

        $this->_onLoaded();

        return true;
    }

    public function getId()
    {
        return $this->_id;
    }


    public function setId($id)
    {
        $this->_id = $id;
    }


    protected function _doDelete()
    {
        unlink($this->_getPath($this->getId()));
    }

    protected function _getNewId()
    {
        do {
            $id = StringTools::random(16, 'abcdefghijklmnopqrstuvwxyz0123456789');
        } while (is_file($this->_getPath($id)));

        return $id;
    }

    protected function _doSave()
    {
        if (!$this->getId()) {
            $this->setId($this->_getNewId());
        }

        $path = $this->_getPath($this->getId());

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, serialize($this->_saveData));
    }

    public function listSets($options)
    {
        /*
        $options = array(
            'offset' => 0,
            'count' => '20',
            'sort' => array(
                'field' => 'id',
                'direction' => 'desc'
            ),
            'search' => 'testsuche'
        );
        */

        $options = new ArrayFilter($options);

        $directory = $this->_getDirectory();

        if (is_dir($directory)) {
            $dir = opendir($directory);

            $offset = $options->get('offset', 0);
            $count = $options->get('count', 0);

            $extension = $this->_getExtension();
            $extensionLength = strlen($extension);

            $i = 0;

            $files = array();
            while(($file = readdir($dir)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $fileLength = strlen($file);

                if (substr($file, $fileLength - $extensionLength, $extensionLength) !== $extension) {
                    continue;
                }

                if ($i >= $offset && $i < ($offset + $count)) {
                    $files[] = substr($file, 0, $fileLength - $extensionLength);
                }
                $i++;
            }

            $result = new FileSetListResult($this, $files);
            $result->countAll = $i;

            return $result;
        }

        $result = new FileSetListResult($this, array());
        $result->countAll = 0;

        return $result;
    }

    public function getCapabilities()
    {
        if (!FileSet::$capabilities) {
            $c = FileSet::$capabilities = new SetTypeCapabilities();
            $c->pagination = true;
        }

        return self::$capabilities;
    }
}