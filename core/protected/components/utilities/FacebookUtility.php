<?php

class FacebookUtility {

    //TODO: This stuff might exist in the facebook library.
    public static function getApplicationAccessToken() {
        $facebook = Yii::app()->facebook;
        return $facebook->appId . '|' . $facebook->secret;
    }

    public static function search($terms, $minResults = 100) {
        $query = http_build_query(
                array(
                    'type' => 'post',
                    'access_token' => FacebookUtility::getApplicationAccessToken(),
                    'q' => $terms,
                    'fields' => 'message,from',
                    'limit' => $minResults,
                )
        );
        $url = 'https://graph.facebook.com/search?' . $query;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        return json_decode($result);
    }

    public static function getUsernameFromID($id) {
        $facebookCache = Yii::app()->cache->get("facebookUser-$id");
        if (empty($facebookCache->name)) {
            $query = http_build_query(
                    array(
                        'fields' => 'name',
                    )
            );
            $url = "https://graph.facebook.com/{$id}?" . $query;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = json_decode(curl_exec($ch));
            Yii::app()->cache->set("facebookUser-$id", $result, 259000);
            return empty($result->name);
        }
        return $facebookCache->name;
    }

    public static function getAvatarFromID($id) {
        return "https://graph.facebook.com/$id/picture?type=large";
    }

    public static function connect($userFacebook) {
        $facebook = Yii::app()->facebook;
        $facebook->setExtendedAccessToken();
        $userFacebook->user_id = Yii::app()->user->getId();
        $userFacebook->access_token = $facebook->getAccessToken();
        if (isset($_POST['signed_request'])) {
            list($encoded, $payload) = explode('.', $_REQUEST["signed_request"], 2);
            $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            $userFacebook->expiresIn = $data['expires'] - $data['issued_at'];
            $userFacebook->facebook_user_id = $data['user_id'];
        } else {
            $userFacebook->expiresIn = $_POST['expiresIn'];
            $userFacebook->facebook_user_id = $_POST['userID'];
        }
        if ($userFacebook->save()) {
            $audit = new eAudit;
            $audit->user_id = Yii::app()->user->getId();
            $audit->action = 'connected facebook account';
            $audit->save();
            return true;
        } else {
            return false;
        }
    }
    
    public static function connectNoSigReq($userFacebook) {
        $userFacebook->user_id = Yii::app()->user->getId();
        $userFacebook->access_token = $_POST['accessToken'];
        $userFacebook->expiresIn = $_POST['expiresIn'];
        $userFacebook->facebook_user_id = $_POST['userID'];
        
        if ($userFacebook->save()) {
            $audit = new eAudit;
            $audit->user_id = Yii::app()->user->getId();
            $audit->action = 'connected facebook account';
            $audit->save();
            return true;
        } else {
            return false;
        }
    }

    /*
     * See https://developers.facebook.com/docs/reference/api/post/ for keys/values of $post array
     * Ideally, you should only need to set 'message' and 'link' keys.  Open Graph tags on the site
     * should take care of the rest; however, any value you specify in this array will override OG tags.
     * eg:
     *
     *  $post = array(
     *       'message'=>'Check out this video!',
     *       'link' => 'http://www.youtoo.com/videos/55f5d7d8cfbd69f6aa9e03e3a39c2351',
     *  );
     *
     * This will return a facebook post id if successful;
     */

    public static function shareAs($uID = false, $post = Array()) {
        if (!$uID || empty($post)) {
            //Can't be called without these.
            return false;
        }
        if (is_numeric($uID)) {
            $user = eUserFacebook::model()->findByAttributes(Array('user_id' => $uID));
            if(!isset($user->access_token))
                return false;
            $access_token = $user->access_token;
            if (is_null($user)) {
                //User doesn't have an access token stored in the system.
                return false;
            }
            $facebook = Yii::app()->facebook;
            $facebook->setAccessToken($access_token);
            $facebook->getUser();
            try {
                $result = $facebook->api('/me/feed/', 'POST', $post);
            } catch (Exception $e) {
                return(Array(
                    'error' => $e,
                    'result' => false
                ));
            }
        } else {
            switch ($uID) {
                case 'client':
                    $user = eUserFacebook::model()->findByAttributes(Array('user_id' => Yii::app()->user->getId()));
                    $access_token = $user->access_token;
                    if (is_null($user)) {
                        return(Array(
                            'error' => 'User does not have facebook connected.',
                            'result' => false
                        ));
                    }
                    $facebook = Yii::app()->facebook;
                    $facebook->setAccessToken($access_token);
                    $facebook->getUser();
                    try {
                        $permissions = $facebook->api('/me/permissions/');
                    } catch (Exception $e) {
                        return(Array(
                            'error' => $e,
                            'result' => false
                        ));
                    }
                    if (array_key_exists('manage_pages', $permissions['data'][0])) {
                        try {
                            $pages = $facebook->api('/me/accounts/');
                        } catch (Exception $e) {
                            return(Array(
                                'error' => $e,
                                'result' => false
                            ));
                        }
                        foreach ($pages['data'] as $target) {
                            $page[$target['id']] = $target['access_token'];
                        }
                        if (in_array(Yii::app()->facebook->pageId, array_keys($page))) {
                            try {
                                $facebook->setAccessToken($page[Yii::app()->facebook->pageId]);
                                $result = $facebook->api('/' . Yii::app()->facebook->pageId . '/feed/', 'POST', $post);
                                $facebook->setAccessToken($access_token);
                            } catch (Exception $e) {
                                return(Array(
                                    'error' => $e,
                                    'result' => false
                                ));
                            }
                        } else {
                            //User gave us permission to manage pages, but they are not a facebook admin of the client page.
                            return(Array(
                                'error' => 'User is not a Facebook admin.',
                                'result' => false
                            ));
                        }
                    } else {
                        //User never gave us permission to manage their pages.
                        return(Array(
                            'error' => 'User declined to give permissions.',
                            'result' => false
                        ));
                    }
                    break;
                default:
                    break;
            }
        }
        return(Array(
            'response' => $result,
            'result' => true
        ));
    }

