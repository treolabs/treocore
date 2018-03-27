<div class="row">
    <div class="detail-button-container button-container clearfix" style="padding-left: 15px;">
        <div class="btn-group pull-left" role="group">
            <button class="btn btn-primary action {{#unless isDetailMode}}hidden{{/unless}}" data-action="edit" type="button">{{translate 'Edit'}}</button>
            <button class="btn btn-primary action {{#if isDetailMode}}hidden{{/if}}" data-action="save" type="button">{{translate 'Save'}}</button>
            <button class="btn btn-default action {{#if isDetailMode}}hidden{{/if}}" data-action="cancelEdit" type="button">{{translate 'Cancel'}}</button>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">{{translate 'settings' scope='ModuleManager' category='labels'}}</h4>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-xs-12 col-sm-6">{{translate 'settingsText' scope='ModuleManager' category='labels'}}</div>
                <div class="col-xs-12 col-sm-6">
                    <div class="row">
                        <div class="cell col-xs-12 form-group" data-name="username">
                            <label class="control-label" data-name="username">
                                <span class="label-text">{{translate 'username' scope='ModuleManager' category='labels'}}</span>
                            </label>
                            <div class="field" data-name="username">{{{username}}}</div>
                        </div>
                        <div class="cell col-xs-12 form-group" data-name="password">
                            <label class="control-label" data-name="password">
                                <span class="label-text">{{translate 'password' scope='ModuleManager' category='labels'}}</span>
                            </label>
                            <div class="field" data-name="password">{{{password}}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>