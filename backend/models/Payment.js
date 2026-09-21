const mongoose = require("mongoose");

const paymentSchema = new mongoose.Schema(
  {
    paymentId: {
      type: String,
      unique: true,
      required: true,
      trim: true,
    },

    tenantId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Tenant",
      required: true,
    },

    // Customer's User._id
    customerId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "User",
      default: null,
    },

    leaseId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Lease",
      default: null,
    },

    amount: {
      type: Number,
      required: true,
      min: 0,
    },

    paymentMethod: {
      type: String,
      enum: [
        "M-Pesa",
        "Bank Transfer",
        "Cash",
        "Other",
      ],
      required: true,
    },

    reference: {
      type: String,
      trim: true,
      default: "",
    },

    status: {
      type: String,
      enum: [
        "Pending",
        "Paid",
        "Failed",
        "Cancelled",
      ],
      default: "Pending",
    },

    paymentDate: {
      type: Date,
      default: Date.now,
    },

    // User._id of the account that submitted/created
    // the payment.
    submittedBy: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "User",
      default: null,
    },
  },
  {
    timestamps: true,
  }
);

module.exports =
  mongoose.model("Payment", paymentSchema);
