<?php

class VideoUtility {

    public static function rotateVideo($videoPath, $newVideoPath, $direction = 'left') {
        // rotate video
        /*
         * 0 = 90CounterCLockwise and Vertical Flip (default)
          1 = 90Clockwise
          2 = 90CounterClockwise
          3 = 90Clockwise and Vertical Flip
         * ffmpeg -i in.mov -vf "transpose=1" out.mov
         */
        switch ($direction) {
            case 'left':
                $cmd = Yii::app()->params['paths']['ffmpeg'] . " -y -i $videoPath -vf \"transpose=2\" $newVideoPath";
                break;
            case 'right':
                $cmd = Yii::app()->params['paths']['ffmpeg'] . " -y -i $videoPath -vf \"transpose=1\" $newVideoPath";
                break;
        }

        exec($cmd);
        unlink($videoPath);
    }

    public static function encode($filePrefix, $fileExtension, $videoObject) {
        $filenameTmp = uniqid($filePrefix);
        $filename = uniqid($filePrefix);
        $fileInput = Yii::app()->params['paths']['video'] . '/' . $filenameTmp . '.' . $fileExtension;
        $originalfile = Yii::app()->params['paths']['video'] . '/' . $filename . '_orig.' . $fileExtension;

        $fileOutput = Yii::app()->params['paths']['video'] . '/' . $filename . Yii::app()->params['video']['postExt'];
        $fileThumb = Yii::app()->params['paths']['video'] . '/' . $filename . Yii::app()->params['video']['imageExt'];
        $fileGif = Yii::app()->params['paths']['video'] . '/' . $filename . '.gif';
        $duration = "00:00:" . Yii::app()->params['video']['duration'] . ".00";
        $videoObject->saveAs($fileInput);
        $watermark = eAppSetting::model()->findByAttributes(Array('attribute' => 'water_mark_on_video'));
        if ($watermark->value == 1) {
            $fileWatermark = $_SERVER['DOCUMENT_ROOT'] . Yii::app()->params['video']['watermark'];
        }

        $videoEncoded = self::ffmpegFlvToMp4($fileInput, $fileOutput, $duration, $fileWatermark = '');
        //unlink($fileInput); we need the original file (I dont know who has this idea to delete it)

        if (file_exists($fileInput)){
            copy($fileInput, $originalfile);
        }

        if ($videoEncoded) {
            // fix orientation
            self::fixOrientation($fileOutput);
            $durationArray = self::getVideoDuration($fileOutput);
            $durations = explode('.', $durationArray[2]);
            $duration = 60 * 60 * $durationArray[0] + 60 * $durationArray[1] + round($durations[0]);
            $fileInfo = self::getID3Info($fileOutput);
            self::ffmpegGenerateThumbFromVideo($fileOutput, $fileThumb);
            ImageUtility::generateThumbs($fileThumb);
            self::ffmpegMp4ToGif($fileOutput, $fileGif);
            return array('filename' => $filename,
                'fileInput' => $fileInput,
                'fileOutput' => $fileOutput,
                'fileThumb' => $fileThumb,
                'fileGif' => $fileGif,
                'duration' => $duration,
                'fileInfo' => $fileInfo,
                'watermarked' => $watermark->value);
        } else {
            return false;
        }
    }

    private static function fixOrientation($fileOutput) {
        ob_start();
        passthru(Yii::app()->params['paths']['ffprobe'] . " -loglevel error -show_streams " . $fileOutput . " 2>/dev/null | grep rotate");
        $orientationOutput = ob_get_contents();
        ob_end_clean();

        $attemptRotation = false;
        if ($orientationOutput != '') {
            $orientation = explode('=', $orientationOutput);
            $orientation = $orientation[1];
            if (isset($orientation)) {
                $attemptRotation = true;
            }
        }

        if ($attemptRotation) {
            $tmpFile = Yii::app()->params['paths']['video'] . '/' . Utility::generateRandomString() . Yii::app()->params['video']['postExt'];
            switch ($orientation) {
                case 90:
                    exec(Yii::app()->params['paths']['ffmpeg'] . ' -y -i ' . $fileOutput . ' -vf "transpose=1" -metadata:s:v rotate=0 ' . $tmpFile);
                    copy($tmpFile, $fileOutput);
                    unlink($tmpFile);
                    break;
                case 180:
                    exec(Yii::app()->params['paths']['ffmpeg'] . ' -y -i ' . $fileOutput . ' -vf "transpose=2,transpose=2" -metadata:s:v rotate=0 ' . $tmpFile);
                    copy($tmpFile, $fileOutput);
                    unlink($tmpFile);
                    break;
                case 270:
                    exec(Yii::app()->params['paths']['ffmpeg'] . ' -y -i ' . $fileOutput . ' -vf "transpose=2" -metadata:s:v rotate=0 ' . $tmpFile);
                    copy($tmpFile, $fileOutput);
                    unlink($tmpFile);
                    break;
                default:
                    //echo "unknown orientation or 0 which is fine";
                    break;
            }
        }
    }

