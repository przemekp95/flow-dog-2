<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

$appDebugValue = $_SERVER['APP_DEBUG'] ?? '1';
$appDebug = \is_string($appDebugValue) ? $appDebugValue : '1';
$_SERVER['APP_DEBUG'] = $appDebug;
$_ENV['APP_DEBUG'] = $appDebug;
putenv('APP_DEBUG='.$appDebug);

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernelClassValue = $_SERVER['KERNEL_CLASS'] ?? App\Kernel::class;
$kernelClass = \is_string($kernelClassValue) ? $kernelClassValue : App\Kernel::class;
$_SERVER['KERNEL_CLASS'] = $kernelClass;
$_ENV['KERNEL_CLASS'] = $kernelClass;
putenv('KERNEL_CLASS='.$kernelClass);

if ('1' === $appDebug) {
    umask(0000);
}