    public static function setOGTags($controller) {
        $og = $controller->id . '.' . $controller->getAction()->id;
        switch ($og) {
            case 'video.play':
                $video = eVideo::model()->findByAttributes($controller->getActionParams());
                $url = 'http://' . $_SERVER['HTTP_HOST'] . '/uservideos/' . $video->filename . Yii::app()->params['video']['postExt'];
                $secure_url = 'https://' . $_SERVER['HTTP_HOST'] . '/uservideos/' . $video->filename . Yii::app()->params['video']['postExt'];
                $docroot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : Yii::app()->params['docroot'];
                $videolink = realpath(Yii::app()->params['paths']['video'] . "/{$video->filename}" . '.png');
                $link = isset($_SERVER['HTTPS']) ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . '/uservideos/' . $video->filename . '.png';
                if(!empty($videolink)){
                list($width, $height, $type) = getimagesize($videolink);
                }
                $imagetype = image_type_to_mime_type(isset($type));
                if($imagetype == 'image/gif'){
                    $imagetype = 'image/png';
                }
                    Yii::app()->facebook->ogTags = array(
                        'og:title' => html_entity_decode($video->title),
                        'og:url' => Yii::app()->createAbsoluteUrl(Yii::app()->request->url),
                        'og:description' => !empty(Yii::app()->facebook->videoShareText) ? html_entity_decode(Yii::app()->facebook->videoShareText) : $video->description,
                        'og:type' => 'video.movie',
                        'og:video' => 'http://' . $_SERVER['HTTP_HOST'] . '/webassets/swf/player.swf?file=' . urlencode($url),
                        'og:video:secure_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/webassets/swf/player.swf?file=' . urlencode($secure_url),
                        'og:video:type' => 'application/x-shockwave-flash',
                        'og:video:width' => isset($width) ? $width : '420',
                        'og:video:height' => isset($height) ? $height :'260',
                        'og:image' => $link,
                        'og:image:secure_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/uservideos/' . $video->filename . '.png',
                        'og:image:type' => $imagetype,
//                      'og:image:width' => isset($width) ? $width : '600',
//                      'og:image:height' => isset($height) ? $height : '315',
                        'og:image:width' => 200,
                        'og:image:height' => 200,
                    );
                return true;
                break;
            case 'image.view':
                // todo - change pointer to image path
                $image = eImage::model()->findByAttributes($controller->getActionParams());
                $docroot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : Yii::app()->params['docroot'];
                $imagelink = realpath(Yii::app()->params['paths']['image'] . "/{$image->filename}");
                $link = 'http://' . $_SERVER['HTTP_HOST'] . '/userimages/' . $image->filename;
                if ($imagelink) {
                    list($width, $height, $type) = getimagesize($imagelink);

                    Yii::app()->facebook->ogTags = array(
                        'og:title' => html_entity_decode($image->title),
                        'og:url' => Yii::app()->createAbsoluteUrl(Yii::app()->request->url),
                        'og:description' => !empty(Yii::app()->facebook->imageShareText) ? html_entity_decode(Yii::app()->facebook->imageShareText) : $image->description,
                        'og:image' => $link,
                        'og:image:secure_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/userimages/' . $image->filename,
                        'og:image:type' => image_type_to_mime_type($type),
                        'og:image:width' => $width,
                        'og:image:height' => $height,
                    );
                }
                return true;

                break;
            default:
                Yii::app()->facebook->ogTags = array(
                    'og:url' => Yii::app()->createAbsoluteUrl(Yii::app()->request->url),
                    'og:image' => 'http://' . $_SERVER['HTTP_HOST'] . '/webassets/' . Yii::app()->params['ticker']['icon'],
                );
                return true;
                break;
        }
    }

    public static function isConnected($userID) {
        $facebook = eUserFacebook::model()->findByAttributes(Array('user_id' => $userID));
        return(!is_null($facebook));
    }

}

?>
