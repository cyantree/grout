<?php
namespace Cyantree\Grout;

use Cyantree\Grout\Ui\Ui;

class StatusContainer
{
    public $error = false;
    public $errors = array();
    public $hasErrorMessages = false;

    public $success = false;
    public $successMessages = array();
    public $hasSuccessMessages = false;

    public $info = false;
    public $infoMessages = array();
    public $hasInfoMessages = false;

    public function addError($id, $message = null)
    {
        if($id){
            $this->errors[$id] = $message;
        }else{
            $this->errors[] = $message;
        }

        $this->error = true;
        if ($message != null) {
            $this->hasErrorMessages = true;
        }
    }

    public function addErrors($errors)
    {
        foreach ($errors as $code => $message) {
            $this->addError($code, $message);
        }
    }

    public function addInfos($infos)
    {
        foreach ($infos as $code => $message) {
            $this->addInfo($code, $message);
        }
    }

    public function addSuccesses($successes)
    {
        foreach ($successes as $code => $message) {
            $this->addSuccess($code, $message);
        }
    }

    public function addInfo($id, $message = null)
    {
        if($id){
            $this->infoMessages[$id] = $message;
        }else{
            $this->infoMessages[] = $message;
        }

        $this->info = true;
        if ($message != null) {
            $this->hasInfoMessages = true;
        }
    }

    public function addSuccess($id, $message = null)
    {
        if($id){
            $this->successMessages[$id] = $message;
        }else{
            $this->successMessages[] = $message;
        }

        $this->success = true;
        if ($message != null) {
            $this->hasSuccessMessages = true;
        }
    }

    public function hasError($id)
    {
        if (is_array($id)) {
            foreach ($id as $i) {
                if (isset($this->errors[$i])) {
                    return true;
                }
            }

            return false;
        }
        return isset($this->errors[$id]);
    }

    public function hasSuccessMessage($id)
    {
        if (is_array($id)) {
            foreach ($id as $i) {
                if (isset($this->successMessages[$i])) {
                    return true;
                }
            }

            return false;
        }
        return isset($this->successMessages[$id]);
    }

    public function hasInfoMessage($id)
    {
        if (is_array($id)) {
            foreach ($id as $i) {
                if (isset($this->infoMessages[$i])) {
                    return true;
                }
            }

            return false;
        }
        return isset($this->infoMessages[$id]);
    }

    public function reset()
    {
        $this->error = $this->success = $this->info = false;
        $this->hasErrorMessages = $this->hasInfoMessages = $this->hasSuccessMessages = false;
        $this->errors = array();
        $this->successMessages = array();
        $this->infoMessages = array();
    }
}