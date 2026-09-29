const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const User = require("../models/User");

/**
 * ============================================================
 * CREATE JWT TOKEN
 * ============================================================
 */
const createToken = (user) => {
  return jwt.sign(
    {
      id: user._id,
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

/**
 * ============================================================
 * FORMAT USER
 * ============================================================
 */
const formatUser = (user) => {
  return {
    id: user._id,
    name: user.name,
    email: user.email,
    phone: user.phone,
    role: user.role,
    status: user.status,
  };
};

/**
 * ============================================================
 * LOGIN
 * POST /api/auth/login
 * ============================================================
 */
const login = async (req, res) => {
  try {
    const email = req.body.email?.trim().toLowerCase();
    const password = req.body.password;

    if (!email || !password) {
      return res.status(400).json({
        success: false,
        message: "Email and password are required.",
      });
    }

    const user = await User.findOne({ email });

    if (!user) {
      return res.status(401).json({
        success: false,
        message: "Invalid email or password.",
      });
    }

    if (user.status && user.status !== "Active") {
      return res.status(403).json({
        success: false,
        message:
          "Your account is inactive. Please contact the administrator.",
      });
    }

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

    const token = createToken(user);
    const formattedUser = formatUser(user);

    let redirect = "/customer/dashboard.php";

    if (
      user.role === "Administrator" ||
      user.role === "Admin" ||
      user.role === "admin"
    ) {
      redirect = "/admin/dashboard.php";
    }

    return res.status(200).json({
      success: true,
      message: "Login successful.",
      data: {
        token,
        user: formattedUser,
        redirect,
      },
    });
  } catch (error) {
    console.error("LOGIN ERROR:", error);

    return res.status(500).json({
      success: false,
      message: "Server error during login.",
    });
  }
};

/**
 * ============================================================
 * REGISTER
 * POST /api/auth/register
 *
 * IMPORTANT:
 * Public registration ALWAYS creates a Customer.
 *
 * Administrator accounts must be created separately.
 * ============================================================
 */
const register = async (req, res) => {
  try {
    const name = req.body.name?.trim();
    const email = req.body.email?.trim().toLowerCase();
    const password = req.body.password;
    const phone = req.body.phone?.trim() || "";

    /**
     * ----------------------------------------------------------
     * VALIDATE BASIC FIELDS
     * ----------------------------------------------------------
     */
    if (!name || !email || !password) {
      return res.status(400).json({
        success: false,
        message: "Name, email and password are required.",
      });
    }

    /**
     * ----------------------------------------------------------
     * VALIDATE EMAIL
     * ----------------------------------------------------------
     */
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(email)) {
      return res.status(400).json({
        success: false,
        message: "Please provide a valid email address.",
      });
    }

    /**
     * ----------------------------------------------------------
     * VALIDATE PASSWORD
     * ----------------------------------------------------------
     */
    if (password.length < 6) {
      return res.status(400).json({
        success: false,
        message: "Password must be at least 6 characters long.",
      });
    }

    /**
     * ----------------------------------------------------------
     * CHECK EXISTING EMAIL
     * ----------------------------------------------------------
     */
    const existingUser = await User.findOne({ email });

    if (existingUser) {
      return res.status(409).json({
        success: false,
        message: "An account with this email already exists.",
      });
    }

    /**
     * ----------------------------------------------------------
     * HASH PASSWORD
     * ----------------------------------------------------------
     */
    const hashedPassword = await bcrypt.hash(password, 12);

    /**
     * ----------------------------------------------------------
     * CREATE CUSTOMER
     *
     * DO NOT accept role from the registration form.
     * Every public registration is a Customer.
     * ----------------------------------------------------------
     */
    const user = await User.create({
      name,
      email,
      password: hashedPassword,
      phone,
      role: "Customer",
      status: "Active",
    });

    /**
     * ----------------------------------------------------------
     * AUTOMATIC LOGIN AFTER REGISTRATION
     * ----------------------------------------------------------
     */
    const token = createToken(user);
    const formattedUser = formatUser(user);

    return res.status(201).json({
      success: true,
      message: "Customer account created successfully.",
      data: {
        token,
        user: formattedUser,
        redirect: "/customer/dashboard.php",
      },
    });
  } catch (error) {
    console.error("REGISTER ERROR:", error);

    if (error.name === "ValidationError") {
      const messages = Object.values(error.errors).map(
        (item) => item.message
      );

      return res.status(400).json({
        success: false,
        message: messages.join(" "),
      });
    }

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

/**
 * ============================================================
 * CURRENT USER
 * GET /api/auth/me
 * ============================================================
 */
const me = async (req, res) => {
  try {
    const user = await User.findById(req.user.id).select("-password");

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
    console.error("ME ERROR:", error);

    return res.status(500).json({
      success: false,
      message: "Server error.",
    });
  }
};

/**
 * ============================================================
 * LOGOUT
 * POST /api/auth/logout
 * ============================================================
 */
const logout = async (req, res) => {
  return res.status(200).json({
    success: true,
    message: "Logged out successfully.",
  });
};

/**
 * ============================================================
 * EXPORTS
 * ============================================================
 */
module.exports = {
  login,
  register,
  me,
  logout,
};