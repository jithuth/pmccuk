require('dotenv').config({ path: require('path').join(__dirname, '.env') });
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

// ── Memory & Retry Cache ──
let NodeCacheModule = null;
try {
    NodeCacheModule = require('node-cache');
} catch (e) {
    NodeCacheModule = null;
}

const msgRetryCounterCache = NodeCacheModule
    ? new NodeCacheModule({ stdTTL: 7200, checkperiod: 300 })
    : new Map();

const app = express();
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));

const PORT = parseInt(process.env.PORT || '8085', 10);
const API_KEY = (process.env.API_KEY || 'pmcc_wa_sec_key_2026_x9').trim();
const SESSION_DIR = path.resolve(process.env.SESSION_DIR || path.join(__dirname, 'session_auth'));
const LOGS_DIR = path.resolve(__dirname, 'logs');
const LOG_FILE = path.join(LOGS_DIR, 'whatsapp.log');

if (!fs.existsSync(SESSION_DIR)) {
    try { fs.mkdirSync(SESSION_DIR, { recursive: true }); } catch (e) {}
}

if (!fs.existsSync(LOGS_DIR)) {
    try { fs.mkdirSync(LOGS_DIR, { recursive: true }); } catch (e) {}
}

// ── State tracking ──
let sock = null;
let connectionState = 'disconnected'; // 'disconnected' | 'connecting' | 'qr_ready' | 'connected'
let currentQrRaw = null;
let currentQrImage = null;
let currentUser = null;
let isLoggingOut = false;
let reconnectTimer = null;

function scheduleInitWhatsApp(delayMs = 2000) {
    if (reconnectTimer) {
        clearTimeout(reconnectTimer);
        reconnectTimer = null;
    }
    reconnectTimer = setTimeout(() => {
        reconnectTimer = null;
        initWhatsApp();
    }, delayMs);
}

// ── Persistent LID <-> Phone Mapping ──
// Avoids "Waiting for this message" when communicating with devices using Linked Identity (LID)
const LID_MAPPINGS_FILE = path.join(SESSION_DIR, 'lid_mappings.json');
const lidToPhone = new Map();
const phoneToLid = new Map();

function loadPersistentMappings() {
    try {
        if (fs.existsSync(LID_MAPPINGS_FILE)) {
            const data = JSON.parse(fs.readFileSync(LID_MAPPINGS_FILE, 'utf8'));
            if (data.lidToPhone) {
                for (const [k, v] of Object.entries(data.lidToPhone)) lidToPhone.set(k, v);
            }
            if (data.phoneToLid) {
                for (const [k, v] of Object.entries(data.phoneToLid)) phoneToLid.set(k, v);
            }
        }
    } catch (e) {}
}

function savePersistentMappings() {
    try {
        const lidObj = {};
        const phoneObj = {};
        for (const [k, v] of lidToPhone.entries()) lidObj[k] = v;
        for (const [k, v] of phoneToLid.entries()) phoneObj[k] = v;
        fs.writeFileSync(LID_MAPPINGS_FILE, JSON.stringify({ lidToPhone: lidObj, phoneToLid: phoneObj }), 'utf8');
    } catch (e) {}
}

function recordLidPhoneMapping(rawLid, rawPn) {
    if (!rawLid || !rawPn) return;
    rawLid = String(rawLid).split('@')[0].split(':')[0];
    rawPn = String(rawPn).split('@')[0].split(':')[0];
    
    lidToPhone.set(rawLid, rawPn);
    phoneToLid.set(rawPn, rawLid);

    // Normalize UK representations (447... <-> 07...)
    if (rawPn.startsWith('44')) {
        const local = '0' + rawPn.substring(2);
        phoneToLid.set(local, rawLid);
        phoneToLid.set(rawPn.substring(2), rawLid); // 10-digit national
    } else if (rawPn.startsWith('0')) {
        const intl = '44' + rawPn.substring(1);
        phoneToLid.set(intl, rawLid);
        phoneToLid.set(rawPn.substring(1), rawLid);
    }

    savePersistentMappings();
}

