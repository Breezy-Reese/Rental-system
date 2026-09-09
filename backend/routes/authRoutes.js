const express = require("express");

const protect = require("../middleware/auth");

const {
  login,
  register,
  me,
  logout,
} = require("../controllers/authController");

const router = express.Router();

router.post("/login", login);

router.post("/register", register);

router.post("/logout", logout);

router.get("/me", protect, me);

module.exports = router;