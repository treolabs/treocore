<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 TreoLabs GmbH
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

namespace Treo\Core;

use Treo\PHPUnit\Framework\TestCase;

/**
 * Class UpgradeManagerTest
 *
 * @author r.zablodskiy@zinitsolutions.com
 */
class UpgradeManagerTest extends TestCase
{
    /**
     * Test is getManifest method exists
     */
    public function testIsGetManifestMethodExists()
    {
        $service = $this->createMockService(UpgradeManager::class);

        // test
        $this->assertTrue(method_exists($service, 'getManifest'));
    }

    /**
     * Test is upload method exists
     */
    public function testIsUploadMethodExists()
    {
        $service = $this->createMockService(UpgradeManager::class);

        // test
        $this->assertTrue(method_exists($service, 'upload'));
    }

    /**
     * Test is install method exists
     */
    public function testIsInstallMethodExists()
    {
        $service = $this->createMockService(UpgradeManager::class);

        // test
        $this->assertTrue(method_exists($service, 'install'));
    }

    /**
     * Test is uninstall method exists
     */
    public function testIsUninstallMethodExists()
    {
        $service = $this->createMockService(UpgradeManager::class);

        // test
        $this->assertTrue(method_exists($service, 'uninstall'));
    }

    /**
     * Test is delete method exists
     */
    public function testIsDeleteMethodExists()
    {
        $service = $this->createMockService(UpgradeManager::class);

        // test
        $this->assertTrue(method_exists($service, 'delete'));
    }
}
