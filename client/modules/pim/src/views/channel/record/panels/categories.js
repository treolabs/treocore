Espo.define('pim:views/channel/record/panels/categories', 'views/record/panels/relationship',
    Dep => Dep.extend({

        boolFilterData: {
            notLinkedWithChannel() {
                return this.model.id;
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            let select = this.actionList.find(item => item.action === (this.defs.selectAction || 'selectRelated'));

            if (select) {
                select.data = {
                    link: this.link,
                    scope: 'Category',
                    boolFilterListCallback: 'getSelectBoolFilterList',
                    boolFilterDataCallback: 'getSelectBoolFilterData',
                    primaryFilterName: this.defs.selectPrimaryFilterName || null
                }
            }
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

        getSelectBoolFilterList() {
            return this.defs.selectBoolFilterList || null
        },

        actionUnlinkRelated: function (data) {
            let id = data.id;

            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                let model = this.collection.get(id);
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
            let id = data.id;

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages'),
                confirmText: this.translate('Remove')
            }, function () {
                let model = this.collection.get(id);
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

    })
);