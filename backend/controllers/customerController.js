const bcrypt = require("bcryptjs");

const User = require("../models/User");
const Tenant = require("../models/Tenant");
const Lease = require("../models/Lease");
const Payment = require("../models/Payment");
const Maintenance = require("../models/Maintenance");
const Property = require("../models/Property");
const Notification = require("../models/Notification");

// ============================================================
// HELPER
// ============================================================

const getCustomerTenant = async (userId) => {
  return Tenant.findOne({ userId });
};

// ============================================================
// CUSTOMER DASHBOARD
// ============================================================

const getDashboard = async (req, res) => {
  try {
    const tenant = await getCustomerTenant(req.user.id);

    if (!tenant) {
      return res.json({
        success: true,
        data: {
          tenant: null,
          lease: null,
          payments: [],
          maintenance: [],
          notifications: 0,
        },
      });
    }

    const [
      lease,
      payments,
      maintenance,
      unreadNotifications,
    ] = await Promise.all([
      Lease.findOne({
        tenantId: tenant._id,
        status: "Active",
      })
        .populate(
          "propertyId",
          "propertyId name location address"
        )
        .populate(
          "unitId",
          "unitId unitNumber type rent status"
        ),

      Payment.find({
        tenantId: tenant._id,
      })
        .sort({
          paymentDate: -1,
          createdAt: -1,
        })
        .limit(5),

      Maintenance.find({
        tenantId: tenant._id,
      })
        .populate(
          "propertyId",
          "propertyId name location"
        )
        .populate(
          "unitId",
          "unitId unitNumber"
        )
        .sort({ createdAt: -1 })
        .limit(5),

      Notification.countDocuments({
        recipientRole: "Customer",
        recipientId: req.user.id,
        read: false,
      }),
    ]);

    res.json({
      success: true,
      data: {
        tenant,
        lease,
        payments,
        maintenance,
        unreadNotifications,
      },
    });
  } catch (error) {
    console.error(
      "Customer dashboard error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to retrieve customer dashboard",
    });
  }
};

// ============================================================
// MY LEASE
// ============================================================

const getMyLease = async (req, res) => {
  try {
    const tenant = await getCustomerTenant(req.user.id);

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message:
          "Customer tenant record not found",
      });
    }

    const lease = await Lease.findOne({
      tenantId: tenant._id,
      status: "Active",
    })
      .populate(
        "propertyId",
        "propertyId name location address description"
      )
      .populate(
        "unitId",
        "unitId unitNumber type rent status"
      );

    if (!lease) {
      return res.status(404).json({
        success: false,
        message: "No active lease found",
      });
    }

    res.json({
      success: true,
      data: lease,
    });
  } catch (error) {
    console.error(
      "Get my lease error:",
      error
    );

    res.status(500).json({
      success: false,
      message: "Failed to retrieve lease",
    });
  }
};

// ============================================================
// MY PAYMENTS
// ============================================================

const getMyPayments = async (req, res) => {
  try {
    const tenant = await getCustomerTenant(req.user.id);

    if (!tenant) {
      return res.json({
        success: true,
        count: 0,
        data: [],
      });
    }

    const payments = await Payment.find({
      tenantId: tenant._id,
    })
      .populate(
        "leaseId",
        "leaseId startDate endDate rent status"
      )
      .sort({
        paymentDate: -1,
        createdAt: -1,
      });

    const totalPaid = payments
      .filter(
        (payment) => payment.status === "Paid"
      )
      .reduce(
        (total, payment) =>
          total + Number(payment.amount),
        0
      );

    const totalPending = payments
      .filter(
        (payment) => payment.status === "Pending"
      )
      .reduce(
        (total, payment) =>
          total + Number(payment.amount),
        0
      );

    res.json({
      success: true,
      count: payments.length,
      summary: {
        totalPaid,
        totalPending,
      },
      data: payments,
    });
  } catch (error) {
    console.error(
      "Get my payments error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to retrieve payments",
    });
  }
};

// ============================================================
// MY MAINTENANCE
// ============================================================

const getMyMaintenance = async (req, res) => {
  try {
    const tenant = await getCustomerTenant(req.user.id);

    if (!tenant) {
      return res.json({
        success: true,
        count: 0,
        data: [],
      });
    }

    const requests = await Maintenance.find({
      tenantId: tenant._id,
    })
      .populate(
        "propertyId",
        "propertyId name location"
      )
      .populate(
        "unitId",
        "unitId unitNumber type status"
      )
      .sort({ createdAt: -1 });

    res.json({
      success: true,
      count: requests.length,
      data: requests,
    });
  } catch (error) {
    console.error(
      "Get my maintenance error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to retrieve maintenance requests",
    });
  }
};

// ============================================================
// SUBMIT MAINTENANCE REQUEST
// ============================================================

