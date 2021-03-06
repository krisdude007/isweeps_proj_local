<?php

class ReportUtility {
    public $startDate;
    public $lastWeek;

    public function __construct($startDate){
        $this->startDate = $startDate;
        $this->lastWeek = date('Y-m-d', strtotime($this->startDate." - 1 week"));
    }

    public static function cleanLatLng($str) {
        if (strpos($str, '+') !== false) {
            $str = str_replace('+', '', $str);
        }
        if (strpos($str, '-') !== false) {
            $str = str_replace('-', '', $str);
            $str = ltrim($str, 0);
            $str = '-' . $str;
        }
        return $str;
    }

    public static function initResults($startDate, $endDate){
        $resultSet = Array();
        $date = $startDate;
        while($date != $endDate){
            $resultSet[$date] = 0;
            $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
        }
        return $resultSet;
    }

    public function formatCategoryWeek($model, $criteria, $startDate = null){
        $startDate = is_null($startDate) ? $this->startDate : $startDate;
        $results = Array();
        for($i=0; $i<7; $i++){
            $tempCriteria = new CDbCriteria;
            $tempCriteria->condition = $criteria->condition;
            $tempCriteria->condition .= ' and date(created_on) = "'.gmdate('Y-m-d', strtotime($startDate." + ".$i." days")).'"';
            $results[] = $model->count($tempCriteria);
        }
        unset($model);
        return $results;
    }

    public function compareCategoryWeeks($model){
        $results = Array();
        $criteria = new CDbCriteria;
        $criteria->condition = '1 = 1';
        $lastWeek = $this->formatCategoryWeek($model, $criteria, $this->lastWeek);
        $thisWeek = $this->formatCategoryWeek($model, $criteria);
        foreach($lastWeek as $key => $record){
            $results[] = $thisWeek[$key] - $lastWeek[$key];
        }
        unset($lastWeek);
        unset($thisWeek);
        return $results;
    }

    public static function formatAnalyticWeek($obj, $start = 7){
        //$obj has last week and this week
        //start at 7 to start with this week
        $pageviews = array();
        $duration = array();
        $unique = array();
        $bounce = array();
        $ppv = array();
        $percentNew = array();
        $end = $start + 7;

        for($i=$start; $i<$end; $i++){
            $obj[$i]['Returning Visitor']['pageviews'] = !empty($obj[$i]['Returning Visitor']['pageviews']) ? $obj[$i]['Returning Visitor']['pageviews'] : 0;
            $obj[$i]['Returning Visitor']['visitors'] = !empty($obj[$i]['Returning Visitor']['visitors']) ? $obj[$i]['Returning Visitor']['visitors'] : 0;
            $obj[$i]['Returning Visitor']['timeOnSite'] = !empty($obj[$i]['Returning Visitor']['timeOnSite']) ? $obj[$i]['Returning Visitor']['timeOnSite'] : 0;
            $obj[$i]['Returning Visitor']['bounces'] = !empty($obj[$i]['Returning Visitor']['bounces']) ? $obj[$i]['Returning Visitor']['bounces'] : 0;
            $obj[$i]['Returning Visitor']['visits'] = !empty($obj[$i]['Returning Visitor']['visits']) ? $obj[$i]['Returning Visitor']['visits'] : 0;

            if(isset($obj[$i]) && !empty($obj)){
                if(isset($obj[$i]['New Visitor'])) {
                    $pageviews[] = $obj[$i]['New Visitor']['pageviews'] + $obj[$i]['Returning Visitor']['pageviews'];
                    $duration[] = gmdate("H:i:s", round(($obj[$i]['New Visitor']['timeOnSite'] + $obj[$i]['Returning Visitor']['timeOnSite'])/($obj[$i]['New Visitor']['visits'] + $obj[$i]['Returning Visitor']['visits']),2));
                    $unique[] = $obj[$i]['New Visitor']['visitors'] + $obj[$i]['Returning Visitor']['visitors'];
                    $bounce[] = round(100 * ($obj[$i]['New Visitor']['bounces'] + $obj[$i]['Returning Visitor']['bounces']) / ($obj[$i]['New Visitor']['visits'] + $obj[$i]['Returning Visitor']['visits']), 2);
                    $ppv[] = round(($obj[$i]['New Visitor']['pageviews'] + $obj[$i]['Returning Visitor']['pageviews'])/ ($obj[$i]['Returning Visitor']['visits'] + $obj[$i]['New Visitor']['visits']), 2);
                    $percentNew[] = round(100 * ($obj[$i]['New Visitor']['visits'] / ($obj[$i]['New Visitor']['visits'] + $obj[$i]['Returning Visitor']['visits'])), 2);
                } else {

                }
            }
        }
        $results['Pageviews'] = $pageviews;
        $results['Avg. Visit Duration'] = $duration;
        $results['Unique Visitors'] = $unique;
        $results['Bounce Rate'] = $bounce;
        $results['Pages Per Visit'] = $ppv;
        $results['%New Visit'] = $percentNew;
        unset($obj);
        return $results;
    }

    public static function compareAnalyticWeeks($obj){
    	$comparedWeek = array();
        $lastWeekSimple = self::formatAnalyticWeek($obj, 0);
        $thisWeekSimple = self::formatAnalyticWeek($obj);
        foreach($lastWeekSimple as $key => $metric){
            for($i=0; $i<=6; $i++){
                if(isset($thisWeekSimple[$key][$i])){
                    if($key == 'Avg. Visit Duration'){
                        $difference = strtotime($thisWeekSimple[$key][$i])- strtotime($lastWeekSimple[$key][$i]);
                        if($difference < 0){
                            $comparedWeek[$key][] = "-".gmdate("H:i:s", round(abs($difference)));
                        }else{
                            $comparedWeek[$key][] = gmdate("H:i:s", round(abs($difference)));
                        }
                    }else
                        $comparedWeek[$key][] = $thisWeekSimple[$key][$i] - $lastWeekSimple[$key][$i];
                }
            }
        }
        unset($obj);
        unset($lastWeekSimple);
        unset($thisWeekSimple);
        return $comparedWeek;
    }
}