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

Espo.define('treo-crm:views/module-manager/list', 'views/list',
    Dep => Dep.extend({

        createButton: false,

        searchPanel: false,

        loadList() {
            this.getCollectionFactory().create('ModuleManager', collection => {
                collection.maxSize = 200;
                collection.url = 'ModuleManager/list';

                this.listenToOnce(collection, 'sync', () => {
                    this.createView('list', 'views/record/list', {
                        collection: collection,
                        el: `${this.options.el} .list-container`,
                        type: 'list',
                        searchManager: false,
                        selectable: false,
                        checkboxes: false,
                        massActionsDisabled: true,
                        checkAllResultDisabled: false,
                        buttonsDisabled: false,
                        paginationEnabled: false,
                        showCount: false,
                        showMore: false,
                        rowActionsDisabled: true
                    }, view => {
                        let rows = view.nestedViews || {};
                        for (let key in rows) {
                            let setEditMode;
                            if (rows[key].model.get('isActive')) {
                                setEditMode = collection.every(model => !model.get('isActive') || !(model.get('required') || []).includes(key));
                            } else {
                                setEditMode = (collection.get(key).get('required') || []).every(item => {
                                    let model = collection.get(item);
                                    return model && model.get('isActive');
                                });
                            }
                            if (setEditMode) {
                                rows[key].getView('isActive').setMode('edit');
                            }
                            view.listenTo(rows[key].model, `change:isActive`, model => {
                                this.notify('Saving...');
                                this.ajaxPutRequest(`ModuleManager/${model.get('id')}/updateActivation`)
                                .then(() => {
                                    this.notify(this.translate('successAndReload', 'messages', 'Global').replace('{value}', 2), 'success');
                                    for (let k in rows) {
                                        rows[k].getView('isActive').setMode('list');
                                    }
                                    this.getView('list').reRender();
                                    setTimeout(function () {
                                        window.location.reload(true);
                                    }, 2000);
                                });
                            });
                        }
                        this.listenTo(view, 'after:render', () => {
                            this.$el.find('.list-container td.cell ').css({
                                'white-space': 'normal',
                                'text-overflow': 'ellipsis',
                                'overflow': 'hidden'
                            })
                        });
                        view.render();
                    });
                });

                collection.fetch();
            });
        },

        getHeader() {
            return '<a href="#Admin">' + this.translate('Administration') + "</a> » " + this.getLanguage().translate('moduleManager', 'labels', 'Admin');
        },

        updatePageTitle() {
            this.setPageTitle(this.getLanguage().translate('moduleManager', 'labels', 'Admin'));
        }

    })
);