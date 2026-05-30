# Railway Deployment Guide

## Prerequisites
- GitHub repository connected to Railway
- MySQL database provisioned on Railway

## Environment Variables Required

Set these in Railway dashboard under **Variables**:

```
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=your-secret-key-here

# Database (provided by Railway MySQL plugin)
DATABASE_URL=mysql://user:password@hostname:3306/dbname

# JWT Authentication
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-jwt-passphrase-here

# CORS for mobile app
CORS_ALLOW_ORIGIN=^https?://localhost(:[0-9]+)?$|^https://yourmobiledomain\.com$

# Google OAuth (if using)
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret

# API Platform
API_ENTRYPOINT=https://your-railway-domain.com/api

# Session settings
SESSION_HANDLER=database

# WebSocket real-time (required for live dashboard + mobile updates)
WEBSOCKET_SECRET=your-strong-random-secret-here
WEBSOCKET_PUBLIC_URL=wss://your-railway-domain.com/ws
WEBSOCKET_BROADCAST_URL=http://127.0.0.1:${PORT}/broadcast

# Firebase Cloud Messaging (push notifications to mobile app)
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_SERVICE_ACCOUNT_B64=base64-encoded-service-account-json
```

### Firebase push setup

1. In [Firebase Console](https://console.firebase.google.com/), open your project → **Project settings** → **Service accounts**.
2. Click **Generate new private key** and download the JSON file.
3. Base64-encode the entire JSON file (one line, no line breaks):
   - **Linux/macOS:** `base64 -w0 service-account.json`
   - **PowerShell:** `[Convert]::ToBase64String([IO.File]::ReadAllBytes("service-account.json"))`
4. In Railway → your **web** service → **Variables**, set:
   - `FIREBASE_PROJECT_ID` = the project ID from Firebase (e.g. `my-cafe-app`)
   - `FIREBASE_SERVICE_ACCOUNT_B64` = the base64 string from step 3
5. Redeploy. When staff completes or cancels an order, customers receive a push such as: **Order Update — #ORD-… is now completed.**

The mobile app registers tokens via `POST /api/customer/fcm-token` with `{ "customer_id": 1, "token": "<fcm-device-token>" }`. Tokens can also be sent on login/register as `fcm_token` or `token`.

### WebSocket real-time setup

The `railway-start.sh` script runs:
1. **PHP** on `127.0.0.1:8080` (internal)
2. **Node WebSocket + HTTP proxy** on public `$PORT` — proxies API requests to PHP and serves `/ws`

Set `WEBSOCKET_PUBLIC_URL` to `wss://your-railway-domain.com/ws` (same host as your API). The mobile app derives the WebSocket URL from `API_BASE` automatically.

Set `WEBSOCKET_SECRET` to a strong random string (used to authenticate broadcast requests from Symfony).

## Deployment Steps

1. **Connect GitHub Repository**
   - Go to Railway dashboard
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose `thv-maker/midterm-project`

2. **Add MySQL Database**
   - Click "Add Service"
   - Select "MySQL"
   - Railway will auto-populate `DATABASE_URL` variable

3. **Set Environment Variables**
   - In your project settings, add all variables from the "Environment Variables Required" section above
   - Generate a strong `APP_SECRET` (32+ random characters)
   - Create JWT keypair or use existing from `config/jwt/`

4. **Deploy**
   - Railway auto-deploys on push to main branch
   - Watch deployment logs in dashboard
   - First deployment runs migrations automatically

5. **Post-Deployment**
   - Test API endpoint: `https://your-railway-domain.com/api`
   - Update mobile app BASE_URL to Railway domain
   - Check logs if issues occur

## Mobile App Configuration

After deployment, update the mobile app's API base URL in `src/app/api/config.ts`:

```typescript
export const API_BASE = 'https://your-railway-domain.com/api';
// WS_BASE is derived automatically from API_BASE
```

Replace `your-railway-domain.com` with your actual Railway domain from the dashboard.

## Troubleshooting

- **Migrations fail**: Check `DATABASE_URL` is correct
- **JWT errors**: Ensure JWT keys are in `config/jwt/`
- **CORS errors**: Update `CORS_ALLOW_ORIGIN` for your mobile domain
- **Static files 404**: Check `public/` directory is accessible
- **WebSocket not connecting**: Ensure `WEBSOCKET_SECRET` is set and `railway-start.sh` is the start command. Test `GET /health` on your domain — should return `{ status: "ok" }` from the Node proxy
- **Live updates not working locally**: Start the WebSocket server with `node websocket/server.js` on port 8081
