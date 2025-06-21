# Guide d'installation - Système d'abonnement

## 1. Configuration des variables d'environnement

Ajoutez ces lignes à votre fichier `.env` :

```env
###> Lengo Pay Configuration ###
LENGO_PAY_API_KEY=your_lengo_pay_api_key_here
LENGO_PAY_MERCHANT_ID=your_merchant_id_here
###< Lengo Pay Configuration ###

###> Application URL ###
APP_URL=http://localhost:8000
###< Application URL ###

###> Email Configuration ###
MAILER_DSN=smtp://localhost:1025
###< Email Configuration ###
```

## 2. Installation des dépendances

```bash
composer install
```

## 3. Migration de la base de données

```bash
# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Ou créer la migration si elle n'existe pas
php bin/console make:migration
```

## 4. Configuration des tâches planifiées (Cron)

Ajoutez cette ligne à votre crontab pour vérifier quotidiennement les abonnements :

```bash
# Éditer le crontab
crontab -e

# Ajouter cette ligne (remplacez /path/to/project par le chemin réel)
0 0 * * * cd /path/to/project && php bin/console app:desactiver-abonnements-expires >> var/log/cron.log 2>&1
```

## 5. Test du système

### Tester la commande de vérification des abonnements :
```bash
php bin/console app:desactiver-abonnements-expires
```

### Vérifier les routes :
```bash
php bin/console debug:router | grep abonnement
```

## 6. Fonctionnalités disponibles

### Routes principales :
- `/abonnement/status` - Statut de l'abonnement
- `/abonnement/souscrire` - Souscrire un abonnement
- `/abonnement/historique` - Historique des abonnements
- `/abonnement/confirmation` - Confirmation après paiement
- `/admin/abonnements/statistiques` - Statistiques (admin)
- `/admin/abonnements/dashboard` - Tableau de bord (admin)

### Commandes disponibles :
```bash
# Vérifier et désactiver les abonnements expirés
php bin/console app:desactiver-abonnements-expires
```

## 7. Configuration Lengo Pay

1. Créez un compte sur [Lengo Pay](https://lengopay.com)
2. Récupérez votre API Key et Merchant ID
3. Configurez l'URL de callback : `https://votre-domaine.com/abonnement/confirmation`
4. Testez avec l'environnement de test avant la production

## 8. Sécurité

- Les utilisateurs sans abonnement sont automatiquement redirigés
- Les administrateurs ont accès à toutes les fonctionnalités
- Les données de paiement sont sécurisées via Lengo Pay
- Les logs sont enregistrés pour le suivi

## 9. Dépannage

### Problème : Les abonnements ne se désactivent pas automatiquement
**Solution :** Vérifiez que la tâche cron est configurée et fonctionne

### Problème : Erreur de paiement Lengo Pay
**Solution :** Vérifiez vos clés API et l'URL de callback

### Problème : Utilisateurs bloqués sans abonnement
**Solution :** Vérifiez que l'EventSubscriber fonctionne correctement

## 10. Support

Pour toute question :
- Vérifiez les logs dans `var/log/`
- Consultez la documentation Lengo Pay
- Contactez le support technique

## 11. Mise en production

1. Configurez les vraies clés API Lengo Pay
2. Mettez à jour l'URL de l'application
3. Configurez le serveur email
4. Testez tous les flux de paiement
5. Configurez les sauvegardes de base de données
