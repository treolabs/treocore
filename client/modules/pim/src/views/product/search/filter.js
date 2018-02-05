Espo.define('pim:views/product/search/filter', 'views/search/filter', function (Dep) {

    return Dep.extend({

        template: 'pim:product/search/filter',

        setup: function () {
            var name = this.name = this.options.name;
            name = name.split('-')[0];
            this.clearedName = name;
            var type = this.model.getFieldType(name) || this.options.type;

            if (type) {
                var viewName = this.model.getFieldParam(name, 'view') || this.getFieldManager().getViewName(type);

                this.createView('field', viewName, {
                    mode: 'search',
                    model: this.model,
                    el: this.options.el + ' .field',
                    defs: {
                        name: name,
                    },
                    searchParams: this.options.params,
                });
            }
        },

        data: function () {
            return _.extend({
                label: this.options.isAttribute ? this.options.label : this.getLanguage().translate(this.name, 'fields', this.scope),
                clearedName: this.clearedName
            }, Dep.prototype.data.call(this));
        }
    });
});

