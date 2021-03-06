<?php

class AdminCampaignController extends Controller {

    public $user; 
    public $notification;
    public $layout = '//layouts/admin';
     

    public function filters() {
        return array(
            //'accessControl', // perform access control for CRUD operations
        );
    }

    public function accessRules() {
        return array(
            array('allow',
                'actions' => array('*'),
                'expression' => 'Yii::app()->user->isAdmin()',
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }
    
    function init() {
        parent::init();
        Yii::app()->setComponents(array('errorHandler' => array('errorAction' => 'admin/error',)));
        $this->user = ClientUtility::getUser();
        $this->notification = eNotification::model()->orderDesc()->findAllByAttributes(array('user_id' => Yii::app()->user->id));
         
    }
    
    public function actionIndex()
    {
        if($this->hasActiveCampaign()) {
            $facebook_user =Yii::app()->facebook->getUser();
            $campaign = new eCampaign('search');
    		$campaign->unsetAttributes();   
    		if(isset($_GET['eCampaign']))
    			$campaign->attributes=$_GET['eCampaign'];
            $this->render('index', array(
                'campaign'=>$campaign
            ));
        } else {
            $this->redirect('/adminCampaign/create');
        }
         
    }
    
    public function actionCreate()
    {
        $campaign = new eCampaign;
        if( isset($_POST['submit_step1']) && Yii::app()->request->getPost('submit_step1') == 'step1') {
            if(isset($_POST['eCampaign'])) {
                $campaign->attributes = $_POST['eCampaign'];
                if($campaign->save()) {
                    $this->redirect(array('package','id'=>$campaign->id));
                }
            }
            $this->render('create_step2', array(
                'campaign'=>$campaign,
            ));
        } else {
            $this->render('create_step1');
        }  
    }
    
    public function actionPackage($id)
    {
        $campaign = $this->loadModel($id);
         
        $this->render('package', array(
            'campaign'=>$campaign,
        ));
    }
    
    
    public function actionChangePackage($id)
    {
        $campaign = $this->loadModel($id);
         
        $this->render('change_package', array(
            'campaign'=>$campaign,
        ));
    }
    
    public function actionViewPost()
    {
        $campaign = $this->loadActiveCampaign();
        $this->render('view_post', array(
            'campaign'=>$campaign,
        ));
    }
    
    public function actionView($id)
    {
        $campaign = $this->loadModel($id);
        $campaign_post = new eCampaignPost('search');
		$campaign_post->unsetAttributes();   
		if(isset($_GET['eCampaignPost']))
			$campaign_post->attributes=$_GET['eCampaignPost'];
        $this->render('view', array(
            'campaign'=>$campaign, 
            'campaign_post'=>$campaign_post,
        ));
    }
    
    public function actionUpdate($id)
    {
        $campaign = $this->loadModel($id);
        if(isset($_POST['eCampaign'])) {
            $campaign->attributes = $_POST['eCampaign'];
            if($campaign->save()) {
                $this->redirect(array('index'));
            }
        }
        $this->render('update', array(
            'campaign'=>$campaign,
        ));
       
    }   
     
    public function actionUpgrade()
    {
        $campaign_id = Yii::app()->request->getPost('id'); 
        if($campaign = $this->loadModel($campaign_id)) {
            $plan = Yii::app()->request->getPost('plan');
            if(in_array($plan, array('a','b','c'))) {
                $campaign->package = strtoupper($plan);
                if($campaign->save()) {
                    Yii::app()->user->setFlash('success', 'You successfully switched to package '. strtoupper($plan));
                    echo json_encode(array('status'=>1));
                    Yii::app()->end(); 
                }   
            }
        }
        echo json_encode(array('status'=>0));
        Yii::app()->end(); 
        
    }
    
    public function actionCreatePost($id)
    {
        $campaign = $this->loadModel($id);
        $post = new eCampaignPost;
        $post->campaign_id = $id;
        if(isset($_POST['eCampaignPost'])) {
            $post->attributes = $_POST['eCampaignPost'];
            if($post->save()) {
               $this->redirect(array('view', 'id'=>$id));
            }
        }
        $this->render('post', array(
            'campaign'=>$campaign,
            'post'=>$post,
        ));
        
    }
    
    public function actionUpdatePost($post_id)
    {
        if($post = eCampaignPost::model()->findByPk($post_id)) {
             
            if(isset($_POST['eCampaignPost'])) {
                $post->attributes = $_POST['eCampaignPost'];
                if($post->save()) {
                   $this->redirect(array('view', 'id'=>$post->campaign_id));
                }
            }
            $this->render('update_post', array('post'=>$post));
        } else {
            throw new CHttpException(404);
        }
    }
    
    
    
    public function actionPost()
    {
        if(Yii::app()->request->isAjaxRequest && ($post = eCampaignPost::model()->findByPk(Yii::app()->request->getPost('post_id')))) {
            $return = array();
            $campaign = $post->campaign;
            if($campaign->connect_facebook) {
                $post_message['message'] = $post->post_content;
                if($post->media_type == 'video') {
                    $post_message['link'] = Yii::app()->createAbsoluteUrl('/video/play', array('view_key'=>$post->video->view_key));
                } else {
                    $post_message['link'] = Yii::app()->createAbsoluteUrl('/image/view', array('view_key'=>$post->image->view_key));
                }
                $response = FacebookUtility::shareAs('client', $post_message);
                if ($response['result']) {
                    $post->facebook_post_id = $response['response']['id'];
                    $post->save();
                    $return['facebook'] ='Successfully posted to facebook';
                } else {
                    $return['facebook'] = 'Failed to post to facebook';
                }
            }
            if($campaign->connect_twitter) {
                $text = $post->post_content. ' ';
                $text .= $post->media_type == 'video' ? Yii::app()->createAbsoluteUrl('/video/play', array('view_key'=>$post->video->view_key)) : Yii::app()->createAbsoluteUrl('/image/view', array('view_key'=>$post->image->view_key));
                $response = TwitterUtility::tweetAs('client', $text);
                 
                if (isset($response->erros) && sizeof($response->errors) > 0) {
                    $return['twitter'] = 'Failed to post to twitter. Error:';
                    foreach ($response->errors as $error) {
                        $return['twitter'] .= $error->message;
                    }
                } else {
                    $post->twitter_post_id = $response->id_str;
                    $post->save();
                    $return['twitter'] ='Successfully posted to twitter';
                } 
            }
            echo json_encode($return);
        } else {
            echo 'Access denied';
        }
        Yii::app()->end();
    }
    
    public function actionReport($id) 
    { 
        $campaign = $this->loadModel($id); 
        $this->render('report', array( 
            'campaign'=>$campaign, 
        ));     
    } 
	
    
    public function actionDelete($id)
    {
        $this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }
    
    public function actionDeletePost($post_id)
    {
        eCampaignPost::model()->findByPk($post_id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }
    
    public function actionChangeStatus($id)
    {
        if(Yii::app()->request->isAjaxRequest) {
            $model = $this->loadModel($id); 
  	        $model->status = $model->status ?  0 : 1; 
 	 	    if($model->save()) {
                Yii::app()->user->setFlash('success', 'The current campaign has been changed succesfully.');
                Yii::app()->end();
            }
        } else {
            die('Access denied');
        }
    }
    
    public function actionEditAccount()
    {
        $this->render('edit_account', array());
    }
    
    public function loadModel($id)
	{
		$model=eCampaign::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}
	
	public function loadActiveCampaign()
	{
	    return eCampaign::model()->find('status=1');
	}
	
	public function hasActiveCampaign()
	{
	    return eCampaign::model()->count('status=1');
 	}
	
 	 
}
