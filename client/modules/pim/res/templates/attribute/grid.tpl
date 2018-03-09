{{#if gridLayout}}
{{#each gridLayout}}
<div data-group="{{id}}">
    <div class="row"><div class="col-sm-12"><b>{{label}}</b></div></div>
    {{#each rows}}
    <div class="row">
        {{#each this}}
        <div class="cell col-sm-6 form-group attribute-cell" data-name="{{name}}">
            {{#if isCustom}}<a href="javascript:" style="padding-left: 5px;" class="pull-right inline-remove-link hidden" data-name="{{../name}}"><span class="glyphicon glyphicon-remove"></span></a>{{/if}}
            <a href="javascript:" class="pull-right inline-edit-link edit-attribute hidden" data-name="{{name}}"><span class="glyphicon glyphicon-pencil"></span></a>
            <a href="javascript:" class="pull-right inline-cancel-link hidden">{{translate 'Cancel'}}</a>
            <a href="javascript:" class="pull-right inline-save-link hidden">{{translate 'Update'}}</a>
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
{{else}}
{{translate 'No Data'}}
{{/if}}