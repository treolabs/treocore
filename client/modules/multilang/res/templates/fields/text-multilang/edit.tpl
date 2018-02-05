<textarea class="main-element form-control" name="{{name}}" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}} {{#if params.rows}} rows="{{params.rows}}"{{/if}}>{{value}}</textarea>
{{#each valueList}}
    <label class="control-label" data-name="{{name}}">
        <span class="label-text">{{#if customLabel}}{{customLabel}}{{else}}{{translate ../../name category='fields' scope=../../scope}}{{/if}} &rsaquo; {{shortLang}}</span>
        <span class="required-sign "> *</span>
    </label>
    <textarea class="main-element form-control" name="{{name}}" {{#if ../params.maxLength}} maxlength="{{../../params.maxLength}}"{{/if}} {{#if ../params.rows}} rows="{{../../params.rows}}"{{/if}}>{{value}}</textarea>
{{/each}}


