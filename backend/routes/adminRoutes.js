const express = require("express");

const protect = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const { getDashboard } = require("../controllers/dashboardController");

const router = express.Router();

router.use(protect);
router.use(adminOnly);

router.get("/dashboard", getDashboard);

module.exports = router;
