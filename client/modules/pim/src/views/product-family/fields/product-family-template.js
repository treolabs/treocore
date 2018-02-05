Espo.define('pim:views/product-family/fields/product-family-template', 'pim:views/fields/filtered-link',
    Dep => Dep.extend({

        createDisabled: true,

        selectBoolFilterList:  ['onlyActive'],

    })
);
