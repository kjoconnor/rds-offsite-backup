<?php

require_once('settings.php');

require_once('aws-sdk-for-php/sdk.class.php');

date_default_timezone_set('UTC');
ini_set("max_execution_time", "0");

$rds = new AmazonRDS(array('key' => $settings['AWS_ACCESS_KEY'],
							'secret' => $settings['AWS_SECRET_KEY']));

$new_identifier = $settings['DATABASE_IDENTIFIER'] . "-offsite-" . rand(1000,9999);

$restore = $rds->restore_db_instance_to_point_in_time($settings['DATABASE_IDENTIFIER'],
	$new_identifier,
	array('UseLatestRestorableTime' => 'true',
		'DBInstanceClass' => $settings['INSTANCE_TYPE'],
		'MultiAZ' => 'false'));

$new_identifier = 'readability-production-dbserver-1-offsite-9043';

// print_r($restore);

//if restore->isOK
if(!$restore->isOK()) {
	print_r($restore);
	print "Unable to restore DB - " . $new_identifier . "\n";
	exit(1);
}

$status = $rds->describe_db_instances(array('DBInstanceIdentifier' => $new_identifier));

while($status->body->DescribeDBInstancesResult->DBInstances->DBInstance->DBInstanceStatus !== 'available') {
	print "Waiting for instance to become available, status is currently " .
	$status->body->DescribeDBInstancesResult->DBInstances->DBInstance->DBInstanceStatus . "\n";
	sleep(30);
	$status = $rds->describe_db_instances(array('DBInstanceIdentifier' => $new_identifier));
}

$create_sg = $rds->create_db_security_group($new_identifier . '-sg', 'Automated SG created for offsite backup.');

if(!$create_sg->isOK()) {
	print_r($create_sg);
	print "Unable to create SG - " . $new_identifier . "-sg\n";
	exit(2);
}

foreach($settings['ALLOWED_IPS'] as $ip) {
	$modify_sg = $rds->authorize_db_security_group_ingress($new_identifier . "-sg", array(
		'CIDRIP' => $ip));

	if(!$modify_sg->isOK()) {
		print_r($modify_sg);
		print "Unable to modify SG - " . $new_identifier . "-sg, " . $ip . "\n";
		exit(3);
	}
}

$change_status = $rds->modify_db_instance($new_identifier, array('DBSecurityGroups' => $new_identifier . '-sg',
	'ApplyImmediately' => 'false',
	'BackupRetentionPeriod' => 0,
	'MultiAZ' => 'false',
	'AutoMinorVersionUpgrade' => 'false'));

if(!$change_status->isOK()) {
	print_r($change_status);
	print "Unable to modify db instance - " . $new_identifier . "\n";
	exit(4);
}

// Get hostname here
$hostname = $status;

$excluded_tables = '';
if(isset($settings['EXCLUDED_TABLES'])) {
	foreach($settings['EXCLUDED_TABLES'] as $excluded_table) {
		if(!strstr($excluded_table, '.')) {
			print $excluded_table . " is in the wrong format, should be database.table\n";
		} else {
			$excluded_tables .= ' --ignore-table=' . $excluded_table;
		}
	}
}

?>