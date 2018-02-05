Espo.define('pim:views/category/record/list-image', 'views/record/list',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this, 'after:save', function () {
                this.collection.fetch();
            }, this);
        }

    })
);
