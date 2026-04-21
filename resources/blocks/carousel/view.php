<?php

use Qubus\Support\DataType;

$random = new DataType()->string->random(type: 'alpha', length: 6);
$itemCount = intval($block->setting('carousel_item_count'));
$itemCount = $itemCount < 0 ? 1 : $itemCount;
?>

<div id="carousel<?=$random;?>" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php for ($i = 0; $i < $itemCount; $i++) : ?>
        <div class="carousel-item<?= $i === 1 ? ' active' : '';?>" id="carousel-item-<?=$i;?>">
            [block slug="carousel-item" id="carousel-item-<?= $i ?>"]
        </div>
        <?php endfor; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#carousel<?=$random;?>" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carousel<?=$random;?>" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
