const express = require("express");

const protect = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const {
  getDashboard,
  getCustomers,
} = require("../controllers/dashboardController");

const router = express.Router();

// ============================================================
// ADMIN AUTHORIZATION
// ============================================================

router.use(protect);
router.use(adminOnly);

// ============================================================
// ADMIN DASHBOARD
// ============================================================

router.get(
  "/dashboard",
  getDashboard
);

// ============================================================
// ADMIN CUSTOMERS
// ============================================================

router.get(
  "/customers",
  getCustomers
);

module.exports = router;