Espo.define('pim:views/product/record/panels/channel-product-attribute-values', 'views/record/panels/bottom',
    Dep => Dep.extend({

        channels: [],

        template: "pim:product/record/panels/channel-product-attribute-values",

        events: {
            'click [data-action="selectChannelAttributes"]': function(e) {
                this.selectChannelAttributes($(e.currentTarget).data('channel'));
            },
            'click [data-action="addChannelAttribute"]': function(e) {
                this.addChannelAttribute($(e.currentTarget).data('channel'));
            }
        },

        setup() {
            Dep.prototype.setup.call(this);
            this.getLanguage().data['channelAttributeValueCollection'] = {
                fields: {
                    attributeName: "Attribute Name",
                    attributeValue: "Attribute Value"
                }
            };

            this.once('after:render', () => {
                this.actionRefresh();
            });

            this.listenTo(this.model, 'after:relate after:unrelate', data => {
                if (data === 'attributes' || data === 'channelProducts') {
                    this.actionRefresh();
                }
            }, this);

            this.listenTo(this.model, 'after:save', () => {
                this.actionRefresh();
            });
        },

        buildChannels() {
            let listLayout = [
                {
                    name: 'attributeName',
                    notSortable: true
                },
                {
                    name: 'attributeValue',
                    notSortable: true
                }
            ];

            this.channels = [];

            this.ajaxGetRequest(`Markets/Product/${this.model.id}/channelAttributes`)
                .then(data => {
                    this.clearNestedViews();
                    this.$el.html('');

                    if (!data || !data.length) {
                        this.showEmptyData();
                    }

                    let inputLanguageList = this.getConfig().get('inputLanguageList') || [];
                    let inputLanguageListKeys = false;
                    if (Array.isArray(inputLanguageList) && inputLanguageList.length) {
                        inputLanguageListKeys = inputLanguageList.map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''));
                    }

                    this.channels = data;

                    data.forEach(channel => {
                        this.getCollectionFactory().create('channelAttributeValueCollection', collection => {
                            collection.total = channel.attributes.length;

                            channel.attributes.forEach(attribute => {
                                this.getModelFactory().create('ChannelProductAttributeValue', model => {
                                    let defs = {
                                        fields: {
                                            attributeName: {
                                                type: 'varchar',
                                                required: true,
                                                readonly: true
                                            },
                                            attributeValue: {
                                                type: attribute.attributeType,
                                                required: false,
                                                options: attribute.attributeTypeValue
                                            },
                                            attributeIsMultiChannel: {
                                                type: 'bool',
                                                required: false,
                                                readonly: true
                                            }
                                        }
                                    };
                                    let data = {
                                        attributeName: attribute.attributeName,
                                        attributeValue: attribute.attributeValue,
                                        attributeIsMultiChannel: attribute.attributeIsMultiChannel
                                    };

                                    if (inputLanguageListKeys) {
                                        if (['varcharMultiLang', 'textMultiLang', 'enumMultiLang', 'multiEnumMultiLang', 'arrayMultiLang'].indexOf(attribute.attributeType) > -1) {
                                            inputLanguageListKeys.forEach(item => {
                                                data[`attributeValue${item}`] = attribute[`attributeValue${item}`];
                                                defs.fields.attributeValue[`options${item}`] = attribute[`attributeTypeValue${item}`];
                                            });
                                        }
                                    }
                                    model.setDefs(defs);
                                    model.set(data);
                                    model.id = attribute.channelProductAttributeValueId;
                                    collection.add(model);
                                    collection._byId[model.id] = model;
                                });
                            });

                            $(this.options.el).append(`<div class="list-container" data-id="${channel.channelId}"></div>`);

                            this.createView(`list-${channel.channelId}`, 'pim:views/channel-product-attribute-value/record/grouped-list', {
                                collection: collection,
                                el: `${this.options.el} .list-container[data-id="${channel.channelId}"]`,
                                type: 'list',
                                searchManager: this.searchManager,
                                listLayout: listLayout,
                                collectionLabel: channel.channelName,
                                channelId: channel.channelId
                            }, function (view) {
                                view.render();
                            }, this);
                        });
                    }, this);
                });
        },

        clearNestedViews() {
            for (let key in this.nestedViews) {
                this.clearView(key);
            }
        },

        showEmptyData() {
            this.$el.html(this.translate('No Data'));
        },

        selectChannelAttributes(channelId) {
            let viewName = 'pim:views/product/modals/select-channel-attributes';

            this.notify('Loading...');
            this.createView('selectChannelAttributes', viewName, {
                scope: 'Attribute',
                multiple: true,
                createButton: false,
                channelId: channelId,
                productId: this.model.id,
                channels: this.channels
            }, function (dialog) {
                dialog.render();
                this.notify(false);
            }.bind(this));
        },

        addChannelAttribute(channelId) {
            let viewName = 'pim:views/product/modals/add-channel-attribute';
            this.notify('Loading...');
            this.createView('addChannelAttribute', viewName, {
                scope: 'ChannelProductAttributeValue',
                channelId: channelId,
                channels: this.channels,
                productId: this.model.id
            }, function (dialog) {
                dialog.render();
                this.notify(false);
            }.bind(this));
        },

        actionRefresh: function () {
            this.buildChannels();
        },

    })
);