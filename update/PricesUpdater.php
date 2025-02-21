<?php
// Configuration
use PrestaShop\PrestaShop\Adapter\Entity\Db;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
//require_once dirname(__FILE__) . '/../b2b.php';

class PricesUpdater
{

    /** Lecture du CSV et mise à jour à jour des prix automatique */
    public static function read($csv)
    {
        // Erreur d'URL
        if (! file_exists($csv)) {
            die("Impossible de trouver le fichier CSV !");
        }

        // Erreur de contenu
        $handle = fopen($csv, 'r');
        if (! $handle) {die("Impossible de lire le fichier CSV.");}

        // Lecture du contenu
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {

            // Ignore les lignes invalides
            if (count($data) < 2) {
                continue;
            }

                                                // Récupération des données
            $ref   = trim($data[0]);            // Référence produit
            $price = sprintf("%.2f", $data[1]); // Nouveau prix
            echo "{$ref} : {$price}";

            // Mise à jour de la ligne
            if (! empty($red) && $price >= 0) {
                self::setPriceToRef($price, $ref);
            }

        }
        fclose($handle);
        echo "Mise à jour finie !<br>";
    }

    /** Met un jour le prix d'un article selon sa référence */
    private static function setPriceToRef($price, $ref)
    {
        $db        = Db::getInstance();
        $idProduct = (int) $db->getValue(
            'SELECT id_product FROM ' . _DB_PREFIX_ . 'product WHERE reference = "' . pSQL($ref) . '"'
        );

        if ($idProduct > 0) {
            $product = new Product($idProduct);

            if (Validate::isLoadedObject($product)) {
                $product->price = $price;
                $product->update(); // 🚀 Mise à jour du produit

                echo "Référence {$ref} (ID: {$idProduct}) à {$price}€.<br>";
            } else {
                echo "Référence {$ref} non mise à jour.<br>";
            }
        } else {
            echo "Par d'article trouvé avec la réf: {$reference}.<br>";
        }
    }
}

//$csv = __DIR__ . '/prices.csv'; // Dans le même dossier que le script
//PricesUpdater::read($csv);
echo "Fin du script.";
