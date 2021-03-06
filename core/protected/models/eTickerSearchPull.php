<?php

class eTickerSearchPull extends TickerSearchPull {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ticker_search_pull';
    }

    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('user_id, question_id, followers, following', 'numerical', 'integerOnly' => true),
            array('ticker, source, source_content_id, source_user_id, username, avatar, name', 'length', 'max' => 255),
            array('hashtag', 'length', 'max' => 64),
            array('created_on, updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'insert'),
            array('updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'update'),
            array('user_id, ticker, source, source_content_id, source_user_id, username, avatar, name, source_date, followers, following, hashtag, created_on, updated_on', 'safe', 'on' => 'search'),
        );
    }

    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'user' => array(self::BELONGS_TO, 'User', 'user_id'),
            //'question' => array(self::BELONGS_TO, 'Question', 'question_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'user_id' => 'User',
            'question_id' => 'Question',
            'ticker' => 'Ticker',
            'source' => 'Source',
            'source_content_id' => 'Source Content ID',
            'source_user_id' => 'Source User ID',
            'username' => 'Username',
            'avatar' => 'Avatar',
            'name' => 'Name',
            'source_date' => 'Source ID',
            'followers' => 'Followers',
            'following' => 'Following',
            'hashtag' => 'Hashtag',
            'created_on' => 'Created On',
            'updated_on' => 'Updated On',
        );
    }

    public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('user_id', $this->user_id);
        $criteria->compare('question_id', $this->question_id);
        $criteria->compare('ticker', $this->ticker);
        $criteria->compare('source', $this->source);
        $criteria->compare('source_content_id', $this->source_content_id);
        $criteria->compare('source_user_id', $this->source_user_id);
        $criteria->compare('username', $this->username);
        $criteria->compare('avatar', $this->avatar);
        $criteria->compare('name', $this->name);
        $criteria->compare('source_date', $this->source_date);
        $criteria->compare('followers', $this->followers);
        $criteria->compare('following', $this->following);
        $criteria->compare('hashtag', $this->hashtag);
        $criteria->compare('created_on', $this->created_on !== null ? gmdate("Y-m-d H:i:s", strtotime($this->created_on)) : null);
        $criteria->compare('updated_on', $this->updated_on !== null ? gmdate("Y-m-d H:i:s", strtotime($this->updated_on)) : null);
        
        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }
    
    public function filterByDates($startDate, $endDate) {
        return DateTimeUtility::filterByDates($this, $startDate, $endDate);
    }
    
    public function scopes() {
        $defaultScopes = array(
            'recent' => array('order' => '`t`.`id` DESC'),
        );
        
        return $defaultScopes;
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TickerSearchStats the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

}

