<?php

class eTicker extends Ticker {

    //Virtual attribute for stopping the ticker from running
    public $stop;
    //Virtual attribute for checking the language of a ticker
    public $clean;
    public $extendedStatus;
    public $username;
    public $prize_id;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        $defaultRules = array(
            array('ticker', 'filter', 'filter' => array($obj = new CHtmlPurifier(), 'purify')),
            array('ticker', 'required', 'message' => Yii::t('youtoo', 'Ticker cannot be blank')),
            array('type, source', 'required'),
            array('prize_id', 'length', 'max'=>11),
            array('status', 'default', 'value' => 'new'),
            array('status_date', 'default', 'value' => date("Y-m-d H:i:s")),
            array('to_facebook, to_twitter, to_web, to_tv, to_mobile, is_breaking', 'default', 'value' => 0),
            array('created_on,updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'insert'),
            array('updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'update'),
            array('user_id, question_id, entity_id, ordinal, frequency, is_breaking, to_facebook, to_twitter, to_web, to_tv, to_mobile, arbitrator_id', 'numerical', 'integerOnly' => true),
            array('source, source_content_id, source_user_id', 'length', 'max' => 255),
            array('ticker', 'length', 'max' => 140, 'encoding' => 'UTF-8'), //tickers can be longer then 140
            array('type', 'length', 'max' => 6),
            array('status', 'length', 'max' => 8),
            array('stopticker', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, user_id, username, question_id, prize_id, ticker, type, source, source_content_id, source_user_id, to_facebook, to_twitter, to_web, to_tv, to_mobile, arbitrator_id, status, is_breaking, status_date, created_on, updated_on', 'safe', 'on' => 'search'),
        );

        $extendedRules = array(
            //bit value 128 for "new" and 16 for "new_tv"
            array('statusbit', 'default', 'value' => 144, 'on' => 'insert'),
            array('extendedStatus', 'safe')
        );

        if (Yii::app()->params['ticker']['useExtendedFilters']) {
            return CMap::mergeArray($defaultRules, $extendedRules);
        } else {
            return $defaultRules;
        }
    }

    public function afterFind() {
        if(Yii::app()->controller->id == 'adminTicker') {
            $this->clean = LanguageUtility::filter($this->ticker);
        }

        if (Yii::app()->params['ticker']['useExtendedFilters']) {
            $this->extendedStatus = Utility::setExtendedStatus($this);
        }
        return parent::afterFind();
    }

    public function beforeSave() {
        if (Yii::app()->params['ticker']['useExtendedFilters']) {
            $this->statusbit = Utility::setStatusbit($this);
            $this->extendedStatus = Utility::setExtendedStatus($this);
        }
        return parent::beforeSave();
    }

    public function afterSave() {
        if ($this->status == 'accepted' || Yii::app()->params['ticker']['useExtendedFilters'] && $this->extendedStatus['accepted']) {
            if ($this->to_twitter == 1) {
                $destination = eDestination::model()->findByAttributes(Array('destination' => 'twitter'));
                $sent = eTickerDestination::model()->findByAttributes(Array('ticker_id' => $this->id, 'user_id' => $this->user_id, 'destination_id' => $destination->id));
                if (is_null($sent)) {
                    $response = TwitterUtility::tweetAs($this->user_id, $this->ticker);
                    $dest = new eTickerDestination;
                    $dest->ticker_id = $this->id;
                    $dest->user_id = $this->user_id;
                    $dest->destination_id = $destination->id;
                    if (isset($response->errors) && sizeof($response->errors) > 0) {
                        foreach ($response->errors as $i => $error) {
                            $dest->response .= $error->message;
                        }
                    } else if (isset($response->id_str)) {
                        $dest->response = $response->id_str;
                    }
                    $dest->save();
                }
            }
            if ($this->to_facebook == 1) {
                $destination = eDestination::model()->findByAttributes(Array('destination' => 'facebook'));
                $sent = eTickerDestination::model()->findByAttributes(Array('ticker_id' => $this->id, 'user_id' => $this->user_id, 'destination_id' => $destination->id));
                if (is_null($sent)) {
                    $post = array(
                        'message' => $this->ticker,
                        'link' => Yii::app()->createAbsoluteUrl('/'),
                    );
                    $response = FacebookUtility::shareAs($this->user_id, $post);
                    $dest = new eTickerDestination;
                    $dest->ticker_id = $this->id;
                    $dest->user_id = $this->user_id;
                    $dest->destination_id = $destination->id;
                    if (!$response['result']) {
                        $dest->response = $response['error'];
                    } else {
                        $dest->response = $response['response']['id'];
                    }
                    $dest->save();
                }
            }
        }
        return parent::afterSave();
    }

    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'prize' => array(self::BELONGS_TO, 'Prize', 'prize_id'),
            'arbitrator' => array(self::BELONGS_TO, 'eUser', 'arbitrator_id'),
            'user' => array(self::BELONGS_TO, 'eUser', 'user_id'),
            'entity' => array(self::BELONGS_TO, 'eEntity', 'entity_id'),
            'question' => array(self::BELONGS_TO, 'eQuestion', 'question_id'),
            'tickerDestinations' => array(self::HAS_MANY, 'eTickerDestination', 'ticker_id'),
            'tickerRuns' => array(self::HAS_MANY, 'eTickerRun', 'ticker_id'),
            'tickerStreams' => array(self::HAS_MANY, 'eTickerStream', 'ticker_id'),
        );
    }

