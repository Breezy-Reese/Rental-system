/*
 * ============================================================
 * PropertyPro - System Routes
 * ============================================================
 *
 * Endpoints (admin-only):
 *   GET  /api/system/db-stats
 *   POST /api/system/send-otp
 *   POST /api/system/verify-otp
 *
 * OTP delivery:
 *   - SMS through TextSMS
 *   - Email through Brevo SMTP / Nodemailer
 *   - Both channels are attempted independently
 *   - OTP expires after 5 minutes
 *   - Maximum 5 verification attempts
 */

const express = require("express");
const mongoose = require("mongoose");
const crypto = require("crypto");
const nodemailer = require("nodemailer");

const protect = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const router = express.Router();

/*
|--------------------------------------------------------------------------
| OTP configuration
|--------------------------------------------------------------------------
*/

const otpStore = new Map();

const OTP_TTL_MS = 5 * 60 * 1000;
const OTP_MAX_TRIES = 5;

function cleanupExpiredOtps() {
    const now = Date.now();

    for (const [key, entry] of otpStore.entries()) {
        if (entry.expiresAt <= now) {
            otpStore.delete(key);
        }
    }
}

/*
|--------------------------------------------------------------------------
| Database helpers
|--------------------------------------------------------------------------
*/

const READY_STATES = {
    0: "disconnected",
    1: "connected",
    2: "connecting",
    3: "disconnecting",
};

function databaseState() {
    return READY_STATES[mongoose.connection.readyState] || "unknown";
}

/*
|--------------------------------------------------------------------------
| Phone number helper
|--------------------------------------------------------------------------
*/

function normalizePhone(input) {
    let phone = String(input || "").replace(/\D/g, "");

    if (!phone) {
        return null;
    }

    if (phone.startsWith("0")) {
        phone = "254" + phone.slice(1);
    } else if (phone.startsWith("7") || phone.startsWith("1")) {
        phone = "254" + phone;
    }

    if (!/^254[17]\d{8}$/.test(phone)) {
        return null;
    }

    return phone;
}

/*
|--------------------------------------------------------------------------
| SMS sender - TextSMS
|--------------------------------------------------------------------------
*/

async function sendOtpSms(phone, code) {
    const apiKey = process.env.TEXTSMS_API_KEY;
    const partnerId = process.env.TEXTSMS_PARTNER_ID;
    const senderId = process.env.TEXTSMS_SENDER_ID;

    if (!apiKey || !partnerId || !senderId) {
        console.warn("[OTP SMS] Not configured.");
        return {
            sent: false,
            reason: "SMS is not configured.",
        };
    }

    const normalizedPhone = normalizePhone(phone);

    if (!normalizedPhone) {
        console.warn("[OTP SMS] Invalid phone number.");
        return {
            sent: false,
            reason: "Invalid phone number.",
        };
    }

    const message =
        `Your PropertyPro system access code is ${code}. ` +
        "It expires in 5 minutes.";

    try {
        const response = await fetch(
            "https://sms.textsms.co.ke/api/services/sendsms/",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    apikey: apiKey,
                    partnerID: partnerId,
                    message,
                    shortcode: senderId,
                    mobile: normalizedPhone,
                }),
                signal: AbortSignal.timeout(15000),
            }
        );

        const responseText = await response.text();

        if (!response.ok) {
            console.error(
                "[OTP SMS] HTTP failure:",
                response.status,
                responseText.slice(0, 250)
            );

            return {
                sent: false,
                reason: `SMS provider returned HTTP ${response.status}.`,
            };
        }

        let result;

        try {
            result = JSON.parse(responseText);
        } catch {
            result = null;
        }

        const smsAccepted = Array.isArray(result?.responses)
            ? result.responses.some(
                (item) => Number(item["response-code"]) === 200
            )
            : false;

        if (!smsAccepted) {
            console.error(
                "[OTP SMS] Provider did not confirm delivery acceptance."
            );

            return {
                sent: false,
                reason: "SMS provider did not confirm acceptance.",
            };
        }

        console.log("[OTP SMS] Provider accepted the message.");

        return { sent: true };
    } catch (error) {
        console.error("[OTP SMS] Sending failed:", error.message);

        return {
            sent: false,
            reason: error.message,
        };
    }
}

/*
|--------------------------------------------------------------------------
| Email sender - Brevo SMTP through Nodemailer
|--------------------------------------------------------------------------
*/

