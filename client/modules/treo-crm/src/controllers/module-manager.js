Espo.define('treo-crm:controllers/module-manager', 'controller', function (Dep) {

    return Dep.extend({

        defaultAction: "list",

        list: function () {
            this.collectionFactory.create('ModuleManager', function (collection) {
                collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;
                collection.sortBy = 'name';
                collection.asc = false;

                this.main('treo-crm:views/module-manager/list', {
                    scope: 'ModuleManager',
                    collection: collection
                });
            }, this);
        },
    });
});
