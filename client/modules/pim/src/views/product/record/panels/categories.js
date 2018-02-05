Espo.define('pim:views/product/record/panels/categories', 'views/record/panels/relationship',
    Dep => Dep.extend({

        boolFilterData: {
            notLinkedWithProduct() {
                return this.model.id;
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            let create = this.buttonList.find(item => item.action === (this.defs.createAction || 'createRelated'));
            if (create) {
                create.data.fullFormDisabled = true;
            }

            let select = this.actionList.find(item => item.action === (this.defs.selectAction || 'selectRelated'));

            if (select) {
                select.data.boolFilterListCallback = 'getSelectBoolFilterList';
                select.data.boolFilterDataCallback = 'getSelectBoolFilterData';
            }
        },

        actionUnlinkRelated (data) {
            var id = data.id;

            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                var model = this.collection.get(id);
                this.notify('Unlinking...');
                $.ajax({
                    url: this.collection.url,
                    type: 'DELETE',
                    data: JSON.stringify({
                        id: id
                    }),
                    contentType: 'application/json',
                    success: function () {
                        this.notify('Unlinked', 'success');
                        this.collection.fetch();
                        this.model.trigger('after:unrelate', this.link);
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        },

        actionRemoveRelated: function (data) {
            var id = data.id;

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages'),
                confirmText: this.translate('Remove')
            }, function () {
                var model = this.collection.get(id);
                this.notify('Removing...');
                model.destroy({
                    success: function () {
                        this.notify('Removed', 'success');
                        this.collection.fetch();
                        this.model.trigger('after:unrelate', this.link);
                    }.bind(this),
                });
            }, this);
        },

        getSelectBoolFilterList() {
            return this.defs.selectBoolFilterList || null
        },

        getSelectBoolFilterData(boolFilterList) {
            let data = {};
            if (Array.isArray(boolFilterList)) {
                boolFilterList.forEach(item => {
                    if (this.boolFilterData && typeof this.boolFilterData[item] === 'function') {
                        data[item] = this.boolFilterData[item].call(this);
                    }
                });
            }
            return data;
        },

    })
);
