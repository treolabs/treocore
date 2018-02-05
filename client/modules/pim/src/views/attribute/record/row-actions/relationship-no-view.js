Espo.define('pim:views/attribute/record/row-actions/relationship-no-view', 'views/record/row-actions/relationship',
    Dep => Dep.extend({

        getActionList() {
            var list = [];
            if (this.getAcl().check('Attribute', 'edit')) {
                list.push({
                    action: 'quickEditAttribute',
                    label: 'Edit',
                    data: {
                        id: this.model.get('attributeId'),
                    }
                });
            }
            list.push({
                action: 'removeFamilyAttributeLink',
                label: 'Unlink',
                data: {
                    id: this.model.id
                }
            });
            return list;
        }

    })
);

