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

Espo.define('treo-crm:views/progress-manager/fields/status', 'views/fields/base',
    Dep => Dep.extend({

        listTemplate: 'treo-crm:progress-manager/fields/status/list',

        data() {
            return {
                value: (this.model.get(this.name) || {}).translate || '',
                color: this.getColor()
            };
        },

        getColor() {
            let color;
            switch ((this.model.get(this.name) || {}).key) {
                case 'new':
                    color = '#5bc0de';
                    break;
                case 'in_progress':
                    color = '#ef990e';
                    break;
                case 'error':
                    color = '#cf605d';
                    break;
                case 'success':
                    color = '#85b75f';
                    break;
                default:
                    color = '#000';
                    break;
            }
            return color;
        }

    })
);