const createMyMaintenance = async (req, res) => {
  try {
    const {
      maintenanceId,
      issue,
      description,
      priority,
    } = req.body;

    if (!maintenanceId || !issue) {
      return res.status(400).json({
        success: false,
        message:
          "Maintenance ID and issue are required",
      });
    }

    const existing = await Maintenance.findOne({
      maintenanceId,
    });

    if (existing) {
      return res.status(409).json({
        success: false,
        message:
          "Maintenance ID already exists",
      });
    }

    const tenant = await Tenant.findOne({
      userId: req.user.id,
    });

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message:
          "Customer tenant record not found",
      });
    }

    if (!tenant.propertyId) {
      return res.status(400).json({
        success: false,
        message:
          "You are not currently assigned to a property",
      });
    }

    const property = await Property.findById(
      tenant.propertyId
    );

    if (!property) {
      return res.status(404).json({
        success: false,
        message:
          "Your assigned property was not found",
      });
    }

    const unitId = tenant.unitId || null;

    const request = await Maintenance.create({
      maintenanceId,
      tenantId: tenant._id,
      propertyId: tenant.propertyId,
      unitId,
      issue,
      description,
      priority: priority || "Medium",
      status: "Pending",
    });

    // Notify administrators.
    await Notification.create({
      recipientRole: "Administrator",
      recipientId: null,

      // Logged-in customer's User._id
      senderId: req.user.id,

      type: "maintenance_created",

      title: "New Maintenance Request",

      message:
        `${tenant.name} submitted a maintenance request: ${issue}.`,

      data: {
        maintenanceId: request._id,

        // Customer User._id
        customerId: req.user.id,

        // Tenant document ID
        tenantId: tenant._id,

        // Property
        propertyId: tenant.propertyId,

        // Unit
        unitId,

        // Request information
        priority: priority || "Medium",
        issue,
        description: description || null,
      },
    });

    const populatedRequest =
      await Maintenance.findById(
        request._id
      )
        .populate(
          "propertyId",
          "propertyId name location"
        )
        .populate(
          "unitId",
          "unitId unitNumber type status"
        );

    res.status(201).json({
      success: true,
      message:
        "Maintenance request submitted successfully",
      data: populatedRequest,
    });
  } catch (error) {
    console.error(
      "Create customer maintenance error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to submit maintenance request",
    });
  }
};

// ============================================================
// SUBMIT PAYMENT
// ============================================================

const createMyPayment = async (req, res) => {
  try {
    const {
      paymentId,
      amount,
      paymentMethod,
      reference,
      paymentDate,
      leaseId,
    } = req.body;

    // --------------------------------------------------------
    // VALIDATION
    // --------------------------------------------------------

    if (
      !paymentId ||
      amount === undefined ||
      !paymentMethod
    ) {
      return res.status(400).json({
        success: false,
        message:
          "Payment ID, amount and payment method are required",
      });
    }

    if (Number(amount) <= 0) {
      return res.status(400).json({
        success: false,
        message:
          "Payment amount must be greater than zero",
      });
    }

    // --------------------------------------------------------
    // CHECK DUPLICATE PAYMENT ID
    // --------------------------------------------------------

    const existingPayment =
      await Payment.findOne({
        paymentId,
      });

    if (existingPayment) {
      return res.status(409).json({
        success: false,
        message:
          "Payment ID already exists",
      });
    }

    // --------------------------------------------------------
    // GET CUSTOMER TENANT
    // --------------------------------------------------------

    const tenant = await Tenant.findOne({
      userId: req.user.id,
    });

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message:
          "Customer tenant record not found",
      });
    }

    // --------------------------------------------------------
    // GET CUSTOMER LEASE
    // --------------------------------------------------------

    let lease = null;

    if (leaseId) {
      lease = await Lease.findById(
        leaseId
      );

      if (!lease) {
        return res.status(404).json({
          success: false,
          message: "Lease not found",
        });
      }

      // Security check:
      // customer can only use their own lease.
      if (
        lease.tenantId.toString() !==
        tenant._id.toString()
      ) {
        return res.status(403).json({
          success: false,
          message:
            "You cannot submit a payment for another tenant's lease",
        });
      }
    } else {
      lease = await Lease.findOne({
        tenantId: tenant._id,
        status: "Active",
      }).sort({ createdAt: -1 });
    }

    if (!lease) {
      return res.status(400).json({
        success: false,
        message:
          "No active lease was found for your account",
      });
    }

    // --------------------------------------------------------
    // CREATE PAYMENT
    // --------------------------------------------------------

    const payment = await Payment.create({
      paymentId,

      tenantId: tenant._id,

      // Customer User._id
      customerId: req.user.id,

      leaseId: lease._id,

      amount: Number(amount),

      paymentMethod,

      reference: reference || "",

      // Customer payments always start pending.
      status: "Pending",

      paymentDate:
        paymentDate || new Date(),

      // Customer User._id
      submittedBy: req.user.id,
    });

    // --------------------------------------------------------
    // NOTIFY ADMINISTRATORS
    // --------------------------------------------------------

    await Notification.create({
      recipientRole: "Administrator",
      recipientId: null,

      // Customer who submitted the payment
      senderId: req.user.id,

      type: "payment_created",

      title: "New Payment Submitted",

      message:
        `${tenant.name} submitted a payment of KES ${Number(
          amount
        ).toLocaleString()}.`,

      data: {
        paymentId: payment._id,

        // Customer User._id
        customerId: req.user.id,

        // Tenant
        tenantId: tenant._id,

        // Lease
        leaseId: lease._id,

        amount: Number(amount),

        paymentMethod,

        reference: reference || "",

        status: "Pending",

        paymentDate:
          payment.paymentDate,
      },
    });

    // --------------------------------------------------------
    // RETURN PAYMENT
    // --------------------------------------------------------

    const populatedPayment =
      await Payment.findById(
        payment._id
      )
        .populate(
          "tenantId",
          "tenantId name email phone userId"
        )
        .populate(
          "customerId",
          "name email phone"
        )
        .populate(
          "leaseId",
          "leaseId startDate endDate rent status"
        );

    res.status(201).json({
      success: true,
      message:
        "Payment submitted successfully and is awaiting verification",
      data: populatedPayment,
    });
  } catch (error) {
    console.error(
      "Create customer payment error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to submit payment",
    });
  }
};

