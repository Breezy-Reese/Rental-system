const Notification = require("../models/Notification");
const User = require("../models/User");
const Tenant = require("../models/Tenant");

const getNotifications = async (req, res) => {
  try {
    const role = req.user.role;

    const filter =
      role === "Administrator"
        ? {
            recipientRole: "Administrator",
            $or: [
              { recipientId: null },
              { recipientId: req.user.id },
            ],
          }
        : {
            recipientRole: "Customer",
            recipientId: req.user.id,
          };

    const notifications = await Notification.find(filter)
      .populate("senderId", "name email phone role")
      .populate("recipientId", "name email phone role")
      .sort({ createdAt: -1 });

    const unreadCount = await Notification.countDocuments({
      ...filter,
      read: false,
    });

    res.json({
      success: true,
      count: notifications.length,
      unreadCount,
      data: notifications,
    });
  } catch (error) {
    console.error("Get notifications error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve notifications",
    });
  }
};


const getNotification = async (req, res) => {
  try {
    const notification = await Notification.findById(
      req.params.id
    )
      .populate("senderId", "name email phone role")
      .populate("recipientId", "name email phone role");

    if (!notification) {
      return res.status(404).json({
        success: false,
        message: "Notification not found",
      });
    }

    const canAccess =
      notification.recipientRole === req.user.role &&
      (
        notification.recipientId === null ||
        notification.recipientId?.toString() ===
          req.user.id.toString()
      );

    if (!canAccess) {
      return res.status(403).json({
        success: false,
        message: "You cannot access this notification",
      });
    }

    res.json({
      success: true,
      data: notification,
    });
  } catch (error) {
    console.error("Get notification error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve notification",
    });
  }
};


const markAsRead = async (req, res) => {
  try {
    const notification = await Notification.findById(
      req.params.id
    );

    if (!notification) {
      return res.status(404).json({
        success: false,
        message: "Notification not found",
      });
    }

    const canAccess =
      notification.recipientRole === req.user.role &&
      (
        notification.recipientId === null ||
        notification.recipientId?.toString() ===
          req.user.id.toString()
      );

    if (!canAccess) {
      return res.status(403).json({
        success: false,
        message: "You cannot modify this notification",
      });
    }

    notification.read = true;

    await notification.save();

    res.json({
      success: true,
      message: "Notification marked as read",
      data: notification,
    });
  } catch (error) {
    console.error("Mark notification read error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to update notification",
    });
  }
};


const markAllAsRead = async (req, res) => {
  try {
    const role = req.user.role;

    const filter =
      role === "Administrator"
        ? {
            recipientRole: "Administrator",
            $or: [
              { recipientId: null },
              { recipientId: req.user.id },
            ],
            read: false,
          }
        : {
            recipientRole: "Customer",
            recipientId: req.user.id,
            read: false,
          };

    const result = await Notification.updateMany(
      filter,
      { $set: { read: true } }
    );

    res.json({
      success: true,
      message: "All notifications marked as read",
      updated: result.modifiedCount,
    });
  } catch (error) {
    console.error("Mark all notifications read error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to update notifications",
    });
  }
};


/*
|--------------------------------------------------------------------------
| Resolve customer from notification
|--------------------------------------------------------------------------
|
| Notifications created by payments, maintenance, leases, etc. may
| contain tenantId/customerId/userId inside the data object.
|
*/

const resolveCustomer = async (notification) => {
  const data = notification.data || {};

  // Direct customer/user ID
  const directUserId =
    data.userId ||
    data.customerId ||
    data.recipientId;

  if (directUserId) {
    const user = await User.findById(directUserId);

    if (
      user &&
      user.role === "Customer"
    ) {
      return user;
    }
  }

  // Tenant ID
  const tenantId =
    data.tenantId ||
    data.tenant;

  if (tenantId) {
    const tenant = await Tenant.findById(tenantId)
      .populate("userId");

    if (
      tenant &&
      tenant.userId &&
      tenant.userId.role === "Customer"
    ) {
      return tenant.userId;
    }
  }

  return null;
};


/*
|--------------------------------------------------------------------------
| Admin reply / feedback
|--------------------------------------------------------------------------
*/

const replyToNotification = async (req, res) => {
  try {
    // Only administrators can reply
    if (req.user.role !== "Administrator") {
      return res.status(403).json({
        success: false,
        message: "Only administrators can send notification feedback",
      });
    }

    const { message } = req.body;

    if (!message || !message.trim()) {
      return res.status(400).json({
        success: false,
        message: "Feedback message is required",
      });
    }

    if (message.trim().length > 2000) {
      return res.status(400).json({
        success: false,
        message: "Feedback message cannot exceed 2000 characters",
      });
    }

    const originalNotification =
      await Notification.findById(req.params.id);

    if (!originalNotification) {
      return res.status(404).json({
        success: false,
        message: "Original notification not found",
      });
    }

    // An administrator may only reply to administrator notifications
    if (
      originalNotification.recipientRole !==
      "Administrator"
    ) {
      return res.status(403).json({
        success: false,
        message: "This notification cannot receive an administrator reply",
      });
    }

    const customer = await resolveCustomer(
      originalNotification
    );

    if (!customer) {
      return res.status(400).json({
        success: false,
        message:
          "The customer associated with this notification could not be found.",
      });
    }

    const reply = await Notification.create({
      recipientRole: "Customer",
      recipientId: customer._id,
      senderId: req.user.id,

      type: "admin_feedback",

      title: "Admin Feedback",

      message: message.trim(),

      data: {
        originalNotificationId:
          originalNotification._id,

        administratorId: req.user.id,

        customerId: customer._id,
      },

      replyTo: originalNotification._id,

      parentNotificationId:
        originalNotification.parentNotificationId ||
        originalNotification._id,

      read: false,
    });

    // Mark original notification as read
    originalNotification.read = true;
    await originalNotification.save();

    const populatedReply =
      await Notification.findById(reply._id)
        .populate("senderId", "name email phone role")
        .populate("recipientId", "name email phone role");

    res.status(201).json({
      success: true,
      message: "Feedback sent successfully",
      data: populatedReply,
    });
  } catch (error) {
    console.error("Reply notification error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to send feedback",
    });
  }
};


/*
|--------------------------------------------------------------------------
| Delete notification
|--------------------------------------------------------------------------
*/

const deleteNotification = async (req, res) => {
  try {
    const notification = await Notification.findById(
      req.params.id
    );

    if (!notification) {
      return res.status(404).json({
        success: false,
        message: "Notification not found",
      });
    }

    const canAccess =
      notification.recipientRole === req.user.role &&
      (
        notification.recipientId === null ||
        notification.recipientId?.toString() ===
          req.user.id.toString()
      );

    if (!canAccess) {
      return res.status(403).json({
        success: false,
        message: "You cannot delete this notification",
      });
    }

    await Notification.findByIdAndDelete(req.params.id);

    res.json({
      success: true,
      message: "Notification deleted successfully",
    });
  } catch (error) {
    console.error("Delete notification error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to delete notification",
    });
  }
};


module.exports = {
  getNotifications,
  getNotification,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  replyToNotification,
};