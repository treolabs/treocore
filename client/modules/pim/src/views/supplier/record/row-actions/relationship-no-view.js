Espo.define('pim:views/supplier/record/row-actions/relationship-no-view', 'views/record/row-actions/default',
    Dep=> Dep.extend({

        getActionList: function () {
            var list = [];
            if (this.options.acl.edit) {
                list = list.concat([
                    {
                        action: 'quickEditSupplier',
                        label: 'Edit',
                        data: {
                            id: this.model.id
                        }
                    },
                    {
                        action: 'removeSupplierLink',
                        label: 'Unlink',
                        data: {
                            id: this.model.id
                        }
                    }
                ]);
            }
            return list;
        },

    })
);


