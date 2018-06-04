<?php
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use SchumannIt\DBAL\Schema\Converter\ConverterChain;
use SchumannIt\DBAL\Schema\Converter\CopyConverter;
use SchumannIt\DBAL\Schema\Converter\DoctrineConverter;
use SchumannIt\DBAL\Schema\Converter\EnsureAutoIncrementPrimaryKeyConverter;
use SchumannIt\DBAL\Schema\Converter\RenamePrimaryKeyIfSingleColumnIndex;
use SchumannIt\DBAL\Schema\Mapping;
use SchumannIt\DBAL\Schema\Migration;

require_once 'vendor/autoload.php';

$source = DriverManager::getConnection(array(
    'dbname' => 'source',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
    'user' => 'root',
    'password' => 'master',
    'port' => 3306,
), new Configuration());

$target = DriverManager::getConnection(array(
    'dbname' => 'target',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
    'user' => 'root',
    'password' => 'master',
    'port' => 3306,
), new Configuration());

$mapping = new Mapping();
$chain = new ConverterChain($mapping);
$chain->add(new DoctrineConverter());
$chain->add(new EnsureAutoIncrementPrimaryKeyConverter());
$chain->add(new RenamePrimaryKeyIfSingleColumnIndex());
$mig = new Migration($source, $target, $chain);

if ($mig->hasChanges()) {
    echo "migrate schema\n";
    $mig->applyChanges();
}
else {
    echo "migrate data\n";
    $mig->migrateData();
}
