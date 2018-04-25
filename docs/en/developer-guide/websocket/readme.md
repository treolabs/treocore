# Configure Websocket #
For configure Websocket you should edit ```data/config.php```

### Example: ###
```
    ...
    'websockets'    => [
        'server'       => [
            'host'    => '127.0.0.1',
            'port'    => 8080,
            'address' => '0.0.0.0'
        ],
        'zmq'          => [
            'host' => '127.0.0.1',
            'port' => 5555,
        ],
    ],
    ...
```

The example shows the default configuration.