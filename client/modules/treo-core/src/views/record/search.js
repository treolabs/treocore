/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

Espo.define('treo-core:views/record/search', 'class-replace!treo-core:views/record/search', function (Dep) {

    return Dep.extend({

        template: 'treo-core:record/search',

        typesWithOneFilter: ['array', 'bool', 'enum', 'multiEnum', 'linkMultiple'],

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
                    }
                };
                getLastIndexName.call(this);
                name = name + '-' + nameCount;
                this.advanced[name] = {};
                this.advanced = this.sortAdvanced(this.advanced);

                var nameType = this.model.getFieldType(name.split('-')[0]);
                if (this.typesWithOneFilter.includes(nameType)) {
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

                this.$el.find('ul.filter-list li[data-name="' + name.split('-')[0] + '"]').removeClass('hide');
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
            },
            'click a[data-action="selectPreset"]': function (e) {
                var presetName = $(e.currentTarget).data('name') || null;
                this.selectPreset(presetName);
                if (presetName) {
                    this.$el.find('div.filter-applying-condition').removeClass('hidden');
                }
            },
            'keypress .field input[type="text"]': function (e) {
                if (e.keyCode === 13) {
                    this.search();
                }
            },
        },

        updateAddFilterButton: function () {
            var $ul = this.$el.find('ul.filter-list');
            if ($ul.children().not('.hide').size() == 0) {
                this.$el.find('a.add-filter-button').addClass('hidden');
            } else {
                this.$el.find('a.add-filter-button').removeClass('hidden');
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

            this.createView('filter-' + name, 'treo-core:views/search/filter', {
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
        },

        managePresetFilters: function () {
            var presetName = this.presetName || null;
            var data = this.getPresetData();
            var primary = this.primary;

            this.$el.find('ul.filter-menu a.preset span').remove();

            var filterLabel = this.translate('All');
            var filterStyle = 'default';

            if (!presetName && primary) {
                presetName = primary;
            }

            if (presetName && presetName != primary) {
                var label = null;
                var style = 'default';
                var id = null;

                this.presetFilterList.forEach(function (item) {
                    if (item.name == presetName) {
                        label = item.label || false;
                        style = item.style || 'default';
                        id = item.id;
                        return;
                    }
                }, this);
                label = label || this.translate(this.presetName, 'presetFilters', this.entityType);

                filterLabel = label;
                filterStyle = style;

                if (id) {
                    this.$el.find('ul.dropdown-menu > li.divider.preset-control').removeClass('hidden');
                    this.$el.find('ul.dropdown-menu > li.preset-control.remove-preset').removeClass('hidden');
                }

            } else {
                if (Object.keys(this.advanced).length !== 0) {
                    if (!this.disableSavePreset) {
                        this.$el.find('ul.dropdown-menu > li.divider.preset-control').removeClass('hidden');
                        this.$el.find('ul.dropdown-menu > li.preset-control.save-preset').removeClass('hidden');
                        this.$el.find('ul.dropdown-menu > li.preset-control.remove-preset').addClass('hidden');

                    }
                }

                if (primary) {
                    var label = this.translate(primary, 'presetFilters', this.entityType);
                    var style = this.getPrimaryFilterStyle();
                    filterLabel = label;
                    filterStyle = style;
                }
            }

            this.currentFilterLabelList.push(filterLabel);

            this.$filtersButton.removeClass('btn-default')
                .removeClass('btn-primary')
                .removeClass('btn-danger')
                .removeClass('btn-success')
                .removeClass('btn-info');
            this.$filtersButton.addClass('btn-' + filterStyle);

            presetName = presetName || '';

            this.$el.find('ul.filter-menu a.preset[data-name="'+presetName+'"]').prepend('<span class="glyphicon glyphicon-ok pull-right"></span>');
        },
    });
});