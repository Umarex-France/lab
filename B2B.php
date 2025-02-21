<?php
require_once __DIR__ . '/env.php'; // Charger les variables d'environnement
require_once __DIR__ . '/Utils.php';

/**
 * Classe B2B permettant d'interagir avec l'API B2B d'Umarex.
 */
class B2B
{
    private static array $HTTP_HEADERS = [
        'X-AUTH-TOKEN: ' . B2B_API_KEY, // Définit dans env.php
        'Content-Type: application/json',
    ];

    /**
     * Récupère les données JSON depuis l'API.
     *
     * @param string $endpoint L'endpoint de l'API (ex: 'article' ou 'stock').
     * @return string Le JSON brut renvoyé par l'API.
     * @throws Exception En cas d'erreur cURL ou HTTP.
     */
    public static function fetchData(string $endpoint): string
    {
        if (! self::isValideEndpoint($endpoint)) {
            throw new Exception('Erreur endPoint : ' . $endpoint); // Ni "stock" ni "article"
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, B2B_API_URL . "/" . $endpoint); // Définit dans env.php
        curl_setopt($ch, CURLOPT_HTTPHEADER, self::$HTTP_HEADERS);

        // Exécution de la requête
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Erreur cURL : ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Erreur HTTP $httpCode : " . $response);
        }
        return $response;
    }

    /**
     * Récupère les informations de tous les articles depuis l'API.
     *
     * @return array Tableau des articles.
     * @throws Exception En cas d'erreur.
     */
    public static function getArticleAt(DateTime $date = null): array
    {
        if ($date) {
            return B2B::getFileAt(ARTICLE, $date);
        }

        return Utils::toJson(self::fetchData(ARTICLE));

    }

    /**
     * Récupère les informations de stock depuis l'API.
     *
     * @return array Tableau des stocks.
     * @throws Exception En cas d'erreur.
     */
    public static function getStockAt(DateTime $date = null): array
    {
        if ($date) {
            return B2B::getFileAt(STOCK, $date);
        }

        return Utils::toJson(self::fetchData(STOCK));

    }

    /**
     * Enregistre le fichier JSON des articles pour la date actuelle.
     *
     * @param string $endpoint L'endpoint de l'API (ex: 'article' ou 'stock').
     * @return string Le contenu du fichier JSON enregistré.
     *
     * @throws Exception En cas d'erreur.
     */
    public static function saveFile(string $endpoint): array
    {
        try {
            $fileContent = self::fetchData($endpoint);
            $filename    = sprintf('%s/%s_%s.json', $endpoint, $endpoint, date('Y-m-d'));
            return Utils::saveJson($fileContent, $filename);
            //
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Récupère le contenu d'un fichier JSON basé sur un nom et une date.
     *
     * @param string   $name Nom du dossier et préfixe du fichier (ex: "Articles" ou "Stock").
     * @param DateTime $date Date utilisée pour construire le nom du fichier (format YYYY-MM-DD).
     * @return array Contenu du fichier JSON sous forme de tableau, ou un tableau vide si une erreur survient.
     */
    public static function getFileAt(string $name, DateTime $date): array
    {
        // Construire le chemin complet du fichier en fonction du nom et de la date
        $filePath = sprintf('%s/%s_%s.json', $name, $name, $date->format('Y-m-d'));

        // Vérifier si le fichier existe
        if (! file_exists($filePath)) {
            return []; // Retourner un tableau vide si le fichier est introuvable
        }

        // Lire le contenu du fichier
        $content = file_get_contents($filePath);

        // Décoder le JSON en tableau associatif
        $data = json_decode($content, true);

        // Vérifier si le JSON est valide et retourner les données
        return is_array($data) ? $data : [];
    }

    /**
     * Valide si le nom de point d'entrée est autorisé.
     *
     * @param string $filename Nom du fichier à valider (ex: "ARTICLE" ou "STOCK").
     * @return bool Retourne true si le nom est valide, sinon false.
     */
    public static function isValideEndpoint(string $endpoint): bool
    {
        switch ($endpoint) {
            case ARTICLE;
            case STOCK:
                return true;

            default:
                return false;
        }
    }
}
