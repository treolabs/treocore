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

Espo.define('treo-core:views/record/detail', 'class-replace!treo-core:views/record/detail', function (Dep) {

    return Dep.extend({

        template: 'treo-core:record/detail',

        panelNavigationView: 'treo-core:views/record/panel-navigation',

        setup: function () {
            Dep.prototype.setup.call(this);

            $(window).on('keydown', e => {
                if (e.keyCode === 69 && e.ctrlKey && !$('body').hasClass('modal-open')) {
                    this.hotKeyEdit(e);
                }
                if (e.keyCode === 83 && e.ctrlKey && !$('body').hasClass('modal-open')) {
                    this.hotKeySave(e);
                }
            });
        },

        hotKeyEdit: function (e) {
            e.preventDefault();
            if (this.mode !== 'edit') {
                this.actionEdit();
            }
        },

        hotKeySave: function (e) {
            e.preventDefault();
            if (this.mode === 'edit') {
                this.actionSave();
            } else {
                let viewsFields = this.getFieldViews();
                Object.keys(viewsFields).forEach(item => {
                    if (viewsFields[item].mode === "edit") {
                        viewsFields[item].inlineEditSave();
                    }
                });
            }
        },

        createBottomView: function () {
            var el = this.options.el || '#' + (this.id);
            this.createView('bottom', this.bottomView, {
                model: this.model,
                scope: this.scope,
                el: el + ' .bottom',
                readOnly: this.readOnly,
                type: this.type,
                inlineEditDisabled: this.inlineEditDisabled,
                recordHelper: this.recordHelper,
                recordViewObject: this,
                portalLayoutDisabled: this.portalLayoutDisabled
            }, view => {
                this.listenToOnce(view, 'after:render', () => {
                    this.createPanelNavigationView(view.panelList);
                })
            });
        },

        createPanelNavigationView(panelList) {
            let el = this.options.el || '#' + (this.id);
            this.createView('panelNavigation', this.panelNavigationView, {
                panelList: panelList,
                model: this.model,
                scope: this.scope,
                el: el + ' .panel-navigation',
            }, function (view) {
                view.render();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            var $container = this.$el.find('.detail-button-container');

            var stickTop = this.getThemeManager().getParam('stickTop') || 62;
            var blockHeight = this.getThemeManager().getParam('blockHeight') || 21;

            var $block = $('<div>').css('height', blockHeight + 'px').html('&nbsp;').hide().insertAfter($container);
            var $window = $(window);
            var screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

            $window.off('scroll.detail-' + this.numId);
            $window.on('scroll.detail-' + this.numId, function (e) {
                if ($(window.document).width() < screenWidthXs) {
                    $container.removeClass('stick-sub');
                    $block.hide();
                    $container.show();
                    return;
                }

                var edge = this.$el.position().top + this.$el.outerHeight(true);
                var scrollTop = $window.scrollTop();

                if (scrollTop < edge) {
                    if (scrollTop > stickTop) {
                        $container.addClass('stick-sub');
                        $block.show();

                        var $p = $('.popover');
                        $p.each(function (i, el) {
                            var $el = $(el);
                            $el.css('top', ($el.position().top - blockHeight) + 'px');
                        });
                    } else {
                        $container.removeClass('stick-sub');
                        $block.hide();

                        var $p = $('.popover');
                        $p.each(function (i, el) {
                            var $el = $(el);
                            $el.css('top', ($el.position().top + blockHeight) + 'px');
                        });
                    }
                }
            }.bind(this));
        },

        setEditMode: function () {
            this.trigger('before:set-edit-mode');
            this.$el.find('.record-buttons').addClass('hidden');
            this.$el.find('.edit-buttons').removeClass('hidden');
            this.disableButtons();

            var fields = this.getFieldViews(true);
            var count = Object.keys(fields || {}).length;
            for (var field in fields) {
                var fieldView = fields[field];
                if (!fieldView.readOnly) {
                    if (fieldView.mode == 'edit') {
                        fieldView.fetchToModel();
                        fieldView.removeInlineEditLinks();
                    }
                    fieldView.setMode('edit');
                    fieldView.render(() => {
                        count--;
                        if (count === 0) {
                            this.enableButtons();
                        }
                    });
                } else {
                    count--;
                }
            }
            this.mode = 'edit';
            this.trigger('after:set-edit-mode');
        },

    });

});