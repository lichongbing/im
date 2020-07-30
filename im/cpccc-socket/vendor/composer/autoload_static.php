<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4f7eccae2b0402c0b124b0e02467c1dd
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/workerman',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4f7eccae2b0402c0b124b0e02467c1dd::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4f7eccae2b0402c0b124b0e02467c1dd::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}