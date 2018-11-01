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

use Treo\PHPUnit\Framework\TestCase;

/**
 * Class InstallerTest
 *
 * @author r.zablodskiy@zinitsolutions.com
 */
class InstallerTest extends TestCase
{

    /**
     * Test is generateConfig return true
     */
    public function testIsGenerateConfigReturnTrue()
    {
        $service = $this->createMockService(Installer::class, ['checkIfConfigExist']);

        $service
            ->expects($this->once())
            ->method('checkIfConfigExist')
            ->willReturn(true);


        $this->assertTrue($service->generateConfig());
    }

    /**
     * Test is generateConfig return false
     */
    public function testIsGenerateConfigReturnFalse()
    {
        $service = $this->createMockService(Installer::class, ['checkIfConfigExist']);

        $service
            ->expects($this->once())
            ->method('checkIfConfigExist')
            ->willReturn(false);


        $this->assertFalse($service->generateConfig());
    }

    /**
     * Test is setLanguage return array
     */
    public function testIsSetLanguageReturnArray()
    {
        $service = $this->createMockService(Installer::class, ['isCorrectLanguage', 'translateError', 'saveConfig']);

        $service
            ->expects($this->once())
            ->method('isCorrectLanguage')
            ->willReturn(true);

        $service
            ->expects($this->any())
            ->method('translateError')
            ->willReturn(true);

        $service
            ->expects($this->any())
            ->method('saveConfig')
            ->willReturn(true);

        $this->assertInternalType('array', $service->setLanguage('de_DE'));
    }

    /**
     * Test is setDbSettings return array
     */
    public function testIsSetDbSettingsReturnArray()
    {
        $service = $this->createMockService(
            Installer::class,
            ['prepareDbParams', 'isConnectToDb', 'saveConfig', 'translateError']
        );

        $service
            ->expects($this->once())
            ->method('prepareDbParams')
            ->willReturn([
                'field' => 'value'
            ]);

        $service
            ->expects($this->once())
            ->method('translateError')
            ->willReturn('Error');

        $result = $service->setDbSettings([]);

        // test is return array
        $this->assertInternalType('array', $result);

        // test if not empty message
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Test is createAdmin return array
     */
    public function testIsCreateAdminReturnArray()
    {
        $service= $this->createMockService(
            Installer::class,
            ['createSystemUsers', 'translateError']
        );

        $service
            ->expects($this->any())
            ->method('createSystemUsers')
            ->willReturn(null);

        $service
            ->expects($this->any())
            ->method('translateError')
            ->willReturn('Error');

        // test is password equals to confirmPassword
        $this->assertInternalType('array', $service->createAdmin([
            'password' => 'some-password',
            'confirmPassword' => 'some-password'
        ]));

        // test is password not equals to confirmPassword
        $this->assertInternalType('array', $service->createAdmin([
            'password' => 'some-password',
            'confirmPassword' => 'some-other-password'
        ]));
    }

    /**
     * Test is getRequiredsList method return array
     */
    public function testIsGetRequiredsListMethodReturnArray()
    {
        $service = $this->createMockService(Installer::class, ['getInstallConfig']);

        $service
            ->expects($this->once())
            ->method('getInstallConfig')
            ->willReturn([
                'requirements' => []
            ]);

        $this->assertInternalType('array', $service->getRequiredsList());
    }

    /**
     * Test is checkDBConnection return array data
     */
    public function testIsCheckDbConnectReturnArray()
    {
        $service = $this->createMockService(Installer::class, ['isConnectToDb', 'prepareDbParams']);
        $data = [
            'host'     => 'host',
            'port'     => 'port',
            'dbname'   => 'dbname',
            'user'     => 'user',
            'password' => 'password'
        ];

        $service
            ->expects($this->once())
            ->method('isConnectToDb')
            ->willReturn(true);

        $service
            ->expects($this->once())
            ->method('prepareDbParams')
            ->willReturn($data);

        $result = $service->checkDBConnect($data);

        // test is array
        $this->assertInternalType('array', $result);

        // test is have key
        $this->assertArrayHasKey('status', $result);
    }

    /**
     * Test is checkDBConnection throw PDOException
     */
    public function testIsCheckDbConnectThrowException()
    {
        $service = $this->createMockService(Installer::class, ['isConnectToDb', 'prepareDbParams', 'translateError']);
        $data = [
            'host'     => 'host',
            'port'     => 'port',
            'dbname'   => 'dbname',
            'user'     => 'user',
            'password' => 'password'
        ];

        $service
            ->expects($this->once())
            ->method('isConnectToDb')
            ->willThrowException(
                new \PDOException()
            );

        $service
            ->expects($this->once())
            ->method('prepareDbParams')
            ->willReturn($data);

        $service
            ->expects($this->once())
            ->method('translateError')
            ->willReturn('Error');

        $result = $service->checkDBConnect($data);

        // test is set error message
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Test is checkPermissions return true
     */
    public function testIsCheckPermissionsReturnTrue()
    {
        $service = $this->createMockService(Installer::class, ['getLastPermissionError']);

        $service
            ->expects($this->once())
            ->method('getLastPermissionError')
            ->willReturn('');

        $this->assertTrue($service->checkPermissions());
    }

    /**
     * Test is getLicenseAndLanguages method return array
     */
    public function testIsGetLicenseAndLanguagesReturnArray()
    {
        $service = $this->createMockService(Installer::class);

        $this->assertInternalType('array', $service->getLicenseAndLanguages());
    }

    /**
     * Test is isInstalled method exist
     */
    public function testIsInstalledMethodExists()
    {
        $service = $this->createMockService(Installer::class);

        // test is method return true
        $this->assertTrue(method_exists($service, 'isInstalled'));
    }

    /**
     * Test is getTranslations method exists
     */
    public function testIsGetTranslationsMethodExists()
    {
        $service = $this->createMockService(Installer::class);

        $this->assertTrue(method_exists($service, 'getTranslations'));
    }

    /**
     * Test is getDefaultDbSettings method exists
     */
    public function testIsGetDefaultDbSettingsMethodExist()
    {
        $service = $this->createMockService(Installer::class);

        $this->assertTrue(method_exists($service, 'getDefaultDbSettings'));
    }
}
