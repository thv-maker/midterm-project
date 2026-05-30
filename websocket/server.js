'use strict';

const http = require('http');
const { WebSocketServer } = require('ws');
const { URL } = require('url');

const PORT = parseInt(process.env.PORT || process.env.WEBSOCKET_PORT || '8081', 10);
const PHP_BACKEND = process.env.PHP_BACKEND_URL || 'http://127.0.0.1:8080';
const BROADCAST_SECRET = process.env.WEBSOCKET_SECRET || 'dev-websocket-secret';
const WS_PATH = '/ws';

/** @type {Map<WebSocket, { topics: Set<string>, customerId: number|null, isAdmin: boolean }>} */
const clients = new Map();

function parseBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    req.on('data', chunk => chunks.push(chunk));
    req.on('end', () => {
      try {
        resolve(JSON.parse(Buffer.concat(chunks).toString('utf8') || '{}'));
      } catch (err) {
        reject(err);
      }
    });
    req.on('error', reject);
  });
}

function sendJson(ws, payload) {
  if (ws.readyState === ws.OPEN) {
    ws.send(JSON.stringify(payload));
  }
}

function broadcast(topic, data) {
  for (const [ws, meta] of clients.entries()) {
    if (ws.readyState !== ws.OPEN) continue;
    if (!meta.topics.has(topic)) continue;
    sendJson(ws, { topic, data });
  }
}

function proxyToPhp(req, res) {
  const target = new URL(req.url || '/', PHP_BACKEND);
  const headers = { ...req.headers, host: target.host };
  delete headers.connection;

  const proxyReq = http.request(
    {
      hostname: target.hostname,
      port: target.port,
      path: target.pathname + target.search,
      method: req.method,
      headers,
    },
    proxyRes => {
      res.writeHead(proxyRes.statusCode || 502, proxyRes.headers);
      proxyRes.pipe(res);
    },
  );

  proxyReq.on('error', () => {
    if (!res.headersSent) {
      res.writeHead(502, { 'Content-Type': 'text/plain' });
      res.end('Bad Gateway — PHP backend unavailable');
    }
  });

  req.pipe(proxyReq);
}

const server = http.createServer(async (req, res) => {
  const url = req.url || '/';

  if (req.method === 'GET' && url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok', clients: clients.size }));
    return;
  }

  if (req.method === 'POST' && url === '/broadcast') {
    const auth = req.headers.authorization || '';
    const token = auth.startsWith('Bearer ') ? auth.slice(7) : '';
    if (token !== BROADCAST_SECRET) {
      res.writeHead(401, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ error: 'Unauthorized' }));
      return;
    }

    try {
      const body = await parseBody(req);
      const topic = body.topic;
      const data = body.data;
      if (!topic || !data) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'topic and data are required' }));
        return;
      }
      broadcast(topic, data);
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true }));
    } catch {
      res.writeHead(400, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ error: 'Invalid JSON body' }));
    }
    return;
  }

  proxyToPhp(req, res);
});

const wss = new WebSocketServer({ noServer: true });

server.on('upgrade', (req, socket, head) => {
  const url = new URL(req.url || '/', `http://${req.headers.host}`);
  if (url.pathname !== WS_PATH) {
    socket.destroy();
    return;
  }

  wss.handleUpgrade(req, socket, head, ws => {
    wss.emit('connection', ws, req, url);
  });
});

wss.on('connection', (ws, req, url) => {
  const customerIdParam = url.searchParams.get('customer_id');
  const customerId = customerIdParam ? parseInt(customerIdParam, 10) : null;
  const isAdmin = url.searchParams.get('admin') === '1';

  const meta = {
    topics: new Set(),
    customerId: Number.isFinite(customerId) ? customerId : null,
    isAdmin,
  };
  clients.set(ws, meta);

  if (isAdmin) {
    for (const topic of ['/orders', '/products', '/users', '/activity-logs', '/stocks']) {
      meta.topics.add(topic);
    }
  }

  if (meta.customerId) {
    meta.topics.add(`/customer/${meta.customerId}/orders`);
    meta.topics.add('/products');
  }

  ws.on('message', raw => {
    try {
      const msg = JSON.parse(String(raw));
      if (msg.type === 'subscribe' && Array.isArray(msg.topics)) {
        for (const topic of msg.topics) {
          if (typeof topic === 'string' && topic.startsWith('/')) {
            if (topic.startsWith('/customer/') && meta.customerId) {
              const match = topic.match(/^\/customer\/(\d+)\/orders$/);
              if (match && parseInt(match[1], 10) !== meta.customerId) {
                continue;
              }
            }
            meta.topics.add(topic);
          }
        }
        if (msg.customer_id) {
          const cid = parseInt(msg.customer_id, 10);
          if (Number.isFinite(cid)) {
            meta.customerId = cid;
            meta.topics.add(`/customer/${cid}/orders`);
            meta.topics.add('/products');
          }
        }
        if (msg.admin === true || msg.admin === 1) {
          meta.isAdmin = true;
          for (const topic of ['/orders', '/products', '/users', '/activity-logs', '/stocks']) {
            meta.topics.add(topic);
          }
        }
        sendJson(ws, { type: 'subscribed', topics: [...meta.topics] });
      }
    } catch {
      // ignore malformed messages
    }
  });

  ws.on('close', () => clients.delete(ws));
  ws.on('error', () => clients.delete(ws));

  sendJson(ws, { type: 'connected' });
});

server.listen(PORT, () => {
  console.log(`[websocket] listening on port ${PORT} (ws: ${WS_PATH}, php proxy: ${PHP_BACKEND})`);
});
