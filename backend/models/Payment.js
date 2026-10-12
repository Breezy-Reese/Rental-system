const mongoose = require("mongoose");

const paymentSchema = new mongoose.Schema(
  {
    paymentId: {
      type: String,
      required: true,
      unique: true,
      trim: true,
    },

    tenantId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Tenant",
      required: true,
    },

    // The customer's User._id
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
      enum: ["M-Pesa", "Bank Transfer", "Cash", "Other"],
      required: true,
    },

    reference: {
      type: String,
      trim: true,
      default: "",
    },

    status: {
      type: String,
      enum: ["Pending", "Paid", "Failed", "Cancelled"],
      default: "Pending",
    },

    paymentDate: {
      type: Date,
      default: Date.now,
    },

    submittedBy: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "User",
      default: null,
    },

    // M-Pesa transaction information
    mpesaPhoneNumber: {
      type: String,
      trim: true,
      default: "",
    },

    merchantRequestId: {
      type: String,
      trim: true,
      default: "",
    },

    checkoutRequestId: {
      type: String,
      trim: true,
      default: "",
    },

    mpesaReceiptNumber: {
      type: String,
      trim: true,
      default: "",
    },

    mpesaResultCode: {
      type: Number,
      default: null,
    },

    mpesaResultDesc: {
      type: String,
      trim: true,
      default: "",
    },

    mpesaPaidAmount: {
      type: Number,
      default: null,
    },
  },
  {
    timestamps: true,
  }
);

// Prevent two payment records from sharing a non-empty
// Safaricom checkout request ID.
paymentSchema.index(
  { checkoutRequestId: 1 },
  {
    unique: true,
    partialFilterExpression: {
      checkoutRequestId: { $type: "string", $gt: "" },
    },
  }
);

module.exports = mongoose.model("Payment", paymentSchema);