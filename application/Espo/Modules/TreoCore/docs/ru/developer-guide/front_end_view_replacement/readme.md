# Front End View Replacement #
При помощи данного инструмента предоставляется возможность внести изменения в базовые views без их прямого редактирования.
### 1. Создание конфигурации ###
Для начала необходимо создать `clientClassReplaceMap.json` файл с необходимой конфигурацией, где указать какой view необходимо заменить и в каком модуле. 
Его нужно разместить в `/application/Espo/Modules/{MODULE_NAME}/metadata/app/clientClassReplaceMap.json`
#### Структура файла ####
```
{
    "{VIEW_PATH}": [
        "{MODULE_NAME}"
    ]
}
```
Где:
* {VIEW_PATH} - путь к базовой view, в которой необходимо произвести изменения
* {MODULE_NAME} - модуль, в рамках которого будет производиться замена 
### 2. Создание новой view ###
Создаём view по пути {VIEW_PATH}.
При её определении необходимо придерживаться такой структуры в её названии и названии view, от которой происходит наследование:
```
Espo.define('{MODULE_NAME}:{VIEW_PATH}', 'class-replace!{MODULE_NAME}:{VIEW_PATH}', function (Dep) {
    return Dep.extend({
        //необходимые изменения
    });
});
```
### 3. Пример ###
#### 1. Создаём конфигурацию ####
Создаём файл конфигурации, где указываем какие views необходимо заменить. 
`/application/Espo/Modules/TreoCore/Resources/metadata/app/clientClassReplaceMap.json`
```
{
    "views/record/base": [
        "treo-core"
    ],
    "views/record/list": [
        "treo-core"
    ]
}
```
#### 2. Создаём views: ####
Новый view для base:
`/client/modules/treo-core/src/views/record/base.js`
Содержание:
```
Espo.define('treo-core:views/record/base', 'class-replace!treo-core:views/record/base', function (Dep) {
    return Dep.extend({
        populateDefaults: function () {
            Dep.prototype.populateDefaults.call(this);
            this.model.populateDefaults();
            let defaultHash = {};
            if (!this.getUser().get('portalId')) {
                if (this.model.hasField('ownerUser')) {
                    let fillOwnerUser = true;
                    if (this.getPreferences().get('doNotFillOwnerUserIfNotRequired')) {
                        fillOwnerUser = false;
                        if (this.model.getFieldParam('ownerUser', 'required')) {
                            fillOwnerUser = true;
                        }
                    }
                    if (fillOwnerUser) {
                        defaultHash['ownerUserId'] = this.getUser().id;
                        defaultHash['ownerUserName'] = this.getUser().get('name');
                    }
                }
            }
            for (let attr in defaultHash) {
                if (this.model.has(attr)) {
                    delete defaultHash[attr];
                }
            }
            this.model.set(defaultHash, {silent: true});
        },
    });
});
```
Новый view для list:
`/client/modules/treo-core/src/views/record/list.js`
Содержание:
```
Espo.define('treo-core:views/record/list', 'class-replace!treo-core:views/record/list', function (Dep) {
    return Dep.extend({
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
        }
    });
});
```