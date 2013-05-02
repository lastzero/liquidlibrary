<?php
/**
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * =============================| Description of form definition parameters |===========================================
 * caption              Field title (used for form rendering and in validation messages)
 * type                 Data type: int, numeric, scalar, list, bool, string, email, date, switch and
 *                      all Zend_Validate validators
 * type_params          Optional parameters for Zend_Validate data types
 * options              Array of possible values for the field (for select lists or radio button groups)
 * min                  Minimum value for numbers/dates, length for strings or number of elements for lists
 * max                  Maximum value for numbers/dates, length for strings or number of elements for lists
 * required             Field cannot be empty
 * readonly             User is not allowed to change the field
 * hidden               User can not see the field
 * default              Default value
 * checkbox             A checkbox-like form input element is used (the form class will assign false for
 *                      boolean fields or array() for lists, if the value is not passed to setDefinedValues()
 *                      or setDefinedWritableValues()). This is a work around, because browsers do not submit
 *                      any data for unchecked checkboxes or multi-select fields without a selected element.
 * regex                Regular expression to match against
 * matches              Field value must match another form field (e.g. for password or email validation).
 *                      Property can be prefixed with "!" to state that the fields must be different.
 * depends              Field is required, if the given form field is not empty
 * depends_value        Field is required, if the field defined in "depends" has this value
 * depends_value_empty  Field is required, if the field defined in "depends" is empty
 * depends_first_option Field is required, if the field defined in "depends" has the first value (see "options")
 * depends_last_option  Field is required, if the field defined in "depends" has the last value (see "options")
 * page                 Page number for multi-page forms
 * =====================================================================================================================
 *
 * @category   Liquid
 * @package    Liquid_Form
 * @copyright  Copyright (c) 2010-2011 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

require_once 'Liquid/Form/Exception.php';

class Liquid_Form {
    private $_definition = array();
    private $_values = array();
    private $_errors = array();

    protected $_validationDone = false;
    protected $_translateAdapter = null;
    protected $_locale = null;

    protected static $_defaultTranslateAdapter = null;
    protected static $_defaultLocale = null;

    public static function setDefaultTranslateAdapter (Zend_Translate $translateAdapter = null) {
        self::$_defaultTranslateAdapter = $translateAdapter;
    }

    public function setTranslateAdapter (Zend_Translate $translateAdapter = null) {
        $this->_translateAdapter = $translateAdapter;

        return $this;
    }

    public function setLocale (Zend_Locale $locale = null) {
        $this->_locale = $locale;

        return $this;
    }

    public function getLocale () {
        if($this->_locale) {
            return $this->_locale;
        }

        return self::getDefaultLocale();
    }

    public static function setDefaultLocale (Zend_Locale $locale = null) {
        self::$_defaultLocale = $locale;
    }

    public static function getDefaultLocale () {
        if(self::$_defaultLocale) {
            return self::$_defaultLocale;
        } elseif (Zend_Registry::isRegistered('Zend_Locale')) {
            return Zend_Registry::get('Zend_Locale');
        }

        return new Zend_Locale();
    }

    public function setDefinition (array $definition) {
        $this->_definition = $definition;

        return $this;
    }

    public function getDefinition ($key = null, $propertyName = null) {
        if(!is_array($this->_definition)) {
            throw new Liquid_Form_Exception('Form definition is not an array. Something went totally wrong.');
        } elseif(count($this->_definition) == 0) {
            throw new Liquid_Form_Exception('Form definition is empty.');
        } elseif($key == null) {
            return $this->_definition;
        } elseif(isset($this->_definition[$key])) {
            if($propertyName != null) {
                if(isset($this->_definition[$key][$propertyName])) {
                    return $this->_definition[$key][$propertyName];
                }

                return null;
            }

            return $this->_definition[$key];
        }

        throw new Liquid_Form_Exception('No form field definition found for "'. $key .'".');
    }

    public function addDefinition ($key, array $definition) {
        if(isset($this->_definition[$key])) {
            throw new Liquid_Form_Exception('Definition for '.$key.' already exists');
        }

        $this->_definition[$key] = $definition;
    }

    public function changeDefinition ($key, array $changes) {
        if(!isset($this->_definition[$key])) {
            throw new Liquid_Form_Exception('Definition for '.$key.' does not exist');
        }

        foreach($changes as $prop => $val) {
            if($val === null) {
                unset($this->_definition[$key][$prop]);
            } else {
                $this->_definition[$key][$prop] = $val;
            }
        }
    }

    public function getForm () {
        $result = array();

        foreach($this->_definition as $key => $def) {
            $result[$key]          = $def;
            $result[$key]['value'] = $this->$key;
            $result[$key]['uid']   = 'id' . uniqid();
        }

        return $result;
    }

    protected function isWritable ($key) {
        return $this->getDefinition($key, 'readonly') != true;
    }

    protected function isCheckbox ($key) {
        return $this->getDefinition($key, 'checkbox') == true;
    }

    protected function setCheckboxValueInArray ($key, &$values) {
        if($this->isCheckbox($key) && !array_key_exists($key, $values)) {
            $type = $this->getDefinition($key, 'type');
            switch($type) {
                case 'list':
                    $values[$key] = array();
                    break;
                case 'bool':
                    $values[$key] = false;
                    break;
                default:
                    $values[$key] = null;
            }
        }
    }

    public function setAllValues (Array $values) {
        foreach($values as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    public function setDefinedValues (array $values) {
        foreach($this->_definition as $key => $value) {
            $this->setCheckboxValueInArray($key, $values);

            if(!array_key_exists($key, $values)) {
                throw new Liquid_Form_Exception ('Array provided to setDefinedValues() was not complete: ' . $key);
            }

            $this->$key = $values[$key];
        }

        return $this;
    }

    public function setWritableValues (array $values) {
        foreach($values as $key => $value) {
            if($this->isWritable($key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    public function setDefinedWritableValues (array $values) {
        foreach($this->_definition as $key => $value) {
            if($this->isWritable($key)) {
                $this->setCheckboxValueInArray($key, $values);

                if(!array_key_exists($key, $values)) {
                    throw new Liquid_Form_Exception ('Array provided to setDefinedWritableValues() was not complete: ' . $key);
                }


                $this->$key = $values[$key];
            }
        }

        return $this;
    }

    public function setWritableValuesOnPage (array $values, $page) {
        foreach($this->_definition as $key => $value) {
            if(isset($value['page']) && $value['page'] == $page && $this->isWritable($key)) {
                $this->setCheckboxValueInArray($key, $values);

                if(!array_key_exists($key, $values)) {
                    throw new Liquid_Form_Exception ('Array provided to setWritableValuesOnPage() was not complete: ' . $key);
                }

                $this->$key = $values[$key];
            }

        }

        return $this;
    }

    public function getValuesByPage () {
        $result = array();

        foreach($this->_definition as $key => $value) {
            $page = $this->getDefinition($key, 'page');

            if($page) {
                $result[$page][$key] = $this->$key;
            }
        }

        return $result;
    }

    public function getValues () {
        $result = array();

        foreach($this->_definition as $key => $value) {
            $result[$key] = $this->$key;
        }

        return $result;
    }

    public function __set ($key, $val) {
        if(isset($this->_definition[$key])) {
            if($this->getDefinition($key, 'type') == 'bool') {
                $val = (bool) $val;
            }

            if($this->getDefinition($key, 'type') != 'string' && $val === '') {
                $val = null;
            }

            $this->clearErrors();

            $this->_values[$key] = $val;
        } else {
            throw new Liquid_Form_Exception ('Form field not defined: ' . $key);
        }
    }

    public function __get ($key) {
        try {
            $default = $this->getDefinition($key, 'default');

            if(isset($this->_values[$key])) {
                return $this->_values[$key];
            }

            return $default;
        } catch (Exception $e) {
            throw new Liquid_Form_Exception ('Form field not defined: ' . $key);
        }
    }

    protected function translate () {
        $args = func_get_args();

        if($this->_translateAdapter) {
            $args[0] = call_user_func(array($this->_translateAdapter, '_'), $args[0]);
        } elseif(self::$_defaultTranslateAdapter) {
            $args[0] = call_user_func(array(self::$_defaultTranslateAdapter, '_'), $args[0]);
        }

        return call_user_func_array('sprintf', $args);
    }

    protected function getFieldCaption ($key) {
        $caption = $this->getDefinition($key, 'caption');

        if($caption) {
            $caption = str_replace('%', '%%', $caption); // Escaping for sprintf()
            return $this->translate($caption);
        }

        return $key;
    }

    protected function addError($key, $text) {
        $args = func_get_args();

        $key = array_shift($args);
        $text = array_shift($args);
        array_unshift($args, $text, $this->getFieldCaption($key));

        $this->_errors[$key][] = call_user_func_array(array($this, 'translate'), $args);

        return $this;
    }

    protected function validateRequired ($key, $def, $value) {
        if(isset($def['required']) && ($def['required'] === true) && ($value === null || $value === '')) {
            $this->addError($key, '%1$s must not be empty');
        }
    }

    protected function validateMin ($key, $def, $value) {
        if(!isset($def['options']) && isset($def['min']) && $value != '') {
            if(isset($def['type']) && ($def['type'] == 'int' || $def['type'] == 'numeric' || $def['type'] == 'float')) {
                if($value < $def['min']) {
                    $this->addError($key, '%1$s is too small (min %2$s)', $def['min']);
                }
            } elseif(isset($def['type']) && $def['type'] == 'date') {
                if(Zend_Validate::is($value, 'Date')) {
                    $date = new Zend_Date($value);

                    if(is_int($def['min'])) {
                        $now = new Zend_Date();
                        $jdNow = gregoriantojd($now->toString('M'), $now->toString('d'), $now->toString('y'));
                        $jdDate = gregoriantojd($date->toString('M'), $date->toString('d'), $date->toString('y'));

                        if(($jdDate - $jdNow) < $def['min']) {
                            $this->addError($key, '%1$s is too far in the past (%2$s days)', ($jdDate - $jdNow) - $def['min']);
                        }
                    } else {
                        $limit = new Zend_Date($def['min']);
                        if($date->compare($limit) < 0) {
                            $this->addError($key, '%1$s is too far in the past (min %2$s)', $limit->toString(Zend_Date::DATE_MEDIUM));
                        }
                    }
                }
            } elseif(strlen($value) < $def['min']) {
                $this->addError($key, '%1$s is too short (min %2$s characters)', $def['min']);
            }
        }
    }

    protected function validateMax ($key, $def, $value) {
        if(!isset($def['options']) && isset($def['max']) && $value != '') {
            if(isset($def['type']) && ($def['type'] == 'int' || $def['type'] == 'numeric' || $def['type'] == 'float')) {
                if($value > $def['max']) {
                    $this->addError($key, '%1$s is too big (max %2$s)', $def['max']);
                }
            } elseif(isset($def['type']) && $def['type'] == 'date') {
                if(Zend_Validate::is($value, 'Date')) {
                    $date = new Zend_Date($value);

                    if(is_int($def['max'])) {
                        $now = new Zend_Date();
                        $jdNow = gregoriantojd($now->toString('M'), $now->toString('d'), $now->toString('y'));
                        $jdDate = gregoriantojd($date->toString('M'), $date->toString('d'), $date->toString('y'));

                        if(($jdDate - $jdNow) > $def['max']) {
                            $this->addError($key, '%1$s is too far in the future (%2$s days)', ($jdDate - $jdNow) - $def['max']);
                        }
                    } else {
                        $limit = new Zend_Date($def['max']);
                        if($date->compare($limit) > 0) {
                            $this->addError($key, '%1$s is too far in the future (max %2$s)', $limit->toString(Zend_Date::DATE_MEDIUM));
                        }
                    }
                }
            } elseif(strlen($value) > $def['max']) {
                $this->addError($key, '%1$s is too long (max %2$s characters)', $def['max']);
            }
        }
    }

    protected function validateMatches ($key, $def, $value) {
        if(isset($def['matches'])) {
            if($def['matches'][0] == '!') {
                $fieldName = substr($def['matches'], 1);
                if($value == $this->$fieldName) {
                    $this->addError($key, '%1$s the same as %2$s', $this->getFieldCaption($fieldName));
                }
            } else {
                if($value != $this->{$def['matches']}) {
                    $this->addError($key, '%1$s is not the same as %2$s', $this->getFieldCaption($def['matches']));
                }
            }
        }
    }

    protected function validateDepends ($key, $def, $value) {
        if(isset($def['depends'])) {
            if($this->{$def['depends']} != '' && $value == '' && !isset($def['depends_value_empty'])) {
                if(isset($def['depends_first_option']) && isset($this->_definition[$def['depends']]['options'])) {
                    reset($this->_definition[$def['depends']]['options']);
                    if($this->{$def['depends']} == key($this->_definition[$def['depends']]['options'])) {
                        $this->addError($key, '%1$s must not be empty, if %2$s has the value %3$s',
                            $this->getFieldCaption($def['depends']),
                            current($this->_definition[$def['depends']]['options'])
                        );
                    }
                } elseif (isset($def['depends_last_option']) && isset($this->_definition[$def['depends']]['options'])) {
                    end($this->_definition[$def['depends']]['options']);
                    if($this->{$def['depends']} == key($this->_definition[$def['depends']]['options'])) {
                        $this->addError($key, '%1$s must not be empty, if %2$s has the value %3$s',
                            $this->getFieldCaption($def['depends']),
                            current($this->_definition[$def['depends']]['options'])
                        );
                    }
                } elseif(!isset($def['depends_value']) ||  $this->{$def['depends']} == $def['depends_value']) {
                    $this->addError($key, '%1$s must not be empty');
                }
            } elseif($this->{$def['depends']} == '' && $value == '' && isset($def['depends_value_empty'])) {
                $this->addError($key, '%1$s must not be empty, if %2$s is empty',
                    $this->getFieldCaption($def['depends'])
                );
            }
        }
    }

    protected function validateRegex ($key, $def, $value) {
        if(isset($def['regex']) && !empty($value) && !preg_match($def['regex'], $value)) {
            $this->addError($key, '%1$s is not valid');
        }
    }

    protected function validateOptions ($key, $def, $value) {
        if(isset($def['options']) && $value != '') {
            if(isset($def['min']) || isset($def['min'])) {
                if(!is_array($value)) {
                    $this->addError($key, '%1$s must be a list');
                } else {
                    if(isset($def['min']) && count($value) < $def['min']) {
                        $this->addError($key, '%1$s must have at least %2$s options selected', $def['min']);
                    }

                    if(isset($def['max']) && count($value) > $def['max']) {
                        $this->addError($key, '%1$s can only have %2$s options selected', $def['max']);
                    }
                }
            }

            if(is_array($value)) {
                foreach($value as $option => $order) {
                    if(is_int($option)) {
                        if(!isset($def['options'][$order])) {
                            $this->addError($key, '%1$s contains an invalid option (%2$s)', $order);
                        }
                    } else {
                        if(!isset($def['options'][$option])) {
                            $this->addError($key, '%1$s contains an invalid option (%2$s)', $option);
                        }
                    }
                }
            } else {
                if(!isset($def['options'][$value])) {
                    $this->addError($key, '%1$s contains an invalid option (%2$s)', $value);
                }
            }
        }
    }

    protected function validateType ($key, $def, $value) {
        if(isset($def['type']) && $value != '') {
            switch($def['type']) {
                case 'int':
                    if(!is_int($value)) {
                        $this->addError($key, '%1$s must be an integer number');
                    }
                    break;
                case 'numeric':
                    if(!is_numeric($value)) {
                        $this->addError($key, '%1$s must be a number' );
                    }
                    break;
                case 'scalar':
                    if(!is_scalar($value)) {
                        $this->addError($key, '%1$s must be a scalar value');
                    }
                    break;
                case 'list':
                    if(!is_array($value)) {
                        $this->addError($key, '%1$s must be a list');
                    }
                    break;
                case 'float':
                    try {
                        if(!Zend_Locale_Format::isNumber($value, array('locale' => $this->getLocale()))) {
                            $this->addError($key, '%1$s must be a number');
                        }
                    } catch (Exception $e) {
                        $this->addError($key, '%1$s must be a number');
                    }
                    break;
                case 'bool':
                    if(!is_bool($value)) {
                        $this->addError($key, '%1$s must be a boolean value');
                    }
                    break;
                case 'string':
                    if(!is_string($value)) {
                        $this->addError($key, '%1$s must be a string');
                    }
                    break;
                case 'email':
                    if(!Zend_Validate::is($value, 'EmailAddress')) {
                        $this->addError($key, '%1$s must be an email address');
                    }
                    break;
                case 'date':
                    if(!Zend_Validate::is($value, 'Date')) {
                        $this->addError($key, '%1$s must be a date');
                    }
                    break;
                default:
                    $params = (isset($def['type_params']) && is_array($def['type_params'])) ? $def['type_params'] : array();

                    if(!Zend_Validate::is($value, $def['type'], $params)) {
                        $this->addError($key, '%1$s must be a %2$s', $this->translate($def['type']));
                    }
                    break;
            }
        }
    }

    protected function validateField ($key, $def, $value) {
        $this->validateRequired($key, $def, $value);
        $this->validateMin($key, $def, $value);
        $this->validateMax($key, $def, $value);
        $this->validateMatches($key, $def, $value);
        $this->validateDepends($key, $def, $value);
        $this->validateRegex($key, $def, $value);
        $this->validateOptions($key, $def, $value);
        $this->validateType($key, $def, $value);
    }

    protected function _validate ($functionName) {
        if($this->_validationDone) {
            throw new Liquid_Form_Exception('Validation was already done. Call clearErrors() to reset');
        }

        foreach($this->_definition as $key => $def) {
            if(is_int($key)) {
                throw new Liquid_Form_Exception ('Form field names can not be numeric - there probably is a typo in the form definition');
            }

            $value = $this->$key;

            $this->$functionName($key, $def, $value);
        }

        $this->_validationDone = true;

        return $this;
    }

    public function validate () {
        return $this->_validate('validateField');
    }

    public function getErrors () {
        if(!$this->_validationDone) {
            throw new Liquid_Form_Exception('You must run validate() before calling getErrors()');
        }

        return $this->_errors;
    }

    public function getErrorsByPage () {
        $result = array();
        $errors = $this->getErrors();

        foreach($errors as $key => $val) {
            $page = $this->getDefinition($key, 'page');

            if($page) {
                $result[$page][$key] = $val;
            }
        }

        return $result;
    }

    public function clearErrors () {
        $this->_validationDone = false;

        if(count($this->_errors) != 0) {
            $this->_errors = array();
        }

        return $this;
    }
}
