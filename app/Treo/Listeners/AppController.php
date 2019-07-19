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

use Espo\Entities\Preferences;
use Espo\ORM\Entity;
use stdClass;
use Treo\Core\EventManager\Event;

/**
 * Class AppController
 *
 * @author y.haiduchyk <y.haiduchyk@treolabs.com>
 */
class AppController extends AbstractListener
{
    /**
     * After action user
     * Change language and Hide dashlets
     *
     * @param Event $event
     *
     * @throws \Espo\Core\Exceptions\Error
     */
    public function afterActionUser(Event $event)
    {
        $result = $event->getArgument('result');
        $language = $event->getArgument('request')->get('language');
        $currentLanguage = $result['language'] ?? '';

        $this->hideDashletsWithEmptyEntity($result['preferences']);

        if (!empty($result['user']) && !empty($language) && $currentLanguage !== $language) {
            /** @var Entity $preferences */
            $preferences = $this->getPreferences();

            // change language for user
            $preferences->set('language', $language);

            $result['language'] = $language;

            $this->saveEntity($preferences);
        }
        $event->setArgument('result', $result);
    }

    /**
     * Get preferences
     *
     * @return Preferences
     */
    protected function getPreferences(): Preferences
    {
        return $this->getContainer()->get('Preferences');
    }

    /**
     * Save entity
     *
     * @param Entity $entity
     */
    protected function saveEntity(Entity $entity): void
    {
        $this->getEntityManager()->saveEntity($entity);
    }

    /**
     * Hide dashlets with empty entity
     *
     * @param stdClass $preferences
     *
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function hideDashletsWithEmptyEntity(stdClass &$preferences): void
    {
        $dashletsOptions = $preferences->dashletsOptions;

        if (!empty($dashletsOptions)) {
            $dashboards = $preferences->dashboardLayout;
            foreach ($dashboards as $dashboard) {//iterate over dashboard
                foreach ($dashboard->layout as $key => $layout) {//iterate over layout of dashboard
                    $id = $layout->id;
                    //check isset dashlet with this ID layout
                    $issetDashlet = isset($dashletsOptions->{$id}) && is_object($dashletsOptions->{$id});
                    if ($issetDashlet && !class_exists($this->getEntityManager()->normalizeEntityName($dashletsOptions->{$id}->entityType))) {
                        //hide dashlet
                        unset($dashletsOptions->{$id});
                        unset($dashboard->layout[$key]);
                    }
                }
                //reset key in array
                $dashboard->layout = array_values($dashboard->layout);
            }
        }
    }
}
