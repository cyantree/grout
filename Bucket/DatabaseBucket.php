<?php
namespace Cyantree\Grout\Bucket;

use Cyantree\Grout\Bucket\Bucket;
use Cyantree\Grout\Database\Database;
use Cyantree\Grout\Database\DatabaseConnection;
use Cyantree\Grout\DateTime\DateTime;

class DatabaseBucket extends Bucket
{
    /** @var DatabaseConnection */
    public $database = null;

    public $table = 'cwf_buckets';

    public function save()
    {
        $this->expires = Bucket::mapExpirationDate($this->expires);

        $createNew = false;
        if ($this->id === null) {
            $this->id = $this->_createBucketId();
            $createNew = true;
        }

        $data = base64_encode(serialize($this->data));

        $expires = DateTime::$utc->toSqlString($this->expires);

        if ($createNew) {
            $this->database->exec(
                'INSERT INTO ' . $this->table . ' (id, expires, data, context) VALUES (%t%, %t%, %t%, %t%)',
                array($this->id, $expires, $data, $this->context)
            );

        } else {
            $this->database->exec(
                'UPDATE ' . $this->table . ' SET data = %t%, expires = %t%, context = %t% WHERE id = %t%',
                array($data, $expires, $this->context, $this->id)
            );
        }
    }

    public function delete()
    {
        $this->database->exec('DELETE FROM ' . $this->table . ' WHERE id = %t%', array($this->id));
    }

    protected function _createBucketId()
    {
        do {
            $id = Bucket::createId();
            $exists = $this->database->query(
                'SELECT COUNT(*) c FROM ' . $this->table . ' WHERE id = %t%',
                array($id),
                Database::FILTER_FIELD
            );
        } while ($exists);

        return $id;
    }

    private function mergeSettings($base)
    {
        $this->database = $base->database;
        $this->table = $base->table;
    }

    private function checkDatabase()
    {
        if (!$this->database) {
            $this->database = Database::getDefault();
        }
    }

    public function cleanUp()
    {
        $this->checkDatabase();

        $this->database->exec(
            'DELETE FROM ' . $this->table . ' WHERE expires < %t%',
            array(DateTime::$utc->toSqlString(time()))
        );
    }

    public function create($data = '', $expires = null, $context = null, $id = null, $returnNewBucket = true)
    {
        $this->checkDatabase();

        if ($returnNewBucket) {
            $b = new DatabaseBucket();
            $b->mergeSettings($this);

        } else {
            $b = $this;
        }

        $b->data = $data;
        $b->context = $context;

        $b->expires = Bucket::mapExpirationDate($expires);

        if ($id) {
            $b->id = $id;

        } else {
            $b->id = $this->_createBucketId();
        }

        $data = base64_encode(serialize($b->data));

        $expires = DateTime::$utc->toSqlString($b->expires);
        $this->database->exec(
            'INSERT INTO ' . $this->table . ' (id, expires, data, context) VALUES (%t%, %t%, %t%, %t%)',
            array($b->id, $expires, $data, $b->context)
        );

        return $b;
    }

    public function load($id, $context = null, $returnNewBucket = true)
    {
        if (!Bucket::isValidId($id)) {
            return false;
        }

        $this->checkDatabase();

        $data = $this->database->query(
            'SELECT expires, data, context FROM ' . $this->table . ' WHERE id = %t% LIMIT 0, 1',
            array($id),
            Database::FILTER_ROW
        );
        if (!$data) {
            return false;
        }

        $expires = DateTime::$utc->setBySqlString($data['expires'])->getTimestamp();

        if ($expires < time()) {
            $this->database->exec('DELETE FROM ' . $this->table . ' WHERE id = %t%', array($id));
            return false;
        }

        if ($context !== false && $data['context'] != $context) {
            return false;
        }

        if ($returnNewBucket) {
            $b = new DatabaseBucket();
            $b->mergeSettings($this);

        } else {
            $b = $this;
        }

        $b->database = $this->database;
        $b->table = $this->table;
        $b->id = $id;
        $b->expires = $expires;
        $b->data = unserialize(base64_decode($data['data']));
        $b->context = $data['context'];

        return $b;
    }
}
