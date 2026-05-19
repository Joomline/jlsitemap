document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('controlPanel');
    const options = Joomla.getOptions('com_jlsitemap.controlpanel') || {};
    const genericError = options.genericError || 'An error has occurred.';

    if (!panel || typeof Joomla === 'undefined' || typeof Joomla.request !== 'function') {
        return;
    }

    const collectMessages = (response, fallbackType) => {
        const messages = [];
        const seen = new Set();

        const addMessage = (type, text) => {
            if (!text) {
                return;
            }

            const messageType = type || fallbackType || 'message';
            const key = `${messageType}:${text}`;

            if (seen.has(key)) {
                return;
            }

            seen.add(key);
            messages.push({
                type: messageType,
                text,
            });
        };

        const appendMessages = (items) => {
            if (!items) {
                return;
            }

            if (Array.isArray(items)) {
                items.forEach((item) => {
                    if (item && typeof item === 'object') {
                        addMessage(item.type, item.text || item.message);
                    }
                });

                return;
            }

            if (typeof items === 'object') {
                Object.keys(items).forEach((type) => {
                    const texts = Array.isArray(items[type]) ? items[type] : [items[type]];

                    texts.forEach((text) => {
                        addMessage(type, text);
                    });
                });
            }
        };

        if (response && response.message) {
            addMessage(fallbackType, response.message);
        }

        appendMessages(response && response.messages);
        appendMessages(response && response.data && response.data.messages);

        return messages;
    };

    const renderMessages = (items) => {
        if (!Array.isArray(items) || !items.length || typeof Joomla.renderMessages !== 'function') {
            return;
        }

        const queue = {};

        items.forEach((item) => {
            const type = item && item.type ? item.type : 'message';
            const text = item && item.text ? item.text : '';

            if (!text) {
                return;
            }

            if (!queue[type]) {
                queue[type] = [];
            }

            queue[type].push(text);
        });

        if (Object.keys(queue).length) {
            Joomla.renderMessages(queue);
        }
    };

    const refreshPanel = () => {
        fetch(window.location.href, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    return '';
                }

                return response.text();
            })
            .then((html) => {
                if (!html) {
                    return;
                }

                const doc = new DOMParser().parseFromString(html, 'text/html');
                const updatedPanel = doc.getElementById('controlPanel');

                if (updatedPanel) {
                    panel.innerHTML = updatedPanel.innerHTML;
                }
            })
            .catch(() => {});
    };

    panel.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-jlsitemap-ajax="1"]');

        if (!link) {
            return;
        }

        event.preventDefault();

        if (link.dataset.jlsitemapBusy === '1') {
            return;
        }

        const card = link.closest('.card');
        const separator = link.href.indexOf('?') === -1 ? '?' : '&';
        const requestUrl = link.href + separator + 'response=json';

        link.dataset.jlsitemapBusy = '1';

        if (card) {
            card.classList.add('opacity-50');
        }

        Joomla.request({
            url: requestUrl,
            method: 'POST',
            perform: true,
            onSuccess: (responseText) => {
                let response;

                try {
                    response = JSON.parse(responseText);
                } catch (error) {
                    renderMessages([{type: 'error', text: genericError}]);

                    return;
                }

                if (!response.success) {
                    const messages = collectMessages(response, 'error');

                    renderMessages(messages.length ? messages : [{type: 'error', text: genericError}]);

                    return;
                }

                renderMessages(collectMessages(response, 'message'));
                refreshPanel();
            },
            onError: () => {
                renderMessages([{type: 'error', text: genericError}]);
            },
            onComplete: () => {
                delete link.dataset.jlsitemapBusy;

                if (card) {
                    card.classList.remove('opacity-50');
                }
            }
        });
    });
});
