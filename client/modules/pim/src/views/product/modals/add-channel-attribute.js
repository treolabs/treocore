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

Espo.define('pim:views/product/modals/add-channel-attribute', 'views/modals/edit',
    Dep => Dep.extend({

        inputLanguageListKeys: false,

        fullFormDisabled: true,

        sideDisabled: true,

        bottomDisabled: true,

        template: 'pim:product/modals/add-channel-attribute',

        setup() {
            this.buttonList = [];

            if ('saveDisabled' in this.options) {
                this.saveDisabled = this.options.saveDisabled;
            }

            if (!this.saveDisabled) {
                this.buttonList.push({
                    name: 'save',
                    label: 'Save',
                    style: 'primary',
                });
            }

            this.fullFormDisabled = this.options.fullFormDisabled || this.fullFormDisabled;

            this.layoutName = this.options.layoutName || this.layoutName;

            if (!this.fullFormDisabled) {
                this.buttonList.push({
                    name: 'fullForm',
                    label: 'Full Form'
                });
            }

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel'
            });

            this.scope = this.scope || this.options.scope;
            this.id = this.options.id;

            if (!this.id) {
                this.header = this.getLanguage().translate('Create ' + this.scope, 'labels', this.scope);
            } else {
                this.header = this.getLanguage().translate('Edit');
                this.header += ': ' + this.getLanguage().translate(this.scope, 'scopeNames');
            }

            if (!this.fullFormDisabled) {
                if (!this.id) {
                    this.header = '<a href="#' + this.scope + '/create" class="action" title="'+this.translate('Full Form')+'" data-action="fullForm">' + this.header + '</a>';
                } else {
                    this.header = '<a href="#' + this.scope + '/edit/' + this.id+'" class="action" title="'+this.translate('Full Form')+'" data-action="fullForm">' + this.header + '</a>';
                }
            }

            this.sourceModel = this.model;

            this.waitForView('edit');

            this.getModelFactory().create('channelProdcutAttributeGrid', function (model) {
                this.ajaxGetRequest(`Markets/Channel/${this.options.channelId}/Product/${this.options.productId}/attributes`)
                    .then(response => {
                        let channel = this.options.channels.find(item => item.channelId === this.options.channelId);
                        let options = [];
                        let translateOptions = {};

                        let inputLanguageList = this.getConfig().get('inputLanguageList');
                        if (Array.isArray(inputLanguageList) && inputLanguageList.length) {
                            this.inputLanguageListKeys = inputLanguageList.map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''));
                        }

                        response.forEach(attribute => {
                            if (attribute.attributeId && !channel.attributes.find(item => item.attributeId === attribute.attributeId)) {
                                options.push(attribute.attributeId);
                                translateOptions[attribute.attributeId] = attribute.name;
                            }
                        });
                        if (options.length) {
                            let first = response.find(item => item.attributeId === options[0]);
                            this.getLanguage().data['channelProdcutAttributeGrid'] = {
                                fields: {
                                    attributeId: this.translate('Attribute', 'scopeNames'),
                                    value: this.translate('value', 'fields', 'ChannelProductAttributeValue')
                                },
                                options: {attributeId: translateOptions}
                            };

                            model.defs.fields = {
                                attributeId: {
                                    type: 'enum',
                                    options: options,
                                    required: true
                                },
                                value: {
                                    type: first.type,
                                    options: first.typeValue,
                                }
                            };

                            let data = {
                                attributeId: first.attributeId,
                                value: first.value
                            };

                            if (this.inputLanguageListKeys) {
                                this.inputLanguageListKeys.forEach(item => {
                                    data[`value${item}`] = first[`value${item}`];
                                    model.defs.fields.value[`options${item}`] = first[`typeValue${item}`];
                                });
                            }

                            model.set(data);

                            this.createRecordView(model);

                            this.listenTo(model, 'change:attributeId', model => {
                                if (model.changed.attributeId) {
                                    let current = response.find(item => item.attributeId === model.get('attributeId'));

                                    model.defs.fields.value = {
                                        type: current.type,
                                        options: current.typeValue,
                                    };

                                    let data = {value: current.value};
                                    if (this.inputLanguageListKeys) {
                                        this.inputLanguageListKeys.forEach(item => {
                                            data[`value${item}`] = current[`value${item}`];
                                            model.defs.fields.value[`options${item}`] = current[`typeValue${item}`];
                                        });
                                    }
                                    model.set(data);

                                    this.getView('edit').attributes = {};

                                    this.createRecordView(model, view => view.render());
                                }
                            }, this);
                        } else {
                            this.createEmptyDataView();
                        }
                    });
            }.bind(this));
        },

        createRecordView(model, callback) {
            let detailLayout = [
                {
                    "label": "",
                    "rows": [
                        [
                            {
                                "name": "attributeId",
                            },
                            {
                                "name": "value",
                            }
                        ]
                    ]
                }
            ];
            let viewName =
                this.editViewName ||
                this.editView ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'editSmall']) ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'editQuick']) ||
                'views/record/edit-small';
            let options = {
                model: model,
                el: this.containerSelector + ' .edit-container',
                type: 'editSmall',
                layoutName: this.layoutName || 'detailSmall',
                detailLayout: detailLayout,
                columnCount: this.columnCount,
                buttonsPosition: false,
                sideDisabled: this.sideDisabled,
                bottomDisabled: this.bottomDisabled,
                isWide: true,
                exit: function () {}
            };
            this.createView('edit', viewName, options, callback);
        },

        createEmptyDataView() {
            this.createView('edit', 'views/base', {
                template: 'pim:product/modals/empty-data'
            });
        },

        actionSave: function () {
            var editView = this.getView('edit');

            let data = editView.fetch();
            data['productId'] = this.getParentView().model.id;
            data['channelId'] = this.options.channelId;

            this.ajaxPostRequest('ChannelProductAttributeValue', data)
                .then(function (response) {
                    this.dialog.close();
                    this.getParentView().actionRefresh();
                });
        },

    })
);

