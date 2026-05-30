/**
 * Shared WebSocket client for the admin dashboard.
 */
window.CafeRealtime = (function () {
    const RECONNECT_MS = 3000;
    const MAX_RECONNECT = 10;

    function connect(topics, onTopicMessage) {
        const wsUrl = window.WEBSOCKET_PUBLIC_URL;
        if (!wsUrl) {
            console.warn('WEBSOCKET_PUBLIC_URL is not configured');
            return { close() {} };
        }

        let ws = null;
        let reconnectAttempts = 0;
        let reconnectTimer = null;
        let closed = false;
        const topicList = Array.isArray(topics) ? topics : [topics];
        const separator = wsUrl.includes('?') ? '&' : '?';
        const fullUrl = wsUrl + separator + 'admin=1';

        function open() {
            if (closed) return;
            try {
                ws = new WebSocket(fullUrl);
            } catch (e) {
                console.warn('WebSocket unavailable', e);
                scheduleReconnect();
                return;
            }

            ws.onopen = () => {
                reconnectAttempts = 0;
                ws.send(JSON.stringify({ type: 'subscribe', topics: topicList, admin: true }));
            };

            ws.onmessage = (event) => {
                try {
                    const msg = JSON.parse(event.data);
                    if (msg.topic && msg.data && onTopicMessage) {
                        onTopicMessage(msg.topic, msg.data);
                    }
                } catch (e) {
                    console.warn('Invalid WebSocket payload', e);
                }
            };

            ws.onerror = () => ws.close();
            ws.onclose = () => {
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

    return { connect };
})();