// ============================================================
// CUSTOMER PROFILE
// ============================================================

const getProfile = async (req, res) => {
  try {
    const user = await User.findById(
      req.user.id
    ).select("-password");

    if (!user) {
      return res.status(404).json({
        success: false,
        message:
          "Customer account not found",
      });
    }

    const tenant =
      await Tenant.findOne({
        userId: req.user.id,
      })
        .populate(
          "propertyId",
          "propertyId name location address"
        )
        .populate(
          "unitId",
          "unitId unitNumber type rent status"
        );

    res.json({
      success: true,
      data: {
        user,
        tenant,
      },
    });
  } catch (error) {
    console.error(
      "Get customer profile error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to retrieve profile",
    });
  }
};

// ============================================================
// UPDATE CUSTOMER PROFILE
// ============================================================

const updateProfile = async (req, res) => {
  try {
    const {
      name,
      email,
      phone,
    } = req.body;

    if (!name && !email && !phone) {
      return res.status(400).json({
        success: false,
        message:
          "Provide at least one field to update",
      });
    }

    const user = await User.findById(
      req.user.id
    );

    if (!user) {
      return res.status(404).json({
        success: false,
        message:
          "Customer account not found",
      });
    }

    if (
      email &&
      email.toLowerCase() !== user.email
    ) {
      const existingUser =
        await User.findOne({
          email: email.toLowerCase(),
          _id: {
            $ne: user._id,
          },
        });

      if (existingUser) {
        return res.status(409).json({
          success: false,
          message:
            "Email is already in use",
        });
      }

      user.email =
        email.toLowerCase();
    }

    if (name !== undefined) {
      user.name = name;
    }

    if (phone !== undefined) {
      user.phone = phone;
    }

    await user.save();

    await Tenant.findOneAndUpdate(
      {
        userId: user._id,
      },
      {
        name: user.name,
        email: user.email,
        phone: user.phone,
      }
    );

    const updatedUser =
      await User.findById(
        user._id
      ).select("-password");

    res.json({
      success: true,
      message:
        "Profile updated successfully",
      data: updatedUser,
    });
  } catch (error) {
    console.error(
      "Update customer profile error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to update profile",
    });
  }
};

// ============================================================
// CHANGE PASSWORD
// ============================================================

const changePassword = async (req, res) => {
  try {
    const {
      currentPassword,
      newPassword,
    } = req.body;

    if (
      !currentPassword ||
      !newPassword
    ) {
      return res.status(400).json({
        success: false,
        message:
          "Current password and new password are required",
      });
    }

    if (newPassword.length < 6) {
      return res.status(400).json({
        success: false,
        message:
          "New password must contain at least 6 characters",
      });
    }

    const user = await User.findById(
      req.user.id
    );

    if (!user) {
      return res.status(404).json({
        success: false,
        message:
          "Customer account not found",
      });
    }

    const passwordMatch =
      await bcrypt.compare(
        currentPassword,
        user.password
      );

    if (!passwordMatch) {
      return res.status(401).json({
        success: false,
        message:
          "Current password is incorrect",
      });
    }

    user.password =
      await bcrypt.hash(
        newPassword,
        12
      );

    await user.save();

    res.json({
      success: true,
      message:
        "Password changed successfully",
    });
  } catch (error) {
    console.error(
      "Change password error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to change password",
    });
  }
};

// ============================================================
// EXPORTS
// ============================================================

module.exports = {
  getDashboard,
  getMyLease,
  getMyPayments,
  getMyMaintenance,
  createMyMaintenance,
  createMyPayment,
  getProfile,
  updateProfile,
  changePassword,
};

