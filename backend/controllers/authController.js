
const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const User = require("../models/User");

/**
 * Normalize email safely.
 */
const normalizeEmail = (email) => {
  if (typeof email !== "string") {
    return "";
  }

  return email.trim().toLowerCase();
};

/**
 * Create JWT token.
 */
const createToken = (user) => {
  const secret = process.env.JWT_SECRET;

  if (!secret || !secret.trim()) {
    throw new Error("JWT_SECRET is not configured");
  }

  return jwt.sign(
    {
      id: user._id.toString(),
      name: user.name,
      email: user.email,
      role: user.role,
    },
    secret,
    {
      expiresIn: process.env.JWT_EXPIRES_IN || "7d",
    }
  );
};

/**
 * Format user data returned to the frontend.
 */
const formatUser = (user) => ({
  id: user._id.toString(),
  name: user.name,
  email: user.email,
  phone: user.phone || "",
  role: user.role,
  status: user.status,
});

/**
 * LOGIN
 */
const login = async (req, res) => {
  try {
    const email = normalizeEmail(req.body?.email);
    const password = req.body?.password;

    if (!email || typeof password !== "string" || !password) {
      return res.status(400).json({
        success: false,
        message: "Email and password are required",
      });
    }

    // Find user by normalized email.
    const user = await User.findOne({
      email,
    });

    if (!user) {
      return res.status(401).json({
        success: false,
        message: "Invalid email or password.",
      });
    }

    // Reject inactive accounts.
    if (user.status && user.status !== "Active") {
      return res.status(403).json({
        success: false,
        message:
          "Your account is inactive. Please contact the administrator.",
      });
    }

    // Verify password.
    if (!user.password) {
      console.error("Login failed: user has no stored password hash.");

      return res.status(401).json({
        success: false,
        message: "Invalid email or password.",
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

    // Create authentication token.
    const token = createToken(user);
    const formattedUser = formatUser(user);

    // Redirect based on the stored database role.
    let redirect = "/customer/dashboard.php";

    if (user.role === "Administrator") {
      redirect = "/admin/dashboard.php";
    }

    console.log(`Successful login for ${user.email}`);

    return res.status(200).json({
      success: true,
      message: "Login successful",
      data: {
        token,
        user: formattedUser,
        redirect,
      },
    });
  } catch (error) {
    // Log the real error in Render, not sensitive credentials.
    console.error("Login error:", error.message);

    return res.status(500).json({
      success: false,
      message: "Server error during login",
    });
  }
};

/**
 * PUBLIC REGISTRATION
 *
 * All public registrations are Customers.
 * Never accept a role from the submitted form.
 */
const register = async (req, res) => {
  try {
    const name =
      typeof req.body?.name === "string"
        ? req.body.name.trim()
        : "";

    const email = normalizeEmail(req.body?.email);

    const phone =
      typeof req.body?.phone === "string"
        ? req.body.phone.trim()
        : "";

    const password = req.body?.password;
    const confirmPassword = req.body?.confirmPassword;

    if (
      !name ||
      !email ||
      typeof password !== "string" ||
      !password
    ) {
      return res.status(400).json({
        success: false,
        message: "Name, email and password are required",
      });
    }

    if (
      confirmPassword !== undefined &&
      password !== confirmPassword
    ) {
      return res.status(400).json({
        success: false,
        message: "Passwords do not match",
      });
    }

    if (password.length < 6) {
      return res.status(400).json({
        success: false,
        message: "Password must be at least 6 characters",
      });
    }

    // Check for an existing account.
    const existingUser = await User.findOne({
      email,
    });

    if (existingUser) {
      return res.status(409).json({
        success: false,
        message: "An account with this email already exists",
      });
    }

    // Hash password before storing it.
    const hashedPassword = await bcrypt.hash(password, 10);

    // Public registration always creates a Customer.
    const user = await User.create({
      name,
      email,
      phone,
      password: hashedPassword,
      role: "Customer",
      status: "Active",
    });

    const token = createToken(user);
    const formattedUser = formatUser(user);

    console.log(`New customer registered: ${user.email}`);

    return res.status(201).json({
      success: true,
      message: "Registration successful",
      data: {
        token,
        user: formattedUser,
        redirect: "/customer/dashboard.php",
      },
    });
  } catch (error) {
    console.error("Registration error:", error.message);

    if (error.code === 11000) {
      return res.status(409).json({
        success: false,
        message: "An account with this email already exists",
      });
    }

    return res.status(500).json({
      success: false,
      message: "Server error during registration",
    });
  }
};

/**
 * LOGOUT
 *
 * The PHP frontend clears the local session and token.
 */
const logout = async (req, res) => {
  return res.status(200).json({
    success: true,
    message: "Logout successful",
  });
};

/**
 * CURRENT USER
 */
const me = async (req, res) => {
  try {
    const user = await User.findById(req.user.id).select(
      "-password"
    );

    if (!user) {
      return res.status(404).json({
        success: false,
        message: "User not found",
      });
    }

    return res.status(200).json({
      success: true,
      data: {
        user: formatUser(user),
      },
    });
  } catch (error) {
    console.error("Get current user error:", error.message);

    return res.status(500).json({
      success: false,
      message: "Server error",
    });
  }
};

module.exports = {
  login,
  register,
  logout,
  me,
};