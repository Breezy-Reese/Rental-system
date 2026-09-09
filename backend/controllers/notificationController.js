const Notification = require("../models/Notification");

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
};
