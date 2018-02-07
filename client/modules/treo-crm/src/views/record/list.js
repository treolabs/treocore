Espo.define('treo-crm:views/record/list', 'class-replace!treo-crm:views/record/list', function (Dep) {

    return Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            _.extend(this.events, {
                'click a.link': function (e) {
                    e.stopPropagation();
                    if (e.ctrlKey) {
                        return;
                    }
                    if (!this.scope || this.selectable) {
                        return;
                    }
                    e.preventDefault();
                    var id = $(e.currentTarget).data('id');
                    var model = this.collection.get(id);

                    var scope = this.getModelScope(id);

                    var options = {
                        id: id,
                        model: model
                    };
                    if (this.options.keepCurrentRootUrl) {
                        options.rootUrl = this.getRouter().getCurrentUrl();
                    }

                    this.getRouter().navigate('#' + scope + '/view/' + id, {trigger: false});
                    this.getRouter().dispatch(scope, 'view', options);
                },
            });
        }
    });
});