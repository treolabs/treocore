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

Espo.define('pim:views/channel-product-attribute-value/record/grouped-list', 'views/record/list',
    Dep => Dep.extend({

        selectable: false,

        checkboxes: false,

        massActionsDisabled: true,

        checkAllResultDisabled: true,

        buttonsDisabled: true,

        paginationEnabled: false,

        showCount: false,

        rowHasOwnLayout: true,

        showMore: false,

        template: 'pim:channel-product-attribute-value/record/grouped-list',

        rowActionsView: 'pim:views/channel-product-attribute-value/record/row-actions/multi-channel-not-remove',

        data() {
            let data = Dep.prototype.data.call(this);
            data['collectionLabel'] = this.options.collectionLabel;
            data['channelId'] = this.options.channelId;
            return data;
        },

        getInternalLayoutForModel: function (callback, model) {
            this._internalLayout = this._convertLayout(this.listLayout);
            callback(this._convertLayout(this.listLayout, model));
        },

        actionQuickEdit: function (data) {
            data = data || {}
            var id = data.id;
            if (!id) return;

            var model = null;
            if (this.collection) {
                model = this.collection.get(id);
            }
            if (!data.scope && !model) {
                return;
            }

            var scope = data.scope || model.name || this.scope;

            var viewName = 'pim:views/channel-product-attribute-value/modals/grouped-edit';

            this.notify('Loading...');
            this.createView('modal', viewName, {
                scope: scope,
                id: id,
                model: model,
            }, function (view) {
                view.once('after:render', function () {
                    Espo.Ui.notify(false);
                });

                view.render();

                this.listenToOnce(view, 'remove', function () {
                    this.clearView('modal');
                }, this);

            }, this);
        },

        actionQuickRemove: function (data) {
            data = data || {}
            var id = data.id;
            if (!id) return;

            var model = this.collection.get(id);
            if (!this.getAcl().checkModel(model, 'delete')) {
                this.notify('Access denied', 'error');
                return false;
            }

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages'),
                confirmText: this.translate('Remove')
            }, function () {
                this.collection.trigger('model-removing', id);
                this.collection.remove(model);
                this.notify('Removing...');
                model.destroy({
                    success: function () {
                        this.notify('Removed', 'success');
                        this.removeRecordFromList(id);
                        this.getParentView().actionRefresh();
                    }.bind(this),
                    error: function () {
                        this.notify('Error occured', 'error');
                    }.bind(this)
                });
            }, this);
        },

    })
);

