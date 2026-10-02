const express = require("express");

const protect = require("../middleware/auth");

const {
  login,
  register,
  me,
  logout,
} = require("../controllers/authController");

const router = express.Router();

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Login
router.post("/login", login);

// Customer registration
router.post("/register", register);

// Logout
router.post("/logout", logout);

// Current authenticated user
router.get("/me", protect, me);

module.exports = router;
