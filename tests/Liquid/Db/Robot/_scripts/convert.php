<?php

echo 'Starting conversion script...';
$dbConverter = new Liquid_Db_Robot_Converter($xmlFilename);
echo " ok\n";

echo 'Parsing tables...';
$dbConverter->parseTables();
echo " ok\n";

echo 'Dropping tables...';
$dbConverter->dropTables(in_array('export', $options), in_array('import', $options));
echo " ok\n";

echo 'Creating tables...';
$dbConverter->createTables(in_array('export', $options), in_array('import', $options));
echo " ok\n";

if(in_array('import', $options)) {
    echo 'Import data...';
    $dbConverter->importTables();
    echo " ok\n";
}

if(in_array('export', $options)) {
    echo 'Export data...';
    $dbConverter->exportTables();
    echo " ok\n";
}

echo "\n".count($dbConverter->myTables)." tables done :-)\n\n";