// ── Persistent LRU Message Store (Fixes "Waiting for this message. This may take a while") ──
// Retains messages on disk so Baileys can always answer WhatsApp peer retry requests across restarts
const MESSAGE_STORE_FILE = path.join(SESSION_DIR, 'message_store.json');
const msgRetryStore = new Map();

function loadPersistentMessages() {
    try {
        if (fs.existsSync(MESSAGE_STORE_FILE)) {
            const data = JSON.parse(fs.readFileSync(MESSAGE_STORE_FILE, 'utf8'));
            for (const [k, v] of Object.entries(data)) {
                msgRetryStore.set(k, v);
            }
        }
    } catch (e) {}
}

let saveMsgTimeout = null;
function scheduleSaveMessages() {
    if (saveMsgTimeout) return;
    saveMsgTimeout = setTimeout(() => {
        saveMsgTimeout = null;
        try {
            const obj = {};
            for (const [k, v] of msgRetryStore.entries()) obj[k] = v;
            fs.writeFileSync(MESSAGE_STORE_FILE, JSON.stringify(obj), 'utf8');
        } catch (e) {}
    }, 2000);
}

function storeMessage(keyId, message) {
    if (!keyId || !message) return;
    const clean = message.message || message;
    msgRetryStore.set(keyId, clean);
    if (msgRetryStore.size > 2000) {
        const oldestKey = msgRetryStore.keys().next().value;
        msgRetryStore.delete(oldestKey);
    }
    scheduleSaveMessages();
}

function getStoredMessage(keyId) {
    if (!keyId) return undefined;
    return msgRetryStore.get(keyId);
}

// ── Real-Time Gateway Log Store ──
let logCounter = 1;
const systemLogs = []; // In-memory ring buffer (up to 500 entries)

function logEvent(type, level, message, details = null) {
    const entry = {
        id: logCounter++,
        timestamp: new Date().toISOString(),
        type: type || 'system',
        level: level || 'INFO',
        message: String(message),
        details: details || null
    };

    systemLogs.push(entry);
    if (systemLogs.length > 500) {
        systemLogs.shift();
    }

    const consoleStr = `[${entry.timestamp}] [${entry.level}] [${entry.type.toUpperCase()}] ${entry.message}`;
    if (level === 'ERROR') {
        console.error(consoleStr);
    } else if (level === 'WARNING') {
        console.warn(consoleStr);
    } else {
        console.log(consoleStr);
    }

    try {
        fs.appendFile(LOG_FILE, JSON.stringify(entry) + '\n', () => {});
    } catch (e) {}
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
    } else if (clean.startsWith('440') && clean.length === 13) {
        clean = '44' + clean.substring(3);
    } else if (clean.startsWith('0044')) {
        clean = clean.substring(2);
    } else if (clean.length === 10) {
        // Without country code (e.g. 7901296858): add 44
        clean = '44' + clean;
    }

    // 🛡️ DYNAMIC LID ROUTING:
    // If we have an active mapped LID for this recipient, route directly to their @lid!
    // This uses the recipient's existing, active Signal encryption session, completely preventing
    // "Waiting for this message. This may take a while" on outbound dispatches!
    if (phoneToLid.has(clean)) {
        const lid = phoneToLid.get(clean);
        return `${lid}@lid`;
    }

    return `${clean}@s.whatsapp.net`;
}

