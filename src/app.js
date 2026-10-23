document.addEventListener('DOMContentLoaded', () => {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    const revealButtons = document.querySelectorAll('.btn-reveal');
    const copyButtons = document.querySelectorAll('.btn-copy');
    let autoHideTimeout = null;

    const fetchDecryptedPassword = async (entryId) => {
        const body = new URLSearchParams({
            action: 'reveal',
            entry_id: entryId,
            csrf_token: csrfToken
        });

        const response = await fetch('/vault', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken
            },
            body: body.toString()
        });

        if (!response.ok) {
            throw new Error('Failed to decrypt password');
        }

        const result = await response.json();
        if (result.status === 'ok' && typeof result.password === 'string') {
            return result.password;
        }

        throw new Error('Invalid response');
    };

    const clearAutoHideTimeout = () => {
        if (autoHideTimeout !== null) {
            clearTimeout(autoHideTimeout);
            autoHideTimeout = null;
        }
    };

    const maskPassword = (btn) => {
        const wrapper = btn.closest('.pwd-cell-wrapper');
        const textSpan = wrapper ? wrapper.querySelector('.pwd-masked-text') : null;
        if (textSpan && textSpan.getAttribute('data-revealed') === 'true') {
            textSpan.classList.add('is-switching');
            setTimeout(() => {
                textSpan.textContent = '••••••••••••';
                textSpan.setAttribute('data-revealed', 'false');
                textSpan.classList.remove('is-revealed', 'is-switching');
            }, 150);
        } else if (textSpan) {
            textSpan.textContent = '••••••••••••';
            textSpan.setAttribute('data-revealed', 'false');
            textSpan.classList.remove('is-revealed', 'is-switching');
        }
        btn.classList.remove('is-active', 'is-loading');
        btn.disabled = false;
    };

    const maskAllPasswords = (exceptBtn = null) => {
        clearAutoHideTimeout();
        revealButtons.forEach((btn) => {
            if (btn !== exceptBtn) {
                maskPassword(btn);
            }
        });
    };

    revealButtons.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const wrapper = btn.closest('.pwd-cell-wrapper');
            const textSpan = wrapper ? wrapper.querySelector('.pwd-masked-text') : null;
            if (!textSpan) {
                return;
            }

            if (textSpan.getAttribute('data-revealed') === 'true') {
                clearAutoHideTimeout();
                maskPassword(btn);
                return;
            }

            maskAllPasswords(btn);

            const entryId = btn.getAttribute('data-entry-id');
            btn.disabled = true;
            btn.classList.add('is-loading');

            try {
                const password = await fetchDecryptedPassword(entryId);
                textSpan.classList.add('is-switching');
                setTimeout(() => {
                    textSpan.textContent = password;
                    textSpan.setAttribute('data-revealed', 'true');
                    textSpan.classList.remove('is-switching');
                    textSpan.classList.add('is-revealed');
                    btn.classList.add('is-active');
                }, 150);

                clearAutoHideTimeout();
                autoHideTimeout = setTimeout(() => {
                    maskPassword(btn);
                    autoHideTimeout = null;
                }, 5000);
            } catch (err) {
                maskPassword(btn);
            } finally {
                btn.classList.remove('is-loading');
                btn.disabled = false;
            }
        });
    });

    copyButtons.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const entryId = btn.getAttribute('data-entry-id');
            if (!entryId || btn.disabled) {
                return;
            }

            btn.disabled = true;
            btn.classList.add('is-loading');

            try {
                if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
                    const item = new ClipboardItem({
                        'text/plain': fetchDecryptedPassword(entryId).then((pwd) => new Blob([pwd], { type: 'text/plain' }))
                    });
                    await navigator.clipboard.write([item]);
                } else {
                    const password = await fetchDecryptedPassword(entryId);
                    await navigator.clipboard.writeText(password);
                }

                btn.classList.add('is-copied');
                setTimeout(() => {
                    btn.classList.remove('is-copied');
                }, 2000);
            } catch (err) {
                btn.classList.remove('is-copied');
            } finally {
                btn.classList.remove('is-loading');
                btn.disabled = false;
            }
        });
    });

    const modal = document.getElementById('credential-modal');
    const openAddBtn = document.getElementById('open-add-modal');
    const closeModalBtn = document.getElementById('close-modal');
    const cancelModalBtn = document.getElementById('cancel-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalSubmitBtn = document.getElementById('modal-submit-btn');
    const formAction = document.getElementById('form-action');
    const formEntryId = document.getElementById('form-entry-id');
    const formUrl = document.getElementById('form-url');
    const formUsername = document.getElementById('form-username');
    const formPassword = document.getElementById('form-password');
    const passwordHint = document.getElementById('password-hint');

    const openModal = () => {
        if (modal) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            if (formUrl) {
                formUrl.focus();
            }
        }
    };

    const closeModal = () => {
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (formPassword) {
                formPassword.value = '';
            }
        }
    };

    if (openAddBtn) {
        openAddBtn.addEventListener('click', () => {
            modalTitle.textContent = 'Add a Credential';
            modalSubmitBtn.textContent = 'Encrypt & Store';
            formAction.value = 'add';
            formEntryId.value = '';
            formUrl.value = '';
            formUsername.value = '';
            formPassword.value = '';
            formPassword.required = true;
            formPassword.placeholder = 'Password to encrypt';
            if (passwordHint) {
                passwordHint.hidden = true;
            }
            openModal();
        });
    }

    document.querySelectorAll('.btn-edit').forEach((editBtn) => {
        editBtn.addEventListener('click', () => {
            modalTitle.textContent = 'Edit Credential';
            modalSubmitBtn.textContent = 'Update & Re-encrypt';
            formAction.value = 'edit';
            formEntryId.value = editBtn.getAttribute('data-entry-id') || '';
            formUrl.value = editBtn.getAttribute('data-url') || '';
            formUsername.value = editBtn.getAttribute('data-username') || '';
            formPassword.value = '';
            formPassword.required = false;
            formPassword.placeholder = 'New password (optional)';
            if (passwordHint) {
                passwordHint.hidden = false;
            }
            openModal();
        });
    });

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }
    if (cancelModalBtn) {
        cancelModalBtn.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Are you sure you want to delete this credential?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});