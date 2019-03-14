<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

namespace Treo\Layouts;

use Espo\Entities\User;
use Treo\PHPUnit\Framework\TestCase;

/**
 * Class PreferencesTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class PreferencesTest extends TestCase
{
    /**
     * Test layoutDetail method
     */
    public function testLayoutDetailMethod()
    {
        $service = $this->createMockService(Preferences::class);

        // test 1
        $testData = [
            [
                'name' => 'Test',
                'rows' => []
            ]
        ];

        $expected = [
            [
                'name' => 'Test',
                'rows' => [
                    [
                        [
                            'name' => 'dateFormat'
                        ],
                        [
                            'name' => 'timeZone'
                        ],
                    ],
                    [
                        [
                            'name' => 'timeFormat'
                        ],
                        [
                            'name' => 'weekStart'
                        ],
                    ],
                    [
                        [
                            'name' => 'decimalMark'
                        ],
                        [
                            'name' => 'thousandSeparator'
                        ],
                    ],
                    [
                        [
                            'name' => 'language'
                        ],
                        [
                        ],
                    ],
                ]
            ]
        ];

        $this->assertEquals($expected, $service->layoutDetail($testData));

        // test 2
        $testData = [
            [
                'name' => 'Test',
                'rows' => []
            ],
            [
                'name' => 'notifications',
                'rows' => [
                    [
                        [
                            'name' => 'test'
                        ],
                        false
                    ]
                ]
            ]
        ];

        $expected = [
            [
                'name' => 'Test',
                'rows' => [
                    [
                        [
                            'name' => 'dateFormat'
                        ],
                        [
                            'name' => 'timeZone'
                        ],
                    ],
                    [
                        [
                            'name' => 'timeFormat'
                        ],
                        [
                            'name' => 'weekStart'
                        ],
                    ],
                    [
                        [
                            'name' => 'decimalMark'
                        ],
                        [
                            'name' => 'thousandSeparator'
                        ],
                    ],
                    [
                        [
                            'name' => 'language'
                        ],
                        [
                        ],
                    ],
                ]
            ],
            [
                'name' => 'notifications',
                'rows' => [
                    [
                        [
                            'name' => 'test'
                        ],
                        false
                    ],
                    [
                        [
                            'name' => 'receiveNewSystemVersionNotifications'
                        ],
                        [
                            'name' => 'receiveNewModuleVersionNotifications'
                        ],
                    ],
                    [
                        [
                            'name' => 'receiveInstallDeleteModuleNotifications'
                        ],
                        false
                    ]
                ]
            ]
        ];

        $service = $this->createPartialMock(Preferences::class, ['getUser']);

        $user = $this->createPartialMock(User::class, ['isAdmin']);
        $user
            ->expects($this->any())
            ->method('isAdmin')
            ->willReturn(true);

        $service
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals($expected, $service->layoutDetail($testData));

        // test 3
        $expected = [
            [
                'name' => 'Test',
                'rows' => [
                    [
                        [
                            'name' => 'dateFormat'
                        ],
                        [
                            'name' => 'timeZone'
                        ],
                    ],
                    [
                        [
                            'name' => 'timeFormat'
                        ],
                        [
                            'name' => 'weekStart'
                        ],
                    ],
                    [
                        [
                            'name' => 'decimalMark'
                        ],
                        [
                            'name' => 'thousandSeparator'
                        ],
                    ],
                    [
                        [
                            'name' => 'language'
                        ],
                        [
                        ],
                    ],
                ]
            ]
        ];
        $service = $this->createPartialMock(Preferences::class, ['getUser']);

        $user = $this->createPartialMock(User::class, ['isAdmin']);
        $user
            ->expects($this->any())
            ->method('isAdmin')
            ->willReturn(false);

        $service
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals($expected, $service->layoutDetail($testData));

        // test 4
        $testData = [];

        $this->assertEquals($testData, $service->layoutDetail($testData));
    }

    /**
     * Test layoutDetailPortal method
     */
    public function testLayoutDetailPortalMethod()
    {
        $service = $this->createMockService(Preferences::class);

        // test 1
        $testData = [
            [
                'name' => 'Test',
                'rows' => []
            ]
        ];

        $expected = [
            [
                'name' => 'Test',
                'rows' => [
                    [
                        [
                            'name' => 'dateFormat'
                        ],
                        [
                            'name' => 'timeZone'
                        ],
                    ],
                    [
                        [
                            'name' => 'timeFormat'
                        ],
                        [
                            'name' => 'weekStart'
                        ],
                    ],
                    [
                        [
                            'name' => 'decimalMark'
                        ],
                        [
                            'name' => 'thousandSeparator'
                        ],
                    ],
                    [
                        [
                            'name' => 'language'
                        ],
                        [
                        ],
                    ],
                ]
            ]
        ];

        $this->assertEquals($expected, $service->layoutDetailPortal($testData));

        // test 2
        $testData = [];
        $this->assertEquals($testData, $service->layoutDetailPortal($testData));
    }
}
