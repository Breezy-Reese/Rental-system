const Unit = require("../models/Unit");
const Property = require("../models/Property");

const getUnits = async (req, res) => {
  try {
    const units = await Unit.find()
      .populate("propertyId", "propertyId name location")
      .populate("tenantId", "tenantId name email phone")
      .sort({ createdAt: -1 });

    res.json({
      success: true,
      count: units.length,
      data: units,
    });
  } catch (error) {
    console.error("Get units error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve units",
    });
  }
};

const getUnitsByProperty = async (req, res) => {
  try {
    const units = await Unit.find({
      propertyId: req.params.propertyId,
    })
      .populate("tenantId", "tenantId name email phone")
      .sort({ unitNumber: 1 });

    res.json({
      success: true,
      count: units.length,
      data: units,
    });
  } catch (error) {
    console.error("Get property units error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve property units",
    });
  }
};

const getUnit = async (req, res) => {
  try {
    const unit = await Unit.findById(req.params.id)
      .populate("propertyId", "propertyId name location")
      .populate("tenantId", "tenantId name email phone");

    if (!unit) {
      return res.status(404).json({
        success: false,
        message: "Unit not found",
      });
    }

    res.json({
      success: true,
      data: unit,
    });
  } catch (error) {
    console.error("Get unit error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve unit",
    });
  }
};

const createUnit = async (req, res) => {
  try {
    const {
      unitId,
      propertyId,
      unitNumber,
      type,
      rent,
      status,
      tenantId,
    } = req.body;

    if (!unitId || !propertyId || !unitNumber || rent === undefined) {
      return res.status(400).json({
        success: false,
        message: "Unit ID, property, unit number and rent are required",
      });
    }

    const property = await Property.findById(propertyId);

    if (!property) {
      return res.status(404).json({
        success: false,
        message: "Property not found",
      });
    }

    const existingUnit = await Unit.findOne({ unitId });

    if (existingUnit) {
      return res.status(409).json({
        success: false,
        message: "Unit ID already exists",
      });
    }

    const unit = await Unit.create({
      unitId,
      propertyId,
      unitNumber,
      type,
      rent,
      status: status || "Vacant",
      tenantId: tenantId || null,
    });

    // Keep property's unit count synchronized.
    await Property.findByIdAndUpdate(propertyId, {
      $inc: { totalUnits: 1 },
    });

    const populatedUnit = await Unit.findById(unit._id)
      .populate("propertyId", "propertyId name location")
      .populate("tenantId", "tenantId name email phone");

    res.status(201).json({
      success: true,
      message: "Unit created successfully",
      data: populatedUnit,
    });
  } catch (error) {
    console.error("Create unit error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to create unit",
    });
  }
};

const updateUnit = async (req, res) => {
  try {
    const unit = await Unit.findByIdAndUpdate(
      req.params.id,
      req.body,
      {
        new: true,
        runValidators: true,
      }
    )
      .populate("propertyId", "propertyId name location")
      .populate("tenantId", "tenantId name email phone");

    if (!unit) {
      return res.status(404).json({
        success: false,
        message: "Unit not found",
      });
    }

    res.json({
      success: true,
      message: "Unit updated successfully",
      data: unit,
    });
  } catch (error) {
    console.error("Update unit error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to update unit",
    });
  }
};

const deleteUnit = async (req, res) => {
  try {
    const unit = await Unit.findById(req.params.id);

    if (!unit) {
      return res.status(404).json({
        success: false,
        message: "Unit not found",
      });
    }

    await Unit.findByIdAndDelete(req.params.id);

    await Property.findByIdAndUpdate(unit.propertyId, {
      $inc: { totalUnits: -1 },
    });

    res.json({
      success: true,
      message: "Unit deleted successfully",
    });
  } catch (error) {
    console.error("Delete unit error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to delete unit",
    });
  }
};

module.exports = {
  getUnits,
  getUnitsByProperty,
  getUnit,
  createUnit,
  updateUnit,
  deleteUnit,
};
