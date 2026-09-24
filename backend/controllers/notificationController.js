const Notification = require("../models/Notification");
const User = require("../models/User");
const Tenant = require("../models/Tenant");

/*
|--------------------------------------------------------------------------
| Resolve customer connected to a notification
|--------------------------------------------------------------------------
*/

const resolveCustomer = async (notification) => {
  try {
    const data = notification.data || {};

    /*
    | Direct customer/user ID
    */
    const directUserId =
      data.userId ||
      data.customerId ||
      data.recipientId;

    if (directUserId) {
      const user = await User.findById(directUserId).select(
        "name email phone role status"
      );

      if (user && user.role === "Customer") {
        return user;
      }
    }

    /*
    | Customer connected through Tenant
    */
    const tenantId =
      data.tenantId ||
      data.tenant;

    if (tenantId) {
      const tenant = await Tenant.findById(tenantId)
        .populate(
          "userId",
          "name email phone role status"
        );

      if (
        tenant &&
        tenant.userId &&
        tenant.userId.role === "Customer"
      ) {
        return tenant.userId;
      }
    }

    return null;
  } catch (error) {
    console.error("Resolve customer error:", error);
    return null;
  }
};


/*
|--------------------------------------------------------------------------
| Get all notifications
|--------------------------------------------------------------------------
*/

const getNotifications = async (req, res) => {
  try {
    const role = req.user.role;

    console.log(
      "DEBUG getNotifications: req.user.id=",
      req.user.id,
      "role=",
      role
    );

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

    console.log(
      "DEBUG getNotifications filter:",
      JSON.stringify(filter)
    );

    const notifications = await Notification.find(filter)
      .populate(
        "senderId",
        "name email phone role"
      )
      .populate(
        "recipientId",
        "name email phone role"
      )
      .sort({ createdAt: -1 });

    console.log(
      "DEBUG getNotifications found:",
      notifications.length,
      "docs. Raw recipientIds in DB for Customer role:",
    );

    const allCustomerNotifs = await Notification.find({
      recipientRole: "Customer",
    }).select("recipientId title createdAt");

    console.log(JSON.stringify(allCustomerNotifs, null, 2));

    /*
    | Add customer information for administrators
    */
    const result = [];

    for (const notification of notifications) {
      const item = notification.toObject();

      if (role === "Administrator") {
        const customer =
          await resolveCustomer(notification);

        item.customer = customer;
      }

      result.push(item);
    }

    const unreadCount =
      await Notification.countDocuments({
        ...filter,
        read: false,
      });

    res.json({
      success: true,
      count: result.length,
      unreadCount,
      data: result,
    });
  } catch (error) {
    console.error(
      "Get notifications error:",
      error
    );

    res.status(500).json({
      success: false,
      message: "Failed to retrieve notifications",
    });
  }
};


/*
|--------------------------------------------------------------------------
| Get one notification
|--------------------------------------------------------------------------
*/

const getNotification = async (req, res) => {
  try {
    const notification =
      await Notification.findById(req.params.id)
        .populate(
          "senderId",
          "name email phone role"
        )
        .populate(
          "recipientId",
          "name email phone role"
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
        message:
          "You cannot access this notification",
      });
    }

    const result = notification.toObject();

    /*
    | Administrators need customer information
    */
    if (req.user.role === "Administrator") {
      result.customer =
        await resolveCustomer(notification);
    }

    res.json({
      success: true,
      data: result,
    });
  } catch (error) {
    console.error(
      "Get notification error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to retrieve notification",
    });
  }
};


/*
|--------------------------------------------------------------------------
| Mark one notification as read
|--------------------------------------------------------------------------
*/

const markAsRead = async (req, res) => {
  try {
    const notification =
      await Notification.findById(req.params.id);

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
        message:
          "You cannot modify this notification",
      });
    }

    notification.read = true;

    await notification.save();

    res.json({
      success: true,
      message:
        "Notification marked as read",
      data: notification,
    });
  } catch (error) {
    console.error(
      "Mark notification read error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to update notification",
    });
  }
};


