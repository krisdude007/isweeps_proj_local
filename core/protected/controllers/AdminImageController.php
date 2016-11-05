<?php

class AdminImageController extends Controller {

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
                    // IMAGE ACTIONS
                    'index',
                    'imageModal',
                    'imageModalHistory',
                    'imageImportModal',
                    'ajaxImageAddTags',
                    'ajaxImageUpdateStatus',
                    'ajaxImageGetUsers',
                    'ajaxImageGetAdmins',
                    'ajaxImageFTP',
                    'ajaxRotateImage',
                    'ajaxImageImport',
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

    public function actionAjaxImageImport() {
        $source = $_POST['source'];
        $hashtag = $_POST['hashtag'];
        //$questions = eQuestion::model()->video()->current()->findAll();
        //foreach ($questions as $question) {
            //$question->hashtag = $question->hashtag ? $question->hashtag : Yii::app()->params['video']['defaultHashtag'];
            // we don't want to import for empty hashtag
            //if ($question->hashtag) {
            if($hashtag != '') {
                $imageImportUtility = new ImageImportUtility($source, $hashtag, 15);
                if ($imageImportUtility) {
                    $imageImportUtility->importImages();
                }
            } else {
                echo 'Hash tag should not be empty';
                Yii::app()->end();
            }
            //} else {
            //    echo 'Hash tag should not be empty for "' . $question->question . '"  !!!';
             //   Yii::app()->end();
            //}
        //}
    }
    
