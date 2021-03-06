<?php

/**
 * This is the model class for table "entity_twitter".
 *
 * The followings are the available columns in table 'entity_twitter':
 * @property integer $id
 * @property integer $entity_id
 * @property string $twitter_user_id
 * @property string $oauth_token
 * @property string $oauth_token_secret
 * @property string $created_on
 * @property string $updated_on
 *
 * The followings are the available model relations:
 * @property Entity $entity
 */
class EntityTwitter extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'entity_twitter';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('entity_id, twitter_user_id, oauth_token, oauth_token_secret, created_on, updated_on', 'required'),
			array('entity_id', 'numerical', 'integerOnly'=>true),
			array('twitter_user_id', 'length', 'max'=>20),
			array('oauth_token, oauth_token_secret', 'length', 'max'=>255),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, entity_id, twitter_user_id, oauth_token, oauth_token_secret, created_on, updated_on', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'entity' => array(self::BELONGS_TO, 'Entity', 'entity_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'entity_id' => 'Entity',
			'twitter_user_id' => 'Twitter User',
			'oauth_token' => 'Oauth Token',
			'oauth_token_secret' => 'Oauth Token Secret',
			'created_on' => 'Created On',
			'updated_on' => 'Updated On',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('entity_id',$this->entity_id);
		$criteria->compare('twitter_user_id',$this->twitter_user_id,true);
		$criteria->compare('oauth_token',$this->oauth_token,true);
		$criteria->compare('oauth_token_secret',$this->oauth_token_secret,true);
		$criteria->compare('created_on',$this->created_on!==null?gmdate("Y-m-d H:i:s",strtotime($this->created_on)):null);
		$criteria->compare('updated_on',$this->updated_on!==null?gmdate("Y-m-d H:i:s",strtotime($this->updated_on)):null);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return EntityTwitter the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
