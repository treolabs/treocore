Espo.define('pim:views/channel/record/panels/channel-products', 'views/record/panels/relationship',
    Dep => Dep.extend({

        linkScope: 'ChannelProduct',

        products: [],

        boolFilterData: {
            notLinkedWithChannel() {
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
                            scope: 'Product',
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
                        scope: 'Product',
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

            this.listenTo(this.model, 'after:relate after:unrelate', function (data) {
                if (data === 'categories') {
                    this.setupList();
                }
            }, this);
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

            this.products = [];

            let promise = this.ajaxGetRequest(`Markets/Channel/${this.model.id}/products`);

            promise.then(response => {
                this.clearNestedViews();
                if (!response.length) {
                    this.showEmptyData();
                    return;
                }

                let formedResponse = response.map(item => {
                    return {
                        id: item.productId,
                        name: item.productName,
                        isActive: item.isActive,
                        categories: item.categories,
                        channelProductId: item.channelProductId,
                        isEditable: item.isEditable
                    };
                });

                this.products = formedResponse;

                this.getCollectionFactory().create('Product', collection => {
                    collection.total = formedResponse.length;

                    formedResponse.forEach(product => {
                        this.getModelFactory().create('Product', model => {
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
                        listLayout: listLayout,
                    }, function (view) {
                        view.events = Espo.Utils.cloneDeep(view.events);
                        delete view.events['click a.link'];
                        view.render();
                    }, this);
                });
            });
        },

        actionUnlinkRelated(data) {
            let product = this.products.find(item => item.id === data.id);

            if (!product) {
                return;
            }

            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                this.notify('Unlinking...');
                $.ajax({
                    url: `${this.linkScope}/${product.channelProductId}`,
                    type: 'DELETE',
                    contentType: 'application/json',
                    success: function () {
                        this.notify('Unlinked', 'success');
                        this.setupList();
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
                channelId: this.model.id,
                productId: item.id
            }))).then(() => {
                this.notify('Linked', 'success');
                this.setupList();
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