<?php
// page specific css
Yii::app()->clientScript->registerCssFile(Yii::app()->request->baseUrl . '/core/webassets/css/jquery.gritter.css');
Yii::app()->clientScript->registerCssFile(Yii::app()->request->baseUrl . '/core/webassets/css/chosen.css');
Yii::app()->clientScript->registerCssFile(Yii::app()->request->baseUrl . '/core/webassets/css/jquery.tagsinput.css');
Yii::app()->clientScript->registerCssFile(Yii::app()->request->baseUrl . '/core/webassets/css/bootstrap-toggle-buttons.css');
Yii::app()->clientScript->registerCssFile(Yii::app()->request->baseUrl . '/core/webassets/css/DT_bootstrap.css');
Yii::app()->clientScript->registerCssFile(Yii::app()->request->baseUrl . '/core/webassets/css/jquery-ui-1.10.0.css');
Yii::app()->clientScript->registerCssFile(Yii::app()->request->baseUrl . '/core/webassets/css/_contact.css');

// page specific js
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseurl . '/core/webassets/js/jquery.blockui.js', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseurl . '/core/webassets/js/chosen.jquery.min.js', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseurl . '/core/webassets/js/jquery.toggle.buttons.js', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseurl . '/core/webassets/js/app.js', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseurl . '/core/webassets/js/_contact.js', CClientScript::POS_END);
?>

<!-- BEGIN PAGE -->
<div class="fab-page-content">

    <!-- BEGIN PAGE TITLE & BREADCRUMB-->
    <div id="fab-top" style="background:#eeeded; margin-bottom:0px;">
        <h2 class="fab-title" style="color:#040404"><img class="floatLeft marginRight10" src="<?php echo Yii::app()->request->baseUrl; ?>/core/webassets/images/contact-image.png" style="margin-top:4px"/>Contact Us</h2>
    </div>
    <!-- END PAGE TITLE & BREADCRUMB-->
    <!-- BEGIN PAGE CONTAINER-->
    <div class="fab-container-fluid">
        <!-- END PAGE HEADER-->
        <!-- END PAGE HEADER-->
        <div class="fab-row-fluid">
            <div id="fab-contact">
                <?php if (Yii::app()->user->isSuperAdmin()): ?>
                        <?php
                        $contactRowFormat = '
                        <div style="font-weight:bold;">%s</div>
                        <div>%s</div>%s
                        ';
                        $form = $this->beginWidget('CActiveForm', array(
                            'id' => 'contact-form',
                            'enableAjaxValidation' => true,
                        ));
                        foreach ($contacts as $contact) {
                            echo sprintf($contactRowFormat, ucwords(str_replace('_', ' ', $contact->attribute)), $form->textField($contact, '[' . $contact->id . ']value', array('style' => 'width:250px')), $form->error($contact, 'value'));
                        }
                        echo(CHtml::submitButton('Save'));
                        $this->endWidget();
                        ?>
                    <?php else: ?>
                    <div class="contactPoints">
                        <?php
                        $contactRowFormat = '
                        <div style="font-weight:bold;">%s</div>
                        <div>%s</div>
                        ';
                        foreach ($contacts as $contact) {

                            echo sprintf($contactRowFormat, ucwords(str_replace('_', ' ', $contact->attribute)),$contact->value
                            );
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- END PAGE CONTAINER-->
</div>
<!-- END PAGE -->
