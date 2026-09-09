const express = require("express");

const protect = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const {
  getPayments,
  getPayment,
  createPayment,
  updatePayment,
  deletePayment,
} = require("../controllers/paymentController");

const router = express.Router();

router.use(protect);
router.use(adminOnly);

router.get("/", getPayments);
router.get("/:id", getPayment);
router.post("/", createPayment);
router.put("/:id", updatePayment);
router.delete("/:id", deletePayment);

module.exports = router;
