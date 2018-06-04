<?php
/**
 * Created by IntelliJ IDEA.
 * User: jan.schumann
 * Date: 03.06.18
 * Time: 22:56
 */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use SchumannIt\DBAL\Schema\Converter\ConverterChain;
use SchumannIt\DBAL\Schema\Converter\CopyConverter;
use SchumannIt\DBAL\Schema\Converter\DoctrineConverter;
use SchumannIt\DBAL\Schema\Converter\RenamePrimaryKeyIfSingleColumnIndex;
use SchumannIt\DBAL\Schema\Mapping;
use SchumannIt\DBAL\Schema\Migration;

require_once 'vendor/autoload.php';

$source = DriverManager::getConnection(array(
    'dbname' => 'sde_original',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
    'user' => 'evetoolkit',
    'password' => 'master',
    'port' => 3306,
), new Configuration());

$target = DriverManager::getConnection(array(
    'dbname' => 'sde',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
    'user' => 'evetoolkit',
    'password' => 'master',
    'port' => 3306,
), new Configuration());

$mapping = new Mapping();
$chain = new ConverterChain($mapping);
$chain->add(new DoctrineConverter());
$chain->add(new RenamePrimaryKeyIfSingleColumnIndex());
$mig = new Migration($source, $target, $chain);

//if ($mig->hasChanges()) {
    //var_dump($mig->getChangesSql());
    //$mig->applyChanges();
//}
//else {
    $mig->migrateData();
//}
