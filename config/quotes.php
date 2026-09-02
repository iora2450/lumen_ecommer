<?php

return [
    'notification_emails' => array_values(array_filter(array_map(
        'trim',
        preg_split('/[,;]+/', (string) env('QUOTE_NOTIFICATION_EMAILS', 'douglas.manzanares@lumens.com.sv')) ?: []
    ))),
];
