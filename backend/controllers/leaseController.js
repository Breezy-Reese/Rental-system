const Lease = require("../models/Lease");
const Tenant = require("../models/Tenant");
const Unit = require("../models/Unit");
const Property = require("../models/Property");
const Notification = require("../models/Notification");

const getLeases = async (req, res) => {
  try {
    const leases = await Lease.find()
      .populate("tenantId", "tenantId name email phone")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status")
      .sort({ createdAt: -1 });

    res.json({
      success: true,
      count: leases.length,
      data: leases,
    });
  } catch (error) {
    console.error("Get leases error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve leases",
    });
  }
};

const getLease = async (req, res) => {
  try {
    const lease = await Lease.findById(req.params.id)
      .populate("tenantId", "tenantId name email phone userId")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status");

    if (!lease) {
      return res.status(404).json({
        success: false,
        message: "Lease not found",
      });
    }

    res.json({
      success: true,
      data: lease,
    });
  } catch (error) {
    console.error("Get lease error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve lease",
    });
  }
};

const createLease = async (req, res) => {
  try {
    const {
      leaseId,
      tenantId,
      propertyId,
      unitId,
      startDate,
      endDate,
      rent,
      deposit,
      status,
    } = req.body;

    if (
      !leaseId ||
      !tenantId ||
      !propertyId ||
      !unitId ||
      !startDate ||
      !endDate ||
      rent === undefined
    ) {
      return res.status(400).json({
        success: false,
        message:
          "Lease ID, tenant, property, unit, dates and rent are required",
      });
    }

    if (new Date(endDate) <= new Date(startDate)) {
      return res.status(400).json({
        success: false,
        message: "End date must be after start date",
      });
    }

    const existingLease = await Lease.findOne({ leaseId });

    if (existingLease) {
      return res.status(409).json({
        success: false,
        message: "Lease ID already exists",
      });
    }

    const tenant = await Tenant.findById(tenantId);

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message: "Tenant not found",
      });
    }

    const property = await Property.findById(propertyId);

    if (!property) {
      return res.status(404).json({
        success: false,
        message: "Property not found",
      });
    }

    const unit = await Unit.findById(unitId);

    if (!unit) {
      return res.status(404).json({
        success: false,
        message: "Unit not found",
      });
    }

    if (unit.propertyId.toString() !== propertyId) {
      return res.status(400).json({
        success: false,
        message: "Unit does not belong to the selected property",
      });
    }

    if (
      unit.status === "Occupied" &&
      (!unit.tenantId ||
        unit.tenantId.toString() !== tenantId)
    ) {
      return res.status(409).json({
        success: false,
        message: "Unit is already occupied by another tenant",
      });
    }

    const activeLease = await Lease.findOne({
      unitId,
      status: "Active",
    });

    if (activeLease) {
      return res.status(409).json({
        success: false,
        message: "This unit already has an active lease",
      });
    }

    const lease = await Lease.create({
      leaseId,
      tenantId,
      propertyId,
      unitId,
      startDate,
      endDate,
      rent,
      deposit: deposit || 0,
      status: status || "Active",
    });

    await Unit.findByIdAndUpdate(unitId, {
      tenantId,
      status: "Occupied",
    });

    await Tenant.findByIdAndUpdate(tenantId, {
      propertyId,
      unitId,
      status: "Active",
    });

    // Notify administrators about the new lease.
    await Notification.create({
      recipientRole: "Administrator",
      recipientId: null,
      type: "lease_created",
      title: "New Lease Created",
      message: `${tenant.name} has been assigned a new lease for ${unit.unitNumber}.`,
      data: {
        leaseId: lease._id,
        tenantId: tenant._id,
        propertyId: property._id,
        unitId: unit._id,
      },
    });

    const populatedLease = await Lease.findById(lease._id)
      .populate("tenantId", "tenantId name email phone")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status");

    res.status(201).json({
      success: true,
      message: "Lease created successfully",
      data: populatedLease,
    });
  } catch (error) {
    console.error("Create lease error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to create lease",
    });
  }
};

const updateLease = async (req, res) => {
  try {
    const lease = await Lease.findById(req.params.id);

    if (!lease) {
      return res.status(404).json({
        success: false,
        message: "Lease not found",
      });
    }

    const oldStatus = lease.status;

    const allowedFields = [
      "startDate",
      "endDate",
      "rent",
      "deposit",
      "status",
    ];

    allowedFields.forEach((field) => {
      if (req.body[field] !== undefined) {
        lease[field] = req.body[field];
      }
    });

    if (lease.endDate <= lease.startDate) {
      return res.status(400).json({
        success: false,
        message: "End date must be after start date",
      });
    }

    await lease.save();

    // Free the unit when a lease is terminated or expired.
    if (
      ["Terminated", "Expired"].includes(lease.status) &&
      oldStatus === "Active"
    ) {
      await Unit.findByIdAndUpdate(lease.unitId, {
        tenantId: null,
        status: "Vacant",
      });

      await Tenant.findByIdAndUpdate(lease.tenantId, {
        unitId: null,
      });
    }

    // Restore occupancy if an inactive lease becomes active.
    if (
      lease.status === "Active" &&
      ["Terminated", "Expired"].includes(oldStatus)
    ) {
      await Unit.findByIdAndUpdate(lease.unitId, {
        tenantId: lease.tenantId,
        status: "Occupied",
      });

      await Tenant.findByIdAndUpdate(lease.tenantId, {
        unitId: lease.unitId,
      });
    }

    const updatedLease = await Lease.findById(lease._id)
      .populate("tenantId", "tenantId name email phone")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status");

    res.json({
      success: true,
      message: "Lease updated successfully",
      data: updatedLease,
    });
  } catch (error) {
    console.error("Update lease error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to update lease",
    });
  }
};

const deleteLease = async (req, res) => {
  try {
    const lease = await Lease.findById(req.params.id);

    if (!lease) {
      return res.status(404).json({
        success: false,
        message: "Lease not found",
      });
    }

    if (lease.status === "Active") {
      await Unit.findByIdAndUpdate(lease.unitId, {
        tenantId: null,
        status: "Vacant",
      });

      await Tenant.findByIdAndUpdate(lease.tenantId, {
        unitId: null,
      });
    }

    await Lease.findByIdAndDelete(req.params.id);

    res.json({
      success: true,
      message: "Lease deleted successfully",
    });
  } catch (error) {
    console.error("Delete lease error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to delete lease",
    });
  }
};

module.exports = {
  getLeases,
  getLease,
  createLease,
  updateLease,
  deleteLease,
};
