<div class="input-group">
    <select name="{{name}}" class="form-control main-element">
        {{options params.options value scope=scope field=name translatedOptions=translatedOptions}}
    </select>
    <div class="input-group-btn">
        <button type="button" class="btn btn-default" data-name="{{name}}" data-action="editOptions">
            <span class="fas fa-pencil-alt fa-sm"></span>
        </button>
    </div>
</div>