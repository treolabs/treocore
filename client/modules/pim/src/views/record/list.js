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

Espo.define('pim:views/record/list', 'views/record/list',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            (this.getMetadata().get(['clientDefs', this.scope, 'disabledMassActions']) || []).forEach(item => this.removeMassAction(item));
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.options.dragableListRows) {
                this.initDraggableList();
            }
        },

        initDraggableList() {
            this.$el.find(this.listContainerEl).sortable({
                delay: 150,
                update: function () {
                    this.saveListItemOrder();
                }.bind(this)
            });
        },

        saveListItemOrder() {
            let saveUrl = this.getListRowsOrderSaveUrl();
            if (saveUrl) {
                this.ajaxPutRequest(saveUrl, {ids: this.getIdsFromDom()})
                    .then(response => {
                        let statusMsg = 'Error occurred';
                        let type = 'error';
                        if (response) {
                            statusMsg = 'Saved';
                            type = 'success';
                        }
                        this.notify(statusMsg, type, 3000);
                    });
            }
        },

        getListRowsOrderSaveUrl() {
            return this.options.listRowsOrderSaveUrl;
        },

        getIdsFromDom() {
            return $.map(this.$el.find(`${this.listContainerEl} tr`), function (item) {
                return $(item).data('id');
            });
        }

    })
);

