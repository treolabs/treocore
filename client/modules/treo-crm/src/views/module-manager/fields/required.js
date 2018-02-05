Espo.define('treo-crm:views/module-manager/fields/required', 'views/fields/multi-enum',
    Dep => Dep.extend({

        listTemplate: 'treo-crm:module-manager/fields/required/list',

        setup() {
            Dep.prototype.setup.call(this);

            this.translatedOptions = (this.getLanguage().data.ModuleManager || {}).moduleNames || [];
        }

    })
);

