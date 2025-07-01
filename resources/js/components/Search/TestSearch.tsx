import React from 'react'
import { SearchWithSuggestions, SimpleSearch } from '@/components/Search'

/**
 * Composant de test pour vérifier le fonctionnement des composants de recherche
 * À utiliser temporairement pour tester l'intégration Laravel
 */
export function TestSearch() {
  const handleSearch = (query: string) => {
    console.log('Recherche effectuée:', query)
  }

  return (
    <div className="max-w-4xl mx-auto p-8 space-y-8">
      <div className="text-center">
        <h1 className="text-3xl font-bold mb-2">Test des Composants de Recherche</h1>
        <p className="text-muted-foreground">
          Cette page permet de tester l'intégration des composants de recherche avec Laravel
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* SearchWithSuggestions */}
        <div className="space-y-4">
          <h2 className="text-xl font-semibold">SearchWithSuggestions</h2>
          <p className="text-sm text-muted-foreground">
            Recherche avec autocomplétion et suggestions. Tapez au moins 2 caractères.
          </p>
          <SearchWithSuggestions 
            placeholder="Rechercher des produits (ex: iPhone, MacBook...)"
            onSearch={handleSearch}
            className="w-full"
          />
          <div className="text-xs text-muted-foreground space-y-1">
            <p>• Suggestions en temps réel depuis la base de données</p>
            <p>• Navigation au clavier (↑↓, Enter, Escape)</p>
            <p>• Historique des recherches récentes</p>
            <p>• Images et prix des produits</p>
          </div>
        </div>

        {/* SimpleSearch */}
        <div className="space-y-4">
          <h2 className="text-xl font-semibold">SimpleSearch</h2>
          <p className="text-sm text-muted-foreground">
            Recherche basique sans suggestions.
          </p>
          <SimpleSearch 
            placeholder="Recherche simple..."
            onSearch={handleSearch}
            className="w-full"
          />
          <div className="text-xs text-muted-foreground space-y-1">
            <p>• Recherche directe sans autocomplétion</p>
            <p>• Bouton de soumission intégré</p>
            <p>• Validation de saisie</p>
            <p>• Plus léger et rapide</p>
          </div>
        </div>
      </div>

      {/* Informations techniques */}
      <div className="bg-muted/50 rounded-lg p-6 space-y-4">
        <h3 className="text-lg font-semibold">Informations Techniques</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <h4 className="font-medium mb-2">Routes Laravel utilisées:</h4>
            <ul className="space-y-1 text-muted-foreground">
              <li>• <code>GET /products/suggestions</code> - Autocomplétion</li>
              <li>• <code>GET /products/search</code> - Recherche avancée</li>
              <li>• <code>GET /products</code> - Liste des produits</li>
              <li>• <code>GET /products/[uuid]</code> - Détail produit</li>
            </ul>
          </div>
          <div>
            <h4 className="font-medium mb-2">Données recherchées:</h4>
            <ul className="space-y-1 text-muted-foreground">
              <li>• Produits (nom, description, SKU)</li>
              <li>• Catégories avec compteur produits</li>
              <li>• Produits tendances (plus vendus)</li>
              <li>• PostgreSQL full-text search</li>
            </ul>
          </div>
        </div>
      </div>

      {/* Instructions */}
      <div className="bg-blue-50 dark:bg-blue-950 rounded-lg p-6">
        <h3 className="text-lg font-semibold mb-2">Pour tester :</h3>
        <ol className="list-decimal list-inside space-y-2 text-sm">
          <li>Assurez-vous que les migrations et seeders sont exécutés</li>
          <li>Ouvrez les DevTools (F12) pour voir les requêtes réseau</li>
          <li>Tapez dans le champ "SearchWithSuggestions" pour voir les suggestions</li>
          <li>Vérifiez que les URLs générées sont correctes</li>
          <li>Testez la navigation au clavier dans les suggestions</li>
        </ol>
      </div>
    </div>
  )
}

export default TestSearch 