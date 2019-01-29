<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Upgrade' scope='Admin'}}</h3></div>

<div class="panel panel-default">
    <div class="panel-body">
        <h3>Web upgrading is disabled for current version</h3>
        <p>For next upgradings you shoud execute few commands from CLI (<b>PHP7.1</b> is required):</p>

        <p>1. Force upgrade of core files</p>
        <pre>/usr/bin/php console.php upgrade 3.0.1 --force</pre>
        <p>2. Run migration</p>
        <pre>/usr/bin/php console.php migrate TreoCore 2.9.9 3.0.1</pre>
        <p>3. Update dependencies</p>
        <pre>/usr/bin/php composer.phar update --no-dev</pre>
        <p>4. Make cron handler file executable</p>
        <pre>chmod +x bin/cron.sh </pre>
        <p>5. Reconfigure crontab</p>
        <pre>* * * * * cd /var/www/treopim; ./bin/cron.sh process-treopim /usr/bin/php </pre>
        <p>Parameters:</p>
        <p><b>/var/www/treopim</b> - path to project root</p>
        <p><b>process-treopim</b> - an unique id of process. You should use different process ID if you had few TreoPIM project in one server</p>
        <p><b>/usr/bin/php</b> - PHP7.1</p>
    </div>
</div>
