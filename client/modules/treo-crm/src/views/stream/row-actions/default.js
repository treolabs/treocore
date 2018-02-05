Espo.define('treo-crm:views/stream/row-actions/default', 'class-replace!treo-crm:views/stream/row-actions/default', function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var list = Dep.prototype.getActionList.call(this);

            if (this.options.acl.edit && this.model.get('type') === 'Update') {
                list.push({
                    action: 'quickRestore',
                    label: 'Restore',
                    data: {
                        id: this.model.id
                    }
                });
            }

            return list;
        }

    });
});

