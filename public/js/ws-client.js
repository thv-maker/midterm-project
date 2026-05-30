/**
 * Shared WebSocket client for the admin dashboard.
 */
window.CafeRealtime = (function () {
    const RECONNECT_MS = 3000;
    const MAX_RECONNECT = 20;
    const ADMIN_TOPICS = ['/orders', '/products', '/users', '/activity-logs', '/stocks'];

    function resolveWsUrl() {
        if (window.WEBSOCKET_PUBLIC_URL && !window.WEBSOCKET_PUBLIC_URL.includes('127.0.0.1')) {
            return window.WEBSOCKET_PUBLIC_URL;
        }
        const scheme = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        return scheme + '//' + window.location.host + '/ws';
    }

    function connect(topics, onTopicMessage, onStatus) {
        const wsUrl = resolveWsUrl();
        const topicList = Array.isArray(topics) ? topics : [topics];
        const separator = wsUrl.includes('?') ? '&' : '?';
        const fullUrl = wsUrl + separator + 'admin=1';

        let ws = null;
        let reconnectAttempts = 0;
        let reconnectTimer = null;
        let closed = false;

        function open() {
            if (closed) return;
            onStatus?.('connecting');
            try {
                ws = new WebSocket(fullUrl);
            } catch (e) {
                console.warn('WebSocket unavailable', e);
                onStatus?.('error');
                scheduleReconnect();
                return;
            }

            ws.onopen = () => {
                reconnectAttempts = 0;
                onStatus?.('connected');
                ws.send(JSON.stringify({
                    type: 'subscribe',
                    topics: topicList,
                    admin: true,
                }));
            };

            ws.onmessage = (event) => {
                try {
                    const msg = JSON.parse(event.data);
                    if (msg.type === 'subscribed') {
                        onStatus?.('subscribed');
                        return;
                    }
                    if (msg.topic && msg.data && onTopicMessage) {
                        onTopicMessage(msg.topic, msg.data);
                    }
                } catch (e) {
                    console.warn('Invalid WebSocket payload', e);
                }
            };

            ws.onerror = () => {
                onStatus?.('error');
                ws.close();
            };

            ws.onclose = () => {
                onStatus?.('disconnected');
                if (!closed) scheduleReconnect();
            };
        }

        function scheduleReconnect() {
            if (closed || reconnectAttempts >= MAX_RECONNECT) return;
            reconnectAttempts++;
            reconnectTimer = setTimeout(open, RECONNECT_MS);
        }

        open();

        return {
            close() {
                closed = true;
                if (reconnectTimer) clearTimeout(reconnectTimer);
                if (ws) ws.close();
            },
        };
    }

    return { connect, ADMIN_TOPICS };
})();
