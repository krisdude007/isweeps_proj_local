<div id='choose_package' class="campaign_subtitle">
	<span>Choose Package&nbsp;&nbsp;</span>
	<span id='toggle' class='<?php echo $campaign->package == 'A' ? 'toggle_up' : 'toggle_down' ;?>'>&nbsp;&nbsp;</span>
</div>
<div class="campaign_divider"></div>

<div id='package' class='row-fluid'>
 	<div class='<?php echo ($campaign->package == 'A') ? 'package_selected' : 'package_unselected'; ?>'>
		<div class='package_name'>
			Package A
		</div>
		<div class='package_price'>FREE</div>
		<div class='package_divider_white'></div>
		<div class='package_content'>
			<div class='package_content_row'>>  10 Weekly Posts</div>
			<div class='package_content_row'>>  2 Socail Platforms <br/>&nbsp;&nbsp;(1 Account Per Platform)</div>
			<div class='package_content_row'>>  1 administrator</div>
			<div class='package_content_row'>>  Basic Analytics</div>
			<div class='package_content_row'>>  Tracking Tags</div>
			<div class='package_content_row'>>  Link Shorterner</div>
		</div>
		<div class='package_button <?php if($campaign->package != 'B') echo "show_cursor";?>'<?php if($campaign->package != 'A'): ?> data-toggle="modal" data-target="#upgrade_modal_A"<?php endif;?>>
		<?php if($campaign->package == 'A'): ?> 
			Selected 
		<?php else: ?>
			Downgrade
		<?php endif;?>
		</div>
	</div>
	<div class='<?php echo ($campaign->package == 'B') ? 'package_selected' : 'package_unselected'; ?>'>
		<div class='package_name'>
			Package B
		</div>
		<div class='package_price'>$99.95 / mo</div>
		<div class='package_divider_white'></div>
		<div class='package_content'>
			<div class='package_content_row'>>  20 Weekly Posts</div>
			<div class='package_content_row'>>  Add up to 4 Socail Platforms <br/>&nbsp;&nbsp;(5 Accounts Per Platform)</div>
			<div class='package_content_row'>>  10 administrator</div>
			<div class='package_content_row'>>  Premium Analytics</div>
			<div class='package_content_row'>>  Tracking Tags</div>
			<div class='package_content_row'>>  Link Shorterner</div>
			<div class='package_content_row'>>  White Labeled Posts</div>
		</div>
		<div class='package_button <?php if($campaign->package != 'B') echo "show_cursor";?>'<?php if($campaign->package != 'B'): ?> data-toggle="modal" data-target="#upgrade_modal_B" <?php endif; ?>>
		<?php if($campaign->package == 'B'): ?> 
			Selected 
		<?php elseif($campaign->package == 'A'): ?>
			Upgrade
		<?php else:?>
			Downgrade
		<?php endif;?>
		</div>
	</div>
	<div class='<?php echo ($campaign->package == 'C') ? 'package_selected' : 'package_unselected'; ?>'>
		<div class='package_name'>
			Package C
		</div>
		<div class='package_price'>$149.95 / mo</div>
		<div class='package_divider_white'></div>
		<div class='package_content'>
			<div class='package_content_row'>>  35 Weekly Posts</div>
			<div class='package_content_row'>>  Add up to 6 Socail Platforms <br/>&nbsp;&nbsp;(Unlimited Accounts Per Platform)</div>
			<div class='package_content_row'>>  Unlimited administrator</div>
			<div class='package_content_row'>>  Premium Analytics</div>
			<div class='package_content_row'>>  Tracking Tags</div>
			<div class='package_content_row'>>  Link Shorterner</div>
			<div class='package_content_row'>>  White Labeled Posts</div>
			<div class='package_content_row'>>  Extended Support Hours</div>
		</div>
		<div class='package_button <?php if($campaign->package != 'C') echo "show_cursor";?>'<?php if($campaign->package != 'C'): ?> data-toggle="modal" data-target="#upgrade_modal_C"<?php endif;?>>
		<?php if($campaign->package == 'C'): ?> 
			Selected 
		<?php else: ?>
			Upgrade
		<?php endif;?>
		</div>
	</div>
	 <div class='row'>
    	<a href='<?php echo Yii::app()->createUrl('/adminCampaign'); ?>' class='btn btn-primary btn-large'>Finish</a>
    </div>
