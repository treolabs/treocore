<div class="input-group">
    <input type="text" class="main-element form-control" name="{{name}}" value="{{value}}" autocomplete="off" pattern="[\-]?[0-9,.]*" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}>
    <span class="input-group-btn unit-container">
        <button class="btn btn-default" data-action="showUnitSelection">
            <span class="unit-value">{{#if unitValue}}{{unitValue}}{{else}}{{translate 'None'}}{{/if}}</span>
            <span class="fas fa-caret-square-right text-muted"></span>
        </button>
    </span>
</div>

