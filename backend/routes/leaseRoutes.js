const express = require("express");

const protect = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const {
  getLeases,
  getLease,
  createLease,
  assignCustomerToUnit,
  updateLease,
  deleteLease,
} = require("../controllers/leaseController");

const router = express.Router();

// ============================================================
// ADMIN AUTHORIZATION
// ============================================================

router.use(protect);
router.use(adminOnly);

// ============================================================
// LEASES
// ============================================================

router.get(
  "/",
  getLeases
);

// ============================================================
// ASSIGN CUSTOMER TO RENTAL UNIT
// ============================================================
//
// Must come before /:id
//
// ============================================================

router.post(
  "/assign-customer",
  assignCustomerToUnit
);

router.get(
  "/:id",
  getLease
);

router.post(
  "/",
  createLease
);

router.put(
  "/:id",
  updateLease
);

router.delete(
  "/:id",
  deleteLease
);

module.exports = router;