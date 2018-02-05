Espo.define('pim:views/product-image/record/edit-small', 'views/record/edit-small',
    Dep => Dep.extend({
        save(callback) {
            if (this.model.get('type') === 'Files') {
                this.multipleSave(callback);
            } else {
                Dep.prototype.save.call(this, callback);
            }
        },

        multipleSave(callback) {
            let data = {};
            let model = this.model;
            let beforeSaveAttributes = this.model.getClonedAttributes();
            let imagesIds = this.model.get('imagesMultipleIds');
            let promises = [];

            if (this.validate()) {
                model.attributes = beforeSaveAttributes;
                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }

            this.beforeSave();

            this.trigger('before:save');
            model.trigger('before:save');

            data.alt = this.model.get('alt');
            data.assignedUserId = this.model.get('assignedUserId');
            data.isMain = this.model.get('isMain');
            data.productId = this.model.get('productId');
            data.state = this.model.get('state');
            data.teamsIds = this.model.get('teamsIds');
            data.type = "File";

            imagesIds.forEach((item) => {
                data.imageId = item;
                data.imageName = this.model.get('imagesMultipleNames')[item];
                promises.push(this.ajaxPostRequest(this.model.name, Espo.Utils.clone(data)));
            });

            Promise.all(promises)
                .then(response => {
                    this.afterSave();
                    if (self.isNew) {
                        self.isNew = false;
                    }
                    this.trigger('after:save');
                    this.model.trigger('after:save');

                    if (!callback) {
                        this.exit('save');
                    } else {
                        callback(this);
                    }
                });
        }
    })
);