<?php
// Affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Force le cache
if (function_exists('opcache_reset')) {
    opcache_reset();
}

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/B2B.php';
require_once __DIR__ . '/Utils.php';
require_once __DIR__ . '/JsonComparator.php';

// Exemple d'utilisation
try {
    $today = B2B::getArticleAt();
    $jsonA = B2B::getArticleAt(new DateTime('2025-02-14'));
    $jsonB = B2B::getArticleAt(new DateTime('2025-02-17'));
    //B2B::saveFile(ARTICLE, $json, $date);
    //$data    = B2B::setStockForToday();

    $comparator = new JsonComparator($jsonA["articles"], $jsonB["articles"]); // 'ref' pour Stock, 'reference' pour Articles
    $changes    = $comparator->compareOn('reference');

    echo '<pre>';
    print_r($today);

    echo "<br><br><br>";
    echo '</pre>';

} catch (Exception $e) {
    error_log("Erreur capturée : " . $e->getMessage()); // Sauvegarde dans un log
}
