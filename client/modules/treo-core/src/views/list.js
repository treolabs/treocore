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

Espo.define('treo-core:views/list', 'class-replace!treo-core:views/list',
    Dep => Dep.extend({

        enabledfixedHeader: true,

        prepareRecordViewOptions(options) {
            Dep.prototype.prepareRecordViewOptions.call(this, options);

            options.enabledfixedHeader = this.enabledfixedHeader;
        },

        setupSorting() {
            var sortingParams = this.getStorage().get('listSorting', this.collection.name);

            if (sortingParams && sortingParams.sortBy && !(sortingParams.sortBy in this.getMetadata().get(['entityDefs', this.collection.name, 'fields']))) {
                this.getStorage().clear('listSorting', this.collection.name);
            }

            Dep.prototype.setupSorting.call(this);
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            let $window = $(window);

            $window.off('scroll.main');
            $window.on('scroll.main', () => {
                let scrollTop = $window.scrollTop();
                let header = this.$el.find('.header-breadcrumbs');
                let width = $('.header-breadcrumbs').parent().parent().width();

                if (scrollTop > this.$el.find('.page-header').outerHeight()) {
                    header.addClass('fixed-header-breadcrumbs')
                        .css('width',width);
                } else {
                    header.removeClass('fixed-header-breadcrumbs')
                        .css('width','auto');
                }
            });
        }

    })
);