    public function actionImageImportModal() {

        $this->layout = null;
        $formImageImportModel = new FormImageImport();

        if (isset($_POST['ajax']) && $_POST['ajax'] === 'image-import') {
            echo CActiveForm::validate($formImageImportModel);
            Yii::app()->end();
        }

        if (isset($_POST['FormImageImport'])) {

            $formImageImportModel->attributes = $_POST['FormImageImport'];

            if ($formImageImportModel->validate()) {
                $imageImportUtility = new VideoImportUtility($formImageImportModel->source, $formImageImportModel->categoryIdentifier, $formImageImportModel->numImages);

                if ($imageImportUtility) {
                    // import images here
                    $imageImportUtility->importImages();
                }
            } else {
                Yii::app()->user->setFlash('error', 'There was an error while importing images.');
            }

            $this->redirect('/adminImages');
        }

        $this->render('imageImportModal', array('formImageImportModel' => $formImageImportModel));
    }
    
    
    /**
     *
     *
     * IMAGE ACTIONS
     * This section contains everything required for the image section of the admin
     *
     *
     */
    // FTP image to client server for
    // display on their network
    public function actionAjaxImageFTP() {

        $this->layout = false;

        foreach ($_POST as $k => $v) {
            $$k = $v;
        }

        $image = eImage::model()->findByPk($id);

        if (!is_null($image)) {

            $fileName = $image->filename;
            $fileLocal = Yii::app()->params['paths']['image'] . '/' . $fileName;
            $fileExplode = explode('.', $fileName);
            $fileExt = "." . $fileExplode[1];
            $fileExtn = "." . pathinfo($fileLocal, PATHINFO_EXTENSION);
            $imageFileLocalScale = '';

            // get destination id
            $destination = eDestination::model()->findByAttributes(array('destination' => 'client'));
            if (is_null($destination)) {
                echo json_encode(array('response' => 'Unable to locate client as a destination.'));
                exit;
            }

            // get latest count
            $imageIncrementValue = eImageDestination::model()->countByAttributes(array('destination_id' => $destination->id));
            if (is_null($imageIncrementValue)) {
                $imageIncrementValue = 0;
            }

            //convert image to video file
            if (Yii::app()->params['image']['allowImageToVideo']) {
                $imageFileLocalScale = Yii::app()->params['paths']['image'] . '/' . $fileExplode[0] . '_scale' . $fileExt . $fileExtn;
                $videoFileLocalMp4 = Yii::app()->params['paths']['image'] . '/' . $fileExplode[0] . '_mp4.mp4';
                $videoFileLocalMp4WAudio = Yii::app()->params['paths']['image'] . '/' . $fileExplode[0] . '_mova.mp4';
                $videoFileLocalMov = Yii::app()->params['paths']['image'] . '/' . $fileExplode[0] . '_mov.mov';
                $videoFileLocalTv = Yii::app()->params['paths']['image'] . '/' . $fileExplode[0] . '_tv.mov';
                $audioFile = $_SERVER['DOCUMENT_ROOT'] . '/core/webassets/audio/selent_1sec.wav';

                // convert image to video
                $scale = ImageUtility::ffmpegImageScale($fileLocal, $imageFileLocalScale);
                if (!$scale) {
                    echo json_encode(array('response' => 'Unable to scale image.'));
                    exit;
                }

                // convert image to video
                $toVideo = ImageUtility::ffmpegImageToVideo($imageFileLocalScale, $videoFileLocalMp4);
                if (!$toVideo) {
                    echo json_encode(array('response' => 'Unable to convert image to video.'));
                    exit;
                }

                // convert image to video
                $audio = ImageUtility::ffmpegImageVideoAddAudio($videoFileLocalMp4, $audioFile, $videoFileLocalMp4WAudio);
                if (!$audio) {
                    echo json_encode(array('response' => 'Unable to add audio to video.'));
                    exit;
                }

                // convert mp4 to mov
                $toMov = VideoUtility::ffmpegMp4ToMov($videoFileLocalMp4WAudio, $videoFileLocalMov);
                if (!$toMov) {
                    echo json_encode(array('response' => 'Unable to convert .mp4 to .mov.'));
                    exit;
                }

                // convert to tv format
                $toTv = ImageUtility::ffmpegFinalizeImageVideoForTv($videoFileLocalMov, $videoFileLocalTv);
                if (!$toTv) {
                    echo json_encode(array('response' => 'Unable to finalize video for tv.'));
                    exit;
                }

                $fileLocal = $videoFileLocalTv;
                $fileExt = '.mov';
            }

            if (Yii::app()->params['image']['allowCustomFileNameToNetwork'] === true) {

                if (Yii::app()->params['image']['useEvalForCustomFileName'] === true) {
                    $fileOutputTv = Yii::app()->params['image']['customFileNamePrefix'] . eval('return ' . str_replace('{INCREMENTED_VALUE}', $imageIncrementValue, Yii::app()->params['image']['customFileNameFormat'])) . $fileExt;
                } else {
                    $fileOutputTv = Yii::app()->params['image']['customFileNamePrefix'] . str_replace('{INCREMENTED_VALUE}', $imageIncrementValue, Yii::app()->params['image']['customFileNameFormat']) . $fileExt;
                }

                $fileOutputRemoteTv = $fileOutputTv;
            } else {
                // use existing filename for remote file
                $fileOutputRemoteTv = $fileName;
            }
            //print_r($fileOutputRemoteTv);exit();
            $fileOutputLocalTv = $fileLocal;

            if (!file_exists($fileLocal)) {
                echo json_encode(array('response' => 'Cannot find local file.'));
                exit;
            }
            if (Yii::app()->params['image']['allowImageToVideo'] === false) {
                $ftp = FTPUtility::transfer(Yii::app()->params['ftp']['secure'], $fileOutputLocalTv, str_replace($fileExt, $fileExtn, $fileOutputRemoteTv));
            } else {
                $ftp = FTPUtility::transfer(Yii::app()->params['ftp']['secure'], $fileOutputLocalTv, $fileOutputRemoteTv);
            }

            if (!$ftp) {
                $message = Yii::app()->user->getFlash('error');
                echo json_encode(array('response' => $message . $fileLocal));
                exit;
            }

            // get data for email
            $user = eUser::model()->findByPk(Yii::app()->user->id);
            $admin = $user->first_name . ' ' . $user->last_name;
            $thumbnail = '<img src="' . Yii::app()->request->getBaseUrl(true) . '/' . basename(Yii::app()->params['paths']['image']) . '/' . $fileName . '">';
            $filename_original = $fileName;
            $filename_new = $fileOutputTv;
            $datetime = date('Y-m-d h:i:s');
            $question = 'n/a';
            $username = $image->user->first_name . ' ' . $image->user->last_name;
            $title = $image->title;

            // generate & send xml
            if (Yii::app()->params['ftp']['sendImageXML']) {
                $xml_array = array(
                    'guid' => preg_replace('/\..{3,4}$/', '', $filename_new),
                    'id' => $image->id,
                    'subject' => 'FTP',
                    'title' => $title,
                    'keywords' => 'n/a',
                    'author' => $admin,
                    'description' => $image->description,
                    'filename' => $filename_new
                );

                $xmlFile = str_replace($fileExt, '.xml', $fileOutputRemoteTv);
                $xmlLocalFile = Yii::app()->params['paths']['image'] . '/' . $xmlFile;
                $xml = new SimpleXMLElement('<asset/>');
                $xml_array = array_flip($xml_array);
                array_walk_recursive($xml_array, array($xml, 'addChild'));
                $doc = dom_import_simplexml($xml)->ownerDocument;
                $doc->encoding = Yii::app()->params['xml']['encoding'];
                $saveXML = $doc->save($xmlLocalFile);

                // todo - remove params secure and place in ftp util
                $ftp = FTPUtility::transfer(Yii::app()->params['ftp']['secure'], $xmlLocalFile, $xmlFile);

                if (!$ftp) {
                    $message = Yii::app()->user->getFlash('error');
                    echo json_encode(array('response' => $message . $xmlLocalFile));
                    exit;
                }
            }

            // store image_destination record
            $imageDestination = new eImageDestination();
            $imageDestination->image_id = $image->id;
            $imageDestination->user_id = Yii::app()->user->id;
            $imageDestination->destination_id = $destination->id;
            $imageDestination->response = 'Image was successfully converted for tv and sent over to client';
            $imageDestination->created_on = date("Y-m-d H:i:s");
            $imageDestination->save();

            // prep email
            $replacements = array('admin' => $admin,
                'thumbnail' => $thumbnail,
                'filename_original' => $filename_original,
                'filename_new' => $filename_new,
                'datetime' => $datetime,
                'question' => $question,
                'username' => $username,
                'title' => $title);

            MailUtility::send('photo_approved', ContactUtility::getFTPEmail(), $replacements, true, false);

            // clean up
            unlink($xmlLocalFile);
            if (Yii::app()->params['image']['allowImageToVideo']) {
                if (file_exists($imageFileLocalScale)) {
                    unlink($imageFileLocalScale);
                }
                if (file_exists($videoFileLocalMp4)) {
                    unlink($videoFileLocalMp4);
                }
                if (file_exists($videoFileLocalMov)) {
                    unlink($videoFileLocalMov);
                }
                if (file_exists($videoFileLocalMp4WAudio)) {
                    unlink($videoFileLocalMp4WAudio);
                }
                if (file_exists($videoFileLocalTv)) {
                    unlink($videoFileLocalTv);
                }
                if (file_exists($fileOutputTv)) {
                    unlink($fileOutputTv);
                }
            }


            $message = Yii::app()->user->getFlash('success');
            echo json_encode(array('response' => $message));
        } else {
            echo json_encode(array('response' => 'Unable to find image by id.'));
        }
    }

