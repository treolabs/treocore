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

namespace Espo\Modules\TreoCore\Metadata;

/**
 * Metadata
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Metadata extends AbstractMetadata
{
    /**
     * @var array
     */
    protected $crmEntities = ['Account', 'Contact', 'Lead', 'Opportunity', 'Case'];

    /**
     * @var array
     */
    protected $allowedTheme = ['TreoDarkTheme'];

    /**
     * Modify
     *
     * @param array $data
     *
     * @return array
     */
    public function modify(array $data): array
    {
        // prepare entity defs for entity owners
        $data = $this->prepareOwners($data);

        // delete activities from CRM entity
        $data = $this->deleteActivities($data);

        // delete tasks from CRM entity
        $data = $this->deleteTasks($data);

        // set allowed themes
        $data = $this->setAllowedTheme($data);

        return $data;
    }

    /**
     * Prepare entity defs
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareOwners(array $data): array
    {
        foreach ($data['entityDefs'] as $ent => $row) {
            // unset ownerUser
            if (empty($data['scopes'][$ent]['hasOwner'])) {
                if (isset($data['entityDefs'][$ent]['fields']['ownerUser'])) {
                    unset($data['entityDefs'][$ent]['fields']['ownerUser']);
                }
                if (isset($data['entityDefs'][$ent]['links']['ownerUser'])) {
                    unset($data['entityDefs'][$ent]['links']['ownerUser']);
                }
                if (isset($data['entityDefs'][$ent]['indexes'])) {
                    $data['entityDefs'][$ent]['indexes'] = $this
                        ->prepareOwnersInIndex($data['entityDefs'][$ent]['indexes'], 'ownerUser');
                }
            }

            // unset assignedUser
            if (empty($data['scopes'][$ent]['hasAssignedUser'])) {
                if (isset($data['entityDefs'][$ent]['fields']['assignedUser'])) {
                    unset($data['entityDefs'][$ent]['fields']['assignedUser']);
                }
                if (isset($data['entityDefs'][$ent]['links']['assignedUser'])) {
                    unset($data['entityDefs'][$ent]['links']['assignedUser']);
                }
                if (isset($data['entityDefs'][$ent]['indexes'])) {
                    $data['entityDefs'][$ent]['indexes'] = $this
                        ->prepareOwnersInIndex($data['entityDefs'][$ent]['indexes'], 'assignedUser');
                }
            }

            // unset team
            if (empty($data['scopes'][$ent]['hasTeam'])) {
                if (isset($data['entityDefs'][$ent]['fields']['teams'])) {
                    unset($data['entityDefs'][$ent]['fields']['teams']);
                }
                if (isset($data['entityDefs'][$ent]['links']['teams'])) {
                    unset($data['entityDefs'][$ent]['links']['teams']);
                }
            }
        }

        return $data;
    }

    /**
     * Remove owner ids from index
     *
     * @param array  $indexes
     * @param string $fieldName
     *
     * @return array
     */
    protected function prepareOwnersInIndex(array $indexes, string $fieldName): array
    {
        foreach ($indexes as $indexName => $fields) {
            // search field in index
            $key = array_search($fieldName . 'Id', $fields['columns']);
            // remove field if exists
            if ($key !== false) {
                unset($indexes[$indexName]['columns'][$key]);
            }
        }

        return $indexes;
    }

    /**
     * Delete activities from CRM entity
     *
     * @param array $data
     *
     * @return array
     */
    protected function deleteActivities(array $data): array
    {
        foreach ($this->crmEntities as $entity) {
            // remove from entityList
            $entityList = [];
            foreach ($data['entityDefs']['Meeting']['fields']['parent']['entityList'] as $item) {
                if ($entity != $item) {
                    $entityList[] = $item;
                }
            }
            $data['entityDefs']['Meeting']['fields']['parent']['entityList'] = $entityList;

            // delete link
            if (isset($data['entityDefs'][$entity]['links']['meetings'])) {
                unset($data['entityDefs'][$entity]['links']['meetings']);
            }

            // delete from side panel
            foreach (['detail', 'detailSmall'] as $panel) {
                if (!empty($data['clientDefs'][$entity]['sidePanels'][$panel])) {
                    $sidePanelsData = [];
                    foreach ($data['clientDefs'][$entity]['sidePanels'][$panel] as $k => $item) {
                        if (!in_array($item['name'], ['activities', 'history'])) {
                            $sidePanelsData[] = $item;
                        }
                    }
                    $data['clientDefs'][$entity]['sidePanels'][$panel] = $sidePanelsData;
                }
            }
        }

        return $data;
    }

    /**
     * Delete tasks from CRM entity
     *
     * @param array $data
     *
     * @return array
     */
    protected function deleteTasks(array $data): array
    {
        foreach ($this->crmEntities as $entity) {
            // remove from entityList
            $entityList = [];
            foreach ($data['entityDefs']['Task']['fields']['parent']['entityList'] as $item) {
                if ($entity != $item) {
                    $entityList[] = $item;
                }
            }
            $data['entityDefs']['Task']['fields']['parent']['entityList'] = $entityList;

            // remove from client defs
            if (isset($data['clientDefs'][$entity]['sidePanels'])) {
                foreach ($data['clientDefs'][$entity]['sidePanels'] as $panel => $rows) {
                    $sidePanelsData = [];
                    foreach ($rows as $k => $row) {
                        if ($row['name'] != 'tasks') {
                            $sidePanelsData[] = $row;
                        }
                    }
                    $data['clientDefs'][$entity]['sidePanels'][$panel] = $sidePanelsData;
                }
            }
        }

        return $data;
    }

    /**
     * Set allowed theme
     *
     * @param array $data
     *
     * @return array
     */
    protected function setAllowedTheme(array $data): array
    {
        foreach ($data['themes'] as $themeName => $themeData) {
            // check is theme allowed
            if (!in_array($themeName, $this->allowedTheme)) {
                unset($data['themes'][$themeName]);
            }
        }

        return $data;
    }
}
