Espo.define('pim:views/record/search', 'views/record/search',
    Dep => Dep.extend({

        template: 'pim:record/search',

        hiddenBoolFilterList: [],

        boolFilterData: [],

        setup() {
            this.hiddenBoolFilterList = this.options.hiddenBoolFilterList || this.hiddenBoolFilterList;
            this.boolFilterData = this.options.boolFilterData || this.boolFilterData;

            Dep.prototype.setup.call(this);
        },

        data() {
            let data = Dep.prototype.data.call(this);
            data.boolFilterListLength = 0;
            data.boolFilterListComplex = data.boolFilterList.map(item => {
                let includes = this.hiddenBoolFilterList.includes(item);
                if (!includes) {
                    data.boolFilterListLength++;
                }
                return {name: item, hidden: includes};
            });
            return data;
        },

        manageBoolFilters() {
            (this.boolFilterList || []).forEach(item => {
                if (this.bool[item] && !this.hiddenBoolFilterList.includes(item)) {
                    this.currentFilterLabelList.push(this.translate(item, 'boolFilters', this.entityType));
                }
            });
        },

        updateCollection() {
            this.collection.reset();
            this.notify('Please wait...');
            this.listenTo(this.collection, 'sync', function () {
                this.notify(false);
            }.bind(this));
            let where = this.searchManager.getWhere();
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
            this.collection.fetch();
        },

    })
);
