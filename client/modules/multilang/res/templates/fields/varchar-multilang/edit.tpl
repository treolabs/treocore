<input type="text" class="main-element form-control" name="{{name}}" value="{{value}}" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}} autocomplete="off">
{{#each valueList}}
<label class="control-label" data-name="{{name}}">
    <span class="label-text">{{#if customLabel}}{{customLabel}}{{else}}{{translate ../../name category='fields' scope=../../scope}}{{/if}} &rsaquo; {{shortLang}}</span>
    <span class="required-sign"> *</span>
</label>
<input type="text" class="main-element form-control" name="{{name}}" value="{{value}}" {{#if ../params.maxLength}} maxlength="{{../../params.maxLength}}"{{/if}} autocomplete="off">
{{/each}}