    public static function concatenatePlaylist($playlist, $prefix) {
        if (is_array($playlist)) {
            $outfile = uniqid($prefix);
            foreach ($playlist as $k => $v) {
                $concat .= "{$v}.ts|";
                $ffmpeg[] = Yii::app()->params['paths']['ffmpeg'] . " -y -i {$v}" . Yii::app()->params['video']['postExt'] . " -c copy -bsf:v h264_mp4toannexb -f mpegts {$v}.ts";
            }
            $concat = rtrim($concat, '|');
            $ffmpeg[] = Yii::app()->params['paths']['ffmpeg'] . " -y -f mpegts -i \"concat:{$concat}\" " . Yii::app()->params['ffmpeg']['concatParams'] . " -bsf:a aac_adtstoasc " . Yii::app()->params['paths']['video'] . "/{$outfile}" . Yii::app()->params['video']['postExt'];
            foreach ($ffmpeg as $cmd) {
                exec($cmd);
            }
            VideoUtility::ffmpegGenerateThumbFromVideo(Yii::app()->params['paths']['video'] . '/' . $outfile . Yii::app()->params['video']['postExt'], Yii::app()->params['paths']['video'] . '/' . $outfile . Yii::app()->params['video']['imageExt']);
            VideoUtility::ffmpegMp4ToGif(Yii::app()->params['paths']['video'] . '/' . $outfile . Yii::app()->params['video']['postExt'], Yii::app()->params['paths']['video'] . '/' . $outfile . '.gif');
            return $outfile;
        } else {
            return false;
        }
    }

