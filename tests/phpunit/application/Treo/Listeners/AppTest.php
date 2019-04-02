<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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
 * and "TreoCore" word.
 */

namespace Treo\Listeners;

use Espo\Entities\Preferences;
use Treo\Core\Slim\Http\Request;
use Treo\PHPUnit\Framework\TestCase;

/**
 * Class AppTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class AppTest extends TestCase
{
    /**
     * Test afterActionUserMethod
     */
    public function testAfterActionUserMethod()
    {
        $preferences = $this->createMockService(Preferences::class, ['set']);
        $preferences
            ->expects($this->any())
            ->method('set')
            ->willReturn(null);

        $service = $this->createMockService(App::class, ['saveEntity', 'getPreferences']);
        $service
            ->expects($this->any())
            ->method('saveEntity')
            ->willReturn(null);
        $service
            ->expects($this->any())
            ->method('getPreferences')
            ->willReturn($preferences);

        $request = $this->createMockService(Request::class, ['get']);
        $request
            ->expects($this->any())
            ->method('get')
            ->willReturn('en_US');

        // test 1
        $testData = [
            'request' => $request,
            'result' => [
                'language' => 'de_DE'
            ]
        ];

        $this->assertEquals($testData, $service->afterActionUser($testData));

        // test 2
        $testData = [
            'request' => $request,
            'result' => [
                'language' => 'de_DE',
                'user' => 'user'
            ]
        ];

        $expected = [
            'request' => $request,
            'result' => [
                'language' => 'en_US',
                'user' => 'user'
            ]
        ];

        $this->assertEquals($expected, $service->afterActionUser($testData));
    }
}
