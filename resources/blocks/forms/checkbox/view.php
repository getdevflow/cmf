<?php

use Qubus\Support\DataType;

$random = new DataType()->string->random(type: 'alpha', length: 8);
//phpcs:disable
?>

<div class="form-check">
    <input class="form-check-input" type="checkbox" value="<?=$block->setting('checkbox-value');?>" id="<?=$random;?>"<?=$block->setting('input-required') ? ' required' : '';?>>
    <label class="form-check-label" for="<?=$random;?>">
        <?=$block->setting('checkbox-label');?><?=$block->setting('checkbox-required') ? '<span class="form-label-required">*</span>' : '';?>
    </label>
</div>
