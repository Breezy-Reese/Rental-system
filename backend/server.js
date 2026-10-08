require("dotenv").config();

const express = require("express");
const cors = require("cors");
const helmet = require("helmet");
const rateLimit = require("express-rate-limit");
const mongoose = require("mongoose");

const connectDB = require("./config/db");

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

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
const systemRoutes = require("./routes/systemRoutes");

/*
|--------------------------------------------------------------------------
| App
|--------------------------------------------------------------------------
*/

const app = express();

const PORT =
  Number(process.env.PORT) || 5000;

const HOST = "0.0.0.0";

app.set("trust proxy", 1);

/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

app.use(
  helmet({
    crossOriginResourcePolicy: {
      policy: "cross-origin",
    },
  })
);

/*
|--------------------------------------------------------------------------
| CORS
|--------------------------------------------------------------------------
*/

const clientUrl =
  process.env.CLIENT_URL ||
  "http://localhost:8000";

app.use(
  cors({
    origin: clientUrl,
    credentials: true,
  })
);

/*
|--------------------------------------------------------------------------
| Body Parsing
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
|
| This endpoint must remain available even while MongoDB is
| reconnecting. Render can therefore determine that the Node
| process itself is alive.
|
|--------------------------------------------------------------------------
*/

app.get(
  "/api/health",
  (req, res) => {

    const states = {
      0: "disconnected",
      1: "connected",
      2: "connecting",
      3: "disconnecting",
    };

    const database =
      states[
        mongoose.connection.readyState
      ] || "unknown";

    res.status(200).json({
      success: true,
      message: "PropertyPro API is running",
      environment:
        process.env.NODE_ENV ||
        "development",
      database,
      timestamp:
        new Date().toISOString(),
    });
  }
);

/*
|--------------------------------------------------------------------------
| API Rate Limiter
|--------------------------------------------------------------------------
*/

const limiter =
  rateLimit({
    windowMs:
      15 * 60 * 1000,

    max: 200,

    standardHeaders: true,

    legacyHeaders: false,

    message: {
      success: false,
      message:
        "Too many requests. Please try again later.",
    },
  });

app.use(
  "/api",
  limiter
);

/*
|--------------------------------------------------------------------------
| Database Availability Guard
|--------------------------------------------------------------------------
|
| Health endpoint is above this middleware.
|
| If MongoDB is temporarily reconnecting, API requests receive a
| clean 503 JSON response instead of crashing or hanging.
|
|--------------------------------------------------------------------------
*/

app.use(
  "/api",
  (req, res, next) => {

    const ready =
      mongoose.connection.readyState === 1;

    if (!ready) {

      return res.status(503).json({
        success: false,
        message:
          "Database is temporarily unavailable. Please try again.",
      });
    }

    next();
  }
);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

app.use(
  "/api/auth",
  authRoutes
);

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

app.use(
  "/api/admin",
  adminRoutes
);

/*
|--------------------------------------------------------------------------
| Customer
|--------------------------------------------------------------------------
*/

app.use(
  "/api/customer",
  customerRoutes
);

/*
|--------------------------------------------------------------------------
| Properties
|--------------------------------------------------------------------------
*/

app.use(
  "/api/properties",
  propertyRoutes
);

/*
|--------------------------------------------------------------------------
| Units
|--------------------------------------------------------------------------
*/

app.use(
  "/api/units",
  unitRoutes
);

/*
|--------------------------------------------------------------------------
| Tenants
|--------------------------------------------------------------------------
*/

app.use(
  "/api/tenants",
  tenantRoutes
);

/*
|--------------------------------------------------------------------------
| Leases
|--------------------------------------------------------------------------
*/

app.use(
  "/api/leases",
  leaseRoutes
);

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
*/

app.use(
  "/api/payments",
  paymentRoutes
);

/*
|--------------------------------------------------------------------------
| Expenses
|--------------------------------------------------------------------------
*/

app.use(
  "/api/expenses",
  expenseRoutes
);

/*
|--------------------------------------------------------------------------
| Maintenance
|--------------------------------------------------------------------------
*/

app.use(
  "/api/maintenance",
  maintenanceRoutes
);

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/

app.use(
  "/api/notifications",
  notificationRoutes
);

/*
|--------------------------------------------------------------------------
| M-Pesa
|--------------------------------------------------------------------------
*/

app.use(
  "/api/mpesa",
  mpesaRoutes
);

/*
|--------------------------------------------------------------------------
| System
|--------------------------------------------------------------------------
*/

app.use(
  "/api/system",
  systemRoutes
);

