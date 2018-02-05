<div class="link-container list-group" data-name="{{name}}">
    {{#each itemHtmlList}}
        {{{./this}}}
    {{/each}}
</div>
<div class="array-control-container">
    {{#if hasOptions}}
        <button class="btn btn-default btn-block" type="button" data-action="showAddModal" data-name="{{../name}}">{{translate 'Add'}}</button>
    {{else}}
        <input class="main-element form-control select" type="text" data-name="{{../name}}" autocomplete="off" placeholder="{{#if this.options}}{{translate 'Select'}}{{else}}{{translate 'typeAndPressEnter' category='messages'}}{{/if}}">
    {{/if}}
</div>

{{#each valueList}}
    <label class="control-label" data-name="{{name}}">
        <span class="label-text">{{#if customLabel}}{{customLabel}}{{else}}{{translate ../../name category='fields' scope=../../scope}}{{/if}} &rsaquo; {{shortLang}}</span>
        <span class="required-sign"> *</span>
    </label>

    <div class="link-container list-group" data-name="{{name}}">
        {{#each itemHtmlList}}
            {{{./this}}}
        {{/each}}
    </div>
    <div class="array-control-container">
        {{#if hasOptions}}
            <button class="btn btn-default btn-block" type="button" data-action="showAddModal" data-name="{{../name}}">{{translate 'Add'}}</button>
        {{else}}
            <input class="main-element form-control select" type="text" data-name="{{../name}}" autocomplete="off" placeholder="{{#if this.options}}{{translate 'Select'}}{{else}}{{translate 'typeAndPressEnter' category='messages'}}{{/if}}">
        {{/if}}
    </div>
{{/each}}