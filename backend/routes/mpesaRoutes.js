const express = require("express");

const protect = require("../middleware/auth");
const customerOnly = require("../middleware/customer");

const {
  stkPush,
  stkCallback,
  checkStatus,
} = require("../controllers/mpesaController");

const router = express.Router();

// Customer-only: initiate payment.
router.post("/stk-push", protect, customerOnly, stkPush);

// Customer-only: poll status.
router.get("/status/:paymentId", protect, customerOnly, checkStatus);

// Public: Safaricom's callback. No auth - Safaricom won't send your JWT.
router.post("/callback", stkCallback);

module.exports = router;