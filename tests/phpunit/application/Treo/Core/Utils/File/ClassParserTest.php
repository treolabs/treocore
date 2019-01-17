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

namespace Treo\Core\Utils\File;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\File\Manager;
use PHPUnit\Framework\TestCase;
use Treo\Core\Utils\Config;
use Treo\Core\Utils\Metadata;

/**
 * Class ClassParserTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class ClassParserTest extends TestCase
{
    /**
     * Test getData method
     */
    public function testGetDataMethod()
    {
        $service = $this->createPartialMock(ClassParser::class, [
            'getClassNameHash', 'fileExists'
        ]);

        $service
            ->expects($this->any())
            ->method('getClassNameHash')
            ->withConsecutive(
                ['application/Espo/'],
                ['application/Treo/']
            )->willReturnOnConsecutiveCalls(['core/'], ['treo/']);

        // test 1
        $expected = [
            'core/', 'treo/'
        ];
        $this->assertEquals($expected, $service->getData(['corePath' => 'application/Espo/']));

        // test 2
        $service = $this->createPartialMock(ClassParser::class, [
           'getClassNameHash', 'getMetadata', 'fileExists'
        ]);
        $metadata = $this->createPartialMock(Metadata::class, ['getModuleList']);
        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn(['Module']);

        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('getClassNameHash')
            ->withConsecutive(
                ['application/Espo/'],
                ['application/Treo/'],
                ['application/Espo/Modules/Module/']
            )->willReturnOnConsecutiveCalls(['core/'], ['treo/'], ['module/']);

        $expected = [
            'core/', 'treo/', 'module/'
        ];
        $this->assertEquals($expected, $service->getData([
            'corePath' => 'application/Espo/',
            'modulePath' => 'application/Espo/Modules/{*}/'
        ]));

        // test 3
        $service = $this->createPartialMock(ClassParser::class, [
              'getClassNameHash', 'getMetadata', 'fileExists'
        ]);
        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn(['Module']);

        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('getClassNameHash')
            ->withConsecutive(
                ['application/Espo/'],
                ['application/Treo/'],
                ['application/Espo/Modules/Module/'],
                ['Custom/']
            )->willReturnOnConsecutiveCalls(['core/'], ['treo/'], ['module/'], ['custom/']);

        $expected = [
            'core/', 'treo/', 'module/', 'custom/'
        ];
        $this->assertEquals($expected, $service->getData([
            'corePath' => 'application/Espo/',
            'modulePath' => 'application/Espo/Modules/{*}/',
            'customPath' => 'Custom/'
        ]));

        // test 4
        $service = $this->createPartialMock(ClassParser::class, [
            'getClassNameHash', 'getMetadata', 'getConfig', 'getFileManager', 'fileExists'
        ]);
        $fileManager = $this->createPartialMock(Manager::class, ['putPhpContents']);
        $fileManager
            ->expects($this->any())
            ->method('putPhpContents')
            ->willReturn(false);

        $config = $this->createPartialMock(Config::class, ['get']);
        $config
            ->expects($this->any())
            ->method('get')
            ->willReturn(true);

        $service
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $service
            ->expects($this->any())
            ->method('getFileManager')
            ->willReturn($fileManager);
        $service
            ->expects($this->any())
            ->method('getClassNameHash')
            ->withConsecutive(
                ['application/Espo/'],
                ['application/Treo/']
            )->willReturnOnConsecutiveCalls(['core/'], ['treo/']);

        try {
            $service->getData([
                'corePath' => 'application/Espo/'
            ], 'cacheFile');
        } catch (Error $e) {
            $this->assertInstanceOf(Error::class, $e);
        }

        // test 5
        $service = $this->createPartialMock(ClassParser::class, [
            'fileExists', 'getConfig', 'getFileManager'
        ]);

        $fileManager = $this->createPartialMock(Manager::class, ['getPhpContents']);
        $fileManager
            ->expects($this->any())
            ->method('getPhpContents')
            ->willReturn([
                'core/'
            ]);

        $config = $this->createPartialMock(Config::class, ['get']);
        $config
            ->expects($this->any())
            ->method('get')
            ->willReturn(true);

        $service
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $service
            ->expects($this->any())
            ->method('getFileManager')
            ->willReturn($fileManager);
        $service
            ->expects($this->any())
            ->method('fileExists')
            ->willReturn(true);

        $this->assertEquals(['core/'], $service->getData([
            'corePath' => 'application/Espo/'
        ], 'cacheFile'));
    }
}
