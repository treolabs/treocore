<?php
declare(strict_types=1);

namespace Espo\Modules\TreoCrm\Metadata;

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
            if (isset($data['scopes'][$ent]['hasOwner']) && empty($data['scopes'][$ent]['hasOwner'])) {
                if (isset($data['entityDefs'][$ent]['fields']['ownerUser'])) {
                    unset($data['entityDefs'][$ent]['fields']['ownerUser']);
                }
                if (isset($data['entityDefs'][$ent]['links']['ownerUser'])) {
                    unset($data['entityDefs'][$ent]['links']['ownerUser']);
                }
                if (isset($data['entityDefs'][$ent]['indexes']['ownerUser'])) {
                    unset($data['entityDefs'][$ent]['indexes']['ownerUser']);
                }
            }

            // unset assignedUser
            if (isset($data['scopes'][$ent]['hasAssignedUser']) && empty($data['scopes'][$ent]['hasAssignedUser'])) {
                if (isset($data['entityDefs'][$ent]['fields']['assignedUser'])) {
                    unset($data['entityDefs'][$ent]['fields']['assignedUser']);
                }
                if (isset($data['entityDefs'][$ent]['links']['assignedUser'])) {
                    unset($data['entityDefs'][$ent]['links']['assignedUser']);
                }
                if (isset($data['entityDefs'][$ent]['indexes']['assignedUser'])) {
                    unset($data['entityDefs'][$ent]['indexes']['assignedUser']);
                }
            }

            // unset team
            if (isset($data['scopes'][$ent]['hasTeam']) && empty($data['scopes'][$ent]['hasTeam'])) {
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
