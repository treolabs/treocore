<?php
/**
 * This file is part of EspoCRM and/or TreoCORE.
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

namespace Treo\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\Entity;
use Treo\PHPUnit\Framework\TestCase;

/**
 * Class QueueItemTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class QueueItemTest extends TestCase
{
    /**
     * Test is prepareEntityForOutput method exists
     */
    public function testIsPrepareEntityForOutputExists()
    {
        $service = $this->createMockService(QueueItem::class);

        // test
        $this->assertTrue(method_exists($service, 'prepareEntityForOutput'));
    }

    /**
     * Test is updateEntity method exists
     */
    public function testIsUpdateEntityExists()
    {
        $service = $this->createMockService(QueueItem::class);

        // test
        $this->assertTrue(method_exists($service, 'updateEntity'));
    }

    /**
     * Test is updateEntity method throw exception
     */
    public function testIsUpdateEntityThrowException()
    {
        try {
            $service = $this->createMockService(QueueItem::class, ['getEntity', 'exception']);
            $entity = $this->createMockService(Entity::class, ['get']);

            $entity
                ->expects($this->any())
                ->method('get')
                ->willReturn('Success');

            $service
                ->expects($this->any())
                ->method('getEntity')
                ->willReturn($entity);
            $service
                ->expects($this->any())
                ->method('exception')
                ->willReturn('Queue item cannot be changed.');

            $service->updateEntity('id', (object)['status' => 'some-status']);
        } catch (BadRequest $e) {
            // test 1
            $this->assertEquals('Queue item cannot be changed.', $e->getMessage());
        }

        try {
            $service = $this->createMockService(QueueItem::class, ['getEntity', 'exception']);
            $entity = $this->createMockService(Entity::class, ['get']);

            $entity
                ->expects($this->any())
                ->method('get')
                ->willReturn('some-status');

            $service
                ->expects($this->any())
                ->method('getEntity')
                ->willReturn($entity);
            $service
                ->expects($this->any())
                ->method('exception')
                ->willReturn('Queue item cannot be changed.');

            $service->updateEntity('id', (object)['status' => 'Closed']);
        } catch (BadRequest $e) {
            // test 2
            $this->assertEquals('Queue item cannot be changed.', $e->getMessage());
        }
    }
}
