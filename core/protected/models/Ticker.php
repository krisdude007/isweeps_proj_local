<?php

/**
 * This is the model class for table "ticker".
 *
 * The followings are the available columns in table 'ticker':
 * @property integer $id
 * @property integer $user_id
 * @property integer $entity_id
 * @property integer $question_id
 * @property string $prize_id
 * @property integer $ordinal
 * @property integer $frequency
 * @property integer $is_breaking
 * @property string $ticker
 * @property string $type
 * @property string $source
 * @property string $source_content_id
 * @property string $source_user_id
 * @property integer $to_facebook
 * @property integer $to_twitter
 * @property integer $to_web
 * @property integer $to_tv
 * @property integer $to_mobile
 * @property integer $arbitrator_id
 * @property string $status
 * @property string $statusbit
 * @property string $status_date
 * @property string $created_on
 * @property string $updated_on
 *
 * The followings are the available model relations:
 * @property Prize $prize
 * @property User $user
 * @property User $arbitrator
 * @property Question $question
 * @property Entity $entity
 * @property TickerDestination[] $tickerDestinations
 * @property TickerRun[] $tickerRuns
 * @property TickerStream[] $tickerStreams
 */
class Ticker extends ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ticker';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('ticker, type, source, source_content_id, source_user_id, to_facebook, to_twitter, to_web, to_tv, to_mobile, arbitrator_id, status, statusbit, status_date, created_on, updated_on', 'required'),
			array('user_id, entity_id, question_id, ordinal, frequency, is_breaking, to_facebook, to_twitter, to_web, to_tv, to_mobile, arbitrator_id', 'numerical', 'integerOnly'=>true),
			array('prize_id', 'length', 'max'=>11),
			array('ticker, source, source_content_id, source_user_id', 'length', 'max'=>255),
			array('type', 'length', 'max'=>6),
			array('status, statusbit', 'length', 'max'=>8),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, user_id, entity_id, question_id, prize_id, ordinal, frequency, is_breaking, ticker, type, source, source_content_id, source_user_id, to_facebook, to_twitter, to_web, to_tv, to_mobile, arbitrator_id, status, statusbit, status_date, created_on, updated_on', 'safe', 'on'=>'search'),
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
			'prize' => array(self::BELONGS_TO, 'Prize', 'prize_id'),
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
			'arbitrator' => array(self::BELONGS_TO, 'User', 'arbitrator_id'),
			'question' => array(self::BELONGS_TO, 'Question', 'question_id'),
			'entity' => array(self::BELONGS_TO, 'Entity', 'entity_id'),
			'tickerDestinations' => array(self::HAS_MANY, 'TickerDestination', 'ticker_id'),
			'tickerRuns' => array(self::HAS_MANY, 'TickerRun', 'ticker_id'),
			'tickerStreams' => array(self::HAS_MANY, 'TickerStream', 'ticker_id'),
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
			'entity_id' => 'Entity',
			'question_id' => 'Question',
			'prize_id' => 'Prize',
			'ordinal' => 'Ordinal',
			'frequency' => 'Frequency',
			'is_breaking' => 'Is Breaking',
			'ticker' => 'Ticker',
			'type' => 'Type',
			'source' => 'Source',
			'source_content_id' => 'Source Content',
			'source_user_id' => 'Source User',
			'to_facebook' => 'To Facebook',
			'to_twitter' => 'To Twitter',
			'to_web' => 'To Web',
			'to_tv' => 'To Tv',
			'to_mobile' => 'To Mobile',
			'arbitrator_id' => 'Arbitrator',
			'status' => 'Status',
			'statusbit' => 'Statusbit',
			'status_date' => 'Status Date',
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
	public function search($status, $questions, $question_id)
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('entity_id',$this->entity_id);
		$criteria->compare('question_id',$this->question_id);
		$criteria->compare('prize_id',$this->prize_id,true);
		$criteria->compare('ordinal',$this->ordinal);
		$criteria->compare('frequency',$this->frequency);
		$criteria->compare('is_breaking',$this->is_breaking);
		$criteria->compare('ticker',$this->ticker,true);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('source',$this->source,true);
		$criteria->compare('source_content_id',$this->source_content_id,true);
		$criteria->compare('source_user_id',$this->source_user_id,true);
		$criteria->compare('to_facebook',$this->to_facebook);
		$criteria->compare('to_twitter',$this->to_twitter);
		$criteria->compare('to_web',$this->to_web);
		$criteria->compare('to_tv',$this->to_tv);
		$criteria->compare('to_mobile',$this->to_mobile);
		$criteria->compare('arbitrator_id',$this->arbitrator_id);
		$criteria->compare('status',$this->status,true);
		$criteria->compare('statusbit',$this->statusbit,true);
		$criteria->compare('status_date',$this->status_date,true);
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
	 * @return Ticker the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
