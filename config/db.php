<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../priv/Info.php';

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=cottage',
    'username' => \app\priv\Info::DB_USER,
    'password' => \app\priv\Info::DB_PASSWORD,
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
