document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('controlPanel');
    const options = Joomla.getOptions('com_jlsitemap.controlpanel') || {};
    const genericError = options.genericError || 'An error has occurred.';

    if (!panel || typeof Joomla === 'undefined' || typeof Joomla.request !== 'function') {
        return;
    }

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
                    renderMessages([{type: 'error', text: response.message || genericError}]);

                    return;
                }

                renderMessages(response.data && response.data.messages ? response.data.messages : []);
                window.setTimeout(() => window.location.reload(), 600);
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
