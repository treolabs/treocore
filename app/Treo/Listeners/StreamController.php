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

declare(strict_types=1);

namespace Treo\Listeners;

use Treo\Core\EventManager\Event;

/**
 * Class StreamController
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class StreamController extends AbstractListener
{

    /**
     * After action List
     *
     * @param Event $event
     */
    public function afterActionList(Event $event)
    {
        $result = $event->getArgument('result');

        $this->hideRemovedEntity($result);

        $event->setArgument('result', $result);
    }

    /**
     * Hide items if parentType (Entity) was removed
     *
     * @param array $result
     */
    protected function hideRemovedEntity(array &$result): void
    {
        //get all entities
        $entities = array_keys($this->getContainer()->get('metadata')->get('entityDefs'));
        foreach ($result['list'] as $key => $item) {
            //is parentType in active entities
            if (!in_array($item["parentType"], $entities)) {
                //remove item
                unset($result['list'][$key]);
            }
        }
        //reset key
        $result['list'] = array_values($result['list']);
    }
}
