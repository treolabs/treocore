Espo.define('treo-crm:views/progress-manager/panel', 'view', function (Dep) {

    return Dep.extend({

        template: 'treo-crm:progress-manager/panel',

        setup: function () {
            this.wait(true);
            this.getCollectionFactory().create('ProgressManager', function (collection) {
                this.collection = collection;
                this.collection.maxSize = 200;
                this.collection.url = 'ProgressManager/popupData';

                this.listenTo(this.collection, 'reloadList', () => {
                    this.reloadList();
                });

                this.wait(false);
            }, this);
        },

        afterRender: function () {
            this.listenToOnce(this.collection, 'sync', function () {
                var viewName = 'views/record/list';
                this.createView('list', viewName, {
                    el: this.options.el + ' .list-container',
                    collection: this.collection,
                    rowActionsDisabled: true,
                    checkboxes: false,
                    headerDisabled: true,
                    listLayout: [
                        {
                            name: 'name',
                            notSortable: true,
                        },
                        {
                            name: 'progress',
                            view: 'treo-crm:views/progress-manager/fields/progress',
                            width: '90px'
                        },
                        {
                            name: 'status',
                            view: 'treo-crm:views/progress-manager/fields/status',
                            width: '90px'
                        },
                        {
                            name: 'actions',
                            view: 'treo-crm:views/progress-manager/fields/actions',
                            width: '240px'
                        }
                    ]
                }, function (view) {
                    view.render();
                });
            }, this);
            this.reloadList();
        },

        reloadList() {
            this.collection.fetch();
        }

    });

});
