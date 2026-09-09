const express = require("express");

const protect = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const {
  getProperties,
  getProperty,
  createProperty,
  updateProperty,
  deleteProperty,
} = require("../controllers/propertyController");

const router = express.Router();

router.use(protect);
router.use(adminOnly);

router.get("/", getProperties);
router.get("/:id", getProperty);
router.post("/", createProperty);
router.put("/:id", updateProperty);
router.delete("/:id", deleteProperty);

module.exports = router;
