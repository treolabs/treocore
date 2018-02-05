Espo.define('pim:views/modals/edit-without-side', 'views/modals/edit',
    Dep => Dep.extend({

        sideDisabled: true,

        fullFormDisabled: true,

        setup() {
            Dep.prototype.setup.call(this);

            this.header = this.options.header || this.header;
        },

        createRecordView: function (model, callback) {
            var viewName =
                this.editViewName ||
                this.editView ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'editSmall']) ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'editQuick']) ||
                'views/record/edit-small';
            var options = {
                model: model,
                el: this.containerSelector + ' .edit-container',
                type: 'editSmall',
                layoutName: this.layoutName || 'detailSmall',
                columnCount: this.columnCount,
                buttonsPosition: false,
                sideDisabled: this.sideDisabled,
                bottomDisabled: this.bottomDisabled,
                isWide: true,
                exit: function () {}
            };
            this.createView('edit', viewName, options, callback);
        },

    })
);

