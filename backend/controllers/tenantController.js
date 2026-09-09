const Tenant = require("../models/Tenant");
const User = require("../models/User");
const Property = require("../models/Property");
const Unit = require("../models/Unit");

const getTenants = async (req, res) => {
  try {
    const tenants = await Tenant.find()
      .populate("userId", "name email phone role status")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status")
      .sort({ createdAt: -1 });

    res.json({
      success: true,
      count: tenants.length,
      data: tenants,
    });
  } catch (error) {
    console.error("Get tenants error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve tenants",
    });
  }
};

const getTenant = async (req, res) => {
  try {
    const tenant = await Tenant.findById(req.params.id)
      .populate("userId", "name email phone role status")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status");

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message: "Tenant not found",
      });
    }

    res.json({
      success: true,
      data: tenant,
    });
  } catch (error) {
    console.error("Get tenant error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve tenant",
    });
  }
};

const createTenant = async (req, res) => {
  try {
    const {
      tenantId,
      userId,
      propertyId,
      unitId,
      name,
      email,
      phone,
      status,
    } = req.body;

    if (!tenantId || !userId || !name || !email) {
      return res.status(400).json({
        success: false,
        message: "Tenant ID, user ID, name and email are required",
      });
    }

    const user = await User.findById(userId);

    if (!user) {
      return res.status(404).json({
        success: false,
        message: "Customer account not found",
      });
    }

    if (user.role !== "Customer") {
      return res.status(400).json({
        success: false,
        message: "Tenant must be linked to a Customer account",
      });
    }

    const existingTenant = await Tenant.findOne({
      $or: [{ tenantId }, { userId }],
    });

    if (existingTenant) {
      return res.status(409).json({
        success: false,
        message: "Tenant ID or customer account is already linked",
      });
    }

    if (propertyId) {
      const property = await Property.findById(propertyId);

      if (!property) {
        return res.status(404).json({
          success: false,
          message: "Property not found",
        });
      }
    }

    if (unitId) {
      const unit = await Unit.findById(unitId);

      if (!unit) {
        return res.status(404).json({
          success: false,
          message: "Unit not found",
        });
      }

      if (unit.status === "Occupied") {
        return res.status(409).json({
          success: false,
          message: "This unit is already occupied",
        });
      }

      if (propertyId && unit.propertyId.toString() !== propertyId) {
        return res.status(400).json({
          success: false,
          message: "Unit does not belong to the selected property",
        });
      }
    }

    const tenant = await Tenant.create({
      tenantId,
      userId,
      name,
      email: email.toLowerCase(),
      phone,
      propertyId: propertyId || null,
      unitId: unitId || null,
      status: status || "Active",
    });

    if (unitId) {
      await Unit.findByIdAndUpdate(unitId, {
        tenantId: tenant._id,
        status: "Occupied",
      });
    }

    const populatedTenant = await Tenant.findById(tenant._id)
      .populate("userId", "name email phone role status")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status");

    res.status(201).json({
      success: true,
      message: "Tenant created successfully",
      data: populatedTenant,
    });
  } catch (error) {
    console.error("Create tenant error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to create tenant",
    });
  }
};

const updateTenant = async (req, res) => {
  try {
    const tenant = await Tenant.findById(req.params.id);

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message: "Tenant not found",
      });
    }

    const oldUnitId = tenant.unitId ? tenant.unitId.toString() : null;

    const {
      name,
      email,
      phone,
      propertyId,
      unitId,
      status,
    } = req.body;

    if (propertyId) {
      const property = await Property.findById(propertyId);

      if (!property) {
        return res.status(404).json({
          success: false,
          message: "Property not found",
        });
      }
    }

    if (unitId) {
      const unit = await Unit.findById(unitId);

      if (!unit) {
        return res.status(404).json({
          success: false,
          message: "Unit not found",
        });
      }

      if (
        unit.status === "Occupied" &&
        (!unit.tenantId ||
          unit.tenantId.toString() !== tenant._id.toString())
      ) {
        return res.status(409).json({
          success: false,
          message: "This unit is already occupied",
        });
      }

      if (propertyId && unit.propertyId.toString() !== propertyId) {
        return res.status(400).json({
          success: false,
          message: "Unit does not belong to the selected property",
        });
      }
    }

    tenant.name = name ?? tenant.name;
    tenant.email = email
      ? email.toLowerCase()
      : tenant.email;
    tenant.phone = phone ?? tenant.phone;
    tenant.propertyId = propertyId ?? tenant.propertyId;
    tenant.unitId = unitId ?? tenant.unitId;
    tenant.status = status ?? tenant.status;

    await tenant.save();

    if (oldUnitId && oldUnitId !== (unitId || null)) {
      await Unit.findByIdAndUpdate(oldUnitId, {
        tenantId: null,
        status: "Vacant",
      });
    }

    if (unitId) {
      await Unit.findByIdAndUpdate(unitId, {
        tenantId: tenant._id,
        status: "Occupied",
      });
    }

    const updatedTenant = await Tenant.findById(tenant._id)
      .populate("userId", "name email phone role status")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status");

    res.json({
      success: true,
      message: "Tenant updated successfully",
      data: updatedTenant,
    });
  } catch (error) {
    console.error("Update tenant error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to update tenant",
    });
  }
};

const deleteTenant = async (req, res) => {
  try {
    const tenant = await Tenant.findById(req.params.id);

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message: "Tenant not found",
      });
    }

    if (tenant.unitId) {
      await Unit.findByIdAndUpdate(tenant.unitId, {
        tenantId: null,
        status: "Vacant",
      });
    }

    await Tenant.findByIdAndDelete(req.params.id);

    res.json({
      success: true,
      message: "Tenant deleted successfully",
    });
  } catch (error) {
    console.error("Delete tenant error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to delete tenant",
    });
  }
};

module.exports = {
  getTenants,
  getTenant,
  createTenant,
  updateTenant,
  deleteTenant,
};
