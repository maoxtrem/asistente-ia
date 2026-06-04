(function () {
    const widget = document.getElementById('asistente-ia-widget');

    if (!widget) {
        return;
    }

    const endpoint = widget.dataset.endpoint || '/api/v1/asistente-ia/message';
    const toggle = document.getElementById('asistente-ia-toggle');
    const panel = document.getElementById('asistente-ia-panel');
    const closeButton = document.getElementById('asistente-ia-close');
    const form = document.getElementById('asistente-ia-form');
    const input = document.getElementById('asistente-ia-input');
    const messages = document.getElementById('asistente-ia-messages');
    let conversationId = null;

    const appendMessage = (role, text) => {
        const item = document.createElement('article');
        item.className = 'asistente-ia-message asistente-ia-message--' + role;

        const paragraph = document.createElement('p');
        paragraph.textContent = text;
        item.appendChild(paragraph);

        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
    };

    const setPanelVisibility = (isOpen) => {
        panel.hidden = !isOpen;
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        setPanelVisibility(panel.hidden);
    });

    closeButton.addEventListener('click', () => {
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
        input.disabled = true;

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message,
                    conversation_id: conversationId,
                    context: {
                        pathname: window.location.pathname
                    }
                })
            });

            const payload = await response.json();
            const reply = payload?.data?.message || payload?.message || 'No hubo respuesta del asistente.';

            if (payload?.data?.conversation_id) {
                conversationId = payload.data.conversation_id;
            }

            appendMessage('assistant', reply);
        } catch (error) {
            appendMessage('assistant', 'Ocurrio un error al consultar el asistente.');
        } finally {
            input.disabled = false;
            input.focus();
        }
    });
})();
