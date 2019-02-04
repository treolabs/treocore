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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
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

namespace Treo\Core\Loaders;

use PHPUnit\Framework\TestCase;
use Treo\Core\Utils\Database\Schema\Schema as Instance;
use Treo\Core\Utils\Config;
use Treo\Core\Utils\Metadata;
use Treo\Core\Utils\File\ClassParser;
use Espo\Core\Utils\File\Manager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Metadata\OrmMetadata;
use Treo\Core\Container;

/**
 * Class SchemaTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class SchemaTest extends TestCase
{
    /**
     * Test load method
     */
    public function testLoadMethod()
    {
        $mock = $this->createPartialMock(
            Schema::class,
            [
                'getConfig',
                'getMetadata',
                'getFileManager',
                'getEntityManager',
                'getClassParser',
                'getOrmMetadata',
                'getContainer',
                'getSchema'
            ]
        );
        $config = $this->createPartialMock(Config::class, ['get']);
        $metadata = $this->createPartialMock(Metadata::class, []);
        $fileManager = $this->createPartialMock(Manager::class, []);
        $entityManager = $this->createPartialMock(EntityManager::class, []);
        $classParser = $this->createPartialMock(ClassParser::class, []);
        $ormMetadata = $this->createPartialMock(OrmMetadata::class, []);
        $container = $this->createPartialMock(Container::class, []);
        $instance = $this->getMockBuilder(Instance::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config
            ->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $mock
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
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
            ->method('getEntityManager')
            ->willReturn($entityManager);
        $mock
            ->expects($this->any())
            ->method('getClassParser')
            ->willReturn($classParser);
        $mock
            ->expects($this->any())
            ->method('getOrmMetadata')
            ->willReturn($ormMetadata);
        $mock
            ->expects($this->any())
            ->method('getContainer')
            ->willReturn($container);
        $mock
            ->expects($this->any())
            ->method('getSchema')
            ->willReturn($instance);

        // test
        $this->assertInstanceOf(Instance::class, $mock->load());
    }
}
