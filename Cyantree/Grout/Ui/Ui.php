<?php
namespace Cyantree\Grout\Ui;

use Cyantree\Grout\Form\FormStatus;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class Ui
{
    public static $count = 0;

    /** @var UiConfig */
    public $config;

    /** @var array */
    public $currentForm;

    /** @param $config UiConfig */
    public function __construct($config = null)
    {
        if (!$config) {
            $this->config = new UiConfig();
        } else {
            $this->config = $config;
        }
    }

    public function label($text, $element = null, $parameters = null)
    {
        if (is_string($element)) {
            $elementContent = $element;
            $element = null;
        } else {
            $elementContent = null;
        }

        $escape = ArrayTools::get($parameters, 'escape', true);

        $el = new UiElement('label', array('class' => 'CT_Label'), $text, $escape);
        $el->type = 'Label';

        if ($element) {
            if (isset($element->attributes['id'])) {
                $labelFor = $element->attributes['id'];
            } else {
                $labelFor = 'CT_UiElement_' . (++Ui::$count);
                $element->attributes['id'] = $labelFor;
            }

            $el->attributes['for'] = $labelFor;
            if ($element->type) {
                $el->addClass('CT_' . $element->type . 'Label');
            }
        }

        $this->mapGenericAttributesToElement($el, $parameters);

        $this->errorClass('CT_Label', $el, $parameters);

        $isCheckboxOrRadioButton = $element && $element->tag == 'input' && ($element->attributes['type'] == 'checkbox' || $element->attributes['type'] == 'radio');

        if ($isCheckboxOrRadioButton) {
            return new UiElement(null, null, array('element' => $element ? $element : $elementContent, 'label' => $el));
        } else {
            return new UiElement(null, null, array('label' => $el, 'element' => $element ? $element : $elementContent));
        }
    }

    public function image($source, $alt = '', $parameters = null)
    {
        $attributes = array('src' => $source, 'alt' => $alt);

        $element = new UiElement('img', $attributes);

        $this->mapGenericAttributesToElement($element, $parameters);

        return $element;
    }

    public function link($url, $title = null, $target = null, $parameters = null)
    {
        if ($title === null) {
            $title = $url;
        }

        $attributes = array('href' => $url);
        if ($target) {
            $attributes['target'] = $target;
        }

        $escapeTitle = ArrayTools::get($parameters, 'escapeTitle', true) == 1;

        $element = new UiElement('a', $attributes, $title, $escapeTitle);

        $this->mapGenericAttributesToElement($element, $parameters);

        return $element;
    }

    public function form($action = null, $method = 'post', $parameters = null)
    {
        if (!$action) {
            $action = '.';
        }

        $el = new UiElement('form', array('action' => $action, 'method' => $method, 'class' => 'CT_Form'));
        $el->type = 'Form';
        $container = new UiElement('div', array('class' => 'CT_Form'), '[[__CONTENT__]]');
        $el->contents = array($container);

        if ($method == 'file') {
            $el->attributes['method'] = 'post';
            $el->attributes['enctype'] = 'multipart/form-data';
        }

        if ($parameters) {
            if (isset($parameters['containerClass'])) {
                $container->attributes['class'] = $parameters['containerClass'];
            }

            $this->mapGenericAttributesToElement($el, $parameters);

            if (isset($parameters['content'])) {
                $container->contents = $parameters['content'];
            }
        }

        return $el;
    }

    public function formStart($action = null, $method = 'post', $parameters = null)
    {
        $this->currentForm = $this->form($action, $method, $parameters)->getOpenClose();

        return $this->currentForm[0];
    }

    public function formEnd()
    {
        $f = $this->currentForm[1];
        $this->currentForm = null;

        return $f;
    }

    public function checkbox($name, $value = 1, $checked = false, $parameters = array())
    {
        $el = new UiElement('input', array('class' => 'CT_Checkbox', 'type' => 'checkbox'));
        $el->type = 'Checkbox';

        $this->errorClass('CT_Checkbox', $el, $parameters);

        if ($checked !== false && ($checked === true || (is_array($checked) && array_key_exists($value, $checked)) || (!is_array($checked) && strval($checked) === strval($value)))) {
            $el->attributes['checked'] = 'checked';
        }
        if ($name) {
            $el->attributes['name'] = $name;
        }
        $el->attributes['value'] = $value;

        if (!ArrayTools::get($parameters, 'hoverSkin') && !ArrayTools::get($parameters, 'clickSkin')) {
            if ($this->config->checkboxClickSkin) {
                $parameters['clickSkin'] = $this->config->checkboxClickSkin;
            } else if ($this->config->checkboxHoverSkin) {
                $parameters['hoverSkin'] = $this->config->checkboxHoverSkin;
            }
        }

        $this->mapGenericAttributesToElement($el, $parameters);

        return $el;
    }

    public function selectOption($value, $label, $selected = false, $parameters = array())
    {
        $el = new UiElement('option', array('value' => $value, 'class' => 'CT_SelectOption'), $label);
        $el->type = 'SelectOption';

        if ($selected === true || strval($selected) === strval($value) || (is_array($selected) && in_array($value, $selected))) {
            $el->attributes['selected'] = 'selected';
        }

        $this->mapGenericAttributesToElement($el, $parameters);

        return $el;
    }

    public function submitButton($name, $value = null, $parameters = null)
    {
        if (!$parameters) {
            $parameters = array();
        }
        $parameters['type'] = 'submit';

        $el = $this->button($name, $value, $parameters);
        $el->type = 'SubmitButton';

        return $el;
    }

    public function button($name, $value = null, $parameters = null)
    {
        $el = new UiElement('input', array('type' => 'button', 'class' => 'CT_Button'));
        $el->type = 'Button';

        if ($parameters) {
            if (!ArrayTools::get($parameters, 'hoverSkin') && !ArrayTools::get($parameters, 'clickSkin')) {
                if ($this->config->buttonClickSkin) {
                    $parameters['clickSkin'] = $this->config->buttonClickSkin;
                } else if ($this->config->buttonHoverSkin) {
                    $parameters['hoverSkin'] = $this->config->buttonHoverSkin;
                }
            }

            if (isset($parameters['url'])) {
                $parameters['onclick'] = 'CT_Tools.submitForm(this, "' . StringTools::escapeHtml($name) . '", "' . StringTools::escapeHtml($parameters['url']) . '")';
            }

            if (isset($parameters['type']) && $parameters['type'] == 'submit') {
                $el->attributes['type'] = 'submit';
                $el->type = 'SubmitButton';
                $el->attributes['class'] = 'CT_SubmitButton';
            }
            $this->mapGenericAttributesToElement($el, $parameters);
        }

        $skinned = ArrayTools::get($parameters, 'hoverSkin') || ArrayTools::get($parameters, 'clickSkin');

        if ($value && $this->config->buttonNoValueIfSkinned && $skinned) {
            $el->attributes['title'] = $value;
            $value = '';
        }

        if ($name) {
            $el->attributes['name'] = $name;
        }
        $el->attributes['value'] = $value;

        return $el;
    }

    public function select($name, $options, $value = null, $parameters = null)
    {
        $value = strval($value);
        if (!current($options) instanceof UiElement) {
            $newOptions = array();
            foreach ($options as $k => $v) {
                $newOptions[] = self::selectOption($k, $v, $value);
            }
            $options = $newOptions;
        } else {
            /** @var $o UiElement */
            foreach ($options as $o) {
                if ($o->attributes['value'] === true || $value === strval($o->attributes['value'])) {
                    $o->attributes['selected'] = 'selected';
                    break;
                }
            }
        }

        $el = new UiElement('select', array('class' => 'CT_Select'), $options);
        $el->type = 'Select';

        $this->errorClass('CT_Select', $el, $parameters);

        if ($name) {
            $el->attributes['name'] = $name;
        }

        if ($parameters) {
            $this->mapGenericAttributesToElement($el, $parameters);
            if (isset($parameters['onchange'])) {
                $el->attributes['onchange'] = $parameters['onchange'];
            }
            if (isset($parameters['size'])) {
                $el->attributes['size'] = $parameters['size'];
            }
            if (isset($parameters['multiple']) && $parameters['multiple']) {
                $el->attributes['multiple'] = 'multiple';
            }
        }

        return $el;
    }

    public function radioButton($name, $value = 1, $selected = false, $parameters = array())
    {
        $el = new UiElement('input', array('class' => 'CT_RadioButton', 'type' => 'radio'));
        $el->type = 'RadioButton';

        $this->errorClass('CT_RadioButton', $el, $parameters);

        if ($selected === true || strval($selected) === strval($selected)) {
            $el->attributes['checked'] = 'checked';
        }
        if ($name) {
            $el->attributes['name'] = $name;
        }
        $el->attributes['value'] = $value;

        if (!ArrayTools::get($parameters, 'hoverSkin') && !ArrayTools::get($parameters, 'clickSkin')) {
            if ($this->config->radioButtonClickSkin) {
                $parameters['clickSkin'] = $this->config->radioButtonClickSkin;
            } else if ($this->config->radioButtonHoverSkin) {
                $parameters['hoverSkin'] = $this->config->radioButtonHoverSkin;
            }
        }

        $this->mapGenericAttributesToElement($el, $parameters);

        return $el;
    }

    public function textInput($name, $value = '', $maxLength = 128, $parameters = null)
    {
        $el = new UiElement('input', array('class' => 'CT_TextInput', 'type' => 'text'));
        $el->type = 'TextInput';

        $type = 'CT_TextInput';

        if ($name) {
            $el->attributes['name'] = $name;
        }
        if ($value != '') {
            $el->attributes['value'] = $value;
        }
        if ($maxLength > 0) {
            $el->attributes['maxlength'] = $maxLength;
        }

        if ($parameters) {
            if (isset($parameters['type'])) {
                $el->attributes['type'] = $parameters['type'];
                if ($parameters['type'] == 'password') {
                    $el->type = 'PasswordInput';
                    $type = $el->attributes['class'] = 'CT_PasswordInput';
                } else if ($parameters['type'] == 'file') {
                    $el->type = 'FileInput';
                    $type = $el->attributes['class'] = 'CT_FileInput';
                }
            }
            if (isset($parameters['accept'])) {
                $el->attributes['accept'] = $parameters['accept'];
            }

            if (ArrayTools::get($parameters, 'readOnly')) {
                $el->attributes['readonly'] = 'readonly';
            }

            $this->mapGenericAttributesToElement($el, $parameters);
        }

        $this->errorClass($type, $el, $parameters);

        return $el;
    }

    public function passwordInput($name, $maxLength = 128, $parameters = null)
    {
        if ($parameters == null) {
            $parameters = array();
        }
        $parameters['type'] = 'password';

        $el = $this->textInput($name, null, $maxLength, $parameters);
        $el->type = 'PasswordInput';

        return $el;
    }

    public function hiddenInput($name, $value = '', $parameters = null)
    {
        $el = new UiElement('input', array('type' => 'hidden'));
        $el->type = 'HiddenInput';
        if ($name) {
            $el->attributes['name'] = $name;
        }
        if ($value) {
            $el->attributes['value'] = $value;
        }

        if ($parameters) {
            $this->mapGenericAttributesToElement($el, $parameters);
        }

        return $el;
    }

    public function fileInput($name, $accept = null, $parameters = null)
    {
        if ($parameters == null) {
            $parameters = array();
        }
        $parameters['type'] = 'file';
        if ($accept) {
            $parameters['accept'] = $accept;
        }

        $el = $this->textInput($name, null, 0, $parameters);
        $el->type = 'FileInput';

        return $el;
    }

    public function textArea($name, $value = '', $parameters = null)
    {
        $el = new UiElement('textarea', array('class' => 'CT_TextArea', 'cols' => '30', 'rows' => '5'), StringTools::escapeHtml($value));
        $el->type = 'TextArea';

        $this->errorClass('CT_TextArea', $el, $parameters);

        if ($name) {
            $el->attributes['name'] = $name;
        }

        if ($parameters) {
            $this->mapGenericAttributesToElement($el, $parameters);

            $rows = ArrayTools::get($parameters, 'rows');
            $cols = ArrayTools::get($parameters, 'cols');
            if ($rows) {
                $el->attributes['rows'] = $rows;
            }
            if ($cols) {
                $el->attributes['cols'] = $cols;
            }
        }

        return $el;
    }

    public function statusInfo($text, $parameters = null)
    {
        return $this->_addStatus($text, 'CT_FormStatus_Info');
    }

    public function statusSuccess($text, $parameters = null)
    {
        return $this->_addStatus($text, 'CT_FormStatus_Success');
    }

    public function statusError($text, $parameters = null)
    {
        return $this->_addStatus($text, 'CT_FormStatus_Error');
    }

    /** @param FormStatus $status */
    public function status($status, $showIfEmpty = false){
        if(!$showIfEmpty && !$status->success && !$status->error && !$status->info){
            return '';
        }

        $c = '<div class="CT_FormStatus_Box">';

        foreach($status->infoMessages as $infoMessage){
            $c .= $this->statusInfo($infoMessage);
        }

        foreach($status->successMessages as $successMessage){
            $c .= $this->statusSuccess($successMessage);
        }

        foreach($status->errors as $errorMessage){
            $c .= $this->statusError($errorMessage);
        }

        $c .= '</div>';

        return $c;
    }

    public function mapGenericAttributesToElement($element, $attributes)
    {
        if (isset($attributes['id'])) {
            $element->attributes['id'] = $attributes['id'];
        }
        if (isset($attributes['name'])) {
            $element->attributes['name'] = $attributes['name'];
        }
        if (isset($attributes['title'])) {
            $element->attributes['title'] = $attributes['title'];
        }
        if (isset($attributes['class']) && $attributes['class'] !== null) {
            $element->addClass($attributes['class']);
        }
        if (isset($attributes['style'])) {
            $element->attributes['style'] = $attributes['style'];
        }
        if (isset($attributes['placeholder'])) {
            $element->attributes['placeholder'] = $attributes['placeholder'];
        }
        if (isset($attributes['onkeypress'])) {
            $element->attributes['onkeypress'] = $attributes['onkeypress'];
        }
        if (isset($attributes['onkeydown'])) {
            $element->attributes['onkeydown'] = $attributes['onkeydown'];
        }
        if (isset($attributes['onkeyup'])) {
            $element->attributes['onkeyup'] = $attributes['onkeyup'];
        }
        if (isset($attributes['onclick'])) {
            $element->attributes['onclick'] = $attributes['onclick'];
        }
        if (isset($attributes['onmouseover'])) {
            $element->attributes['onmouseover'] = $attributes['onmouseover'];
        }
        if (isset($attributes['onmouseout'])) {
            $element->attributes['onmouseout'] = $attributes['onmouseout'];
        }
        if (isset($attributes['onmousedown'])) {
            $element->attributes['onmousedown'] = $attributes['onmousedown'];
        }
        if (isset($attributes['onmousemove'])) {
            $element->attributes['onmousemove'] = $attributes['onmousemove'];
        }
        if (isset($attributes['onmouseup'])) {
            $element->attributes['onmouseup'] = $attributes['onmouseup'];
        }
        if (isset($attributes['onfocus'])) {
            $element->attributes['onfocus'] = $attributes['onfocus'];
        }
        if (isset($attributes['onblur'])) {
            $element->attributes['onblur'] = $attributes['onblur'];
        }

        if (isset($attributes['data'])) {
            foreach ($attributes['data'] as $k => $v) {
                $element->attributes['data-' . $k] = $v;
            }
        }

        if (isset($attributes['hoverSkin'])) {
            if ($attributes['hoverSkin'] === true) {
                $element->addClass('CT_HoverItem');
            } else {
                $element->addClass('CT_HoverItem ' . $attributes['hoverSkin']);
            }
        } else if (isset($attributes['clickSkin'])) {
            if ($attributes['clickSkin'] === true) {
                $element->addClass('CT_ClickItem');
            } else {
                $element->addClass('CT_ClickItem ' . $attributes['clickSkin']);
            }
        }
    }

    public function errorClass($baseClass, $element, $attributes = null)
    {
        if (!ArrayTools::get($attributes, 'error')) {
            return;
        }

        if (!$this->config->errorClassFull) {
            $element->addClass('CT_Error');
        } else {
            $element->addClass($baseClass . 'Error');
        }
    }

    public function errorClassText($hasError, $fullAttribute = false)
    {
        if (!$hasError) {
            return '';
        }

        if (!$fullAttribute) {
            return ' CT_Error';
        } else {
            return ' class="CT_Error"';
        }
    }

    private function _addStatus($text, $class)
    {
        if (!$text) {
            return null;
        }

        $el = new UiElement('div', array('class' => 'CT_FormStatus ' . $class));
        if (is_string($text)) {
            $el->contents = array(new UiElement('p', null, $text));
        } else if (is_array($text)) {
            $el->contents = array();
            foreach ($text as $t) {
                array_push($el->contents, new UiElement('p', null, $t));
            }
        }else{
            $el->contents = array(new UiElement('p', null, strval($text)));
        }

        return $el;
    }

    public function calculatePageSelector($countPages, $currentPage, $startEndCount = 3, $innerRadius = 3, $ignoreGapSize = 1)
    {
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if ($countPages < 1) {
            $countPages = 1;
        }

        if ($countPages <= $startEndCount * 2 + $innerRadius * 2 - 1) {
            return array('currentPage' => $currentPage, 'pages' => range(1, $countPages, 1));
        } else {
            $innerStart = $currentPage - $innerRadius + 1;
            $innerEnd = $currentPage + $innerRadius - 1;
            if ($innerEnd > $countPages) {
                $innerEnd = $countPages;
            }

            $res = range(1, $startEndCount, 1);

            $nextPage = $startEndCount + 1;

            if ($nextPage + $ignoreGapSize < $innerStart) {
                $res[] = '.';
                $nextPage = $innerStart;
            }

            if ($nextPage <= $innerEnd) {
                $res = array_merge($res, range($nextPage, $innerEnd, 1));
                $nextPage = $innerEnd + 1;
            }


            if ($nextPage + $ignoreGapSize < $countPages - $startEndCount + 1) {
                $res[] = '.';
                $nextPage = $countPages - $startEndCount + 1;
            }

            if ($nextPage <= $countPages) {
                $res = array_merge($res, range($nextPage, $countPages, 1));
            }

            return array('currentPage' => $currentPage, 'pages' => $res);
        }
    }

    public function pageSelector($pages, $link, $attributes = null)
    {
        $currentPage = $pages['currentPage'];
        $pages = $pages['pages'];

        $defaultAttributes = array('pagePlaceholder' => '%page%', 'additionalLinkClasses' => '');
        $attributes = $attributes ? array_merge($defaultAttributes, $attributes) : $defaultAttributes;

        $pagePlaceholder = $attributes['pagePlaceholder'];
        $additionalLinkClasses = $attributes['additionalLinkClasses'];
        if ($additionalLinkClasses != '') {
            $additionalLinkClasses = ' ' . $additionalLinkClasses;
        }

        $link = StringTools::escapeHtml($link);

        $s = '';
        $el = new UiElement('div', array('class' => 'CT_PageSelector'));

        if ($currentPage > 1) {
            $s .= '<a class="previousPage' . $additionalLinkClasses . '" href="' . str_replace($pagePlaceholder, $currentPage - 1, $link) . '">‹</a>';
        }

        $page = 0;
        foreach ($pages as $page) {
            if ($page == '.') {
                $s .= '<span class="pageGap">...</span>';
            } else {
                if ($page == $currentPage) {
                    $s .= '<a class="currentPage' . $additionalLinkClasses . '" href="' . str_replace($pagePlaceholder, $page, $link) . '">';
                } else {
                    $s .= '<a ' . ($additionalLinkClasses != '' ? 'class="' . $additionalLinkClasses . '" ' : '') . 'href="' . str_replace($pagePlaceholder, $page, $link) . '">';
                }

                $s .= $page . '</a>';
            }
        }

        if ($currentPage < $page) {
            $s .= '<a class="nextPage' . $additionalLinkClasses . '" href="' . str_replace($pagePlaceholder, $currentPage + 1, $link) . '">›</a>';
        }

        $el->contents = $s;

        return $el;
    }
}