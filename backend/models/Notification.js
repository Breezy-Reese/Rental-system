const mongoose = require("mongoose");

const notificationSchema = new mongoose.Schema(
  {
    recipientRole: {
      type: String,
      enum: ["Administrator", "Customer"],
      required: true,
    },

    recipientId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "User",
      default: null,
    },

    // User who created/sent the notification
    senderId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "User",
      default: null,
    },

    type: {
      type: String,
      required: true,
      trim: true,
    },

    title: {
      type: String,
      required: true,
      trim: true,
    },

    message: {
      type: String,
      required: true,
      trim: true,
    },

    data: {
      type: mongoose.Schema.Types.Mixed,
      default: {},
    },

    // Allows customer/admin conversations to be linked
    replyTo: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Notification",
      default: null,
    },

    parentNotificationId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Notification",
      default: null,
    },

    read: {
      type: Boolean,
      default: false,
    },
  },
  {
    timestamps: true,
  }
);

module.exports = mongoose.model("Notification", notificationSchema);