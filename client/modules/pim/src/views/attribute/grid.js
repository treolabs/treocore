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

Espo.define('pim:views/attribute/grid', 'views/base',
    Dep => Dep.extend({

        template: 'pim:attribute/grid',

        gridLayout: null,

        mode: 'detail',

        setup() {
            Dep.prototype.setup.call(this);

            this.gridLayout = this.options.gridLayout;

            this.events = _.extend({
                'click .inline-remove-link': (e) => this.actionRemoveAttribute($(e.currentTarget).data('name')),
                'click .inline-edit-link': (e) => {
                    this.initInlineEditAttribute($(e.currentTarget).data('name'));
                }
            }, this.events || {});
        },

        data() {
            return {gridLayout: this.gridLayout} || [];
        },

        afterRender() {
            this.buildGrid();

            Dep.prototype.afterRender.call(this);
        },

        buildGrid() {
            if (this.nestedViews) {
                for (let child in this.nestedViews) {
                    this.clearView(child);
                }
            }

            let mode = this.getDetailViewMode();

            this.gridLayout.forEach(panel => {
                panel.rows.forEach(row => {
                    row.forEach(cell => {
                        let fieldDefs = cell.defs;
                        let viewName = fieldDefs.type !== 'bool' ? this.getFieldManager().getViewName(fieldDefs.type) : 'pim:views/fields/bool-required';
                        this.createView(cell.name, viewName, {
                            mode: mode,
                            inlineEditDisabled: true,
                            model: this.model,
                            el: `${this.options.el} .field[data-name="${cell.name}"]`,
                            customLabel: cell.label,
                            defs: {
                                name: cell.name,
                            },
                            params: {
                                required: fieldDefs.required
                            }
                        }, (view) => {
                            view.listenToOnce(view, 'after:render', this.initInlineEdit, view);
                            view.listenTo(view, 'edit', function () {
                                let fields = this.getParentView().nestedViews;
                                for (let field in fields) {
                                    if (fields[field] && fields[field].mode === 'edit' && fields[field] !== view) {
                                        this.getParentView().inlineCancelEdit(fields[field]);
                                    }
                                }
                            }, view);
                            view.render();
                        });
                    }, this);
                }, this);
            }, this)
        },

        actionRemoveAttribute(id) {
            if (!id) {
                return;
            }
            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                this.notify('Unlinking...');
                let productId = this.getParentView().model.id;
                $.ajax({
                    url: `Product/${productId}/attributes`,
                    data: JSON.stringify({id: id}),
                    type: 'DELETE',
                    contentType: 'application/json',
                    success: function () {
                        this.getParentView().updateGrid();
                        this.notify('Unlinked', 'success');
                        this.getParentView().model.trigger('after:unrelate', 'attributes');
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        },

        initInlineEditAttribute(id) {
            if (!id) {
                return;
            }
            let view = this.getView(id);
            view.trigger('edit');
            view.setMode('edit');
            this.initialAttributes = this.model.getClonedAttributes();
            this.hideInlineLinks(view);
            let cell = view.getCellElement();
            let saveLink = cell.find('.inline-save-link');
            let cancelLink = cell.find('.inline-cancel-link');
            view.once('after:render', function () {
                saveLink.click(function() {
                    this.inlineEditSave(view);
                }.bind(this));
                cancelLink.click(function () {
                    this.inlineCancelEdit(view);
                }.bind(this));
            }, this);
            view.reRender();
        },

        initInlineEdit: function () {
            let cell = this.getCellElement();
            let editLink = cell.find('.inline-edit-link.edit-attribute');
            let lastChangesLink = cell.find('.inline-edit-link.last-changes');
            let removeLink = cell.find('.inline-remove-link');

            if (cell.size() === 0) {
                return;
            }

            cell.on('mouseenter', function (e) {
                e.stopPropagation();
                if (this.disabled || this.readOnly) {
                    return;
                }
                if (this.mode === 'detail') {
                    removeLink.removeClass('hidden');
                    lastChangesLink.removeClass('hidden');
                    editLink.removeClass('hidden');
                }
            }.bind(this)).on('mouseleave', function (e) {
                e.stopPropagation();
                if (this.mode === 'detail') {
                    removeLink.addClass('hidden');
                    lastChangesLink.addClass('hidden');
                    editLink.addClass('hidden');
                }
            }.bind(this));
        },

        inlineEditSave: function (view) {
            let data = view.fetch();
            let prev = this.initialAttributes;

            view.model.set(data, {silent: true});
            let dataToSave = [];
            let inputLanguageList = (this.getConfig().get('inputLanguageList') || [])
                .map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''));
            let item = {
                attributeId: view.name,
                value: data[view.name],
            };
            inputLanguageList.forEach(lang => item[`value${lang}`] = data[`${view.name}${lang}`] || null);
            dataToSave.push(item);

            data = this.model.attributes;

            let attrs = false;
            for (let attr in data) {
                if (_.isEqual(prev[attr], data[attr])) {
                    continue;
                }
                (attrs || (attrs = {}))[attr] = data[attr];
            }

            if (!attrs) {
                this.inlineCancelEdit(view);
                return;
            }

            if (view.validate()) {
                view.notify('Not valid', 'error');
                view.model.set(prev, {silent: true});
                return;
            }

            this.notify('Saving...');
            this.ajaxPutRequest(`Markets/Product/${this.getParentView().model.id}/attributes`, dataToSave)
                .then(response => {
                    this.getParentView().updateGrid();
                    this.getParentView().model.trigger('after:attributesSave');
                    this.notify('Saved', 'success');
                });
            this.inlineCancelEdit(view, true);
        },

        inlineCancelEdit(view, dontReset) {
            view.setMode('detail');
            view.once('after:render', function () {
                this.showInlineLinks(view);
            }, this);
            if (!dontReset) {
                view.model.set(this.initialAttributes);
            }
            view.reRender();
        },

        hideInlineLinks(view) {
            let cell = view.getCellElement();
            cell.find('.inline-edit-link.edit-attribute').addClass('hidden');
            cell.find('.inline-edit-link.last-changes').addClass('hidden');
            cell.find('.inline-remove-link').addClass('hidden');
            cell.find('.inline-save-link').removeClass('hidden');
            cell.find('.inline-cancel-link').removeClass('hidden');
        },

        showInlineLinks(view) {
            let cell = view.getCellElement();
            cell.find('.inline-edit-link.edit-attribute').removeClass('hidden');
            cell.find('.inline-edit-link.last-changes').removeClass('hidden');
            cell.find('.inline-remove-link').removeClass('hidden');
            cell.find('.inline-save-link').addClass('hidden');
            cell.find('.inline-cancel-link').addClass('hidden');
        },

        getDetailViewMode() {
            let mode = 'detail';
            let parentView = this.getParentView();
            if (parentView) {
                let detailView = this.getParentView().getDetailView();
                if (detailView) {
                    mode = detailView.mode;
                }
            }
            return mode;
        }

    })
);
