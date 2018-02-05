Espo.define('treo-crm:views/stream/record/list', 'class-replace!treo-crm:views/stream/record/list', function (Dep) {

    return Dep.extend({

        actionQuickRestore: function (data) {
            this.confirm({
                message: this.translate('restoreRecordConfirmation', 'messages'),
                confirmText: this.translate('Restore')
            }, () => {
                this.ajaxPostRequest(`Revisions/${this.model.name}/${data.id}/restore`).then((response) => {
                    if (response) {
                        this.notify('Restored', 'success');
                        this.model.fetch();
                    } else {
                        this.notify(this.translate('restoreRecordUnconfirmed', 'messages'), 'warning');
                    }
                });
            });
        }
    });

});
