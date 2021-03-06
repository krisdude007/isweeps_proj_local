<?php

class ePollAnswer extends PollAnswer {

    public $hashtag;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('user_id, answer, color', 'required'),
            array('poll_id, user_id, point_value', 'numerical', 'integerOnly' => true),
            array('color', 'length', 'max' => 255),
            array('answer, hashtag', 'length', 'max' => 255, 'encoding' => 'UTF-8'),
            array('created_on,updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'insert'),
            array('updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'update'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, poll_id, user_id, answer, color, point_value, hashtag, created_on, updated_on', 'safe', 'on' => 'search'),
        );
    }

    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'poll_id' => 'Poll',
            'user_id' => 'User',
            'answer' => 'Answer',
            'color' => 'Color',
            'point_value' => 'Point Value',
            'hashtag' => 'Hashtag',
            'created_on' => 'Created',
            'updated_on' => 'Updated',
        );
    }

    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'poll' => array(self::BELONGS_TO, 'ePoll', 'poll_id'),
            'user' => array(self::BELONGS_TO, 'eUser', 'user_id'),
            'pollResponses' => array(self::HAS_MANY, 'ePollResponse', 'answer_id'),
            'tally' => array(self::STAT, 'ePollResponse', 'answer_id', 'select' => 'COUNT(id)', 'group' => 'answer_id'),
        );
    }

}