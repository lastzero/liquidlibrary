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
 
class Liquid_Form_Example extends Liquid_Form {
    public function __construct () {
        $this->setDefinition(
            array(
                'firstname' => array(
                    'type' => 'string',
                    'min' => 2,
                    'max' => 10,
                    'caption' => 'Firstname'
                 ), 
                 'temperature' => array(
                    'type' => 'numeric',
                    'min' => 29.9,
                    'max' => 50.1,
                    'caption' => 'Temperature'
                 ), 
                 'email' => array(
                    'type' => 'email'
                 ),
                 'cars' => array(
                    'type' => 'array',
                    'options' => array(
                        'bmw' => 'BMW',
                        'hond' => 'Honda',
                        'gmc' => 'General Motors'
                    ),
                    'min' => 1,
                    'max' => 2
                 ),
                 'computers' => array(
                    'type' => 'array',
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                 ),
                 'sports' => array(
                    'caption' => 'Sports',
                    'options' => array(
                        'soccer' => 'Soccer',
                        'chess' => 'Chess',
                        'dance' => 'Dance'
                    )
                 ),
                 'fun' => array(
                    'default' => 'for_me'
                 ),
                 'spacetravel' => array(
                    'depends' => 'sports',
                    'depends_value' => 'chess'
                 ),
                 'drink' => array(
                    'caption' => 'Drink',
                    'depends' => 'sports',
                    'depends_last_option' => true
                 ),
                 'vehicle' => array(
                    'caption' => 'Vehicle',
                    'type' => 'float'
                 ),
                 'drive' => array(
                    'depends' => 'vehicle',
                    'depends_value_empty' => true
                 )
            )
        );
    }
}