    // FTP image to client server for
    // display on their network
    /*
      public function actionAjaxImageFTP() {

      $this->layout = false;
      foreach ($_POST as $k => $v) {
      $$k = $v;
      }

      $image = eImage::model()->findByPk($id);

      if (!is_null($image)) {

      $fileName = $image->filename;
      $fileLocal = Yii::app()->params['paths']['image'] . '/' . $fileName;

      // get destination id
      $destination = eDestination::model()->findByAttributes(array('destination' => 'client'));
      if(is_null($destination)) {
      echo json_encode(array('response' => 'Unable to locate client as a destination.'));
      exit;
      }

      // get latest count
      $imageIncrementValue = eImageDestination::model()->countByAttributes(array('destination_id' => $destination->id));
      if(is_null($imageIncrementValue)) {
      $imageIncrementValue = 0;
      }

      if (!file_exists($fileLocal)) {
      echo json_encode(array('response' => 'Cannot find local file.'));
      }

      $ftp = FTPUtility::transfer(Yii::app()->params['ftp']['secure'], $fileLocal, $fileName);

      if (!$ftp) {
      $message = Yii::app()->user->getFlash('error');
      echo json_encode(array('response' => $message . $fileLocal));
      }

      // store image_destination record
      $imageDestination = new eImageDestination();
      $imageDestination->video_id = $image->id;
      $imageDestination->user_id = Yii::app()->user->id;
      $imageDestination->destination_id = $destination->id;
      $imageDestination->response = 'Image was successfully converted for tv and sent over to client';
      $imageDestination->created_on = date("Y-m-d H:i:s");
      $imageDestination->save();

      $message = Yii::app()->user->getFlash('success');
      echo json_encode(array('response' => $message));

      } else {
      echo json_encode(array('response' => 'Unable to find image by id.'));
      }
      }
     *
     */

