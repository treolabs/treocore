<style>
    .progress {
        height: 5px;
        margin-bottom: 5px;
    }
</style>
<div class="completeness general">
    {{#if isNotEmpty}}
    {{value}}%
    <div class="progress">
        <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:{{value}}%"></div>
    </div>
    {{else}}
    {{translate 'None'}}
    {{/if}}</div>
{{#each valueList}}
    <label class="control-label" data-name="{{name}}">
        <span class="label-text">{{#if customLabel}}{{customLabel}}{{else}}{{translate ../../name category='fields' scope=../../scope}}{{/if}} &rsaquo; {{shortLang}}</span>
    </label>
    <div class="completeness list-elem-{{index}}">
        {{#if isNotEmpty}}
        {{value}}%
        <div class="progress">
            <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:{{value}}%"></div>
        </div>
        {{else}}
        {{translate 'None'}}
        {{/if}}</div>
{{/each}}
