<style>
    .progress.progress-field {
        height: 5px;
        margin-bottom: 5px;
    }
</style>
{{value}}%
<div class="progress progress-field">
    <div class="progress-bar {{stateClass}}" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:{{value}}%"></div>
</div>