    public function actionImageModalHistory($id) {
        $this->layout = false;
        $image = eImage::model()->findByPk($id);
        if (!is_null($image)) {
            $criteria = new CDbCriteria;
            $criteria->condition = "action like 'adminImage/ajaxImageUpdateStatus/%imageId=" . $id . "%'";
            $criteria->order = 't.id desc';
            $audits = eAudit::model()->with('user')->findAll($criteria);
            $statuses = ImageUtility::getStatuses();
            $i = 0;
            $parsedAudits = array();
            foreach ($audits as $audit) {
                $action = $audit->action;
                $parsed_url = parse_url($action);
                parse_str($parsed_url['query']); //status,currentStatus,imageId
                $parsedAudits[$i]['created_on'] = $audit->created_on;
                $parsedAudits[$i]['status'] = AuditUtility::translate($audit->action);
                $parsedAudits[$i]['username'] = $audit->user->username;
                $i++;
            }
            $this->renderPartial('imageModalHistory', array('parsedAudits' => $parsedAudits));
        }
    }

    public function actionIndex($perPage = '') {

        // Ensure a per page value is set
        if (empty($perPage)) {
            $perPage = Yii::app()->params['imageAdmin']['perPage'];
            //$this->redirect('/adminImage?perPage=' . Yii::app()->params['imageAdmin']['perPage']);
        }

        // set to null
        $images = null;

        // create image filter form
        $filterImageModel = new FormFilterImage;
        $statuses = ImageUtility::getStatuses();
        $types = ImageUtility::getTypes();
        $perPageOptions = ImageUtility::getPerPageOptions();
        $criteria = new CDbCriteria;

        if (isset($_GET['FormFilterImage'])) {

            $filterImageModel->attributes = $_GET['FormFilterImage'];

            if ($filterImageModel->validate()) {

                $columnConditions = array();

                $status = $filterImageModel->status;
                $type = $filterImageModel->type;
                if (!empty($type) && $type != 'all') {
                    switch ($type) {
                        case "avatar":
                            $columnConditions['is_avatar'] = 1;
                            break;
                        case "image":
                            $columnConditions['is_avatar'] = 0;
                            break;
                    }
                }

                if (!empty($filterImageModel->user_id)) {
                    $columnConditions['user_id'] = $filterImageModel->user_id;
                }

                if (!empty($filterImageModel->tags)) {
                    $tags = explode(' ', $filterImageModel->tags);
                    $criteriaImg = new CDbCriteria;
                    $criteriaImg->addInCondition('title', $tags);
                    $tags = eTag::model()->with('tagImages')->findAll($criteriaImg);

                    $tagImageArr = array();
                    if (!is_null($tags)) {
                        foreach ($tags as $tag) {
                            foreach ($tag->tagImages as $tagImages) {
                                $tagImageArr[] = $tagImages->image_id;
                            }
                        }
                    }

                    if (count($tagImageArr) > 0) {
                        $criteria->addInCondition('id', $tagImageArr);
                    }
                }

                if (count($columnConditions) > 0) {
                    $criteria->addColumnCondition($columnConditions);
                }


                if (!empty($filterImageModel->dateStart) && !empty($filterImageModel->dateStop)) {
                    $fmtStartDate = date('Y-m-d H:i:s', strtotime($filterImageModel->dateStart));
                    $fmtStopDate = date('Y-m-d H:i:s', strtotime($filterImageModel->dateStop));
                    $criteria->addBetweenCondition('created_on', gmdate('Y-m-d H:i:s', strtotime($fmtStartDate)), gmdate('Y-m-d H:i:s', strtotime($fmtStopDate)));
                }

                $perPage = $filterImageModel->perPage;
            }
        } else {
            $defaultStatus = Utility::getDefaultStatus(Yii::app()->params['image']['extendedFilterLabels']);

            $filterImageModel->perPage = $perPage;
            $filterImageModel->status = $defaultStatus;
            //$columnConditions['status'] = 'new';
            //$criteria->addColumnCondition($columnConditions);
            $status = $defaultStatus;
        }

        $model = new eImage;
        $imagesTotal = $model->{$status}()->count($criteria);
        $pages = new CPagination($imagesTotal);
        $pages->pageSize = $perPage;
        $pages->applyLimit($criteria);

        $images = $model->recent()->{$status}()->findAll($criteria);
        $this->render('index', array(
            'statuses' => $statuses,
            'perPageOptions' => $perPageOptions,
            'filterImageModel' => $filterImageModel,
            'images' => $images,
            'imagesTotal' => $imagesTotal,
            'pages' => $pages,
            'types' => $types,
        ));
    }

