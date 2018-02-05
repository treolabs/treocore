Espo.define('treo-crm:views/record/base', 'class-replace!treo-crm:views/record/base', function (Dep) {

    return Dep.extend({

        populateDefaults: function () {
            Dep.prototype.populateDefaults.call(this);

            this.model.populateDefaults();
            let defaultHash = {};

            if (!this.getUser().get('portalId')) {
                if (this.model.hasField('ownerUser')) {
                    let fillOwnerUser = true;
                    if (this.getPreferences().get('doNotFillOwnerUserIfNotRequired')) {
                        fillOwnerUser = false;
                        if (this.model.getFieldParam('ownerUser', 'required')) {
                            fillOwnerUser = true;
                        }
                    }
                    if (fillOwnerUser) {
                        defaultHash['ownerUserId'] = this.getUser().id;
                        defaultHash['ownerUserName'] = this.getUser().get('name');
                    }
                }
            }

            for (let attr in defaultHash) {
                if (this.model.has(attr)) {
                    delete defaultHash[attr];
                }
            }
            this.model.set(defaultHash, {silent: true});
        },
    });
});