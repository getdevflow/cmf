<?php

use Qubus\Support\DataType;

$random = new DataType()->string->random(type: 'alpha', length: 8);
//phpcs:disable
?>
<div class="form-floating mb-3">
    <input
        type="<?=$block->setting('input-field-type');?>"
        name="<?=$block->setting('input-field-name');?>"
        class="form-control"
        id="<?=$block->setting('input-id') ?: $random;?>"<?=$block->setting('input-required') ? ' required' : '';?>
        <?=$block->setting('input-placeholder') ? 'placeholder="'.$block->setting('input-placeholder').'"' : '';?>
    >
    <label for="<?=$block->setting('input-id') ?: $random;?>" class="form-label">
        <?=$block->setting('input-label-text');?><?=$block->setting('input-required') ? '<span class="form-label-required">*</span>' : '';?>
    </label>
</div>
