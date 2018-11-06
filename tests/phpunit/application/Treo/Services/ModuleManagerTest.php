<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
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

declare(strict_types=1);

namespace Treo\Services;

use Slim\Http\Request;
use Treo\Core\Utils\Metadata;
use Treo\PHPUnit\Framework\TestCase;

/**
 * Class ModuleManagerTest
 *
 * @author r.zablodskiy@zinitsolutions.com
 */
class ModuleManagerTest extends TestCase
{
    public function testGetListMethod()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['getComposerService', 'getInstalledModules', 'getUninstalledModules']
        );
        $composer = $this->createMockService(Composer::class, ['getModuleComposerJson', 'getComposerDiff']);

        $service
            ->expects($this->any())
            ->method('getComposerService')
            ->willReturn($composer);
        $service
            ->expects($this->any())
            ->method('getInstalledModules')
            ->willReturn([
                'module1' => [
                    'id' => 'some-id1',
                    'name' => 'module1-name'
                ]
            ]);
        $service
            ->expects($this->any())
            ->method('getUninstalledModules')
            ->willReturn([
                'module2' => [
                    'id' => 'some-id2',
                    'name' => 'module2-name'
                ]
            ]);

        $composer
            ->expects($this->any())
            ->method('getModuleComposerJson')
            ->willReturn([
                'require' => [
                    'module1' => '1.0.0'
                ]
            ]);
        $composer
            ->expects($this->any())
            ->method('getComposerDiff')
            ->willReturn([
                'install' => [
                    [
                        'id' => 'some-id2',
                        'package' => 'some-package'
                    ]
                ]
            ]);

        // test 1
        $expects = [
            'total' => 2,
            'list' => [
                [
                    'id' => 'some-id1',
                    'name' => 'module1-name'
                ],
                [
                    'id' => 'some-id2',
                    'name' => 'module2-name'
                ]
            ]
        ];
        $this->assertEquals($expects, $service->getList());

        $service = $this->createMockService(
            ModuleManager::class,
            ['getComposerService', 'getInstalledModules', 'getUninstalledModules']
        );
        $composer = $this->createMockService(Composer::class, ['getModuleComposerJson', 'getComposerDiff']);

        $service
            ->expects($this->any())
            ->method('getComposerService')
            ->willReturn($composer);
        $service
            ->expects($this->any())
            ->method('getInstalledModules')
            ->willReturn([]);
        $service
            ->expects($this->any())
            ->method('getUninstalledModules')
            ->willReturn([]);

        $composer
            ->expects($this->any())
            ->method('getModuleComposerJson')
            ->willReturn([
                'require' => [
                    'module1' => '1.0.0'
                ]
            ]);
        $composer
            ->expects($this->any())
            ->method('getComposerDiff')
            ->willReturn([
                'install' => [
                    [
                        'id' => 'some-id2',
                        'package' => 'some-package'
                    ]
                ]
            ]);

        // test 2
        $expects = [
            'total' => 0,
            'list' => []
        ];
        $this->assertEquals($expects, $service->getList());
    }

    /**
     * Test getAvailableModulesList method
     */
    public function testGetAvailableModulesListMethod()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['getPackagistPackages']
        );

        $service
            ->expects($this->any())
            ->method('getPackagistPackages')
            ->willReturn([]);

        // test 1
        $expects = [
            'total' => 0,
            'list' => []
        ];
        $this->assertEquals($expects, $service->getAvailableModulesList());

        $service = $this->createMockService(
            ModuleManager::class,
            ['getPackagistPackages', 'getComposerService', 'getMetadata', 'packageTranslate']
        );
        $composer = $this->createMockService(Composer::class, ['getComposerDiff']);
        $metadata = $this->createMockService(Metadata::class, ['getModuleList']);

        $service
            ->expects($this->any())
            ->method('getPackagistPackages')
            ->willReturn([
                [
                    'treoId' => 'some-treoId',
                    'name' => [],
                    'description' => [],
                    'status' => 'some-status',
                    'versions' => []
                ]
            ]);
        $service
            ->expects($this->any())
            ->method('getComposerService')
            ->willReturn($composer);
        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('packageTranslate')
            ->willReturn('Translate');

        $composer
            ->expects($this->any())
            ->method('getComposerDiff')
            ->willReturn(['install' => [
                [
                    'id' => 'some-id',
                    'package' => []
                ]
            ]]);

        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn(['module1', 'module2']);

        // test 2
        $expects = [
            'total' => 1,
            'list' => [
                [
                    'id' => 'some-treoId',
                    'name' => 'Translate',
                    'description' => 'Translate',
                    'status' => 'some-status',
                    'versions' => []
                ]
            ]
        ];
        $this->assertEquals($expects, $service->getAvailableModulesList());

        $service = $this->createMockService(
            ModuleManager::class,
            ['getPackagistPackages', 'getComposerService', 'getMetadata', 'packageTranslate']
        );
        $metadata = $this->createMockService(Metadata::class, ['getModuleList']);
        $composer = $this->createMockService(Composer::class, ['getComposerDiff']);

        $service
            ->expects($this->any())
            ->method('getPackagistPackages')
            ->willReturn([
                [
                    'treoId' => 'some-treoId',
                    'name' => [],
                    'description' => [],
                    'status' => 'some-status',
                    'versions' => []
                ]
            ]);
        $service
            ->expects($this->any())
            ->method('getComposerService')
            ->willReturn($composer);
        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('packageTranslate')
            ->willReturn('Translate');

        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn(['some-treoId']);

        $composer
            ->expects($this->any())
            ->method('getComposerDiff')
            ->willReturn(['install' => []]);

        // test 3
        $expects = [
            'total' => 0,
            'list' => []
        ];
        $this->assertEquals($expects, $service->getAvailableModulesList());
    }

    /**
     * Test is installModule return true
     */
    public function testIsInstallModuleReturnTrue()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['isSystemUpdating', 'getPackagistPackage', 'getMetadata', 'getComposerService']
        );

        $metadata = $this->createMockService(Metadata::class, ['getModule']);
        $composer = $this->createMockService(Composer::class, ['update']);

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(false);
        $service
            ->expects($this->any())
            ->method('getPackagistPackage')
            ->willReturn([
                'packageId' => 'some-id'
            ]);
        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('getComposerService')
            ->willReturn($composer);

        $metadata
            ->expects($this->any())
            ->method('getModule')
            ->willReturn([]);

        $composer
            ->expects($this->any())
            ->method('update')
            ->willReturn(null);

        // test 1
        $this->assertTrue($service->installModule('id', '1.0.0'));
        // test 2
        $this->assertTrue($service->installModule('id'));
    }

    /**
     * Test is installModule return false
     */
    public function testIsInstallModuleReturnFalse()
    {
        $service = $this->createMockService(ModuleManager::class, ['isSystemUpdating']);

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(true);

        // test
        $this->assertFalse($service->installModule('id', '1.0.0'));
    }

    /**
     * Test is installModule throw exception
     *
     * @expectedException Espo\Core\Exceptions\Error
     */
    public function testIsInstallModuleThrowException()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['isSystemUpdating', 'getPackagistPackage', 'translateError']
        );

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(false);

        $service
            ->expects($this->any())
            ->method('translateError')
            ->willReturn('Error');

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn([]);

        // test
        $this->assertTrue($service->installModule('id', '1.0.0'));
    }

    /**
     * Test is updateModule return true
     */
    public function testIsUpdateModuleReturnTrue()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['isSystemUpdating', 'getMetadata', 'getPackagistPackage', 'getComposerService']
        );
        $metadata = $this->createMockService(Metadata::class, ['getModule']);
        $composer = $this->createMockService(Composer::class, ['update']);

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(false);
        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('getPackagistPackage')
            ->willReturn(['packageId' => 'some-id']);

        $metadata
            ->expects($this->any())
            ->method('getModule')
            ->willReturn([
                'name' => 'some-name'
            ]);

        $composer
            ->expects($this->any())
            ->method('update')
            ->willReturn(null);

        // test
        $this->assertTrue($service->updateModule('id', '1.0.0'));
    }

    /**
     * Test is updateModule return false
     */
    public function testIsUpdateModuleReturnFalse()
    {
        $service = $this->createMockService(ModuleManager::class, ['isSystemUpdating']);

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(true);

        // test
        $this->assertFalse($service->updateModule('id', '1.0.0'));
    }

    /**
     * Test is updateModule throw exception
     *
     * @expectedException Espo\Core\Exceptions\Error
     */
    public function testIsUpdateModuleThrowException()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['isSystemUpdating', 'getMetadata', 'getPackagistPackage']
        );
        $metadata = $this->createMockService(Metadata::class, ['getModule']);

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(false);

        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);

        $service
            ->expects($this->any())
            ->method('getPackagistPackage')
            ->willReturn([]);

        // test
        $this->assertTrue($service->updateModule('id', '1.0.0'));
    }

    /**
     * Test is deleteModule return true
     */
    public function testIsDeleteModuleReturnTrue()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['isSystemUpdating', 'isModuleSystem', 'getMetadata', 'getComposerService']
        );
        $metadata = $this->createMockService(Metadata::class, ['getModule']);
        $composer = $this->createMockService(Composer::class, ['delete']);

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(false);
        $service
            ->expects($this->any())
            ->method('isModuleSystem')
            ->willReturn(false);
        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('getComposerService')
            ->willReturn($composer);

        $metadata
            ->expects($this->any())
            ->method('getModule')
            ->willReturn(['name' => 'some-name']);

        $composer
            ->expects($this->any())
            ->method('delete')
            ->willReturn(null);

        // test
        $this->assertTrue($service->deleteModule('id'));
    }

    /**
     * Test is deleteModule return false
     */
    public function testIsDeleteModuleReturnFalse()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['isSystemUpdating']
        );

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(true);

        // test
        $this->assertFalse($service->deleteModule('id'));
    }

    /**
     * Test is deleteModuel throw exception
     *
     * @expectedException Espo\Core\Exceptions\Error
     */
    public function testIsDeleteModuleThrowException()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['isSystemUpdating', 'isModuleSystem']
        );

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(false);

        $service
            ->expects($this->any())
            ->method('isModuleSystem')
            ->willReturn(true);

        // test
        $this->assertTrue($service->deleteModule('id'));
    }

    public function testIsCancelReturnTrue()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['isSystemUpdating', 'getPackagistPackage', 'getComposerService']
        );
        $composer = $this->createMockService(
            Composer::class,
            ['getModuleComposerJson', 'getModuleStableComposerJson', 'setModuleComposerJson']
        );

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(false);
        $service
            ->expects($this->any())
            ->method('getPackagistPackage')
            ->willReturn([
                'packageId' => 'some-id'
            ]);
        $service
            ->expects($this->any())
            ->method('getComposerService')
            ->willReturn($composer);

        $composer
            ->expects($this->any())
            ->method('getModuleComposerJson')
            ->willReturn(['require' => [
                'some-id' => 'some-value'
            ]]);
        $composer
            ->expects($this->any())
            ->method('getModuleStableComposerJson')
            ->willReturn(['require' => [
                'some-id' => 'some-value'
            ]]);
        $composer
            ->expects($this->any())
            ->method('setModuleComposerJson')
            ->willReturn(null);

        // test
        $this->assertTrue($service->cancel('id'));
    }

    /**
     * Test is cancel method return false
     */
    public function testIsCancelReturnFalse()
    {
        $service = $this->createMockService(ModuleManager::class, ['isSystemUpdating']);

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(true);

        // test 1
        $this->assertFalse($service->cancel('id'));

        $service = $this->createMockService(ModuleManager::class, ['isSystemUpdating', 'getPackagistPackage']);

        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(false);

        $service
            ->expects($this->any())
            ->method('getPackagistPackage')
            ->willReturn([
                'packageId' => ''
            ]);

        // test 2
        $this->assertFalse($service->cancel('id'));
    }

    /**
     * Test getLogs method
     */
    public function testGetLogsMethod()
    {
        $service = $this->createMockService(ModuleManager::class, ['getNoteCount']);
        $request = $this->createMockService(Request::class, ['get']);

        $service
            ->expects($this->any())
            ->method('getNoteCount')
            ->willReturn(0);

        $request
            ->expects($this->any())
            ->method('get')
            ->willReturn(5);

        // test 1
        $expects = [
            'total' => 0,
            'list' => []
        ];
        $this->assertEquals($expects, $service->getLogs($request));

        $service = $this->createMockService(ModuleManager::class, ['getNoteCount', 'getNoteData']);

        $service
            ->expects($this->any())
            ->method('getNoteCount')
            ->willReturn(1);
        $service
            ->expects($this->any())
            ->method('getNoteData')
            ->willReturn([
                [
                    'id' => 'some-id',
                    'deleted' => 0,
                    'data' => 'some-data'
                ]
            ]);

        // test 2
        $expects = [
            'total' => 1,
            'list' => [
                [
                    'id' => 'some-id',
                    'deleted' => 0,
                    'data' => 'some-data'
                ]
            ]
        ];
        $this->assertEquals($expects, $service->getLogs($request));
    }

    /**
     * Test is updateLoadOrder return true
     */
    public function testIsUpdateLoadOrderReturnTrue()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['getMetadata', 'createModuleLoadOrder', 'putContentsJson']
        );
        $metadata = $this->createMockService(Metadata::class, ['init', 'getModuleList']);

        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('createModuleLoadOrder')
            ->willReturn(10);
        $service
            ->expects($this->any())
            ->method('putContentsJson')
            ->willReturn(true);

        $metadata
            ->expects($this->any())
            ->method('init')
            ->willReturn(null);
        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn(['module1', 'module2']);

        // test
        $this->assertTrue($service->updateLoadOrder());
    }

    /**
     * Test is updateLoadOrder method return false
     */
    public function testIsUpdateLoadOrderReturnFalse()
    {
        $service = $this->createMockService(
            ModuleManager::class,
            ['getMetadata', 'createModuleLoadOrder', 'putContentsJson']
        );
        $metadata = $this->createMockService(Metadata::class, ['init', 'getModuleList']);

        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('createModuleLoadOrder')
            ->willReturn(10);
        $service
            ->expects($this->any())
            ->method('putContentsJson')
            ->willReturn(false);

        $metadata
            ->expects($this->any())
            ->method('init')
            ->willReturn(null);
        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn(['module1', 'module2']);

        // test
        $this->assertFalse($service->updateLoadOrder());
    }

    /**
     * Test is clearModuleData method exists
     */
    public function testIsClearModuleDataExists()
    {
        $service = $this->createMockService(ModuleManager::class);

        // test
        $this->assertTrue(method_exists($service, 'clearModuleData'));
    }
}
