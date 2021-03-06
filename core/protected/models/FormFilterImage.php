<?php

class FormFilterImage extends CFormModel {

    public $status;
    public $dateStart;
    public $dateStop;
    public $user_id;
    public $tags;
    public $perPage;
    public $type;

    public function rules() {

        return array(
            array('type, status, perPage', 'required'),
            array('user_id', 'numerical', 'integerOnly' => true),
            //array('name', 'required'),
            //array('dateStart', 'allowEmpty' => true),
            //array('perPage', 'allowEmpty' => false),
            //array('user', 'allowEmpty' => true),
            //array('admin', 'allowEmpty' => true),
            array('dateStart', 'checkStartDate'),
            array('dateStop', 'checkStopDate'),
            array('tags', 'safe'),
        );

        return $rules;
    }
    
    public function checkStartDate($attribute, $params) {
        if (!$this->hasErrors()) {
            if (strtotime($this->dateStart) > strtotime($this->dateStop))
                //$this->addError($attribute, '"From" date should be lest than than or equal to To date.');
                Yii::app()->user->setFlash('error', '"From" date should be lest than than or equal to To date.');
        }
    }
    
    public function checkStopDate($attribute, $params) {
        if (!$this->hasErrors()) {
            if (strtotime($this->dateStop) < strtotime($this->dateStart))
                //$this->addError($attribute, '"To" date should be greater than or equal to From date.');
                Yii::app()->user->setFlash('error', '"To" date should be greater than or equal to From date.');
        }
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels() {
        return array(
            'type' => 'Type',
            'status' => 'Status',
            'dateStart' => 'From',
            'dateStop' => 'To',
            'user_id' => 'User ID',
            'tags' => 'Tags',
            'perPage' => 'Items per page'
        );
    }

}