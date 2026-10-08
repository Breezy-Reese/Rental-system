/**
 * ============================================================
 * PropertyPro - System Routes
 * ============================================================
 *
 * Endpoints (all admin-only):
 *   GET  /api/system/db-stats     → database stats
 *   POST /api/system/send-otp     → send 4-digit OTP (SMS → email → console)
 *   POST /api/system/verify-otp   → verify OTP
 */

const express  = require("express");
const mongoose = require("mongoose");

const protect   = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const router = express.Router();

/*
|--------------------------------------------------------------------------
| In-memory OTP store
|--------------------------------------------------------------------------
*/

const otpStore = new Map();

const OTP_TTL_MS    = 5 * 60 * 1000;   // 5 minutes
const OTP_MAX_TRIES = 5;

function cleanupExpiredOtps() {
  const now = Date.now();

  for (const [key, entry] of otpStore.entries()) {
    if (entry.expiresAt < now) {
      otpStore.delete(key);
    }
  }
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const READY_STATES = {
  0: "disconnected",
  1: "connected",
  2: "connecting",
  3: "disconnecting",
};

function databaseState() {
  return (
    READY_STATES[mongoose.connection.readyState] ||
    "unknown"
  );
}

/**
 * Normalize a Kenyan phone number to 2547XXXXXXXX or 2541XXXXXXXX.
 */
function normalizePhone(input) {
  let p = String(input || "").replace(/\D/g, "");

  if (!p) {
    return null;
  }

  if (p.startsWith("0")) {
    p = "254" + p.slice(1);
  } else if (p.startsWith("7") || p.startsWith("1")) {
    p = "254" + p;
  } else if (p.startsWith("254")) {
    // already correct
  } else {
    return null;
  }

  if (!/^254(7|1)\d{8}$/.test(p)) {
    return null;
  }

  return p;
}

/*
|--------------------------------------------------------------------------
| SMS sender (TextSMS.co.ke)
|--------------------------------------------------------------------------
*/

async function sendOtpSms(phone, code) {
  const apiKey    = process.env.TEXTSMS_API_KEY;
  const partnerId = process.env.TEXTSMS_PARTNER_ID;
  const senderId  = process.env.TEXTSMS_SENDER_ID;

  if (!apiKey || !partnerId || !senderId) {
    console.log("[SMS] not configured — missing TEXTSMS_* env vars");
    return { sent: false, reason: "SMS not configured" };
  }

  const normalized = normalizePhone(phone);

  if (!normalized) {
    console.log("[SMS] invalid phone:", JSON.stringify(phone));
    return { sent: false, reason: "Invalid phone number" };
  }

  const message =
    `Your PropertyPro system access code is ${code}. ` +
    `It expires in 5 minutes.`;

  try {
    const response = await fetch(
      "https://sms.textsms.co.ke/api/services/sendsms/",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
        },
        body: JSON.stringify({
          apikey:    apiKey,
          partnerID: partnerId,
          message:   message,
          shortcode: senderId,
          mobile:    normalized,
        }),
      }
    );

    const text = await response.text();

    if (!response.ok) {
      console.warn(
        "[SMS] send failed:",
        response.status,
        text.slice(0, 400)
      );
      return {
        sent: false,
        reason: `HTTP ${response.status}`,
        raw: text.slice(0, 400),
      };
    }

    console.log(
      "[SMS] sent to",
      normalized,
      "→",
      text.slice(0, 200)
    );

    return { sent: true, raw: text.slice(0, 400) };
  } catch (err) {
    console.warn("[SMS] error:", err.message);
    return { sent: false, reason: err.message };
  }
}

/*
|--------------------------------------------------------------------------
| Email sender (nodemailer) — optional fallback
|--------------------------------------------------------------------------
*/

async function sendOtpEmail(email, code) {
  if (!email) {
    return { sent: false, reason: "No email address" };
  }

  let nodemailer;

  try {
    nodemailer = require("nodemailer");
  } catch (_) {
    console.log("[EMAIL] nodemailer not installed — skipping");
    return { sent: false, reason: "nodemailer not installed" };
  }

  if (
    !process.env.SMTP_HOST ||
    !process.env.SMTP_USER ||
    !process.env.SMTP_PASS
  ) {
    console.log("[EMAIL] SMTP not configured — skipping");
    return { sent: false, reason: "SMTP not configured" };
  }

  try {
    const transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port: Number(process.env.SMTP_PORT || 587),
      secure: Number(process.env.SMTP_PORT) === 465,
      auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASS,
      },
    });

    await transporter.sendMail({
      from: process.env.SMTP_FROM || process.env.SMTP_USER,
      to: email,
      subject: "PropertyPro — System Access Code",
      text: `Your code is ${code}. Expires in 5 minutes.`,
      html: `
        <div style="font-family:sans-serif;padding:24px;background:#0f172a;color:#fff;border-radius:12px;">
          <h2 style="margin:0 0 12px;">System Access Code</h2>
          <p style="margin:0 0 20px;color:#94a3b8;">Use the code below to verify your identity.</p>
          <div style="font-size:32px;letter-spacing:12px;font-weight:700;background:#1e293b;padding:20px;border-radius:10px;text-align:center;color:#fbbf24;">
            ${code}
          </div>
          <p style="margin:20px 0 0;color:#64748b;font-size:12px;">Expires in 5 minutes.</p>
        </div>
      `,
    });

    console.log("[EMAIL] sent to", email);
    return { sent: true };
  } catch (err) {
    console.warn("[EMAIL] failed:", err.message);
    return { sent: false, reason: err.message };
  }
}

/*
|--------------------------------------------------------------------------
| GET /api/system/db-stats
|--------------------------------------------------------------------------
*/

