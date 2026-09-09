const mongoose = require("mongoose");

const leaseSchema = new mongoose.Schema(
  {
    leaseId: { type: String, unique: true, required: true },
    tenantId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Tenant",
      required: true,
    },
    propertyId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Property",
      required: true,
    },
    unitId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Unit",
      required: true,
    },
    startDate: { type: Date, required: true },
    endDate: { type: Date, required: true },
    rent: { type: Number, required: true, min: 0 },
    deposit: { type: Number, default: 0, min: 0 },
    status: {
      type: String,
      enum: ["Active", "Expired", "Terminated", "Pending"],
      default: "Pending",
    },
  },
  { timestamps: true }
);

module.exports = mongoose.model("Lease", leaseSchema);
