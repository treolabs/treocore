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

$(function () {

    let GeneralModel = Backbone.Model.extend({});
    let generalModel = new GeneralModel;

    let MainView = Backbone.View.extend({

        el: $('#main-template'),

        initialize() {
            this.render();
        },

        render() {
            this.getTranslations().done(function (translations) {
                this.model.set({translate: translations});
                let languageAndLicenseStep = new LanguageAndLicenseStep({model: generalModel});
                this.changeView(languageAndLicenseStep);
            }.bind(this));
        },

        changeView(viewName) {
            this.$el.append(viewName.render().el);
        },

        getTranslations() {
            return $.ajax({
                url: 'api/v1/Installer/getTranslations',
                type: 'GET'
            });
        },

        validate(step) {
            let check = true;
            this.requiredFields.forEach(function (field) {
                if (this.model.get(step)[field] === '') {
                    let msg = this.model.get('translate').messages.fieldIsRequired.replace('{field}', this.model.get('translate').fields[field]);
                    mainView.showValidationMessage.call(this, msg, field);
                    check = false;
                }
            }.bind(this));
            return check;
        },

        showValidationMessage: function (message, selector) {
            selector = '#' + selector;

            let $el = this.$el.find(selector);

            $el.popover({
                placement: 'bottom',
                container: 'body',
                content: message,
                trigger: 'manual'
            }).popover('show');

            $el.parent().addClass('has-error');

            $el.one('mousedown click', function () {
                $el.popover('destroy');
                $el.parent().removeClass('has-error');
            });

            this.once('render destroy', function () {
                if ($el) {
                    $el.popover('destroy');
                    $el.parent().removeClass('has-error')
                }
            });
        }
    });

    let LanguageAndLicenseStep = Backbone.View.extend({

        className: 'main-template container',

        template: _.template($('#language-license').html()),

        events: {
            'change select[name="user-lang"]': 'languageChange',
            'click .next-step': 'nextStep'
        },

        initialize() {
            this.getLicenseAndLanguages().done(function (data) {
                this.model.set({licenseAndLanguages: data});
                this.render();
            }.bind(this));
        },

        render() {
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        },

        getLicenseAndLanguages() {
            return $.ajax({
                url: 'api/v1/Installer/getLicenseAndLanguages',
                type: 'GET',
                async: false
            });
        },

        nextStep() {
            if (this.$el.find('#license-agree').is(':checked')) {
                if (!this.$el.find('.msg-box').hasClass('hidden')) {
                    this.$el.find('.msg-box').addClass('hidden');
                }
                this.remove();
                let dbConnectSettings = new DbConnectSettings({model: generalModel});
                mainView.changeView(dbConnectSettings);
            } else {
                this.$el.find('.msg-box').text(this.model.get('translate').messages.youMustAgreeToTheLicenseAgreement);
                this.$el.find('.msg-box').addClass('alert-danger');
                this.$el.find('.msg-box').removeClass('hidden');
            }
        },

        languageChange() {
            let data = this.model.get('licenseAndLanguages');
            _.extend(data, {language: this.$el.find('select[name="user-lang"]').val() || ''});
            this.model.set('licenseAndLanguages', data);
            let dataToSave = {
                language: this.model.get('licenseAndLanguages').language
            };
            $.ajax({
                url: 'api/v1/Installer/setLanguage',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(dataToSave)
            }).done(function (data) {
                if (data.status) {
                    mainView.getTranslations().done(function (translations) {
                        this.model.set({translate: translations});
                        this.render();
                    }.bind(this));
                } else {
                    this.$el.find('.msg-box').text(data.message);
                    this.$el.find('.msg-box').removeClass('alert-success');
                    this.$el.find('.msg-box').addClass('alert-danger');
                    this.$el.find('.msg-box').removeClass('hidden');
                }
            }.bind(this));
        },
    });

    let DbConnectSettings = Backbone.View.extend({

        className: 'main-template container',

        template: _.template($('#db-connect').html()),

        requiredFields: ['host', 'dbname', 'user'],

        events: {
            'click .test-db-connection': 'testDbConnection',
            'click .back-step': 'backStep',
            'click .next-step': 'nextStep',
            'focusout .modal-body input.form-control': 'setFieldsValuesToModel'
        },

        initialize() {
            if (!this.model.has('dbSettings')) {
                this.getDefaultDbSettings().done(function (data) {
                    this.model.set({dbSettings: data});
                    this.render();
                }.bind(this));
            }
        },

        render() {
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        },

        getDefaultDbSettings() {
            return $.ajax({
                url: 'api/v1/Installer/getDefaultDbSettings',
                type: 'GET',
                async: false
            });
        },

        setFieldsValuesToModel() {
            let data = {
                host: this.$el.find('#host').val(),
                dbname: this.$el.find('#dbname').val(),
                user: this.$el.find('#user').val(),
                password: this.$el.find('#password').val(),
                port: this.$el.find('#port').val()
            };
            this.model.set({dbSettings: data});
        },

        testDbConnection() {
            if (mainView.validate.call(this, 'dbSettings')) {
                this.checkDbConnect().done(function (data) {
                    if (data.status) {
                        this.$el.find('.msg-box').text(this.model.get('translate').messages.connectionSuccessful);
                        this.$el.find('.msg-box').removeClass('alert-danger');
                        this.$el.find('.msg-box').addClass('alert-success');
                        this.$el.find('.msg-box').removeClass('hidden');
                    } else {
                        this.$el.find('.msg-box').text(data.message);
                        this.$el.find('.msg-box').removeClass('alert-success');
                        this.$el.find('.msg-box').addClass('alert-danger');
                        this.$el.find('.msg-box').removeClass('hidden');
                    }
                }.bind(this));
            }
        },

        checkDbConnect() {
            let data = {
                host: this.model.get('dbSettings').host,
                dbname: this.model.get('dbSettings').dbname,
                user: this.model.get('dbSettings').user,
                password: this.model.get('dbSettings').password,
                port: this.model.get('dbSettings').port
            };
            return $.ajax({
                url: 'api/v1/Installer/checkDbConnect',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data)
            });
        },

        backStep() {
            this.trigger('destroy');
            this.remove();
            let languageAndLicenseStep = new LanguageAndLicenseStep({model: generalModel});
            mainView.changeView(languageAndLicenseStep);
        },

        nextStep() {
            if (mainView.validate.call(this, 'dbSettings')) {
                this.setDbSettings().done(function (data) {
                    if (data.status) {
                        this.remove();
                        let adminCreation = new AdminCreation({model: generalModel});
                        mainView.changeView(adminCreation);
                    } else {
                        this.$el.find('.msg-box').text(data.message);
                        this.$el.find('.msg-box').removeClass('alert-success');
                        this.$el.find('.msg-box').addClass('alert-danger');
                        this.$el.find('.msg-box').removeClass('hidden');
                    }
                }.bind(this));
            }
        },

        setDbSettings() {
            let data = {
                host: this.model.get('dbSettings').host,
                dbname: this.model.get('dbSettings').dbname,
                user: this.model.get('dbSettings').user,
                password: this.model.get('dbSettings').password,
                port: this.model.get('dbSettings').port
            };
            return $.ajax({
                url: 'api/v1/Installer/setDbSettings',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data)
            });
        },

    });

    let AdminCreation = Backbone.View.extend({

        className: 'main-template container',

        template: _.template($('#admin-creation').html()),

        requiredFields: ['username', 'password', 'confirmPassword'],

        events: {
            'click .back-step': 'backStep',
            'click .next-step': 'nextStep',
            'focusout .modal-body input.form-control': 'setFieldsValuesToModel'
        },

        initialize() {
            if (!this.model.has('adminSettings')) {
                this.model.set({
                    adminSettings: {
                        username: '',
                        password: '',
                        confirmPassword: ''
                    }
                });
            }
        },

        render() {
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        },

        setFieldsValuesToModel() {
            let data = {
                username: this.$el.find('#username').val(),
                password: this.$el.find('#password').val(),
                confirmPassword: this.$el.find('#confirmPassword').val()
            };
            this.model.set({adminSettings: data});
        },

        backStep() {
            this.trigger('destroy');
            this.remove();
            let dbConnectSettings = new DbConnectSettings({model: generalModel});
            mainView.changeView(dbConnectSettings);
        },

        nextStep() {
            if (mainView.validate.call(this, 'adminSettings')) {
                this.setAdminSettings().done(function (data) {
                    if (data.status) {
                        window.location.reload();
                    } else {
                        this.$el.find('.msg-box').text(data.message);
                        this.$el.find('.msg-box').removeClass('alert-success');
                        this.$el.find('.msg-box').addClass('alert-danger');
                        this.$el.find('.msg-box').removeClass('hidden');
                    }
                }.bind(this));
            }
        },

        setAdminSettings() {
            let data = {
                username: this.model.get('adminSettings').username,
                password: this.model.get('adminSettings').password,
                confirmPassword: this.model.get('adminSettings').confirmPassword
            };
            return $.ajax({
                url: 'api/v1/Installer/createAdmin',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data)
            });
        }
    });

    let mainView = new MainView({model: generalModel});
});