<?php

/**
 * This is the model class for table "user_twitter".
 *
 * The followings are the available columns in table 'user_twitter':
 * @property integer $id
 * @property integer $user_id
 * @property string $twitter_user_id
 * @property string $oauth_token
 * @property string $oauth_token_secret
 * @property integer $authorize_pay
 * @property string $created_on
 * @property string $updated_on
 *
 * The followings are the available model relations:
 * @property User $user
 */
class UserTwitter extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'user_twitter';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, twitter_user_id, oauth_token, oauth_token_secret, created_on, updated_on', 'required'),
			array('user_id, authorize_pay', 'numerical', 'integerOnly'=>true),
			array('twitter_user_id', 'length', 'max'=>20),
			array('oauth_token, oauth_token_secret', 'length', 'max'=>255),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, user_id, twitter_user_id, oauth_token, oauth_token_secret, authorize_pay, created_on, updated_on', 'safe', 'on'=>'search'),
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
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => 'User',
			'twitter_user_id' => 'Twitter User',
			'oauth_token' => 'Oauth Token',
			'oauth_token_secret' => 'Oauth Token Secret',
			'authorize_pay' => 'Authorize Pay',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('twitter_user_id',$this->twitter_user_id,true);
		$criteria->compare('oauth_token',$this->oauth_token,true);
		$criteria->compare('oauth_token_secret',$this->oauth_token_secret,true);
		$criteria->compare('authorize_pay',$this->authorize_pay);
		$criteria->compare('created_on',$this->created_on,true);
		$criteria->compare('updated_on',$this->updated_on,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return UserTwitter the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
