<?php

use Qubus\Support\DataType;

$random = new DataType()->string->random(type: 'alpha', length: 8);
//phpcs:disable
?>

<div class="form-floating mb-3">
    <textarea
        class="form-control"
        style="height: 100px"
        id="<?=$random;?>"<?=$block->setting('textarea-required') ? ' required' : '';?>
        <?=$block->setting('textarea-placeholder') ? 'placeholder="'.$block->setting('textarea-placeholder').'"' : '';?>
    ></textarea>
    <label for="<?=$random;?>" class="form-label">
        <?=$block->setting('textarea-label');?><?=$block->setting('textarea-required') ? '<span class="form-label-required">*</span>' : '';?>
    </label>
</div>
