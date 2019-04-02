<?php
/**
 * This file is part of EspoCRM and/or TreoCORE.
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
 * and "TreoPIM" word.
 */

namespace Treo\Core\Utils\File;

use Espo\Core\Utils\File\Manager;
use PHPUnit\Framework\TestCase;
use Treo\Core\Utils\Metadata;

/**
 * Class UnifierTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class UnifierTest extends TestCase
{
    /**
     * Test unify method
     */
    public function testUnify()
    {
        // test 1
        $mock = $this->createPartialMock(Unifier::class, ['unifySingle', 'merge']);
        $mock
            ->expects($this->any())
            ->method('unifySingle')
            ->withConsecutive(
                ['application/Espo/', 'some-name', false],
                ['application/Treo/', 'some-name', false]
            )->willReturnOnConsecutiveCalls(
                ['content-from-espo'],
                ['content-from-treo']
            );
        $mock
            ->expects($this->any())
            ->method('merge')
            ->willReturn([
                'content-from-espo', 'content-from-treo'
            ]);

        $testData = [
            'corePath' => 'application/Espo/'
        ];

        $expects = ['content-from-espo', 'content-from-treo'];

        $this->assertEquals($expects, $mock->unify('some-name', $testData, false));

        // test 2
        $mock = $this->createPartialMock(
            Unifier::class,
            ['unifySingle', 'merge', 'getMetadata']
        );
        $metadata = $this->createPartialMock(Metadata::class, ['getModuleList']);

        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn(['Module']);

        $mock
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $mock
            ->expects($this->any())
            ->method('unifySingle')
            ->withConsecutive(
                ['application/Espo/', 'some-name', false],
                ['application/Treo/', 'some-name', false],
                ['application/Espo/Modules/Module/', 'some-name', false, 'Module']
            )->willReturnOnConsecutiveCalls(
                ['content-from-espo'],
                ['content-from-treo'],
                ['content-from-module']
            );
        $mock
            ->expects($this->any())
            ->method('merge')
            ->withConsecutive(
                [['content-from-espo'], ['content-from-treo']],
                [['content-from-espo', 'content-from-treo'], ['content-from-module']]
            )
            ->willReturn(
                ['content-from-espo', 'content-from-treo'],
                ['content-from-espo', 'content-from-treo', 'content-from-module']
            );

        $testData = [
            'corePath' => 'application/Espo/',
            'treoPath' => 'application/Treo/',
            'modulePath' => 'application/Espo/Modules/{*}/'
        ];

        $expects = [
            'content-from-espo',
            'content-from-treo',
            'content-from-module'
        ];

        $this->assertEquals($expects, $mock->unify('some-name', $testData, false));

        // test 3
        $mock = $this->createPartialMock(
            Unifier::class,
            ['unifySingle', 'merge', 'getFileManager', 'getMetadata']
        );
        $fileManager = $this->createPartialMock(Manager::class, ['getFileList']);

        $fileManager
            ->expects($this->any())
            ->method('getFileList')
            ->willReturn(['Module']);

        $mock
            ->expects($this->any())
            ->method('getFileManager')
            ->willReturn($fileManager);
        $mock
            ->expects($this->any())
            ->method('unifySingle')
            ->withConsecutive(
                ['application/Espo/', 'some-name', false],
                ['application/Treo/', 'some-name', false],
                ['application/Espo/Modules/Module/', 'some-name', false, 'Module'],
                ['Custom/']
            )->willReturnOnConsecutiveCalls(
                ['content-from-espo'],
                ['content-from-treo'],
                ['content-from-module'],
                ['content-from-custom']
            );
        $mock
            ->expects($this->any())
            ->method('merge')
            ->withConsecutive(
                [['content-from-espo'], ['content-from-treo']],
                [['content-from-espo', 'content-from-treo'], ['content-from-module']],
                [['content-from-espo', 'content-from-treo', 'content-from-module'], ['content-from-custom']]
            )
            ->willReturn(
                ['content-from-espo', 'content-from-treo'],
                ['content-from-espo', 'content-from-treo', 'content-from-module'],
                ['content-from-espo', 'content-from-treo', 'content-from-module', 'content-from-custom']
            );

        $testData = [
            'corePath' => 'application/Espo/',
            'treoPath' => 'application/Treo/',
            'modulePath' => 'application/Espo/Modules/{*}/',
            'customPath' => 'Custom/'
        ];

        $expects = [
            'content-from-espo',
            'content-from-treo',
            'content-from-module',
            'content-from-custom'
        ];

        $this->assertEquals($expects, $mock->unify('some-name', $testData, false));
    }
}
