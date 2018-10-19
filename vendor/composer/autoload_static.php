<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3f318df151d6e5c38591d3f2c13351ca
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'Linkhub\\Tests\\' => 14,
            'Linkhub\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Linkhub\\Tests\\' => 
        array (
            0 => __DIR__ . '/../..' . '/tests',
        ),
        'Linkhub\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3f318df151d6e5c38591d3f2c13351ca::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3f318df151d6e5c38591d3f2c13351ca::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}