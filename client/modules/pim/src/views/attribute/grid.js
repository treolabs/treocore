Espo.define('pim:views/attribute/grid', 'views/base',
    Dep => Dep.extend({

        template: 'pim:attribute/grid',

        gridLayout: null,

        mode: 'detail',

        setup() {
            Dep.prototype.setup.call(this);

            this.gridLayout = this.options.gridLayout;

            this.events = _.extend({
                'click .inline-remove-link': (e) => this.actionRemoveAttribute($(e.currentTarget).data('name'))
            }, this.events || {});
        },

        data() {
            return {gridLayout: this.gridLayout} || [];
        },

        afterRender() {
            this.buildGrid();

            Dep.prototype.afterRender.call(this);
        },

        buildGrid() {
            if (this.nestedViews) {
                for (let child in this.nestedViews) {
                    this.clearView(child);
                }
            }

            let mode = this.getDetailViewMode();

            this.gridLayout.forEach(panel => {
                panel.rows.forEach(row => {
                    row.forEach(cell => {
                        let fieldDefs = cell.defs;
                        let viewName = fieldDefs.type !== 'bool' ? this.getFieldManager().getViewName(fieldDefs.type) : 'pim:views/fields/bool-required';
                        this.createView(cell.name, viewName, {
                            mode: mode,
                            inlineEditDisabled: true,
                            model: this.model,
                            el: `${this.options.el} .field[data-name="${cell.name}"]`,
                            customLabel: cell.label,
                            defs: {
                                name: cell.name,
                            },
                            params: {
                                required: fieldDefs.required
                            }
                        }, view => view.render());
                    }, this);
                }, this);
            }, this)
        },

        actionRemoveAttribute(id) {
            if (!id) {
                return;
            }
            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                this.notify('Unlinking...');
                let productId = this.getParentView().model.id;
                $.ajax({
                    url: `Product/${productId}/attributes`,
                    data: JSON.stringify({id: id}),
                    type: 'DELETE',
                    contentType: 'application/json',
                    success: function () {
                        this.getParentView().updateGrid();
                        this.notify('Unlinked', 'success');
                        this.getParentView().model.trigger('after:unrelate', 'attributes');
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        },

        getDetailViewMode() {
            let mode = 'detail';
            let parentView = this.getParentView();
            if (parentView) {
                let detailView = this.getParentView().getDetailView();
                if (detailView) {
                    mode = detailView.mode;
                }
            }
            return mode;
        }

    })
);