</div>
             
         
<div id="upgrade_modal_A" class="modal hide fade upgrade_model" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header upgrade_modal_header"> 
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="icon-remove-sign"></i></button>
    <div id="myModalLabel" class='text-center'>Upgrade</div>
  </div>
  <div class="modal-body text-center">
    <p class='upgrade_modal_title'>You have chosen to downgrade to Package A</p>
    <p><span class='upgrade_price'>Free</span><span class='upgrade_price_month'>/month</span></p>
    <p>By clicking continue, you are authorizing Youtoo Technologies to drop your current package <?php echo $campaign->package;?> and make change to your monthly invoice.</p>
    <p>A confirmation email will be sent to you.</p>
  </div>
  <div class="text-center">
  <?php echo CHtml::ajaxButton('Continue', $this->createUrl('adminCampaign/upgrade'),
                  array('type'=>'post', 'dataType'=>'json', 'data'=>http_build_query(array('plan'=>'a','id'=>$campaign->id)),
                      'success'=>'js:function(data){ if(data.status == 1) { window.location = "/adminCampaign";}}'
                  ),
                  array('class'=>'btn btn-primary btn-large text-center',
                      'data-loading-text'=>'Wait, loading',
                      'onclick'=>'if(confirm("Are you sure you want to continue?")){$(".upgrade_model").hide();}else{return false;}'
                  )
       );
  ?></div>
</div>
<div id="upgrade_modal_B" class="modal hide fade upgrade_model" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header upgrade_modal_header"> 
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="icon-remove-sign"></i></button>
    <div id="myModalLabel" class='text-center'>Upgrade</div>
  </div>
  <div class="modal-body text-center">
    <p class='upgrade_modal_title'>You have chosen to upgrade to Package B</p>
    <p><span class='upgrade_price'>$99.95</span><span class='upgrade_price_month'>/month</span></p>
    <p>By clicking continue, you are authorizing Youtoo Technologies to include this charge on your monthly invoice.</p>
    <p>A confirmation email will be sent to you.</p>
  </div>
  <div class="text-center">
  <?php echo CHtml::ajaxButton('Continue', $this->createUrl('adminCampaign/upgrade'),
                  array('type'=>'post', 'dataType'=>'json', 'data'=>http_build_query(array('plan'=>'b','id'=>$campaign->id)),
                      'success'=>'js:function(data){ if(data.status == 1) { window.location = "/adminCampaign";}}'
                  ),
                  array('class'=>'btn btn-primary btn-large text-center',
                      'data-loading-text'=>'Wait, loading',
                      'onclick'=>'if(confirm("Are you sure you want to continue?")){$(".upgrade_model").hide();}else{return false;}'
                  )
       );
  ?>
  </div>
</div>
<div id="upgrade_modal_C" class="modal hide fade upgrade_model" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header upgrade_modal_header"> 
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="icon-remove-sign"></i></button>
    <div id="myModalLabel" class='text-center'>Upgrade</div>
  </div>
  <div class="modal-body text-center">
    <p class='upgrade_modal_title'>You have chosen to upgrade to Package C</p>
    <p><span class='upgrade_price'>$149.95</span><span class='upgrade_price_month'>/month</span></p>
    <p>By clicking continue, you are authorizing Youtoo Technologies to include this charge on your monthly invoice.</p>
    <p>A confirmation email will be sent to you.</p>
  </div>
  <div class="text-center">
  <?php echo CHtml::ajaxButton('Continue', $this->createUrl('adminCampaign/upgrade'),
                  array('type'=>'post', 'dataType'=>'json', 'data'=>http_build_query(array('plan'=>'c','id'=>$campaign->id)),
                      'success'=>'js:function(data){ if(data.status == 1) { window.location = "/adminCampaign";}}'
                  ),
                  array('class'=>'btn btn-primary btn-large text-center',
                      'data-loading-text'=>'Wait, loading',
                      'onclick'=>'if(confirm("Are you sure you want to continue?")){$(".upgrade_model").hide();}else{return false;}'
                  )
       );
  ?></div>
</div>