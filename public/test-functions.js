// Fonctions de test pour l'application Symfony

// Fonction utilitaire pour afficher les résultats
function showResult(elementId, success, message, data = null) {
    const element = document.getElementById(elementId);
    element.style.display = 'block';
    element.className = `test-result ${success ? 'result-success' : 'result-error'}`;
    
    let content = `<strong>${success ? '✅ SUCCÈS' : '❌ ÉCHEC'}</strong><br>${message}`;
    if (data) {
        content += `<br><small>Données: ${JSON.stringify(data, null, 2)}</small>`;
    }
    element.innerHTML = content;
}

// Fonction utilitaire pour les requêtes API
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    } catch (error) {
        throw new Error(`Erreur réseau: ${error.message}`);
    }
}

// Test 1: Gestion des utilisateurs
async function testUsers() {
    try {
        const result = await apiRequest('/api/users');
        
        if (result.success && result.data.length > 0) {
            const adminCount = result.data.filter(u => u.roles.includes('ROLE_ADMIN')).length;
            const userCount = result.data.filter(u => u.roles.includes('ROLE_USER')).length;
            
            showResult('test-users-result', true, 
                `${result.data.length} utilisateurs trouvés (${adminCount} admin, ${userCount} utilisateurs)`,
                { total: result.data.length, admins: adminCount, users: userCount }
            );
        } else {
            showResult('test-users-result', false, 'Aucun utilisateur trouvé');
        }
    } catch (error) {
        showResult('test-users-result', false, error.message);
    }
}

// Test 2: Gestion des catégories
async function testCategories() {
    try {
        const result = await apiRequest('/api/categories');
        
        if (result.success && result.data.length > 0) {
            showResult('test-categories-result', true, 
                `${result.data.length} catégories trouvées`,
                result.data.map(c => ({ nom: c.nom, description: c.description }))
            );
        } else {
            showResult('test-categories-result', false, 'Aucune catégorie trouvée');
        }
    } catch (error) {
        showResult('test-categories-result', false, error.message);
    }
}

// Test 3: Gestion des produits
async function testProduits() {
    try {
        const result = await apiRequest('/api/produits');
        
        if (result.success && result.data.length > 0) {
            const actifs = result.data.filter(p => p.actif).length;
            const inactifs = result.data.filter(p => !p.actif).length;
            const stockCritique = result.data.filter(p => p.quantite <= 5 && p.quantite > 0).length;
            const rupture = result.data.filter(p => p.quantite === 0).length;
            
            showResult('test-produits-result', true, 
                `${result.data.length} produits (${actifs} actifs, ${inactifs} inactifs, ${stockCritique} stock critique, ${rupture} rupture)`,
                { total: result.data.length, actifs, inactifs, stockCritique, rupture }
            );
        } else {
            showResult('test-produits-result', false, 'Aucun produit trouvé');
        }
    } catch (error) {
        showResult('test-produits-result', false, error.message);
    }
}

// Test 4: Système d'abonnement
async function testAbonnements() {
    try {
        const result = await apiRequest('/api/abonnements');
        
        if (result.success && result.data.length > 0) {
            const actifs = result.data.filter(a => a.estActif).length;
            const totalRevenu = result.data.filter(a => a.estActif).reduce((sum, a) => sum + a.montant, 0);
            
            showResult('test-abonnements-result', true, 
                `${result.data.length} abonnements (${actifs} actifs, ${totalRevenu.toLocaleString()} GNF de revenus)`,
                { total: result.data.length, actifs, revenus: totalRevenu }
            );
        } else {
            showResult('test-abonnements-result', false, 'Aucun abonnement trouvé');
        }
    } catch (error) {
        showResult('test-abonnements-result', false, error.message);
    }
}

// Test 5: Création de produit
async function testCreateProduit() {
    try {
        const nouveauProduit = {
            nom: 'Produit Test ' + Date.now(),
            prix: 25000,
            quantite: 10,
            categorie_id: 1,
            utilisateur_id: 2
        };
        
        const result = await apiRequest('/api/produits', {
            method: 'POST',
            body: JSON.stringify(nouveauProduit)
        });
        
        if (result.success) {
            showResult('test-create-result', true, 
                `Produit "${result.data.nom}" créé avec succès (ID: ${result.data.id})`,
                result.data
            );
        } else {
            showResult('test-create-result', false, 'Échec de création du produit');
        }
    } catch (error) {
        showResult('test-create-result', false, error.message);
    }
}