/*
|--------------------------------------------------------------------------
| Mark all notifications as read
|--------------------------------------------------------------------------
*/

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

    const result =
      await Notification.updateMany(
        filter,
        { $set: { read: true } }
      );

    res.json({
      success: true,
      message:
        "All notifications marked as read",
      updated: result.modifiedCount,
    });
  } catch (error) {
    console.error(
      "Mark all notifications read error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to update notifications",
    });
  }
};


/*
|--------------------------------------------------------------------------
| Admin sends feedback to customer
|--------------------------------------------------------------------------
*/

const replyToNotification = async (
  req,
  res
) => {
  try {
    /*
    | Only administrators can reply
    */
    if (req.user.role !== "Administrator") {
      return res.status(403).json({
        success: false,
        message:
          "Only administrators can send feedback",
      });
    }

    const message =
      typeof req.body.message === "string"
        ? req.body.message.trim()
        : "";

    if (!message) {
      return res.status(400).json({
        success: false,
        message:
          "Feedback message is required",
      });
    }

    if (message.length > 2000) {
      return res.status(400).json({
        success: false,
        message:
          "Feedback message cannot exceed 2000 characters",
      });
    }

    /*
    | Find original notification
    */
    const originalNotification =
      await Notification.findById(
        req.params.id
      );

    if (!originalNotification) {
      return res.status(404).json({
        success: false,
        message:
          "Original notification not found",
      });
    }

    /*
    | Only notifications received by administrators
    | can be answered by administrators.
    */
    if (
      originalNotification.recipientRole !==
      "Administrator"
    ) {
      return res.status(403).json({
        success: false,
        message:
          "This notification cannot receive administrator feedback",
      });
    }

    /*
    | Find customer
    */
    const customer =
      await resolveCustomer(
        originalNotification
      );

    if (!customer) {
      return res.status(400).json({
        success: false,
        message:
          "The customer associated with this notification could not be found.",
      });
    }

    /*
    | Create customer notification
    */
    const reply =
      await Notification.create({
        recipientRole: "Customer",

        recipientId: customer._id,

        senderId: req.user.id,

        type: "admin_feedback",

        title: "Admin Feedback",

        message,

        data: {
          originalNotificationId:
            originalNotification._id,

          administratorId:
            req.user.id,

          customerId:
            customer._id,

          tenantId:
            originalNotification.data?.tenantId ||
            null,
        },

        replyTo:
          originalNotification._id,

        parentNotificationId:
          originalNotification.parentNotificationId ||
          originalNotification._id,

        read: false,
      });

    /*
    | Original notification becomes read
    */
    originalNotification.read = true;

    await originalNotification.save();

    /*
    | Populate reply
    */
    const populatedReply =
      await Notification.findById(
        reply._id
      )
        .populate(
          "senderId",
          "name email phone role"
        )
        .populate(
          "recipientId",
          "name email phone role"
        );

    res.status(201).json({
      success: true,
      message:
        "Feedback sent successfully",
      data: populatedReply,
    });
  } catch (error) {
    console.error(
      "Reply notification error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to send feedback",
    });
  }
};


/*
|--------------------------------------------------------------------------
| Delete notification
|--------------------------------------------------------------------------
*/

const deleteNotification = async (
  req,
  res
) => {
  try {
    const notification =
      await Notification.findById(
        req.params.id
      );

    if (!notification) {
      return res.status(404).json({
        success: false,
        message: "Notification not found",
      });
    }

    const canAccess =
      notification.recipientRole ===
        req.user.role &&
      (
        notification.recipientId === null ||
        notification.recipientId?.toString() ===
          req.user.id.toString()
      );

    if (!canAccess) {
      return res.status(403).json({
        success: false,
        message:
          "You cannot delete this notification",
      });
    }

    await Notification.findByIdAndDelete(
      req.params.id
    );

    res.json({
      success: true,
      message:
        "Notification deleted successfully",
    });
  } catch (error) {
    console.error(
      "Delete notification error:",
      error
    );

    res.status(500).json({
      success: false,
      message:
        "Failed to delete notification",
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