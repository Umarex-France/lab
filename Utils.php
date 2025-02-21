<?php

/**
 * Classe utilitaire pour les opérations courantes.
 */
class Utils
{
    /**
     * Décode une chaîne JSON en tableau associatif.
     *
     * @param string $src La chaîne JSON à décoder.
     * @return array Le tableau associatif résultant.
     * @throws Exception En cas d'erreur de décodage JSON.
     */
    public static function toJson($src)
    {
        if (! is_string($src)) {
            throw new Exception('Le paramètre doit être une chaîne JSON valide.');
        }

        $json = json_decode($src, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erreur JSON : ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Décode une chaîne encodée en UTF-8 Unicode en texte brut.
     *
     * @param string $str La chaîne encodée en UTF-8 Unicode.
     * @return string La chaîne décodée en texte lisible.
     * @throws Exception Si le paramètre n'est pas une chaîne valide.
     */
    public static function decode_unicode($str)
    {
        if (! is_string($str)) {
            throw new Exception('Le paramètre doit être une chaîne valide.');
        }

        // Décodage des séquences Unicode (\uXXXX)
        $str = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
            return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UTF-16BE');
        }, $str);

        // Remplacement des séquences d'échappement pour les slashs
        return str_replace('\/', '/', $str);
    }

    /**
     * Enregistre le contenu JSON dans un fichier.
     *
     * Si le contenu est un tableau, il est converti en chaîne JSON avec une mise en forme lisible.
     * Le dossier du fichier est créé automatiquement s'il n'existe pas.
     * Le fichier est ensuite écrit avec un verrou exclusif pour éviter les conflits d'accès.
     *
     * @param array|string $jsonSrc Le contenu JSON à enregistrer (tableau ou chaîne JSON).
     * @param string       $jsonPath    Le chemin complet du fichier, incluant les dossiers et le nom du fichier.
     *
     * @throws Exception En cas d'erreur lors de la conversion en JSON, de la création des dossiers ou de l'écriture du fichier.
     */
    public static function saveJson($jsonSrc, string $jsonPath): array
    {
        // Vérification et conversion en JSON si nécessaire
        if (is_array($jsonSrc)) {
            $jsonContent  = json_encode($jsonSrc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $returnedJson = $jsonSrc;

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur lors de l\'encodage JSON : ' . json_last_error_msg());
            }
            // Ni tableau, ni String
        } elseif (! is_string($jsonSrc)) {
            throw new Exception('Le paramètre fourni doit être un tableau ou une chaîne JSON valide.');
        }

        // Extraction du dossier à partir de JsonPath (compatible avec plusieurs niveaux de dossiers)
        $directory = dirname($jsonPath);
        if (! is_dir($directory)) {
            if (! mkdir($directory, 0666, true) && ! is_dir($directory)) {
                throw new Exception("Impossible de créer le dossier $directory");
            }
        }

        // Écriture dans le fichier avec LOCK_EX pour éviter les conflits d'accès
        if (file_put_contents($jsonPath, $jsonSrc, LOCK_EX) === false) {
            throw new Exception("Échec de l'enregistrement du fichier $jsonPath");
        } else {
            $returnedJson = self::toJson($jsonSrc);
        }
        // Définit selon le type de l'arugement $jsonSrc
        return $returnedJson;
    }

    /**
     * Transforme une collection d'objets JSON en un tableau bidimensionnel
     * avec en-têtes dynamiques et valeurs sous forme de lignes.
     *
     * @param mixed $source Chaîne JSON ou tableau PHP contenant les données.
     * @return array Tableau PHP formaté (première ligne = en-têtes, suivantes = valeurs).
     * @throws Exception Si le JSON est invalide ou si le format des données est incorrect.
     */
    public static function collectionToArray($source)
    {
        // Vérifie si l'entrée est une chaîne JSON et tente de la décoder
        if (is_string($source)) {
            $data = json_decode($source, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Erreur lors du décodage JSON : " . json_last_error_msg());
            }
        } elseif (is_array($source)) {
            // Si l'entrée est déjà un tableau PHP
            $data = $source;
        } else {
            throw new Exception("Le paramètre source doit être une chaîne JSON ou un tableau PHP.");
        }

        // Vérifie que les données sont bien sous forme de tableau
        if (empty($data) || ! is_array($data)) {
            throw new Exception("Les données fournies sont vides ou mal formées.");
        }

        // Vérifie que le premier élément est un tableau associatif (objet JSON)
        if (! isset($data[0]) || ! is_array($data[0])) {
            throw new Exception("Le format JSON est invalide : attend une liste d'objets.");
        }

        // Extraction des en-têtes (clés du premier objet)
        $headers = array_keys($data[0]);

        // Création du tableau final avec les en-têtes en première ligne
        $table   = [];
        $table[] = $headers;

        // Ajout des valeurs sous forme de lignes
        foreach ($data as $row) {
            $table[] = array_map(function ($key) use ($row) {
                return isset($row[$key]) ? $row[$key] : null;
            }, $headers);
        }

        return $table;
    }

    /**
     * Lit un fichier CSV et retourne la première ligne sous forme de tableau.
     *
     * @param string $csvPath Chemin vers le fichier CSV.
     * @return array|false Retourne un tableau contenant la première ligne du CSV ou false en cas d'erreur.
     */
    public static function readCSV($csvPath)
    {
        // Vérifie si le fichier existe avant d'essayer de le lire
        if (! file_exists($csvPath)) {
            die("Impossible de trouver le fichier CSV !");
        }

        // Ouvre le fichier en mode lecture
        $handle = fopen($csvPath, 'r');
        if (! $handle) {
            die("Impossible de lire le fichier CSV.");
        }

        // Lit la première ligne du fichier CSV
        // fgetcsv retourne un tableau contenant les valeurs de la ligne, séparées par ","
        $data = fgetcsv($handle, 1000, ',');

        // Ferme le fichier après lecture pour libérer les ressources
        fclose($handle);

        // Retourne les données extraites ou false si aucune donnée
        return $data;
    }

    /**
     * Lit un fichier JSON et retourne son contenu sous forme de tableau associatif.
     *
     * @param string $jsonPath Chemin vers le fichier JSON.
     * @return array Retourne un tableau contenant les données JSON.
     * @throws Exception Si le fichier est introuvable, illisible ou si le JSON est invalide.
     */
    public static function readJSON($jsonPath)
    {
        // Vérifie si le fichier existe avant de le lire
        if (! file_exists($jsonPath)) {
            throw new Exception("Impossible de trouver le fichier JSON !");
        }

        // Retourne le contenu du fichier JSON sous forme de chaîne de caractères
        return self::toJson(file_get_contents($jsonPath));
    }
}
