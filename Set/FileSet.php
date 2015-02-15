<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\StringTools;

abstract class FileSet extends Set
{
    /** @var SetTypeCapabilities */
    public static $capabilities;

    private $saveData;
    private $id;

    protected function collectData()
    {
        foreach ($this->contents as $name => $content) {
            if ($content->storeInSet) {
                $this->saveData[$name] = $content->getValue();
            }
        }
    }

    abstract protected function getDirectory();

    protected function getExtension()
    {
        return '.set';
    }

    protected function getPath($id)
    {
        return $this->getDirectory() . $id . $this->getExtension();
    }

    public function idExists($id)
    {
        return is_file($this->getPath($id));
    }

    public function loadById($id)
    {
        if (!$this->idExists($id)) {
            return false;
        }
        $file = $this->getPath($id);

        $this->saveData = unserialize(file_get_contents($file));

        foreach ($this->contents as $name => $content) {
            if ($content->storeInSet) {
                $content->setValue($this->saveData[$name]);
            }
        }

        $this->setId($id);

        $this->onLoaded();

        return true;
    }

    public function getId()
    {
        return $this->id;
    }


    public function setId($id)
    {
        $this->id = $id;
    }


    protected function doDelete()
    {
        unlink($this->getPath($this->getId()));
    }

    protected function getNewId()
    {
        do {
            $id = StringTools::random(16, 'abcdefghijklmnopqrstuvwxyz0123456789');
        } while (is_file($this->getPath($id)));

        return $id;
    }

    protected function doSave()
    {
        if (!$this->getId()) {
            $this->setId($this->getNewId());
        }

        $path = $this->getPath($this->getId());

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, serialize($this->saveData));
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

        $directory = $this->getDirectory();

        if (is_dir($directory)) {
            $dir = opendir($directory);

            $offset = $options->get('offset', 0);
            $count = $options->get('count', 0);

            $extension = $this->getExtension();
            $extensionLength = strlen($extension);

            $i = 0;

            $files = array();
            while (($file = readdir($dir)) !== false) {
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
