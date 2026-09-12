require('dotenv').config();
const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');
const QRCode = require('qrcode');
const pino = require('pino');

const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion
} = require('@whiskeysockets/baileys');

const app = express();
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));

const PORT = parseInt(process.env.PORT || '8085', 10);
const API_KEY = process.env.API_KEY || 'pmcc_wa_sec_key_2026_x9';
const SESSION_DIR = path.resolve(process.env.SESSION_DIR || path.join(__dirname, 'session_auth'));

// State tracking
let sock = null;
let connectionState = 'disconnected'; // 'disconnected' | 'connecting' | 'qr_ready' | 'connected'
let currentQrRaw = null;
let currentQrImage = null;
let currentUser = null;
let isLoggingOut = false;

// ── In-Memory Retry Message Store (Fixes "Waiting for this message. This may take a while") ──
const msgRetryStore = new Map();
const lidToPhone = new Map();

function storeMessage(keyId, message) {
    if (!keyId || !message) return;
    msgRetryStore.set(keyId, message);
    if (msgRetryStore.size > 2000) {
        const oldestKey = msgRetryStore.keys().next().value;
        msgRetryStore.delete(oldestKey);
    }
}

// ── Authentication Middleware ──
function authenticate(req, res, next) {
    const authHeader = req.headers['authorization'];
    const queryKey = req.query.api_key;
    
    let token = null;
    if (authHeader && authHeader.startsWith('Bearer ')) {
        token = authHeader.substring(7).trim();
    } else if (queryKey) {
        token = String(queryKey).trim();
    }

    if (!token || token !== API_KEY) {
        return res.status(401).json({
            success: false,
            message: 'Unauthorized: Invalid or missing WhatsApp API key'
        });
    }
    next();
}

// ── Format recipient phone number or preserve JID / LID ──
function formatJid(target) {
    if (!target) return '';
    target = String(target).trim();

    // Preserve full WhatsApp JIDs (e.g. 275767166550158@lid or 447901296858@s.whatsapp.net or group@g.us)
    if (target.includes('@lid') || target.includes('@g.us') || target.includes('@s.whatsapp.net')) {
        return target;
    }

    let clean = target.replace(/[^0-9]/g, '');

    // Format UK numbers starting with 07 to 447
    if (clean.startsWith('0') && clean.length === 11) {
        clean = '44' + clean.substring(1);
    } else if (clean.startsWith('440')) {
        clean = '44' + clean.substring(3);
    }

    // Format Indian numbers 10 digits to 91xxx
    if (clean.length === 10 && ['6', '7', '8', '9'].includes(clean[0])) {
        clean = '91' + clean;
    }

    return `${clean}@s.whatsapp.net`;
}

