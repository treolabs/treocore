Espo.define('treo-crm:views/progress-manager/fields/status', 'views/fields/base',
    Dep => Dep.extend({

        listTemplate: 'treo-crm:progress-manager/fields/status/list',

        data() {
            return {
                value: (this.model.get(this.name) || {}).translate || '',
                color: this.getColor()
            };
        },

        getColor() {
            let color;
            switch ((this.model.get(this.name) || {}).key) {
                case 'new':
                    color = '#5bc0de';
                    break;
                case 'in_progress':
                    color = '#ef990e';
                    break;
                case 'error':
                    color = '#cf605d';
                    break;
                case 'success':
                    color = '#85b75f';
                    break;
                default:
                    color = '#000';
                    break;
            }
            return color;
        }

    })
);

