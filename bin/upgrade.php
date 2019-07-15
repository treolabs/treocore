<?php

function remove($path)
{
    if (file_exists($path)) {
        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($path . "/" . $object)) {
                        remove($path . "/" . $object);
                    } else {
                        unlink($path . "/" . $object);
                    }
                }
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}

// prepare root path
$rootPath = dirname(__DIR__);

/**
 * Update composer.json
 */
$composer = [
    'name'              => 'treolabs/skeleton',
    'description'       => 'Skeleton for Treo project',
    'homepage'          => 'https://treolabs.com',
    'license'           => 'GPL-3.0-only',
    'minimum-stability' => 'RC',
    'require'           => [
        'treolabs/treocore' => '^3.20.10'
    ],
    'scripts'           => [
        'post-update-cmd' => 'Treo\\Composer\\Cmd::postUpdate'
    ],
    'repositories'      => [
        [
            'type' => 'composer',
            'url'  => 'https://packagist.treopim.com/packages.json?id=public'
        ]
    ]
];
if (file_exists($rootPath . '/data/composer.json')) {
    $composer = array_merge_recursive($composer, json_decode(file_get_contents($rootPath . '/data/composer.json'), true));
}
if (file_exists($rootPath . '/data/repositories.json')) {
    $composer = array_merge_recursive($composer, json_decode(file_get_contents($rootPath . '/data/repositories.json'), true));
}
file_put_contents($rootPath . '/composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

// delete old
remove($rootPath . '/application');
remove($rootPath . '/bin/treo-module-update.sh');
remove($rootPath . '/bin/treo-self-upgrade.sh');
remove($rootPath . '/client');
remove($rootPath . '/data/.backup');
remove($rootPath . '/data/cache');
remove($rootPath . '/data/migrations');
remove($rootPath . '/data/module-manager-events');
remove($rootPath . '/data/composer.json');
remove($rootPath . '/data/dev-composer.json');
remove($rootPath . '/data/repositories.json');
remove($rootPath . '/data/treo-module-update.log');
remove($rootPath . '/data/treo-self-upgrade.log');
remove($rootPath . '/docs');
remove($rootPath . '/tests');
remove($rootPath . '/console.php');
remove($rootPath . '/composer.lock');
remove($rootPath . '/index.php');
remove($rootPath . '/pre-commit-hook.sh');
remove($rootPath . '/README.md');

echo 'Removed!';
die();


