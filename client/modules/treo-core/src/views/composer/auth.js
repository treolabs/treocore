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

Espo.define('treo-core:views/composer/auth', 'view',
    Dep => Dep.extend({

        template: 'treo-core:composer/auth',

        attributes: null,

        model: null,

        mode: 'edit',

        events: {
            'click .action': function (e) {
                let action = $(e.currentTarget).data('action');
                if (!action) {
                    return;
                }
                action = `action${Espo.Utils.upperCaseFirst(action)}`;
                if (typeof this[action] === 'function') {
                    this[action]();
                }
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.ajaxGetRequest('Composer/gitAuth').then(response => {
                this.getModelFactory().create(null, model => {
                    this.model = model;
                    this.attributes = {
                        username: typeof response.username !== 'undefined' ? response.username : null,
                        password: typeof response.password !== 'undefined' ? response.password : null
                    };
                    model.set(this.attributes);
                    this.createFieldsViews();
                    this.wait(false);
                })
            });
        },

        createFieldsViews() {
            this.createView('username', 'views/fields/varchar', {
                el: `${this.options.el} .field[data-name="username"]`,
                model: this.model,
                mode: this.mode,
                readOnly: true,
                defs: {
                    name: 'username',
                    params: {
                        required: true,
                    }
                }
            });

            this.createView('password', 'views/fields/varchar', {
                el: `${this.options.el} .field[data-name="password"]`,
                model: this.model,
                mode: this.mode,
                readOnly: true,
                defs: {
                    name: 'password',
                    params: {
                        required: true,
                    }
                }
            });
        },

        getFieldViews() {
            let fieldsViews = {};
            if (this.hasView('username')) {
                fieldsViews.username = this.getView('username');
            }
            if (this.hasView('password')) {
                fieldsViews.password = this.getView('password');
            }
            return fieldsViews
        },

        fetch() {
            let data = {};
            let fieldViews = this.getFieldViews();
            for (let key in fieldViews) {
                _.extend(data, fieldViews[key].fetch());
            }
            return data;
        },

        updatePageTitle() {
            this.setPageTitle(this.getLanguage().translate('Git Authentication', 'labels', 'Admin'));
        },

    })
);