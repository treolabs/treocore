Espo.define('treo-crm:controllers/currency', 'controller', function (Dep) {

    return Dep.extend({

        defaultAction: "view",

        view: function () {
            // get model
            var model = this.getConfig().clone();
            model.defs = this.getConfig().defs;

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/currency',
                    recordView: 'views/admin/currency'
                });
            }, this);
            model.fetch();
        },
    });
});
