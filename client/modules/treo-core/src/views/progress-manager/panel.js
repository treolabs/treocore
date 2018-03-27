/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
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

Espo.define('treo-core:views/progress-manager/panel', 'view', function (Dep) {

    return Dep.extend({

        template: 'treo-core:progress-manager/panel',

        setup: function () {
            this.wait(true);
            this.getCollectionFactory().create('ProgressManager', function (collection) {
                this.collection = collection;
                this.collection.maxSize = 200;
                this.collection.url = 'ProgressManager/popupData';

                this.listenTo(this.collection, 'reloadList', () => {
                    this.reloadList();
                });

                this.wait(false);
            }, this);
        },

        afterRender: function () {
            this.listenToOnce(this.collection, 'sync', function () {
                var viewName = 'views/record/list';
                this.createView('list', viewName, {
                    el: this.options.el + ' .list-container',
                    collection: this.collection,
                    rowActionsDisabled: true,
                    checkboxes: false,
                    headerDisabled: true,
                    listLayout: [
                        {
                            name: 'name',
                            notSortable: true,
                        },
                        {
                            name: 'progress',
                            view: 'treo-core:views/progress-manager/fields/progress',
                            width: '90px'
                        },
                        {
                            name: 'status',
                            view: 'treo-core:views/progress-manager/fields/status',
                            width: '90px'
                        },
                        {
                            name: 'actions',
                            view: 'treo-core:views/progress-manager/fields/actions',
                            width: '240px'
                        }
                    ]
                }, function (view) {
                    view.render();
                });
            }, this);
            this.reloadList();
        },

        reloadList() {
            this.collection.fetch();
        }

    });

});
