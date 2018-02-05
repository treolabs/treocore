Espo.define('pim:views/list', 'views/list',
    Dep => Dep.extend({

        searchView: 'pim:views/record/search',

        setup() {
            this.quickCreate = this.getMetadata().get(`clientDefs.${this.scope}.quickCreate`);

            Dep.prototype.setup.call(this);
        },

        setupSearchPanel() {
            let hiddenBoolFilterList = this.getMetadata().get(`clientDefs.${this.scope}.hiddenBoolFilterList`) || [];
            let searchView = this.getMetadata().get(`clientDefs.${this.scope}.recordViews.search`) || this.searchView;

            this.createView('search', searchView, {
                collection: this.collection,
                el: '#main > .search-container',
                searchManager: this.searchManager,
                scope: this.scope,
                hiddenBoolFilterList: hiddenBoolFilterList,
            }, function (view) {
                this.listenTo(view, 'reset', function () {
                    this.collection.sortBy = this.defaultSortBy;
                    this.collection.asc = this.defaultAsc;
                    this.getStorage().clear('listSorting', this.collection.name);
                }, this);
            }.bind(this));
        },

        navigateToEdit(id) {
            let router = this.getRouter();

            let url = `#${this.scope}/view/${id}`;

            router.dispatch(this.scope, 'view', {
                id: id,
                mode: 'edit',
                setEditMode: true
            });
            router.navigate(url, {trigger: false});
        },

        actionQuickCreate() {
            let options = _.extend({
                scope: this.scope,
                attributes: this.getCreateAttributes() || {}
            }, this.getMetadata().get(`clientDefs.${this.scope}.quickCreateOptions`) || {})

            this.notify('Loading...');
            this.createView('quickCreate', 'views/modals/edit', options, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.collection.fetch();

                    if (this.getMetadata().get(`clientDefs.${this.scope}.navigateToEntityAfterQuickCreate`)) {
                        this.navigateToEdit(view.getView('edit').model.id);
                    }
                }, this);
            }.bind(this));
        }
    })
);

