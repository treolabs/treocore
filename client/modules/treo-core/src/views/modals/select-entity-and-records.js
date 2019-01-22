/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
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

Espo.define('treo-core:views/modals/select-entity-and-records', 'pim:views/modals/select-records',
    Dep => Dep.extend({

        template: 'treo-core:modals/select-entity-and-records',

        setup() {
            Dep.prototype.setup.call(this);

            this.buttonList.find(button => button.name === 'select').label = 'applyRelation';
            this.header = this.getLanguage().translate(this.options.type, 'massActions', 'Global');

            this.waitForView('selectedLink');
            this.createSelectedLinkView();

            this.listenTo(this.model, 'change:selectedLink', model => {
                this.reloadList(model.get('selectedLink'));
            });

            if (this.multiple) {
                let selectButton = this.buttonList.find(button => button.name === 'select');
                selectButton.onClick = dialog => {
                    if (this.validate()) {
                        this.notify('Not valid', 'error');
                        return;
                    }

                    let listView = this.getView('list');
                    if (listView.allResultIsChecked) {
                        let where = this.collection.where;
                        this.trigger('select', {
                            massRelate: true,
                            where: where
                        });
                    } else {
                        let list = listView.getSelected();
                        if (list.length) {
                            this.trigger('select', list);
                        }
                    }
                    dialog.close();
                };
            }

            this.listenTo(this, 'select', models => {
                if (this.validate()) {
                    this.notify('Not valid', 'error');
                    return;
                }

                let foreignIds = [];
                (models || []).forEach(model => foreignIds.push(model.id));
                let data = this.getDataForUpdateRelation(foreignIds, this.model);
                let url = `${this.model.get('mainEntity')}/${this.model.get('selectedLink')}/relation`;
                this.sendDataForUpdateRelation(url, data);
            });
        },

        getDataForUpdateRelation(foreignIds, viewModel) {
            return {
                ids: this.options.checkedList,
                foreignIds: foreignIds
            }
        },

        sendDataForUpdateRelation(url, data) {
            if (this.options.type === 'addRelation') {
                this.ajaxPostRequest(url, data).then(response => {
                    this.notify('Linked', 'success');
                });
            } else if (this.options.type === 'removeRelation') {
                data = JSON.stringify(data);
                this.ajaxRequest(url, 'DELETE', data).then(response => {
                    this.notify('Unlinked', 'success');
                });
            }
        },

        createSelectedLinkView() {
            let options = [];
            let translatedOptions = {};
            this.model.get('foreignEntities').forEach(entityDefs => {
                let link = entityDefs.link;
                options.push(link);
                let translation = this.translate(link, 'links', this.model.get('mainEntity'));
                if (entityDefs.customDefs) {
                    translation = this.translate(entityDefs.customDefs.link, 'links', this.model.get('mainEntity'));
                }
                translatedOptions[link] = translation;
            });

            this.createView('selectedLink', 'views/fields/enum', {
                model: this.model,
                el: `${this.options.el} .entity-container .field[data-name="selectedLink"]`,
                defs: {
                    name: 'selectedLink',
                    params: {
                        options: options,
                        translatedOptions: translatedOptions
                    }
                },
                mode: 'edit'
            }, view => {});
        },

        getEntityFromSelectedLink() {
            let selectedLink = this.model.get('selectedLink');
            let entityDefs = (this.model.get('foreignEntities') || []).find(item => item.link === selectedLink) || {};
            return entityDefs.customDefs ? entityDefs.customDefs.entity : entityDefs.entity;
        },

        reloadList(selectedLink) {
            if (!selectedLink) {
                return;
            }
            let entity = this.getEntityFromSelectedLink();
            this.scope = entity;
            this.collection.name = this.collection.urlRoot = this.collection.url = entity;
            let collectionDefs = (this.getMetadata().get(['entityDefs', entity, 'collection']) || {});
            this.collection.sortBy = collectionDefs.sortBy;
            this.collection.asc = collectionDefs.asc;
            this.getModelFactory().getSeed(entity, seed => this.collection.model = seed);
            this.loadSearch();
            this.loadList();
        },

        validate: function () {
            let notValid = false;
            let fields = this.getFieldViews();
            for (let i in fields) {
                if (fields[i].mode === 'edit') {
                    if (!fields[i].disabled && !fields[i].readOnly) {
                        notValid = fields[i].validate() || notValid;
                    }
                }
            }
            return notValid
        },

        getFieldViews() {
            return {};
        },

        close() {
            if (this.validate()) {
                return;
            }

            Dep.prototype.close.call(this);
        }
    })
);

