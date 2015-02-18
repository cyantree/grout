<?php
namespace Cyantree\Grout\Bucket;

use Cyantree\Grout\Bucket\Bucket;
use Cyantree\Grout\Database\Database;
use Cyantree\Grout\DateTime\DateTime;
use Cyantree\Grout\Tools\DoctrineTools;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class DoctrineBucket extends Bucket
{
    /** @var Connection */
    public $connection = null;

    public $table = 'cwf_buckets';

    private $schema;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function createTable()
    {
        $schema = $this->getTableSchema();

        $queries = $schema->toSql($this->connection->getDatabasePlatform());

        foreach ($queries as $query) {
            $this->connection->prepare($query)->execute();
        }
    }

    public function dropTable()
    {
        $schema = $this->getTableSchema();

        $queries = $schema->toDropSql($this->connection->getDatabasePlatform());

        foreach ($queries as $query) {
            $this->connection->prepare($query)->execute();
        }
    }

    public function getTableSchema()
    {
        if ($this->schema) {
            return $this->schema;
        }

        $this->schema = new Schema();

        $table = $this->schema->createTable($this->table);
        $table->addOption('collate', 'utf8_general_ci');
        $table->addColumn('id', 'string', array('length' => 32));
        $table->addColumn('expiresOn', 'datetime');
        $table->addColumn('data', 'text');
        $table->addColumn('context', 'string', array('length' => 64));
        $table->setPrimaryKey(array('id'));

        return $this->schema;
    }

    public function save()
    {
        $this->expires = Bucket::mapExpirationDate($this->expires);

        $createNew = false;
        if ($this->id === null) {
            $this->id = $this->createBucketId();
            $createNew = true;
        }

        $data = base64_encode(serialize($this->data));

        DateTime::$utc->setTimestamp($this->expires);
        $expires = DateTime::$utc->copy();

        if ($createNew) {
            $st = DoctrineTools::prepareQuery(
                $this->connection,
                'INSERT INTO ' . $this->table . ' (id, expiresOn, data, context) VALUES (%t%, %d%, %t%, %t%)',
                array($this->id, $expires, $data, $this->context)
            );

        } else {
            $st = DoctrineTools::prepareQuery(
                $this->connection,
                'UPDATE ' . $this->table . ' SET data = %t%, expiresOn = %d%, context = %t% WHERE id = %t%',
                array($data, $expires, $this->context, $this->id)
            );
        }
        $st->execute();
    }

    public function delete()
    {
        DoctrineTools::prepareQuery(
            $this->connection,
            'DELETE FROM ' . $this->table . ' WHERE id = %t%',
            $this->id
        )->execute();

    }

    protected function createBucketId()
    {
        do {
            $id = Bucket::createId();
            $st = DoctrineTools::prepareQuery(
                $this->connection,
                'SELECT COUNT(*) c FROM ' . $this->table . ' WHERE id = %t%',
                array($id)
            );
            $st->execute();
            $exists = $st->fetchColumn() > 0;

        } while ($exists);

        return $id;
    }

    private function mergeSettings($base)
    {
        $this->connection = $base->connection;
        $this->table = $base->table;
    }

    public function cleanUp()
    {
        DoctrineTools::prepareQuery(
            $this->connection,
            'DELETE FROM ' . $this->table . ' WHERE expiresOn < %t%',
            array(DateTime::$utc->toSQLString(time()))
        )->execute();
    }

    public function create($data = '', $expires = null, $context = null, $id = null, $returnNewBucket = true)
    {
        if ($returnNewBucket) {
            $b = new DoctrineBucket($this->connection);
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
            $b->id = $this->createBucketId();
        }

        $data = base64_encode(serialize($b->data));

        DateTime::$utc->setTimestamp($b->expires);
        $expires = DateTime::$utc->copy();

        DoctrineTools::prepareQuery(
            $this->connection,
            'INSERT INTO ' . $this->table . ' (id, expiresOn, data, context) VALUES (%t%, %d%, %t%, %t%)',
            array($b->id, $expires, $data, $b->context)
        )->execute();

        return $b;
    }

    public function load($id, $context = null, $returnNewBucket = true, $checkExpiration = true)
    {
        if (!Bucket::isValidId($id)) {
            return false;
        }

        $data = DoctrineTools::prepareQuery(
            $this->connection,
            'SELECT expiresOn, data, context FROM ' . $this->table . ' WHERE id = %t%',
            array($id),
            Database::FILTER_ROW
        );
        $data->execute();

        $data = $data->fetch();

        if (!$data) {
            return false;
        }

        // >> Lower columns to circumvent database inconsistencies
        $d = $data;
        $data = array();
        foreach ($d as $k => $v) {
            $data[strtolower($k)] = $v;
        }


        $expires = DateTime::$utc->setBySqlString($data['expireson'])->getTimestamp();

        if ($checkExpiration && $expires < time()) {
            DoctrineTools::prepareQuery(
                $this->connection,
                'DELETE FROM ' . $this->table . ' WHERE id = %t%',
                $id
            )->execute();
            return false;
        }

        if ($context !== false && $data['context'] != $context) {
            return false;
        }

        if ($returnNewBucket) {
            $b = new DoctrineBucket($this->connection);
            $b->mergeSettings($this);

        } else {
            $b = $this;
        }

        $b->connection = $this->connection;
        $b->table = $this->table;
        $b->id = $id;
        $b->expires = $expires;
        $b->data = unserialize(base64_decode($data['data']));
        $b->context = $data['context'];

        return $b;
    }
}
