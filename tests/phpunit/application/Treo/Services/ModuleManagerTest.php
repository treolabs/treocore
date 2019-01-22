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

use Espo\Core\Exceptions\Error;
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
        $methods = [
            'getComposerService',
            'getMetadata',
            'getPackagistPackage',
            'packageTranslate',
            'getModuleRequireds',
            'getComposerDiff'
        ];

        $service = $this->createPartialMock(ModuleManager::class, $methods);
        $composer = $this->createPartialMock(Composer::class, ['getModuleComposerJson']);
        $metadata = $this->createPartialMock(Metadata::class, ['getModuleList', 'getModule']);

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
            ->method('getPackagistPackage')
            ->willReturn(
                [
                    'packageId'   => 'module1',
                    'name'        => [],
                    'description' => []
                ]
            );
        $service
            ->expects($this->any())
            ->method('packageTranslate')
            ->willReturn('Translate');
        $service
            ->expects($this->any())
            ->method('getComposerDiff')
            ->willReturn(
                [
                    'install' => [
                        [
                            'id'      => 'some-id2',
                            'package' => 'some-package'
                        ]
                    ]
                ]
            );

        $composer
            ->expects($this->any())
            ->method('getModuleComposerJson')
            ->willReturn(
                [
                    'require' => [
                        'module1' => '1.0.0'
                    ]
                ]
            );


        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn([]);

        // test 1
        $expects = [
            'total' => 1,
            'list'  => [
                [
                    'id'             => 'some-id2',
                    'name'           => 'Translate',
                    'description'    => 'Translate',
                    'settingVersion' => '1.0.0',
                    'currentVersion' => '',
                    'required'       => [],
                    'isSystem'       => false,
                    'isComposer'     => true,
                    'status'         => 'install'
                ]
            ]
        ];
        $this->assertEquals($expects, $service->getList());

        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn(['module1', 'module2']);
        $metadata
            ->expects($this->any())
            ->method('getModule')
            ->willReturn([]);

        // test 2
        $this->assertEquals($expects, $service->getList());

        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn(['module1']);
        $metadata
            ->expects($this->any())
            ->method('getModule')
            ->willReturn(
                [
                    'extra'   => [
                        'name'        => 'module1',
                        'description' => 'description'
                    ],
                    'version' => '1.1.0'
                ]
            );

        $service
            ->expects($this->any())
            ->method('getPackagistPackage')
            ->willReturn([]);

        // test 3
        $expects = [
            'total' => 1,
            'list'  => [
                [
                    'id'             => 'some-id2',
                    'name'           => 'Translate',
                    'description'    => 'Translate',
                    'settingVersion' => '1.0.0',
                    'currentVersion' => '',
                    'required'       => [],
                    'isSystem'       => false,
                    'isComposer'     => true,
                    'status'         => 'install'
                ]
            ]
        ];
        $this->assertEquals($expects, $service->getList());

        $service
            ->expects($this->any())
            ->method('getModuleRequireds')
            ->willReturn(
                [
                    'some-id2' => [
                        'require' => 'module1'
                    ]
                ]
            );

        // test 4
        $this->assertEquals($expects, $service->getList());
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
            ->willReturn(
                [
                    'packageId' => 'some-id'
                ]
            );
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
     * Test is installModule throw exception if empty package
     */
    public function testIsInstallModuleThrowExceptionEmptyPackage()
    {
        try {
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
                ->willReturn('No such module');

            $service
                ->expects($this->any())
                ->method('getPackagistPackage')
                ->willReturn([]);

            $service->installModule('id', '1.0.0');
        } catch (Error $e) {
            // test
            $this->assertEquals('No such module', $e->getMessage());
        }
    }

    /**
     * Test is installModule throw exception if module install
     */
    public function testIsInstallModuleThrowExceptionModuleInstall()
    {
        try {
            $service = $this->createMockService(
                ModuleManager::class,
                ['isSystemUpdating', 'getPackagistPackage', 'translateError', 'getMetadata']
            );
            $metadata = $this->createMockService(Metadata::class, ['getModule']);

            $service
                ->expects($this->any())
                ->method('isSystemUpdating')
                ->willReturn(false);
            $service
                ->expects($this->any())
                ->method('translateError')
                ->willReturn('Such module is already installed');
            $service
                ->expects($this->any())
                ->method('getPackagistPackage')
                ->willReturn([]);
            $service
                ->expects($this->any())
                ->method('getMetadata')
                ->willReturn($metadata);

            $metadata
                ->expects($this->any())
                ->method('getModule')
                ->willReturn([]);

            $service->installModule('id', '1.0.0');
        } catch (Error $e) {
            // test
            $this->assertEquals('Such module is already installed', $e->getMessage());
        }
    }

    /**
     * Test is installModule throw exception if invalid module version
     */
    public function testIsInstallModuleThrowExceptionVersionInvalid()
    {
        try {
            $service = $this->createMockService(
                ModuleManager::class,
                ['isSystemUpdating', 'getPackagistPackage', 'translateError', 'getMetadata', 'isVersionValid']
            );
            $metadata = $this->createMockService(Metadata::class, ['getModule']);

            $service
                ->expects($this->any())
                ->method('isSystemUpdating')
                ->willReturn(false);
            $service
                ->expects($this->any())
                ->method('translateError')
                ->willReturn('Version in invalid');
            $service
                ->expects($this->any())
                ->method('getPackagistPackage')
                ->willReturn([]);
            $service
                ->expects($this->any())
                ->method('getMetadata')
                ->willReturn($metadata);
            $service
                ->expects($this->any())
                ->method('isVersionValid')
                ->willReturn(false);

            $metadata
                ->expects($this->any())
                ->method('getModule')
                ->willReturn([]);

            $service->installModule('id', '1.0.0');
        } catch (Error $e) {
            // test
            $this->assertEquals('Version in invalid', $e->getMessage());
        }
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
            ->willReturn(
                [
                    'name' => 'some-name'
                ]
            );

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
     * Test is updateModule throw exception if don't such module
     */
    public function testIsUpdateModuleThrowExceptionNoFindModule()
    {
        try {
            $service = $this->createMockService(
                ModuleManager::class,
                ['isSystemUpdating', 'getMetadata', 'getPackagistPackage', 'translateError']
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
            $service
                ->expects($this->any())
                ->method('translateError')
                ->willReturn('No such module');

            $metadata
                ->expects($this->any())
                ->method('getModule')
                ->willReturn(
                    [
                        'name' => 'some-name'
                    ]
                );

            $service->updateModule('id', '1.0.0');
        } catch (Error $e) {
            // test
            $this->assertEquals('No such module', $e->getMessage());
        }
    }

    /**
     * Test updateModule if module wasn't installed
     */
    public function testIsUpdateModuleThrowExceptionModuleWasNotInstalled()
    {
        try {
            $service = $this->createMockService(
                ModuleManager::class,
                ['isSystemUpdating', 'getMetadata', 'getPackagistPackage', 'translateError']
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
                ->willReturn(
                    [
                        'name' => 'some-name'
                    ]
                );
            $service
                ->expects($this->any())
                ->method('translateError')
                ->willReturn('Module was not installed');

            $metadata
                ->expects($this->any())
                ->method('getModule')
                ->willReturn([]);

            $service->updateModule('id', '1.0.0');
        } catch (Error $e) {
            // test
            $this->assertEquals('Module was not installed', $e->getMessage());
        }
    }

    /**
     * Test updateModule throw exception if invalid module version
     */
    public function testIsUpdateModuleThrowExceptionModuleVersionInvalid()
    {
        try {
            $service = $this->createMockService(
                ModuleManager::class,
                ['isSystemUpdating', 'getMetadata', 'getPackagistPackage', 'translateError']
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
                ->willReturn(
                    [
                        'name' => 'some-name'
                    ]
                );
            $service
                ->expects($this->any())
                ->method('translateError')
                ->willReturn('Version in invalid');

            $metadata
                ->expects($this->any())
                ->method('getModule')
                ->willReturn([]);

            $service->updateModule('id', '1.0.0');
        } catch (Error $e) {
            // test
            $this->assertEquals('Version in invalid', $e->getMessage());
        }
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
     * Test is deleteModule throw exception if system module
     */
    public function testIsDeleteModuleThrowExceptionIsSystemModule()
    {
        try {
            $service = $this->createMockService(
                ModuleManager::class,
                ['isSystemUpdating', 'isModuleSystem', 'translateError']
            );

            $service
                ->expects($this->any())
                ->method('isSystemUpdating')
                ->willReturn(false);
            $service
                ->expects($this->any())
                ->method('isModuleSystem')
                ->willReturn(true);
            $service
                ->expects($this->any())
                ->method('translateError')
                ->willReturn('isSystem');

            $service->deleteModule('id');
        } catch (Error $e) {
            // test
            $this->assertEquals('isSystem', $e->getMessage());
        }
    }

    public function testIsDeleteModuleThrowExceptionNoSuchModule()
    {
        try {
            $service = $this->createMockService(
                ModuleManager::class,
                ['isSystemUpdating', 'isModuleSystem', 'translateError', 'getMetadata']
            );
            $metadata = $this->createMockService(Metadata::class, ['getModule']);

            $service
                ->expects($this->any())
                ->method('isSystemUpdating')
                ->willReturn(false);
            $service
                ->expects($this->any())
                ->method('isModuleSystem')
                ->willReturn(true);
            $service
                ->expects($this->any())
                ->method('translateError')
                ->willReturn('No such module');
            $service
                ->expects($this->any())
                ->method('getMetadata')
                ->willReturn([]);

            $service->deleteModule('id');
        } catch (Error $e) {
            // test
            $this->assertEquals('No such module', $e->getMessage());
        }
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
            ->willReturn(
                [
                    'packageId' => 'some-id'
                ]
            );
        $service
            ->expects($this->any())
            ->method('getComposerService')
            ->willReturn($composer);

        $composer
            ->expects($this->any())
            ->method('getModuleComposerJson')
            ->willReturn(
                ['require' => [
                    'some-id' => 'some-value'
                ]]
            );
        $composer
            ->expects($this->any())
            ->method('getModuleStableComposerJson')
            ->willReturn(
                ['require' => [
                    'some-id' => 'some-value'
                ]]
            );
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
            ->willReturn(
                [
                    'packageId' => ''
                ]
            );

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
            'list'  => []
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
            ->willReturn(
                [
                    [
                        'id'      => 'some-id',
                        'deleted' => 0,
                        'data'    => 'some-data'
                    ]
                ]
            );

        // test 2
        $expects = [
            'total' => 1,
            'list'  => [
                [
                    'id'      => 'some-id',
                    'deleted' => 0,
                    'data'    => 'some-data'
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
