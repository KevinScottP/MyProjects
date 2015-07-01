<form id="payment" class="form-horizontal">
  <fieldset>
	<legend><?php echo $text_credit_card; ?></legend>
	<div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-owner"><?php echo $entry_cc_owner; ?></label>
      <div class="col-sm-5">
        <input type="text" name="cc_frist" value="<?php echo $cc_frist; ?>" placeholder="<?php echo $entry_frist; ?>" id="input-frist" class="form-control" />
      </div>
      <div class="col-sm-5">
        <input type="text" name="cc_last" value="<?php echo $cc_last; ?>" placeholder="<?php echo $entry_last; ?>" id="input-last" class="form-control" />
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-number"><?php echo $entry_cc_number; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_number" value="" placeholder="<?php echo $entry_cc_number; ?>" id="input-cc-number" class="form-control" />
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-expire-date"><?php echo $entry_cc_expire_date; ?></label>
      <div class="col-sm-3">
        <select name="cc_expire_date_month" id="input-cc-expire-date" class="form-control">
          <?php foreach ($months as $month) { ?>
          <option value="<?php echo $month['value']; ?>"><?php echo $month['text']; ?></option>
          <?php } ?>
        </select>
       </div>
       <div class="col-sm-3">
        <select name="cc_expire_date_year" class="form-control">
          <?php foreach ($year_expire as $year) { ?>
          <option value="<?php echo $year['value']; ?>"><?php echo $year['text']; ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-cvv2"><?php echo $entry_cc_cvv2; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_cvv2" value="" placeholder="<?php echo $entry_cc_cvv2; ?>" id="input-cc-cvv2" class="form-control" />
      </div>
    </div>
  </fieldset>
</form>
<div class="buttons">
  <div class="pull-right">
    <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="btn btn-primary" />
  </div>
</div>
<script type="text/javascript"><!--
$('#button-confirm').on('click', function() {
	$.ajax({
		url: 'index.php?route=payment/converge/send',
		type: 'post',
		data: $('#payment :input'),
		dataType: 'json',
		beforeSend: function() {
	      $('#button-confirm').attr('disabled', true);
	      $('#payment').before('<div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $text_wait; ?></div>');
      	},
		complete: function() {
	      $('#button-confirm').attr('disabled', false);
	      $('.attention').remove();
		},
		success: function(json) {
			if (json['error']) {
				alert(json['error']);
			}

			if (json['success']) {
				location = json['success'];
			}
		}
	});
});
//--></script>