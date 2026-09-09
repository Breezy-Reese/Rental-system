const mongoose = require("mongoose");

const unitSchema = new mongoose.Schema(
  {
    unitId: { type: String, unique: true, required: true },
    propertyId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Property",
      required: true,
    },
    unitNumber: { type: String, required: true, trim: true },
    type: { type: String, trim: true },
    rent: { type: Number, required: true, min: 0 },
    status: {
      type: String,
      enum: ["Vacant", "Occupied", "Maintenance"],
      default: "Vacant",
    },
    tenantId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Tenant",
      default: null,
    },
  },
  { timestamps: true }
);

module.exports = mongoose.model("Unit", unitSchema);
