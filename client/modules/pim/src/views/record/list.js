Espo.define('pim:views/record/list', 'views/record/list',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            (this.getMetadata().get(['clientDefs', this.scope, 'disabledMassActions']) || []).forEach(item => this.removeMassAction(item));
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.options.dragableListRows) {
                this.initDraggableList();
            }
        },

        initDraggableList() {
            this.$el.find(this.listContainerEl).sortable({
                delay: 150,
                update: function () {
                    this.saveListItemOrder();
                }.bind(this)
            });
        },

        saveListItemOrder() {
            let saveUrl = this.getListRowsOrderSaveUrl();
            if (saveUrl) {
                this.ajaxPutRequest(saveUrl, {ids: this.getIdsFromDom()})
                    .then(response => {
                        let statusMsg = 'Error occurred';
                        let type = 'error';
                        if (response) {
                            statusMsg = 'Saved';
                            type = 'success';
                        }
                        this.notify(statusMsg, type, 3000);
                    });
            }
        },

        getListRowsOrderSaveUrl() {
            return this.options.listRowsOrderSaveUrl;
        },

        getIdsFromDom() {
            return $.map(this.$el.find(`${this.listContainerEl} tr`), function (item) {
                return $(item).data('id');
            });
        }

    })
);

