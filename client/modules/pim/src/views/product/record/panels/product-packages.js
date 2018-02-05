Espo.define('pim:views/product/record/panels/product-packages', 'views/record/panels/bottom',
    Dep => Dep.extend({

        packageModel: null,

        setup() {
            Dep.prototype.setup.call(this);

            this.once('after:render', () => {
                this.setupGrid();
            });
        },

        setupGrid() {
            this.ajaxGetRequest(`Markets/ProductTypePackage/${this.model.id}/view`)
                .then(response => {
                    this.clearNestedViews();

                    this.getModelFactory().create('ProductTypePackage', model => {
                        this.packageModel = model;

                        model.set(response);
                        if (response.id) {
                            model.id = response.id;
                        }

                        this.createView('grid', 'pim:views/product-type-package/grid', {
                            model: model,
                            el: this.options.el + ' .row',
                            attributes: response
                        }, function (view) {
                            view.render();
                        }, this);
                    });

                });
        },

        clearNestedViews() {
            for (let key in this.nestedViews) {
                this.clearView(key);
            }
        },

        getDetailView() {
            let panelView = this.getParentView();
            if (panelView) {
                return panelView.getParentView()
            }
            return null;
        },

        getFieldViews() {
            let gridView = this.getView('grid');
            return gridView ? gridView.nestedViews : null;
        },

        getInitAttributes() {
            return this.getView('grid').attributes || [];
        },

        cancelEdit() {
            let gridView = this.getView('grid');
            if (gridView) {
                gridView.model.set(gridView.attributes);
            }
        },

        save() {
            let data = {};
            let fieldViews = this.getFieldViews() || {};
            for (let key in fieldViews) {
                data = _.extend(data, fieldViews[key].fetch());
            }

            if (this.packageModel) {
                this.ajaxPutRequest(`Markets/ProductTypePackage/${this.model.id}/update`, this.packageModel.getClonedAttributes())
                    .then(response => {});
            }
        },

        actionRefresh: function () {
            this.setupGrid();
        },

    })
);