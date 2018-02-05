Espo.define('treo-crm:views/module-manager/list', 'views/list',
    Dep => Dep.extend({

        createButton: false,

        searchPanel: false,

        loadList() {
            this.getCollectionFactory().create('ModuleManager', collection => {
                collection.maxSize = 200;
                collection.url = 'ModuleManager/list';

                this.listenToOnce(collection, 'sync', () => {
                    this.createView('list', 'views/record/list', {
                        collection: collection,
                        el: `${this.options.el} .list-container`,
                        type: 'list',
                        searchManager: false,
                        selectable: false,
                        checkboxes: false,
                        massActionsDisabled: true,
                        checkAllResultDisabled: false,
                        buttonsDisabled: false,
                        paginationEnabled: false,
                        showCount: false,
                        showMore: false,
                        rowActionsDisabled: true
                    }, view => {
                        let rows = view.nestedViews || {};
                        for (let key in rows) {
                            let setEditMode;
                            if (rows[key].model.get('isActive')) {
                                setEditMode = collection.every(model => !model.get('isActive') || !(model.get('required') || []).includes(key));
                            } else {
                                setEditMode = (collection.get(key).get('required') || []).every(item => {
                                    let model = collection.get(item);
                                    return model && model.get('isActive');
                                });
                            }
                            if (setEditMode) {
                                rows[key].getView('isActive').setMode('edit');
                            }
                            view.listenTo(rows[key].model, `change:isActive`, model => {
                                this.notify('Saving...');
                                this.ajaxPutRequest(`ModuleManager/${model.get('id')}/updateActivation`)
                                .then(() => {
                                    this.notify(this.translate('successAndReload', 'messages', 'Global').replace('{value}', 2), 'success');
                                    for (let k in rows) {
                                        rows[k].getView('isActive').setMode('list');
                                    }
                                    this.getView('list').reRender();
                                    setTimeout(function () {
                                        window.location.reload(true);
                                    }, 2000);
                                });
                            });
                        }
                        this.listenTo(view, 'after:render', () => {
                            this.$el.find('.list-container td.cell ').css({
                                'white-space': 'normal',
                                'text-overflow': 'ellipsis',
                                'overflow': 'hidden'
                            })
                        });
                        view.render();
                    });
                });

                collection.fetch();
            });
        },

        getHeader() {
            return '<a href="#Admin">' + this.translate('Administration') + "</a> » " + this.getLanguage().translate('moduleManager', 'labels', 'Admin');
        },

        updatePageTitle() {
            this.setPageTitle(this.getLanguage().translate('moduleManager', 'labels', 'Admin'));
        }

    })
);