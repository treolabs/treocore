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

Espo.define('treo-core:views/login', 'class-replace!treo-core:views/login',
    Dep => Dep.extend({

        template: 'treo-core:login',

        language: null,

        events: _.extend({
            'change select[name="language"]': function (event) {
                this.language = $(event.currentTarget).val();
                if (this.language) {
                    this.ajaxGetRequest('I18n', {locale: this.language}).then((data) => {
                        this.getLanguage().data = data;
                        this.reRender();
                    });
                }
            }
        }, Dep.prototype.events),

        data() {
            return _.extend({
                locales: this.getLocales()
            }, Dep.prototype.data.call(this));
        },

        getLocales() {
            let language = this.language || this.getConfig().get('language');
            let translatedOptions = Espo.Utils.clone(this.getLanguage().translate('language', 'options') || {});

            return Espo.Utils
                .clone(this.getConfig().get('languageList')).sort((v1, v2) => this.getLanguage().translateOption(v1, 'language').localeCompare(this.getLanguage().translateOption(v2, 'language'))                )
                .map(item => {
                    return {
                        value: item,
                        label: translatedOptions[item],
                        selected: item === language
                    };
                });
        }

   })
);