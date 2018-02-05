Espo.define('treo-crm:views/progress-manager/fields/actions', 'views/fields/base',
    Dep => Dep.extend({

        listTemplate: 'treo-crm:progress-manager/fields/actions/list',

        defaultActionView: 'treo-crm:views/progress-manager/actions/show-message',

        data() {
            return {
                actions: this.model.get(this.name) || []
            };
        },

        afterRender() {
            this.buildActions();
        },

        buildActions() {
            (this.model.get(this.name) || []).forEach(action => {
                let viewName = this.getMetadata().get(['clientDefs', 'ProgressManager', 'progressActionViews', action.type]) || this.defaultActionView;
                this.createView(action.type, viewName, {
                    el: `${this.options.el} .progress-manager-action[data-type="${action.type}"]`,
                    actionData: action.data,
                    actionId: this.model.id
                }, view => {
                    this.listenTo(view, 'reloadList', () => {
                        this.model.trigger('reloadList');
                    });
                    view.render();
                });
            });
        }

    })
);

