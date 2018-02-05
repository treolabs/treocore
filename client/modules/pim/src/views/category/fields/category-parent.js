Espo.define('pim:views/category/fields/category-parent', 'pim:views/fields/filtered-link',
    Dep => Dep.extend({

        selectBoolFilterList:  ['onlyActive', 'notEntity', 'notChildCategory'],

        boolFilterData: {
            notEntity() {
                return this.model.id;
            },
            notChildCategory() {
                return this.model.id;
            }
        },

    })
);
