<?php
namespace Cyantree\Grout\Form;

use Cyantree\Grout\Ui\Ui;

class FormStatus
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

    public function postError($id, $message = null)
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

    public function postInfo($id, $message = null)
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

    public function postSuccess($id, $message = null)
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

    /** @param $v Validator */
    public function fromValidator($v, $mergeFieldErrors = true)
    {
        $this->success = $v->success;
        $this->error = !$v->success;

        $results = $v->errors;
        foreach($results as $field => $errors){
            if($mergeFieldErrors){
                $this->postError($field, implode(' ', $errors));
            }else{
                $this->postError($field);
                foreach($errors as $code => $message){
                    $this->postError($field.'.'.$code, $message);
                }
            }
        }
    }
}