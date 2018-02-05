Espo.define('pim:views/association-product/fields/related-product', 'pim:views/fields/filtered-link',
    Dep => Dep.extend({

        selectBoolFilterList:  ['onlyActive', 'notEntity', 'notAssociatedProducts'],

        boolFilterData: {
            notEntity() {
                return this.model.get('mainProductId');
            },
            notAssociatedProducts() {
                return {mainProductId: this.model.get('mainProductId'), associationId: this.model.get('associationId')};
            }
        },

    })
);
