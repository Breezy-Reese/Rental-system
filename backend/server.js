require("dotenv").config();

const express = require("express");
const cors = require("cors");
const helmet = require("helmet");
const rateLimit = require("express-rate-limit");

const connectDB = require("./config/db");

// ============================================================
// ROUTES
// ============================================================

const authRoutes = require("./routes/authRoutes");
const adminRoutes = require("./routes/adminRoutes");
const customerRoutes = require("./routes/customerRoutes");
const propertyRoutes = require("./routes/propertyRoutes");
const unitRoutes = require("./routes/unitRoutes");
const tenantRoutes = require("./routes/tenantRoutes");
const leaseRoutes = require("./routes/leaseRoutes");
const paymentRoutes = require("./routes/paymentRoutes");
const expenseRoutes = require("./routes/expenseRoutes");
const maintenanceRoutes = require("./routes/maintenanceRoutes");
const notificationRoutes = require("./routes/notificationRoutes");
const mpesaRoutes = require("./routes/mpesaRoutes");

// ============================================================
// APP
// ============================================================

const app = express();

const PORT = Number(process.env.PORT) || 5000;
const HOST = "0.0.0.0";

// ============================================================
// TRUST RENDER PROXY
// ============================================================

app.set("trust proxy", 1);

// ============================================================
// SECURITY
// ============================================================

app.use(
  helmet({
    crossOriginResourcePolicy: {
      policy: "cross-origin",
    },
  })
);

// ============================================================
// CORS
// ============================================================

const clientUrl =
  process.env.CLIENT_URL || "http://localhost:8000";

app.use(
  cors({
    origin: clientUrl,
    credentials: true,
  })
);

// ============================================================
// BODY PARSING
// ============================================================

app.use(
  express.json({
    limit: "1mb",
  })
);

app.use(
  express.urlencoded({
    extended: true,
    limit: "1mb",
  })
);

// ============================================================
// HEALTH CHECK
// ============================================================
//
// Keep this BEFORE the /api rate limiter.
//
// Render can continuously check this endpoint without consuming
// application API rate-limit allowance.
//

app.get("/api/health", (req, res) => {
  res.status(200).json({
    success: true,
    message: "PropertyPro API is running",
    environment: process.env.NODE_ENV || "development",
    timestamp: new Date().toISOString(),
  });
});

// ============================================================
// RATE LIMITER
// ============================================================

const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,

  max: 200,

  standardHeaders: true,

  legacyHeaders: false,

  message: {
    success: false,
    message: "Too many requests. Please try again later.",
  },
});

app.use("/api", limiter);

// ============================================================
// AUTHENTICATION
// ============================================================

app.use("/api/auth", authRoutes);

// ============================================================
// ADMIN
// ============================================================

app.use("/api/admin", adminRoutes);

// ============================================================
// CUSTOMER
// ============================================================

app.use("/api/customer", customerRoutes);

// ============================================================
// PROPERTIES
// ============================================================

app.use("/api/properties", propertyRoutes);

// ============================================================
// UNITS
// ============================================================

app.use("/api/units", unitRoutes);

// ============================================================
// TENANTS
// ============================================================

app.use("/api/tenants", tenantRoutes);

// ============================================================
// LEASES
// ============================================================

app.use("/api/leases", leaseRoutes);

// ============================================================
// PAYMENTS
// ============================================================

app.use("/api/payments", paymentRoutes);

// ============================================================
// EXPENSES
// ============================================================

app.use("/api/expenses", expenseRoutes);

// ============================================================
// MAINTENANCE
// ============================================================

app.use("/api/maintenance", maintenanceRoutes);

// ============================================================
// NOTIFICATIONS
// ============================================================

app.use("/api/notifications", notificationRoutes);

// ============================================================
// MPESA
// ============================================================

app.use("/api/mpesa", mpesaRoutes);

// ============================================================
// 404 HANDLER
// ============================================================

app.use((req, res) => {
  res.status(404).json({
    success: false,
    message: "API route not found",
    path: req.originalUrl,
  });
});

// ============================================================
// GLOBAL ERROR HANDLER
// ============================================================

app.use((err, req, res, next) => {
  console.error("========================================");
  console.error("Unhandled server error");
  console.error("Message:", err.message);
  console.error("Stack:", err.stack);
  console.error("========================================");

  if (res.headersSent) {
    return next(err);
  }

  res.status(err.status || 500).json({
    success: false,
    message: err.message || "Internal server error",
  });
});

// ============================================================
// START SERVER
// ============================================================

let server;

async function startServer() {
  try {
    // --------------------------------------------------------
    // Connect to MongoDB BEFORE accepting application traffic.
    // --------------------------------------------------------

    console.log("Connecting to MongoDB...");

    await connectDB();

    console.log("MongoDB connection established.");

    // --------------------------------------------------------
    // Start HTTP server.
    // --------------------------------------------------------

    server = app.listen(PORT, HOST, () => {
      console.log("========================================");
      console.log("PropertyPro API started successfully");
      console.log(`Host: ${HOST}`);
      console.log(`Port: ${PORT}`);
      console.log(
        `Environment: ${process.env.NODE_ENV || "development"}`
      );
      console.log(`Client URL: ${clientUrl}`);
      console.log("========================================");
    });

    // --------------------------------------------------------
    // Render / Node connection reliability.
    // --------------------------------------------------------

    server.keepAliveTimeout = 120000;

    // Keep headers timeout slightly higher than keep-alive.
    server.headersTimeout = 125000;

    // Allow long-running API requests to complete.
    server.requestTimeout = 120000;

    server.on("error", (error) => {
      console.error("HTTP server error:", error);
    });
  } catch (error) {
    console.error("========================================");
    console.error("FAILED TO START PROPERTYPRO API");
    console.error(error);
    console.error("========================================");

    process.exit(1);
  }
}

// ============================================================
// UNHANDLED PROMISE REJECTION
// ============================================================

process.on("unhandledRejection", (reason) => {
  console.error("========================================");
  console.error("Unhandled Promise Rejection");
  console.error(reason);
  console.error("========================================");
});

// ============================================================
// UNCAUGHT EXCEPTION
// ============================================================

process.on("uncaughtException", (error) => {
  console.error("========================================");
  console.error("Uncaught Exception");
  console.error(error);
  console.error("========================================");

  process.exit(1);
});

// ============================================================
// GRACEFUL SHUTDOWN
// ============================================================

function shutdown(signal) {
  console.log(`${signal} received. Shutting down gracefully...`);

  if (!server) {
    process.exit(0);
  }

  server.close(() => {
    console.log("HTTP server closed.");
    process.exit(0);
  });

  setTimeout(() => {
    console.error("Forced shutdown after timeout.");
    process.exit(1);
  }, 10000);
}

process.on("SIGTERM", () => {
  shutdown("SIGTERM");
});

process.on("SIGINT", () => {
  shutdown("SIGINT");
});

// ============================================================
// BOOT
// ============================================================

startServer();