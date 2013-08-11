<?php
namespace Cyantree\Grout\Bucket;

class FileBucket extends Bucket
{
    public $directory;

    public function save()
    {
        if ($this->id === null) $this->id = $this->_createBucketId();
        $this->expires = Bucket::mapExpirationDate($this->expires);
        $file = $this->directory . $this->id . '.bck';

        $f = fopen($file, 'w');
        fwrite($f, serialize(array($this->context, $this->data)));
        fclose($f);

        touch($file, $this->expires);
    }

    public function delete()
    {
        if (file_exists($this->directory . $this->id . '.bck'))
            unlink($this->directory . $this->id . '.bck');
    }

    private function _mergeSettings($base)
    {
        $this->directory = $base->directory;
    }

    public function cleanUp()
    {
        $dir = opendir($this->directory);

        $t = time();

        while (($item = readdir($dir)) !== false) {
            if ($item != '.' && $item != '..' && preg_match('@\.bck$@', $item)) {
                if (filemtime($this->directory . $item) < $t)
                    unlink($this->directory . $item);
            }
        }
    }

    protected function _createBucketId()
    {
        do {
            $id = Bucket::createId();
            $exists = file_exists($this->directory . $id . '.bck');
        } while ($exists);

        return $id;
    }

    public function create($data = '', $expires = null, $context = null, $id = null, $returnNewBucket = true)
    {
        if ($returnNewBucket) {
            $b = new FileBucket();
            $b->_mergeSettings($this);
        } else $b = $this;

        $b->data = $data;
        $b->expires = Bucket::mapExpirationDate($expires);
        $b->context = $context;

        if ($id) $b->id = $id;
        else $b->id = $this->_createBucketId();

        $b->save();

        return $b;
    }

    public function load($id, $context = null, $returnNewBucket = true)
    {
        if (!Bucket::isValidId($id)) return false;

        $file = $this->directory . $id . '.bck';

        if (!file_exists($file)) return false;

        $mod = filemtime($file);

        if ($mod < time()) {
            unlink($file);
            return false;
        }

        $data = unserialize(file_get_contents($file));
        if ($context !== false && $data[0] != $context) return false;

        if ($returnNewBucket) {
            $b = new FileBucket();
            $b->_mergeSettings($this);
        } else $b = $this;

        $b->directory = $this->directory;
        $b->id = $id;
        $b->data = $data[1];
        $b->context = $data[0];
        $b->expires = $mod;

        return $b;
    }
}