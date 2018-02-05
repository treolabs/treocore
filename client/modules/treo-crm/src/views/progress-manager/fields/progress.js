Espo.define('treo-crm:views/progress-manager/fields/progress', 'views/fields/base',
    Dep => Dep.extend({

        listTemplate: 'treo-crm:progress-manager/fields/progress/list',

        data() {
            let data = {
                value: this.model.get(this.name) || 0
            };

            switch(data.value) {
                case 100:
                    data.stateClass = 'progress-bar-success';
                    break;
                case 0:
                    data.stateClass = 'progress-bar-danger';
                    break;
                default:
                    data.stateClass = 'progress-bar-warning';
                    break;
            }

            return data;
        },

    })
);

