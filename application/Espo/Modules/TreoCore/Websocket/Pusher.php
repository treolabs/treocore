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

namespace Espo\Modules\TreoCore\Websocket;

use Espo\Modules\TreoCore\Core\Container;
use Espo\Modules\TreoCore\Traits\ContainerTrait;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\Wamp\Topic;

/**
 * Websocket Pusher
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Pusher implements WampServerInterface
{
    /**
     * @var array
     */
    protected $dataMappers = [];

    /**
     * @var array
     */
    protected $subscribedTopics = [];

    use ContainerTrait;

    /**
     * Pusher constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        // set container
        $this->setContainer($container);

        // get mappers
        $mappers = $this
            ->getContainer()
            ->get('config')
            ->get('modules.websockets.data-mappers');

        if (!empty($mappers)) {
            $serviceFactory = $this->getContainer()->get('serviceFactory');

            foreach ($mappers as $service) {
                $this->dataMappers[] = $serviceFactory->create($service);
            }
        }
    }

    /**
     * Subscribe user
     *
     * @param ConnectionInterface $conn
     * @param string|Topic        $topic
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        // Make default storage
        if (empty($this->subscribedTopics[$topic->getId()])) {
            $this->subscribedTopics[$topic->getId()] = [
                'topic'   => $topic,
                'filters' => [
                    'default' => [],
                ],
            ];
        }

        // Add $conn
        $this->addConnToFilter($conn, $topic);

        // Refresh userdata
        $topic->broadcast($this->getDataByFilter($topic->getId(), 'default'), [], [$conn->WAMP->sessionId]);
    }

    /**
     * Unsubscribe user
     *
     * @param ConnectionInterface $conn
     * @param string|Topic        $topic
     *
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        // Remove topic from starage
        if ($topic->count() < 1) {
            unset($this->subscribedTopics[$topic->getId()]);

            return;
        }

        // Remove $conn from filters and empty filters
        $this->removeConnFromFilters($conn, $topic);
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        echo 'Open.' . PHP_EOL
            . ' Id: ' . $conn->WAMP->sessionId . PHP_EOL
            . ' At: ' . date('Y-m-d H:i:s') . PHP_EOL;
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        echo 'Close.' . PHP_EOL
            . ' Id: ' . $conn->WAMP->sessionId . PHP_EOL
            . ' At: ' . date('Y-m-d H:i:s') . PHP_EOL;
    }

    /**
     * @param ConnectionInterface $conn
     * @param string              $id
     * @param Topic|string        $topic
     * @param array               $params
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    /**
     * Set filter
     *
     * @param ConnectionInterface $conn
     * @param string|Topic        $topic
     * @param string              $event
     * @param array               $exclude
     * @param array               $eligible
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        if (!empty($event)) {
            $this->removeConnFromFilters($conn, $topic);
            $this->addConnToFilter($conn, $topic, $event);

            $topic->broadcast(
                $this->getDataByFilter(
                    $topic->getId(),
                    $this->prepareFilterStr($event)
                ),
                [],
                [$conn->WAMP->sessionId]
            );
        }
    }

    /**
     * Dummy
     *
     * @param ConnectionInterface $conn
     * @param \Exception          $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
    }

    /**
     * Re-send data to users
     *
     * @param string $topicId
     *
     * @return void
     */
    public function onChangeData($topicId)
    {
        // If the lookup topic object isn't set there is no one to publish to
        if (!array_key_exists($topicId, $this->subscribedTopics)) {
            return;
        }

        $topic = $this->subscribedTopics[$topicId]['topic'];

        // Prepare and send data by filters
        foreach ($this->subscribedTopics[$topicId]['filters'] as $filter => $connArray) {
            if (!empty($connArray)) {
                $data = $this->getDataByFilter($topic->getId(), $filter);
                if (!empty($data)) {
                    // re-send data
                    $topic->broadcast($data, [], $connArray);
                }
            }
        }
    }

    /**
     * @param $filter
     *
     * @return string
     */
    protected function prepareFilterStr($filter)
    {
        return (is_string($filter)) ? $filter : serialize($filter);
    }

    /**
     * Add conn to filter
     *
     * @param ConnectionInterface $conn
     * @param string|Topic        $topic
     * @param string              $filter
     *
     * @return Pusher
     */
    protected function addConnToFilter(ConnectionInterface $conn, $topic, $filter = 'default')
    {
        $id = $topic->getId();
        if (isset($this->subscribedTopics[$id])) {
            $this->subscribedTopics[$id]['filters'][$this->prepareFilterStr($filter)][] = $conn->WAMP->sessionId;
        }

        return $this;
    }

    /**
     * Remove conn from filters
     *
     * @param ConnectionInterface $conn
     * @param string|Topic        $topic
     *
     * @return bool
     */
    protected function removeConnFromFilters(ConnectionInterface $conn, $topic)
    {
        $id = $topic->getId();
        if (!isset($this->subscribedTopics[$id])) {
            return false;
        }
        foreach ($this->subscribedTopics[$id]['filters'] as $key => &$filter) {
            $filter = array_diff($filter, array($conn->WAMP->sessionId));
            if (empty($filter) && $key != 'default') {
                unset($this->subscribedTopics[$id]['filters'][$key]);
            }
        }
        unset($filter);
    }

    /**
     *
     * @param string $topicId
     * @param string $filter
     *
     * @return mixed
     */
    protected function getDataByFilter($topicId, $filter)
    {
        if (empty($this->dataMappers[$topicId])) {
            return false;
        }

        $service = $this->dataMappers[$topicId];
        $data = ($filter != 'default') ? unserialize($filter) : [];
        $service->setFilter($data);

        $rs = $service->getData();

        return $rs;
    }
}
