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
 * Class TreoUpgradeTest
 *
 * @author r.ratsun@zinitsolutions.com
 */
class TreoUpgradeTest extends TestCase
{
    public function testIsGetAvailableVersionReturnString()
    {
        $service = $this->createMockService(TreoUpgrade::class, ['getVersionData', 'getCurrentVersion']);
        $service
            ->expects($this->any())
            ->method('getCurrentVersion')
            ->willReturn('1.0.0');
        $service
            ->expects($this->any())
            ->method('getVersionData')
            ->willReturn(['version' => '2.0.1']);

        // test 1
        $this->assertEquals('2.0.1', $service->getAvailableVersion());

        $service = $this->createMockService(TreoUpgrade::class, ['getVersionData', 'getCurrentVersion']);
        $service
            ->expects($this->any())
            ->method('getCurrentVersion')
            ->willReturn('1.0.0');
        $service
            ->expects($this->any())
            ->method('getVersionData')
            ->willReturn(['version' => '2.1.1']);

        // test 2
        $this->assertEquals('2.1.1', $service->getAvailableVersion());
    }

    public function testIsGetAvailableVersionReturnNull()
    {
        $service = $this->createMockService(TreoUpgrade::class, ['getVersionData', 'getCurrentVersion']);
        $service
            ->expects($this->any())
            ->method('getCurrentVersion')
            ->willReturn('1.0.0');
        $service
            ->expects($this->any())
            ->method('getVersionData')
            ->willReturn([]);

        // test
        $this->assertEquals(null, $service->getAvailableVersion());
    }

    public function testIsCreateUpgradeJobMethodExists()
    {
        $service = $this->createMockService(TreoUpgrade::class);

        // test
        $this->assertTrue(method_exists($service, 'createUpgradeJob'));
    }

    public function testIsRunUpgradeJobMethodExists()
    {
        $service = $this->createMockService(TreoUpgrade::class);

        // test
        $this->assertTrue(method_exists($service, 'runUpgradeJob'));
    }

    public function testIsDownloadPackageMethodExists()
    {
        $service = $this->createMockService(TreoUpgrade::class);

        // test
        $this->assertTrue(method_exists($service, 'downloadPackage'));
    }
}
