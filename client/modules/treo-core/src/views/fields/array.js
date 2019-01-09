/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
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

Espo.define('treo-core:views/fields/array', 'class-replace!treo-core:views/fields/array', function (Dep) {

    return Dep.extend({

        searchTemplate: 'treo-core:fields/array/search',

        searchTypeList: ['anyOf', 'noneOf', 'isEmpty', 'isNotEmpty'],

        setupSearch: function () {
            this.events = _.extend({
                'change select.search-type': function (e) {
                    this.handleSearchType($(e.currentTarget).val());
                },
            }, this.events || {});
        },

        handleSearchType: function (type) {
            var $inputContainer = this.$el.find('div.input-container');

            if (~['anyOf', 'noneOf'].indexOf(type)) {
                $inputContainer.removeClass('hidden');
            } else {
                $inputContainer.addClass('hidden');
            }
        },

        renderSearch: function () {
            var $element = this.$element = this.$el.find('[name="' + this.name + '"]');

            var type = this.$el.find('select.search-type').val();
            this.handleSearchType(type);

            var valueList = this.searchParams.valueFront || [];
            this.$element.val(valueList.join(':,:'));

            var data = [];
            (this.params.options || []).forEach(function (value) {
                var label = this.getLanguage().translateOption(value, this.name, this.scope);
                if (this.translatedOptions) {
                    if (value in this.translatedOptions) {
                        label = this.translatedOptions[value];
                    }
                }
                data.push({
                    value: value,
                    label: label
                });
            }, this);

            this.$element.selectize({
                create: true,
                options: data,
                delimiter: ':,:',
                labelField: 'label',
                valueField: 'value',
                highlight: false,
                searchField: ['label'],
                plugins: ['remove_button'],
                score: function (search) {
                    var score = this.getScoreFunction(search);
                    search = search.toLowerCase();
                    return function (item) {
                        if (item.label.toLowerCase().indexOf(search) === 0) {
                            return score(item);
                        }
                        return 0;
                    };
                }
            });

            this.$el.find('.selectize-dropdown-content').addClass('small');
        },

        parseItemForSearch: function (item) {
            return item;
        },

        fetchSearch: function () {
            var type = this.$el.find('[name="'+this.name+'-type"]').val();

            var list = this.$element.val().split(':,:');
            if (list.length === 1 && list[0] == '') {
                list = [];
            }

            list.forEach(function (item, i) {
                list[i] = this.parseItemForSearch(item);
            }, this);

            if (type === 'anyOf') {
                if (list.length === 0) {
                    return {
                        data: {
                            type: 'anyOf',
                            valueList: list
                        }
                    };
                }
                return {
                    type: 'or',
                    value: list.map(item => {
                        return {
                            type: 'like',
                            value: `%"${item}"%`,
                            attribute: this.name
                        }
                    }),
                    valueFront: list
                };
            } else if (type === 'noneOf') {
                if (list.length === 0) {
                    return {
                        data: {
                            type: 'noneOf',
                            valueList: list
                        }
                    };
                }
                return {
                    type: 'or',
                    value: [
                        {
                            type: 'isNull',
                            attribute: this.name
                        },
                        {
                            type: 'and',
                            value: list.map(item => {
                                return {
                                    type: 'notLike',
                                    value: `%"${item}"%`,
                                    attribute: this.name
                                }
                            })
                        }
                    ],
                    valueFront: list
                };
            } else if (type === 'isEmpty') {
                return {
                    type: 'or',
                    value: [
                        {
                            type: 'isNull',
                            attribute: this.name
                        },
                        {
                            type: 'equals',
                            value: '',
                            attribute: this.name
                        },
                        {
                            type: 'equals',
                            value: '[]',
                            attribute: this.name
                        }
                    ]
                };
            } else if (type === 'isNotEmpty') {
                return {
                    type: 'and',
                    value: [
                        {
                            type: 'isNotNull',
                            attribute: this.name
                        },
                        {
                            type: 'notEquals',
                            value: '',
                            attribute: this.name
                        },
                        {
                            type: 'notEquals',
                            value: '[]',
                            attribute: this.name
                        }
                    ]
                };
            }
        },

        getSearchType: function () {
            return this.getSearchParamsData().type || 'anyOf';
        }

    })
});