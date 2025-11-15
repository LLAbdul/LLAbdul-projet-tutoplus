// Script pour gérer le modal de contact tuteur

/**
 * Ouvre le modal de contact avec les informations du tuteur
 * tuteurId : ID du tuteur
 * tuteurNom : Nom complet du tuteur
 */
function openContactModal(tuteurId, tuteurNom) {
    const modal = document.getElementById('contactModal');
    if (!modal) {
        console.error('Modal de contact non trouvé dans le DOM');
        return;
    }
    
    // Remplir les champs du formulaire
    const tuteurIdInput = document.getElementById('contact-tuteur-id');
    const tuteurNameInput = document.getElementById('contact-tuteur-name');
    
    if (tuteurIdInput) {
        tuteurIdInput.value = tuteurId;
    }
    
    if (tuteurNameInput) {
        tuteurNameInput.value = tuteurNom || '';
    }
    
    // Réinitialiser le formulaire
    resetContactForm();
    
    // Afficher le modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Focus sur le champ sujet
    const sujetInput = document.getElementById('contact-sujet');
    if (sujetInput) {
        setTimeout(() => sujetInput.focus(), 100);
    }
}

/**
 * Ferme le modal de contact
 */
function closeContactModal() {
    const modal = document.getElementById('contactModal');
    if (!modal) {
        return;
    }
    
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Réinitialiser le formulaire après la fermeture
    setTimeout(() => {
        resetContactForm();
    }, 300);
}

/**
 * Réinitialise le formulaire de contact
 */
function resetContactForm() {
    const form = document.getElementById('contactForm');
    if (form) {
        form.reset();
    }
    
    // Réinitialiser le compteur de caractères
    updateCharCount();
    
    // Masquer les messages d'erreur
    const errorDiv = document.getElementById('contact-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
    
    // Réinitialiser l'état du bouton submit
    const submitBtn = document.getElementById('btnContactSubmit');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Envoyer';
    }
}

/**
 * Met à jour le compteur de caractères pour le message
 */
function updateCharCount() {
    const textarea = document.getElementById('contact-contenu');
    const counter = document.getElementById('char-count');
    
    if (textarea && counter) {
        const length = textarea.value.length;
        counter.textContent = length;
        
        // Changer la couleur si proche de la limite
        if (length > 450) {
            counter.style.color = 'var(--accent-color)';
        } else {
            counter.style.color = 'var(--text-light)';
        }
    }
}

/**
 * Affiche un message d'erreur dans le formulaire
 * message : Message d'erreur à afficher
 */
function showContactError(message) {
    const errorDiv = document.getElementById('contact-error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        // Scroll vers l'erreur
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

/**
 * Masque le message d'erreur
 */
function hideContactError() {
    const errorDiv = document.getElementById('contact-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
}

/**
 * Envoie le message de contact via l'API
 */
async function submitContactForm() {
    const form = document.getElementById('contactForm');
    if (!form) {
        console.error('Formulaire de contact non trouvé');
        return;
    }
    
    // Récupérer les données du formulaire
    const tuteurId = document.getElementById('contact-tuteur-id')?.value;
    const sujet = document.getElementById('contact-sujet')?.value.trim();
    const contenu = document.getElementById('contact-contenu')?.value.trim();
    const priorite = document.getElementById('contact-priorite')?.value || null;
    
    // Validation côté client
    if (!tuteurId) {
        showContactError('Erreur : ID du tuteur manquant');
        return;
    }
    
    if (!sujet || sujet.length === 0) {
        showContactError('Le sujet est requis');
        document.getElementById('contact-sujet')?.focus();
        return;
    }
    
    if (sujet.length > 255) {
        showContactError('Le sujet ne peut pas dépasser 255 caractères');
        document.getElementById('contact-sujet')?.focus();
        return;
    }
    
    if (!contenu || contenu.length === 0) {
        showContactError('Le message est requis');
        document.getElementById('contact-contenu')?.focus();
        return;
    }
    
    if (contenu.length > 500) {
        showContactError('Le message ne peut pas dépasser 500 caractères');
        document.getElementById('contact-contenu')?.focus();
        return;
    }
    
    // Masquer les erreurs précédentes
    hideContactError();
    
    // Désactiver le bouton submit pendant l'envoi
    const submitBtn = document.getElementById('btnContactSubmit');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';
    }
    
    // Préparer les données à envoyer
    const data = {
        tuteur_id: tuteurId,
        sujet: sujet,
        contenu: contenu
    };
    
    if (priorite) {
        data.priorite = priorite;
    }
    
    try {
        const response = await fetch('api/messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Erreur lors de l\'envoi du message');
        }
        
        // Succès : afficher un message et fermer le modal
        alert('Message envoyé avec succès !');
        closeContactModal();
        
    } catch (error) {
        console.error('Erreur lors de l\'envoi du message:', error);
        showContactError(error.message || 'Erreur lors de l\'envoi du message. Veuillez réessayer.');
        
        // Réactiver le bouton submit
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Envoyer';
        }
    }
}

// Gestion des événements pour le modal de contact
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('contactModal');
    if (!modal) {
        return;
    }
    
    // Bouton de fermeture
    const btnClose = modal.querySelector('.contact-modal-close');
    if (btnClose) {
        btnClose.addEventListener('click', closeContactModal);
    }
    
    // Fermer avec l'overlay
    const overlay = modal.querySelector('.contact-modal-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeContactModal);
    }
    
    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeContactModal();
        }
    });
    
    // Compteur de caractères pour le textarea
    const textarea = document.getElementById('contact-contenu');
    if (textarea) {
        textarea.addEventListener('input', updateCharCount);
        // Initialiser le compteur
        updateCharCount();
    }
    
    // Gestion de la soumission du formulaire
    const form = document.getElementById('contactForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitContactForm();
        });
    }
    
    // Bouton Annuler
    const btnCancel = document.getElementById('btnContactCancel');
    if (btnCancel) {
        btnCancel.addEventListener('click', closeContactModal);
    }
    
    // Empêcher la fermeture du modal en cliquant dans le contenu
    const modalContent = modal.querySelector('.contact-modal-content');
    if (modalContent) {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Gérer les clics sur les boutons "Contacter le tuteur"
    const contactButtons = document.querySelectorAll('.btn-contact-tuteur');
    contactButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const tuteurId = this.getAttribute('data-tuteur-id');
            const tuteurNom = this.getAttribute('data-tuteur-nom');
            
            if (tuteurId) {
                openContactModal(tuteurId, tuteurNom);
            } else {
                console.error('ID du tuteur manquant sur le bouton de contact');
            }
        });
    });
});

// Rendre la fonction openContactModal accessible globalement
window.openContactModal = openContactModal;

