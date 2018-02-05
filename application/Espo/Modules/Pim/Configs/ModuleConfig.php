<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Configs;

return [
    'Pim' => [
        'channelType' => [
            'base'     => [
                'name' => 'Base'
            ],
            'amazon'   => [
                'name' => 'Amazon'
            ],
            'afterbuy' => [
                'name' => 'Afterbuy'
            ]
        ],
        'productType' => [
            'simpleProduct'  => [
                'name' => 'Simple Product'
            ],
            'bundleProduct'  => [
                'name' => 'Bundle Product'
            ],
            'packageProduct' => [
                'name' => 'Package Product'
            ]
        ]
    ]
];
