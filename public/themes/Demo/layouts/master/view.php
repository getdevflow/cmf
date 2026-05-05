<?php

use App\Application\Devflow;

use function App\Shared\Helpers\cms_footer;
use function App\Shared\Helpers\cms_head;

?>
<!doctype html>
<html lang="<?=Devflow::$PHP->configContainer->string(key: 'app.language');?>">
<head>
    <!-- Required meta tags -->
    <meta charset="<?=Devflow::$PHP->configContainer->string(key: 'app.charset');?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?=$page->getTranslation('meta_description');?>">
    <title><?=$page->getTranslation('meta_title');?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= phpb_theme_asset('css/style.css') ?>" />
    <?php cms_head(); ?>
</head>
<body>

<?= $body ?>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Run PHPageBuilder script.js files -->
<script type="text/javascript">
    document.querySelectorAll("script").forEach(function(scriptTag) {
        scriptTag.dispatchEvent(new Event('run-script'));
    });
</script>
<!-- Optional JavaScript -->
<script src="<?=phpb_theme_asset(path: 'js/script.js');?>"></script>
<?php cms_footer(); ?>
</body>
</html>
