Espo.define('pim:views/product-family/record/panels/product-family-attributes', 'views/record/panels/relationship',
    Dep => Dep.extend({

        linkScope: 'ProductFamilyAttribute',

        boolFilterData: {
            notLinkedWithProductFamily() {
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

            if (this.getAcl().check('Attribute', 'create') && !~['User', 'Team'].indexOf()) {
                this.buttonList.push({
                    title: 'Create',
                    action: this.defs.createAction || 'createRelated',
                    link: this.link,
                    acl: 'create',
                    aclScope: 'Attribute',
                    html: '<span class="glyphicon glyphicon-plus"></span>',
                    data: {
                        link: this.link,
                        scope: 'Attribute',
                        afterSaveCallback: 'actionCreateLink'
                    }
                });
            }

            this.actionList.unshift({
                label: 'Select',
                action: this.defs.selectAction || 'selectRelated',
                data: {
                    link: this.link,
                    scope: 'Attribute',
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
                    name: 'attribute',
                    type: 'link',
                    notSortable: true,
                },
                {
                    name: 'attributeType',
                    type: 'varchar',
                    notSortable: true,
                },
                {
                    name: 'attributeGroup',
                    type: 'link',
                    notSortable: true,
                },
                {
                    name: 'isRequired',
                    type: 'bool',
                    notSortable: true,
                },
                {
                    name: 'isMultiChannel',
                    type: 'bool',
                    notSortable: true,
                }
            ];

            let promise = this.ajaxGetRequest(`Markets/ProductFamily/${this.model.id}/attributes`);

            promise.then(response => {
                this.clearNestedViews();
                if (!response.length) {
                    this.showEmptyData();
                    return;
                }

                this.getCollectionFactory().create('ProductFamilyAttribute', collection => {
                    collection.total = response.length;

                    response.forEach(attribute => {
                        this.getModelFactory().create('ProductFamilyAttribute', model => {
                            let defs = {
                                fields: {
                                    attribute: {
                                        type: 'link'
                                    },
                                    attributeGroup: {
                                        type: 'link'
                                    },
                                    attributeType: {
                                        type: 'varchar'
                                    },
                                    isRequired: {
                                        type: 'bool'
                                    },
                                    isMultiChannel: {
                                        type: 'bool'
                                    }
                                },
                                links: {
                                    attribute: {
                                        entity: "Attribute",
                                        type: "belongsTo"
                                    },
                                    attributeGroup: {
                                        entity: "AttributeGroup",
                                        type: "belongsTo"
                                    }
                                }
                            };

                            attribute.attributeType = this.getLanguage().translateOption(attribute.attributeType, 'type', 'Attribute');

                            model.setDefs(defs);
                            model.set(attribute);
                            model.id = attribute.productFamilyAttributeId;
                            collection.add(model);
                            collection._byId[model.id] = model;
                        });
                    }, this);

                    this.createView('list', 'views/record/list', {
                        collection: collection,
                        el: `${this.options.el} .list-container`,
                        type: 'list',
                        searchManager: this.searchManager,
                        listLayout: listLayout,
                        selectable: false,
                        checkboxes: false,
                        massActionsDisabled: true,
                        checkAllResultDisabled: true,
                        buttonsDisabled: true,
                        paginationEnabled: false,
                        showCount: false,
                        showMore: false,
                        rowActionsView: 'pim:views/attribute/record/row-actions/relationship-no-view',
                    }, function (view) {
                        let rows = view.nestedViews || {};
                        let fieldChanging = function (rowValue, fieldName) {
                            rowValue.getView(fieldName).setMode('edit');
                            view.listenTo(rowValue.model, `change:${fieldName}`, model => {
                                this.notify('Saving...');
                                this.ajaxPatchRequest(`ProductFamilyAttribute/${model.id}`, {[fieldName]: model.get(`${fieldName}`)})
                                    .then(response => this.notify('Saved', 'success'));
                            }, this);
                        };
                        for (let key in rows) {
                            fieldChanging.call(this, rows[key], 'isRequired');
                            fieldChanging.call(this, rows[key], 'isMultiChannel');
                        }
                        view.render();
                    }, this);
                });
            });

        },

        actionCreateLink(models) {
            let items = Array.isArray(models) ? models : [models];
            Promise.all(items.map(item => this.ajaxPostRequest(this.linkScope, {
                productFamilyId: this.model.id,
                attributeId: item.id,
                isRequired: false
            }))).then(() => {
                this.notify('Linked', 'success');
                this.setupList();
            });
        },

        actionRemoveFamilyAttributeLink(data) {
            var id = data.id;
            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                this.notify('Unlinking...');
                $.ajax({
                    url: `ProductFamilyAttribute/${id}`,
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

        actionQuickEditAttribute(data) {
            data = data || {}
            var id = data.id;
            if (!id) return;

            this.notify('Loading...');

            this.getModelFactory().create('Attribute', model => {
                model.id = id;
                model.fetch();
                this.listenToOnce(model, 'sync', function (model1) {
                    var viewName = 'views/modals/edit';
                    this.createView('modal', viewName, {
                        scope: model.scope,
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