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

namespace Treo\Core\Loaders;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Metadata;
use PHPUnit\Framework\TestCase;
use Espo\Core\Utils\Metadata\OrmMetadata as Instance;

/**
 * Class OrmMetadataTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class OrmMetadataTest extends TestCase
{
    /**
     * Test load method
     */
    public function testLoadMethod()
    {
        $mock = $this->createPartialMock(OrmMetadata::class, ['getMetadata', 'getFileManager', 'getConfig']);
        $metadata = $this->createPartialMock(Metadata::class, []);
        $fileManager = $this->createPartialMock(Manager::class, []);
        $config = $this->createPartialMock(Config::class, ['get']);

        $config
            ->expects($this->any())
            ->method('get')
            ->withConsecutive(['useCache'])
            ->willReturnOnConsecutiveCalls([false]);

        $mock
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $mock
            ->expects($this->any())
            ->method('getFileManager')
            ->willReturn($fileManager);
        $mock
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        // test
        $this->assertInstanceOf(Instance::class, $mock->load());
    }
}
