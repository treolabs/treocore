Espo.define('pim:views/category/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this, 'after:save', () => this.model.fetch());
        }
    })
);

