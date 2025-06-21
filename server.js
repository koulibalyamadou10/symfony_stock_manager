const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const path = require('path');

const app = express();
const PORT = 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(express.static('public'));

// Simulation des données de l'application Symfony
let users = [
  { id: 1, nom: 'Admin User', email: 'admin@uganc.edu.gn', roles: ['ROLE_ADMIN'], hasActiveSubscription: true },
  { id: 2, nom: 'Koulibaly Amadou', email: 'amadou@uganc.edu.gn', roles: ['ROLE_USER'], hasActiveSubscription: true },
  { id: 3, nom: 'Test User', email: 'test@uganc.edu.gn', roles: ['ROLE_USER'], hasActiveSubscription: false }
];

let categories = [
  { id: 1, nom: 'Électronique', description: 'Appareils électroniques et accessoires' },
  { id: 2, nom: 'Vêtements', description: 'Vêtements et accessoires de mode' },
  { id: 3, nom: 'Alimentation', description: 'Produits alimentaires et boissons' }
];

let produits = [
  { id: 1, nom: 'Smartphone Samsung', prix: 2500000, quantite: 15, actif: true, categorie_id: 1, utilisateur_id: 2, dateAjout: '2025-01-15' },
  { id: 2, nom: 'T-shirt Coton', prix: 25000, quantite: 3, actif: true, categorie_id: 2, utilisateur_id: 2, dateAjout: '2025-01-14' },
  { id: 3, nom: 'Ordinateur Portable', prix: 4500000, quantite: 0, actif: false, categorie_id: 1, utilisateur_id: 1, dateAjout: '2025-01-13' },
  { id: 4, nom: 'Café Premium', prix: 15000, quantite: 50, actif: true, categorie_id: 3, utilisateur_id: 2, dateAjout: '2025-01-12' }
];

let abonnements = [
  { id: 1, utilisateur_id: 1, dateDebut: '2025-01-01', dateFin: '2025-02-01', estActif: true, montant: 50000, statut: 'actif' },
  { id: 2, utilisateur_id: 2, dateDebut: '2025-01-10', dateFin: '2025-02-10', estActif: true, montant: 50000, statut: 'actif' }
];

// Routes de test
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// API Routes pour tester les fonctionnalités
app.get('/api/users', (req, res) => {
  res.json({ success: true, data: users, message: 'Utilisateurs récupérés avec succès' });
});

app.get('/api/categories', (req, res) => {
  res.json({ success: true, data: categories, message: 'Catégories récupérées avec succès' });
});

app.get('/api/produits', (req, res) => {
  const produitsAvecDetails = produits.map(produit => {
    const categorie = categories.find(c => c.id === produit.categorie_id);
    const utilisateur = users.find(u => u.id === produit.utilisateur_id);
    return {
      ...produit,
      categorie: categorie ? categorie.nom : 'Inconnue',
      utilisateur: utilisateur ? utilisateur.nom : 'Inconnu'
    };
  });
  res.json({ success: true, data: produitsAvecDetails, message: 'Produits récupérés avec succès' });
});

app.get('/api/abonnements', (req, res) => {
  const abonnementsAvecDetails = abonnements.map(abonnement => {
    const utilisateur = users.find(u => u.id === abonnement.utilisateur_id);
    return {
      ...abonnement,
      utilisateur: utilisateur ? utilisateur.nom : 'Inconnu'
    };
  });
  res.json({ success: true, data: abonnementsAvecDetails, message: 'Abonnements récupérés avec succès' });
});

// Test de création d'un produit
app.post('/api/produits', (req, res) => {
  const { nom, prix, quantite, categorie_id, utilisateur_id } = req.body;
  
  if (!nom || !prix || quantite === undefined || !categorie_id || !utilisateur_id) {
    return res.status(400).json({ success: false, message: 'Données manquantes' });
  }

  const nouveauProduit = {
    id: produits.length + 1,
    nom,
    prix: parseFloat(prix),
    quantite: parseInt(quantite),
    actif: quantite > 0,
    categorie_id: parseInt(categorie_id),
    utilisateur_id: parseInt(utilisateur_id),
    dateAjout: new Date().toISOString().split('T')[0]
  };

  produits.push(nouveauProduit);
  res.json({ success: true, data: nouveauProduit, message: 'Produit créé avec succès' });
});

// Test de vente d'un produit
app.post('/api/produits/:id/vendre', (req, res) => {
  const produitId = parseInt(req.params.id);
  const { quantite } = req.body;
  
  const produit = produits.find(p => p.id === produitId);
  
  if (!produit) {
    return res.status(404).json({ success: false, message: 'Produit non trouvé' });
  }
  
  if (quantite > produit.quantite) {
    return res.status(400).json({ success: false, message: 'Stock insuffisant' });
  }
  
  produit.quantite -= quantite;
  if (produit.quantite <= 0) {
    produit.actif = false;
  }
  
  res.json({ 
    success: true, 
    data: produit, 
    message: `Vente de ${quantite} unité(s) effectuée. Stock restant: ${produit.quantite}` 
  });
});

// Test de paiement d'abonnement (simulation Lengo Pay)
app.post('/api/subscription/pay', (req, res) => {
  const { userId } = req.body;
  
  // Simulation d'un paiement réussi
  const nouvelAbonnement = {
    id: abonnements.length + 1,
    utilisateur_id: userId,
    dateDebut: new Date().toISOString().split('T')[0],
    dateFin: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    estActif: true,
    montant: 50000,
    statut: 'actif'
  };
  
  abonnements.push(nouvelAbonnement);
  
  // Mettre à jour le statut de l'utilisateur
  const user = users.find(u => u.id === userId);
  if (user) {
    user.hasActiveSubscription = true;
  }
  
  res.json({ 
    success: true, 
    data: nouvelAbonnement, 
    message: 'Abonnement activé avec succès',
    payment_url: 'https://portal.lengopay.com/pay/test_payment_id'
  });
});

// Statistiques du tableau de bord
app.get('/api/dashboard/stats', (req, res) => {
  const stats = {
    totalProduits: produits.length,
    produitsActifs: produits.filter(p => p.actif).length,
    totalCategories: categories.length,
    totalUtilisateurs: users.length,
    abonnementsActifs: abonnements.filter(a => a.estActif).length,
    ruptureStock: produits.filter(p => p.quantite === 0).length,
    stockCritique: produits.filter(p => p.quantite > 0 && p.quantite <= 5).length,
    revenusMensuel: abonnements.filter(a => a.estActif).reduce((total, a) => total + a.montant, 0)
  };
  
  res.json({ success: true, data: stats, message: 'Statistiques récupérées avec succès' });
});

// Test de vérification d'abonnement
app.get('/api/subscription/check/:userId', (req, res) => {
  const userId = parseInt(req.params.userId);
  const user = users.find(u => u.id === userId);
  
  if (!user) {
    return res.status(404).json({ success: false, message: 'Utilisateur non trouvé' });
  }
  
  const abonnementActif = abonnements.find(a => 
    a.utilisateur_id === userId && 
    a.estActif && 
    new Date(a.dateFin) > new Date()
  );
  
  res.json({ 
    success: true, 
    data: { 
      hasActiveSubscription: !!abonnementActif,
      abonnement: abonnementActif || null,
      user: user
    }, 
    message: 'Vérification d\'abonnement effectuée' 
  });
});

app.listen(PORT, () => {
  console.log(`🚀 Serveur de test démarré sur http://localhost:${PORT}`);
  console.log(`📊 Interface de test disponible à l'adresse ci-dessus`);
});