<style>
    div.completeness {
        display: inline-block;
        float: left;
        clear: left;
        padding: 0 5px;
        border: 1px solid white;
        border-radius: 25px;
    }
    div.completeness label span {
        color: #000;
    }
    .green {
        background-color: #b3ffb3;
    }
    .orange {
        background-color: #ffc966
    }
    .red {
        background-color: #ff8080;
    }
</style>
<div class="completeness general">{{#if isNotEmpty}}{{value}}%{{else}}{{translate 'None'}}{{/if}}</div>
{{#each valueList}}
    <div class="completeness list-elem-{{index}}">
        <label class="control-label" data-name="{{name}}">
            <span class="label-text">{{shortLang}}:</span>
        </label>
        <span>{{#if isNotEmpty}}{{value}}%{{else}}{{translate 'None'}}{{/if}}</span>
    </div>
{{/each}}
