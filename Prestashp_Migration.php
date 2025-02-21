<?php

// 🛠️ CONFIGURATION
$sourceDb = [
    'host' => 'localhost',
    'dbname' => 'prestashop17_db',
    'user' => 'root',
    'password' => '',
];

$targetDb = [
    'host' => 'localhost',
    'dbname' => 'prestashop82_db',
    'user' => 'root',
    'password' => '',
];

// 📂 Chemins vers les PrestaShop 1.7 et 8.2
$sourcePath = '/var/www/monsite.com/';
$targetPath = '/var/www/dev.monsite.com/';

// 📌 Fichiers à copier
$filesToCopy = [
    'img/p',  // Images des produits
    'img/c',  // Images des catégories
    'img/m',  // Images des marques
    'modules', // Modules installés
];

// 🔄 MIGRATION DES DONNÉES
try {
    echo "🔄 1. Export de la base de données de PrestaShop 1.7...\n";
    $dumpFile = $sourcePath . 'backup_ps17.sql';
    exec("mysqldump -u {$sourceDb['user']} -p{$sourceDb['password']} -h {$sourceDb['host']} {$sourceDb['dbname']} > $dumpFile");

    echo "✅ Base exportée dans : $dumpFile\n";

    echo "🔄 2. Import de la base de données dans PrestaShop 8.2...\n";
    exec("mysql -u {$targetDb['user']} -p{$targetDb['password']} -h {$targetDb['host']} {$targetDb['dbname']} < $dumpFile");

    echo "✅ Importation terminée !\n";

    // 🔄 COPIE DES FICHIERS
    echo "🔄 3. Copie des fichiers importants...\n";
    foreach ($filesToCopy as $file) {
        $source = rtrim($sourcePath, '/') . '/' . $file;
        $destination = rtrim($targetPath, '/') . '/' . $file;

        if (is_dir($source)) {
            exec("rsync -av --progress $source/ $destination/");
            echo "✅ Copie de $file terminée.\n";
        } else {
            echo "⚠️ Fichier/Dossier $file introuvable dans PrestaShop 1.7.\n";
        }
    }

    // 🔄 METTRE À JOUR LA BASE DE DONNÉES
    echo "🔄 4. Mise à jour de la base de données...\n";
    exec("php {$targetPath}bin/console prestashop:schema:update-without-foreign");

    // 🔄 VIDAGE DU CACHE
    echo "🔄 5. Nettoyage du cache...\n";
    exec("php {$targetPath}bin/console cache:clear");

    echo "🎉 MIGRATION TERMINÉE AVEC SUCCÈS ! 🎉\n";

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