    public function attributeLabels() {

        $defaultLabels = CMap::mergeArray(parent::attributeLabels(), array(
                    'id' => 'ID',
                    'user_id' => 'User',
                    'user.username' => 'User Name',
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
                    'to_facebook' => 'Post To Facebook',
                    'to_twitter' => 'Post To Twitter',
                    'to_web' => 'Post To Web',
                    'to_tv' => 'Post To Tv',
                    'to_mobile' => 'Post To Mobile',
                    'arbitrator_id' => 'Arbitrator',
                    'status' => 'Status',
                    'status_date' => 'Status Date',
                    'created_on' => 'Created On',
                    'updated_on' => 'Updated On',
        ));

        $extendedLabels = array(
            'statusbit' => 'Statusbit',
            'extendedStatus' => 'ExtendedStatus',
        );

        if (Yii::app()->params['ticker']['useExtendedFilters']) {
            return CMap::mergeArray($defaultLabels, $extendedLabels);
        } else {
            return $defaultLabels;
        }
    }

    public function scopes() {
        $defaultScopes1 = array(
            'recent' => array('order' => '`t`.`id` DESC'),
            'hasWebRuns' => array('condition' => 'tickerRun.web_runs != 0'),
            'hasTvRuns' => array('condition' => 'tickerRun.tv_runs != 0'),
            'hasMobileRuns' => array('condition' => 'tickerRun.mobile_runs != 0'),
            'hasDestination' => array('condition' => 'tickerDestination.ticker_id is not null'),
            'hasRuns' => array('condition' => '(tickerRuns.mobile_runs != 0 || tickerRuns.tv_runs != 0 || tickerRuns.web_runs != 0)'),
            'canRun' => array('condition' => 'tickerRun.ticker_id is not null'),
            'ticker' => array('condition' => $this->getTableAlias() . '.type="ticker"'),
            'social' => array('condition' => 'type="social"'),
            'toWeb' => array('condition' => 'tickerDestinations.destination_id = (select id from destination where destination = "web")'),
            'today' => array('condition' => "DATE(`t`.created_on) = CURDATE()"),
            'isEntity' => array('condition' => 'type="entity"'),
        );

        $defaultScopes2 = array(
            'new' => array('condition' => 'status="new"'),
            'accepted' => array('condition' => 'status="accepted"'),
            'denied' => array('condition' => 'status="denied"'),
        );

        if (Yii::app()->params['ticker']['useExtendedFilters']) {
            $extendedScopes = array(
                'new' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['new']),
                'accepted' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['accepted'] . "
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['denied'] . ") = 0"),
                'denied' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['denied']),
                'newtv' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['newTv']),
                'acceptedtv' => Utility::getAcceptedTVScope(Yii::app()->params['ticker']['extendedFilterLabels']),
                'breaking' => array('condition' => '`t`.is_breaking="1"'),
                'notbreaking' => array('condition' => '`t`.is_breaking="0"'),
                'deniedtv' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['deniedTv']),
                'newsup1' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['acceptedTv'] . "
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['deniedTv'] . ") = 0
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['acceptedSuperAdmin1'] . ") = 0"), //(accepted tv) and (not deniedtv) and (not accepted sup1)
                'newsup2' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['acceptedTv'] . "
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['deniedTv'] . ") = 0
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['acceptedSuperAdmin2'] . ") = 0"), //(accepted tv) and (not deniedtv) and (not accepted sup2)
                'statustv' => Utility::getStatusTVScope(Yii::app()->params['ticker']['extendedFilterLabels']),
            );

            return CMap::mergeArray($defaultScopes1, $extendedScopes);
        } else {
            return CMap::mergeArray($defaultScopes1, $defaultScopes2);
        }
    }

    public function filterByDates($startDate, $endDate) {
        return DateTimeUtility::filterByDates($this, $startDate, $endDate);
    }

    public function filterByTickerDate($filterDate) {
        $this->getDbCriteria()->mergeWith(array(
            'condition' => 't.created_on>=:filterDate',
            'params' => array(':filterDate' => $filterDate !== null ? gmdate('Y-m-d H:i:s', strtotime($filterDate)) : null),
        ));
        return $this;
    }

    public function filterByDestinationDate($filterDate) {
        $this->getDbCriteria()->mergeWith(array(
            'condition' => 'tickerDestination.created_on>=:filterDate',
            'params' => array(':filterDate' => $filterDate !== null ? gmdate('Y-m-d H:i:s', strtotime($filterDate)) : null),
        ));
        return $this;
    }

    public function filterByDestination($destinationId) {
        $this->getDbCriteria()->mergeWith(array(
            'condition' => 'destination_id=:id',
            'params' => array(':id' => $destinationId,),
        ));
        return $this;
    }

    public static function searchSocial($terms, $boolean, $filters, $q, $minResults = 100) {
        if (empty($terms)) {
            return;
        }
        switch ($boolean) {
            case 'AND':
                $terms = '"' . $terms . ' ' . $filters . '"';
                break;
            case 'OR':
                $terms = '"' . $terms . '" OR "' . $filters . '"';
                break;
            case 'NOT':
                $terms = '"' . $terms . '" -"' . $filters . '"';
                break;
        }
        $results = TwitterUtility::search($terms, null, $minResults);
        $errors = array();
        if (isset($results->errors[0]->message)) {
            $errors['twitter'] = $results->errors[0]->message;
            $rates['twitter'] = $results->rate_limit_remaining . ' search calls remaining.  Resets ' . $results->rate_limit_reset;
        } else {
            $rates['twitter'] = $results->rate_limit_remaining . ' search calls remaining.  Resets ' . DateTimeUtility::niceTime($results->rate_limit_reset);
            while (count($results->statuses) < $minResults - 5) { // "-5" is alowing for some leeway so it does not need to search if its only 5 short
                if (count($results->statuses) == 0) {
                    break;
                }
                $max_id = end($results->statuses)->id;
                $moreTweets = TwitterUtility::search($terms, $max_id, $minResults);
                if (count($moreTweets->statuses) == 0) {
                    break;
                }
                if ($results->statuses != $moreTweets->statuses) {
                    $results->statuses = array_merge($results->statuses, $moreTweets->statuses);
                } else {
                    break;
                }
                $rates['twitter'] = $moreTweets->rate_limit_remaining . ' search calls remaining.  Resets ' . DateTimeUtility::niceTime($moreTweets->rate_limit_reset);
            }

            $count = 0;
            foreach ($results->statuses as $k => $v) {
                $ret[] = TwitterUtility::parseTweet($v, $q, $terms);
                $count++;
            }
        }
        $ret['errors'] = $errors;
        $ret['rates'] = $rates = array();

        $tickerSearchStats = new eTickerSearchStats();
        $tickerSearchStats->user_id = Yii::app()->user->getId();
        $tickerSearchStats->question_id = $q;
        $tickerSearchStats->hashtag = $terms;
        $tickerSearchStats->num = $count;
        $tickerSearchStats->save();

        return $ret;
    }

    public function streamSocial($position = false) {
        return(TwitterUtility::getStream($position));
    }

    public function search($status, $questions, $question_id) {
        $criteria = new CDbCriteria;
        $criteria->index = 'id';
        $criteria->with = 'user';
        $criteria->compare('id', $this->id);
        $criteria->compare('user_id', $this->user_id);
        $criteria->compare('user.username', $this->username);
        $criteria->compare('entity_id', $this->entity_id);
        $criteria->compare('question_id', $this->question_id);
        $criteria->compare('prize_id',$this->prize_id,true);
        $criteria->compare('ordinal', $this->ordinal);
        $criteria->compare('frequency', $this->frequency);
        $criteria->compare('is_breaking', $this->is_breaking);
        $criteria->compare('ticker', $this->ticker, true);
        $criteria->compare('type', $this->type, true);
        $criteria->compare('source', $this->source, true);
        $criteria->compare('source_content_id', $this->source_content_id, true);
        $criteria->compare('source_user_id', $this->source_user_id, true);
        $criteria->compare('to_facebook', $this->to_facebook);
        $criteria->compare('to_twitter', $this->to_twitter);
        $criteria->compare('to_web', $this->to_web);
        $criteria->compare('to_tv', $this->to_tv);
        $criteria->compare('to_mobile', $this->to_mobile);
        $criteria->compare('arbitrator_id', $this->arbitrator_id);
        $criteria->compare('status', $this->status, true);
        $criteria->compare('statusbit', $this->statusbit, true);
        $criteria->compare('status_date', $this->status_date !== null ? gmdate('Y-m-d H:i:s', strtotime($this->status_date)) : null);
        $criteria->compare('created_on', $this->created_on !== null ? gmdate('Y-m-d H:i:s', strtotime($this->created_on)) : null);
        $criteria->compare('updated_on', $this->updated_on !== null ? gmdate('Y-m-d H:i:s', strtotime($this->updated_on)) : null);

        unset($questions[0]);
        if ($question_id || $question_id == '') { // where question_id = '' search for all active, question_id = 0 search for all
            $query = implode(',', array_keys($questions));
            if (!empty($query)) {
                $criteria->condition .= " and question_id in (" . $query . ")";
            } else {
                $criteria->condition .= " and 1=0";
            }
        }
        $criteria->select = array('*');
        //paging
        $pages = new CPagination(eTicker::model()->{$status}()->count($criteria));
        $pages->pageSize = isset(Yii::app()->params['tickerAdmin']['perPage']) ? Yii::app()->params['tickerAdmin']['perPage'] : 20;
        $pages->applyLimit($criteria);
        //sort
        $sort = new CSort('eTicker');
        $sort->multiSort = false;
        //$sort->params = array('ajax' => 'admin-Question-table');//disable ajax load
        $sort->attributes = array(
            //'user.username',
            'ticker',
            'created_on',
            'frequency'
        );
        $sort->defaultOrder = 't.id DESC';
        $sort->applyOrder($criteria);
        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => $pages,
            'sort' => $sort,
        ));
    }

}
