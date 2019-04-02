<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: http://www.treopim.com
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
 * and "TreoPIM" word.
 */

declare(strict_types=1);

namespace Treo\Metadata;

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
    public static $jobs = ['Dummy', 'CheckNewVersion', 'CheckNewExtensionVersion'];

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
        // add owner
        $data = $this->addOwner($data);

        // delete activities
        $data = $this->deleteActivities($data);

        // delete tasks
        $data = $this->deleteTasks($data);

        // set allowed themes
        $data = $this->setAllowedTheme($data);

        // delete espo scheduled jobs
        $data = $this->deleteEspoScheduledJobs($data);

        // add onlyActive bool filter
        $data = $this->addOnlyActiveFilter($data);

        return $data;
    }

    /**
     * Add owner, assigned user, team if it needs
     *
     * @param array $data
     *
     * @return array
     */
    protected function addOwner(array $data): array
    {
        foreach ($data['scopes'] as $scope => $row) {
            // for owner user
            if (!empty($row['hasOwner'])) {
                if (empty($data['entityDefs'][$scope]['fields']['ownerUser'])) {
                    $data['entityDefs'][$scope]['fields']['ownerUser'] = [
                        "type"     => "link",
                        "required" => true,
                        "view"     => "views/fields/owner-user"
                    ];
                }
                if (empty($data['entityDefs'][$scope]['links']['ownerUser'])) {
                    $data['entityDefs'][$scope]['links']['ownerUser'] = [
                        "type"   => "belongsTo",
                        "entity" => "User"
                    ];
                }
                if (empty($data['entityDefs'][$scope]['indexes']['ownerUser'])) {
                    $data['entityDefs'][$scope]['indexes']['ownerUser'] = [
                        "columns" => [
                            "ownerUserId",
                            "deleted"
                        ]
                    ];
                }
            }

            // for assigned user
            if (!empty($row['hasAssignedUser'])) {
                if (empty($data['entityDefs'][$scope]['fields']['assignedUser'])) {
                    $data['entityDefs'][$scope]['fields']['assignedUser'] = [
                        "type"     => "link",
                        "required" => true,
                        "view"     => "views/fields/owner-user"
                    ];
                }
                if (empty($data['entityDefs'][$scope]['links']['assignedUser'])) {
                    $data['entityDefs'][$scope]['links']['assignedUser'] = [
                        "type"   => "belongsTo",
                        "entity" => "User"
                    ];
                }
                if (empty($data['entityDefs'][$scope]['indexes']['assignedUser'])) {
                    $data['entityDefs'][$scope]['indexes']['assignedUser'] = [
                        "columns" => [
                            "assignedUserId",
                            "deleted"
                        ]
                    ];
                }
            }

            // for teams
            if (!empty($row['hasTeam'])) {
                if (empty($data['entityDefs'][$scope]['fields']['teams'])) {
                    $data['entityDefs'][$scope]['fields']['teams'] = [
                        "type" => "linkMultiple",
                        "view" => "views/fields/teams"
                    ];
                }
                if (empty($data['entityDefs'][$scope]['links']['teams'])) {
                    $data['entityDefs'][$scope]['links']['teams'] = [
                        "type"                        => "hasMany",
                        "entity"                      => "Team",
                        "relationName"                => "EntityTeam",
                        "layoutRelationshipsDisabled" => true
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Remove field from index
     *
     * @param array  $indexes
     * @param string $fieldName
     *
     * @return array
     */
    protected function removeFieldFromIndex(array $indexes, string $fieldName): array
    {
        foreach ($indexes as $indexName => $fields) {
            // search field in index
            $key = array_search($fieldName, $fields['columns']);
            // remove field if exists
            if ($key !== false) {
                unset($indexes[$indexName]['columns'][$key]);
            }
        }

        return $indexes;
    }


    /**
     * Delete activities
     *
     * @param array $data
     *
     * @return array
     */
    protected function deleteActivities(array $data): array
    {
        foreach ($data['entityDefs'] as $entity => $row) {
            if (empty($data['scopes'][$entity]['hasActivities'])) {
                // remove from entityList
                $entityList = [];
                if (!empty($data['entityDefs']['Meeting']['fields']['parent']['entityList'])) {
                    foreach ($data['entityDefs']['Meeting']['fields']['parent']['entityList'] as $item) {
                        if ($entity != $item) {
                            $entityList[] = $item;
                        }
                    }
                }
                $data['entityDefs']['Meeting']['fields']['parent']['entityList'] = $entityList;

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

                // delete link
                if (isset($data['entityDefs'][$entity]['links']['meetings'])
                    && !in_array($entity, ['User'])) {
                    unset($data['entityDefs'][$entity]['links']['meetings']);
                }
            }
        }

        return $data;
    }

    /**
     * Delete tasks
     *
     * @param array $data
     *
     * @return array
     */
    protected function deleteTasks(array $data): array
    {
        foreach ($data['entityDefs'] as $entity => $row) {
            if (isset($data['scopes'][$entity]['hasTasks']) && $data['scopes'][$entity]['hasTasks'] === false) {
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

    /**
     * @param array $data
     *
     * @return array
     */
    protected function deleteEspoScheduledJobs(array $data): array
    {
        foreach (self::$jobs as $job) {
            if (isset($data['entityDefs']['ScheduledJob']['jobs'][$job])) {
                unset($data['entityDefs']['ScheduledJob']['jobs'][$job]);
            }
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function addOnlyActiveFilter(array $data): array
    {
        foreach ($data['entityDefs'] as $entity => $row) {
            if (isset($row['fields']['isActive']['type']) && $row['fields']['isActive']['type'] == 'bool') {
                // push
                $data['clientDefs'][$entity]['boolFilterList'][] = 'onlyActive';
            }
        }

        return $data;
    }
}
