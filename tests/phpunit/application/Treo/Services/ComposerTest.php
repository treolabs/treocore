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
 * Class ComposerTest
 *
 * @author r.ratsun@treolabs.com
 */
class ComposerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test is updateConfig method exists
     */
    public function testIsUpdateConfigMethodExists()
    {
        $service = $this->createPartialMock(Composer::class, []);

        // test
        $this->assertTrue(method_exists($service, 'updateConfig'));
    }

    /**
     * Test for runUpdate method
     */
    public function testRunUpdateMethod()
    {
        $service = $this->createPartialMock(Composer::class, ['filePutContents', 'updateConfig']);

        // test
        $this->assertTrue($service->runUpdate());
    }

    public function testIsCancelChangesMethodExists()
    {
        $service = $this->createPartialMock(Composer::class, []);

        // test
        $this->assertTrue(method_exists($service, 'cancelChanges'));
    }

    public function testIsUpdateMethodExists()
    {
        $service = $this->createPartialMock(Composer::class, []);

        // test
        $this->assertTrue(method_exists($service, 'update'));
    }

    public function testIsDeleteMethodExists()
    {
        $service = $this->createPartialMock(Composer::class, []);

        // test
        $this->assertTrue(method_exists($service, 'delete'));
    }

    public function testIsGetModuleComposerJsonMethodReturnArray()
    {
        $service = $this->createPartialMock(Composer::class, ['setModuleComposerJson']);
        $service
            ->expects($this->any())
            ->method('setModuleComposerJson')
            ->willReturn(null);

        $result = $service->getModuleComposerJson();

        // test 1
        $this->assertInternalType('array', $result);

        // test 2
        $this->assertTrue(isset($result['require']));
    }

    public function testIsGetModuleStableComposerJsonReturnArray()
    {
        $service = $this->createPartialMock(Composer::class, []);

        // test
        $this->assertInternalType('array', $service->getModuleStableComposerJson());
    }

    public function testIsSetModuleComposerJsonMethodExists()
    {
        $service = $this->createPartialMock(Composer::class, []);

        // test
        $this->assertTrue(method_exists($service, 'setModuleComposerJson'));
    }

    /**
     * Test for getComposerDiff method
     */
    public function testGetComposerDiffMethod()
    {
        // prepare expected
        $expected = ['install' => [], 'update' => [], 'delete' => []];

        $service = $this->createPartialMock(Composer::class, ['compareComposerSchemas']);
        $service
            ->expects($this->any())
            ->method('compareComposerSchemas')
            ->willReturn($expected);

        // test
        $this->assertEquals($expected, $service->getComposerDiff());
    }
}
