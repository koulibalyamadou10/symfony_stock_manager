# 📋 Système d'Abonnement Mensuel - Gestion de Stock

## 🎯 Vue d'ensemble

Ce système d'abonnement mensuel permet aux utilisateurs de payer 50 000 GNF par mois pour accéder aux fonctionnalités de l'application de gestion de stock. L'intégration utilise l'API Lengo Pay pour traiter les paiements.

## 💰 Fonctionnalités

- **Abonnement mensuel obligatoire** : 50 000 GNF/mois
- **Vérification automatique** : Contrôle de l'abonnement avant chaque accès
- **Paiement sécurisé** : Intégration avec Lengo Pay
- **Gestion des expirations** : Notifications et blocage automatique
- **Interface admin** : Gestion des abonnements par les administrateurs

## 🔧 Configuration Lengo Pay

### 1. Prérequis
- Compte marchand Lengo Pay
- License Key (clé d'autorisation)
- Website ID (identifiant de votre site)

### 2. Variables d'environnement
Ajoutez ces variables dans votre fichier `.env` :

```env
# Configuration Lengo Pay
LENGO_PAY_LICENSE_KEY=votre_license_key_ici
LENGO_PAY_WEBSITE_ID=votre_website_id_ici
LENGO_PAY_API_URL=https://portal.lengopay.com/api/v1/payments
LENGO_PAY_CURRENCY=GNF
SUBSCRIPTION_AMOUNT=50000
```

### 3. API Lengo Pay - Détails techniques

#### Endpoint de paiement
```
POST https://portal.lengopay.com/api/v1/payments
```

#### Headers requis
```
Authorization: Basic {license_key}
Accept: application/json
Content-Type: application/json
```

#### Corps de la requête
```json
{
    "websiteid": "votre_website_id",
    "amount": 50000,
    "currency": "GNF",
    "return_url": "https://votre-site.com/subscription/success",
    "callback_url": "https://votre-site.com/subscription/callback"
}
```

#### Réponse attendue
```json
{
    "status": "success",
    "pay_id": "unique_payment_id",
    "payment_url": "https://portal.lengopay.com/pay/unique_payment_id"
}
```

## 🚀 Installation et Configuration

### 1. Migration de base de données
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 2. Configuration des routes
Les nouvelles routes sont automatiquement configurées :
- `/subscription/status` - Statut de l'abonnement
- `/subscription/pay` - Initier un paiement
- `/subscription/success` - Page de succès
- `/subscription/callback` - Webhook Lengo Pay

### 3. Middleware de vérification
Le système vérifie automatiquement l'abonnement avant chaque accès aux pages protégées.

## 📱 Utilisation

### Pour les utilisateurs
1. **Inscription** : Créer un compte normalement
2. **Premier accès** : Redirection automatique vers la page d'abonnement
3. **Paiement** : Payer 50 000 GNF via Lengo Pay
4. **Accès complet** : Utilisation normale de l'application

### Pour les administrateurs
- **Gestion des abonnements** : Voir tous les abonnements
- **Activation manuelle** : Activer/désactiver des abonnements
- **Statistiques** : Revenus et utilisateurs actifs

## 🔒 Sécurité

### Vérifications automatiques
- Contrôle de l'expiration avant chaque page
- Validation des paiements via callback
- Protection contre les accès non autorisés

### Gestion des erreurs
- Messages d'erreur clairs pour les utilisateurs
- Logs détaillés pour les administrateurs
- Gestion des échecs de paiement

## 📊 Monitoring

### Métriques disponibles
- Nombre d'abonnements actifs
- Revenus mensuels
- Taux de renouvellement
- Utilisateurs en attente de paiement

### Notifications
- Email de confirmation de paiement
- Alertes d'expiration (7 jours avant)
- Notifications d'échec de paiement

## 🛠️ Maintenance

### Tâches automatiques
```bash
# Vérifier les abonnements expirés (à exécuter quotidiennement)
php bin/console app:check-expired-subscriptions

# Envoyer les rappels d'expiration
php bin/console app:send-expiration-reminders
```

### Logs
Les logs sont disponibles dans :
- `var/log/subscription.log` - Logs des abonnements
- `var/log/lengo_pay.log` - Logs des paiements

## 🔧 Dépannage

### Problèmes courants

1. **Paiement non confirmé**
   - Vérifier la configuration du callback
   - Contrôler les logs Lengo Pay

2. **Accès refusé malgré paiement**
   - Vérifier le statut en base de données
   - Contrôler la date d'expiration

3. **Erreur API Lengo Pay**
   - Vérifier la license key
   - Contrôler le website ID

### Support
Pour toute assistance technique :
- Consulter les logs de l'application
- Vérifier la documentation Lengo Pay
- Contacter le support technique

## 📈 Évolutions futures

### Fonctionnalités prévues
- Abonnements annuels avec réduction
- Essai gratuit de 7 jours
- Plans d'abonnement multiples
- Facturation automatique

### Intégrations possibles
- Autres passerelles de paiement
- Système de facturation avancé
- Analytics détaillées
- Programme de parrainage

---

**Note importante** : Ce système nécessite une configuration correcte de Lengo Pay et un environnement de production sécurisé pour fonctionner correctement.