async function sendOtpEmail(email, code) {
    if (!email) {
        return {
            sent: false,
            reason: "No email address is available.",
        };
    }

    const host = process.env.SMTP_HOST || "smtp-relay.brevo.com";
    const port = Number(process.env.SMTP_PORT || 587);
    const user = process.env.SMTP_USER;
    const pass = process.env.SMTP_PASS;
    const from = process.env.SMTP_FROM;

    if (!user || !pass || !from) {
        console.error(
            "[OTP EMAIL] Missing SMTP_USER, SMTP_PASS, or SMTP_FROM."
        );

        return {
            sent: false,
            reason: "Email configuration is incomplete.",
        };
    }

    if (!Number.isInteger(port) || port < 1 || port > 65535) {
        console.error("[OTP EMAIL] Invalid SMTP_PORT.");
        return {
            sent: false,
            reason: "Invalid SMTP port.",
        };
    }

    let transporter;

    try {
        transporter = nodemailer.createTransport({
            host,
            port,
            secure: port === 465,
            auth: {
                user,
                pass,
            },
            connectionTimeout: 15000,
            greetingTimeout: 15000,
            socketTimeout: 20000,
        });

        console.log("[OTP EMAIL] Checking SMTP connection...");

        await transporter.verify();

        console.log("[OTP EMAIL] SMTP connection verified.");

        const result = await transporter.sendMail({
            from,
            to: email,
            subject: "PropertyPro - System Access Code",
            text:
                `Your PropertyPro system access code is ${code}. ` +
                "It expires in 5 minutes. If you did not request this " +
                "code, you can ignore this email.",

            html: `
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport"
                          content="width=device-width, initial-scale=1.0">
                    <title>PropertyPro Access Code</title>
                </head>
                <body style="
                    margin:0;
                    padding:24px;
                    background:#f1f5f9;
                    font-family:Arial,Helvetica,sans-serif;
                    color:#0f172a;
                ">
                    <div style="
                        max-width:480px;
                        margin:0 auto;
                        padding:32px;
                        background:#ffffff;
                        border-radius:16px;
                        border:1px solid #e2e8f0;
                    ">
                        <div style="
                            font-size:13px;
                            font-weight:bold;
                            letter-spacing:2px;
                            color:#4f46e5;
                            margin-bottom:24px;
                        ">
                            PROPERTYPRO
                        </div>

                        <h2 style="
                            margin:0 0 12px;
                            font-size:24px;
                            color:#0f172a;
                        ">
                            System Access Code
                        </h2>

                        <p style="
                            margin:0 0 24px;
                            line-height:1.6;
                            color:#475569;
                        ">
                            Use this code to verify your identity.
                        </p>

                        <div style="
                            padding:22px 12px;
                            text-align:center;
                            border-radius:12px;
                            background:#0f172a;
                            color:#fbbf24;
                            font-size:34px;
                            font-weight:bold;
                            letter-spacing:10px;
                        ">${code}</div>

                        <p style="
                            margin:22px 0 0;
                            font-size:13px;
                            line-height:1.6;
                            color:#64748b;
                        ">
                            This code expires in 5 minutes. Never share
                            your verification code with anyone.
                        </p>

                        <hr style="
                            margin:26px 0;
                            border:none;
                            border-top:1px solid #e2e8f0;
                        ">

                        <p style="
                            margin:0;
                            font-size:12px;
                            line-height:1.6;
                            color:#94a3b8;
                        ">
                            If you did not request this code, you can
                            safely ignore this email.
                        </p>
                    </div>
                </body>
                </html>
            `,
        });

        console.log(
            "[OTP EMAIL] SMTP accepted message. Message ID:",
            result.messageId
        );

        if (result.accepted && result.accepted.length > 0) {
            return {
                sent: true,
                messageId: result.messageId,
            };
        }

        console.error("[OTP EMAIL] SMTP did not confirm a recipient.");

        return {
            sent: false,
            reason: "SMTP did not confirm recipient acceptance.",
        };
    } catch (error) {
        console.error(
            "[OTP EMAIL] Sending failed:",
            error.code || "SMTP_ERROR",
            error.message
        );

        return {
            sent: false,
            reason: error.message,
        };
    } finally {
        if (transporter) {
            transporter.close();
        }
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
                data: {
                    state: databaseState(),
                },
            });
        }

        const db = connection.db;

        let pingMs = null;
        let serverVersion = null;

        try {
            const start = Date.now();
            await db.admin().ping();
            pingMs = Date.now() - start;
        } catch (error) {
            console.warn("[DB STATS] Ping failed:", error.message);
        }

        try {
            const info = await db.admin().serverInfo();
            serverVersion = info.version || null;
        } catch (error) {
            console.warn(
                "[DB STATS] Could not read server version:",
                error.message
            );
        }

        const collections = await db.listCollections().toArray();
        const counts = {};

        for (const collection of collections) {
            try {
                counts[collection.name] = await db
                    .collection(collection.name)
                    .estimatedDocumentCount();
            } catch {
                counts[collection.name] = null;
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
        console.error("[DB STATS] Failed:", error.message);

        return res.status(500).json({
            success: false,
            message: "Failed to read database statistics.",
        });
    }
});

