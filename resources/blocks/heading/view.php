
<h<?=$block->setting('heading-type') ?? '1';?> class="<?=$block->setting('heading-display');?>">
    <?=$block->setting('primary-text');?>

    <?php if (!empty($block->setting('secondary-text'))) : ?>
    <small class="text-muted"><?=$block->setting('secondary-text');?></small>
    <?php endif; ?>
</h<?=$block->setting('heading-type') ?? '1';?>>
