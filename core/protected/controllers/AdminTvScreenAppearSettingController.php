<?php

class AdminTvScreenAppearSettingController extends Controller {

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
                    'save',
                    'index',
                    'AjaxDeleteImage'
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

    public function actionIndex($e_type, $refid) {
        $this->layout = false;
        $id = '';
        $filesArr = array();
        $foreBgimageFile = array();
        $BgimageFile = array();

        if (!file_exists(Yii::app()->params['paths']['image'] . '/tvscreensetting/')) {
            if (!mkdir(Yii::app()->params['paths']['image'] . '/tvscreensetting', 0777)) {
                die('Failed to create folders...');
            }
        }

        $formTvScreenSettingModel = eTvScreenAppearSetting::model()->findByAttributes(array('entity_type' => $e_type));

        if (is_null($formTvScreenSettingModel)) {
            $formTvScreenSettingModel = new eTvScreenAppearSetting;
            $formTvScreenSettingModel->screen_type = 'transparent';
            $formTvScreenSettingModel->entity_type = $e_type;
            $formTvScreenSettingModel->slide_speed = 20;
            $formTvScreenSettingModel->direction = 1;
        } else {
            $id = $formTvScreenSettingModel->id;
        }
        $foreBgImages = glob(Yii::app()->params['paths']['image'] . '/tvscreensetting/forebg_*');

        foreach ($foreBgImages as $key => $value) {
            $imagePaths = explode('/', $value);
            $foreBgimageFile[] = $imagePaths[count($imagePaths) - 1];
        }

        $BgImages = glob(Yii::app()->params['paths']['image'] . '/tvscreensetting/tvsc_*');

        foreach ($BgImages as $key => $value) {
            $imagePaths = explode('/', $value);
            $BgimageFile[] = $imagePaths[count($imagePaths) - 1];
        }

        $array = array('formTvScreenSettingModel' => $formTvScreenSettingModel, 'BgimageFile' => $BgimageFile, 'refId' => $refid, 'foreBgimageFile' => $foreBgimageFile);
        $this->render($e_type, $array);
    }

    public function actionSave() {
        $this->layout = false;
        $result = '';
        $fileName = '';
        $forebgFileName = '';

        if ($_POST['eTvScreenAppearSetting']) {
            if (!empty($_POST['foreground_type']) && $_POST['foreground_type'] == 'color')
                $_POST['eTvScreenAppearSetting']['existingForeBGImage'] = '';
            $model = eTvScreenAppearSetting::model()->findByAttributes(array('entity_type' => $_POST['eTvScreenAppearSetting']['entity_type']));
            $model = (is_null($model)) ? new eTvScreenAppearSetting() : $model;
            $model->attributes = $_POST['eTvScreenAppearSetting'];
            $selectedFile = $_POST['eTvScreenAppearSetting']['existingBGImage'];
            $selectedForeBGFile = empty($_POST['eTvScreenAppearSetting']['existingForeBGImage'])?null:$_POST['eTvScreenAppearSetting']['existingForeBGImage'];
            $model->filename = CUploadedFile::getInstance($model, 'filename');
            $model->forebg_filename = CUploadedFile::getInstance($model, 'forebg_filename');

            $model->validate();
            if (!empty($model->filename)) {
                list($width, $height) = getimagesize($model->filename->getTempName());
                if ($width . 'x' . $height !== Yii::app()->params['cloudGraphicAppearanceSetting']['tvScreenImageAllowedDimension'])
                    $model->addError('filename', 'Size must be ' . Yii::app()->params['cloudGraphicAppearanceSetting']['tvScreenImageAllowedDimension']);
            }
            if (!empty($model->forebg_filename)) {
                list($width, $height) = getimagesize($model->forebg_filename->getTempName());
                if ($width . 'x' . $height !== Yii::app()->params['cloudGraphicAppearanceSetting']['tvScreenScrollImageAllowedDimension'])
                    $model->addError('forebg_filename', 'Size must be ' . Yii::app()->params['cloudGraphicAppearanceSetting']['tvScreenScrollImageAllowedDimension']);
            }
            if (count($model->getErrors()) == 0) {
                if (!empty($model->filename)) {
                    $extension = pathinfo($model->filename->getName(), PATHINFO_EXTENSION);
                    $fileName = uniqid('tvsc_') . '.' . $extension;
                    $model->filename->saveAs(Yii::app()->params['paths']['image'] . '/tvscreensetting/' . $fileName);
                    $model->filename = $fileName;
                }
                if (!empty($model->forebg_filename)) {
                    $extension = pathinfo($model->forebg_filename->getName(), PATHINFO_EXTENSION);
                    $forebgFileName = uniqid('forebg_') . '.' . $extension;
                    $model->forebg_filename->saveAs(Yii::app()->params['paths']['image'] . '/tvscreensetting/' . $forebgFileName);
                    $model->forebg_filename = $forebgFileName;
                }
                if ($selectedFile)
                    $model->filename = $selectedFile;
                if ($selectedForeBGFile)
                    $model->forebg_filename = $selectedForeBGFile;
                $model->save();
                $result = 'Setting Saved!';
            } else {
                $errorMsg = '';
                foreach ($model->getErrors() as $key => $val):
                    switch ($key) {
                        case 'forebg_filename':
                            $errorMsg .= ' Forebackground Image : ' . $val[0];
                            break;
                        case 'filename':
                            $errorMsg .= ' Background Image : ' . $val[0];
                            break;
                    }
                endforeach;
                $result = $errorMsg;
            }
        }
        header('Content-type: application/json');
        echo CJSON::encode(array('result' => $result, 'filename' => $fileName, 'forebgFileName' => $forebgFileName));
        Yii::app()->end();
    }

    public function actionAjaxDeleteImage() {
        if (Yii::app()->request->isAjaxRequest)
            unlink(Yii::app()->params['paths']['image'] . '/tvscreensetting/' . $_POST['filename']);
    }

}