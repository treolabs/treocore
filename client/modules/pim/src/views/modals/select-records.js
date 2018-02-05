Espo.define('pim:views/modals/select-records', ['views/modals/select-records', 'search-manager'],
    (Dep, SearchManager) => Dep.extend({

        listLayout: null,

        searchView: 'pim:views/record/search',

        boolFilterData: [],

        setup() {
            this.boolFilterData = this.options.boolFilterData || this.boolFilterData;

            this.listLayout = this.options.listLayout || this.listLayout;

            Dep.prototype.setup.call(this);
        },

        loadList() {
            let viewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.listSelect') ||
                this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') ||
                'views/record/list';

            this.listenToOnce(this.collection, 'sync', function () {
                this.createView('list', viewName, {
                    collection: this.collection,
                    el: this.containerSelector + ' .list-container',
                    selectable: true,
                    checkboxes: this.multiple,
                    massActionsDisabled: true,
                    rowActionsView: false,
                    layoutName: this.listLayout || 'listSmall',
                    searchManager: this.searchManager,
                    checkAllResultDisabled: !this.massRelateEnabled,
                    buttonsDisabled: true
                }, function (list) {
                    list.once('select', function (model) {
                        this.trigger('select', model);
                        this.close();
                    }.bind(this));
                }.bind(this));

            }.bind(this));
        },

        loadSearch: function () {
            var searchManager = this.searchManager = new SearchManager(this.collection, 'listSelect', null, this.getDateTime());
            searchManager.emptyOnReset = true;
            if (this.filters) {
                searchManager.setAdvanced(this.filters);
            }

            var boolFilterList = this.boolFilterList || this.getMetadata().get('clientDefs.' + this.scope + '.selectDefaultFilters.boolFilterList');
            if (boolFilterList) {
                var d = {};
                boolFilterList.forEach(function (item) {
                    d[item] = true;
                });
                searchManager.setBool(d);
            }
            var primaryFilterName = this.primaryFilterName || this.getMetadata().get('clientDefs.' + this.scope + '.selectDefaultFilters.filter');
            if (primaryFilterName) {
                searchManager.setPrimary(primaryFilterName);
            }

            let where = searchManager.getWhere();
            where.forEach(item => {
                if (item.type === 'bool') {
                    let data = {};
                    item.value.forEach(elem => {
                        if (elem in this.boolFilterData) {
                            data[elem] = this.boolFilterData[elem];
                        }
                    });
                    item.data = data;
                }
            });
            this.collection.where = where;


            if (this.searchPanel) {
                let hiddenBoolFilterList = this.getMetadata().get(`clientDefs.${this.scope}.hiddenBoolFilterList`) || [];
                let searchView = this.getMetadata().get(`clientDefs.${this.scope}.recordViews.search`) || this.searchView;

                this.createView('search', searchView, {
                    collection: this.collection,
                    el: this.containerSelector + ' .search-container',
                    searchManager: searchManager,
                    disableSavePreset: true,
                    hiddenBoolFilterList: hiddenBoolFilterList,
                    boolFilterData: this.boolFilterData
                });
            }
        },

    })
);

