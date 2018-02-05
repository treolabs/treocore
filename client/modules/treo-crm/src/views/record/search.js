Espo.define('treo-crm:views/record/search', 'class-replace!treo-crm:views/record/search', function (Dep) {

    return Dep.extend({

        template: 'treo-crm:record/search',

        typesWithOneFilter: ['bool'],

        setup: function () {
            Dep.prototype.setup.call(this);

            _.extend(Dep.prototype.events, this.newEvents);
        },

        sortAdvanced: function (advanced) {
            var result = {};
            Object.keys(advanced).sort(function (item1, item2) {
                return item1.localeCompare(item2, undefined, {numeric: true});
            }).forEach(function (item) {
                result[item] = advanced[item];
            }.bind(this));
            return result;
        },

        newEvents: {
            'click a[data-action="addFilter"]': function (e) {
                var $target = $(e.currentTarget);
                var name = $target.data('name');
                var nameCount = 1;
                var getLastIndexName = function () {
                    if (this.advanced.hasOwnProperty(name + '-' + nameCount)) {
                        nameCount++;
                        getLastIndexName.call(this);
                    } else {
                        name = name + '-' + nameCount;
                        nameCount = 1;
                    }
                };
                getLastIndexName.call(this);
                this.advanced[name] = {};
                this.advanced = this.sortAdvanced(this.advanced);

                var nameType = this.model.getFieldType(name.split('-')[0]);
                if (this.typesWithOneFilter.indexOf(nameType) > -1) {
                    $target.closest('li').addClass('hide');
                }

                this.presetName = this.primary;

                this.createFilter(name, {}, function (view) {
                    view.populateDefaults();
                    this.fetch();
                    this.updateSearch();
                }.bind(this));
                this.updateAddFilterButton();
                this.handleLeftDropdownVisibility();

                this.manageLabels();
            },
            'click .advanced-filters a.remove-filter': function (e) {
                var $target = $(e.currentTarget);
                var name = $target.data('name');

                this.$el.find('ul.filter-list li[data-name="' + name + '"]').removeClass('hide');
                var container = this.getView('filter-' + name).$el.closest('div.filter');
                this.clearView('filter-' + name);
                container.remove();
                delete this.advanced[name];
                if (!Object.keys(this.advanced).length) {
                    this.$el.find('div.filter-applying-condition').addClass('hidden');
                    this.reRender();
                }
                this.presetName = this.primary;

                this.updateAddFilterButton();

                this.fetch();
                this.updateSearch();

                this.manageLabels();
                this.handleLeftDropdownVisibility();
            }
        },

        afterRender: function () {
            if (Object.keys(this.advanced).length) {
                this.$el.find('div.filter-applying-condition').removeClass('hidden');
            }

            this.$filtersLabel = this.$el.find('.search-row span.filters-label');
            this.$filtersButton = this.$el.find('.search-row button.filters-button');
            this.$leftDropdown = this.$el.find('div.search-row div.left-dropdown');

            this.updateAddFilterButton();

            this.$advancedFiltersBar = this.$el.find('.advanced-filters-bar');
            this.$advancedFiltersPanel = this.$el.find('.advanced-filters');

            this.manageLabels();
        },

        createFilter: function (name, params, callback, noRender) {
            params = params || {};

            var rendered = false;
            if (this.isRendered()) {
                rendered = true;
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

            this.createView('filter-' + name, 'treo-crm:views/search/filter', {
                name: name,
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
                if (rendered && !noRender) {
                    view.render();
                }
                this.listenTo(view, 'after:render', function () {
                    this.$el.find('.advanced-filters .filter').css({
                        'min-height': '110px'
                    });
                }, this);
            }.bind(this));
        },

        getAdvancedDefs: function () {
            var defs = [];
            for (var i in this.moreFieldList) {
                var field = this.moreFieldList[i];
                var fieldType = this.model.getFieldType(field.split('-')[0]);
                var advancedFieldsList = [];
                Object.keys(this.advanced).forEach(function (item) {
                    advancedFieldsList.push(item.split('-')[0]);
                });
                var o = {
                    name: field,
                    checked: (this.typesWithOneFilter.indexOf(fieldType) > -1 && advancedFieldsList.indexOf(field) > -1),
                };
                defs.push(o);
            }
            return defs;
        }
    });
});