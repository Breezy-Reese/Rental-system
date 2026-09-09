const express = require("express");

const protect = require("../middleware/auth");
const customerOnly = require("../middleware/customer");

const {
  getDashboard,
  getMyLease,
  getMyPayments,
  getMyMaintenance,
  createMyMaintenance,
  getProfile,
  updateProfile,
  changePassword,
} = require("../controllers/customerController");

const router = express.Router();

router.use(protect);
router.use(customerOnly);

router.get("/dashboard", getDashboard);

router.get("/lease", getMyLease);

router.get("/payments", getMyPayments);

router.get("/maintenance", getMyMaintenance);
router.post("/maintenance", createMyMaintenance);

router.get("/profile", getProfile);
router.put("/profile", updateProfile);

router.put("/settings/password", changePassword);

module.exports = router;
