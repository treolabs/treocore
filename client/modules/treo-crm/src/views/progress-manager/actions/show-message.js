Espo.define('treo-crm:views/progress-manager/actions/show-message', 'view',
    Dep => Dep.extend({

        template: 'treo-crm:progress-manager/actions/show-message',

        actionData: {},

        events: {
            'click [data-action="showMessageModal"]': function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.actionShowMessageModal();
            },
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.actionData = this.options.actionData || this.actionData;
        },

        data() {
            return {
                showButton: !!this.actionData.message
            };
        },

        actionShowMessageModal() {
            this.createView('modal', 'treo-crm:views/progress-manager/modals/show-message', {
                message: this.actionData.message
            }, view => view.render());
        }

    })
);

