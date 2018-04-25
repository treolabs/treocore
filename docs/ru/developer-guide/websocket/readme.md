# Настройка Websocket #
Для настройки Websocket достаточно отредактировать ```data/config.php```

### Пример настройки Websocket в data/config.php ###
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

В примере указаны конфигурации по умолчанию.