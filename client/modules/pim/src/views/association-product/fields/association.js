Espo.define('pim:views/association-product/fields/association', 'pim:views/fields/filtered-link',
    Dep => Dep.extend({

        selectBoolFilterList:  ['onlyActive', 'notUsedAssociations'],

        boolFilterData: {
            notUsedAssociations() {
                return {mainProductId: this.model.get('mainProductId'), relatedProductId: this.model.get('relatedProductId')};
            }
        },

    })
);
