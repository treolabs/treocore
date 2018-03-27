<a href="javascript:" class="notifications-button" data-action="showProgress">
    <span class="glyphicon glyphicon-tasks"></span>
</a>
<div class="progress-panel-container"></div>
<style>
    .progress-panel-container {
        position: absolute;
        width: 700px;
        z-index: 1001;
        right: 0;
        left: auto
    }

    .progress-panel-container > .panel {
        border-width: 1px
    }

    .progress-panel-container > .panel > .panel-body {
        max-height: 350px;
        overflow-y: auto;
        overflow-x: hidden
    }

    .progress-panel-container > .panel > .panel-body .list > table td {
        white-space: normal;
        text-overflow: initial;
        overflow: visible;
    }

    .progress-panel-container .progress-manager-action {
        display: inline-block;
        min-width: 90px;
    }

    @media screen and (max-width: 768px) {
        .progress-panel-container {
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            margin-right: 10px
        }
    }

    @media screen and (max-width: 768px) {
        .progress-panel-container .list {
            overflow-x: initial;
        }
        .progress-panel-container .list > table {
            min-width: initial;
        }
    }

</style>