// Test 6: Vente de produit
async function testVenteProduit() {
    try {
        // Vendre 2 unités du produit ID 1 (Smartphone Samsung)
        const result = await apiRequest('/api/produits/1/vendre', {
            method: 'POST',
            body: JSON.stringify({ quantite: 2 })
        });
        
        if (result.success) {
            showResult('test-vente-result', true, 
                result.message,
                { produit: result.data.nom, stockRestant: result.data.quantite, actif: result.data.actif }
            );
        } else {
            showResult('test-vente-result', false, result.message);
        }
    } catch (error) {
        showResult('test-vente-result', false, error.message);
    }
}

// Test 7: Paiement d'abonnement
async function testPaiementAbonnement() {
    try {
        const result = await apiRequest('/api/subscription/pay', {
            method: 'POST',
            body: JSON.stringify({ userId: 3 }) // Test User sans abonnement
        });
        
        if (result.success) {
            showResult('test-paiement-result', true, 
                `${result.message} - Montant: ${result.data.montant.toLocaleString()} GNF`,
                { abonnement: result.data, paymentUrl: result.payment_url }
            );
        } else {
            showResult('test-paiement-result', false, 'Échec du paiement');
        }
    } catch (error) {
        showResult('test-paiement-result', false, error.message);
    }
}

// Test 8: Vérification d'abonnement
async function testVerificationAbonnement() {
    try {
        const result = await apiRequest('/api/subscription/check/2'); // Koulibaly Amadou
        
        if (result.success) {
            const status = result.data.hasActiveSubscription ? 'ACTIF' : 'INACTIF';
            showResult('test-verification-result', true, 
                `Abonnement ${status} pour ${result.data.user.nom}`,
                { 
                    utilisateur: result.data.user.nom,
                    abonnementActif: result.data.hasActiveSubscription,
                    abonnement: result.data.abonnement
                }
            );
        } else {
            showResult('test-verification-result', false, result.message);
        }
    } catch (error) {
        showResult('test-verification-result', false, error.message);
    }
}

// Charger les statistiques au démarrage
async function loadStats() {
    try {
        const result = await apiRequest('/api/dashboard/stats');
        
        if (result.success) {
            const stats = result.data;
            document.getElementById('stats-container').innerHTML = `
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3>${stats.totalProduits}</h3>
                            <p class="mb-0">Produits Total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3>${stats.produitsActifs}</h3>
                            <p class="mb-0">Produits Actifs</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3>${stats.abonnementsActifs}</h3>
                            <p class="mb-0">Abonnements Actifs</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h3>${stats.revenusMensuel.toLocaleString()}</h3>
                            <p class="mb-0">Revenus (GNF)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <h3>${stats.totalCategories}</h3>
                            <p class="mb-0">Catégories</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h3>${stats.ruptureStock}</h3>
                            <p class="mb-0">Ruptures Stock</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h3>${stats.stockCritique}</h3>
                            <p class="mb-0">Stock Critique</p>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('stats-container').innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">
                    Erreur lors du chargement des statistiques: ${error.message}
                </div>
            </div>
        `;
    }
}

// Test global
async function runAllTests() {
    const globalResult = document.getElementById('global-test-result');
    globalResult.style.display = 'block';
    globalResult.className = 'test-result result-success';
    globalResult.innerHTML = '<strong>🚀 EXÉCUTION DES TESTS EN COURS...</strong><br>Veuillez patienter...';
    
    const tests = [
        { name: 'Utilisateurs', func: testUsers },
        { name: 'Catégories', func: testCategories },
        { name: 'Produits', func: testProduits },
        { name: 'Abonnements', func: testAbonnements },
        { name: 'Création produit', func: testCreateProduit },
        { name: 'Vente produit', func: testVenteProduit },
        { name: 'Paiement abonnement', func: testPaiementAbonnement },
        { name: 'Vérification abonnement', func: testVerificationAbonnement }
    ];
    
    let results = [];
    
    for (const test of tests) {
        try {
            await test.func();
            results.push(`✅ ${test.name}: SUCCÈS`);
            await new Promise(resolve => setTimeout(resolve, 500)); // Pause entre les tests
        } catch (error) {
            results.push(`❌ ${test.name}: ÉCHEC - ${error.message}`);
        }
    }
    
    const successCount = results.filter(r => r.includes('✅')).length;
    const totalCount = results.length;
    
    globalResult.innerHTML = `
        <strong>📊 RÉSULTATS DES TESTS (${successCount}/${totalCount} réussis)</strong><br>
        ${results.join('<br>')}
        <br><br>
        <strong>🎯 SCORE: ${Math.round((successCount/totalCount) * 100)}%</strong>
    `;
    
    if (successCount === totalCount) {
        globalResult.className = 'test-result result-success';
    } else {
        globalResult.className = 'test-result result-error';
    }
}

// Charger les statistiques au démarrage de la page
document.addEventListener('DOMContentLoaded', loadStats);