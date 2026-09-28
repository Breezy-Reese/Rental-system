const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const User = require("../models/User");

// ============================================================
// CREATE JWT TOKEN
// ============================================================

const createToken = (user) => {
  if (!process.env.JWT_SECRET) {
    throw new Error("JWT_SECRET is not configured.");
  }

  return jwt.sign(
    {
      id: user._id.toString(),
      name: user.name,
      email: user.email,
      role: user.role,
    },
    process.env.JWT_SECRET,
    {
      expiresIn: process.env.JWT_EXPIRES_IN || "7d",
    }
  );
};

// ============================================================
// FORMAT USER
// ============================================================

const formatUser = (user) => ({
  id: user._id,
  name: user.name,
  email: user.email,
  phone: user.phone || "",
  role: user.role,
  status: user.status,
});

// ============================================================
// LOGIN
// POST /api/auth/login
// ============================================================

const login = async (req, res) => {
  try {
    const email =
      typeof req.body.email === "string"
        ? req.body.email.trim().toLowerCase()
        : "";

    const password =
      typeof req.body.password === "string"
        ? req.body.password
        : "";

    // ----------------------------------------------------------
    // Validate input
    // ----------------------------------------------------------

    if (!email || !password) {
      return res.status(400).json({
        success: false,
        message: "Email and password are required.",
      });
    }

    // ----------------------------------------------------------
    // Find user
    // ----------------------------------------------------------

    const user = await User.findOne({
      email: email,
    });

    if (!user) {
      return res.status(401).json({
        success: false,
        message: "Invalid email or password.",
      });
    }

    // ----------------------------------------------------------
    // Check account status
    // ----------------------------------------------------------

    if (user.status !== "Active") {
      return res.status(403).json({
        success: false,
        message: "Your account is inactive. Please contact the administrator.",
      });
    }

    // ----------------------------------------------------------
    // Check password
    // ----------------------------------------------------------

    const passwordMatch = await bcrypt.compare(
      password,
      user.password
    );

    if (!passwordMatch) {
      return res.status(401).json({
        success: false,
        message: "Invalid email or password.",
      });
    }

    // ----------------------------------------------------------
    // Create token
    // ----------------------------------------------------------

    const token = createToken(user);

    // ----------------------------------------------------------
    // Login successful
    // ----------------------------------------------------------

    return res.status(200).json({
      success: true,
      message: "Login successful.",
      data: {
        token,
        user: formatUser(user),

        redirect:
          user.role === "Administrator"
            ? "/admin/dashboard.php"
            : "/customer/dashboard.php",
      },
    });
  } catch (error) {
    console.error("Login error:", error);

    return res.status(500).json({
      success: false,
      message: "Server error during login.",
    });
  }
};

// ============================================================
// REGISTER
// POST /api/auth/register
// ============================================================

const register = async (req, res) => {
  try {
    const name =
      typeof req.body.name === "string"
        ? req.body.name.trim()
        : "";

    const email =
      typeof req.body.email === "string"
        ? req.body.email.trim().toLowerCase()
        : "";

    const password =
      typeof req.body.password === "string"
        ? req.body.password
        : "";

    const phone =
      typeof req.body.phone === "string"
        ? req.body.phone.trim()
        : "";

    // ----------------------------------------------------------
    // Validate required fields
    // ----------------------------------------------------------

    if (!name || !email || !password) {
      return res.status(400).json({
        success: false,
        message: "Name, email and password are required.",
      });
    }

    // ----------------------------------------------------------
    // Validate password
    // ----------------------------------------------------------

    if (password.length < 6) {
      return res.status(422).json({
        success: false,
        message: "Password must contain at least 6 characters.",
      });
    }

    // ----------------------------------------------------------
    // Check email
    // ----------------------------------------------------------

    const existingUser = await User.findOne({
      email: email,
    });

    if (existingUser) {
      return res.status(409).json({
        success: false,
        message: "An account with this email already exists.",
      });
    }

    // ----------------------------------------------------------
    // Hash password
    // ----------------------------------------------------------

    const hashedPassword = await bcrypt.hash(
      password,
      12
    );

    // ----------------------------------------------------------
    // Create customer account
    // ----------------------------------------------------------

    const user = await User.create({
      name,
      email,
      password: hashedPassword,
      phone,
      role: "Customer",
      status: "Active",
    });

    // ----------------------------------------------------------
    // Create login token
    // ----------------------------------------------------------

    const token = createToken(user);

    // ----------------------------------------------------------
    // Return account
    // ----------------------------------------------------------

    return res.status(201).json({
      success: true,
      message: "Registration successful.",
      data: {
        token,
        user: formatUser(user),
        redirect: "/customer/dashboard.php",
      },
    });
  } catch (error) {
    console.error("Registration error:", error);

    // Handle duplicate email race condition
    if (error.code === 11000) {
      return res.status(409).json({
        success: false,
        message: "An account with this email already exists.",
      });
    }

    return res.status(500).json({
      success: false,
      message: "Server error during registration.",
    });
  }
};

// ============================================================
// CURRENT USER
// GET /api/auth/me
// ============================================================

const me = async (req, res) => {
  try {
    const user = await User.findById(req.user.id)
      .select("-password");

    if (!user) {
      return res.status(404).json({
        success: false,
        message: "User not found.",
      });
    }

    return res.status(200).json({
      success: true,
      data: {
        user: formatUser(user),
      },
    });
  } catch (error) {
    console.error("Get current user error:", error);

    return res.status(500).json({
      success: false,
      message: "Server error.",
    });
  }
};

// ============================================================
// LOGOUT
// POST /api/auth/logout
// ============================================================

const logout = async (req, res) => {
  return res.status(200).json({
    success: true,
    message: "Logout successful.",
  });
};

// ============================================================
// EXPORTS
// ============================================================

module.exports = {
  login,
  register,
  me,
  logout,
};