<?php

$settings['AWS_ACCESS_KEY'] = "";
$settings['AWS_SECRET_KEY'] = "";

$settings['DATABASE_IDENTIFIER'] = "my-database-server";
$settings['DATABASE_USERNAME'] = "awsadmin";
$settings['DATABASE_PASSWORD'] = "password";
$settings['DATABASE_NAMES'] = array('database01');

$settings['EXCLUDED_TABLES'] = array('database01.sessions', 'database01.garbage');

$settings['DRY_RUN'] = TRUE;

$settings['INSTANCE_TYPE'] = 'db.m1.xlarge';

$settings['ALLOWED_IPS'] = array('172.17.0.0/16');

?>