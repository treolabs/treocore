<div class="search-container">{{{search}}}</div>
<div class="entity-container clearfix">
    <div class="cell form-group col-sm-6 col-xs-12" data-name="entitySelect">
        <label class="control-label" data-name="entitySelect"><span class="label-text">{{translate 'entitySelect' category='labels' scope='Product'}}</span></label>
        <div class="field" data-name="entitySelect">
            {{{entitySelect}}}
        </div>
    </div>
</div>
<div class="list-container">{{{list}}}</div>
{{#if createButton}}
<div class="button-container">
    <button class="btn btn-default" data-action="create">{{createText}}</button>
</div>
{{/if}}
