# PMCC-UK WhatsApp Microservice Daemon

Independent Node.js service using `@whiskeysockets/baileys` to connect to the WhatsApp Web Multi-Device network. Provides a local REST API for the PMCC-UK Laravel platform to send notifications and stream live pairing QR codes.

## Requirements
- Node.js 18.x or 20.x+
- npm 9.x+

## Installation
```bash
npm install
cp .env.example .env
npm start
```

## Production (PM2)
```bash
pm2 start ecosystem.config.js
pm2 save
```

## Endpoints
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/` | Health check & connection state |
| `GET` | `/session/status` | Connection status & user details (Bearer auth) |
| `GET` | `/session/qr` | Base64 PNG QR code data URL (Bearer auth) |
| `POST` | `/session/logout` | Disconnect and clear session (Bearer auth) |
| `POST` | `/send/text` | Dispatch plain text message (Bearer auth) |
| `POST` | `/send/file` | Dispatch PDF or media attachment (Bearer auth) |