// ── Initialize WhatsApp Socket ──
async function initWhatsApp() {
    if (!fs.existsSync(SESSION_DIR)) {
        fs.mkdirSync(SESSION_DIR, { recursive: true });
    }

    try {
        const { state, saveCreds } = await useMultiFileAuthState(SESSION_DIR);
        const { version } = await fetchLatestBaileysVersion();

        const logger = pino({ level: 'silent' });

        sock = makeWASocket({
            version,
            logger,
            printQRInTerminal: false,
            auth: state,
            browser: ['PMCC-UK Automation', 'Chrome', '124.0.0'],
            connectTimeoutMs: 60000,
            defaultQueryTimeoutMs: 60000,
            keepAliveIntervalMs: 25000,
            emitOwnEvents: false,
            markOnlineOnConnect: true,
            syncFullHistory: false,
            // 🛡️ CRUCIAL: Handler for answering WhatsApp peer retry requests
            // Solves the "Waiting for this message. This may take a while" error
            getMessage: async (key) => {
                if (key && key.id && msgRetryStore.has(key.id)) {
                    const stored = msgRetryStore.get(key.id);
                    return stored?.message || stored || undefined;
                }
                return undefined;
            }
        });

        sock.ev.on('creds.update', saveCreds);

        // ── Contact & Phone Number Sharing (LID to Phone mapping) ──
        sock.ev.on('chats.phoneNumberShare', ({ lid, jid }) => {
            if (lid && jid) {
                const rawLid = lid.split('@')[0].split(':')[0];
                const rawPn = jid.split('@')[0].split(':')[0];
                lidToPhone.set(rawLid, rawPn);
                console.log(`[WhatsApp Daemon] Linked LID ${rawLid} -> Phone ${rawPn}`);
            }
        });

        sock.ev.on('contacts.upsert', (contacts) => {
            for (const c of contacts) {
                if (c.lid && c.id) {
                    const rawLid = c.lid.split('@')[0].split(':')[0];
                    const rawPn = c.id.split('@')[0].split(':')[0];
                    lidToPhone.set(rawLid, rawPn);
                }
            }
        });

        sock.ev.on('contacts.update', (updates) => {
            for (const c of updates) {
                if (c.lid && c.id) {
                    const rawLid = c.lid.split('@')[0].split(':')[0];
                    const rawPn = c.id.split('@')[0].split(':')[0];
                    lidToPhone.set(rawLid, rawPn);
                }
            }
        });

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                connectionState = 'qr_ready';
                currentQrRaw = qr;
                try {
                    currentQrImage = await QRCode.toDataURL(qr, {
                        margin: 2,
                        width: 320,
                        color: {
                            dark: '#1e293b',
                            light: '#ffffff'
                        }
                    });
                } catch (qrErr) {
                    console.error('[WhatsApp Daemon] QR Generation error:', qrErr);
                }
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut && !isLoggingOut;

                connectionState = 'disconnected';
                currentQrRaw = null;
                currentQrImage = null;
                currentUser = null;

                console.log(`[WhatsApp Daemon] Connection closed. Status: ${statusCode}, Reconnect: ${shouldReconnect}`);

                if (shouldReconnect) {
                    connectionState = 'connecting';
                    setTimeout(() => initWhatsApp(), 4000);
                } else if (isLoggingOut) {
                    isLoggingOut = false;
                    try {
                        fs.rmSync(SESSION_DIR, { recursive: true, force: true });
                    } catch (rmErr) {}
                    setTimeout(() => initWhatsApp(), 2000);
                }
            } else if (connection === 'connecting') {
                connectionState = 'connecting';
            } else if (connection === 'open') {
                connectionState = 'connected';
                currentQrRaw = null;
                currentQrImage = null;
                currentUser = sock.user || { id: 'connected' };
                console.log('[WhatsApp Daemon] Connected successfully as:', currentUser);
            }
        });

        // ── Inbound Message Listener for 2-Way Interactive Bot ──
        sock.ev.on('messages.upsert', async (m) => {
            try {
                if (m.type !== 'notify' || !m.messages) return;

                for (const msg of m.messages) {
                    // Cache all received messages for E2EE retry resolution
                    if (msg.key?.id && msg.message) {
                        storeMessage(msg.key.id, msg.message);
                    }

                    if (msg.key.fromMe) continue;
                    if (!msg.message) continue;

                    const remoteJid = msg.key.remoteJid;
                    if (!remoteJid || remoteJid.includes('@g.us') || remoteJid.includes('status@broadcast')) continue;

                    const text = msg.message.conversation ||
                                 msg.message.extendedTextMessage?.text ||
                                 msg.message.imageMessage?.caption ||
                                 msg.message.documentMessage?.caption ||
                                 '';

                    if (!text || !text.trim()) continue;

                    // Extract actual phone number if sender is using LID
                    let senderPn = null;
                    if (remoteJid.includes('@s.whatsapp.net')) {
                        senderPn = remoteJid.split('@')[0].split(':')[0];
                    } else if (remoteJid.includes('@lid')) {
                        const rawLid = remoteJid.split('@')[0].split(':')[0];
                        senderPn = msg.key.senderPn || msg.key.participantPn || lidToPhone.get(rawLid) || null;
                    }

                    console.log(`[WhatsApp Inbound] Received from ${remoteJid} (Phone: ${senderPn || 'LID'}, PushName: ${msg.pushName}): "${text.trim()}"`);

                    const webhookUrl = process.env.WEBHOOK_URL || 'https://pmccuk.org/api/whatsapp/webhook';
                    try {
                        await fetch(webhookUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${API_KEY}`
                            },
                            body: JSON.stringify({
                                from: remoteJid,          // The exact JID to reply to (preserves @lid or @s.whatsapp.net)
                                phone: senderPn,          // The detected real phone number
                                message: text.trim(),
                                pushName: msg.pushName || 'Member'
                            })
                        });
                    } catch (fetchErr) {
                        console.error('[WhatsApp Inbound] Error posting to webhook:', fetchErr.message);
                    }
                }
            } catch (upsertErr) {
                console.error('[WhatsApp Inbound] Error processing upsert:', upsertErr.message);
            }
        });
    } catch (err) {
        console.error('[WhatsApp Daemon] Init error:', err);
        connectionState = 'disconnected';
        setTimeout(() => initWhatsApp(), 10000);
    }
}

// ── REST Routes ──

// Health check
app.get('/', (req, res) => {
    res.json({
        service: 'pmcc-whatsapp-daemon',
        status: 'running',
        whatsapp_state: connectionState,
        cached_messages: msgRetryStore.size,
        timestamp: new Date().toISOString()
    });
});

// Session status
app.get('/session/status', authenticate, (req, res) => {
    const isConnected = connectionState === 'connected';
    res.json({
        success: true,
        status: connectionState,
        connected: isConnected,
        hasQr: !!currentQrImage,
        user: isConnected ? {
            id: currentUser?.id || '',
            name: currentUser?.name || currentUser?.notify || 'PMCC-UK Automation'
        } : null
    });
});

// Session QR code
app.get('/session/qr', authenticate, (req, res) => {
    if (connectionState === 'connected') {
        return res.json({
            success: true,
            status: 'connected',
            connected: true,
            qr: null,
            message: 'Device already connected and paired.'
        });
    }

    res.json({
        success: true,
        status: connectionState,
        connected: false,
        qr: currentQrImage,
        message: currentQrImage ? 'Scan QR code with WhatsApp' : 'Generating QR code...'
    });
});

// Session logout
app.post('/session/logout', authenticate, async (req, res) => {
    try {
        isLoggingOut = true;
        if (sock) {
            try {
                await sock.logout();
            } catch (logoutErr) {}
        }
        res.json({
            success: true,
            message: 'WhatsApp session logged out and cleared successfully.'
        });
    } catch (e) {
        res.status(500).json({
            success: false,
            error: e.message
        });
    }
});

// Send Text Message
app.post('/send/text', authenticate, async (req, res) => {
    if (connectionState !== 'connected' || !sock) {
        return res.status(503).json({
            success: false,
            error: 'WhatsApp device is not connected. Current state: ' + connectionState
        });
    }

    const { to, message } = req.body;
    if (!to || !message) {
        return res.status(400).json({
            success: false,
            error: 'Parameters "to" and "message" are required.'
        });
    }

    try {
        const jid = formatJid(to);
        const result = await sock.sendMessage(jid, { text: message });

        // Store outbound message for E2EE retry answering
        if (result?.key?.id && result.message) {
            storeMessage(result.key.id, result.message);
        }

        res.json({
            success: true,
            messageId: result?.key?.id,
            to: jid
        });
    } catch (err) {
        console.error('[WhatsApp Daemon] Send text error:', err);
        res.status(500).json({
            success: false,
            error: err.message
        });
    }
});

// Send File / PDF / Image
app.post('/send/file', authenticate, async (req, res) => {
    if (connectionState !== 'connected' || !sock) {
        return res.status(503).json({
            success: false,
            error: 'WhatsApp device is not connected. Current state: ' + connectionState
        });
    }

    const { to, base64, filename, caption, mimetype } = req.body;
    if (!to || !base64) {
        return res.status(400).json({
            success: false,
            error: 'Parameters "to" and "base64" data are required.'
        });
    }

    try {
        const jid = formatJid(to);
        const cleanBase64 = base64.includes('base64,') ? base64.split('base64,')[1] : base64;
        const buffer = Buffer.from(cleanBase64, 'base64');
        const resolvedMime = mimetype || 'application/pdf';
        const docName = filename || 'document.pdf';

        const isImage = resolvedMime.startsWith('image/');
        const messagePayload = isImage
            ? { image: buffer, caption: caption || '' }
            : { document: buffer, mimetype: resolvedMime, fileName: docName, caption: caption || '' };

        const result = await sock.sendMessage(jid, messagePayload);

        // Store outbound file message for retry answering
        if (result?.key?.id && result.message) {
            storeMessage(result.key.id, result.message);
        }

        res.json({
            success: true,
            messageId: result?.key?.id,
            to: jid,
            filename: docName
        });
    } catch (err) {
        console.error('[WhatsApp Daemon] Send file error:', err);
        res.status(500).json({
            success: false,
            error: err.message
        });
    }
});

// Start Daemon Server
app.listen(PORT, '0.0.0.0', () => {
    console.log(`====================================================`);
    console.log(`  PMCC-UK WhatsApp Daemon running on port ${PORT}`);
    console.log(`  Session Path: ${SESSION_DIR}`);
    console.log(`====================================================`);
    initWhatsApp();
});
