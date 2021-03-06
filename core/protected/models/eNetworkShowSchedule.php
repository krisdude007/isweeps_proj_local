<?php

/**
 * This is the model class for table "network_show_schedule".
 *
 * The followings are the available columns in table 'network_show_schedule':
 * @property integer $id
 * @property integer $network_show_id
 * @property integer $video_id
 * @property integer $user_id
 * @property string $spot_type
 * @property integer $spot_number
 * @property string $spot_length
 * @property integer $spot_available
 * @property integer $spot_order
 * @property string $spot_filename
 * @property string $show_on
 * @property string $spot_on
 * @property string $submitted_on
 * @property string $airs_on
 * @property string $created_on
 * @property string $updated_on
 *
 * The followings are the available model relations:
 * @property NetworkShow $networkShow
 * @property Video $video
 * @property User $user
 */
class eNetworkShowSchedule extends NetworkShowSchedule {

    var $available_slots;
    var $house_number;

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return NetworkShowSchedule the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('network_show_id, spot_type, spot_number, spot_length, spot_order, show_on, spot_on, created_on', 'required'),
            array('network_show_id, spot_number, spot_available, spot_order', 'numerical', 'integerOnly' => true),
            array('spot_type', 'length', 'max' => 2),
            array('spot_filename', 'length', 'max' => 255),
            array('submitted_on, airs_on, updated_on', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, network_show_id, video_id, user_id, spot_type, spot_number, spot_length, spot_available, spot_order, spot_filename, show_on, spot_on, submitted_on, airs_on, created_on, updated_on', 'safe', 'on' => 'search'),
        );
    }

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


    /*
      SELECT *
      FROM  `network_show_schedule`
      WHERE  `network_show_id` =11
      AND  `spot_type` =  'FS'
      AND  `show_on` =  '2013-11-25 15:00:00'
      LIMIT 0 , 30
     */

    public function scopes() {
        return array(
            'showSchedule' => array('select' => array('DISTINCT `t`.`show_on`',
                    'COUNT( `t`.`id` ) AS available_slots',
                    '`t`.`spot_available`',
                    'CONCAT(`t`.`spot_type`, `t`.`spot_number`) AS house_number',
                    '`t`.`spot_type` AS spot_type'
                ),
                'condition' => "`t`.`spot_available` = 1 AND DATE(DATE_ADD(`t`.`show_on`, INTERVAL 1 DAY)) > CURDATE()",
                'group' => '`t`.`show_on`',
                'order' => '`t`.`show_on` ASC',
            ),
            'spotSchedule' => array('select' => array('TIME_TO_SEC(`t`.`spot_length`) AS spot_length',
                    '`t`.`submitted_on` AS submitted_on',
                    'CONCAT(`t`.`spot_type`, `t`.`spot_number`) AS house_number',
                    '`t`.`spot_on` AS spot_on',
                    '`t`.`video_id` AS video_id',
                    '`t`.`user_id` AS user_id',
                    '`t`.`spot_filename` AS spot_filename'
                ),
                'order' => '`house_number` ASC',
            ),
        );
    }

    public static function timeRemaining($spotDateTime) {
        $timezone = date_default_timezone_get();
        $now = new DateTime(null, new DateTimeZone($timezone));
        $future_date = new DateTime($spotDateTime, new DateTimeZone($timezone));
        $interval = $future_date->diff($now);
        $daysToHours = 0;
        if ($interval->d > 0) {
            $daysToHours = (int) $interval->d * 24;
        }

        $timeRemaining = ((int) $interval->h + $daysToHours) . ':' . str_pad($interval->i, 2, '0', STR_PAD_LEFT) . ':' . str_pad($interval->s, 2, '0', STR_PAD_LEFT);
        $timeRemaining = ($interval->invert) ? $timeRemaining : false;

        return $timeRemaining;
    }

    public static function getSpotColor($timeRemaining) {

        $minutesRemainingExp = explode(':', $timeRemaining);

        switch (count($minutesRemainingExp)) {
            case 3:
                $minutesRemaining = ($minutesRemainingExp[0] * 60) + $minutesRemainingExp[1];
                break;
            case 2:
                $minutesRemaining = $minutesRemainingExp[0];
                break;

            default:
                $minutesRemaining = $minutesRemainingExp[0];
                break;
        }

        $color = 'green';
        if ($minutesRemaining < 25) {
            $color = 'red';
        } else if ($minutesRemaining >= 25 && $minutesRemaining < 49) {
            $color = 'yellow';
        }

        return $color;
    }

}

