<?php
namespace Cyantree\Grout\App\Service;

use Exception;

class ServiceResult
{
    public $id;
    public $success = null;
    public $data;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    public function addError($code, $message = null)
    {
        if ($this->success) {
            throw new Exception('Cannot add error on succeeded command.');
        }

        $this->success = false;

        if ($this->data == null) {
            $this->data = array();
        } else if (!is_array($this->data)) {
            throw new Exception('Cannot add error when data was set before.');
        }

        $this->data[] = array('code' => $code, 'message' => $message);
    }

    public function setSuccess($data = null)
    {
        $this->success = true;
        $this->data = $data;
    }

    public static function createWithSuccess($data = null, $id = null)
    {
        $r = new ServiceResult();
        $r->success = true;
        $r->data = $data;
        $r->id = $id;

        return $r;
    }

    public static function createWithError($code, $message = null, $id = null)
    {
        $r = new ServiceResult();
        $r->success = false;
        $r->data = array(array('code' => $code, 'message' => $message));
        $r->id = $id;

        return $r;
    }
}