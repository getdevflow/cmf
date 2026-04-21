<?php

use Qubus\Support\DataType;

$random = new DataType()->string->random(type: 'alpha', length: 6);
$itemCount = intval($block->setting('accordion_item_count'));
$itemCount = $itemCount < 0 ? 1 : $itemCount;
//phpcs:disable
?>

<div class="accordion" id="accordion<?=$random;?>">
    <?php for ($i = 0; $i < $itemCount; $i++) : ?>
    <div class="accordion-item">
        <h2 class="accordion-header" id="heading<?=$i;?>">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?=$i;?>" aria-expanded="true" aria-controls="collapse<?=$i;?>">
                [block slug="accordion-header" id="heading-<?= $i ?>"]
            </button>
        </h2>
        <div id="collapse<?=$i;?>" class="accordion-collapse collapse" aria-labelledby="heading<?=$i;?>" data-bs-parent="#accordion<?=$random;?>">
            <div class="accordion-body">
                [block slug="accordion-body" id="body-<?= $i ?>"]
            </div>
        </div>
    </div>
    <?php endfor; ?>
</div>
