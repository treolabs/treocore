/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

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