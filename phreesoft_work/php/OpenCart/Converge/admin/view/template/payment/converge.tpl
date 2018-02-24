<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-converge" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-list"></i> <?php echo $heading_title; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-converge" class="form-horizontal">
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-merchant_id"><?php echo $entry_merchant_id; ?></label>
            <div class="col-sm-10">
              <input type="text" name="converge_merchant_id" value="<?php echo $converge_merchant_id; ?>" placeholder="<?php echo $entry_merchant_id; ?>" id="input-merchant_id" class="form-control" />
              <?php if ($error_merchant_id) { ?>
              <div class="text-danger"><?php echo $error_merchant_id; ?></div>
              <?php } ?>
            </div>
          </div>
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-user_id"><?php echo $entry_user_id; ?></label>
            <div class="col-sm-10">
              <input type="text" name="converge_user_id" value="<?php echo $converge_user_id; ?>" placeholder="<?php echo $entry_user_id; ?>" id="input-user_id" class="form-control" />
              <?php if ($error_user_id) { ?>
              <div class="text-danger"><?php echo $error_user_id; ?></div>
              <?php } ?>
            </div>
          </div>
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-pin"><?php echo $entry_pin; ?></label>
            <div class="col-sm-10">
              <input type="text" name="converge_pin" value="<?php echo $converge_pin; ?>" placeholder="<?php echo $entry_pin; ?>" id="input-pin" class="form-control" />
              <?php if ($error_pin) { ?>
              <div class="text-danger"><?php echo $error_pin; ?></div>
              <?php } ?>
            </div>
          </div>
       	  <div class="form-group">
            <label class="col-sm-2 control-label" for="input-server"><?php echo $entry_server; ?></label>
            <div class="col-sm-10">
              <select name="converge_server" id="input-server" class="form-control">
                <?php if ($converge_server == 'live') { ?>
                <option value="live" selected="selected"><?php echo $text_live; ?></option>
                <?php } else { ?>
                <option value="live"><?php echo $text_live; ?></option>
                <?php } ?>
                <?php if ($converge_server == 'test') { ?>
                <option value="test" selected="selected"><?php echo $text_test; ?></option>
                <?php } else { ?>
                <option value="test"><?php echo $text_test; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-method"><?php echo $entry_method; ?></label>
            <div class="col-sm-10">
              <select name="converge_method" id="input-method" class="form-control">
                <?php if ($converge_method == 'auth') { ?>
                <option value="auth" selected="selected"><?php echo $text_authorization; ?></option>
                <?php } else { ?>
                <option value="auth"><?php echo $text_authorization; ?></option>
                <?php } ?>
                <?php if ($converge_method == 'cap') { ?>
                <option value="cap" selected="selected"><?php echo $text_capture; ?></option>
                <?php } else { ?>
                <option value="cap"><?php echo $text_capture; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-total"><span data-toggle="tooltip" title="<?php echo $help_total; ?>"><?php echo $entry_total; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="converge_total" value="<?php echo $converge_total; ?>" placeholder="<?php echo $entry_total; ?>" id="input-total" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_order_status; ?></label>
            <div class="col-sm-10">
              <select name="converge_order_status_id" id="input-order-status" class="form-control">
                <?php foreach ($order_statuses as $order_status) { ?>
                <?php if ($order_status['order_status_id'] == $converge_order_status_id) { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
            <div class="col-sm-10">
              <select name="converge_geo_zone_id" id="input-geo-zone" class="form-control">
                <option value="0"><?php echo $text_all_zones; ?></option>
                <?php foreach ($geo_zones as $geo_zone) { ?>
                <?php if ($geo_zone['geo_zone_id'] == $converge_geo_zone_id) { ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
            <div class="col-sm-10">
              <select name="converge_status" id="input-status" class="form-control">
                <?php if ($converge_status) { ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_sort_order; ?></label>
            <div class="col-sm-10">
              <input type="text" name="converge_sort_order" value="<?php echo $converge_sort_order; ?>" placeholder="<?php echo $entry_sort_order; ?>" id="input-sort-order" class="form-control" />
            </div>
          </div>
      </form>
    </div>
  </div>
</div>
<?php echo $footer; ?>