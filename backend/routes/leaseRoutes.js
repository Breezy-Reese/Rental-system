const express = require("express");

const protect = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const {
  getLeases,
  getLease,
  createLease,
  updateLease,
  deleteLease,
} = require("../controllers/leaseController");

const router = express.Router();

router.use(protect);
router.use(adminOnly);

router.get("/", getLeases);
router.get("/:id", getLease);
router.post("/", createLease);
router.put("/:id", updateLease);
router.delete("/:id", deleteLease);

module.exports = router;
