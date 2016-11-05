<?php

class AdminVideoController extends Controller {

    public $user;
    public $notification;
    public $layout = '//layouts/admin';

    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    public function accessRules() {
        return array(
            array('allow',
                'actions' => array(
                    // VIDEO ACTIONS
                    'index',
                    'upload',
                    'videoModal',
                    'videoDownload',
                    'videoModalThumbnails',
                    'videoModalHistory',
                    'videoSchedulerModal',
                    'videoSchedulerModalHistory',
                    'videoImportModal',
                    'ajaxVideoAddTags',
                    'ajaxVideoUpdateThumbnail',
                    'ajaxVideoUpdateStatus',
                    'ajaxVideoGetUsers',
                    'ajaxVideoGetAdmins',
                    'ajaxVideoFTP',
                    'videoFtp',
                    'ajaxVideoImport',
                    'ajaxVideoGetNetworkShowSchedule',
                    'ajaxVideoGetNetworkSpotSchedule',
                    'ajaxVideoFillNetworkSpot',
                    'ajaxVideoUnfillNetworkSpot',
                    'ajaxAmplifyPreview',
                    'ajaxAmplifyConcatenate',
                    'ajaxVideoSetDefaultRoll',
                    'ajaxVideoUnsetDefaultRoll',
                    'ajaxRotateVideo',
                    'ajaxVideoPreRoll',
                ),
                'expression' => '(Yii::app()->user->isAdmin())',
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Anything required on every page should be loaded here
     * and should also be made a class member.
     */
    function init() {
        parent::init();
        Yii::app()->setComponents(array('errorHandler' => array('errorAction' => 'admin/error',)));
        $this->user = ClientUtility::getUser();
        $this->notification = eNotification::model()->orderDesc()->findAllByAttributes(array('user_id' => Yii::app()->user->id));
    }

    /**
     *
     *
     * VIDEO ACTIONS
     * This section contains everything required for the video section of the admin
     *
     *
     */
    public function actionAjaxRotateVideo() {

        $this->layout = false;
        $directions = array('left', 'right');
        $videoId = trim($_POST['videoId']);
        $direction = trim($_POST['direction']);
        $video = eVideo::model()->findByPk($videoId);
        if (in_array($direction, $directions)) {
            if (!empty($video)) {
                $ext = VideoUtility::getVideoFileExtention($video->processed);
                $videoPath = basename(Yii::app()->params['paths']['video']) . '/' . $video->filename . $ext;
                $filename = $video->user_id . "_" . md5(uniqid('', true) . $video->filename);
                $newVideoPath = Yii::app()->params['paths']['video'] . "/" . $filename . $ext;
                $newVideoPathWeb = Yii::app()->request->baseUrl . '/' . basename(Yii::app()->params['paths']['video']) . '/' . $filename;
                VideoUtility::rotateVideo($videoPath, $newVideoPath, $direction);
                $video->filename = $filename;
                $video->arbitrator_id = Yii::app()->user->id;
                $video->updated_on = new CDbExpression('NOW()');
                $video->save();
                sleep(1);
                echo json_encode(array('success' => 'true', 'message' => 'Video rotated.', 'filename' => $newVideoPathWeb));
            } else {
                echo json_encode(array('success' => 'false', 'message' => 'Unable to locate video in db.'));
            }
        } else {
            echo json_encode(array('success' => 'false', 'message' => 'Invalid rotation direction.'));
        }
    }

    public function actionUpload() {

        $model = new FormVideoUpload;

        if (isset($_POST['FormVideoUpload'])) {
            $model->attributes = $_POST['FormVideoUpload'];
            $model->video = CUploadedFile::getInstance($model, 'video');
            if (empty($model->is_ad)) {
                $model->is_ad = 0;
            }
            if ($model->validate()) {

                $encoderResult = VideoUtility::encode('UP', $model->video->extensionName, $model->video);

                if ($encoderResult != false) {

                    // add record
                    $record = array();
                    $record['filename'] = $encoderResult['filename'];
                    $record['thumbnail'] = $encoderResult['filename'];
                    if ($model->question_id != '0') {
                        $record['question_id'] = $model->question_id;
                    }

                    $record['arbitrator_id'] = Yii::app()->user->getId();
                    $record['user_id'] = Yii::app()->user->getId();
                    $record['processed'] = 1;
                    $record['source'] = 'upload';
                    $record['title'] = $model->title;
                    $record['description'] = Yii::app()->facebook->videoShareText;

                    if ($model->is_ad == '1') {
                        $record['title'] = 'AD: ' . $model->title;
                        $record['description'] = 'amplify ad';
                        $record['source'] = 'ad';
                    }

                    if ($model->company_name != '' && $model->company_email != '') {
                        $record['company_name'] = $model->company_name;
                        $record['company_email'] = $model->company_email;
                    }

                    $record['status'] = 'accepted';
                    $record['view_key'] = eVideo::generateViewKey();
                    $record['duration'] = $encoderResult['duration'];
                    $record['watermarked'] = $encoderResult['watermarked'];
                    $record['frame_rate'] = $encoderResult['fileInfo']['video']['frame_rate'];
                    $inserted = eVideo::insertRecord($record);

                    if ($inserted) {

                        // handle tags
                        $tags = $model->tags;
                        if ($tags != '') {
                            // if more than one tag was passed, explode them into an array
                            if (strstr($tags, ',')) {
                                $tags = explode(',', $tags);
                            }
                            // otherwise handle a single tag
                            else {
                                $tags = array($tags);
                            }

                            foreach ($tags as $tag) {

                                // see if tag already exist. If it does, grab its id
                                $tagModel = Tag::model()->findByAttributes(array('title' => $tag));

                                if (!is_null($tagModel)) {
                                    $tagVideoModel = new TagVideo();
                                    $tagVideoModel->tag_id = $tagModel->id;
                                    $tagVideoModel->video_id = $inserted->id;
                                    $tagVideoModel->save();
                                } else {
                                    // If not, create a new tag and grab id
                                    $tagModel = new Tag();
                                    $tagModel->title = $tag;
                                    $tagModel->save();

                                    // add record to tag video
                                    $tagVideoModel = new TagVideo();
                                    $tagVideoModel->tag_id = $tagModel->id;
                                    $tagVideoModel->video_id = $inserted->id;
                                    $tagVideoModel->save();
                                }
                            }
                        }

                        Yii::app()->user->setFlash('success', 'Video upload complete.');
                    } else {
                        Yii::app()->user->setFlash('error', 'Unable to insert video record.');
                    }
                } else {
                    Yii::app()->user->setFlash('error', 'Unable to encode video.');
                }
            } else {

                $error = $model->getErrors();
                $error = $error['video'];
                Yii::app()->user->setFlash('error', $error[0]);
            }

            $this->redirect('/adminVideo');
        }
    }

    public function actionIndex($perPage = '') {


        // Ensure a per page value is set
        if (empty($perPage)) {
            $perPage = Yii::app()->params['videoAdmin']['perPage'];
        }

        // set to null
        $videos = null;
        $types = null;

        // create video filter form
        $filterVideoModel = new FormFilterVideo;
        $formVideoUploadModel = new FormVideoUpload;

        // get questions for filter dropdown
        // pass to Utility in order to get key value pairs for dropdown
        $questionList = Array();
        $questionList[] = 'All';
        $questions = eQuestion::model()->current()->video()->orderByCreatedDesc()->findAll();
        foreach ($questions as $question) {
            //if ($question->videoTally() > 0) { //we need to show all questions that are currently active
            $questionList[$question->id] = $question->question;
            //}
        }

        $questions = $questionList;
        $questionList[0] = 'Select a question (Not required if ad)';
        $questionsUpload = $questionList;


        $statuses = VideoUtility::getStatuses();
        $sources = VideoUtility::getSources();
        if (Yii::app()->params['enableContestants'] === true) {
            $heros = VideoUtility::getHeros();
        }

        if (Yii::app()->params['enableYtFunctionality'] === true) {
            $types = VideoUtility::getTypes();
        }
        $perPageOptions = VideoUtility::getPerPageOptions();
        $criteria = new CDbCriteria;
        if (!Yii::app()->user->isSuperAdmin()) {
            $criteria->addCondition('title !=""');
        }

        $columnConditions = array();
        if (isset($_GET['FormFilterVideo'])) {

            $filterVideoModel->attributes = $_GET['FormFilterVideo'];

            if ($filterVideoModel->validate()) {
                $status = $filterVideoModel->status;
                if (Yii::app()->params['enableYtFunctionality'] === true) {
                    $type = $filterVideoModel->type;
                    if (!empty($type) && $type != 'all') {
                        switch ($type) {
                            case "peoplemercial":
                                $criteria->condition = 'duration !=0 and duration >= 25';
                                break;
                            case "famespot":
                                $criteria->condition = 'duration !=0 and duration < 25 ';
                                break;
                        }
                    }
                }

                if ($filterVideoModel->source != 'all') {
                    $columnConditions['source'] = $filterVideoModel->source;
                }

                // Exclude ads from results
                if ($filterVideoModel->source != 'ad') {
                    $criteria->addNotInCondition('source', array('ad'));
                }

                if ($filterVideoModel->question != '0') {
                    $columnConditions['question_id'] = $filterVideoModel->question;
                }

                if ($filterVideoModel->hero != '0') {
                    $columnConditions['hero_user_id'] = $filterVideoModel->hero;
                }

                if (!empty($filterVideoModel->user_id)) {
                    $columnConditions['user_id'] = $filterVideoModel->user_id;
                }
                
                

                if (!empty($filterVideoModel->tags)) {
                    // not ideal, but will work for now
                    $tags = explode(' ', $filterVideoModel->tags);
                    $criteria2 = new CDbCriteria;
                    $criteria2->addInCondition('title', $tags);
                    $tags = eTag::model()->with('tagVideos')->findAll($criteria2);

                    $tagVideoArr = array();
                    if (!is_null($tags)) {
                        foreach ($tags as $tag) {
                            foreach ($tag->tagVideos as $tagVideos) {
                                $tagVideoArr[] = $tagVideos->video_id;
                            }
                        }
                    }

                    if (count($tagVideoArr) > 0) {
                        $criteria->addInCondition('id', $tagVideoArr);
                    }
                }
                
                //views
                if (!empty($filterVideoModel->viewsDateStart) && !empty($filterVideoModel->viewsDateStop)) {
                    $fmtStartDate = date('Y-m-d H:i:s', strtotime($filterVideoModel->viewsDateStart));
                    $fmtStopDate = date('Y-m-d H:i:s', strtotime($filterVideoModel->viewsDateStop));
                    $views = eVideoView::model()->groupByVideo()->filterByDates($fmtStartDate, $fmtStopDate)->findAll();
                    
                    $minViews = (int)$filterVideoModel->minViews;
                    if($minViews < 1) {
                        $minViews = 1;
                    }
                            
                    $viewVideoArr = array();
                    if (!is_null($views)) {
                        foreach ($views as $view) {
                            if((int)$view->viewCount >= $minViews) {
                                $viewVideoArr[] = $view->video_id;
                            }
                        }
                    }

                    //if (count($viewVideoArr) > 0) {
                        $criteria->addInCondition('id', $viewVideoArr);
                    //}
                }
                //end views

                if (count($columnConditions) > 0) {
                    $criteria->addColumnCondition($columnConditions);
                }

                if (!empty($filterVideoModel->dateStart) && !empty($filterVideoModel->dateStop)) {
                    $fmtStartDate = date('Y-m-d H:i:s', strtotime($filterVideoModel->dateStart));
                    $fmtStopDate = date('Y-m-d H:i:s', strtotime($filterVideoModel->dateStop));
                    $criteria->addBetweenCondition('created_on', gmdate('Y-m-d H:i:s', strtotime($fmtStartDate)), gmdate('Y-m-d H:i:s', strtotime($fmtStopDate)));
                }

                $perPage = $filterVideoModel->perPage;
            }
        } else {
            $defaultStatus = Utility::getDefaultStatus(Yii::app()->params['video']['extendedFilterLabels']);

            $filterVideoModel->perPage = $perPage;
            $filterVideoModel->status = $defaultStatus;
            $filterVideoModel->source = 'all';
            $status = $defaultStatus;
        }

        $videosTotal = eVideo::model()->processed()->{$status}()->count($criteria);
        $pages = new CPagination($videosTotal);
        $pages->pageSize = $perPage;
        $pages->applyLimit($criteria);

        if ($status == '') {
            $videos = eVideo::model()->processed()->recent()->findAll($criteria);
        } else {
            $videos = eVideo::model()->with('views')->processed()->recent()->{$status}()->findAll($criteria);
        }
        
        
        //var_dump($videos);
        //exit;
        
        //$videos = eVideo::model()->getVideosOrderBy("views", 48);

        // - gstringer 20130813
        // todo - this is just a quick hack in order to make sure that we
        // store frame rate and duration. This should happen when we capture
        // the video but we have no idea when the video has finished encoding
        // within wowza. We need to move this to the video controller at some point.

        foreach ($videos as $v) {

            if (empty($v->duration) || empty($v->frame_rate)) {
                $fileName = Yii::app()->params['paths']['video'] . '/' . $v->filename . VideoUtility::getVideoFileExtention($v->processed);
                $fileInfo = VideoUtility::getID3Info($fileName);
                $v->duration = isset($fileInfo['playtime_seconds']) ? $fileInfo['playtime_seconds'] : null;
                $v->frame_rate = isset($fileInfo['video']['frame_rate']) ? $fileInfo['video']['frame_rate'] : null;
                $v->save();
            }
        }

        $array = array(
            'questions' => $questions,
            'questionsUpload' => $questionsUpload,
            'statuses' => $statuses,
            'sources' => $sources,
            'perPageOptions' => $perPageOptions,
            'filterVideoModel' => $filterVideoModel,
            'videos' => $videos,
            'videosTotal' => $videosTotal,
            'pages' => $pages,
            'formVideoUploadModel' => $formVideoUploadModel,
        );

        if (Yii::app()->params['enableYtFunctionality'] === true) {
            $array['types'] = $types;
        }

        if (Yii::app()->params['enableContestants'] === true) {
            $array['heros'] = $heros;
        }

        $this->render('index', $array);
    }

    public function actionVideoModal($id, $currentStatus) {
        $this->layout = false;
        $video = eVideo::model()->with('user', 'user:userPhones:primary', 'user:userLocations:primary', 'user:userTwitters')->findByPk($id);
        $videoTags = eTagVideo::model()->findAllByAttributes(array('video_id' => $id));

        $tagArr = array();
        foreach ($videoTags as $videoTag) {
            $tagArr[] = $videoTag->tag->title;
        }

        $tagModel = new eTag();
        $tagModel->title = implode(',', $tagArr);

        $prePostRolls = eVideo::model()->findAllByAttributes(Array('status' => 'accepted', 'description' => 'amplify ad'));
        $videoSelections = $prePostRolls;
        $prePostRolls = Utility::resultToKeyValue($prePostRolls, 'id', 'title');

        $this->render('videoModal', array(
            'id' => $id,
            'currentStatus' => $currentStatus,
            'video' => $video,
            'videoTags' => $videoTags,
            'tagModel' => $tagModel,
            'prePostRolls' => $prePostRolls,
            'videoSelections' => array_reverse($videoSelections),
                )
        );
    }

    public function actionVideoModalThumbnails($id) {
        $this->layout = false;
        $video = eVideo::model()->findByPk($id);
        $thumbnails = null;

        if (!is_null($video)) {
            $thumbnails = VideoUtility::generateThumbnailsForVideo($video->filename);
        }

        $this->render('videoModalThumbnails', array(
            'video' => $video,
            'thumbnails' => $thumbnails));
    }

    public function actionAjaxVideoUpdateThumbnail() {

        $videoId = trim($_POST['videoId']);
        $thumbnail = trim($_POST['thumbnail']);
        $video = eVideo::model()->findByPk($videoId);

        if (!is_null($video)) {
            $video->thumbnail = $thumbnail;
            $video->save();
        }
    }

    public function actionVideoModalHistory($id) {
        $this->layout = false;
        $video = eVideo::model()->findByPk($id);
        if (!is_null($video)) {
            $criteria = new CDbCriteria;
            $criteria->condition = "action like 'adminVideo/ajaxVideoUpdateStatus/%videoId=" . $id . "%'";
            $criteria->order = 't.id desc';
            $audits = eAudit::model()->with('user')->findAll($criteria);
            $statuses = VideoUtility::getStatuses();
            $i = 0;
            $parsedAudits = array();
            foreach ($audits as $audit) {
                $action = $audit->action;
                $parsed_url = parse_url($action);
                parse_str($parsed_url['query']); //status,currentStatus,vidoeId
                $parsedAudits[$i]['created_on'] = $audit->created_on;
                $parsedAudits[$i]['status'] = AuditUtility::translate($audit->action);
                $parsedAudits[$i]['username'] = $audit->user->username;
                $i++;
            }
            $this->renderPartial('videoModalHistory', array('parsedAudits' => $parsedAudits));
        }
    }

    public function actionAjaxVideoAddTags() {

        $this->layout = false;
        $tags = trim($_POST['tags']);
        $videoId = trim($_POST['videoId']);

        // since $tags will always be set, check for data the old fashioned way
        if ($tags != '') {

            // if more than one tag was passed, explode them into an array
            if (strstr($tags, ',')) {
                $tags = explode(',', $tags);
            }
            // otherwise handle a single tag
            else {
                $tags = array($tags);
            }

            // we need to delete any video tags that exist in the db, but were deleted
            // from this request
            $taggedVideos = TagVideo::model()->with('tag')->findAllByAttributes(array('video_id' => $videoId));

            if (!is_null($taggedVideos)) {
                foreach ($taggedVideos as $tagVideo) {
                    if (!in_array($tagVideo->tag->title, $tags)) {
                        // delete tagVideo record since it was deleted from the input field
                        $tagVideo->delete();
                    }
                }
            }

            foreach ($tags as $tag) {

                // see if tag already exist. If it does, grab its id
                $tagModel = Tag::model()->findByAttributes(array('title' => $tag));

                if (!is_null($tagModel)) {
                    $tagId = $tagModel->id;

                    // see if it already exist in the tag video table. If not, add it.
                    $tagVideo = TagVideo::model()->findByAttributes(array('tag_id' => $tagId, 'video_id' => $videoId));

                    if (is_null($tagVideo)) {
                        // add record to tag video
                        $tagVideoModel = new TagVideo();
                        $tagVideoModel->tag_id = $tagId;
                        $tagVideoModel->video_id = $videoId;
                        $tagVideoModel->save();
                    }
                } else {
                    // If not, create a new tag and grab id
                    $tagModel = new Tag();
                    $tagModel->title = $tag;
                    $tagModel->save();

                    // add record to tag video
                    $tagVideoModel = new TagVideo();
                    $tagVideoModel->tag_id = $tagModel->id;
                    $tagVideoModel->video_id = $videoId;
                    $tagVideoModel->save();
                }
            }
        } else {
            // remove all tags for video
            TagVideo::model()->deleteAllByAttributes(array('video_id' => $videoId));
        }
    }

    public function actionVideoSchedulerModal($video_id = null) {
        $this->layout = null;

        if (!is_null($video_id)) {
            $video = eVideo::model()->findByPk($video_id);
        } else {
            $video = null;
        }

        $networkShowsPrepend = array('0' => 'All');
        $networkShows = Utility::resultToKeyValue(eNetworkShow::model()->ascending()->findAll(), 'id', 'name');
        $networkShows = $networkShowsPrepend + $networkShows;
        $filterVideoSchedulerModel = new FormFilterVideoScheduler;

        $this->render('videoSchedulerModal', array('video' => $video,
            'networkShows' => $networkShows,
            'filterVideoSchedulerModel' => $filterVideoSchedulerModel));
    }

    public function actionVideoSchedulerModalHistory() {
        $criteria = new CDbCriteria;
        $criteria->condition = "destination_id=3";
        $criteria->order = 't.id desc';

        $pages = new CPagination(eVideoDestination::model()->count($criteria));
        $pages->pageSize = Yii::app()->params['videoSchedulerModalHistory']['perPage'];
        $pages->applyLimit($criteria);
        $videoDestinations = eVideoDestination::model()->with('video', 'video:user', 'user')->findAll($criteria);
        $this->renderPartial('videoSchedulerModalHistory', array(
            'videoDestinations' => $videoDestinations,
            'pages' => $pages));
    }

    public function actionAjaxVideoFillNetworkSpot() {

        $response = 'false';
        $this->layout = false;
        $network_show_schedule_id = trim($_POST['network_show_schedule_id']);
        $video_id = trim($_POST['video_id']);

        $network_show_schedule = eNetworkShowSchedule::model()->findByPk($network_show_schedule_id);
        $video = eVideo::model()->findByPk($video_id);

        if (!is_null($video) && !is_null($network_show_schedule)) {
            $network_show_schedule->user_id = Yii::app()->user->id;
            $network_show_schedule->video_id = $video_id;
            $network_show_schedule->submitted_on = date("Y-m-d H:i:s");
            $network_show_schedule->spot_filename = 'YTU_' . $network_show_schedule->spot_type . $network_show_schedule->spot_number . '.mxf';
            $network_show_schedule->spot_available = 0;
            $network_show_schedule->save();

            /*
             * $convertedToMov = VideoUtility::ffmpegMp4ToMov();
             * $sent = VideoUtility::curlVideoToFlipFactory();
             */

            $fileNameMp4 = $video->filename . Yii::app()->params['video']['postExt'];
            $fileNameMov = "YTU_" . $network_show_schedule->spot_type . $network_show_schedule->spot_number . Yii::app()->params['video']['flipExt'];
            $filePathMp4 = Yii::app()->params['paths']['video'] . "/" . $fileNameMp4;
            $filePathMov = Yii::app()->params['paths']['video'] . "/" . $fileNameMov;

            if (VideoUtility::ffmpegMp4ToMov($filePathMp4, $filePathMov)) {

                if (VideoUtility::curlVideoToFlipFactory($filePathMov, $fileNameMov)) {
                    $response = 'Spot has been filled. Video has been sent to Flip Factory.';
                } else {
                    $response = 'Unable to send video to Flip Factory.';
                }
            } else {
                $response = 'Unable to convert .mp4 to .mov.';
            }
        }

        echo json_encode(array('response' => $response));
    }

    public function actionAjaxVideoUnfillNetworkSpot() {
        // remove video id and user id from spot and set saved on = 0000-00-00 00:00:00
        $this->layout = false;
        $network_show_schedule_id = trim($_POST['network_show_schedule_id']);
        $network_show_schedule = eNetworkShowSchedule::model()->findByPk($network_show_schedule_id);

        if (!is_null($network_show_schedule)) {
            $network_show_schedule->user_id = new CDbExpression('NULL');
            $network_show_schedule->video_id = new CDbExpression('NULL');
            $network_show_schedule->submitted_on = new CDbExpression('NULL');
            $network_show_schedule->updated_on = date("Y-m-d H:i:s");
            $network_show_schedule->spot_filename = '';
            $network_show_schedule->spot_available = 1;
            $network_show_schedule->save();
        } else {

            echo json_encode(array('error' => $network_show_schedule->getError()));
        }
    }

    public function actionAjaxVideoGetNetworkShowSchedule($spot_type = 'FS', $network_show_id = null) {
        /*
          SELECT
          DISTINCT show_on,
          network_show.name,
          COUNT( network_show_schedule.id ) AS available_slots,
          spot_available,
          CONCAT(spot_type, spot_number) as house_number
          FROM  `network_show_schedule`
          LEFT JOIN network_show ON network_show.id = network_show_schedule.network_show_id
          WHERE spot_type =  'FS'
          AND spot_available = 1
          AND DATE(DATE_ADD(show_on, INTERVAL 1 DAY)) > CURRENT_DATE
          GROUP BY show_on
          ORDER BY show_on ASC
         */
        $this->layout = null;

        $attributes = array();
        $attributes['spot_type'] = $spot_type;
        $attributes['spot_available'] = 1;

        // check for filter
        if (!is_null($network_show_id)) {
            $attributes['network_show_id'] = $network_show_id;
        }

        $networkShowSchedule = eNetworkShowSchedule::model()->with('networkShow', 'user')->showSchedule()->findAllByAttributes($attributes);

        $output = array(
            "aaData" => array()
        );

        foreach ($networkShowSchedule as $nss) {

            $date = strtotime($nss->show_on);
            $runDateTime = date('Y-m-d H:i:s', $date);
            $runDate = date('m-d-Y', $date);
            $runTime = date('g:i:s a', $date);
            $timeRemainingToSpot = eNetworkShowSchedule::timeRemaining($runDateTime);
            $color = eNetworkShowSchedule::getSpotColor($timeRemainingToSpot);


            if ($timeRemainingToSpot != false) {

                $output['aaData'][] = array($color,
                    $nss->networkShow->name,
                    $runDate,
                    $runTime,
                    $nss->available_slots,
                    $timeRemainingToSpot,
                    strtotime($nss->show_on),
                    $nss->networkShow->id,
                    $nss->spot_type,
                    $nss->id,
                );
            }
        }

        echo json_encode($output);
    }

    public function actionAjaxVideoGetNetworkSpotSchedule($show_on, $spot_type, $network_show_id) {
        /*
          SELECT *
          FROM  `network_show_schedule`
          WHERE  `network_show_id` =11
          AND  `spot_type` =  'FS'
          AND  `show_on` =  '2013-11-25 15:00:00'
          LIMIT 0 , 30
         */

        $this->layout = null;
        $show_on = date('Y-m-d H:i:s', $show_on);
        $attributes = array('show_on' => gmdate('Y-m-d H:i:s', strtotime($show_on)),
            'spot_type' => $spot_type,
            'network_show_id' => $network_show_id);

        //print_r($attributes); exit;
        $networkSpotSchedule = eNetworkShowSchedule::model()->with('networkShow', 'user', 'video')->findAllByAttributes($attributes);

        $output = array(
            "aaData" => array()
        );

        $position = 1;
        $spotTimeTmp = null;
        $defaultDateTime = '0000-00-00 00:00:00';
        $dateTimeFormat = 'Y-m-d g:i:s a';
        $uiDateTimeFormat = 'm-d-Y g:i:s a';
        $timeFormat = 'g:i:s a';

        foreach ($networkSpotSchedule as $nss) {
            if ($nss->submitted_on != $defaultDateTime) {
                $submitted_date = date($uiDateTimeFormat, strtotime($nss->submitted_on));
            } else {
                $submitted_date = $defaultDateTime;
            }

            // calculate run time
            if ($spotTimeTmp != $nss->spot_on) {
                $spotTimeTmp = $nss->spot_on;
                $spotRunDateTime = date($dateTimeFormat, strtotime($spotTimeTmp));
                $spotRunTime = date($timeFormat, strtotime($spotTimeTmp));
            } else {
                $spotRunDateTime = date($dateTimeFormat, strtotime($spotTimeTmp) + $nss->spot_length);
                $spotRunTime = date($timeFormat, strtotime($spotTimeTmp) + $nss->spot_length);
            }

            $spotFilled = (is_null($nss->video_id) && is_null($nss->user_id)) ? 0 : 1;
            $spotThumbnailSrc = ($spotFilled && isset($nss->video->thumbnail)) ? Yii::app()->request->getBaseUrl(true) . '/' . basename(Yii::app()->params['paths']['video']) . '/' . $nss->video->thumbnail . Yii::app()->params['video']['imageExt'] : '';
            $spot_length_exp = explode(':', $nss->spot_length);
            $spot_length = '';

            foreach ($spot_length_exp as $sl) {
                if ($sl != '00') {
                    $spot_length = $spot_length . ':' . $sl;
                }
            }

            $output['aaData'][] = array($position,
                $spotFilled,
                ($nss->spot_filename == '') ? '' : $nss->spot_filename,
                (isset($nss->user->username)) ? $nss->user->username : '',
                $spotRunTime,
                $spot_length,
                eNetworkShowSchedule::timeRemaining($spotRunDateTime),
                $nss->spot_type . $nss->spot_number,
                $submitted_date,
                $nss->id,
                (isset($nss->video->id)) ? $nss->video->id : '',
                $spotThumbnailSrc
            );
            ++$position;
        }

        echo json_encode($output);
    }

    public function actionVideoDownload($id) {
        $video = eVideo::model()->findByPk($id);
        if (!is_null($video)) {
            $file = Yii::app()->params['paths']['video'] . "/{$video->filename}" . Yii::app()->params['video']['postExt'];
            //return Yii::app()->getRequest()->sendFile(basename($file), @file_get_contents($file));
            if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($file));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                //ob_clean();
                //flush();
                //ob_end_flush();
                //readfile($file);

                set_time_limit(0);
                $readfile = @fopen($file, "rb");
                while(!feof($readfile))
		{
			print(@fread($readfile, 1024*8));
			ob_flush();
			flush();
			if (connection_status()!=0)
			{
				@fclose($readfile);
				exit;
			}
		}

		// file save was a success
		@fclose($file);
		exit;
            }
        } else {
            throw new CHttpException(404, 'The requested page does not exist.');
            return false;
        }
    }

    public function actionAjaxVideoImport($source) {

        $questions = eQuestion::model()->video()->current()->findAll();
        foreach ($questions as $question) {
            $question->hashtag = $question->hashtag ? $question->hashtag : Yii::app()->params['video']['defaultHashtag'];
            // we don't want to import for empty hashtag
            if ($question->hashtag) {
                $videoImportUtility = new VideoImportUtility($source, $question->hashtag, 15);
                if ($videoImportUtility) {
                    $videoImportUtility->importVideos();
                }
            } else {
                echo 'Hash tag should not be empty for "' . $question->question . '"  !!!';
                Yii::app()->end();
            }
        }
    }

    public function actionVideoImportModal() {

        $this->layout = null;
        $formVineModel = new FormVideoImportVine();

        if (isset($_POST['ajax']) && $_POST['ajax'] === 'video-import-vine') {
            echo CActiveForm::validate($formVineModel);
            Yii::app()->end();
        }

        if (isset($_POST['FormVideoImportVine'])) {

            $formVineModel->attributes = $_POST['FormVideoImportVine'];

            if ($formVineModel->validate()) {
                $videoImportUtility = new VideoImportUtility($formVineModel->source, $formVineModel->categoryIdentifier, $formVineModel->numVideos);

                if ($videoImportUtility) {
                    // import videos here
                    $videoImportUtility->importVideos();
                }
            } else {
                Yii::app()->user->setFlash('error', 'There was an error while importing videos.');
            }

            $this->redirect('/adminVideo');
        }

        $this->render('videoImportModal', array('formVineModel' => $formVineModel));
    }

    // ajax method for user filter
    public function actionAjaxVideoGetUsers() {

        $this->layout = null;
        $email = trim($_GET['term']);
        $criteria = new CDbCriteria();
        $criteria->addSearchCondition('username', $email, true);
        $users = eUser::model()->findAll($criteria);
        $rows = array();

        if ($users) {
            foreach ($users as $u) {
                $rows[] = array('label' => $u->username,
                    'id' => $u->id);
            }
        }

        echo json_encode($rows);
    }
    
    public function actionAjaxVideoPreRoll() {
        $this->layout = false;
        
        $isPreRoll = (int)trim($_POST['preRoll']);
        $videoID = (int)trim($_POST['videoId']);
        
        $video = eVideo::model()->findByPk($videoID);
        
        if(!is_null($video)) {
            $video->is_preroll = $isPreRoll;
            $video->save(false);
            echo json_encode(array('success' => 'true'));
        } else {
            echo json_encode(array('success' => 'false'));
        }
    }

    public function actionAjaxVideoUpdateStatus() {

        $this->layout = false;
        $status = trim($_POST['status']);
        $currentStatus = trim($_POST['currentStatus']);
        $videoId = trim($_POST['videoId']);
        $optSendEmail = (isset($_POST['optSendEmail'])) ? $_POST['optSendEmail'] : '';

        $video = eVideo::model()->findByPk($videoId);

        if (!is_null($video)) {
            if (Yii::app()->params['video']['useExtendedFilters']) {
                $video->extendedStatus = Utility::updateExtendedStatus($currentStatus, $status, $video);
            } else {
                $video->status = $status;
            }
            $video->arbitrator_id = Yii::app()->user->id;
            $video->status_date = date("Y-m-d H:i:s");
            $video->save(false);

            $statusList = Yii::app()->params['video']['extendedFilterLabels'];

            if ($video->status == 'accepted' || (Yii::app()->params['video']['useExtendedFilters'] && ($currentStatus == key($statusList[0]) || $currentStatus == "denied") && $status == 'accepted')) {
                $brightcove = eBrightcove::model()->findByAttributes(Array('video_id' => $video->id));
                if (is_null($brightcove)) {
                    $brightcove = new eBrightcove();
                    $brightcove->video_id = $video->id;
                    $brightcove->brightcove_id = 'N/A';
                    $brightcove->status = 'new';
                    $brightcove->save();
                }
                if ($optSendEmail == 'Y') {
                    $userEmail = eUserEmail::model()->findByAttributes(array('user_id' => $video->user_id, 'type' => 'primary'));
                    $email = $userEmail->email;
                    $viewKey = $video->view_key;
                    MailUtility::send('video approve', $email, array(
                        'title' => !empty($video->title) ? $video->title : 'title not available',
                        'thumbnail' => Yii::app()->createAbsoluteUrl('/') . '/' . basename(Yii::app()->params['paths']['video']) . '/' . $video->thumbnail . '.png',
                        'view_key' => $video->view_key,
                    ));
                }
            } elseif ($video->status == 'denied' || Yii::app()->params['video']['useExtendedFilters'] && ($currentStatus == key($statusList[0]) || $currentStatus == "accepted") && $status == "denied") {
                if ($optSendEmail == 'Y') {
                    $userEmail = eUserEmail::model()->findByAttributes(array('user_id' => $video->user_id, 'type' => 'primary'));
                    $email = $userEmail->email;
                    $viewKey = $video->view_key;
                    MailUtility::send('video not approved', $email);
                }
            }

            if (Yii::app()->params['video']['autoFtpBasedOnStatus'] && VideoUtility::isFTPVideo($status, $currentStatus, $video)) {
                $this->actionVideoFTP($video->id);
            }
        } else {
            echo json_encode(array('success' => 'false'));
        }
    }

    // FTP video to client server for
    // display on their network
    public function actionAjaxVideoFTP() {

        $this->layout = false;
        foreach ($_POST as $k => $v) {
            $$k = $v;
        }
        $this->videoFtpProcess($id, true);
    }

    public function actionVideoFTP($id) {

        $this->layout = false;
        $this->videoFtpProcess($id, false);
    }

    private function videoFtpProcess($id, $json_request = false) {

        $video = eVideo::model()->findByPk($id);

        if (!is_null($video)) {

            $videoExt = VideoUtility::getVideoFileExtention($video->processed);
            $fileName = $video->filename . $videoExt;
            $fileNameMov = $video->filename . '.mov';
            //$fileNameMxf = $video->filename . '.mxf';
            $fileInputOriginal = Yii::app()->params['paths']['video'] . '/' . $fileName;
            $fileOutputMov = preg_replace('/\.mp4$/', '.mov', $fileInputOriginal);
            $fileOutputMxf = preg_replace('/\.mp4$/', '.mxf', $fileInputOriginal);
            $fileOutputMovTmp = preg_replace('/\.mp4$/', '.mov', $fileInputOriginal . 'tmp');
            $fileOutputMxfTmp = preg_replace('/\.mp4$/', '.mxf', $fileInputOriginal . 'tmp');
            // get destination id
            $destination = eDestination::model()->findByAttributes(array('destination' => 'client'));
            if (is_null($destination)) {

                $response = 'Unable to locate client as a destination.';
                echo json_encode(array('response' => $response));
                exit;
            }
            $fileOutputTv = '';
            // get latest count
            $videoIncrementValue = eVideoDestination::model()->countByAttributes(array('destination_id' => $destination->id));
            if (is_null($videoIncrementValue)) {
                $videoIncrementValue = 0;
            }

            if (Yii::app()->params['video']['allowCustomFileNameToNetwork'] === true) {

                if (Yii::app()->params['video']['useEvalForCustomFileName'] === true) {
                    $fileOutputTv = Yii::app()->params['video']['customFileNamePrefix'] . eval('return ' . str_replace('{INCREMENTED_VALUE}', $videoIncrementValue, Yii::app()->params['video']['customFileNameFormat']) . ';') . Yii::app()->params['video']['customFileNameExt'];
                } else {
                    $fileOutputTv = Yii::app()->params['video']['customFileNamePrefix'] . str_replace('{INCREMENTED_VALUE}', $videoIncrementValue, Yii::app()->params['video']['customFileNameFormat']) . Yii::app()->params['video']['customFileNameExt'];
                }

                $fileOutputLocalTv = Yii::app()->params['paths']['video'] . '/' . $fileOutputTv;
                $fileOutputRemoteTv = $fileOutputTv;
            } else {
                // use existing filename for remote file
                $fileOutputLocalTv = $fileOutputMov;
                $fileOutputRemoteTv = $fileNameMov;
                $fileOutputTv = $fileName;
            }

            if (!file_exists($fileInputOriginal)) {

                $response = 'Cannot find local file.';
                echo json_encode(array('response' => $response));
                exit;
            }
            if (Yii::app()->params['video']['allowMovUploadToNetwork'] === true) {
                // convert mp4 to mov
                $toMov = VideoUtility::ffmpegMp4ToMov($fileInputOriginal, $fileOutputMovTmp);
                if (!$toMov) {

                    $response = 'Unable to convert .mp4 to .mov.';
                    echo json_encode(array('response' => $response));
                    exit;
                }
                // convert to tv format
                $toTv = VideoUtility::ffmpegFinalizeVideoForTv($fileOutputMovTmp, $fileOutputLocalTv);
                if (!$toTv) {

                    $response = 'Unable to finalize video for tv.';
                    echo json_encode(array('response' => $response));
                    exit;
                }

                $ftp = FTPUtility::transfer(Yii::app()->params['ftp']['secure'], $fileOutputLocalTv, $fileOutputRemoteTv);

                if (!$ftp) {

                    $response = Yii::app()->user->getFlash('error') . $fileOutputLocalTv;
                    echo json_encode(array('response' => $response));
                    exit;
                }
            }
            $fileOutputLocalTvMxf = $fileOutputMxf;
            //$fileOutputRemoteTvMxf = $fileNameMxf;

            if (Yii::app()->params['video']['allowMxfUploadToNetwork'] === true) {
                $toMxf = VideoUtility::ffmpegMp4ToMxf($fileInputOriginal, $fileOutputMxfTmp);
                if (!$toMxf) {

                    $response = 'Unable to convert .mp4 to .mxf.';
                    echo json_encode(array('response' => $response));
                    exit;
                }
                $toTv = VideoUtility::ffmpegFinalizeVideoForTvMxf($fileOutputMxfTmp, $fileOutputLocalTvMxf);
                if (!$toTv) {

                    $response = 'Unable to finalize video for tv .mxf.';
                    echo json_encode(array('response' => $response));
                    exit;
                }

                $ftp = FTPUtility::transfer(Yii::app()->params['ftp']['secure'], $fileOutputLocalTvMxf, str_replace(Yii::app()->params['video']['customFileNameExt'], Yii::app()->params['video']['customFileNameExtMxf'], $fileOutputRemoteTv));

                if (!$ftp) {

                    $response = Yii::app()->user->getFlash('error') . $fileOutputLocalTvMxf;
                    echo json_encode(array('response' => $response));
                    exit;
                }
            }

            // get data for email
            $user = eUser::model()->findByPk(Yii::app()->user->id);
            $admin = $user->first_name . ' ' . $user->last_name;
            $thumbnail = '<img src="' . Yii::app()->request->getBaseUrl(true) . '/' . basename(Yii::app()->params['paths']['video']) . '/' . $video->thumbnail . Yii::app()->params['video']['imageExt'] . '">';
            $filename_original = $video->filename . VideoUtility::getVideoFileExtention($video->processed);
            $filename_new = $fileOutputTv;
            $datetime = date('Y-m-d h:i:s');
            $question = $video->question->question;
            $username = $video->user->first_name . ' ' . $video->user->last_name;
            $title = $video->title;

            // generate & send xml
            if (Yii::app()->params['ftp']['sendVideoXML']) {
                $xml_array = array(
                    'guid' => preg_replace('/\..{3,4}$/', '', $filename_new),
                    'id' => $video->id,
                    'subject' => 'FTP',
                    'title' => $title,
                    'keywords' => 'n/a',
                    'author' => $admin,
                    'description' => $video->description,
                    'filename' => $filename_new
                );

                $xmlFile = str_replace('.mov', '.xml', $fileOutputRemoteTv);
                $xmlLocalFile = Yii::app()->params['paths']['video'] . '/' . $xmlFile;
                $xml = new SimpleXMLElement('<asset/>');
                $xml_array = array_flip($xml_array);
                array_walk($xml_array, array($xml, 'addChild'));
                $doc = dom_import_simplexml($xml)->ownerDocument;
                $doc->encoding = Yii::app()->params['xml']['encoding'];
                $saveXML = $doc->save($xmlLocalFile);

                // todo - remove params secure and place in ftp util
                $ftp = FTPUtility::transfer(Yii::app()->params['ftp']['secure'], $xmlLocalFile, $xmlFile);

                if (!$ftp) {

                    $response = Yii::app()->user->getFlash('error') . $xmlLocalFile;
                    echo json_encode(array('response' => $response));
                    exit;
                }
            }

            // store video_destination record
            $videoDestination = new eVideoDestination();
            $videoDestination->video_id = $video->id;
            $videoDestination->user_id = Yii::app()->user->id;
            $videoDestination->destination_id = $destination->id;
            $videoDestination->response = 'Video successfully converted for tv and sent via ftp.';
            $videoDestination->created_on = date("Y-m-d H:i:s");
            $videoDestination->save();

            $_POST['status'] = "FTPed";
            AuditUtility::save($this, $_POST);

            // prep email
            $replacements = array('admin' => $admin,
                'thumbnail' => $thumbnail,
                'filename_original' => $filename_original,
                'filename_new' => $filename_new,
                'datetime' => $datetime,
                'question' => $question,
                'username' => $username,
                'title' => $title);

            // send email
            MailUtility::send('video_approved', ContactUtility::getFTPEmail(), $replacements, true, false);

            // cleanup
            if (file_exists($fileOutputMov)) {
                unlink($fileOutputMov);
            }
            if (file_exists($fileOutputMxf)) {
                unlink($fileOutputMxf);
            }
            if (file_exists($fileOutputMxf)) {
                unlink($fileOutputLocalTv);
            }
            //unlink($fileOutputLocalTvMxf);
            if (file_exists($fileOutputMxf)) {
                unlink($fileOutputMovTmp);
            }
            if (file_exists($fileOutputMxf)) {
                unlink($fileOutputMxfTmp);
            }
            //unlink($xmlLocalFile);
            // notify user
            //$notification = 'Video titled "' . $video->title . '" was successfully converted for tv and sent via ftp.';
            //eNotification::notify(Yii::app()->user->getId(), $notification , '/adminVideo');

            $response = Yii::app()->user->getFlash('success');
            echo json_encode(array('response' => $response));
            //exit;
        } else {
            $response = 'Unable to find video by id.';
            echo json_encode(array('response' => $response));
            exit;
        }
    }

    public function actionAjaxAmplifyPreview() {

        foreach ($_POST as $k => $v) {
            $$k = $v;
        }
        foreach ($videos as $id) {
            if (is_numeric($id)) {
                $video = eVideo::model()->findByPK($id);
                $playlist[] = Array(
                    'file' => '/' . basename(Yii::app()->params['paths']['video']) . "/{$video->filename}" . Yii::app()->params['video']['postExt'],
                    'image' => '/' . basename(Yii::app()->params['paths']['video']) . "/{$video->thumbnail}" . Yii::app()->params['video']['imageExt'],
                );
            }
        }
        echo $this->renderPartial('/admin/_videoPlayer', array(
            'videoInfo' => Array(
                'videofile' => $playlist,
                'image' => '/' . basename(Yii::app()->params['paths']['video']) . "/{$video->thumbnail}" . Yii::app()->params['video']['imageExt'],
                'width' => 243,
                'height' => 137,
            )
                )
        );
    }

    public function actionAjaxAmplifyConcatenate() {
        foreach ($_POST as $k => $v) {
            $$k = $v;
        }

        $baseVideo = eVideo::model()->findByPK($base);
        $baseVideo->status = 'accepted';
        $baseVideo->save();

        foreach ($videos as $id) {

            if (is_numeric($id)) {

                $video = eVideo::model()->findByPK($id);
                $playlist[] = Yii::app()->params['paths']['video'] . "/{$video->filename}";

                // todo - validate email address before we insert it
                if ($video->company_email != '') {

                    // todo - move html to template. Mark needed this asap otherwise it is unacceptable to have
                    // html within a controller.
                    $videoUrl = 'http://' . $_SERVER['SERVER_NAME'] . '/play/' . $baseVideo->view_key;
                    $thumbUrl = 'http://' . $_SERVER['SERVER_NAME'] . '/' . basename(Yii::app()->params['paths']['video']) . '/' . $baseVideo->thumbnail . Yii::app()->params['video']['imageExt'];
                    $twUrl = 'https://ads.twitter.com/accounts/18ce53uv8ww/campaigns_dashboard';
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                    $headers .= "From: ytt.amplify@youtootech.com" . "\r\n" . 'Reply-To: ytt.amplify@youtootech.com' . "\r\n";
                    $msg = '<html><head><title>Amplify Video</title></head><body>';
                    $msg .= "<p>Good news! Your partners at " . $video->company_name . " just published some great video content to all of their Twitter followers.  More importantly, they have chosen to promote your brand at the same time.</p><p>Play the thumbnail video below to preview their content and your brand message.</p><p><a href='" . $videoUrl . "'><img width='300' height='169' src='" . $thumbUrl . "'/></a></p><p>To promote your brand to an even wider Twitter audience, please login now to your Twitter Ads Account now and complete your transaction.</p><p>" . $twUrl . "</p>";
                    $msg .= '</body></html>';
                    mail($video->company_email, 'Amplified Video', $msg, $headers);


                    // prep email
                    /*
                      $replacements = array('company_name' => $video->company_name,
                      'company_email' => $video->company_email,
                      'video_url' => $videoUrl,
                      'thumb_url' => $thumbUrl,
                      'twitter_url' => $twUrl);

                      MailUtility::send('video_amplified', $video->company_email, $replacements);
                     *
                     */
                }
            }
        }

        $outfile = VideoUtility::concatenatePlaylist($playlist, $filenamePrefix);

        if ($outfile) {
            $record = array();
            $record['filename'] = $outfile;
            $record['thumbnail'] = $outfile;
            $record['question_id'] = $baseVideo->question_id;
            $record['source'] = 'amplify';
            $record['arbitrator_id'] = Yii::app()->user->getId();
            $record['user_id'] = Yii::app()->user->getId();
            $record['processed'] = 1;
            $record['title'] = 'Amplified Video';
            $record['description'] = 'Video amplified via video admin.';
            $record['status'] = 'accepted';
            $record['view_key'] = eVideo::generateViewKey();
            $record['watermarked'] = 0;
            $inserted = eVideo::insertRecord($record);

            if ($inserted) {
                $video = $inserted;
                echo $video->id;
                $brightcove = eBrightcove::model()->findByAttributes(Array('video_id' => $video->id));
                if (is_null($brightcove)) {
                    $brightcove = new eBrightcove();
                    $brightcove->video_id = $video->id;
                    $brightcove->brightcove_id = 'N/A';
                    $brightcove->status = 'new';
                    $brightcove->save();
                }
            } else {
                echo 'fail';
            }
        } else {
            echo 'fail';
        }
    }

    public function actionAjaxVideoSetDefaultRoll() {
        // remove video id and user id from spot and set saved on = 0000-00-00 00:00:00
        $this->layout = false;
        $videoId = trim($_POST['videoId']);
        $rollType = trim($_POST['rollType']);

        $video = eVideo::model()->findByPk($videoId);

        if (!is_null($video)) {

            // remove flags from any previous video
            $currentDefaultVideo = eVideo::model()->findByAttributes(array('is_default_ad' => 1, 'roll_type' => $rollType));

            if (!is_null($currentDefaultVideo)) {
                $currentDefaultVideo->is_default_ad = 0;
                $currentDefaultVideo->roll_type = 0;
                $currentDefaultVideo->save();
            }

            $video->is_default_ad = 1;
            $video->roll_type = $rollType;
            $video->save();
            echo json_encode(array('response' => 'Video has been set as a default.'));
            exit;
        }

        echo json_encode(array('response' => 'Unable to find video by id.'));
        exit;
    }

    public function actionAjaxVideoUnsetDefaultRoll() {
        // remove video id and user id from spot and set saved on = 0000-00-00 00:00:00
        $this->layout = false;
        $videoId = trim($_POST['videoId']);
        $video = eVideo::model()->findByPk($videoId);

        if (!is_null($video)) {

            $video->is_default_ad = 0;
            $video->roll_type = 0;
            $video->save();
            echo json_encode(array('response' => 'Video has been unflagged as a default.'));
            exit;
        }

        echo json_encode(array('response' => 'Unable to find video by id.'));
        exit;
    }

}