/*
|--------------------------------------------------------------------------
| POST /api/system/send-otp
|--------------------------------------------------------------------------
|
| Attempts SMS and email independently.
| Does not print the OTP to application logs.
|--------------------------------------------------------------------------
*/

router.post("/send-otp", protect, adminOnly, async (req, res) => {
    try {
        cleanupExpiredOtps();

        const userId = req.user.id || req.user._id;
        const phone = String(req.user.phone || "").trim();
        const email = String(req.user.email || "").trim();

        if (!userId) {
            return res.status(400).json({
                success: false,
                message: "User ID is missing from the authentication token.",
            });
        }

        if (!phone && !email) {
            return res.status(400).json({
                success: false,
                message: "No phone number or email is registered.",
            });
        }

        const key = String(userId);

        // Generate a cryptographically secure four-digit code.
        const code = String(crypto.randomInt(1000, 10000));

        otpStore.set(key, {
            code,
            expiresAt: Date.now() + OTP_TTL_MS,
            attempts: 0,
        });

        console.log(
            "[OTP] Request received. SMS configured:",
            Boolean(phone),
            "Email configured:",
            Boolean(email)
        );

        /*
         * Attempt both channels independently.
         * A successful SMS must not prevent an email attempt.
         */
        const [smsResult, emailResult] = await Promise.all([
            phone
                ? sendOtpSms(phone, code)
                : Promise.resolve({
                    sent: false,
                    reason: "No phone number is registered.",
                }),

            email
                ? sendOtpEmail(email, code)
                : Promise.resolve({
                    sent: false,
                    reason: "No email address is registered.",
                }),
        ]);

        const channels = [];

        if (smsResult.sent) {
            channels.push("sms");
        }

        if (emailResult.sent) {
            channels.push("email");
        }

        if (channels.length === 0) {
            otpStore.delete(key);

            console.error("[OTP] Both delivery channels failed.", {
                sms: smsResult.reason,
                email: emailResult.reason,
            });

            return res.status(502).json({
                success: false,
                message:
                    "The verification code could not be delivered. " +
                    "Please try again later.",
                data: {
                    smsSent: false,
                    emailSent: false,
                },
            });
        }

        let message;

        if (channels.length === 2) {
            message = "Verification code sent by SMS and email.";
        } else if (channels[0] === "sms") {
            message =
                "Verification code sent by SMS. Email delivery failed.";
        } else {
            message =
                "Verification code sent by email. SMS delivery failed.";
        }

        console.log("[OTP] Delivery results:", {
            smsSent: smsResult.sent,
            emailSent: emailResult.sent,
        });

        return res.status(200).json({
            success: true,
            message,
            data: {
                channel: channels.join("+"),
                delivered: true,
                smsSent: smsResult.sent,
                emailSent: emailResult.sent,
                expiresInSeconds: OTP_TTL_MS / 1000,
            },
        });
    } catch (error) {
        console.error("[OTP] Send request failed:", error.message);

        return res.status(500).json({
            success: false,
            message: "Failed to send verification code.",
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
        const code = String(req.body?.code || "").trim();

        if (!userId) {
            return res.status(400).json({
                success: false,
                message: "User ID is missing from the authentication token.",
            });
        }

        if (!/^\d{4}$/.test(code)) {
            return res.status(400).json({
                success: false,
                message: "Enter a valid four-digit code.",
            });
        }

        const key = String(userId);
        const entry = otpStore.get(key);

        if (!entry) {
            return res.status(400).json({
                success: false,
                message: "No active code. Request a new one.",
            });
        }

        if (entry.expiresAt <= Date.now()) {
            otpStore.delete(key);

            return res.status(400).json({
                success: false,
                message: "Code expired. Request a new one.",
            });
        }

        entry.attempts += 1;

        if (entry.attempts > OTP_MAX_TRIES) {
            otpStore.delete(key);

            return res.status(429).json({
                success: false,
                message: "Too many attempts. Request a new code.",
            });
        }

        if (entry.code !== code) {
            const attemptsLeft = Math.max(
                0,
                OTP_MAX_TRIES - entry.attempts
            );

            if (attemptsLeft === 0) {
                otpStore.delete(key);

                return res.status(429).json({
                    success: false,
                    message: "Too many attempts. Request a new code.",
                });
            }

            return res.status(400).json({
                success: false,
                message: "Incorrect code.",
                data: {
                    attemptsLeft,
                },
            });
        }

        otpStore.delete(key);

        return res.status(200).json({
            success: true,
            message: "Verification successful.",
            data: {
                verifiedAt: new Date().toISOString(),
            },
        });
    } catch (error) {
        console.error("[OTP] Verification failed:", error.message);

        return res.status(500).json({
            success: false,
            message: "Failed to verify the code.",
        });
    }
});

module.exports = router;