    public static function curlVideoToFlipFactory($filePath, $fileName) {

        $time = time();
        $newFileName = $time . "-" . $fileName;

        $ftp_server = Yii::app()->params['flipFactory']['host'];
        $ftp_user_name = Yii::app()->params['flipFactory']['username'];
        $ftp_user_pass = Yii::app()->params['flipFactory']['password'];

        $ch = curl_init();
        $fp = @fopen($filePath, 'r');
        curl_setopt($ch, CURLOPT_URL, "ftp://$ftp_user_name:$ftp_user_pass@$ftp_server/" . $newFileName);
        curl_setopt($ch, CURLOPT_UPLOAD, 1);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filePath));
        curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);

        if ($error) {
            return false;
        }

        unlink($filePath);
        return true;
    }

    /*
     * All FFMPEG CALLS
     */

    public static function ffmpegFinalizeVideoForTv($fileInput, $fileOutput) {
        if (file_exists($fileInput)) {
            $params = Yii::app()->params['ffmpeg']['tvParams'];
            $params = str_replace('{FILE_INPUT}', $fileInput, $params);
            $params = str_replace('{FILE_OUTPUT}', $fileOutput, $params);
            $cmd = Yii::app()->params['paths']['ffmpeg'] . $params;
            exec($cmd);
        }

        if (file_exists($fileOutput)) {
            return true;
        }

        return false;
    }

    public static function ffmpegMp4ToMov($fileInput, $fileOutput) {

        if (file_exists($fileInput)) {
            $cmd = Yii::app()->params['paths']['ffmpeg'] . " -y -i $fileInput -acodec copy -vcodec copy -f mov $fileOutput";
            exec($cmd);
        }

        if (file_exists($fileOutput)) {
            return true;
        }

        return false;
    }

    public static function ffmpegMp4ToMxf($fileInput, $fileOutput) {

        $fileTemp = pathinfo($fileInput, PATHINFO_FILENAME);
        $fileTemp = Yii::app()->params['paths']['video'] . '/' . $fileTemp . '.mxf';

        if (file_exists($fileInput)) {
            $cmd1 = Yii::app()->params['paths']['ffmpeg'] . " -y -i $fileInput -vcodec mpeg2video -q:v 1 -qmin 1 -intra -ar 48000 $fileTemp";
            exec($cmd1);
            $cmd2 = Yii::app()->params['paths']['ffmpeg'] . " -y -i $fileTemp -acodec copy -vcodec copy -f mov $fileOutput";
            exec($cmd2);
        }
//        if(file_exists($fileTemp))
//        {
//            echo 'file exists';
//            $fileOutput = $fileTemp.'tmp';
//        }
//        else {
//            echo 'file does not exist';
//        }

        if (file_exists($fileOutput)) {
            return true;
        }

        return false;
    }

    public static function ffmpegMp4ToGif($fileInput, $fileOutput, $scale = '179:101', $fps = "2") {
        self::ffmpegFlvToGif($fileInput, $fileOutput, $scale, $fps);
    }

    public static function ffmpegFlvToGif($fileInput, $fileOutput, $scale = '179:101', $fps = "2") {
        $fileInfo = VideoUtility::getID3Info($fileInput);
        //$duration = round($fileInfo['playtime_seconds']);
        $cmd = Yii::app()->params['paths']['ffmpeg'] . " -y -i $fileInput -vf scale=$scale,fps=fps=$fps -t 15 $fileOutput";
        exec($cmd);
    }

    public static function getWatermarkFilters($watermark) {
        if (file_exists($watermark)) {
            switch (Yii::app()->params['video']['watermarkLocation']) {
                case 'topLeft':
                    $location = '10:10';
                    break;
                case 'topRight':
                    $location = 'main_w-overlay_w-10:10';
                    break;
                case 'bottomLeft':
                    $location = '10:main_h-overlay_h-10';
                    break;
                case 'bottomRight':
                    $location = 'main_w-overlay_w-10:main_h-overlay_h-10';
                    break;
                case 'center':
                    $location = 'main_w/2-overlay_w/2:main_h/2-overlay_h/2';
                    break;
            }
            return "-vf 'movie={$watermark} [watermark]; [in][watermark] overlay={$location} [out]'";
        } else {
            return '';
        }
    }

    public static function ffmpegFlvToMp4($fileInput, $fileOutput, $duration, $watermark = '') {

        if (file_exists($fileInput)) {
            if ($watermark != '') {
                $watermarkFilters = self::getWatermarkFilters($watermark);
                $cmd = Yii::app()->params['paths']['ffmpeg'] . " -y -i $fileInput $watermarkFilters -q:v 7 -async 1 -r 30 -b:v 16M -bt 32M -vcodec libx264 -preset placebo -g 1 -movflags +faststart -acodec libfdk_aac -ac 2 -ar 48000 -ab 192k $fileOutput 2> $fileOutput.log";
            } else {
                $cmd = Yii::app()->params['paths']['ffmpeg'] . " -y -i $fileInput -q:v 7 -async 1 -r 30 -b:v 16M -bt 32M -vcodec libx264 -preset placebo -g 1 -movflags +faststart -acodec libfdk_aac -ac 2 -ar 48000 -ab 192k $fileOutput 2> $fileOutput.log";
            }
            exec($cmd);

            return true;
        }

        return false;
    }

    public static function ffmpegFinalizeVideoForTvMxf($fileInput, $fileOutput) {
        if (file_exists($fileInput)) {
            $params = Yii::app()->params['ffmpeg']['tvParamsMxf'];
            $params = str_replace('{FILE_INPUT}', $fileInput, $params);
            $params = str_replace('{FILE_OUTPUT_MXF}', $fileOutput, $params);
            $cmd = Yii::app()->params['paths']['ffmpeg'] . $params;
            exec($cmd);
        }

        if (file_exists($fileOutput)) {
            return true;
        }

        return false;
    }

    public static function ffmpegFlvToOgg($fileInput, $fileOutput, $duration, $watermark = '') {

        if (file_exists($fileInput)) {
            $watermark = VideoUtility::getWatermarkFilters($watermark);
            $cmd = Yii::app()->params['paths']['ffmpeg'] . " -y -i $fileInput $watermark -async 1 -r 30 -vcodec libtheora -q:v 7 -acodec libvorbis -q:a 5 -t $duration $fileOutput";
            exec($cmd);
            return true;
        }

        return false;
    }

    public static function ffmpegRescaleVideo($fileInput, $fileOutput, $qualityScale = 0, $vf = 'scale=960:540', $fps = 30) {

        if (file_exists($fileInput)) {
            $cmd = Yii::app()->params['paths']['ffmpeg'] . " -i $fileInput -qscale $qualityScale -vf $vf -r $fps -g 1 -y  $fileOutput";
            exec($cmd);
            copy($fileOutput, $fileInput);
            unlink($fileOutput);
            return true;
        }
        return false;
    }

    public static function ffmpegGenerateThumbFromVideo($fileInput, $fileOutput, $seekStart = '00:00:04', $format = 'image2', $size = '640x360', $numFrames = 1, $overwrite = '-y') {

        if (file_exists($fileInput)) {
            $cmd = Yii::app()->params['paths']['ffmpeg'] . " -ss $seekStart $overwrite -i $fileInput -f $format -vsync vfr -vframes $numFrames $fileOutput";
            //$cmd = Yii::app()->params['paths']['ffmpeg'] . " -ss $seekStart $overwrite -i $fileInput -vsync vfr -f $format -s $size -vframes $numFrames $fileOutput";
            exec($cmd);
            if (file_exists($fileOutput)) {
                return true;
            }
        }
        return false;
    }

    public static function ffprobeVideo($fileInput) {

        if (file_exists($fileInput)) {

            $cmd = Yii::app()->params['paths']['ffprobe'] . " -loglevel error -show_format -show_streams -show_format -print_format json $fileInput";
            exec($cmd, $output);

            if (count($output) > 0) {

                $str = '';

                foreach ($output as $o) {
                    $str .= $o;
                }

                return json_decode($str);
            }

            return false;
        }

        return false;
    }

    public static function generateThumbnailsForVideo($fileInput) {

        $videoPath = Yii::app()->params['paths']['video'];
        $videoFilePath = $videoPath . '/' . $fileInput . Yii::app()->params['video']['postExt'];
        $jsonPath = $videoPath . '/' . $fileInput . '.json';

        // for some reason, the json file will be generated  even if the thumbnails were not generated, so we need make sure there is file
        if (file_exists($videoPath . '/' . $fileInput . '_1' . Yii::app()->params['video']['imageExt']) && file_exists($jsonPath)) {
            return json_decode(file_get_contents($jsonPath));
        } else {

            // generate thumbs & .json file containing thumb filenames
            $durationArray = self::getVideoDuration($videoFilePath);

            // make sure we retrieved the video duration
            if ($durationArray != false) {

                $iterations = 10;
                $durations = explode('.', $durationArray[2]);
                //$duration = round($duration[0],2);
                $duration = 60 * 60 * $durationArray[0] + 60 * $durationArray[1] + round($durations[0]);

                if ($duration < $iterations) {
                    $iterations = $duration;
                }

                $return = array();
                // store original thumb
                $return[0] = $fileInput . Yii::app()->params['video']['imageExt'];

                // generate 9 thumbnails since one already exist
                for ($i = 1; $i < $iterations; ++$i) {
                    $imageName = $fileInput . '_' . $i . Yii::app()->params['video']['imageExt'];
                    $imagePath = $videoPath . '/' . $imageName;
                    $exec = Yii::app()->params['paths']['ffmpeg'] . " -ss $i -i $videoFilePath -f image2 -vsync vfr -vframes 1 $imagePath";
                    exec($exec);
                    $return[$i] = $imageName;
                }



                $encodedArr = json_encode($return);
                file_put_contents($jsonPath, $encodedArr);
                return json_decode($encodedArr);
            }
        }
        return false;
    }

    public static function getVideoDuration($videoFilePath) {

        if (file_exists($videoFilePath)) {
            $cmd = Yii::app()->params['paths']['ffmpeg'] . " -i $videoFilePath 2>&1 | grep Duration |  awk '{print $2}' | tr -d ,";
            $result = exec($cmd);
            return explode(":", $result);
        } else {
            return false;
        }
    }

    public static function getFileExtension($videoFilePath) {
        if (file_exists($videoFilePath)) {
            $info = new SplFileInfo($videoFilePath);
            $extn = $info->getExtension();
            return $extn;
        } else {
            return false;
        }
    }

    public static function getTypes() {
        return array('all' => 'All',
            'peoplemercial' => 'Peoplemercials',
            'famespot' => 'Famespots',
        );
    }

    public static function getIndicatorColor($videoDuration) {

        $maxDuration = (float) Yii::app()->params['video']['duration'];
        $thresholdMin = (float) Yii::app()->params['videoAdmin']['indicatorThreshold']['min'];
        $thresholdMax = (float) Yii::app()->params['videoAdmin']['indicatorThreshold']['max'];
        $videoDuration = (float) $videoDuration;

        $str = "";
        // if under the max
        if ($videoDuration < $maxDuration) {
            $str = 'red';
        }
        // if over
        elseif ($videoDuration > $maxDuration) {

            // if under $thresholdMin = green
            $diff = $videoDuration - $maxDuration;
            $diff = (float) $diff;

            if ($diff < $thresholdMin) {
                $str = 'green';
            }
            // else, if over $thresholdMin and under $thresholdMax = yellow
            elseif ($diff >= $thresholdMin && $diff < $thresholdMax) {
                $str = 'yellow';
            }
            // else greater than $thresholdMax = red
            else {
                $str = 'red';
            }
        }
        // if equal (rare)
        else {
            $str = 'green';
        }

        return $str;
    }

    // get values for video source dropdown
    public static function getSources() {
        $result = eVideo::model()->findAll(array(
            'select' => '`t`.`source`',
            'group' => '`t`.`source`',
            'distinct' => true,
        ));
        $sources = Utility::resultToKeyValue($result, 'source', 'source');

        foreach ($sources as $key => $val) {

            // fuck you apple
            if ($val[0] != 'i') {
                $sources[$key] = ucfirst($val);
            }
        }
        $sources = CMap::mergeArray(array('all' => 'All'), $sources);
        return $sources;
    }

    public static function getHeros() {
        $results = eVideo::model()->findAll(array(
            'select' => '`t`.`hero_user_id`',
            'group' => '`t`.`hero_user_id`',
            'distinct' => true,
        ));

        if(isset($results)) {
            foreach ($results as $result) {
                if(isset($result->hero_user_id) && $result->hero_user_id != NULL) {
                    $user = eUser::model()->findByPk($result->hero_user_id);
                    $heros[$result->hero_user_id] = $user['first_name'].' '.$user['last_name'];
                }
            }
        }

        $heros = CMap::mergeArray(array('0' => 'All'), $heros);

        return $heros;
    }

    // gets values for video status dropdown
    public static function getStatuses() {
        $filterLabels = Yii::app()->params['video']['extendedFilterLabels'];
        $statuses = array();
        foreach ($filterLabels as &$value) {
            if (Yii::app()->user->isSuperAdmin() || Yii::app()->user->isSiteAdmin() || Yii::app()->user->hasPermission(key($value))) {
                $statuses = CMap::mergeArray($statuses, $value);
            }
        }

        if (Yii::app()->user->isSuperAdmin()) {
            $statuses = CMap::mergeArray($statuses, Yii::app()->params['video']['superAdminExtendedFilterLabels']);
        }

        return $statuses;
    }

    public static function isFTPVideo($status, $currentStatus, $video) {
        $statusList = Yii::app()->params['video']['extendedFilterLabels'];

        if (Yii::app()->params['video']['useExtendedFilters']) {
            unset($statusList[0]);
            unset($statusList[1]);

            if ($status == 'accepted') {
                $okToFtp = false;

                foreach ($statusList as $v) {
                    if (array_key_exists($currentStatus, $v)) {
                        $okToFtp = true;
                        break;
                    }
                }

                $qualifyingStatuses = Yii::app()->params['video']['autoFtpStatuses'];

                foreach ($qualifyingStatuses as $qualifyingStatuse) {
                    if ($video->extendedStatus[$qualifyingStatuse] != true) {
                        $okToFtp = false;
                        break;
                    }
                }

                if ($okToFtp) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            if ($status == 'accepted' && array_key_exists($currentStatus, $statusList) && $video->status == 'accepted') {
                return true;
            } else {
                return false;
            }
        }
    }

    public static function getVideoFileExtention($processed = 0) {

        if ($processed == 1) {
            return Yii::app()->params['video']['postExt'];
        } else {
            return Yii::app()->params['video']['preExt'];
        }
    }

    public static function getPerPageOptions() {
        return array('12' => '12',
            '24' => '24',
            '36' => '36',
            '48' => '48'
        );
    }

    public static function getID3Info($fileInput) {
        $id3 = new getID3;
        $fileInfo = $id3->analyze($fileInput);
        return $fileInfo;
    }

    public static function getThumbImage($video, $size='') {
        if(empty($size))
            return '/'. basename(Yii::app()->params['paths']['video'])."/". $video->thumbnail . Yii::app()->params['video']['imageExt'];
        $fileName = ImageUtility::getThumbName($video->thumbnail . Yii::app()->params['video']['imageExt'], $size);
        if(file_exists(Yii::app()->params['paths']['video']."/" .$fileName))
            return '/'. basename(Yii::app()->params['paths']['video'])."/". $fileName;
        else
            return '/'. basename(Yii::app()->params['paths']['video'])."/". $video->thumbnail. Yii::app()->params['video']['imageExt'];
    }

}

?>
