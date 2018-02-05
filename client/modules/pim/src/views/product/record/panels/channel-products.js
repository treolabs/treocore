Espo.define('pim:views/product/record/panels/channel-products', 'views/record/panels/relationship',
    Dep => Dep.extend({

        linkScope: 'ChannelProduct',

        channels: [],

        boolFilterData: {
            notLinkedWithProduct() {
                return this.model.id;
            }
        },

        setup() {
            Dep.prototype.__proto__.setup.call(this);

            this.link = this.panelName;

            if (!this.scope && !(this.link in this.model.defs.links)) {
                throw new Error('Link \'' + this.link + '\' is not defined in model \'' + this.model.name + '\'');
            }
            this.title = this.translate(this.link, 'links', this.model.name);
            this.scope = this.scope || this.model.defs.links[this.link].entity;

            let url = this.url || this.model.name + '/' + this.model.id + '/' + this.link;

            if (!this.readOlny && !this.defs.readOnly) {
                if (!('create' in this.defs)) {
                    this.defs.create = true;
                }
                if (!('select' in this.defs)) {
                    this.defs.select = true;
                }
            }

            this.filterList = this.defs.filterList || this.filterList || null;

            if (this.filterList && this.filterList.length) {
                this.filter = this.getStoredFilter();
            }

            if (this.defs.create) {
                if (this.getAcl().check(this.scope, 'create') && !~['User', 'Team'].indexOf()) {
                    this.buttonList.push({
                        title: 'Create',
                        action: this.defs.createAction || 'createRelated',
                        link: this.link,
                        acl: 'create',
                        aclScope: this.scope,
                        html: '<span class="glyphicon glyphicon-plus"></span>',
                        data: {
                            link: this.link,
                            scope: 'Channel',
                            afterSaveCallback: 'actionCreateLink'
                        }
                    });
                }
            }

            if (this.defs.select) {
                this.actionList.unshift({
                    label: 'Select',
                    action: this.defs.selectAction || 'selectRelated',
                    data: {
                        link: this.link,
                        scope: 'Channel',
                        afterSelectCallback: 'actionCreateLink',
                        boolFilterListCallback: 'getSelectBoolFilterList',
                        boolFilterDataCallback: 'getSelectBoolFilterData',
                        primaryFilterName: this.defs.selectPrimaryFilterName || null
                    }
                });
            }

            this.once('after:render', () => {
                this.setupList();
            });

            this.setupFilterActions();
        },

        setupList() {
            let layoutName = 'listSmall';
            let listLayout = null;
            let layout = this.defs.layout || null;
            if (layout) {
                if (typeof layout == 'string') {
                    layoutName = layout;
                } else {
                    layoutName = 'listRelationshipCustom';
                    listLayout = layout;
                }
            }

            this.channels = [];

            let promise = this.ajaxGetRequest(`Markets/Product/${this.model.id}/channels`);

            promise.then(response => {
                this.clearNestedViews();
                if (!response.length) {
                    this.showEmptyData();
                    return;
                }

                let formedResponse = response.map(item => {
                    return {
                        id: item.channelId,
                        name: item.channelName,
                        isActive: item.isActive,
                        categories: item.categories,
                        channelProductId: item.channelProductId,
                        isEditable: item.isEditable
                    };
                });

                this.channels = formedResponse;

                this.getCollectionFactory().create('Channel', collection => {
                    collection.total = formedResponse.length;

                    formedResponse.forEach(product => {
                        this.getModelFactory().create('Channel', model => {
                            model.set(product);
                            model.id = product.id;
                            collection.add(model);
                            collection._byId[model.id] = model;
                        });
                    }, this);

                    let viewName = this.defs.recordListView || this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') || 'views/record/list';

                    this.createView('list', viewName, {
                        collection: collection,
                        el: `${this.options.el} .list-container`,
                        type: 'list',
                        searchManager: this.searchManager,
                        selectable: false,
                        checkboxes: false,
                        massActionsDisabled: true,
                        checkAllResultDisabled: true,
                        buttonsDisabled: true,
                        paginationEnabled: false,
                        showCount: false,
                        showMore: false,
                        rowActionsView: this.defs.readOnly ? false : (this.defs.rowActionsView || this.rowActionsView),
                        layoutName: layoutName,
                        istLayout: listLayout,
                    }, function (view) {
                        view.render();
                    }, this);
                });
            });
        },

        actionUnlinkRelated(data) {
            let channel = this.channels.find(item => item.id === data.id);

            if (!channel) {
                return;
            }

            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                this.notify('Unlinking...');
                $.ajax({
                    url: `${this.linkScope}/${channel.channelProductId}`,
                    type: 'DELETE',
                    contentType: 'application/json',
                    success: function () {
                        this.notify('Unlinked', 'success');
                        this.setupList();
                        this.model.trigger('after:unrelate', this.link);
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        },

        actionCreateLink(models) {
            let items = Array.isArray(models) ? models : [models];
            Promise.all(items.map(item => this.ajaxPostRequest(this.linkScope, {
                productId: this.model.id,
                channelId: item.id
            }))).then(() => {
                this.notify('Linked', 'success');
                this.setupList();
                this.model.trigger('after:relate', this.link);
            });
        },

        getSelectBoolFilterList() {
            return this.defs.selectBoolFilterList || null
        },

        getSelectBoolFilterData(boolFilterList) {
            let data = {};
            if (Array.isArray(boolFilterList)) {
                boolFilterList.forEach(item => {
                    if (this.boolFilterData && typeof this.boolFilterData[item] === 'function') {
                        data[item] = this.boolFilterData[item].call(this);
                    }
                });
            }
            return data;
        },

        actionRefresh: function () {
            this.setupList();
        },

        showEmptyData() {
            this.$el.find('.list-container').html(this.translate('No Data'));
        },

        clearNestedViews() {
            for (let key in this.nestedViews) {
                this.clearView(key);
            }
        },
    })
);