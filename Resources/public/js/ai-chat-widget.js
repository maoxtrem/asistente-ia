(function () {
    if (window.__ospAsistenteIaWidgetInitialized) {
        return;
    }
    window.__ospAsistenteIaWidgetInitialized = true;

    const widget = document.getElementById('asistente-ia-widget');

    if (!widget) {
        console.warn('[AsistenteIA] Widget no encontrado en la pagina');
        return;
    }

    applyTheme(widget);

    const endpoint = widget.dataset.endpoint || '/api/v1/asistente-ia/message';
    const vectorFormUrl = widget.dataset.vectorFormUrl || '';
    const locale = normalizeLocale(window.locale || widget.dataset.locale || document.documentElement.lang || navigator.language || '');
    const toggle = document.getElementById('asistente-ia-toggle');
    const panel = document.getElementById('asistente-ia-panel');
    const closeButton = document.getElementById('asistente-ia-close');
    const form = document.getElementById('asistente-ia-form');
    const input = document.getElementById('asistente-ia-input');
    const submitButton = document.getElementById('asistente-ia-submit');
    const messages = document.getElementById('asistente-ia-messages');

    if (!panel || !form || !input || !submitButton || !messages) {
        console.warn('[AsistenteIA] Faltan nodos del widget');
        return;
    }

    if (panel.parentElement !== document.body) {
        document.body.appendChild(panel);
        console.log('[AsistenteIA] panel reparented', {
            parentTag: panel.parentElement ? panel.parentElement.tagName : null,
            parentId: panel.parentElement ? panel.parentElement.id || null : null,
        });
    }

    console.log('[AsistenteIA] widget init', {
        hasToggle: Boolean(toggle),
        hasClose: Boolean(closeButton),
        hasPanel: Boolean(panel),
        path: window.location.pathname,
    });

    let conversationId = null;
    let typingIndicator = null;
    let visibilitySeq = 0;
    let lastOpenSeq = 0;

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

    const logPanelState = (label) => {
        const panelStyles = window.getComputedStyle(panel);
        const panelRect = panel.getBoundingClientRect();
        console.log('[AsistenteIA] panel state', {
            label,
            isOpen: panel.classList.contains('is-open'),
            ariaHidden: panel.getAttribute('aria-hidden'),
            ariaExpanded: toggle ? toggle.getAttribute('aria-expanded') : null,
            visibility: panelStyles.visibility,
            opacity: panelStyles.opacity,
            pointerEvents: panelStyles.pointerEvents,
            display: panelStyles.display,
            width: panelStyles.width,
            height: panelStyles.height,
            offsetWidth: panel.offsetWidth,
            offsetHeight: panel.offsetHeight,
            offsetParent: panel.offsetParent ? panel.offsetParent.id || panel.offsetParent.className || panel.offsetParent.tagName : null,
            rect: {
                x: panelRect.x,
                y: panelRect.y,
                width: panelRect.width,
                height: panelRect.height,
                top: panelRect.top,
                right: panelRect.right,
                bottom: panelRect.bottom,
                left: panelRect.left,
            },
        });
    };

    const setPanelVisibility = (isOpen, reason = 'unknown') => {
        const seq = ++visibilitySeq;
        console.log('[AsistenteIA] setPanelVisibility', { isOpen, reason });
        console.trace('[AsistenteIA] visibility trace', { seq, isOpen, reason });
        panel.classList.toggle('is-open', isOpen);
        panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (toggle) {
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
        if (isOpen) {
            lastOpenSeq = seq;
            input.focus();
        }
        logPanelState(reason);

        if (isOpen) {
            window.setTimeout(() => {
                if (lastOpenSeq !== seq) {
                    return;
                }

                console.log('[AsistenteIA] delayed open check', {
                    seq,
                    stillOpen: panel.classList.contains('is-open'),
                    ariaHidden: panel.getAttribute('aria-hidden'),
                    ariaExpanded: toggle ? toggle.getAttribute('aria-expanded') : null,
                });
                logPanelState(`delayed-open-check-${seq}`);
            }, 250);
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

    document.addEventListener('click', (event) => {
        if (event.__ospAsistenteIaHandled) {
            return;
        }

        const trigger = event.target instanceof Element ? event.target.closest('#asistente-ia-toggle') : null;
        if (!trigger) {
            const closeTrigger = event.target instanceof Element ? event.target.closest('#asistente-ia-close') : null;
            if (closeTrigger) {
                console.log('[AsistenteIA] click close');
                setPanelVisibility(false, 'click-close');
                event.__ospAsistenteIaHandled = true;
                event.stopPropagation();
            } else {
                console.log('[AsistenteIA] click ignored', {
                    targetTag: event.target instanceof Element ? event.target.tagName : null,
                    targetId: event.target instanceof Element ? event.target.id : null,
                    path: window.location.pathname,
                });
            }
            return;
        }

        if (event.ctrlKey || event.metaKey) {
            console.log('[AsistenteIA] click toggle ignored by modifier', {
                ctrlKey: event.ctrlKey,
                metaKey: event.metaKey,
            });
            event.preventDefault();
            event.__ospAsistenteIaHandled = true;
            event.stopPropagation();
            return;
        }

        event.preventDefault();
        event.__ospAsistenteIaHandled = true;
        event.stopImmediatePropagation?.();
        event.stopPropagation();
        console.log('[AsistenteIA] click toggle', {
            isOpen: panel.classList.contains('is-open'),
            ariaExpanded: trigger.getAttribute('aria-expanded')
        });
        setPanelVisibility(!panel.classList.contains('is-open'), 'click-toggle');
    });

    document.addEventListener('dblclick', (event) => {
        if (event.__ospAsistenteIaHandled) {
            return;
        }

        const trigger = event.target instanceof Element ? event.target.closest('#asistente-ia-toggle') : null;
        if (!trigger || !vectorFormUrl || !(event.ctrlKey || event.metaKey)) {
            return;
        }

        event.preventDefault();
        event.__ospAsistenteIaHandled = true;
        event.stopPropagation();
        console.log('[AsistenteIA] dblclick toggle open vector form', { vectorFormUrl });
        window.open(vectorFormUrl, '_blank', 'noopener,noreferrer');
    }, true);

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

function applyTheme(widget) {
    const rootStyles = getComputedStyle(document.documentElement);
    const bodyStyles = document.body ? getComputedStyle(document.body) : null;

    const pick = (...names) => {
        for (const name of names) {
            const value = readCssVar(rootStyles, bodyStyles, name);
            if (value) {
                return value;
            }
        }

        return '';
    };

    const accent = pick('--color-acento', '--brand', '--primary-accent', '--color-primario', '--primary', '--bs-primary') || '#ffbf00';
    const accentSoft = pick('--color-acento-claro', '--brand-soft', '--primary-hover', '--color-secundario', '--secondary', '--bs-secondary') || mixHex(accent, '#ffffff', 0.22);
    const panelBg = '#0d1321';
    const panelBgSoft = '#101623';
    const text = pick('--text-main', '--text', '--ui-text', '--body-color', '--bs-body-color') || '#e6eefc';
    const textOnAccent = pick('--text-on-primary', '--on-primary', '--primary-text', '--text-ice', '--bs-light') || '#2b2300';
    const assistantBubbleBg = '#ffffff';
    const userBubbleBg = pick('--ai-user-bubble-bg', '--bubble-user') || accent;
    const panelBorder = pick('--ai-panel-border', '--border-color', '--bs-border-color') || 'rgba(255, 255, 255, 0.12)';
    const inputBg = '#ffffff';
    const messageLinkBg = 'rgba(255, 255, 255, 0.12)';
    const messageLinkText = pick('--ai-link-text') || '#f8fafc';

    widget.style.setProperty('--ai-accent', accent);
    widget.style.setProperty('--ai-accent-soft', accentSoft);
    widget.style.setProperty('--ai-panel-bg', panelBg);
    widget.style.setProperty('--ai-panel-bg-soft', panelBgSoft);
    widget.style.setProperty('--ai-panel-border', panelBorder);
    widget.style.setProperty('--ai-text', text);
    widget.style.setProperty('--ai-text-on-accent', textOnAccent);
    widget.style.setProperty('--ai-assistant-bubble-bg', assistantBubbleBg);
    widget.style.setProperty('--ai-user-bubble-bg', userBubbleBg);
    widget.style.setProperty('--ai-input-bg', inputBg);
    widget.style.setProperty('--ai-link-bg', messageLinkBg);
    widget.style.setProperty('--ai-link-text', messageLinkText);
}

function readCssVar(rootStyles, bodyStyles, name) {
    const rootValue = rootStyles.getPropertyValue(name).trim();
    if (rootValue) {
        return rootValue;
    }

    if (bodyStyles) {
        const bodyValue = bodyStyles.getPropertyValue(name).trim();
        if (bodyValue) {
            return bodyValue;
        }
    }

    return '';
}

function mixHex(colorA, colorB, ratio) {
    const parsedA = parseCssColor(colorA);
    const parsedB = parseCssColor(colorB);

    if (!parsedA || !parsedB) {
        return colorA;
    }

    const mix = (a, b) => Math.round(a + (b - a) * ratio);
    const r = mix(parsedA.r, parsedB.r);
    const g = mix(parsedA.g, parsedB.g);
    const b = mix(parsedA.b, parsedB.b);

    return `rgb(${r} ${g} ${b})`;
}

function parseCssColor(value) {
    const input = String(value || '').trim();
    if (!input) {
        return null;
    }

    const hex = input.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
    if (hex) {
        let normalized = hex[1];
        if (normalized.length === 3) {
            normalized = normalized.split('').map((char) => char + char).join('');
        }

        const int = Number.parseInt(normalized, 16);

        return {
            r: (int >> 16) & 255,
            g: (int >> 8) & 255,
            b: int & 255,
        };
    }

    const rgb = input.match(/^rgba?\(([^)]+)\)$/i);
    if (rgb) {
        const parts = rgb[1].split(',').map((part) => Number.parseFloat(part.trim()));
        if (parts.length >= 3 && parts.every((value) => Number.isFinite(value))) {
            return {
                r: parts[0],
                g: parts[1],
                b: parts[2],
            };
        }
    }

    return null;
}

function normalizeLocale(value) {
    const normalized = String(value || '').trim().toLowerCase();
    if (!normalized) {
        return '';
    }

    const compact = normalized.replace('_', '-');
    return compact;
}
