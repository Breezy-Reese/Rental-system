const express = require("express");

const protect = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const {
  getTenants,
  getTenant,
  createTenant,
  updateTenant,
  deleteTenant,
} = require("../controllers/tenantController");

const router = express.Router();

router.use(protect);
router.use(adminOnly);

router.get("/", getTenants);
router.get("/:id", getTenant);
router.post("/", createTenant);
router.put("/:id", updateTenant);
router.delete("/:id", deleteTenant);

module.exports = router;
