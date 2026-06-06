(function () {
    const widget = document.getElementById('asistente-ia-widget');

    if (!widget) {
        console.warn('[AsistenteIA] Widget no encontrado en la pagina');
        return;
    }

    const endpoint = widget.dataset.endpoint || '/api/v1/asistente-ia/message';
    const locale = normalizeLocale(window.locale || widget.dataset.locale || document.documentElement.lang || navigator.language || '');
    const toggle = document.getElementById('asistente-ia-toggle');
    const panel = document.getElementById('asistente-ia-panel');
    const closeButton = document.getElementById('asistente-ia-close');
    const form = document.getElementById('asistente-ia-form');
    const input = document.getElementById('asistente-ia-input');
    const submitButton = document.getElementById('asistente-ia-submit');
    const messages = document.getElementById('asistente-ia-messages');
    let conversationId = null;
    let typingIndicator = null;

    const isSafeInternalRoute = (href) => {
        return typeof href === 'string' && /^(\/(?!\/)|#)/.test(href);
    };

    const appendMessage = (role, text, links = []) => {
        const item = document.createElement('article');
        item.className = 'asistente-ia-message asistente-ia-message--' + role;

        const paragraph = document.createElement('p');
        paragraph.textContent = text;
        item.appendChild(paragraph);

        if (Array.isArray(links) && links.length > 0) {
            const list = document.createElement('div');
            list.className = 'asistente-ia-message__links';

            for (const link of links) {
                if (!link || typeof link !== 'object' || !isSafeInternalRoute(link.href)) {
                    continue;
                }

                const anchor = document.createElement('a');
                anchor.className = 'asistente-ia-message__link';
                anchor.href = link.href;
                anchor.textContent = link.label || link.href;
                anchor.target = '_self';
                anchor.rel = 'noopener';
                list.appendChild(anchor);
            }

            if (list.childElementCount > 0) {
                item.appendChild(list);
            }
        }

        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
    };

    const setSendingState = (isSending) => {
        input.disabled = isSending;
        submitButton.disabled = isSending;
        submitButton.setAttribute('aria-label', isSending ? 'Enviando mensaje' : 'Enviar mensaje');
        submitButton.setAttribute('title', isSending ? 'Enviando...' : 'Enviar mensaje');
        widget.classList.toggle('is-sending', isSending);
    };

    const showTypingIndicator = () => {
        if (typingIndicator) {
            return;
        }

        typingIndicator = document.createElement('article');
        typingIndicator.className = 'asistente-ia-message asistente-ia-message--assistant asistente-ia-message--typing';
        typingIndicator.innerHTML = '<p><span></span><span></span><span></span></p>';
        messages.appendChild(typingIndicator);
        messages.scrollTop = messages.scrollHeight;
    };

    const hideTypingIndicator = () => {
        if (!typingIndicator) {
            return;
        }

        typingIndicator.remove();
        typingIndicator = null;
    };

    const setPanelVisibility = (isOpen) => {
        console.log('[AsistenteIA] setPanelVisibility', { isOpen });
        panel.classList.toggle('is-open', isOpen);
        panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (isOpen) {
            input.focus();
        }
    };

    input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.shiftKey) {
            return;
        }

        event.preventDefault();

        if (form.requestSubmit) {
            form.requestSubmit();
            return;
        }

        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    });

    toggle.addEventListener('click', () => {
        console.log('[AsistenteIA] click toggle', {
            isOpen: panel.classList.contains('is-open'),
            ariaExpanded: toggle.getAttribute('aria-expanded')
        });
        setPanelVisibility(!panel.classList.contains('is-open'));
    });

    closeButton.addEventListener('click', () => {
        console.log('[AsistenteIA] click close');
        setPanelVisibility(false);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const message = input.value.trim();
        if (message === '') {
            return;
        }

        appendMessage('user', message);
        input.value = '';
        setSendingState(true);
        showTypingIndicator();

        try {
            console.log('[AsistenteIA] submit message', { endpoint, message, conversationId });
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message,
                    conversation_id: conversationId,
                    locale,
                    context: {
                        pathname: window.location.pathname
                    }
                })
            });

            const payload = await response.json().catch(() => null);

            if (!response.ok || !payload?.ok) {
                const errorMessage = payload?.error?.message || payload?.message || 'No fue posible consultar el asistente.';
                appendMessage('assistant', errorMessage);
                return;
            }

            const reply = payload?.data?.message || payload?.message || 'No hubo respuesta del asistente.';
            const links = Array.isArray(payload?.data?.links) ? payload.data.links : [];

            if (payload?.data?.conversation_id) {
                conversationId = payload.data.conversation_id;
            }

            appendMessage('assistant', reply, links);
        } catch (error) {
            console.error('[AsistenteIA] error al consultar el asistente', error);
            appendMessage('assistant', 'Ocurrio un error al consultar el asistente.');
        } finally {
            hideTypingIndicator();
            setSendingState(false);
            input.focus();
        }
    });
})();

function normalizeLocale(value) {
    const normalized = String(value || '').trim().toLowerCase();
    if (!normalized) {
        return '';
    }

    const compact = normalized.replace('_', '-');
    return compact;
}
