// menu.js
// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', () => {
    // Écouter les clics sur les boutons "Ajouter au panier"
    document.querySelectorAll('.ajouter-panier').forEach(button => {
        button.addEventListener('click', (event) => {
            const target = event.currentTarget;
            const produitId = target.getAttribute('data-produit-id');
            const produitNom = target.getAttribute('data-produit-nom');
            const lotId = target.getAttribute('data-lot-id');
            const quantiteLot = target.getAttribute('data-quantite-lot');
            const prix = parseFloat(target.getAttribute('data-prix'));
            const optionNom = target.getAttribute('data-option-nom') || '';
            const optionId = target.getAttribute('data-option-id') || '0';

            // Ajouter l'article au panier
            ajouterAuPanier(produitId, produitNom, lotId, quantiteLot, prix, optionNom, optionId);
        });
    });
});

/**
 * Ajoute un article au panier (localStorage)
 */
function ajouterAuPanier(produitId, produitNom, lotId, quantiteLot, prix, optionNom = '', optionId = '0') {
    let panier = JSON.parse(localStorage.getItem('panier')) || [];
    const nomComplet = optionNom ? `${produitNom} - ${optionNom}` : produitNom;
    const itemIdentifier = `${produitId}-${lotId}-${optionId}`;
    const produitExistantIndex = panier.findIndex(item => item.itemIdentifier === itemIdentifier);

    if (produitExistantIndex !== -1) {
        panier[produitExistantIndex].quantite += 1;
    } else {
        panier.push({
            itemIdentifier: itemIdentifier,
            produit_id: parseInt(produitId),
            lot_id: parseInt(lotId),
            option_id: optionId !== '' ? parseInt(optionId) : null,
            nom: nomComplet,
            prix: parseFloat(prix),
            quantite: 1, // Quantité de lots commandés
            quantite_lot: parseInt(quantiteLot) // Quantité d'unités par lot
        });
    }

    localStorage.setItem('panier', JSON.stringify(panier));

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Ajouté au panier !',
        text: `${nomComplet} (Lot de ${quantiteLot}) ajouté.`,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });
}
