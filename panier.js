// panier.js
// Variables globales
const cartItemsContainer = document.getElementById("cart-items");
const emptyCartMessage = document.getElementById("empty-cart-message");
const clearCartButton = document.querySelector(".btn-outline-danger");
const subtotalElement = document.getElementById("subtotal");
const cartTotalElement = document.getElementById("cart-total");
const shippingFeesElement = document.getElementById("shipping-fees-amount");
const checkoutButton = document.querySelector(".btn-checkout");
const SHIPPING_FEES = 1000; // Frais de livraison fixes

// Charger le panier depuis le localStorage
function loadCart() {
    const panier = JSON.parse(localStorage.getItem('panier')) || [];
    renderCart(panier);
}

// Sauvegarder le panier dans le localStorage
function saveCart(panier) {
    localStorage.setItem('panier', JSON.stringify(panier));
}

// Afficher le panier
function renderCart(panier) {
    cartItemsContainer.innerHTML = "";

    if (panier.length === 0) {
        emptyCartMessage.style.display = "block";
        clearCartButton.style.display = "none";
        subtotalElement.textContent = "0 FCFA";
        shippingFeesElement.textContent = "0 FCFA";
        cartTotalElement.textContent = "0 FCFA";
        return;
    }

    emptyCartMessage.style.display = "none";
    clearCartButton.style.display = "inline-block";

    let subtotal = 0;

    panier.forEach((item, index) => {
        const itemTotal = item.prix * item.quantite;
        subtotal += itemTotal;

        const div = document.createElement("div");
        div.classList.add("list-group-item", "d-flex", "justify-content-between", "align-items-center");
        div.innerHTML = `
            <div>
                <strong>${item.nom}</strong> <br>
                <small>${item.quantite} x Lot de ${item.quantite_lot} (${item.prix.toLocaleString()} FCFA)</small>
            </div>
            <div>
                <span class="fw-bold">${itemTotal.toLocaleString()} FCFA</span>
                <button class="btn btn-sm btn-danger ms-2" onclick="removeItem(${index})">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
        cartItemsContainer.appendChild(div);
    });

    subtotalElement.textContent = `${subtotal.toLocaleString()} FCFA`;
    shippingFeesElement.textContent = `${SHIPPING_FEES.toLocaleString()} FCFA`;
    cartTotalElement.textContent = `${(subtotal + SHIPPING_FEES).toLocaleString()} FCFA`;
}

// Supprimer un article du panier
function removeItem(index) {
    const panier = JSON.parse(localStorage.getItem('panier')) || [];
    panier.splice(index, 1);
    saveCart(panier);
    renderCart(panier);
}

// Vider le panier
function clearCart() {
    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Tout le panier sera supprimé.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Oui, vider",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            localStorage.removeItem('panier');
            renderCart([]);
            Swal.fire("Vidé!", "Votre panier est maintenant vide.", "success");
        }
    });
}

// Valider la commande
function checkout() {
    const panier = JSON.parse(localStorage.getItem('panier')) || [];

    if (panier.length === 0) {
        Swal.fire("Panier vide", "Veuillez ajouter des articles avant de commander.", "error");
        return;
    }

    const form = document.getElementById("shipping-form");

    if (!form.checkValidity()) {
        form.reportValidity();
        Swal.fire("Erreur", "Veuillez remplir tous les champs obligatoires.", "error");
        return;
    }

    const formData = new FormData(form);
    const orderData = {
        client: {
            nom: formData.get("full_name"),
            email: formData.get("email"),
            phone: formData.get("phone"),
            city: formData.get("city"),
            address: formData.get("address"),
            comments: formData.get("comments")
        },
        panier: panier
    };

    Swal.fire({
        title: 'Traitement de la commande...',
        text: 'Veuillez patienter.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch("checkout.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(orderData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur HTTP! Statut: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({
                title: "Commande validée 🎉",
                text: data.message,
                icon: "success",
                confirmButtonText: "Voir la facture"
            }).then(() => {
                localStorage.removeItem('panier');
                window.location.href = `facture.php?id=${data.commande_id}`;
            });
        } else {
            Swal.fire("Erreur", data.message, "error");
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Erreur:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erreur Réseau',
            text: 'Une erreur est survenue. Veuillez vérifier votre connexion et réessayer.'
        });
    });
}


// Initialisation
document.addEventListener("DOMContentLoaded", loadCart);
if (checkoutButton) checkoutButton.addEventListener('click', checkout);
if (clearCartButton) clearCartButton.addEventListener('click', clearCart);