    public function actionImageModal($id, $currentStatus) {
        $this->layout = false;
        $image = eImage::model()->with('user', 'user:userLocations:primary')->findByPk($id);
        $imageTags = eTagImage::model()->findAllByAttributes(array('image_id' => $id));

        $tagArr = array();
        foreach ($imageTags as $imageTag) {
            $tagArr[] = $imageTag->tag->title;
        }

        $tagModel = new eTag();
        $tagModel->title = implode(',', $tagArr);
        $this->render('imageModal', array('id' => $id,
            'currentStatus' => $currentStatus,
            'image' => $image,
            'imageTags' => $imageTags,
            'tagModel' => $tagModel));
    }

    public function actionAjaxImageUpdateStatus() {

        $this->layout = false;
        $status = trim($_POST['status']);
        $currentStatus = trim($_POST['currentStatus']);
        $imageId = trim($_POST['imageId']);
        $optSendEmail = (isset($_POST['optSendEmail'])) ? $_POST['optSendEmail'] : '';

        $image = eImage::model()->findByPk($imageId);

        if (!empty($image)) {
            if (Yii::app()->params['image']['useExtendedFilters']) {
                $image->extendedStatus = Utility::updateExtendedStatus($currentStatus, $status, $image);
            } else {
                $image->status = $status;
            }
            $image->arbitrator_id = Yii::app()->user->id;
            $image->status_date = date("Y-m-d H:i:s");
            if ($image->validate())
                $image->save(); //incase save failed, ie required field is not set, use $image->getErrors().
            else {
                echo json_encode(array('success' => 'false ' . print_r($image->getErrors())));
                return;
            }
            if ($image->status == 'accepted' || Yii::app()->params['image']['useExtendedFilters'] && ($currentStatus == "new" || $currentStatus == "denied") && $status == "accepted") {//new,accepted,denied,newtv,acceptedtv,deniedtv,newsup1,newsup2
                if ($optSendEmail == 'Y') {
                    $userEmail = eUserEmail::model()->findByAttributes(array('user_id' => $image->user_id, 'type' => 'primary'));
                    $email = $userEmail->email;
                    $viewKey = $image->view_key;
                    MailUtility::send('photo approve', $email, array(
                        'title' => !empty($image->title) ? $image->title : 'title not available',
                        'thumbnail' => Yii::app()->createAbsoluteUrl('/') . '/' . basename(Yii::app()->params['paths']['image']) . '/' . $image->filename,
                        'view_key' => $image->view_key,
                    ));
                }
                //MailUtility::send('photo approve', $email);
            } elseif ($image->status == 'denied' || Yii::app()->params['image']['useExtendedFilters'] && ($currentStatus == "new" || $currentStatus == "accepted") && $status == "denied") {//new,accepted,denied,newtv,acceptedtv,deniedtv,newsup1,newsup2
                if ($optSendEmail == 'Y') {
                    $userEmail = eUserEmail::model()->findByAttributes(array('user_id' => $image->user_id, 'type' => 'primary'));
                    $email = $userEmail->email;
                    $viewKey = $image->view_key;
                    MailUtility::send('photo not approved', $email);
                }
            }
        } else {
            echo json_encode(array('success' => 'false'));
        }
    }

