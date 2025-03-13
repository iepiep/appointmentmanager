<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8fff24b0a344f073d218938a8b1f99af
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PrestaShop\\Module\\AppointmentManager\\' => 37,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PrestaShop\\Module\\AppointmentManager\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'PrestaShop\\Module\\AppointmentManager\\Controller\\Admin\\AppointmentManagerAppointmentListController' => __DIR__ . '/../..' . '/src/Controller/Admin/AppointmentManagerAppointmentListController.php',
        'PrestaShop\\Module\\AppointmentManager\\Controller\\Admin\\AppointmentManagerItineraryController' => __DIR__ . '/../..' . '/src/Controller/Admin/AppointmentManagerItineraryController.php',
        'PrestaShop\\Module\\AppointmentManager\\Controller\\AppointmentManagerConfigurationController' => __DIR__ . '/../..' . '/src/Controller/AppointmentManagerConfigurationController.php',
        'PrestaShop\\Module\\AppointmentManager\\Form\\AppointmentManagerConfigFormType' => __DIR__ . '/../..' . '/src/Form/AppointmentManagerConfigFormType.php',
        'PrestaShop\\Module\\AppointmentManager\\Form\\AppointmentManagerDataConfig' => __DIR__ . '/../..' . '/src/Form/AppointmentManagerDataConfig.php',
        'PrestaShop\\Module\\AppointmentManager\\Form\\AppointmentManagerDataProvider' => __DIR__ . '/../..' . '/src/Form/AppointmentManagerDataProvider.php',
        'PrestaShop\\Module\\AppointmentManager\\Service\\ItineraryService' => __DIR__ . '/../..' . '/src/Service/ItineraryService.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8fff24b0a344f073d218938a8b1f99af::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8fff24b0a344f073d218938a8b1f99af::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit8fff24b0a344f073d218938a8b1f99af::$classMap;

        }, null, ClassLoader::class);
    }
}