router.get("/db-stats", protect, adminOnly, async (req, res) => {
  try {
    const connection = mongoose.connection;

    if (connection.readyState !== 1 || !connection.db) {
      return res.status(503).json({
        success: false,
        message: "Database is not connected.",
        data: { state: databaseState() },
      });
    }

    const db = connection.db;

    let pingMs = null;
    try {
      const start = Date.now();
      await db.admin().ping();
      pingMs = Date.now() - start;
    } catch (e) {
      console.warn("db-stats ping failed:", e.message);
    }

    let serverVersion = null;
    try {
      const info = await db.admin().serverInfo();
      serverVersion = info.version || null;
    } catch (_) {}

    const collections = await db.listCollections().toArray();
    const counts = {};

    for (const col of collections) {
      try {
        counts[col.name] = await db
          .collection(col.name)
          .estimatedDocumentCount();
      } catch (_) {
        counts[col.name] = null;
      }
    }

    return res.status(200).json({
      success: true,
      data: {
        db: db.databaseName,
        state: databaseState(),
        readyState: connection.readyState,
        pingMs,
        serverVersion,
        collections: counts,
        checkedAt: new Date().toISOString(),
      },
    });
  } catch (error) {
    console.error("db-stats error:", error.message);
    return res.status(500).json({
      success: false,
      message: "Failed to read database stats.",
      error: error.message,
    });
  }
});

/*
|--------------------------------------------------------------------------
| POST /api/system/send-otp
|--------------------------------------------------------------------------
|
| Tries SMS first (TextSMS), then email, then console fallback.
| Destination comes from the JWT payload (phone / email).
|--------------------------------------------------------------------------
*/

router.post("/send-otp", protect, adminOnly, async (req, res) => {
  try {
    cleanupExpiredOtps();

    const userId = req.user.id || req.user._id;
    const phone  = req.user.phone || "";
    const email  = req.user.email || "";

    /*
     * Debug output — shows exactly what the token carried.
     */
    console.log("[OTP DEBUG] token payload:", {
      id:    userId,
      phone: phone || "(empty)",
      email: email || "(empty)",
      role:  req.user.role,
    });

    if (!userId) {
      return res.status(400).json({
        success: false,
        message: "User ID missing from token.",
      });
    }

    const code = String(
      Math.floor(1000 + Math.random() * 9000)
    );

    otpStore.set(String(userId), {
      code,
      expiresAt: Date.now() + OTP_TTL_MS,
      attempts: 0,
      phone,
      email,
    });

    let delivered   = false;
    let channel     = "console";
    let channelNote = "Check server console (no delivery channel configured).";

    /*
     * 1. SMS
     */
    if (phone) {
      const sms = await sendOtpSms(phone, code);

      if (sms.sent) {
        delivered   = true;
        channel     = "sms";
        channelNote = `Code sent by SMS to ${normalizePhone(phone) || phone}.`;
      }
    }

    /*
     * 2. Email
     */
    if (!delivered && email) {
      const em = await sendOtpEmail(email, code);

      if (em.sent) {
        delivered   = true;
        channel     = "email";
        channelNote = `Code sent to ${email}.`;
      }
    }

    /*
     * 3. Console fallback
     */
    if (!delivered) {
      console.log("========================================");
      console.log("[SYSTEM OTP — CONSOLE FALLBACK]");
      console.log("User    :", userId);
      console.log("Phone   :", phone || "(none)");
      console.log("Email   :", email || "(none)");
      console.log("Code    :", code);
      console.log("Expires : 5 minutes");
      console.log("========================================");
    }

    return res.status(200).json({
      success: true,
      message: channelNote,
      data: {
        channel,
        delivered,
        expiresInSeconds: OTP_TTL_MS / 1000,
      },
    });
  } catch (error) {
    console.error("send-otp error:", error.message);
    return res.status(500).json({
      success: false,
      message: "Failed to send verification code.",
      error: error.message,
    });
  }
});

/*
|--------------------------------------------------------------------------
| POST /api/system/verify-otp
|--------------------------------------------------------------------------
*/

router.post("/verify-otp", protect, adminOnly, async (req, res) => {
  try {
    cleanupExpiredOtps();

    const userId = req.user.id || req.user._id;
    const code   = String(req.body.code || "").trim();

    if (!userId) {
      return res.status(400).json({
        success: false,
        message: "User ID missing from token.",
      });
    }

    if (!/^\d{4}$/.test(code)) {
      return res.status(400).json({
        success: false,
        message: "Enter a 4-digit code.",
      });
    }

    const entry = otpStore.get(String(userId));

    if (!entry) {
      return res.status(400).json({
        success: false,
        message: "No active code. Request a new one.",
      });
    }

    if (entry.expiresAt < Date.now()) {
      otpStore.delete(String(userId));
      return res.status(400).json({
        success: false,
        message: "Code expired. Request a new one.",
      });
    }

    entry.attempts += 1;

    if (entry.attempts > OTP_MAX_TRIES) {
      otpStore.delete(String(userId));
      return res.status(429).json({
        success: false,
        message: "Too many attempts. Request a new code.",
      });
    }

    if (entry.code !== code) {
      return res.status(400).json({
        success: false,
        message: "Incorrect code.",
        data: { attemptsLeft: OTP_MAX_TRIES - entry.attempts },
      });
    }

    otpStore.delete(String(userId));

    return res.status(200).json({
      success: true,
      message: "Verified.",
      data: { verifiedAt: new Date().toISOString() },
    });
  } catch (error) {
    console.error("verify-otp error:", error.message);
    return res.status(500).json({
      success: false,
      message: "Failed to verify code.",
      error: error.message,
    });
  }
});

module.exports = router;