    public function actionAjaxImageAddTags() {

        $this->layout = false;
        $tags = trim($_POST['tags']);
        $imageId = trim($_POST['imageId']);

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

            // we need to delete any image tags that exist in the db, but were deleted
            // from this request
            $taggedImages = TagImage::model()->with('tag')->findAllByAttributes(array('image_id' => $imageId));

            if (!is_null($taggedImages)) {
                foreach ($taggedImages as $tagImage) {
                    if (!in_array($tagImage->tag->title, $tags)) {
                        // delete tagImage record since it was deleted from the input field
                        $tagImage->delete();
                    }
                }
            }

            foreach ($tags as $tag) {

                // see if tag already exist. If it does, grab its id
                $tagModel = Tag::model()->findByAttributes(array('title' => $tag));

                if (!is_null($tagModel)) {
                    $tagId = $tagModel->id;

                    // see if it already exist in the tag image table. If not, add it.
                    $tagImage = TagImage::model()->findByAttributes(array('tag_id' => $tagId, 'image_id' => $imageId));

                    if (is_null($tagImage)) {
                        // add record to tag image
                        $tagImageModel = new TagImage();
                        $tagImageModel->tag_id = $tagId;
                        $tagImageModel->image_id = $imageId;
                        $tagImageModel->save();
                    }
                } else {
                    // If not, create a new tag and grab id
                    $tagModel = new Tag();
                    $tagModel->title = $tag;
                    $tagModel->save();

                    // add record to tag image
                    $tagImageModel = new TagImage();
                    $tagImageModel->tag_id = $tagModel->id;
                    $tagImageModel->image_id = $imageId;
                    $tagImageModel->save();
                }
            }
        } else {
            // remove all tags for image
            TagImage::model()->deleteAllByAttributes(array('image_id' => $imageId));
        }
    }

    // ajax method for user filter
    public function actionAjaxImageGetUsers() {

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

    // ajax method for admin filter
    /* public function actionAjaxImageGetAdmins() {

      $this->layout = null;
      $email = trim($_GET['term']);
      $criteria = new CDbCriteria();
      $criteria->addSearchCondition('username', $email, true);
      $users = eUser::model()->isAdmin()->findAll($criteria);
      $rows = array();

      if ($users) {
      foreach ($users as $u) {
      $rows[] = array('label' => $u->username,
      'id' => $u->id);
      }
      }

      echo json_encode($rows);
      } */


    public function actionAjaxRotateImage() {

        $this->layout = false;
        $directions = array('left', 'right');
        $imageId = trim($_POST['imageId']);
        $direction = trim($_POST['direction']);
        $image = eImage::model()->findByPk($imageId);
        if (in_array($direction, $directions)) {
            if (!empty($image)) {
                $imagePath = basename(Yii::app()->params['paths']['image']) . '/' . $image->filename;
                $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
                $filename = $image->user_id . "_" . md5(uniqid('', true) . $image->filename) . '.' . $ext;
                $newImagePath = Yii::app()->params['paths']['image'] . "/" . $filename;
                $newImagePathWeb = Yii::app()->request->baseUrl . '/' . basename(Yii::app()->params['paths']['image']) . '/' . $filename;
                ImageUtility::rotateImage($imagePath, $newImagePath, $direction);
                $image->filename = $filename;
                $image->save();
                sleep(1);
                echo json_encode(array('success' => 'true', 'message' => 'Image rotated.', 'filename' => $newImagePathWeb));
            } else {
                echo json_encode(array('success' => 'false', 'message' => 'Unable to locate image in db.'));
            }
        } else {
            echo json_encode(array('success' => 'false', 'message' => 'Invalid rotation direction.'));
        }
    }

}

