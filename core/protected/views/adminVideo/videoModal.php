<?php if ($video == null): ?>
    Unable to find video with id <?php echo $id; ?>
<?php else: ?>
    <?php
    Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseurl . '/core/webassets/js/adminVideo/videoModal.js', CClientScript::POS_END);
    ?>
    <div class="fab-lightbox-videos">
        <div class="fab-row-fluid">
            <div class="fab-detail-videos fab-left">
                <div class="fab-details-myslider">
                    <section class="slider" style="margin-left: 30px;">
                        <h1><?php echo $video->title; ?></h1>
                        <div class="fab-myslider">
                            <ul class="slides">
                                <li>
                                    <div style="background:#8c8b8b">
                                        <div class="player" id="player">
                                            <!--
                                            <img id="modalVideo" alt="" src="">
                                                <div style="position: absolute; bottom: 0;">
                                                    <button id="btnRotateVideoLeft" href="#" class="btn"><i class="icon-white icon-repeat"></i> -90°</button>
                                                    <button id="btnRotateVideoRight" href="#" class="btn"><i class="icon-white icon-repeat icon-flipped"></i> +90°</button>
                                                    <div style="clear: both;"></div>
                                                </div>
                                            -->
                                            <?php
                                            //$player = (isset($video->brightcove_id)) ? '/admin/_brightcovePlayer' : '/admin/_fallbackPlayer';
                                            $player = (isset($video->brightcoves[0]->brightcove_id) && is_numeric($video->brightcoves[0]->brightcove_id) && !empty($countAndState) && $countAndState->itemState == 'ACTIVE') ? '/admin/_brightcovePlayer' : '/admin/_fallbackPlayer';
                                            $this->renderPartial($player, array(
                                                'video' => $video,
                                                    )
                                            );
                                            ?>

                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>
            <div class="fab-clear"></div>
        </div>
        <div class="fab-clear"  style="height:1px;margin-top: 20px;"></div>
        <div class="fab-lightbox-question">
            <div class="fab-accordion_list">
                <div class="fab-accordion_head fab-question-black">
                    <div class="fab-lightbox-margin">
                        <p align="center" style="margin-bottom:5px;font-size:12px"><b>||||||||||||||||||||||||||||||||||||||||||||</b></p>
                        <?php if(!empty($video->question->question)): ?>
                        <p id="fab-question-content">
                            <b>Question:</b> <?php echo $video->question->question; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div style="clear:right"></div>
                </div>
                <div class="fab-accordion_body">
                    <div id="fab-video-filter-form">
                        <form name="video-form">
                            <div class="fab-form-left">
                                <div id="modalTabs" style="width:616px">
                                    <ul>
                                        <li><a href="#tab-tag">Tag</a></li>
                                        <li><a href="#tab-thumbnail" id="tabThumbnailTrigger">Thumbnail</a></li>
                                        <li><a href="#tab-share">Share</a></li>
                                        <?php
                                        if (Yii::app()->params['video']['adminAllowAmplify']):
                                            echo '<li><a href="#tab-amplify">Amplify</a></li>';
                                        endif;
                                        ?>
                                        <li style="color:#666;font-weight:initial;padding:8px">Audio</li>
                                        <li><a href="#tab-history" id="tabHistoryTrigger">History</a></li>
                                        <!--
                                        <li><a href="#tab-audio">Audio</a></li>
                                        <li><a href="#tab-language">Language</a></li>
                                        -->
                                    </ul>
                                    <div id="tab-tag">
                                        <div class="fab-clear" style="height:4px;"></div>
                                        <?php
                                        $form = $this->beginWidget('CActiveForm', array(
                                            'id' => 'tag-form',
                                                //'enableAjaxValidation' => true,
                                                //'enableClientValidation' => true,
                                                //'clientOptions' => array(
                                                //    'validateOnSubmit' => true,
                                                //),
                                        ));
                                        ?>
                                        <?php echo $form->textField($tagModel, 'title', array('id' => 'videoTags')); ?>
                                        <?php echo $form->error($tagModel, 'title'); ?>
                                        <?php echo CHtml::button('Save Tags', array('id' => 'saveTagTrigger', 'class' => 'fab-filter')); ?>
                                        <?php $this->endWidget(); ?>
                                        <div class="fab-clear"></div>
                                    </div>
                                    <div id="tab-thumbnail">

                                    </div>
                                    <div id="tab-share" style="overflow: hidden;">
                                        <div>
                                            <?php echo CHtml::textField('message', '', array('maxlength' => '140', 'class' => 'counter', 'style' => 'width:500px', 'placeholder' => 'Text to share with video')); ?>
                                        </div>
                                        <div class="fab-pull-left" style="width: 50%;">
                                            <a href="#" id="clientShareTwitterModalTrigger" rev="<?php echo($id); ?>" style="color: #FFF">
                                                <img src="<?php echo Yii::app()->request->baseUrl; ?>/core/webassets/images/twitter-transparent.png" />
                                                Post to Twitter
                                            </a>
                                        </div>
                                        <div class="fab-pull-left" style="width: 50%;">
                                            <a href="#" id="clientShareFacebookModalTrigger" rev="<?php echo($id); ?>" style="color: #FFF">
                                                <img src="<?php echo Yii::app()->request->baseUrl; ?>/core/webassets/images/facebook-transparent.png" />
                                                Post to Facebook
                                            </a>
                                        </div>
                                    </div>
                                    <?php
                                    if (Yii::app()->params['video']['adminAllowAmplify']):
                                        ?>
                                        <div id="tab-amplify">
                                            <div style='color:#FFF;'>
                                                <div style='float:left'>
                                                    <input type='hidden' value='' id='amplifyPreroll' />
                                                    <input type='hidden' value='' id='amplifyPostroll' />
                                                    <div>
                                                        <div>Pre-roll: <span id="defaultPrerollContainer"><input type="checkbox" id="setDefaultPreroll">&nbsp;Set default</span></div>
                                                        <a class="prev browse left"></a>
                                                        <div class="amp_scrollable">
                                                            <div class="items preRollItems">
                                                                <?php if (!is_null($videoSelections)): ?>
                                                                    <?php
                                                                    $i = 0;
                                                                    $ii = 0;
                                                                    $totVideos = count($videoSelections);
                                                                    ?>
                                                                    <?php foreach ($videoSelections as $v): ?>
                                                                        <?php $defaultPreRoll = ''; ?>
                                                                        <?php if ($v->is_default_ad == 1 && $v->roll_type == 1): ?>
                                                                            <?php $defaultPreRoll = 'defaultItem'; ?>
                                                                            <script>$('#amplifyPreroll').val(<?php echo $v->id; ?>);</script>
                                                                        <?php endif; ?>
                                                                        <?php //if($i == 0) { print '<div>';}  ?>
                                                                        <img alt="<?php echo $v->id; ?>" class="preRollImage <?php echo $defaultPreRoll; ?>" src="<?php echo Yii::app()->request->baseUrl; ?>/<?php echo basename(Yii::app()->params['paths']['video']); ?>/<?php echo $v->thumbnail; ?><?php echo Yii::app()->params['video']['imageExt']; ?>" />

                                                                        <?php //if(++$i == 5 || ++$ii === $totVideos) { print '</div>'; $i = 0; }?>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>

                                                            </div>
                                                        </div>
                                                        <a class="next browse right"></a>
                                                    </div>
                                                    <div>
                                                        <div>Post-roll: <span id="defaultPostrollContainer"><input type="checkbox" id="setDefaultPostroll">&nbsp;Set default</span></div>
                                                        <a class="prev browse left"></a>
                                                        <div class="amp_scrollable">
                                                            <div class="items postRollItems">
                                                                <?php if (!is_null($videoSelections)): ?>
                                                                    <?php
                                                                    $i = 0;
                                                                    $ii = 0;
                                                                    $totVideos = count($videoSelections);
                                                                    ?>
                                                                    <?php foreach ($videoSelections as $v): ?>

                                                                        <?php $defaultPostRoll = ''; ?>
                                                                        <?php if ($v->is_default_ad == 1 && $v->roll_type == 2): ?>
                                                                            <?php $defaultPostRoll = 'defaultItem'; ?>
                                                                            <script>$('#amplifyPostroll').val(<?php echo $v->id; ?>);</script>
                                                                        <?php endif; ?>

                                                                        <?php //if($i == 0) { print '<div>';}?>
                                                                        <img alt="<?php echo $v->id; ?>" class="postRollImage <?php echo $defaultPostRoll; ?>" src="<?php echo Yii::app()->request->baseUrl; ?>/<?php echo basename(Yii::app()->params['paths']['video']); ?>/<?php echo $v->thumbnail; ?><?php echo Yii::app()->params['video']['imageExt']; ?>" />
                                                                        <?php //if($i == 4 || ++$ii === $totVideos) { print '</div>'; $i = 0; } else { ++$i; }  ?>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <a class="next browse right"></a>
                                                    </div>
                                                    <div>&nbsp;</div>
                                                    <div>
                                                        <div style='display:inline-block;min-width:100px;'>
                                                            Your Message:
                                                        </div>
                                                        <?php echo CHtml::textField('amplifyText', '', array('maxlength' => '118', 'class' => 'counter')); ?>
                                                    </div>
                                                </div>
                                                <div style='float:left;margin-left:10px;margin-bottom:10px'>
                                                    <div>Preview:</div>
                                                    <div style='border:1px solid black;width:150px;height:85px'>
                                                        <input type='hidden' value='<?php echo $video->id; ?>' id='amplifyBase' />
                                                        <div id='amplifyPreviewVideo'>
                                                            <?php
                                                            $this->renderPartial('/admin/_videoPlayer', array(
                                                                'videoInfo' => Array(
                                                                    'videofile' => '/' . basename(Yii::app()->params['paths']['video']) . "/{$video->filename}" . Yii::app()->params['video']['postExt'],
                                                                    'image' => '/' . basename(Yii::app()->params['paths']['video']) . "/{$video->thumbnail}" . Yii::app()->params['video']['imageExt'],
                                                                    'width' => 150,
                                                                    'height' => 85,
                                                                //'width' => 243,
                                                                //'height' => 137,
                                                                )
                                                            ));
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div style='clear:both;float:right;padding-bottom:10px'>
                                                    <?php echo CHtml::Button('Amplify!', Array('onclick' => 'amplify()')); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div id="tab-history">

                                    </div>
                                    <!--
                                    <div id="tab-audio">
                                        <p>PENDING DEVELOPMENT!</p>
                                    </div>
                                    <div id="tab-language">
                                        <p>PENDING DEVELOPMENT!</p>
                                    </div>
                                    -->
                                </div>
                            </div>
                            <div class="fab-form-right">
                                <div>
                                    <?php if (Yii::app()->user->isSuperAdmin() || Yii::app()->params['enableVideoDownload'] === true): ?>
                                    <button id="fab-modal-download-button" value="<?php echo $id; ?>" class="fab-download-button">Download Video</button>
                                    <?php endif; ?>
                                </div>
                                <div class="fab-clear"></div>
                                <div class="fab-left">
                                    <?php if(isset($video->user->userTwitters[0]->twitter_user_id)) $twUsername = TwitterUtility::getUsernameFromID($video->user->userTwitters[0]->twitter_user_id); ?>
                                    <div class="fab-grey-line" style="margin-top:5px;margin-bottom:5px"></div>
                                    <p><b>Name:</b> <?php echo CHtml::encode($video->user->last_name); ?>, <?php echo CHtml::encode($video->user->first_name); ?></p>
                                    <p><b>User ID:</b> <?php echo CHtml::encode($video->user->first_name); ?> <?php echo CHtml::encode($video->user->last_name); ?></p>
                                    <?php if(Yii::app()->params['showUserCountry']):?>
                                        <?php if(isset($video->user->userLocations[0]->country)): ?>
                                    <p><b>Country: </b> <?php echo $video->user->userLocations[0]->country; ?></p>
                                        <?php endif;?>
                                    <?php endif; ?>
                                    <p><b>Views:</b> <?php echo $video->views; ?></p>
                                    <p><b>UID:</b> <?php echo $video->source_user_id;?></p>
                                    <?php if(isset($video->user->userPhones[0]->number)): ?>
                                    <p><b>Phone:</b> <?php echo CHtml::encode($video->user->userPhones[0]->number); ?></p>
                                    <?php endif;?>
                                    <?php if(isset($twUsername)): ?>
                                    <p><b>Twitter:</b> <?php echo CHtml::encode(($twUsername == 'Twitter User') ? 'n/a' : $twUsername); ?></p>
                                    <?php endif;?>
                                    <div class="fab-grey-line"></div>
                                    <div style="margin-top:5px">
                                        <div class="fab-ratings fab-left">
                                            <span class="star-rating-control"><div class="rating-cancel" style="display: block;"><a title="Cancel Rating"></a></div><div role="text" aria-label="" class="star-rating rater-0 star star-rating-applied star-rating-live"><a title="on">on</a></div><div role="text" aria-label="" class="star-rating rater-0 star star-rating-applied star-rating-live"><a title="on">on</a></div><div role="text" aria-label="" class="star-rating rater-0 star star-rating-applied star-rating-live"><a title="on">on</a></div><div role="text" aria-label="" class="star-rating rater-0 star star-rating-applied star-rating-live"><a title="on">on</a></div><div role="text" aria-label="" class="star-rating rater-0 star star-rating-applied star-rating-live"><a title="on">on</a></div></span><input name="star1" type="radio" class="star star-rating-applied" style="display: none;">
                                            <input name="star1" type="radio" class="star star-rating-applied" style="display: none;">
                                            <input name="star1" type="radio" class="star star-rating-applied" style="display: none;">
                                            <input name="star1" type="radio" class="star star-rating-applied" style="display: none;">
                                            <input name="star1" type="radio" class="star star-rating-applied" style="display: none;">
                                        </div>
                                        <div class="fab-right">
                                            <a href="mailto:<?php echo $video->user->userEmails[0]->email; ?>"><img src="<?php echo Yii::app()->request->baseUrl; ?>/core/webassets/images/mail.png"/></a>
                                        </div>
                                    </div>
                                    <div class="fab-clear"></div>
                                    <div class="fab-grey-line" style="margin-top:5px;"></div>
                                    <div><?php echo CHtml::dropDownList('optSendEmail','Y', array('Y'=>'Send Email','N'=>'Don`t Send Email')) ?></div>
                                    <div class="fab-left" style="margin-top:3px; margin-bottom: 5px;">
                                        <button id="fab-modal-accept-button"  value="<?php echo $video->status; ?>" class="fab-accept-button" onclick="updateVideoStatus('accepted', '<?php echo $currentStatus; ?>', <?php echo $video->id; ?>);
                                                                    return false;">Accept</button>
                                        <button id="fab-modal-deny-button" value="<?php echo $video->status; ?>" class="fab-deny-button" onclick="updateVideoStatus('denied', '<?php echo $currentStatus; ?>', <?php echo $video->id; ?>);
                                                                    return false;">Deny</button>
                                    </div>
                                    <div class="fab-left">
                                        <?php
                                        if($video->is_preroll == 0){
                                            $addPreRollDisplay = "display: block;";
                                            $removePreRollDisplay = "display: none;";
                                        } else {
                                            $addPreRollDisplay = "display: none;";
                                            $removePreRollDisplay = "display: block;";
                                        }
                                        ?>
                                        
                                        <button id="fab-modal-add-preroll-button" style="<?php echo $addPreRollDisplay; ?>" class="fab-preroll-button" onclick="updateVideoPreroll('1', <?php echo $video->id; ?>);
                                                                    return false;">Add Pre-Roll</button>
                                        <button id="fab-modal-remove-preroll-button" style="<?php echo $removePreRollDisplay; ?>" class="fab-preroll-button" onclick="updateVideoPreroll('0', <?php echo $video->id; ?>);
                                                                    return false;">Remove Pre-Roll</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div style="clear: both"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="videoId" style="display: none;"><?php echo $id; ?></div>
<?php endif; ?>
