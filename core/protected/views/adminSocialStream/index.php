<?php
// page specific css
$cs = Yii::app()->clientScript;
$cs->registerCssFile(Yii::app()->request->baseUrl . '/core/webassets/css/adminSocialStream/index.css');
$cs->registerCssFile('/core/webassets/css/jquery.dataTables_themeroller.css');

// page specific js
$cs->registerScriptFile('/core/webassets/js/jquery.dataTables.min.js', CClientScript::POS_END);
$cs->registerScriptFile('/core/webassets/js/jquery.dataTables.currency.js', CClientScript::POS_END);
$cs->registerScriptFile(Yii::app()->request->baseurl . '/core/webassets/js/adminSocialStream/index.js', CClientScript::POS_END);
$cs->registerScript('make-data-table','makeDataTable()',CClientScript::POS_READY);
$cs->registerScript('streamer','startStream()',CClientScript::POS_READY);
$cs->registerScript('formHandler','searchHandler()',CClientScript::POS_READY);
if(Yii::app()->request->getParam('hashtag') != '') {
    $cs->registerScript('lazyClient','toggleStream();$("#stream").submit()',CClientScript::POS_READY);
}
$this->renderPartial('/admin/_csrfToken');
?>


<!-- BEGIN PAGE -->
<div class="fab-page-content">
    <!-- BEGIN PAGE CONTAINER-->
    <!-- BEGIN PAGE TITLE & BREADCRUMB-->
    <div style="background:#4bb55a" id="fab-top">
        <h2 style="color:white" class="fab-title"><img class="floatLeft" style="margin-right: 10px" src="<?php echo Yii::app()->request->baseUrl; ?>/core/webassets/images/social-image.png">Social Stream</h2>
    </div>
    <!-- END PAGE TITLE & BREADCRUMB-->
    <div class="fab-container-fluid">
        <div style="padding:20px;">
            <h2>Social Streaming (Twitter)</h2>
            <div>
                <form id="stream">
                    <div>
                        <label for="track">Stream Terms:</label>
                    </div>
                    <div>
                        <?php $purifier = new CHtmlPurifier(); ?>
                        <input type="text" id="track" name="track" value="<?php echo (Yii::app()->request->getParam('hashtag') != '') ? $purifier->purify(Yii::app()->request->getParam('hashtag')) : ''; ?>"/>
                        <input type="submit" />
                    </div>
                </form>
            </div>
            <div style="clear:both;">
            </div>
            <?php
                if(Yii::app()->twitter->advancedFilters === true){
                    $this->renderPartial('/admin/_twitterFilters',array('questions'=>$questions,'cs'=>$cs));
                }
            ?>
            <div style="clear:both"></div>
            <hr />
            <div style="clear:both">
                <button id="toggleStream" type="button" onclick="toggleStream();">Pause Stream</button>
            </div>
            <div style="margin-top:20px;" id="ret"></div>
            <div style="margin-top:20px;" id="streamTotal"></div>
            <div style="margin-top:20px;">
                <table id="resultsTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Question</th>
                            <th>Avatar</th>
                            <th>From</th>
                            <th>Timestamp</th>
                            <th>Date</th>
                            <th>Content</th>
                            <th>Category</th>
                            <th>Tweet Clean</th>
                            <th>Account Clean</th>
                            <th>Media</th>
                            <th>Tweet Language</th>
                            <th>Account Language</th>
                            <th>Verified</th>
                            <th>Has Location</th>
                            <th>Place</th>
                            <th>Place Coordinates</th>
                            <th>Tweet Coordinates</th>
                            <th>Followers</th>
                            <th>Following</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END PAGE -->
</div>
