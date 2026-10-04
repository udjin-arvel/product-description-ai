<?php

declare(strict_types=1);

require \dirname(__DIR__) . '/vendor/autoload.php';

if (!\class_exists('Yii', false) && \is_file(\dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php')) {
    \defined('YII_DEBUG') or \define('YII_DEBUG', true);
    \defined('YII_ENV') or \define('YII_ENV', 'test');
    require \dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
}
