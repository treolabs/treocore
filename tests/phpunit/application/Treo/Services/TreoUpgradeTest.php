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

/**
 * Class TreoUpgradeTest
 *
 * @author r.ratsun@zinitsolutions.com
 */
class TreoUpgradeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test for getVersions method
     */
    public function testGetVersionsMethod()
    {
        // prepare methods
        $methods = ['getDomain', 'getCurrentVersion', 'isDevelopMod', 'readJsonData'];

        // create service
        $service = $this->createPartialMock(TreoUpgrade::class, $methods);
        $service
            ->expects($this->any())
            ->method('getDomain')
            ->willReturn('some-path');
        $service
            ->expects($this->any())
            ->method('getCurrentVersion')
            ->willReturn('1.0.0');
        $service
            ->expects($this->any())
            ->method('isDevelopMod')
            ->willReturn(true);
        $service
            ->expects($this->any())
            ->method('readJsonData')
            ->willReturn(['1.0.0', '1.0.1']);

        // test
        $this->assertEquals(['1.0.0', '1.0.1'], $service->getVersions());
    }

    /**
     * Is createUpgradeJob method exists
     */
    public function testIsCreateUpgradeJobMethodExists()
    {
        $service = $this->createPartialMock(TreoUpgrade::class, []);

        // test
        $this->assertTrue(method_exists($service, 'createUpgradeJob'));
    }

    /**
     * Is runUpgradeJob method exists
     */
    public function testIsRunUpgradeJobMethodExists()
    {
        $service = $this->createPartialMock(TreoUpgrade::class, []);

        // test
        $this->assertTrue(method_exists($service, 'runUpgradeJob'));
    }

    /**
     * Is downloadPackage method exists
     */
    public function testIsDownloadPackageMethodExists()
    {
        $service = $this->createPartialMock(TreoUpgrade::class, []);

        // test
        $this->assertTrue(method_exists($service, 'downloadPackage'));
    }

    /**
     * Is notify method exists
     */
    public function testIsNotifyMethodExists()
    {
        $this->assertTrue(method_exists($this->createPartialMock(TreoUpgrade::class, []), 'notify'));
    }

    /**
     * Test for getUpdateLog method
     */
    public function testGetUpdateLogMethod()
    {
        // prepare outputs
        $output = 'Some str';
        $output2 = 'Nothing to install or update\n';

        $service = $this->createPartialMock(TreoUpgrade::class, ['getComposerUpdateLog', 'parseComposerOutput']);
        $service2 = clone $service;

        // test 1
        $service
            ->expects($this->any())
            ->method('getComposerUpdateLog')
            ->willReturn(['output' => $output, 'status' => 0]);
        $service
            ->expects($this->any())
            ->method('parseComposerOutput')
            ->willReturn($output);
        $this->assertEquals(['log' => $output, 'status' => true], $service->getUpdateLog());

        // test 2
        $service2
            ->expects($this->any())
            ->method('getComposerUpdateLog')
            ->willReturn(['output' => $output2, 'status' => 0]);
        $service2
            ->expects($this->any())
            ->method('parseComposerOutput')
            ->willReturn($output2);
        $this->assertEquals(['log' => $output2, 'status' => false], $service2->getUpdateLog());
    }
}
