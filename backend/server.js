require("dotenv").config();

const express = require("express");
const cors = require("cors");
const helmet = require("helmet");
const rateLimit = require("express-rate-limit");

const connectDB = require("./config/db");

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

const app = express();

const PORT = process.env.PORT || 5000;

// ============================================================
// DATABASE
// ============================================================

connectDB();

// ============================================================
// SECURITY
// ============================================================

app.use(helmet());

app.use(
  cors({
    origin: process.env.CLIENT_URL || "http://localhost:8000",
    credentials: true,
  })
);

// ============================================================
// BODY PARSER
// ============================================================

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ============================================================
// RATE LIMIT
// ============================================================

const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 200,
  standardHeaders: true,
  legacyHeaders: false,
});

app.use("/api", limiter);

// ============================================================
// HEALTH CHECK
// ============================================================

app.get("/api/health", (req, res) => {
  res.status(200).json({
    success: true,
    message: "PropertyPro API is running",
  });
});

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
// 404
// ============================================================

app.use((req, res) => {
  res.status(404).json({
    success: false,
    message: "API route not found",
    path: req.originalUrl,
  });
});

// ============================================================
// ERROR HANDLER
// ============================================================

app.use((err, req, res, next) => {
  console.error("Unhandled server error:", err);

  res.status(err.status || 500).json({
    success: false,
    message: err.message || "Internal server error",
  });
});

// ============================================================
// START SERVER
// ============================================================

app.listen(PORT, () => {
  console.log(
    `PropertyPro API running on http://localhost:${PORT}`
  );
});