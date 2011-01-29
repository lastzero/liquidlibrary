<?php
/**
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Form
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

 class Liquid_Form_Translate {
    protected static $strings = array(
        'de_DE' => array(
            '%1$s must not be empty' => '%1$s darf nicht leer sein',
            '%1$s is too small (min %2$s)' => '%1$s ist zu klein',
            '%1$s is too short (min %2$s characters)' => '%1$s ist zu kurz',
            '%1$s is too big (max %2$s)' => '%1$s ist zu groß',
            '%1$s is too long (max %2$s characters)' => '%1$s ist zu lang',
            '%1$s is not the same as %2$s' => '%1$s stimmt nicht mit %2$s überein',
            '%1$s must not be empty, if %2$s has the value %3$s' => '%1$s muss vorhanden sein, wenn %2$s den Wert %3$s hat',
            '%1$s must not be empty, if %2$s is empty' => '%1$s muss vorhanden sein, wenn %2$s leer ist',
            '%1$s is not valid' => '%1$s ist nicht gültig',
            '%1$s must be a list' => '%1$s muss eine Liste sein',
            '%1$s must have at least %2$s options selected' => '%1$s muss mindestens %2$s Elemente haben',
            '%1$s can only have %2$s options selected' => '%1$s darf maximal %2$s Elemente haben',
            '%1$s contains an invalid option (%2$s)' => '%1$s enthält eine ungültige Option (%2$s)',
            '%1$s must be an integer number' => '%1$s muss eine ganze Zahl sein',
            '%1$s must be a number' => '%1$s muss eine Zahl sein',
            '%1$s must be a scalar value' => '%1$s muss ein einfacher Wert sein',
            '%1$s must be a list' => '%1$s muss eine Liste sein',
            '%1$s must be a number' => '%1$s muss eine Zahl sein',
            '%1$s must be a boolean value' => '%1$s muss vom Typ "boolean" sein',
            '%1$s must be a string' => '%1$s muss eine Zeichenkette sein',
            '%1$s must be an email address' => '%1$s muss eine E-Mail-Adresse sein',
            '%1$s must be a date' => '%1$s muss ein Datum sein',
            '%1$s must be a %2$s' => '%1$s muss vom Typ %2$s sein',
            '%1$s is too far in the future (%2$s days)' => '%1$s ist zu spät',
            '%1$s is too far in the future (max %2$s)' => '%1$s ist früher als %2$s',
            '%1$s is too far in the past (%2$s days)' => '%1$s ist zu früh',
            '%1$s is too far in the past (min %2$s)' => '%1$s liegt zu weit in der Vergangenheit'
        ),
        'en_US' => array(
        )
    );

    public static function setStrings ($strings, $locale) {
        self::$strings[$locale] = $strings;
    }

    public static function setLocale ($locale) {
        $translate = new Zend_Translate(array(
            'adapter' => 'Array',
            'content' => self::$strings[$locale],
            'locale'  => $locale
            )
        );

        Liquid_Form::setDefaultTranslateAdapter($translate);
    }

    public static function reset () {
        Liquid_Form::setDefaultTranslateAdapter();
    }
 }
