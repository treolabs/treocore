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

        enabledFixedHeader: false,

        checkedAll: false,

        setup() {
            Dep.prototype.setup.call(this);

            this.enabledFixedHeader = this.options.enabledFixedHeader || this.enabledFixedHeader;

            this.listenTo(this, 'after:save', () => {
                this.collection.fetch();
            });

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

                'click tr': function (e) {
                    if (e.target.tagName === 'TD') {
                        let $target = $(e.currentTarget);
                        let id = $target.data('id');
                        let checkbox = this.$el.find($(e.currentTarget).find('.record-checkbox')).get(0);
                        if (checkbox) {
                            if (!checkbox.checked) {
                                this.checkRecord(id);
                            } else {
                                this.uncheckRecord(id);
                            }
                        }
                    }
                },

                'click .select-all': function (e) {
                    let checkbox = this.$el.find('.full-table').find('.select-all');
                    let checkboxFixed = this.$el.find('.fixed-header-table').find('.select-all');

                    if (!this.checkedAll) {
                        checkbox.prop('checked', true);
                        checkboxFixed.prop('checked', true);
                    } else {
                        checkbox.prop('checked', false);
                        checkboxFixed.prop('checked', false);
                    }

                    this.checkedList = [];

                    if (e.currentTarget.checked) {
                        this.$el.find('input.record-checkbox').prop('checked', true);
                        this.$el.find('.actions-button').removeAttr('disabled');
                        this.collection.models.forEach(function (model) {
                            this.checkedList.push(model.id);
                        }, this);

                        this.$el.find('.list > table tbody tr').addClass('active');

                        this.checkedAll = true;
                    } else {
                        if (this.allResultIsChecked) {
                            this.unselectAllResult();
                        }
                        this.$el.find('input.record-checkbox').prop('checked', false);
                        this.$el.find('.actions-button').attr('disabled', true);
                        this.$el.find('.list > table tbody tr').removeClass('active');

                        this.checkedAll = false;
                    }
                },
            });
        },

        setupMassActionItems() {
            Dep.prototype.setupMassActionItems.call(this);

            let foreignEntities = this.getForeignEntities(this.scope);
            if (foreignEntities.length) {
                this.massActionList = Espo.Utils.clone(this.massActionList);
                this.massActionList.push('addRelation');
                this.massActionList.push('removeRelation');
            }
        },

        getForeignEntities(mainEntity) {
            let foreignEntities = [];

            if (this.getAcl().check(this.scope, 'edit')) {
                let links = this.getMetadata().get(['entityDefs', mainEntity, 'links']) || {};
                let linkList = Object.keys(links).sort(function (v1, v2) {
                    return v1.localeCompare(v2);
                }.bind(this));

                linkList.forEach(link => {
                    let defs = links[link];

                    if (defs.foreign && defs.entity && this.getAcl().check(defs.entity, 'edit')) {
                        let foreignType = this.getMetadata().get(['entityDefs', defs.entity, 'links', defs.foreign, 'type']);
                        if (this.checkRelationshipType(defs.type, foreignType) && this.getMetadata().get(['scopes', defs.entity, 'entity'])) {
                            foreignEntities.push(links[link].entity);
                        }
                    }
                });
            }

            return foreignEntities;
        },

        checkRelationshipType: function (type, foreignType) {
            if (type === 'hasMany') {
                if (foreignType === 'hasMany') {
                    return 'manyToMany';
                } else if (foreignType === 'belongsTo') {
                    return 'oneToMany';
                }
            }
        },

        massActionUpdateRelation(type) {
            let foreignEntities = this.getForeignEntities(this.scope);
            if (!foreignEntities.length) {
                return;
            }

            this.notify('Loading...');
            this.getModelFactory().create(null, model => {
                model.set({
                    entitySelect: foreignEntities[0],
                    foreignEntities: foreignEntities
                });

                this.createView('dialog', 'treo-core:views/modals/select-entity-and-records', {
                    model: model,
                    multiple: true,
                    createButton: false,
                    scope: foreignEntities[0],
                    headerLabel: type
                }, view => {
                    view.render(() => {
                        this.notify(false);
                    });
                    view.listenTo(view, 'select', params => {
                        let foreignIds = [];
                        params.forEach(model => foreignIds.push(model.id));
                        let data = {
                            ids: this.checkedList,
                            foreignIds: foreignIds
                        };
                        let links = this.getMetadata().get(['entityDefs', this.scope, 'links']) || {};
                        let foreignEntity = Object.keys(links).find(link => links[link].entity === view.model.get('entitySelect'));
                        let url = `${this.scope}/${foreignEntity}/relation`;
                        this.sendDataForUpdateRelation(type, url, data);
                    });
                });
            });
        },

        sendDataForUpdateRelation(type, url, data) {
            if (type === 'addRelation') {
                this.ajaxPostRequest(url, data).then(response => {
                    this.notify('Linked', 'success');
                });
            } else if (type === 'removeRelation') {
                data = JSON.stringify(data);
                this.ajaxRequest(url, 'DELETE', data).then(response => {
                    this.notify('Unlinked', 'success');
                });
            }
        },

        massActionAddRelation() {
            this.massActionUpdateRelation('addRelation');
        },

        massActionRemoveRelation() {
            this.massActionUpdateRelation('removeRelation');
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.enabledFixedHeader) {
                this.fixedTableHead()
            }

            this.changeDropDownPosition();
        },

        fixedTableHead() {

            let $window = $(window),
                fixedTable = this.$el.find('.fixed-header-table'),
                fullTable = this.$el.find('.full-table'),
                navBarRight = $('.navbar-right'),
                posLeftTable = 0,
                navBarHeight = 0,

                setPosition = () => {
                    posLeftTable = fullTable.offset().left;
                    navBarHeight = navBarRight.outerHeight();

                    fixedTable.css({
                        'position': 'fixed',
                        'left': posLeftTable,
                        'top': navBarHeight - 1,
                        'right': 0,
                        'z-index': 1
                    });
                },
                setWidth = () => {
                    let widthTable = fullTable.outerWidth();

                    fixedTable.css('width', widthTable);

                    fullTable.find('thead').find('th').each(function (i) {
                        let width = $(this).outerWidth();
                        fixedTable.find('th').eq(i).css('width', width);
                    });
                },
                toggleClass = () => {
                    let showPosition = fullTable.offset().top;

                    if ($window.scrollTop() > showPosition && $window.width() >= 768) {
                        fixedTable.removeClass('hidden');
                    } else {
                        fixedTable.addClass('hidden');
                    }
                };

            if (fullTable.length) {
                setPosition();
                setWidth();
                toggleClass();

                $window.on('scroll', toggleClass);
                $window.on('resize', function () {
                    setPosition();
                    setWidth();
                });
            }
        },

        changeDropDownPosition() {
            let el = this.$el;

            el.on('show.bs.dropdown', function (e) {
                let target = e.relatedTarget,
                    menu = $(target).siblings('.dropdown-menu'),
                    menuHeight = menu.height(),
                    pageHeight = $(document).height(),
                    positionTop = $(target).offset().top + $(target).outerHeight(true);

                if ((positionTop + menuHeight) > pageHeight) {
                    menu.css({
                        'top': `-${menuHeight}px`
                    })
                }
            });

            el.on('hide.bs.dropdown', function (e) {
                let target = e.relatedTarget,
                    menu = $(target).next('.dropdown-menu');

                menu.removeAttr('style');
            });
        }

    });
});
