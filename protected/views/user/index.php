<div id="content">
    <div class="you">
        <?php
        $this->renderPartial('/user/_sidebar', array(
            'user' => $user,
                )
        );
        ?>
        <div class="verticalRule">
            <img src="/webassets/images/you/profile.divider.png" />
        </div>
        <div class="youContent">
            <?php if (sizeof($videos) == 0): ?>
                <h2 class="bold" style="margin-top:50px">
                    <?php echo $question; ?>
                </h2>
                <h2 class="bold">
                    Click "record now" to get started!
                </h2>
                <div>
                    <a href="<?php echo Yii::app()->request->baseurl; ?>/record">
                        <img src="<?php echo Yii::app()->request->baseurl; ?>/webassets/images/buttons/Record-Now-Button.png" />
                    </a>
                </div>
            <?php else: ?>
                <div>
                    <h1>YOUR VIDEOS</h1>
                    <div class="sorter" style="font-size:12px;margin-bottom:5px;">View By:
                        <a class="bold" href="<?php echo Yii::app()->request->baseurl; ?>/you/recent">Most Recent</a> |
                        <a href="<?php echo Yii::app()->request->baseurl; ?>/you/views">Most Viewed</a> |
                        <a href="<?php echo Yii::app()->request->baseurl; ?>/you/rating">Highest Rated</a>
                    </div>
                    <div class="videoBlocks scroll-pane jspScrollable">
                        <?php
                        $this->renderPartial('/video/_blocks', array('videos' => $videos)
                        );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>