<a href="javascript:" class="notifications-button" data-action="showQueue">
    <span class="glyphicon glyphicon-tasks"></span>
</a>
<div class="queue-panel-container"></div>
<style>
    .queue-panel-container {
        position: absolute;
        width: 700px;
        z-index: 1001;
        right: 0;
        left: auto
    }

    .queue-panel-container > .panel {
        border-width: 1px
    }

    .queue-panel-container > .panel > .panel-body {
        max-height: 350px;
        overflow-y: auto;
        overflow-x: hidden
    }

    .queue-panel-container > .panel > .panel-body .list > table td {
        white-space: normal;
        text-overflow: initial;
        overflow: visible;
    }

    .queue-panel-container .queue-manager-action {
        display: inline-block;
        min-width: 90px;
    }

    @media screen and (max-width: 768px) {
        .queue-panel-container {
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            margin-right: 10px
        }
    }

    @media screen and (max-width: 768px) {
        .queue-panel-container .list {
            overflow-x: initial;
        }
        .queue-panel-container .list > table {
            min-width: initial;
        }
    }

</style>