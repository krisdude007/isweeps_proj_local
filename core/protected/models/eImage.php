<?php

/**
 * This is the model class for table "image".
 *
 * The followings are the available columns in table 'image':
 * @property integer $id
 * @property integer $user_id
 * @property string $filename
 * @property integer $watermarked
 * @property string $title
 * @property string $description
 * @property string $view_key
 * @property string $source
 * @property integer $is_avatar
 * @property integer $is_photoid
 * @property integer $to_facebook
 * @property integer $to_twitter
 * @property integer $arbitrator_id
 * @property string $status
 * @property string $status_date
 * @property string $created_on
 * @property string $updated_on
 *
 * The followings are the available model relations:
 * @property User $arbitrator
 * @property User $user
 * @property ImageDestination[] $imageDestinations
 * @property ImageRating[] $imageRatings
 * @property ImageView[] $imageViews
 */
class eImage extends Image {

    public $image;
    public $extendedStatus;
    public $image_count;

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Image the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public static function getExternalSourceUsername($image, $username) {
        $sourceArr = array('keek','Instagram','vine');
        if($image->title != '' && in_array($image->source, $sourceArr)) {
            return $image->title;
        }
        return $username;
    }

    public function filterByDates($startDate, $endDate) {
        return DateTimeUtility::filterByDates($this, $startDate, $endDate);
    }

    public function filterByWeek($filterDate) {
        return DateTimeUtility::filterByWeek($this, $filterDate);
    }

    public function getImagesOrderBy($order, $limit = 48)
    {
        $total = eImage::model()->with('user')->isNotAvatar()->accepted()->count();
        $criteria = new CDbCriteria();
        $criteria->limit = $limit;
        $criteria->select = '*, COUNT(imageViews.id) AS views, AVG(imageRatings.rating) as rating';
        $criteria->with = array('user', 'imageRatings', 'imageViews');
        $criteria->together = true;
        $criteria->condition = Yii::app()->params['image']['useExtendedFilters'] ? ("t.statusbit & " . Yii::app()->params['statusBit']['accepted'] . " AND (t.statusbit & " . Yii::app()->params['statusBit']['denied'] . ") = 0") : 'status="accepted"';
        $criteria->group = 't.id';
        $criteria->order = $order . ' DESC';

        if (Yii::app()->params['pagination']['enablePagination'] == true) {
            $pages = new CPagination($total);
            $pages->setPageSize(Yii::app()->params['pagination']['listPerPage']);
            $pages->applyLimit($criteria);
        }
        return self::model()->isNotAvatar()->accepted()->findAll($criteria);

    }

