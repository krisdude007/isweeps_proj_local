<?php

class eUserLocation extends UserLocation {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function validatePostalCode($attribute, $params) {

        $regex = "/(^([0-9]{5})([- ][0-9]{4})?$|[ABCEGHJ-NPRSTVXY]{1}[0-9]{1}[ABCEGHJ-NPRSTV-Z]{1}[- ]?[0-9]{1}[ABCEGHJ-NPRSTV-Z]{1}[0-9]{1}$)/i";

        if(!preg_match_all($regex, $this->postal_code)){
            $this->addError($attribute, 'Please enter a valid US or Canadian postal code.');
        }

    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('postal_code', 'required', 'on' => 'register,profile,twitter'),
            array('postal_code', 'validatePostalCode', 'on' => 'register,profile,twitter'),
            array('user_id, phone_number', 'numerical', 'integerOnly' => true),
            array('city','required','on' => 'payment'),
            array('address1, address2, city, state, country, timezone, type', 'length', 'max' => 255),
            array('created_on,updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'insert, register'),
            array('updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'update, profile'),
            array('type', 'default', 'value' => 'primary', 'on' => 'insert'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, user_id, address1, address2, city, state, country, timezone, postal_code, type, phone_number, created_on, updated_on', 'safe', 'on' => 'search'),
        );
    }

    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'user' => array(self::BELONGS_TO, 'eUser', 'user_id'),
        );
    }

    public function scopes() {
        $alias = $this->getTableAlias();
        return array(
            'primary' => array('condition' => $alias.'.type = "primary"'),
        );
    }

    public function getPostalCodeByPostalCode($postal_code)
    {
        return ePostalCode::model()->find('identifier=?',array($postal_code));
    }
}