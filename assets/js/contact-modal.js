// Script pour gérer le modal de contact tuteur

/**
 * Échappe les caractères HTML pour éviter les injections XSS
 * text : Texte à échapper
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Ouvre le modal de contact avec les informations du tuteur
 * tuteurId : ID du tuteur
 * tuteurNom : Nom complet du tuteur (optionnel)
 */
function openContactModal(tuteurId, tuteurNom) {
    const modal = document.getElementById('contactModal');
    if (!modal) {
        console.error('Modal de contact non trouvé dans le DOM');
        return;
    }

    loadTuteursList(tuteurId)
        .catch(error => {
            console.error('Erreur lors du chargement des tuteurs:', error);
        })
        .finally(() => {
            // Réinitialiser le formulaire
            resetContactForm();

            // Sélectionner le tuteur si fourni
            const tuteurSelect = document.getElementById('contact-tuteur-select');
            if (tuteurSelect && tuteurId) {
                tuteurSelect.value = tuteurId;
            }

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            const sujetInput = document.getElementById('contact-sujet');
            if (sujetInput) {
                setTimeout(() => sujetInput.focus(), 100);
            }
        });
}

/**
 * Ferme le modal de contact
 */
function closeContactModal() {
    const modal = document.getElementById('contactModal');
    if (!modal) return;

    modal.classList.remove('active');
    document.body.style.overflow = '';

    // Réinitialiser le formulaire après la fermeture (petit délai pour l'anim potentielle)
    setTimeout(resetContactForm, 300);
}

/**
 * Charge la liste des tuteurs depuis l'API
 * tuteurIdPreselectionne : ID du tuteur à présélectionner (optionnel)
 */
async function loadTuteursList(tuteurIdPreselectionne = null) {
    const tuteurSelect = document.getElementById('contact-tuteur-select');
    if (!tuteurSelect) return;

    // Si déjà chargé, juste présélectionner si besoin
    if (tuteurSelect.options.length > 1) {
        if (tuteurIdPreselectionne) {
            tuteurSelect.value = tuteurIdPreselectionne;
        }
        return;
    }

    try {
        const response = await fetch('api/tuteurs.php');

        if (!response.ok) {
            throw new Error('Erreur lors du chargement des tuteurs');
        }

        const tuteurs = await response.json();

        tuteurSelect.innerHTML = '<option value="">Sélectionnez un tuteur</option>';

        tuteurs.forEach(tuteur => {
            const option = document.createElement('option');
            option.value = tuteur.id;
            option.textContent = tuteur.nom_complet + (tuteur.departement ? ` (${tuteur.departement})` : '');
            tuteurSelect.appendChild(option);
        });

        if (tuteurIdPreselectionne) {
            tuteurSelect.value = tuteurIdPreselectionne;
        }
    } catch (error) {
        console.error('Erreur lors du chargement des tuteurs:', error);
        tuteurSelect.innerHTML = '<option value="">Erreur lors du chargement des tuteurs</option>';
        throw error;
    }
}

/**
 * Réinitialise le formulaire de contact
 */
function resetContactForm() {
    const sujetInput = document.getElementById('contact-sujet');
    const contenuInput = document.getElementById('contact-contenu');
    const prioriteSelect = document.getElementById('contact-priorite');
    const tuteurSelect = document.getElementById('contact-tuteur-select');
    const errorDiv = document.getElementById('contact-error');
    const submitBtn = document.getElementById('btnContactSubmit');

    if (sujetInput) sujetInput.value = '';
    if (contenuInput) contenuInput.value = '';
    if (prioriteSelect) prioriteSelect.value = '';
    if (tuteurSelect) tuteurSelect.value = '';

    updateCharCount();

    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }

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

    if (!textarea || !counter) return;

    const length = textarea.value.length;
    counter.textContent = length;

    if (length > 450) {
        counter.style.color = 'var(--accent-color)';
    } else {
        counter.style.color = 'var(--text-light)';
    }
}

/**
 * Affiche un message d'erreur dans le formulaire
 */
function showContactError(message) {
    const errorDiv = document.getElementById('contact-error');
    if (!errorDiv) return;

    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Masque le message d'erreur
 */
function hideContactError() {
    const errorDiv = document.getElementById('contact-error');
    if (!errorDiv) return;

    errorDiv.style.display = 'none';
    errorDiv.textContent = '';
}

/**
 * Affiche une notification de succès (toast)
 */
function showSuccessNotification(message) {
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
        <button type="button" style="
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

    const closeBtn = notification.querySelector('button');
    closeBtn.addEventListener('click', () => notification.remove());

    notificationContainer.appendChild(notification);

    setTimeout(() => {
        if (!notification.parentElement) return;
        notification.style.animation = 'slideInRight 0.3s reverse';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
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

    const tuteurId = document.getElementById('contact-tuteur-select')?.value;
    const sujet = document.getElementById('contact-sujet')?.value.trim();
    const contenu = document.getElementById('contact-contenu')?.value.trim();
    const priorite = document.getElementById('contact-priorite')?.value || null;
    const submitBtn = document.getElementById('btnContactSubmit');

    // Validation
    if (!tuteurId) {
        showContactError('Veuillez sélectionner un tuteur');
        document.getElementById('contact-tuteur-select')?.focus();
        return;
    }

    if (!sujet) {
        showContactError('Le sujet est requis');
        document.getElementById('contact-sujet')?.focus();
        return;
    }

    if (sujet.length > 255) {
        showContactError('Le sujet ne peut pas dépasser 255 caractères');
        document.getElementById('contact-sujet')?.focus();
        return;
    }

    if (!contenu) {
        showContactError('Le message est requis');
        document.getElementById('contact-contenu')?.focus();
        return;
    }

    if (contenu.length > 500) {
        showContactError('Le message ne peut pas dépasser 500 caractères');
        document.getElementById('contact-contenu')?.focus();
        return;
    }

    hideContactError();

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';
    }

    const data = {
        tuteur_id: tuteurId,
        sujet,
        contenu
    };

    if (priorite) {
        data.priorite = priorite;
    }

    try {
        const response = await fetch('api/messages.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || 'Erreur lors de l\'envoi du message');
        }

        showSuccessNotification('Message envoyé avec succès !');
        closeContactModal();
    } catch (error) {
        console.error('Erreur lors de l\'envoi du message:', error);
        showContactError(error.message || 'Erreur lors de l\'envoi du message. Veuillez réessayer.');

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Envoyer';
        }
    }
}

// Gestion des événements pour le modal de contact
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('contactModal');
    if (!modal) return;

    const btnClose = modal.querySelector('.contact-modal-close');
    const overlay = modal.querySelector('.contact-modal-overlay');
    const textarea = document.getElementById('contact-contenu');
    const form = document.getElementById('contactForm');
    const btnCancel = document.getElementById('btnContactCancel');
    const modalContent = modal.querySelector('.contact-modal-content');
    const contactButtons = document.querySelectorAll('.btn-contact-tuteur');

    if (btnClose) {
        btnClose.addEventListener('click', closeContactModal);
    }

    if (overlay) {
        overlay.addEventListener('click', closeContactModal);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeContactModal();
        }
    });

    if (textarea) {
        textarea.addEventListener('input', updateCharCount);
        updateCharCount();
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            submitContactForm();
        });
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', closeContactModal);
    }

    if (modalContent) {
        modalContent.addEventListener('click', (e) => e.stopPropagation());
    }

    contactButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const tuteurId = button.getAttribute('data-tuteur-id');
            const tuteurNom = button.getAttribute('data-tuteur-nom');

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
