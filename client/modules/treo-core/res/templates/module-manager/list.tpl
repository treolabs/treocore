<div class="page-header">{{{header}}}</div>
<div class="search-container">{{{search}}}</div>
<div class="panel panel-default panel-channelProducts" style="margin-left: -15px; margin-right: -15px;">
    <div class="panel-heading">
        <h4 class="panel-title">
            <span style="cursor: pointer;" class="action" title="{{translate 'clickToRefresh' category='messages'}}" data-action="refresh" data-collection="installed">{{translate 'Modules (installed)' scope='ModuleManager' category='labels'}}</span>
        </h4>
    </div>
    <div class="panel-body">
        <div class="list-container modules-installed">{{{list}}}</div>
    </div>
</div>
<div class="panel panel-default panel-channelProducts" style="margin-left: -15px; margin-right: -15px;">
    <div class="panel-heading">
        <h4 class="panel-title">
            <span style="cursor: pointer;" class="action" title="{{translate 'clickToRefresh' category='messages'}}" data-action="refresh" data-collection="available">{{translate 'Modules (available)' scope='ModuleManager' category='labels'}}</span>
        </h4>
    </div>
    <div class="panel-body">
        <div class="list-container modules-available">{{{listAvailable}}}</div>
    </div>
</div>