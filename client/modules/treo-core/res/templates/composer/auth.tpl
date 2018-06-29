<div class="header page-header">
    <h3>
        <a href="#Admin">{{translate 'Administration' scope='Global' category='labels'}}</a> &rsaquo; {{translate 'Git Authentication' scope='Admin' category='labels'}}
    </h3>
</div>
<div class="record">
    <div class="edit">
        <div class="detail-button-container button-container clearfix">
            <div class="btn-group" role="group">
                <button class="btn btn-primary action" data-action="save" type="button">{{translate 'Save'}}</button>
                <button class="btn btn-default action" data-action="cancelEdit" type="button">{{translate 'Cancel'}}</button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <div class="middle">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">{{translate 'Git Authentication' scope='Admin' category='labels'}}</h4>
                        </div>
                        <div class="panel-body">
                            <div class="row">
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
            </div>
        </div>
    </div>
</div>