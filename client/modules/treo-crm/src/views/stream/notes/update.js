Espo.define('treo-crm:views/stream/notes/update', 'views/stream/notes/update', function (Dep) {

    return Dep.extend({

        template: 'treo-crm:stream/notes/update',

        setup: function () {
            var data = this.model.get('data');

            var fields = data.fields;

            this.createMessage();

            this.wait(true);
            this.getModelFactory().create(this.model.get('parentType'), function (model) {
                var modelWas = model;
                var modelBecame = model.clone();

                data.attributes = data.attributes || {};

                modelWas.set(data.attributes.was);
                modelBecame.set(data.attributes.became);

                this.fieldsArr = [];

                fields.forEach(function (field) {
                    var type = model.getFieldType(field) || 'base';
                    var viewName = this.getMetadata().get('entityDefs.' + model.name + '.fields.' + field + '.view') || this.getFieldManager().getViewName(type);
                    this.createView(field + 'Was', viewName, {
                        el: this.options.el + '.was',
                        model: modelWas,
                        readOnly: true,
                        defs: {
                            name: field
                        },
                        mode: 'detail',
                        inlineEditDisabled: true
                    });
                    this.createView(field + 'Became', viewName, {
                        el: this.options.el + '.became',
                        model: modelBecame,
                        readOnly: true,
                        defs: {
                            name: field
                        },
                        mode: 'detail',
                        inlineEditDisabled: true
                    });

                    this.fieldsArr.push({
                        field: field,
                        was: field + 'Was',
                        became: field + 'Became'
                    });

                }, this);

                this.wait(false);

            }, this);
        },

    });
});