    public function orderBy($id, $order, $limit = 48) {
        if (Yii::app()->params['image']['useExtendedFilters']) {
            $sqlWhere = "i.statusbit & " . Yii::app()->params['statusBit']['accepted'] . "
                        AND (i.statusbit & " . Yii::app()->params['statusBit']['denied'] . ") = 0";
        } else {
            $sqlWhere = 'status = "accepted"';
        }

        if (is_null($id)) {
            $sql = '
                select view_key,i.user_id,filename,title,i.created_on,first_name,last_name,COUNT(iv.id) AS views, AVG(ir.rating) as rating
                    from image i
                    LEFT JOIN user u ON u.id = i.user_id
                    LEFT JOIN image_rating ir on ir.image_id = i.id
                    LEFT JOIN image_view iv on iv.image_id = i.id
                    where ' . $sqlWhere . ' and ( is_avatar=0 and title!="avatar" )
                    group by i.id
            ';
        } else {
            $sql = '
                select view_key,i.user_id,filename,title,i.created_on,first_name,last_name,COUNT(iv.id) AS views, AVG(ir.rating) as rating
                    from image i
                    LEFT JOIN user u ON u.id = i.user_id
                    LEFT JOIN image_rating ir on ir.image_id = i.id
                    LEFT JOIN image_view iv on iv.image_id = i.id
                    where ' . $sqlWhere . ' and (is_avatar=0 and title!="avatar")
                    and i.user_id = "%d"
                    group by i.id
            ';
            $sql = sprintf($sql, $id);
        }
        switch ($order) {
            case 'views':
                $sql .= 'order by views desc LIMIT ' . $limit;
                $images = Yii::app()->db->createCommand($sql)->queryAll(true);
                $images = json_decode(json_encode($images));
                break;
            case 'rating':
                $sql .= 'order by rating desc LIMIT ' . $limit;
                $images = Yii::app()->db->createCommand($sql)->queryAll(true);
                $images = json_decode(json_encode($images));
                break;
            default:
                $sql .= 'LIMIT ' . $limit;
                break;
        }
        return $images;
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        $defaultLabels = CMap::mergeArray(parent::attributeLabels(), array(
                    'id' => 'ID',
                    'user_id' => 'User',
                    'entity_id' => 'Entity',
                    'filename' => 'Filename',
                    'image' => 'Image',
                    'watermarked' => 'Watermarked',
                    'title' => 'Title',
                    'description' => 'Description',
                    'view_key' => 'View Key',
                    'source' => 'Source',
                    'is_avatar' => 'Is Avatar',
                    'to_facebook' => 'To Facebook',
                    'to_twitter' => 'To Twitter',
                    'arbitrator_id' => 'Arbitrator',
                    'status' => 'Status',
                    'status_date' => 'Status Date',
                    'created_on' => 'Created On',
                    'updated_on' => 'Updated On',
        ));

        $extendedLabels = array(
            'statusbit' => 'Statusbit',
            'extendedStatus' => 'ExtendedStatus',
            'title' => 'Photo Title',
        );

        if (Yii::app()->params['image']['useExtendedFilters']) {
            return CMap::mergeArray($defaultLabels, $extendedLabels);
        } else {
            return $defaultLabels;
        }
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        $extendedRules = array(
            //bit value 128 for "new" and 16 for "new_tv"
            array('statusbit', 'default', 'value' => 144, 'on' => 'insert'),
            array('extendedStatus', 'safe')
        );

        $defaultRules = array(
            array('title, description', 'filter', 'filter' => array($obj = new CHtmlPurifier(), 'purify')),
            array('source', 'required'),
            //array('image','required', 'on'=>'insert', 'message'=>Yii::t('youtoo','Image cannot be blank')),
            array('title', 'required', 'on' => 'insert,update', 'message'=>Yii::t('youtoo','Title cannot be blank')),
            array('created_on, updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'insert'),
            array('updated_on', 'default', 'value' => date("Y-m-d H:i:s"), 'setOnEmpty' => false, 'on' => 'update'),
            array('status', 'default', 'value' => 'new', 'on' => 'insert'),
            array('filename', 'unique', ),
            // a form model should be handling this, .. for now there is none, and no validation is set against uploading other image types such as bitmaps, so reenabled validation in model.
            //array('image', 'file', 'types' => Yii::app()->params['custom_params']['image_upload_filetype'],'maxSize'=>30 * 1024 * 1024,'tooLarge'=>'The File is Too large to be uploaded.','wrongType'=>Yii::app()->params['custom_params']['error_invalid_type'], 'on'=>'insert'),
            array('view_key', 'default', 'value' => md5(uniqid('', true)), 'on' => 'insert'),
            array('user_id, entity_id, watermarked, to_facebook, to_twitter, arbitrator_id, is_avatar', 'numerical', 'integerOnly' => true),
            array('title, view_key, source, description', 'length', 'max' => 255),
            array('status', 'length', 'max' => 8),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, user_id, filename, watermarked, title, description, view_key, source, to_facebook, to_twitter, arbitrator_id, status, is_avatar, status_date, created_on, updated_on', 'safe', 'on' => 'search'),
        );

        if (Yii::app()->params['image']['useExtendedFilters']) {
            return CMap::mergeArray($defaultRules, $extendedRules);
        } else {
            return $defaultRules;
        }
    }

    public function scopes() {
        $defaultScopes1 = array(
            'recent' => array('order' => '`t`.`id` desc'),
            'byViews' => array('order' => '`t`.views desc'),
            'rating' => array('order' => '`t`.rating desc'),
            'isAvatar' => array('condition' => 'is_avatar="1"'),
            'isPhotoId' => array('condition' => 'is_avatar="2"'),
            'isNotAvatar' => array('condition' => '( is_avatar=0 and title!="avatar" )'),
            'isNotPhotoId' => array('condition' => '( is_avatar < 2 and title!="photoId" )'),
            'all' => array('condition' => '')
        );

        $defaultScopes2 = array(
            'new' => array('condition' => '`t`.status="new"'),
            'accepted' => array('condition' => '`t`.status="accepted"'),
            'denied' => array('condition' => '`t`.status="denied"'),
        );

        if (Yii::app()->params['image']['useExtendedFilters']) {
            $extendedScopes = array(
                'new' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['new']),
                'accepted' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['accepted'] . "
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['denied'] . ") = 0"),
                'denied' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['denied']),
                'newtv' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['newTv']),
                'acceptedtv' => Utility::getAcceptedTVScope(Yii::app()->params['image']['extendedFilterLabels']),
                'deniedtv' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['deniedTv']),
                'newsup1' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['acceptedTv'] . "
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['deniedTv'] . ") = 0
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['acceptedSuperAdmin1'] . ") = 0"), //(accepted tv) and (not deniedtv) and (not accepted sup1)
                'newsup2' => array('condition' => "`t`.statusbit & " . Yii::app()->params['statusBit']['acceptedTv'] . "
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['deniedTv'] . ") = 0
                                                   AND (`t`.statusbit & " . Yii::app()->params['statusBit']['acceptedSuperAdmin2'] . ") = 0"), //(accepted tv) and (not deniedtv) and (not accepted sup2)
                'statustv' => Utility::getStatusTVScope(Yii::app()->params['image']['extendedFilterLabels']),
            );

            return CMap::mergeArray($defaultScopes1, $extendedScopes);
        } else {
            return CMap::mergeArray($defaultScopes1, $defaultScopes2);
        }
    }

    public function getCountBySource()
    {
        $return = array();
        $criteria = new CDbCriteria;
        $criteria->select = 'source, count(id) as image_count';
        $criteria->group = 'source';
        if($data = self::model()->findAll($criteria)) {
            foreach($data as $row) {
                $return[$row->source] = $row->image_count;
            }
            return $return;
        }
        return false;
    }
    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'entity' => array(self::BELONGS_TO, 'eEntity', 'entity_id'),
            'arbitrator' => array(self::BELONGS_TO, 'eUser', 'arbitrator_id'),
            'user' => array(self::BELONGS_TO, 'eUser', 'user_id'),
            'imageDestinations' => array(self::HAS_MANY, 'eImageDestination', 'image_id'),
            'imageRatings' => array(self::HAS_MANY, 'eImageRating', 'image_id'),
            'userLocation'=>array(self::BELONGS_TO, 'eUserLocation','user_id'),
            'imageViews' => array(self::HAS_MANY, 'eImageView', 'image_id'),
            'rating' => array(self::STAT, 'eImageRating', 'image_id', 'select' => 'ROUND(AVG(rating))', 'group' => 'image_id'),
            'views' => array(self::STAT, 'eImageView', 'image_id', 'select' => 'COUNT(id)', 'group' => 'image_id'),
            'tagImages' => array(self::HAS_MANY, 'eTagImage', 'image_id'),
        );
    }

    public function afterSave() {
        if ($this->status == 'accepted' || Yii::app()->params['image']['useExtendedFilters'] && $this->extendedStatus['accepted']) {
            if ($this->to_twitter == 1) {
                $destination = eDestination::model()->findByAttributes(Array('destination' => 'twitter'));
                $sent = eImageDestination::model()->findByAttributes(Array('image_id' => $this->id, 'user_id' => $this->user_id, 'destination_id' => $destination->id));
                if (is_null($sent)) {
                    $text = Yii::app()->params['custom_params']['checkout_newimage'];
                    $text .= Yii::app()->createAbsoluteUrl('/viewimage/' . $this->view_key);
                    $dest = new eImageDestination;
                    $dest->image_id = $this->id;
                    $dest->user_id = $this->user_id;
                    $dest->destination_id = $destination->id;
                    $response = TwitterUtility::tweetAs($this->user_id, $text);
                    if (sizeof($response->errors) > 0) {
                        foreach ($response->errors as $i => $error) {
                            $dest->response .= $error->message;
                        }
                    } else {
                        $dest->response = $response->id_str;
                    }
                    $dest->save();
                }
            }
            if ($this->to_facebook == 1) {
                $destination = eDestination::model()->findByAttributes(Array('destination' => 'facebook'));
                $sent = eImageDestination::model()->findByAttributes(Array('image_id' => $this->id, 'user_id' => $this->user_id, 'destination_id' => $destination->id));
                if (is_null($sent)) {
                    $post = array(
                        'message' => Yii::app()->params['custom_params']['checkout_newimage'],
                        'link' => Yii::app()->createAbsoluteUrl('/viewimage/' . $this->view_key),
                    );
                    $dest = new eImageDestination;
                    $dest->image_id = $this->id;
                    $dest->user_id = $this->user_id;
                    $dest->destination_id = $destination->id;
                    $response = FacebookUtility::shareAs($this->user_id, $post);
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

    public function beforeSave() {
        if (Yii::app()->params['image']['useExtendedFilters']) {
            $this->statusbit = Utility::setStatusbit($this);
            $this->extendedStatus = Utility::setExtendedStatus($this);
        }
        return parent::beforeSave();
    }

    public function afterFind() {
        if (Yii::app()->params['image']['useExtendedFilters']) {
            $this->extendedStatus = Utility::setExtendedStatus($this);
        }
        return parent::afterFind();
    }

    public static function generateViewKey() {
        return md5(uniqid('', true) . time());
    }

    public static function insertRecord($keyValuePairs = array()) {

        if (count($keyValuePairs) > 0) {

            $image = new self();
            foreach ($keyValuePairs as $key => $value) {
                $image->{$key} = $value;
            }

            if ($image->save()) {
                return $image;
            }
        }

        return false;
    }


    public function getRecentImages()
    {
        return self::model()->findAll(array('limit'=>50,'order'=>'updated_on desc', 'condition'=>'status="accepted"'));
    }

}
