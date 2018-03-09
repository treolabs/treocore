{{#each gridLayout}}
<div data-group="{{id}}">
    <div class="row"><div class="col-sm-12"><b>{{label}}</b></div></div>
    {{#each rows}}
    <div class="row">
        {{#each this}}
        <div class="cell col-sm-6 form-group attribute-cell" data-name="{{name}}">
            {{#if isCustom}}<a href="javascript:" style="display: none;" class="pull-right inline-remove-link" data-name="{{../name}}"><span class="glyphicon glyphicon-remove"></span></a>{{/if}}
            <label class="control-label" data-name="{{name}}">
                <span class="label-text">{{label}}</span>
            </label>
            <div class="field" data-name="{{name}}"></div>
        </div>
        {{/each}}
    </div>
    {{/each}}
</div>
{{/each}}
<style>
    .attribute-cell:hover .inline-remove-link {
        display: inline !important;
    }
</style>