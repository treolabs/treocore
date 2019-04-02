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

declare(strict_types=1);

namespace Treo\Core\Utils;

use Espo\Core\Utils\Metadata\Helper;
use PHPUnit\Framework\TestCase;

/**
 * Class MetadataTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class MetadataTest extends TestCase
{
    /**
     * Test os getModuleList method exists
     */
    public function testIsGetModuleListExists()
    {
        $mock = $this->createPartialMock(Metadata::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'getModuleList'));
    }

    /**
     * Test is getModuleConfigData method exists
     */
    public function testIsGetModuleConfigDataExists()
    {
        $mock = $this->createPartialMock(Metadata::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'getModuleConfigData'));
    }

    /**
     * Test getModule method
     */
    public function testGetModuleMethod()
    {
        $mock = $this->createPartialMock(Metadata::class, ['loadComposerLock']);

        // test 1
        $this->assertEquals([], $mock->getModule('moduleId'));

        // test 2
        $reflection = new \ReflectionClass($mock);
        $composerLockData = $reflection->getProperty('composerLockData');
        $composerLockData->setAccessible(true);
        $composerLockData->setValue($mock, [
            'packages' => [
                ['extra' => ['treoId' => 'moduleId'], 'version' => 'v1.0.0'],
                ['extra' => ['treoId' => 'someId'], 'version' => 'v1.0.1']
            ]
        ]);

        $expected =  ['extra' => ['treoId' => 'moduleId'], 'version' => '1.0.0'];
        $this->assertEquals($expected, $mock->getModule('moduleId'));
    }

    /**
     * Test is init method exists
     */
    public function testIsInitExists()
    {
        $mock = $this->createPartialMock(Metadata::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'init'));
    }

    /**
     * Test is getAllForFrontend method exists
     */
    public function testIsGetAllForFrontendExists()
    {
        $mock = $this->createPartialMock(Metadata::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'getAllForFrontend'));
    }

    /**
     * Test is dropCache method exists
     */
    public function testIsDropCacheExists()
    {
        $mock = $this->createPartialMock(Metadata::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'dropCache'));
    }

    /**
     * Test getFieldList method
     */
    public function testGetFieldListMethod()
    {
        $mock = $this->createPartialMock(Metadata::class, ['get']);

        $mock
            ->expects($this->any())
            ->method('get')
            ->willReturn([]);

        // test 1
        $this->assertEquals([], $mock->getFieldList('scope', 'field'));

        // test 2
        $mock = $this->createPartialMock(Metadata::class, ['get', 'getMetadataHelper']);
        $helper = $this->createPartialMock(Helper::class, ['getAdditionalFieldList']);

        $helper
            ->expects($this->any())
            ->method('getAdditionalFieldList')
            ->willReturn([]);

        $mock
            ->expects($this->any())
            ->method('get')
            ->willReturn(['data']);
        $mock
            ->expects($this->any())
            ->method('getMetadataHelper')
            ->willReturn($helper);

        $expected = ['field' => ['data']];
        $this->assertEquals($expected, $mock->getFieldList('scope', 'field'));

        // test 3
        $mock = $this->createPartialMock(Metadata::class, ['get', 'getMetadataHelper']);
        $helper = $this->createPartialMock(Helper::class, ['getAdditionalFieldList']);

        $helper
            ->expects($this->any())
            ->method('getAdditionalFieldList')
            ->willReturn(['additionalField' => ['additionalData']]);

        $mock
            ->expects($this->any())
            ->method('get')
            ->willReturn(['data']);
        $mock
            ->expects($this->any())
            ->method('getMetadataHelper')
            ->willReturn($helper);

        $expected = ['field' => ['data'], 'additionalField' => ['additionalData']];
        $this->assertEquals($expected, $mock->getFieldList('scope', 'field'));
    }

    /**
     * Test is getEntityPath method exists
     */
    public function testIsGetEntityPathExists()
    {
        $mock = $this->createPartialMock(Metadata::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'getEntityPath'));
    }

    /**
     * Test is getRepositoryPath method exists
     */
    public function testIsGetRepositoryPathExists()
    {
        $mock = $this->createPartialMock(Metadata::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'getRepositoryPath'));
    }
}
