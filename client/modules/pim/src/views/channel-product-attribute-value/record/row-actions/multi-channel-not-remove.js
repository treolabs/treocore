Espo.define('pim:views/channel-product-attribute-value/record/row-actions/multi-channel-not-remove',
    'views/record/row-actions/default',
    Dep=> Dep.extend({

        getActionList: function () {
            var list = [{
                action: 'quickEdit',
                label: 'Edit',
                data: {
                    id: this.model.id
                }
            }];

            if (!this.model.get('attributeIsMultiChannel')) {
                list = list.concat([
                    {
                        action: 'quickRemove',
                        label: 'Remove',
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


