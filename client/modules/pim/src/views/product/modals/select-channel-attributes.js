Espo.define('pim:views/product/modals/select-channel-attributes', 'views/modals/select-records', function (Dep) {

    return Dep.extend({

        multiple: false,

        header: false,

        template: 'modals/select-records',

        createButton: false,

        searchPanel: false,

        scope: null,

        inputLanguageListKeys: [],

        setup: function () {
            this.filters = this.options.filters || {};
            this.boolFilterList = this.options.boolFilterList || [];
            this.primaryFilterName = this.options.primaryFilterName || null;

            if ('multiple' in this.options) {
                this.multiple = this.options.multiple;
            }

            if ('createButton' in this.options) {
                this.createButton = this.options.createButton;
            }

            this.massRelateEnabled = this.options.massRelateEnabled;

            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            if (this.multiple) {
                this.buttonList.unshift({
                    name: 'select',
                    style: 'primary',
                    label: 'Select',
                    onClick: function (dialog) {
                        var listView = this.getView('list');
                        var list = listView.getSelected();
                        if (list.length) {
                            this.saveChannelAttributes(list);
                        }
                        dialog.close();
                    }.bind(this),
                });
            }

            this.scope = this.entityType = this.options.scope || this.scope;

            if (this.noCreateScopeList.indexOf(this.scope) !== -1) {
                this.createButton = false;
            }

            this.header = this.getLanguage().translate(this.scope, 'scopeNamesPlural');
        },

        afterRender() {
            this.ajaxGetRequest(`Markets/Channel/${this.options.channelId}/Product/${this.options.productId}/attributes`)
                .then(response => {
                    this.getCollectionFactory().create(this.scope, collection => {
                        this.collection = collection;
                        collection.total = 0;

                        let channel = this.options.channels.find(item => item.channelId === this.options.channelId);

                        let inputLanguageList = this.getConfig().get('inputLanguageList');
                        if (Array.isArray(inputLanguageList) && inputLanguageList.length) {
                            this.inputLanguageListKeys = inputLanguageList.map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), 'value'));
                        }

                        response.forEach(attribute => {
                            if (!channel.attributes.find(item => item.attributeId === attribute.attributeId)) {
                                this.getModelFactory().create(this.scope, model => {
                                    let data ={
                                        name: attribute.name,
                                        type: this.translate(attribute.type, this.scope, 'fields'),
                                        value: attribute.value
                                    };

                                    if (this.inputLanguageListKeys) {
                                        this.inputLanguageListKeys.forEach(item => {
                                            data[item] = attribute[item];
                                        });
                                    }

                                    model.setDefs({
                                        fields: {
                                            name: {
                                                type: 'varchar'
                                            },
                                            type: {
                                                type: 'varchar'
                                            },
                                            value: {
                                                type: 'textMultiLang'
                                            }
                                        }
                                    });
                                    model.id = attribute.attributeId
                                    model.set(data);
                                    collection.add(model);
                                    collection._byId[model.id] = model;
                                    collection.total++;
                                });
                            }
                        });

                        this.loadList();
                    }, this);
                });
        },

        loadList() {
            var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.listSelect') ||
                this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') ||
                'views/record/list';
            this.createView('list', viewName, {
                collection: this.collection,
                el: this.containerSelector + ' .list-container',
                selectable: true,
                checkboxes: this.multiple,
                massActionsDisabled: true,
                rowActionsView: false,
                layoutName: 'listSmall',
                searchManager: this.searchManager,
                checkAllResultDisabled: !this.massRelateEnabled,
                buttonsDisabled: true,
                displayTotalCount: false,
                listLayout: [{name: 'name', link: true, notSortable: true}, {name: 'type', notSortable: true}]
            }, function (list) {
                list.once('select', function (models) {
                    this.saveChannelAttributes(models);
                    this.close();
                }.bind(this));
                list.render();
            }.bind(this));
        },

        saveChannelAttributes(models) {
            if (models && models.length) {
                Promise.all(models.map(model => {
                    let data = {
                        channelId: this.options.channelId,
                        attributeId: model.id,
                        productId: this.getParentView().model.id,
                        value: model.get('value'),
                    };

                    if (this.inputLanguageListKeys) {
                        this.inputLanguageListKeys.forEach(item => {
                            data[item] = model.get(item);
                        });
                    }

                    return this.ajaxPostRequest('ChannelProductAttributeValue', data);
                })).then(() => this.getParentView().actionRefresh());
            }
        },

    });
});

