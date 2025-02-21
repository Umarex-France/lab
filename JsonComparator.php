<?php

class JsonComparator
{
    /** @var array Données avant */
    private array $oldData;

    /** @var array Données après */
    private array $newData;

    /**
     * Constructeur
     *
     * @param array $oldData Données avant
     * @param array $newData Données après
     */
    public function __construct(array $oldData, array $newData)
    {
        $this->oldData = $oldData ?? [];
        $this->newData = $newData ?? [];
    }

    /**
     * Compare les données avant/après et retourne les modifications
     *
     * @param string $keyField Clé unique d'identification (par défaut "id")
     * @return array Liste des modifications détectées
     */
    public function compareOn(string $keyField = 'id'): array
    {
        $beforeIndex = $this->indexByKey($this->oldData, $keyField);
        $afterIndex  = $this->indexByKey($this->newData, $keyField);
        $changes     = [];

        // Suppressions et mises à jour
        foreach ($beforeIndex as $key => $oldItem) {
            if (! isset($afterIndex[$key])) {
                $oldItem['action'] = 'delete';
                $changes[]         = $oldItem;
            } else {
                $newItem = $afterIndex[$key];
                if ($this->hasDifferences($oldItem, $newItem)) {
                    $newItem['action'] = 'update';
                    $changes[]         = $newItem;
                }
            }
        }

        // Ajouts
        foreach ($afterIndex as $key => $newItem) {
            if (! isset($beforeIndex[$key])) {
                $newItem['action'] = 'add';
                $changes[]         = $newItem;
            }
        }

        return $changes;
    }

    /**
     * Indexe les données par la clé unique
     *
     * @param array $data Tableau de données
     * @param string $keyField Clé unique
     * @return array Données indexées
     */
    private function indexByKey(array $data, string $keyField): array
    {
        $indexedData = [];
        foreach ($data as $item) {
            if (isset($item[$keyField])) {
                $indexedData[$item[$keyField]] = $item;
            }
        }
        return $indexedData;
    }

    /**
     * Vérifie si deux éléments sont différents
     *
     * @param array $oldItem Élément avant
     * @param array $newItem Élément après
     * @return bool True si différences, False sinon
     */
    private function hasDifferences(array $oldItem, array $newItem): bool
    {
        return $this->findDifferences($oldItem, $newItem) !== [];
    }

    /**
     * Trouve les différences entre deux éléments
     *
     * @param array $oldItem Élément avant
     * @param array $newItem Élément après
     * @return array Liste des différences
     */
    private function findDifferences(array $oldItem, array $newItem): array
    {
        $differences = [];

        foreach ($oldItem as $key => $oldValue) {
            if (! array_key_exists($key, $newItem)) {
                $differences[$key] = ['old' => $oldValue, 'new' => null];
            } else {
                $newValue = $newItem[$key];

                if (is_array($oldValue) && is_array($newValue)) {
                    if ($this->hasDifferences($oldValue, $newValue)) {
                        $differences[$key] = ['old' => $oldValue, 'new' => $newValue];
                    }
                } elseif ($oldValue !== $newValue) {
                    $differences[$key] = ['old' => $oldValue, 'new' => $newValue];
                }
            }
        }

        return $differences;
    }
}
