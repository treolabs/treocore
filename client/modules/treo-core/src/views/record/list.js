/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('treo-core:views/record/list', 'class-replace!treo-core:views/record/list', function (Dep) {

    return Dep.extend({

        template: 'treo-core:record/list',

        enabledFixedHeader: false,

        checkedAll: false,

        dragndropEventName: null,

        massRelationView: 'treo-core:views/modals/select-entity-and-records',

        setup() {
            Dep.prototype.setup.call(this);

            this.enabledFixedHeader = this.options.enabledFixedHeader || this.enabledFixedHeader;

            this.listenTo(this, 'after:save', () => {
                this.collection.fetch();
            });

            this.dragndropEventName = `resize.drag-n-drop-table-${this.cid}`;
            this.listenToOnce(this, 'remove', () => {
                $(window).off(this.dragndropEventName);
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

                    this.selectAllHandler(e.currentTarget.checked);
                    this.checkedAll = e.currentTarget.checked;
                },
            });
        },

        setupMassActionItems() {
            Dep.prototype.setupMassActionItems.call(this);

            let foreignEntities = this.getForeignEntities();
            if (foreignEntities.length) {
                this.massActionList = Espo.Utils.clone(this.massActionList);
                this.massActionList.push('addRelation');
                this.massActionList.push('removeRelation');
            }
        },

        getForeignEntities() {
            let foreignEntities = [];
            if (this.scope && this.getAcl().check(this.scope, 'edit')) {
                let links = this.getMetadata().get(['entityDefs', this.scope, 'links']) || {};
                let linkList = Object.keys(links).sort(function (v1, v2) {
                    return v1.localeCompare(v2);
                }.bind(this));

                linkList.forEach(link => {
                    let defs = links[link];

                    if (defs.foreign && defs.entity && this.getAcl().check(defs.entity, 'edit')) {
                        let foreignType = this.getMetadata().get(['entityDefs', defs.entity, 'links', defs.foreign, 'type']);
                        if (this.checkRelationshipType(defs.type, foreignType)
                            && this.getMetadata().get(['scopes', defs.entity, 'entity'])
                            && !this.getMetadata().get(['scopes', defs.entity, 'disableMassRelation'])
                            && !defs.disableMassRelation) {
                            let data = {
                                link: link,
                                entity: defs.entity,
                            };
                            if (defs.customDefs) {
                                data.customDefs = defs.customDefs;
                            }
                            foreignEntities.push(data);
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
            let foreignEntities = this.getForeignEntities();
            if (!foreignEntities.length) {
                return;
            }

            this.notify('Loading...');
            this.getModelFactory().create(null, model => {
                model.set({
                    mainEntity: this.scope,
                    selectedLink: foreignEntities[0].link,
                    foreignEntities: foreignEntities
                });

                let view = this.getMetadata().get(['clientDefs', this.scope, 'massRelationView']) || this.massRelationView;
                this.createView('dialog', view, {
                    model: model,
                    multiple: true,
                    createButton: false,
                    scope: (foreignEntities[0].customDefs || {}).entity || foreignEntities[0].entity,
                    type: type,
                    checkedList: this.checkedList
                }, view => {
                    view.render(() => {
                        this.notify(false);
                    });
                });
            });
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

            if (this.options.dragableListRows) {
                this.initDraggableList();
                $(window).off(this.dragndropEventName).on(this.dragndropEventName, () => {
                    this.initDraggableList();
                });
            }
        },

        initDraggableList() {
            if (this.getAcl().check(this.scope, 'edit')) {
                this.setCellWidth();

                this.$el.find(this.listContainerEl).sortable({
                    handle: window.innerWidth < 768 ? '.cell[data-name="draggableIcon"]' : false,
                    delay: 150,
                    update: function () {
                        this.saveListItemOrder();
                    }.bind(this)
                });
            }
        },

        setCellWidth() {
            let el = this.$el.find(this.listContainerEl);

            el.find('td').each(function (i) {
                $(this).css('width', $(this).outerWidth());
            });
        },

        saveListItemOrder() {
            let saveUrl = this.getListRowsOrderSaveUrl();
            if (saveUrl) {
                this.ajaxPutRequest(saveUrl, {ids: this.getIdsFromDom()})
                    .then(response => {
                        let statusMsg = 'Error occurred';
                        let type = 'error';
                        if (response) {
                            statusMsg = 'Saved';
                            type = 'success';
                        }
                        this.collection.trigger('listSorted');
                        this.notify(statusMsg, type, 3000);
                    });
            }
        },

        getListRowsOrderSaveUrl() {
            return this.options.listRowsOrderSaveUrl;
        },

        getIdsFromDom() {
            return $.map(this.$el.find(`${this.listContainerEl} tr`), function (item) {
                return $(item).data('id');
            });
        },

        _convertLayout: function (listLayout, model) {
            if (this.options.dragableListRows && listLayout && Array.isArray(listLayout) && !listLayout.find(item => item.name === 'draggableIcon')) {
                listLayout.unshift({
                    widthPx: '40',
                    align: 'center',
                    notSortable: true,
                    customLabel: '',
                    name: 'draggableIcon',
                    view: 'treo-core:views/fields/draggable-list-icon'
                });
            }

            return Dep.prototype._convertLayout.call(this, listLayout, model)
        },

        _getHeaderDefs() {
            let defs = Dep.prototype._getHeaderDefs.call(this);
            let model = this.collection.model.prototype;
            defs.forEach(item => {
                if (item.name && ['wysiwyg', 'wysiwygMultiLang'].includes(model.getFieldType(item.name))) {
                    item.sortable = false;
                }
            });
            return defs;
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
        },

        fetchAttributeListFromLayout() {
            let selectList = [];
            if (this.scope && !this.getMetadata().get(['clientDefs', this.scope, 'disabledSelectList'])) {
                selectList = Dep.prototype.fetchAttributeListFromLayout.call(this);
            }
            return selectList;
        },

        massActionMassUpdate: function () {
            if (!this.getAcl().check(this.entityType, 'edit')) {
                this.notify('Access denied', 'error');
                return false;
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));
            var ids = false;
            var allResultIsChecked = this.allResultIsChecked;
            if (!allResultIsChecked) {
                ids = this.checkedList;
            }

            this.createView('massUpdate', 'views/modals/mass-update', {
                scope: this.entityType,
                ids: ids,
                where: this.collection.getWhere(),
                selectData: this.collection.data,
                byWhere: this.allResultIsChecked
            }, function (view) {
                view.render();
                view.notify(false);
                view.once('after:update', function (count, byQueueManager) {
                    view.close();
                    this.listenToOnce(this.collection, 'sync', function () {
                        if (count) {
                            var msg = 'massUpdateResult';
                            if (count == 1) {
                                msg = 'massUpdateResultSingle'
                            }
                            Espo.Ui.success(this.translate(msg, 'messages').replace('{count}', count));
                        } else if (byQueueManager) {
                            Espo.Ui.success(this.translate('byQueueManager', 'messages', 'QueueItem'));
                            Backbone.trigger('showQueuePanel');
                        } else {
                            Espo.Ui.warning(this.translate('noRecordsUpdated', 'messages'));
                        }
                        if (allResultIsChecked) {
                            this.selectAllResult();
                        } else {
                            ids.forEach(function (id) {
                                this.checkRecord(id);
                            }, this);
                        }
                    }.bind(this));
                    this.collection.fetch();
                }, this);
            }.bind(this));
        },

        massActionRemove: function () {
            if (!this.getAcl().check(this.entityType, 'delete')) {
                this.notify('Access denied', 'error');
                return false;
            }

            var count = this.checkedList.length;
            var deletedCount = 0;

            var self = this;

            this.confirm({
                message: this.translate('removeSelectedRecordsConfirmation', 'messages'),
                confirmText: this.translate('Remove')
            }, function () {
                this.notify('Removing...');

                var ids = [];
                var data = {};
                if (this.allResultIsChecked) {
                    data.where = this.collection.getWhere();
                    data.selectData = this.collection.data || {};
                    data.byWhere = true;
                } else {
                    data.ids = ids;
                }

                for (var i in this.checkedList) {
                    ids.push(this.checkedList[i]);
                }

                $.ajax({
                    url: this.entityType + '/action/massDelete',
                    type: 'POST',
                    data: JSON.stringify(data)
                }).done(function (result) {
                    result = result || {};
                    var count = result.count;
                    var byQueueManager = result.byQueueManager;
                    if (this.allResultIsChecked) {
                        if (count) {
                            this.unselectAllResult();
                            this.listenToOnce(this.collection, 'sync', function () {
                                var msg = 'massRemoveResult';
                                if (count == 1) {
                                    msg = 'massRemoveResultSingle'
                                }
                                Espo.Ui.success(this.translate(msg, 'messages').replace('{count}', count));
                            }, this);
                            this.collection.fetch();
                            Espo.Ui.notify(false);
                        } else if (byQueueManager) {
                            Espo.Ui.success(this.translate('byQueueManager', 'messages', 'QueueItem'));
                            Backbone.trigger('showQueuePanel');
                        } else {
                            Espo.Ui.warning(self.translate('noRecordsRemoved', 'messages'));
                        }
                    } else {
                        var idsRemoved = result.ids || [];
                        if (count) {
                            idsRemoved.forEach(function (id) {
                                Espo.Ui.notify(false);

                                this.collection.trigger('model-removing', id);
                                this.removeRecordFromList(id);
                                this.uncheckRecord(id, null, true);

                            }, this);
                            var msg = 'massRemoveResult';
                            if (count == 1) {
                                msg = 'massRemoveResultSingle'
                            }
                            Espo.Ui.success(self.translate(msg, 'messages').replace('{count}', count));
                        } else if (byQueueManager) {
                            Espo.Ui.success(this.translate('byQueueManager', 'messages', 'QueueItem'));
                            Backbone.trigger('showQueuePanel');
                        } else {
                            Espo.Ui.warning(self.translate('noRecordsRemoved', 'messages'));
                        }
                    }
                }.bind(this));
            }, this);
        },

    });
});
