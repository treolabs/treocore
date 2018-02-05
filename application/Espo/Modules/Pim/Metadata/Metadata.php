<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Metadata;

use Espo\Modules\TreoCrm\Metadata\AbstractMetadata;

/**
 * Metadata
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Metadata extends AbstractMetadata
{

    /**
     * Modify
     *
     * @param array $data
     *
     * @return array
     */
    public function modify(array $data): array
    {
        // prepare tasks
        $data = $this->activateTasks($data);

        return $data;
    }

    /**
     * Activate tasks
     *
     * @param array $data
     *
     * @return array
     */
    protected function activateTasks(array $data): array
    {
        // prepare data
        $pimEntities = ['Product', 'Category', 'ProductFamily', 'Association', 'Attribute'];

        foreach ($pimEntities as $entity) {
            // push to entityList
            $data['entityDefs']['Task']['fields']['parent']['entityList'][] = $entity;

            // add field
            $data['entityDefs']['Task']['fields'][lcfirst($entity)] = [
                "type"     => "link",
                "readOnly" => true
            ];

            // add link
            $data['entityDefs']['Task']['links'][lcfirst($entity)] = [
                "type"   => "belongsTo",
                "entity" => $entity
            ];

            // add link to entity
            $data['entityDefs'][$entity]['links']['tasks'] = [
                "type"                        => "hasChildren",
                "entity"                      => "Task",
                "foreign"                     => "parent",
                "layoutRelationshipsDisabled" => true
            ];

            // add to client defs
            foreach (['detail', 'detailSmall'] as $panel) {
                $data['clientDefs'][$entity]['sidePanels'][$panel][] = [
                    "name"     => "tasks",
                    "label"    => "Tasks",
                    "view"     => "crm:views/record/panels/tasks",
                    "aclScope" => "Task"
                ];
            }
        }

        return $data;
    }
}
