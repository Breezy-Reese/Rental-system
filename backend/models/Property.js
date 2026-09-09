const mongoose = require("mongoose");

const propertySchema = new mongoose.Schema(
  {
    propertyId: { type: String, unique: true, required: true },
    name: { type: String, required: true, trim: true },
    location: { type: String, required: true, trim: true },
    address: { type: String, trim: true },
    description: { type: String, trim: true },
    totalUnits: { type: Number, default: 0 },
    status: {
      type: String,
      enum: ["Active", "Inactive"],
      default: "Active",
    },
  },
  { timestamps: true }
);

module.exports = mongoose.model("Property", propertySchema);
