<?php echo $header; ?><?php echo $column_left; ?>
<div id="content" xmlns="http://www.w3.org/1999/html">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-pp-pro-uk" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
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
        <?php if ($success) { ?>
        <div class="alert alert-success"><i class="fa fa-exclamation-circle"></i> <?php echo $success; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-tranzzo" class="form-horizontal">
                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-pos_id"><?php echo $entry_pos_id; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="tranzzo_pos_id" value="<?php echo $tranzzo_pos_id; ?>" placeholder="<?php echo $entry_pos_id; ?>" id="input-pos_id" class="form-control" />
                            <?php if ($error_pos_id) { ?>
                            <div class="text-danger"><?php echo $error_pos_id; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-api_key"><?php echo $entry_api_key; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="tranzzo_api_key" value="<?php echo $tranzzo_api_key; ?>" placeholder="<?php echo $entry_api_key; ?>" id="input-api_key" class="form-control" />
                            <?php if ($error_api_key) { ?>
                            <div class="text-danger"><?php echo $error_api_key; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-api_secret"><?php echo $entry_api_secret; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="tranzzo_api_secret" value="<?php echo $tranzzo_api_secret; ?>" placeholder="<?php echo $entry_api_secret; ?>" id="input-api_secret" class="form-control" />
                            <?php if ($error_api_secret) { ?>
                            <div class="text-danger"><?php echo $error_api_secret; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-endpoints_key"><?php echo $entry_endpoints_key; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="tranzzo_endpoints_key" value="<?php echo $tranzzo_endpoints_key; ?>" placeholder="<?php echo $entry_endpoints_key; ?>" id="input-endpoints_key" class="form-control" />
                            <?php if ($error_endpoints_key) { ?>
                            <div class="text-danger"><?php echo $error_endpoints_key; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-total"><span data-toggle="tooltip" title="<?php echo $help_total; ?>"><?php echo $entry_total; ?></span></label>
                        <div class="col-sm-10">
                            <input type="text" name="tranzzo_total" value="<?php echo $tranzzo_total; ?>" placeholder="<?php echo $entry_total; ?>" id="input-pos_id" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group required">
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-order-status-complete"><?php echo $entry_order_status_complete; ?></label>
                            <div class="col-sm-10">
                                <select name="tranzzo_order_status_complete_id" id="input-order-status-complete" class="form-control">
                                    <option value="">---</option>
                                    <?php foreach ($order_statuses as $order_status) { ?>
                                    <?php if ($order_status['order_status_id'] == $tranzzo_order_status_complete_id) { ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php } else { ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php } ?>
                                    <?php } ?>
                                </select>

                                <?php if ($error_order_status_complete_id) { ?>
                                <div class="text-danger"><?php echo $error_order_status_complete_id; ?></div>
                                <?php } ?>
                                <?php if ($error_order_status) { ?>
                                <div class="text-danger"><?php echo $error_order_status; ?></div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group required">
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-order-status-failure"><?php echo $entry_order_status_failure; ?></label>
                            <div class="col-sm-10">
                                <select name="tranzzo_order_status_failure_id" id="input-order-status-failure" class="form-control">
                                    <option value="">---</option>
                                    <?php foreach ($order_statuses as $order_status) { ?>
                                    <?php if ($order_status['order_status_id'] == $tranzzo_order_status_failure_id) { ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php } else { ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php } ?>
                                    <?php } ?>
                                </select>

                                <?php if ($error_order_status_failure_id) { ?>
                                <div class="text-danger"><?php echo $error_order_status_failure_id; ?></div>
                                <?php } ?>
                                <?php if ($error_order_status) { ?>
                                <div class="text-danger"><?php echo $error_order_status; ?></div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group required">
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><?php echo $entry_order_status_listen; ?></label>
                            <div class="col-sm-10">
                                <?php foreach ($order_statuses as $order_status) { ?>
                                <?php if (is_array($tranzzo_order_status_listen) && in_array($order_status['order_status_id'], $tranzzo_order_status_listen)) { ?>
                                <label class="lb-order-listen"><input type="checkbox" name="tranzzo_order_status_listen[]" value="<?php echo $order_status['order_status_id']; ?>" checked="checked"><?php echo $order_status['name']; ?></label>
                                <?php } else { ?>
                                <label class="lb-order-listen"><input type="checkbox" name="tranzzo_order_status_listen[]" value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></label>
                                <?php } ?>
                                <?php } ?>

                                <?php if ($error_order_status_listen) { ?>
                                <div class="text-danger"><?php echo $error_order_status_listen; ?></div>
                                <?php } ?>
                                <?php if ($error_order_status) { ?>
                                <div class="text-danger"><?php echo $error_order_status; ?></div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_sort_order; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="tranzzo_sort_order" value="<?php echo $tranzzo_sort_order; ?>" placeholder="<?php echo $entry_sort_order; ?>" id="input-sort-order" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
                        <div class="col-sm-10">
                            <select name="tranzzo_geo_zone_id" id="input-geo-zone" class="form-control">
                                <option value="0"><?php echo $text_all_zones; ?></option>
                                <?php foreach ($geo_zones as $geo_zone) { ?>
                                <?php if ($geo_zone['geo_zone_id'] == $tranzzo_geo_zone_id) { ?>
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
                            <select name="tranzzo_status" id="input-status" class="form-control">
                                <?php if ($tranzzo_status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>


        </div>
    </div>
</div>
<?php echo $footer; ?>