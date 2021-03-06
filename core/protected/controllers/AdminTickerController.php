<?php

class AdminTickerController extends Controller {

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
                    'index',
                    'tickerModalHistory',
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
     * TICKER ACTIONS
     * This section contains everything required for the ticker section of the admin
     *
     *
     */
    public function actionIndex() {
        $types = array();
        $type_id = 0; //default type
        if (Yii::app()->params['ticker']['allowCreateAsEntity']) {
            $types[0] = "question";
            $types[1] = "entity";
            if (isset($_GET['type_id'])) {
                $type_id = $_GET['type_id'];
            }
        }
        $entities = Utility::resultToKeyValue(eEntity::model()->findAll(), 'id', 'name');
        $entity_id = "";
        if (isset($_GET['entity_id'])) {
            $entity_id = $_GET['entity_id'];
        }
        $questions = Utility::resultToKeyValue(eQuestion::model()->ticker()->current()->findAll(), 'id', 'question');
        $questions[0] = 'All';
        $question_id = "";
        if (isset($_GET['question_id'])) {
            $question_id = $_GET['question_id'];
        }
        $statuses = TickerUtility::getStatuses();
        $status = Utility::getDefaultStatus(Yii::app()->params['ticker']['extendedFilterLabels']);
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
        }
        $failedLanguage = (Yii::app()->request->getParam('failedLanguage') == 'on') ? 1 : 0;
        if (isset($_POST['eTicker']) && is_array($_POST['eTicker']) && !isset($_POST['eTicker']['ticker'])) {
            $tickers = array();
            foreach ($_POST['eTicker'] as $id => $ticker) {
                $tickers[$id] = eTicker::model()->findByPk($id); //get from db
                $org_status = $tickers[$id]->status;
                $tickers[$id]->attributes = $_POST['eTicker'][$id]; //set from post: status should not be updated.
                $tickers[$id]->arbitrator_id = Yii::app()->user->getId();
                if (!empty($_POST['eTicker'][$id]['status'])) {//accepted or denied button
                    if (Yii::app()->params['ticker']['useExtendedFilters']) {
                        $tickers[$id]->status = 'new';
                        $tickers[$id]->extendedStatus = Utility::updateExtendedStatus($status, $_POST['eTicker'][$id]['status'], $tickers[$id]);
                    }
                } else {//do not use empty status submitted for tabular form
                    $tickers[$id]->status = $org_status;
                }
                if (isset($ticker['entity_id'])) {//entity
                    if (!is_numeric($ticker['entity_id'])) {
                        $tickers[$id]->entity_id = NULL;
                    }
                    $tickers[$id]->type = 'entity';
                } else if (isset($ticker['question_id'])) {//ticker
                    if (!is_numeric($ticker['question_id'])) {
                        $tickers[$id]->question_id = NULL;
                    }
                    //$tickers[$id]->type = 'ticker';
                }
                if ($tickers[$id]->validate()) {
                    $tickers[$id]->save();
                    //Yii::app()->user->setFlash('success', "Ticker Added!");
                } else {
                    //Yii::app()->user->setFlash('error', "Unable to Add Ticker!");
                }
                if ((isset($_POST['eTicker'][$id]['status']) && $_POST['eTicker'][$id]['status'] == 'denied') || (isset($_POST['eTicker'][$id]['stop']) && $_POST['eTicker'][$id]['stop'] == 1)) {
                    $tickerRuns = eTickerRun::model()->findAllByAttributes(Array('ticker_id' => $id, 'stopped' => 0), 'web_runs > web_ran || mobile_runs > mobile_ran || tv_runs > tv_ran');
                    foreach ($tickerRuns as $tickerRun) {//stop denied
                        $tickerRun->stopped = 1;
                        $tickerRun->user_id = Yii::app()->user->getId();
                        $tickerRun->save();
                    }
                }
            }
        }
        if (isset($_POST['eTicker']['ticker']) && isset($_POST['eEntity']['name']) && !empty($_POST['eEntity']['name'])) {
            $ticker = new eTicker();
            $ticker->attributes = $_POST['eTicker'];
            $ticker->entity_id = $_POST['eEntity']['name'];
            // $ticker->ticker = $_POST['eTicker']['ticker'];
            $ticker->type = 'entity';
            $ticker->status = 'new';
            if (Yii::app()->params['ticker']['useExtendedFilters']) {
                $tickers[$id]->extendedStatus['new'] = true;
            }
            $ticker->user_id = Yii::app()->user->getId();
            $ticker->arbitrator_id = Yii::app()->user->getId();
            if ($ticker->validate()) {
                $ticker->save();
            }
        }
        //TickerRun
        if (isset($_POST['eTickerRun'])) {
            if (is_array($_POST['eTickerRun'])) {
                $tickerRuns = array();
                foreach ($_POST['eTickerRun'] as $id => $tickerRun) {
                    $tickerRuns[$id] = eTickerRun::model()->findByAttributes(array('ticker_id' => $id));
                    if (is_null($tickerRuns[$id])) {
                        $tickerRuns[$id] = new eTickerRun();
                    }
                    $tickerRuns[$id]->attributes = $_POST['eTickerRun'][$id];
                    $tickerRuns[$id]->ticker_id = $id;
                    $tickerRuns[$id]->user_id = Yii::app()->user->getId();
                    if ($tickerRuns[$id]->validate() && ($tickerRuns[$id]->web_runs > 0 || $tickerRuns[$id]->mobile_runs)) {
                        $tickerRuns[$id]->save(false);
                    }
                }
            }
        }
        $ticker = new eTicker('search');
        if ($type_id == 0) {
            $ticker->entity_id = array(null); //allow null search
        } else if ($type_id == 1) {
            $ticker->question_id = array(null); //allow null search
        }
        if (!empty($question_id))
            $ticker->question_id = $question_id;
        if (!empty($entity_id))
            $ticker->entity_id = $entity_id;
        $dataProvider = $ticker->search($status, $questions, $question_id);

