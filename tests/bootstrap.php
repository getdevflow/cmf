<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

define('CMS_TEST_ROOT', dirname(__DIR__));
$testRoot = sys_get_temp_dir() . '/devflow-cms-tests-' . bin2hex(random_bytes(8));
foreach (['config', 'bootstrap', 'storage/framework/sessions', 'storage/scheduler-locks'] as $directory) {
    mkdir($testRoot . '/' . $directory, 0700, true);
}
file_put_contents($testRoot . '/bootstrap/app.php', '<?php return Codefy\Framework\Application::$APP;');
file_put_contents($testRoot . '/config/app.php', '<?php return ["providers" => [], "base_middlewares" => []];');
$previousDirectory = getcwd();
chdir($testRoot);
$app = new Codefy\Framework\Application(['basePath' => $testRoot]);
$app->share($app);
Codefy\Framework\Helpers\app(); // Bind helpers to the isolated container before restoring the working directory.
chdir($previousDirectory);

$config = $app->configContainer;
foreach (['app', 'auth', 'csrf', 'cookies', 'cors', 'throttle', 'queue'] as $name) {
    $config->setConfigKey($name, require CMS_TEST_ROOT . '/config/' . $name . '.php');
}
$config->setConfigKey('app', ['crypto_key' => Defuse\Crypto\Key::createNewRandomKey()->saveToAsciiSafeString()]);
$config->setConfigKey('routes', []);
$config->setConfigKey('session', ['name' => 'CMS_TEST_SESSION', 'save_path' => $testRoot . '/storage/framework/sessions']);
$config->setConfigKey('vihzhuo', [
    'enable' => true,
    'general' => ['assets_url' => '/phpb-assets', 'uploads_url' => '/uploads'],
    'website_manager' => ['use_website_manager' => true, 'url' => '/admin/manager/'],
    'router' => ['use_router' => true],
]);
$app->share(new Qubus\Http\Request('https://cms.test/'));

register_shutdown_function(static function () use ($testRoot): void {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($testRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($testRoot);
});
