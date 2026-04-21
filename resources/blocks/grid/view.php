<?php
$columnsLg = array_filter(explode('-', $block->setting('columns-lg')));
$columnsMd = array_filter(explode('-', $block->setting('columns-md')));
$columnsSm = array_filter(explode('-', $block->setting('columns-sm')));
$columnsXs = array_filter(explode('-', $block->setting('columns-xs')));
$columnsMd = empty($columnsMd) ? $columnsLg : $columnsMd;
$columnsSm = empty($columnsSm) ? $columnsMd : $columnsSm;
$columnsXs = empty($columnsXs) ? $columnsSm : $columnsXs;

$orderLg = $block->setting('order-lg');
$orderMd = $block->setting('order-md');
$orderSm = $block->setting('order-sm');
$orderXs = $block->setting('order-xs');
$orderLg = empty($orderLg) ? '' : $orderLg;
$orderMd = empty($orderMd) ? $orderLg : $orderMd;
$orderSm = empty($orderSm) ? $orderMd : $orderSm;
$orderXs = empty($orderXs) ? $orderSm : $orderXs;
$orderLg = str_replace('--', '-lg-', $orderLg);
$orderMd = str_replace('--', '-md-', $orderMd);
$orderSm = str_replace('--', '-sm-', $orderSm);
$orderXs= str_replace('--', '-', $orderXs);
?>

<div class="row <?= $block->setting('gutter') ?>  <?= $orderLg . ' ' . $orderMd . ' ' . $orderSm . ' ' . $orderXs ?>">
    <?php
    $columnLg = 12;
    $columnMd = 12;
    $columnSm = 12;
    $columnXs = 12;
    $columnCount = intval($block->setting('column_count'));
    $columnCount = $columnCount <= 0 ? sizeof($columnsLg) : $columnCount;
    for ($i = 0; $i < $columnCount; $i++) :
        $columnLg = $columnsLg[$i] ?? $columnLg;
        $columnMd = $columnsMd[$i] ?? $columnMd;
        $columnSm = $columnsSm[$i] ?? $columnSm;
        $columnXs = $columnsXs[$i] ?? $columnXs;
        ?>
        <div class="col-lg-<?= $columnLg ?> col-md-<?= $columnMd ?> col-sm-<?= $columnSm ?> col-<?= $columnXs ?>">
            [block slug="blocks-container" id="col<?= $i ?>"]
        </div>
        <?php
    endfor;
    ?>
</div>
