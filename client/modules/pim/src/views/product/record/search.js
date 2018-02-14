Espo.define('pim:views/product/record/search', 'pim:views/record/search',
    Dep => Dep.extend({

        template: 'pim:product/record/search',

        familiesAttributes: [],

        data() {
            var data = Dep.prototype.data.call(this);

            data.familiesAttributes = this.familiesAttributes;
            return data;
        },

        setup() {
            this.wait(true);
            this.ajaxGetRequest('Markets/Attribute/filtersData').then(response => {
                if (response) {
                    this.familiesAttributes = response;
                    this.wait(false);
                }
            });

            Dep.prototype.setup.call(this);

            _.extend(Dep.prototype.events, this.additionalEvents);
            this.listenToOnce(this, 'after:render', function () {
                this.createAttributeFilters();
            }, this);

        },

        createFilters: function (callback) {
            var i = 0;
            var count = Object.keys(this.advanced || {}).length;

            if (count == 0) {
                if (typeof callback === 'function') {
                    callback();
                }
            }

            for (var field in this.advanced) {
                if (!this.advanced[field]['isAttribute']) {
                    this.createFilter(field, this.advanced[field], function () {
                        i++;
                        if (i == count) {
                            if (typeof callback === 'function') {
                                callback();
                            }
                        }
                    });
                }
            }
        },

        createAttributeFilters: function (callback) {
            var i = 0;
            var count = Object.keys(this.advanced || {}).length;

            if (count == 0) {
                if (typeof callback === 'function') {
                    callback();
                }
            }

            for (var field in this.advanced) {
                if (this.advanced[field]['isAttribute']) {
                    this.createAttributeFilter(field, this.advanced[field], function () {
                        i++;
                        if (i == count) {
                            if (typeof callback === 'function') {
                                callback();
                            }
                        }
                    });
                }
            }
        },

        additionalEvents: {
            'click a[data-action="addAttributeFilter"]': function (e) {
                var $target = $(e.currentTarget);
                var name = $target.data('id');
                var nameCount = 1;
                var getLastIndexName = function () {
                    if (this.advanced.hasOwnProperty(name + '-' + nameCount)) {
                        nameCount++;
                        getLastIndexName.call(this);
                    }
                };
                getLastIndexName.call(this);
                name = name + '-' + nameCount;
                this.advanced[name] = {};
                this.advanced = this.sortAdvanced(this.advanced);

                this.presetName = this.primary;
                this.createAttributeFilter(name, {}, function (view) {
                    view.populateDefaults();
                    this.fetch();
                    this.updateSearch();
                }.bind(this));
                this.updateAddAttributeFilterButton();
                this.handleLeftDropdownVisibility();

                this.manageLabels();
            },
            'click .advanced-filters a.remove-filter': function (e) {
                var $target = $(e.currentTarget);
                var name = $target.data('name');

                this.$el.find('ul.attribute-filter-list li[data-name="' + name + '"]').removeClass('hide');
                var container = this.getView('filter-' + name).$el.closest('div.filter');
                this.clearView('filter-' + name);
                container.remove();
                delete this.advanced[name];
                if (!Object.keys(this.advanced).length) {
                    this.$el.find('div.filter-applying-condition').addClass('hidden');
                    this.reRender();
                }

                this.presetName = this.primary;

                this.updateAddAttributeFilterButton();

                this.fetch();
                this.updateSearch();

                this.manageLabels();
                this.handleLeftDropdownVisibility();
            },
            'click a[data-action="showFamilyAttributes"]': function (e) {
                $(e.target).next('ul').toggle();
                e.stopPropagation();
                e.preventDefault();
            }
        },

        fetch: function () {
            this.textFilter = (this.$el.find('input[name="textFilter"]').val() || '').trim();

            this.bool = {};

            this.boolFilterList.forEach(function (name) {
                this.bool[name] = this.$el.find('input[name="' + name + '"]').prop('checked');
            }, this);

            for (var field in this.advanced) {
                var view = this.getView('filter-' + field).getView('field');
                this.advanced[field] = view.fetchSearch();
                this.familiesAttributes.forEach(function (family) {
                    family.rows.forEach(function (row) {
                        if (row.attributeId === field.split('-')[0]) {
                            this.advanced[field].isAttribute = true;
                        }
                    }, this);
                }, this);
                view.searchParams = this.advanced[field];
            }
        },

        updateAddAttributeFilterButton: function () {
            var $ul = this.$el.find('ul.family-list');
            if ($ul.children().not('.hide').size() == 0) {
                this.$el.find('button.add-attribute-filter-button').addClass('disabled');
            } else {
                this.$el.find('button.add-attribute-filter-button').removeClass('disabled');
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.updateAddAttributeFilterButton();
        },

        createAttributeFilter: function (name, params, callback, noRender) {
            params = params || {};
            var label = "";
            var type = "";

            if (this.isRendered() && !this.$advancedFiltersPanel.find(`.filter.filter-${name}`).length) {
                var div = document.createElement('div');
                div.className = "filter filter-" + name + " col-sm-4 col-md-3";
                div.setAttribute("data-name", name);
                var nameIndex = name.split('-')[1];
                var beforeFilterName = name.split('-')[0] + '-' + (+nameIndex - 1);
                var beforeFilter = this.$advancedFiltersPanel.find('.filter.filter-' + beforeFilterName + '.col-sm-4.col-md-3')[0];
                var afterFilterName = name.split('-')[0] + '-' + (+nameIndex + 1);
                var afterFilter = this.$advancedFiltersPanel.find('.filter.filter-' + afterFilterName + '.col-sm-4.col-md-3')[0];
                if (beforeFilter) {
                    var nextFilter = beforeFilter.nextElementSibling;
                    if (nextFilter) {
                        this.$advancedFiltersPanel[0].insertBefore(div, beforeFilter.nextElementSibling);
                    } else {
                        this.$advancedFiltersPanel[0].appendChild(div);
                    }
                } else if (afterFilter) {
                    this.$advancedFiltersPanel[0].insertBefore(div, afterFilter);
                } else {
                    this.$advancedFiltersPanel[0].appendChild(div);
                }
            }

            this.familiesAttributes.forEach(function (family) {
                family.rows.forEach(function (row) {
                    if (row.attributeId === name.split('-')[0]) {
                        label = row.name;
                        type = row.type;
                    }
                }, this);
            }, this);

            this.createView('filter-' + name, 'pim:views/product/search/filter', {
                name: name,
                type: type,
                label: label,
                isAttribute: true,
                model: this.model,
                params: params,
                el: this.options.el + ' .filter[data-name="' + name + '"]'
            }, function (view) {
                this.$el.find('div.filter-applying-condition').removeClass('hidden');
                if (typeof callback === 'function') {
                    view.once('after:render', function () {
                        callback(view);
                    });
                }
                view.render();
            }.bind(this));
        }
    })
);
