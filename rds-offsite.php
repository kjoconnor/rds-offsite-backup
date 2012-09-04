<?php

require_once('settings.php');

require_once('aws-sdk-for-php/sdk.class.php');

date_default_timezone_set('UTC');
ini_set("max_execution_time", "0");

$rds = new AmazonRDS(array('key' => $settings['AWS_ACCESS_KEY'],
							'secret' => $settings['AWS_SECRET_KEY']));

$snapshots = $rds->describe_db_snapshots(array('DBInstanceIdentifier' => $settings['DATABASE_IDENTIFIER'],
								'SnapshotType' => 'automated'));

if(!$snapshots->isOK()) {
	print "Couldn't get list of snapshots!\n";
	exit(1);
}

$latest_snapshot = NULL;

foreach($snapshots->body->DescribeDBSnapshotsResult->DBSnapshots->DBSnapshot as $snapshot) {
	if($latest_snapshot === NULL) {
		$latest_snapshot = $snapshot;
	} else if(strtotime($latest_snapshot->SnapshotCreateTime) < strtotime($snapshot->SnapshotCreateTime)) {
		$latest_snapshot = $snapshot;
	}
}

if($latest_snapshot === NULL) {
	print "Couldn't find a snapshot to restore from!\n";
	exit(2);
}

print_r($latest_snapshot);

$new_identifier = $settings['DATABASE_IDENTIFIER'] . "-offsite-" . base_convert(rand(1000000,9999999), 10, 36);

$new_instance = $rds->restore_db_instance_from_db_snapshot($latest_snapshot->DBSnapshotIdentifier,
	$new_identifier,
	array('DBInstanceClass' => $settings['INSTANCE_TYPE'],
		'MultiAZ' => 'false')
	);

print_r($new_instance);

$new_instance_status = $rds->describe_db_instances(array('DBInstanceIdentifier' => $new_identifier));

print_r($new_instance_status);

?>