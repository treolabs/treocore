Espo.define('treo-crm:views/progress-manager/actions/request', 'view',
    Dep => Dep.extend({

        template: 'treo-crm:progress-manager/actions/request',

        actionId: null,

        url: 'ProgressManager/:id/request',

        requestType: 'GET',

        buttonLabel: 'request',

        events: {
            'click [data-action="request"]': function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.actionRequest();
            },
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.actionId = this.options.actionId;
        },

        data() {
            return {
                buttonLabel: this.buttonLabel
            };
        },

        actionRequest() {
            let requestMethod = `ajax${Espo.Utils.upperCaseFirst(this.requestType.toLowerCase())}Request`;
            if (typeof this[requestMethod] === 'function') {
                this[requestMethod](this.url.replace(':id', this.actionId), this.getRequestData())
                    .then(response => {
                        this.afterResponseCallback(response);
                    });
            }
        },

        getRequestData() {
            return {};
        },

        afterResponseCallback(response) {
            if (response) {
                this.trigger('reloadList');
            }
        }

    })
);

