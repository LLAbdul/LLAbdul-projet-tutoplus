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
    
    // Charger la liste des tuteurs si ce n'est pas déjà fait
    loadTuteursList(tuteurId).then(() => {
        // Réinitialiser le formulaire d'abord
        resetContactForm();
        
        // Sélectionner le tuteur si fourni
        const tuteurSelect = document.getElementById('contact-tuteur-select');
        if (tuteurSelect && tuteurId) {
            tuteurSelect.value = tuteurId;
        }
        
        // Afficher le modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus sur le champ sujet
        const sujetInput = document.getElementById('contact-sujet');
        if (sujetInput) {
            setTimeout(() => sujetInput.focus(), 100);
        }
    }).catch(error => {
        console.error('Erreur lors du chargement des tuteurs:', error);
        // Afficher le modal quand même
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
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
 * Charge la liste des tuteurs depuis l'API
 * tuteurIdPreselectionne : ID du tuteur à présélectionner (optionnel)
 */
async function loadTuteursList(tuteurIdPreselectionne = null) {
    const tuteurSelect = document.getElementById('contact-tuteur-select');
    if (!tuteurSelect) {
        return;
    }
    
    // Si la liste est déjà chargée, ne pas recharger
    if (tuteurSelect.options.length > 1) {
        if (tuteurIdPreselectionne) {
            tuteurSelect.value = tuteurIdPreselectionne;
        }
        return;
    }
    
    try {
        const response = await fetch('api/tuteurs.php');
        const tuteurs = await response.json();
        
        if (!response.ok) {
            throw new Error('Erreur lors du chargement des tuteurs');
        }
        
        // Vider le select (garder l'option par défaut)
        tuteurSelect.innerHTML = '<option value="">Sélectionnez un tuteur</option>';
        
        // Ajouter les tuteurs
        tuteurs.forEach(tuteur => {
            const option = document.createElement('option');
            option.value = tuteur.id;
            option.textContent = tuteur.nom_complet + (tuteur.departement ? ` (${tuteur.departement})` : '');
            tuteurSelect.appendChild(option);
        });
        
        // Présélectionner le tuteur si fourni
        if (tuteurIdPreselectionne) {
            tuteurSelect.value = tuteurIdPreselectionne;
        }
    } catch (error) {
        console.error('Erreur lors du chargement des tuteurs:', error);
        // Afficher un message d'erreur dans le select
        tuteurSelect.innerHTML = '<option value="">Erreur lors du chargement des tuteurs</option>';
    }
}

/**
 * Réinitialise le formulaire de contact
 */
function resetContactForm() {
    // Réinitialiser les champs modifiables
    const sujetInput = document.getElementById('contact-sujet');
    const contenuInput = document.getElementById('contact-contenu');
    const prioriteSelect = document.getElementById('contact-priorite');
    const tuteurSelect = document.getElementById('contact-tuteur-select');
    
    if (sujetInput) {
        sujetInput.value = '';
    }
    
    if (contenuInput) {
        contenuInput.value = '';
    }
    
    if (prioriteSelect) {
        prioriteSelect.value = '';
    }
    
    if (tuteurSelect) {
        tuteurSelect.value = '';
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
 * Affiche une notification de succès (toast)
 * message : Message à afficher
 */
function showSuccessNotification(message) {
    // Créer le conteneur de notification s'il n'existe pas
    let notificationContainer = document.getElementById('toast-notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'toast-notification-container';
        notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(notificationContainer);
    }
    
    // Créer la notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 300px;
        max-width: 500px;
        animation: slideInRight 0.3s ease-out;
    `;
    
    notification.innerHTML = `
        <span style="font-size: 1.25rem; line-height: 1;">✓</span>
        <span style="flex: 1; font-weight: 500;">${escapeHtml(message)}</span>
        <button type="button" onclick="this.parentElement.remove()" style="
            background: none;
            border: none;
            color: #155724;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        ">&times;</button>
    `;
    
    notificationContainer.appendChild(notification);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideInRight 0.3s reverse';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

/**
 * Échappe les caractères HTML pour éviter les injections XSS
 * text : Texte à échapper
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
    const tuteurId = document.getElementById('contact-tuteur-select')?.value;
    const sujet = document.getElementById('contact-sujet')?.value.trim();
    const contenu = document.getElementById('contact-contenu')?.value.trim();
    const priorite = document.getElementById('contact-priorite')?.value || null;
    
    // Validation côté client
    if (!tuteurId) {
        showContactError('Veuillez sélectionner un tuteur');
        document.getElementById('contact-tuteur-select')?.focus();
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
        
        // Succès : afficher une notification et fermer le modal
        showSuccessNotification('Message envoyé avec succès !');
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

