#config.mq.php 配置(这里是amqp的配置示例)
`
<?php
return [
    'queue_type' => 'amqp',
    'amqp_server' => [
        'host' => '172.18.107.96',
        'port' => 5672,
        'login' => 'guest',
        'password' => 'guest',
        'vhost' => '/',
        'exchange_name' => 'amq.direct',
        'exchange_type' => 'direct'
    ]
];
`