// ── Initialize WhatsApp Socket ──
async function initWhatsApp() {
    if (reconnectTimer) {
        clearTimeout(reconnectTimer);
        reconnectTimer = null;
    }

    // Cleanly detach and discard existing socket if present
    if (sock) {
        try {
            sock.ev?.removeAllListeners();
            sock.end(undefined);
        } catch (e) {}
        sock = null;
    }

    loadPersistentMappings();
    loadPersistentMessages();

    try {
        if (!fs.existsSync(SESSION_DIR)) {
            fs.mkdirSync(SESSION_DIR, { recursive: true });
        }

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
            // 🛡️ Cache for tracking retry counts on WhatsApp multi-device
            msgRetryCounterCache,
            // 🛡️ CRUCIAL: Handler for answering WhatsApp peer retry requests
            // Solves the "Waiting for this message. This may take a while" error
            getMessage: async (key) => {
                if (key && key.id) {
                    const stored = getStoredMessage(key.id);
                    if (stored) {
                        return stored;
                    }
                }
                return undefined;
            }
        });

        sock.ev.on('creds.update', saveCreds);

        // ── Contact & Phone Number Sharing (LID to Phone mapping) ──
        sock.ev.on('chats.phoneNumberShare', ({ lid, jid }) => {
            if (lid && jid) {
                recordLidPhoneMapping(lid, jid);
                logEvent('system', 'INFO', `Linked LID ${lid} -> Phone ${jid}`);
            }
        });

        sock.ev.on('contacts.upsert', (contacts) => {
            for (const c of contacts) {
                if (c.lid && c.id) {
                    recordLidPhoneMapping(c.lid, c.id);
                }
            }
        });

        sock.ev.on('contacts.update', (updates) => {
            for (const c of updates) {
                if (c.lid && c.id) {
                    recordLidPhoneMapping(c.lid, c.id);
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
                    logEvent('qr', 'INFO', 'New pairing QR code generated. Waiting for administrator scan.');
                } catch (qrErr) {
                    logEvent('error', 'ERROR', 'QR Generation error: ' + qrErr.message);
                }
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const isLoggedOutStatus = statusCode === DisconnectReason.loggedOut;
                const shouldReconnect = !isLoggedOutStatus && !isLoggingOut;

                connectionState = 'disconnected';
                currentQrRaw = null;
                currentQrImage = null;
                currentUser = null;

                logEvent('system', 'WARNING', `Connection closed. Status: ${statusCode || 'unknown'}. Reconnect: ${shouldReconnect}`);

                if (shouldReconnect) {
                    connectionState = 'connecting';
                    scheduleInitWhatsApp(3000);
                } else if (isLoggedOutStatus && !isLoggingOut) {
                    try {
                        fs.rmSync(SESSION_DIR, { recursive: true, force: true });
                        fs.mkdirSync(SESSION_DIR, { recursive: true });
                    } catch (rmErr) {}
                    scheduleInitWhatsApp(2000);
                }
            } else if (connection === 'connecting') {
                connectionState = 'connecting';
            } else if (connection === 'open') {
                connectionState = 'connected';
                currentQrRaw = null;
                currentQrImage = null;
                currentUser = sock.user || { id: 'connected' };
                const devName = currentUser?.name || currentUser?.notify || currentUser?.id || 'Connected WhatsApp Device';
                logEvent('system', 'SUCCESS', `WhatsApp Gateway connected successfully as: ${devName}`, currentUser);
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
                    if (!remoteJid || 
                        remoteJid.includes('@g.us') || 
                        remoteJid.includes('status@broadcast') ||
                        remoteJid.includes('@newsletter')) {
                        continue;
                    }

                    const text = msg.message.conversation ||
                                 msg.message.extendedTextMessage?.text ||
                                 msg.message.imageMessage?.caption ||
                                 msg.message.documentMessage?.caption ||
                                 '';

                    if (!text || !text.trim()) continue;

                    // Extract actual phone number if sender is using LID
                    let senderPn = null;
                    if (remoteJid.includes('@s.whatsapp.net')) {
                        senderPn = remoteJid.split('@')[0].split(':')[0].replace(/[^0-9]/g, '');
                    } else if (remoteJid.includes('@lid')) {
                        const rawLid = remoteJid.split('@')[0].split(':')[0];
                        let candidate = msg.key.senderPn || msg.key.participantPn || lidToPhone.get(rawLid) || null;
                        if (candidate && typeof candidate === 'string') {
                            senderPn = candidate.split('@')[0].split(':')[0].replace(/[^0-9]/g, '');
                            if (senderPn) {
                                recordLidPhoneMapping(rawLid, senderPn);
                            }
                        }
                    }
                    if (senderPn && typeof senderPn === 'string') {
                        senderPn = senderPn.replace(/[^0-9]/g, '');
                    }

                    const pushName = msg.pushName || 'Member';
                    logEvent('inbound', 'INFO', `Inbound message from ${pushName} (${senderPn ? '+' + senderPn : remoteJid}): "${text.trim().substring(0, 100)}"`, {
                        from: remoteJid,
                        phone: senderPn,
                        pushName,
                        text: text.trim()
                    });

                    const webhookUrl = process.env.WEBHOOK_URL || 'https://pmccuk.org/api/whatsapp/webhook';
                    try {
                        const response = await fetch(webhookUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${API_KEY}`
                            },
                            body: JSON.stringify({
                                from: remoteJid,          // The exact JID to reply to (preserves @lid or @s.whatsapp.net)
                                phone: senderPn,          // The detected real phone number
                                message: text.trim(),
                                pushName: pushName
                            })
                        });

                        if (response.ok) {
                            logEvent('inbound', 'SUCCESS', `Webhook processed successfully for: "${text.trim().substring(0, 40)}" (HTTP ${response.status})`);
                        } else {
                            logEvent('warn', 'WARNING', `Webhook returned HTTP ${response.status} for: "${text.trim().substring(0, 40)}"`);
                        }
                    } catch (fetchErr) {
                        logEvent('error', 'ERROR', `Webhook dispatch error: ${fetchErr.message}`, { error: fetchErr.message });
                    }
                }
            } catch (upsertErr) {
                logEvent('error', 'ERROR', `Error processing upsert: ${upsertErr.message}`, { error: upsertErr.message });
            }
        });
    } catch (err) {
        logEvent('error', 'ERROR', `WhatsApp Daemon init error: ${err.message}`, { error: err.stack });
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
        mapped_lids: lidToPhone.size,
        log_entries: systemLogs.length,
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

// Session logout (standard graceful unlink)
app.post('/session/logout', authenticate, async (req, res) => {
    try {
        logEvent('auth', 'WARNING', 'Session logout requested by administrator');
        isLoggingOut = true;
        if (sock) {
            try {
                sock.ev?.removeAllListeners();
                await Promise.race([
                    sock.logout(),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('Logout timeout')), 3500))
                ]);
            } catch (logoutErr) {
                try { sock.end(undefined); } catch (e) {}
            }
            sock = null;
        }

        try {
            fs.rmSync(SESSION_DIR, { recursive: true, force: true });
            fs.mkdirSync(SESSION_DIR, { recursive: true });
        } catch (rmErr) {}

        connectionState = 'disconnected';
        currentQrRaw = null;
        currentQrImage = null;
        currentUser = null;
        msgRetryStore.clear();
        lidToPhone.clear();
        phoneToLid.clear();
        isLoggingOut = false;

        logEvent('system', 'SUCCESS', 'WhatsApp session credentials unlinked. Restarting socket for fresh QR...');

        scheduleInitWhatsApp(1200);

        res.json({
            success: true,
            message: 'WhatsApp session logged out and unlinked successfully.'
        });
    } catch (e) {
        isLoggingOut = false;
        logEvent('error', 'ERROR', 'Session logout error: ' + e.message);
        res.status(500).json({
            success: false,
            error: e.message
        });
    }
});

// Session Revoke & Hard Purge (Force reset)
app.post('/session/revoke', authenticate, async (req, res) => {
    const force = req.body.force === true || req.body.force === 'true';
    logEvent('auth', 'WARNING', `Session revoke invoked (Mode: ${force ? 'FORCE PURGE' : 'STANDARD REVOKE'})`);

    try {
        isLoggingOut = true;
        if (sock) {
            try {
                sock.ev?.removeAllListeners();
                if (!force) {
                    await Promise.race([
                        sock.logout(),
                        new Promise((_, reject) => setTimeout(() => reject(new Error('Logout timeout')), 3000))
                    ]);
                } else {
                    sock.end(undefined);
                }
            } catch (err) {
                try { sock.end(undefined); } catch (e) {}
            }
            sock = null;
        }

        // Wipe session directory and immediately ensure clean folder exists
        try {
            fs.rmSync(SESSION_DIR, { recursive: true, force: true });
            fs.mkdirSync(SESSION_DIR, { recursive: true });
        } catch (rmErr) {
            console.warn('[WhatsApp Daemon] Remove session dir error:', rmErr.message);
        }

        // Reset all in-memory states
        connectionState = 'disconnected';
        currentQrRaw = null;
        currentQrImage = null;
        currentUser = null;
        msgRetryStore.clear();
        lidToPhone.clear();
        phoneToLid.clear();
        isLoggingOut = false;

        logEvent('system', 'SUCCESS', 'Session credentials completely wiped. Generating fresh QR code...');

        scheduleInitWhatsApp(1200);

        res.json({
            success: true,
            message: 'WhatsApp session successfully revoked and purged. A fresh QR code is being generated.'
        });
    } catch (e) {
        isLoggingOut = false;
        logEvent('error', 'ERROR', 'Session revoke failed: ' + e.message);
        res.status(500).json({
            success: false,
            error: e.message
        });
    }
});

// Get Live Gateway & Bot Logs
app.get('/logs', authenticate, (req, res) => {
    const limit = Math.min(parseInt(req.query.limit || '100', 10), 500);
    const type = req.query.type;
    const sinceId = req.query.since_id ? parseInt(req.query.since_id, 10) : null;
    const search = req.query.search ? String(req.query.search).toLowerCase() : null;

    let filtered = systemLogs;

    if (sinceId !== null) {
        filtered = filtered.filter(l => l.id > sinceId);
    }

    if (type && type !== 'all') {
        if (type === 'bot') {
            filtered = filtered.filter(l => l.type === 'inbound' || l.type === 'bot_reply');
        } else if (type === 'error') {
            filtered = filtered.filter(l => l.level === 'ERROR' || l.level === 'WARNING' || l.type === 'error');
        } else {
            filtered = filtered.filter(l => l.type === type);
        }
    }

    if (search) {
        filtered = filtered.filter(l => 
            l.message.toLowerCase().includes(search) || 
            (l.details && JSON.stringify(l.details).toLowerCase().includes(search))
        );
    }

    const sliced = filtered.slice(-limit);

    res.json({
        success: true,
        count: sliced.length,
        total_available: systemLogs.length,
        last_id: systemLogs.length > 0 ? systemLogs[systemLogs.length - 1].id : 0,
        logs: sliced
    });
});

// Clear Logs
app.delete('/logs', authenticate, (req, res) => {
    systemLogs.length = 0;
    try {
        fs.writeFileSync(LOG_FILE, '');
    } catch (e) {}
    logEvent('system', 'INFO', 'Log history cleared by administrator');
    res.json({
        success: true,
        message: 'Log buffer and file cleared successfully'
    });
});

// Send Text Message
app.post('/send/text', authenticate, async (req, res) => {
    if (connectionState !== 'connected' || !sock) {
        logEvent('error', 'WARNING', `Cannot dispatch text: Gateway not connected (State: ${connectionState})`);
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

        // Store outbound message for E2EE retry answering (fixes "Waiting for this message")
        if (result?.key?.id && (result.message || message)) {
            storeMessage(result.key.id, result.message || { conversation: message });
        }

        logEvent('outbound', 'SUCCESS', `Text message dispatched to ${jid}: "${message.substring(0, 80)}"`, {
            to: jid,
            messageId: result?.key?.id,
            snippet: message.substring(0, 120)
        });

        res.json({
            success: true,
            messageId: result?.key?.id,
            to: jid
        });
    } catch (err) {
        logEvent('error', 'ERROR', `Send text error to ${to}: ${err.message}`, { error: err.message });
        
        // Auto-recovery: If Bad MAC occurred, purge corrupted session file for this target
        if (err.message && err.message.includes('Bad MAC')) {
            try {
                const cleanPn = to.replace(/[^0-9]/g, '');
                const files = fs.readdirSync(SESSION_DIR);
                for (const f of files) {
                    if (f.startsWith(`session-${cleanPn}`) || (phoneToLid.has(cleanPn) && f.startsWith(`session-${phoneToLid.get(cleanPn)}`))) {
                        fs.unlinkSync(path.join(SESSION_DIR, f));
                        logEvent('system', 'WARNING', `Purged corrupted session file: ${f}`);
                    }
                }
            } catch (pErr) {}
        }

        res.status(500).json({
            success: false,
            error: err.message
        });
    }
});

// Send File / PDF / Image
app.post('/send/file', authenticate, async (req, res) => {
    if (connectionState !== 'connected' || !sock) {
        logEvent('error', 'WARNING', `Cannot dispatch file: Gateway not connected (State: ${connectionState})`);
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

        logEvent('outbound', 'SUCCESS', `File document dispatched to ${jid}: ${docName} (${caption ? '"' + caption.substring(0, 60) + '"' : 'no caption'})`, {
            to: jid,
            filename: docName,
            mimetype: resolvedMime,
            messageId: result?.key?.id
        });

        res.json({
            success: true,
            messageId: result?.key?.id,
            to: jid,
            filename: docName
        });
    } catch (err) {
        logEvent('error', 'ERROR', `Send file error to ${to}: ${err.message}`, { error: err.message });
        res.status(500).json({
            success: false,
            error: err.message
        });
    }
});

// Send Contact / vCard
app.post('/send/contact', authenticate, async (req, res) => {
    if (connectionState !== 'connected' || !sock) {
        logEvent('error', 'WARNING', `Cannot dispatch contact: Gateway not connected (State: ${connectionState})`);
        return res.status(503).json({
            success: false,
            error: 'WhatsApp device is not connected. Current state: ' + connectionState
        });
    }

    const { to, name, phone, role } = req.body;
    if (!to || !name || !phone) {
        return res.status(400).json({
            success: false,
            error: 'Parameters "to", "name", and "phone" are required.'
        });
    }

    try {
        const jid = formatJid(to);
        const cleanPhone = phone.replace(/[^0-9+]/g, '');
        const waid = cleanPhone.replace(/[^0-9]/g, '');
        const org = role ? `PMCC-UK - ${role}` : 'PMCC-UK Executive Committee';

        const vcard = 'BEGIN:VCARD\n'
            + 'VERSION:3.0\n'
            + `FN:${name}\n`
            + `ORG:${org};\n`
            + `TITLE:${role || 'Executive Member'}\n`
            + `TEL;type=CELL;type=VOICE;waid=${waid}:${cleanPhone}\n`
            + 'END:VCARD';

        const result = await sock.sendMessage(jid, {
            contacts: {
                displayName: name,
                contacts: [{ vcard }]
            }
        });

        if (result?.key?.id && result.message) {
            storeMessage(result.key.id, result.message);
        }

        logEvent('outbound', 'SUCCESS', `Contact card "${name}" (${cleanPhone}) dispatched to ${jid}`, {
            to: jid,
            contactName: name,
            contactPhone: cleanPhone,
            messageId: result?.key?.id
        });

        res.json({
            success: true,
            messageId: result?.key?.id,
            to: jid
        });
    } catch (err) {
        logEvent('error', 'ERROR', `Send contact error to ${to}: ${err.message}`, { error: err.message });
        res.status(500).json({
            success: false,
            error: err.message
        });
    }
});

// Autonomous 60-second background ticker to process due scheduled broadcasts
setInterval(() => {
    try {
        const phpBinary = fs.existsSync('/home/u601819832/bin/php') ? '/home/u601819832/bin/php' : 'php';
        const projectDir = path.resolve(__dirname, '..');
        const artisanPath = path.join(projectDir, 'artisan');

        if (fs.existsSync(artisanPath)) {
            const { exec } = require('child_process');
            exec(`"${phpBinary}" "${artisanPath}" whatsapp:process-scheduled`, { cwd: projectDir }, (error, stdout, stderr) => {
                if (stdout && stdout.trim().length > 0 && !stdout.includes('No scheduled')) {
                    logEvent('system', 'INFO', `Scheduled broadcasts processed: ${stdout.trim()}`);
                }
            });
        }
    } catch (e) {}
}, 60000);

// Start Daemon Server
app.listen(PORT, '0.0.0.0', () => {
    logEvent('system', 'INFO', `PMCC-UK WhatsApp Daemon initialized on port ${PORT}`);
    console.log(`====================================================`);
    console.log(`  PMCC-UK WhatsApp Daemon running on port ${PORT}`);
    console.log(`  Session Path: ${SESSION_DIR}`);
    console.log(`====================================================`);
    initWhatsApp();
});
