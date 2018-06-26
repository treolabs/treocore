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

Espo.define('treo-core:views/record/list', 'class-replace!treo-core:views/record/list', function (Dep) {

    return Dep.extend({

        template: 'treo-core:record/list',

        setup() {
            Dep.prototype.setup.call(this);

            _.extend(this.events, {
                'click a.link': function (e) {
                    e.stopPropagation();
                    if (e.ctrlKey) {
                        return;
                    }
                    if (!this.scope || this.selectable) {
                        return;
                    }
                    e.preventDefault();
                    var id = $(e.currentTarget).data('id');
                    var model = this.collection.get(id);

                    var scope = this.getModelScope(id);

                    var options = {
                        id: id,
                        model: model
                    };
                    if (this.options.keepCurrentRootUrl) {
                        options.rootUrl = this.getRouter().getCurrentUrl();
                    }

                    this.getRouter().navigate('#' + scope + '/view/' + id, {trigger: false});
                    this.getRouter().dispatch(scope, 'view', options);
                },
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.fixedTableHead()
        },

        fixedTableHead() {

            let $window = $(window),
                fixedTable = this.$el.find('.fixed-header-table'),
                fullTable = this.$el.find('.full-table'),
                navBarRight = $('.navbar-right'),
                posTopTable = 0,
                posLeftTable = 0,
                navBarHeight = 0,


                setPosition = function() {
                    posLeftTable = fullTable.position().left;
                    posTopTable = fullTable.position().top;
                    navBarHeight = navBarRight.outerHeight();

                    fixedTable.css({
                        'position': 'fixed',
                        'left': posLeftTable,
                        'top': navBarHeight - 1,
                        'right': 0,
                        'z-index': 1
                    });
                },
                setWidth = function () {
                    let widthTable = fullTable.outerWidth();

                    fixedTable.css('width', widthTable);

                    fullTable.find('thead').find('th').each(function (i) {
                        let width = $(this).outerWidth();
                        fixedTable.find('th').eq(i).css('width', width);
                    });
                },
                toggleClass = function () {
                    let showPosition = posTopTable - navBarHeight;

                    if ($window.scrollTop() > showPosition && $window.width() >= 768) {
                        fixedTable.removeClass('hidden');
                    } else {
                        fixedTable.addClass('hidden');
                    }
                };

            if (fullTable.length) {
                setPosition();
                setWidth();

                $window.on('scroll', toggleClass);
                $window.on('resize', function () {
                    setPosition();
                    setWidth();
                });

                $window.trigger('scroll');
            }
        }
    });
});