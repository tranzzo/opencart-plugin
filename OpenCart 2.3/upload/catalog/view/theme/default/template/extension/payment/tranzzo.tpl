<form class="form-horizontal"  method="post" action="<?php echo $action; ?>">
    <?php if(!empty($redirect_url)){ ?>
    <div class="buttons">
        <div class="pull-right">
            <a class="btn btn-primary" href="<?php echo $redirect_url;?>"><?php echo $button_confirm; ?></a>
        </div>
    </div>
    <? } else { ?>
    <div class="buttons">
        <div class="pull-right">
            <input type="submit" value="<?php echo $button_confirm; ?>" id="button-confirm" class="btn btn-primary" />
        </div>
    </div>
    <?php } ?>
</form>
<p class="text-danger"><?php echo $error;?></p>

