const express = require("express");

const protect = require("../middleware/auth");
const customerOnly = require("../middleware/customer");

const {
  getDashboard,
  getMyLease,
  getMyPayments,
  getMyMaintenance,
  createMyMaintenance,
  createMyPayment,
  getProfile,
  updateProfile,
  changePassword,
} = require("../controllers/customerController");

const router = express.Router();

// ============================================================
// CUSTOMER AUTHENTICATION
// ============================================================

router.use(protect);
router.use(customerOnly);

// ============================================================
// DASHBOARD
// ============================================================

router.get(
  "/dashboard",
  getDashboard
);

// ============================================================
// LEASE
// ============================================================

router.get(
  "/lease",
  getMyLease
);

// ============================================================
// PAYMENTS
// ============================================================

router.get(
  "/payments",
  getMyPayments
);

router.post(
  "/payments",
  createMyPayment
);

// ============================================================
// MAINTENANCE
// ============================================================

router.get(
  "/maintenance",
  getMyMaintenance
);

router.post(
  "/maintenance",
  createMyMaintenance
);

// ============================================================
// PROFILE
// ============================================================

router.get(
  "/profile",
  getProfile
);

router.put(
  "/profile",
  updateProfile
);

// ============================================================
// PASSWORD
// ============================================================

router.put(
  "/settings/password",
  changePassword
);

module.exports = router;
