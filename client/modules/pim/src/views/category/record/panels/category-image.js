Espo.define('pim:views/category/record/panels/category-image', 'views/record/panels/relationship',
    Dep => Dep.extend({

        rowActionsView: 'views/record/row-actions/relationship-edit-and-remove',

        setup() {
            this.defs.recordListView = 'pim:views/category/record/list-image';

            Dep.prototype.setup.call(this);

            this.actionList = this.actionList.filter(item => item.action !== 'selectRelated');
        }

    })
);