        $tickers = eTicker::model()->{$status}()->with('tickerDestinations', 'tickerRuns', 'user', 'user.userEmails:primary')->recent()->findAll($dataProvider->criteria);
        if ($failedLanguage) {
            foreach ($tickers as $id => $ticker) {
                if ($ticker['clean']['result']) {
                    unset($tickers[$id]);
                }
            }
        }
        $tickerRuns = Array();
        if ($status == 'accepted') {
            foreach ($tickers as $id => $ticker) {
                $tickerRuns[$id] = new eTickerRun;
            }
        }
        $this->render('index', array(
            'types' => $types, //type dropdown
            'type_id' => $type_id, //type selected
            'entities' => $entities, //entities dropdown
            'entity_id' => $entity_id, //entities selected
            'questions' => $questions, //question dropdown
            'question_id' => $question_id, //question selected
            'statuses' => $statuses, //status dropdown
            'status' => $status, //status selected
            'failedLanguage' => $failedLanguage, //
            'entity' => new eEntity(), //empty ticker to add
            'ticker' => new eTicker(), //empty ticker to add
            'tickers' => $tickers, //tickers for table
            'pages' => $dataProvider->pagination,
            'sort' => $dataProvider->sort,
            'tickerRuns' => $tickerRuns
        ));
    }

    public function actionTickerModalHistory() {
        if (!isset($_POST['ticker_id'])) {
            echo("ticker is not available!");
            return;
        }
        $ticker_id = $_POST['ticker_id'];
        $criteria = new CDbCriteria;
        $criteria->condition = "action REGEXP '^adminTicker/index/\\\\?(.*)eTicker%5B" . $ticker_id . "%5D%5Bstatus%5D=[a-z]+'";
        $criteria->order = 't.id desc';
        $audits = eAudit::model()->with('user')->findAll($criteria);
        $i = 0;
        $parsedAudits = array();
        $statuses = TickerUtility::getStatuses();
        foreach ($audits as $audit) {
            $action = $audit->action;
            $parsed_url = parse_url($action);
            parse_str($parsed_url['query']); //status,eTicker[id][status],eTicker[id][question_id]
            $parsedAudits[$i]['created_on'] = $audit->created_on;
            $newStatus = $statuses[$status];
            $parsedAudits[$i]['status'] = $eTicker[$ticker_id]['status'] . " ticker ID: " . $ticker_id . " on " . $newStatus;
            $parsedAudits[$i]['username'] = $audit->user->username;
            $i++;
        }
        $ticker = eTicker::model()->with('arbitrator')->findByPk($ticker_id);
        if(!is_null($ticker)){
            $parsedAudits[$i]['created_on'] = $ticker->created_on;
            $parsedAudits[$i]['status'] = "pull from ".$ticker->source." ticker ID: " . $ticker_id;
            $parsedAudits[$i]['username'] = $ticker->arbitrator->username;
            $i++;
        }
        $this->renderPartial('tickerModalHistory', array('parsedAudits' => $parsedAudits));
    }

}

