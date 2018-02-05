Espo.define('treo-crm:controllers/representation-settings', 'controllers/admin', function (Dep, ) {

    return Dep.extend({

        defaultAction: 'representationSettings',

        representationSettings: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/settings',
                    recordView: 'views/admin/settings',
                    layoutName: 'representationSettingsList',
                    optionsToPass: ['layoutName']
                });
            }, this);
            model.fetch();
        },

    });

});
