<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Treo\PHPUnit\Framework\TestCase;

/**
 * Class RestApiDocsTest
 *
 * @author r.ratsun@zinitsolutions.com
 */
class RestApiDocsTest extends TestCase
{
    public function testIsGenerateDocumentationReturnTrue()
    {
        // create service
        $service = $this->createMockService(RestApiDocs::class, ['getHtml', 'setToFile']);
        $service
            ->expects($this->any())
            ->method('getHtml')
            ->willReturn('some html');
        $service
            ->expects($this->any())
            ->method('setToFile')
            ->willReturn(true);

        // test
        $this->assertTrue($service->generateDocumentation());
    }

    public function testIsGenerateDocumentationReturnFalse()
    {
        // create service
        $service = $this->createMockService(RestApiDocs::class, ['getHtml']);
        $service
            ->expects($this->any())
            ->method('getHtml')
            ->willReturn('');

        // test 1
        $this->assertFalse($service->generateDocumentation());

        // create service
        $service1 = $this->createMockService(RestApiDocs::class, ['getHtml', 'setToFile']);
        $service1
            ->expects($this->any())
            ->method('getHtml')
            ->willReturn('some html');
        $service1
            ->expects($this->any())
            ->method('setToFile')
            ->willReturn(false);

        // test 2
        $this->assertFalse($service1->generateDocumentation());
    }
}
