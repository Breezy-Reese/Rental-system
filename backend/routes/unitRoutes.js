const express = require("express");

const protect = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const {
  getUnits,
  getUnitsByProperty,
  getUnit,
  createUnit,
  updateUnit,
  deleteUnit,
} = require("../controllers/unitController");

const router = express.Router();

router.use(protect);
router.use(adminOnly);

router.get("/", getUnits);
router.get("/property/:propertyId", getUnitsByProperty);
router.get("/:id", getUnit);
router.post("/", createUnit);
router.put("/:id", updateUnit);
router.delete("/:id", deleteUnit);

module.exports = router;
