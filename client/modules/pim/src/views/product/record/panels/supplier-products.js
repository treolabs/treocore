Espo.define('pim:views/product/record/panels/supplier-products', 'views/record/panels/relationship',
    Dep => Dep.extend({

        suppliers: [],

        linkScope: 'SupplierProduct',

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

            var url = this.url || this.model.name + '/' + this.model.id + '/' + this.link;

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

            if (this.getAcl().check('Supplier', 'create') && !~['User', 'Team'].indexOf()) {
                this.buttonList.push({
                    title: 'Create',
                    action: this.defs.createAction || 'createRelated',
                    link: this.link,
                    acl: 'create',
                    aclScope: 'Supplier',
                    html: '<span class="glyphicon glyphicon-plus"></span>',
                    data: {
                        link: this.link,
                        scope: 'Supplier',
                        afterSaveCallback: 'actionCreateLink'
                    }
                });
            }

            this.actionList.unshift({
                label: 'Select',
                action: this.defs.selectAction || 'selectRelated',
                data: {
                    link: this.link,
                    scope: 'Supplier',
                    afterSelectCallback: 'actionCreateLink',
                    boolFilterListCallback: 'getSelectBoolFilterList',
                    boolFilterDataCallback: 'getSelectBoolFilterData',
                    primaryFilterName: this.defs.selectPrimaryFilterName || null
                }
            });

            this.once('after:render', () => {
                this.setupList();
            });

            this.setupFilterActions();
        },

        setupList() {
            let listLayout = [
                {
                    name: 'name',
                    link: true,
                    notSortable: true,
                }
            ];

            this.suppliers = [];

            let promise = this.ajaxGetRequest(`Markets/Product/${this.model.id}/supplier`);

            promise.then(response => {
                this.clearNestedViews();
                if (!response.length) {
                    this.showEmptyData();
                    return;
                }

                let formedResponse = response.map(item => {
                    return {
                        id: item.supplierId,
                        name: item.supplierName,
                        supplierProductId: item.supplierProductId
                    };
                });

                this.suppliers = formedResponse;

                this.getCollectionFactory().create('Supplier', collection => {
                    collection.total = formedResponse.length;

                    formedResponse.forEach(supplier => {
                        this.getModelFactory().create('Supplier', model => {
                            model.set(supplier);
                            model.id = supplier.id;
                            collection.add(model);
                            collection._byId[model.id] = model;
                        });
                    }, this);

                    this.createView('list', 'views/record/list', {
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
                        rowActionsView: 'pim:views/supplier/record/row-actions/relationship-no-view',
                        listLayout: listLayout,
                    }, function (view) {
                        view.render();
                    }, this);
                });
            });
        },

        actionCreateLink(models) {
            let items = Array.isArray(models) ? models : [models];
            Promise.all(items.map(item => this.ajaxPostRequest(this.linkScope, {
                productId: this.model.id,
                supplierId: item.id
            }))).then(() => {
                this.notify('Linked', 'success');
                this.setupList();
            });
        },

        actionQuickEditSupplier(data) {
            data = data || {}
            let id = data.id;
            if (!id) {
                return;
            }

            this.notify('Loading...');

            this.getModelFactory().create('Supplier', model => {
                model.id = id;
                model.fetch();
                this.listenToOnce(model, 'sync', function (model1) {
                    let viewName = 'views/modals/edit';
                    this.createView('modal', viewName, {
                        scope: 'Supplier',
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

                        this.listenToOnce(view, 'after:save', function () {
                            this.setupList();
                        }, this);

                    }, this);
                });
            });
        },

        actionRemoveSupplierLink(data) {
            let supplier = this.suppliers.find(item => item.id === data.id);

            if (!supplier) {
                return;
            }

            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                this.notify('Unlinking...');
                $.ajax({
                    url: `${this.linkScope}/${supplier.supplierProductId}`,
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

        showEmptyData() {
            this.$el.find('.list-container').html(this.translate('No Data'));
        },

        clearNestedViews() {
            for (let key in this.nestedViews) {
                this.clearView(key);
            }
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

        getSelectBoolFilterList() {
            return this.defs.selectBoolFilterList || null
        },

        actionRefresh: function () {
            this.setupList();
        },

    })
);