/*
|--------------------------------------------------------------------------
| API 404
|--------------------------------------------------------------------------
*/

app.use(
  (req, res) => {

    res.status(404).json({
      success: false,
      message: "API route not found",
      path: req.originalUrl,
    });
  }
);

/*
|--------------------------------------------------------------------------
| Global Error Handler
|--------------------------------------------------------------------------
*/

app.use(
  (err, req, res, next) => {

    console.error(
      "========================================"
    );

    console.error(
      "Unhandled server error"
    );

    console.error(
      "Message:",
      err.message
    );

    console.error(
      "Stack:",
      err.stack
    );

    console.error(
      "========================================"
    );

    if (res.headersSent) {
      return next(err);
    }

    res.status(
      err.status || 500
    ).json({
      success: false,
      message:
        err.message ||
        "Internal server error",
    });
  }
);

/*
|--------------------------------------------------------------------------
| Database Connection Retry
|--------------------------------------------------------------------------
*/

let shuttingDown = false;

const sleep = (ms) =>
  new Promise(
    (resolve) =>
      setTimeout(resolve, ms)
  );

async function connectDatabaseWithRetry() {

  while (!shuttingDown) {

    try {

      console.log(
        "Connecting to MongoDB..."
      );

      await connectDB();

      console.log(
        "MongoDB is ready."
      );

      return;

    } catch (error) {

      console.error(
        "MongoDB connection attempt failed:"
      );

      console.error(
        error.message
      );

      console.log(
        "Retrying MongoDB connection in 5 seconds..."
      );

      await sleep(5000);
    }
  }
}

/*
|--------------------------------------------------------------------------
| Start HTTP Server
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Start listening BEFORE connecting to MongoDB.
|
| This prevents Render from seeing a dead/unavailable HTTP process
| while MongoDB is still connecting.
|
|--------------------------------------------------------------------------
*/

let server;

async function startServer() {

  server = app.listen(
    PORT,
    HOST,
    () => {

      console.log(
        "========================================"
      );

      console.log(
        "PropertyPro API started successfully"
      );

      console.log(
        `Host: ${HOST}`
      );

      console.log(
        `Port: ${PORT}`
      );

      console.log(
        `Environment: ${
          process.env.NODE_ENV ||
          "development"
        }`
      );

      console.log(
        `Client URL: ${clientUrl}`
      );

      console.log(
        "========================================"
      );
    }
  );

  /*
   * Node / Render connection settings
   */

  server.keepAliveTimeout =
    120000;

  server.headersTimeout =
    125000;

  server.requestTimeout =
    120000;

  server.on(
    "error",
    (error) => {

      console.error(
        "HTTP server error:",
        error
      );
    }
  );

  /*
   * Connect to MongoDB after the HTTP server
   * has started listening.
   */

  connectDatabaseWithRetry();
}

/*
|--------------------------------------------------------------------------
| Unhandled Promise Rejection
|--------------------------------------------------------------------------
*/

process.on(
  "unhandledRejection",
  (reason) => {

    console.error(
      "========================================"
    );

    console.error(
      "Unhandled Promise Rejection"
    );

    console.error(reason);

    console.error(
      "========================================"
    );
  }
);

/*
|--------------------------------------------------------------------------
| Uncaught Exception
|--------------------------------------------------------------------------
*/

process.on(
  "uncaughtException",
  (error) => {

    console.error(
      "========================================"
    );

    console.error(
      "Uncaught Exception"
    );

    console.error(error);

    console.error(
      "========================================"
    );

    process.exit(1);
  }
);

/*
|--------------------------------------------------------------------------
| Graceful Shutdown
|--------------------------------------------------------------------------
*/

async function shutdown(signal) {

  console.log(
    `${signal} received. Shutting down gracefully...`
  );

  shuttingDown = true;

  if (!server) {
    process.exit(0);
  }

  server.close(
    async () => {

      console.log(
        "HTTP server closed."
      );

      try {

        await mongoose.disconnect();

        console.log(
          "MongoDB connection closed."
        );

      } catch (error) {

        console.error(
          "MongoDB shutdown error:",
          error.message
        );
      }

      process.exit(0);
    }
  );

  setTimeout(
    () => {

      console.error(
        "Forced shutdown after timeout."
      );

      process.exit(1);
    },
    10000
  );
}

process.on(
  "SIGTERM",
  () => shutdown("SIGTERM")
);

process.on(
  "SIGINT",
  () => shutdown("SIGINT")
);

/*
|--------------------------------------------------------------------------
| Boot
|--------------------------------------------------------------------------
*/

startServer();