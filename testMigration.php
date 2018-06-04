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
use SchumannIt\DBAL\Schema\Converter\EnsureAutoIncrementPrimaryKeyConverter;
use SchumannIt\DBAL\Schema\Converter\RenamePrimaryKeyIfSingleColumnIndex;
use SchumannIt\DBAL\Schema\Mapping;
use SchumannIt\DBAL\Schema\Migration;

require_once 'vendor/autoload.php';

$source = DriverManager::getConnection(array(
    'dbname' => 'sde_original',
    'host' => '172.28.128.3',
    'driver' => 'pdo_mysql',
    'user' => 'evetoolkit',
    'password' => 'master',
    'port' => 3306,
), new Configuration());

$target = DriverManager::getConnection(array(
    'dbname' => 'sde',
    'host' => '172.28.128.3',
    'driver' => 'pdo_mysql',
    'user' => 'evetoolkit',
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
    if (array_key_exists(1, $argv)) {
        if ($argv[1] == 1) $mig->migrateData(["agtAgentTypes","agtAgents"]);
        if ($argv[1] == 2) $mig->migrateData(["agtResearchAgents","certCerts"]);
        if ($argv[1] == 3) $mig->migrateData(["certMasteries","certSkills"]);
        if ($argv[1] == 4) $mig->migrateData(["chrAncestries","chrAttributes"]);
        if ($argv[1] == 5) $mig->migrateData(["chrBloodlines","chrFactions"]);
        if ($argv[1] == 6) $mig->migrateData(["chrRaces","crpActivities"]);
        if ($argv[1] == 7) $mig->migrateData(["crpNPCCorporationDivisions","crpNPCCorporationResearchFields"]);
        if ($argv[1] == 8) $mig->migrateData(["crpNPCCorporationTrades","crpNPCCorporations"]);
        if ($argv[1] == 9) $mig->migrateData(["crpNPCDivisions","dgmAttributeCategories"]);
        if ($argv[1] == 10) $mig->migrateData(["dgmAttributeTypes","dgmEffects"]);
        if ($argv[1] == 11) $mig->migrateData(["dgmExpressions","dgmTypeAttributes"]);
        if ($argv[1] == 12) $mig->migrateData(["dgmTypeEffects","eveGraphics"]);
        if ($argv[1] == 13) $mig->migrateData(["eveIcons","eveUnits"]);
        if ($argv[1] == 14) $mig->migrateData(["industryActivity","industryActivityMaterials"]);
        if ($argv[1] == 15) $mig->migrateData(["industryActivityProbabilities","industryActivityProducts"]);
        if ($argv[1] == 16) $mig->migrateData(["industryActivityRaces","industryActivitySkills"]);
        if ($argv[1] == 17) $mig->migrateData(["industryBlueprints","invCategories"]);
        if ($argv[1] == 18) $mig->migrateData(["invContrabandTypes","invControlTowerResourcePurposes"]);
        if ($argv[1] == 19) $mig->migrateData(["invControlTowerResources","invFlags"]);
        if ($argv[1] == 20) $mig->migrateData(["invGroups","invItems"]);
        if ($argv[1] == 21) $mig->migrateData(["invMarketGroups","invMetaGroups"]);
        if ($argv[1] == 22) $mig->migrateData(["invMetaTypes","invNames"]);
        if ($argv[1] == 23) $mig->migrateData(["invPositions","invTraits"]);
        if ($argv[1] == 24) $mig->migrateData(["invTypeMaterials","invTypeReactions"]);
        if ($argv[1] == 25) $mig->migrateData(["invTypes","invUniqueNames"]);
        if ($argv[1] == 26) $mig->migrateData(["invVolumes","mapCelestialStatistics"]);
        if ($argv[1] == 27) $mig->migrateData(["mapConstellationJumps","mapConstellations"]);
        if ($argv[1] == 28) $mig->migrateData(["mapDenormalize","mapJumps"]);
        if ($argv[1] == 29) $mig->migrateData(["mapLandmarks","mapLocationScenes"]);
        if ($argv[1] == 30) $mig->migrateData(["mapLocationWormholeClasses","mapRegionJumps"]);
        if ($argv[1] == 31) $mig->migrateData(["mapRegions","mapSolarSystemJumps"]);
        if ($argv[1] == 32) $mig->migrateData(["mapSolarSystems","mapUniverse"]);
        if ($argv[1] == 33) $mig->migrateData(["planetSchematics","planetSchematicsPinMap"]);
        if ($argv[1] == 34) $mig->migrateData(["planetSchematicsTypeMap","ramActivities"]);
        if ($argv[1] == 35) $mig->migrateData(["ramAssemblyLineStations","ramAssemblyLineTypeDetailPerCategory"]);
        if ($argv[1] == 36) $mig->migrateData(["ramAssemblyLineTypeDetailPerGroup","ramAssemblyLineTypes"]);
        if ($argv[1] == 37) $mig->migrateData(["ramInstallationTypeContents","skinLicense"]);
        if ($argv[1] == 38) $mig->migrateData(["skinMaterials","skinShip"]);
        if ($argv[1] == 39) $mig->migrateData(["skins","staOperationServices"]);
        if ($argv[1] == 40) $mig->migrateData(["staOperations","staServices"]);
        if ($argv[1] == 41) $mig->migrateData(["staStationTypes","staStations"]);
        if ($argv[1] == 42) $mig->migrateData(["translationTables","trnTranslationColumns"]);
        if ($argv[1] == 43) $mig->migrateData(["trnTranslationLanguages","trnTranslations"]);
        if ($argv[1] == 44) $mig->migrateData(["warCombatZoneSystems","warCombatZones"]);
    }
    else {
        //$mig->generateMigDataCommands();
        $mig->getMigratedRows();
    }
}
