const Maintenance = require("../models/Maintenance");
const Tenant = require("../models/Tenant");
const Property = require("../models/Property");
const Unit = require("../models/Unit");
const Notification = require("../models/Notification");

const getMaintenance = async (req, res) => {
  try {
    const requests = await Maintenance.find()
      .populate("tenantId", "tenantId name email phone userId")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type status")
      .sort({ createdAt: -1 });

    res.json({
      success: true,
      count: requests.length,
      data: requests,
    });
  } catch (error) {
    console.error("Get maintenance error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve maintenance requests",
    });
  }
};

const getMaintenanceById = async (req, res) => {
  try {
    const request = await Maintenance.findById(req.params.id)
      .populate("tenantId", "tenantId name email phone userId")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type status");

    if (!request) {
      return res.status(404).json({
        success: false,
        message: "Maintenance request not found",
      });
    }

    res.json({
      success: true,
      data: request,
    });
  } catch (error) {
    console.error("Get maintenance request error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve maintenance request",
    });
  }
};

const createMaintenance = async (req, res) => {
  try {
    const {
      maintenanceId,
      tenantId,
      propertyId,
      unitId,
      issue,
      description,
      priority,
      status,
    } = req.body;

    if (!maintenanceId || !propertyId || !issue) {
      return res.status(400).json({
        success: false,
        message:
          "Maintenance ID, property and issue are required",
      });
    }

    const existing = await Maintenance.findOne({
      maintenanceId,
    });

    if (existing) {
      return res.status(409).json({
        success: false,
        message: "Maintenance ID already exists",
      });
    }

    const property = await Property.findById(propertyId);

    if (!property) {
      return res.status(404).json({
        success: false,
        message: "Property not found",
      });
    }

    let tenant = null;

    if (tenantId) {
      tenant = await Tenant.findById(tenantId);

      if (!tenant) {
        return res.status(404).json({
          success: false,
          message: "Tenant not found",
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

      if (unit.propertyId.toString() !== propertyId) {
        return res.status(400).json({
          success: false,
          message: "Unit does not belong to this property",
        });
      }
    }

    const request = await Maintenance.create({
      maintenanceId,
      tenantId: tenantId || null,
      propertyId,
      unitId: unitId || null,
      issue,
      description,
      priority: priority || "Medium",
      status: status || "Pending",
    });

    // Notify administrators.
    await Notification.create({
      recipientRole: "Administrator",
      recipientId: null,
      type: "maintenance_created",
      title: "New Maintenance Request",
      message: `A new maintenance request has been submitted: ${issue}.`,
      data: {
        maintenanceId: request._id,
        tenantId: tenantId || null,
        propertyId,
        unitId: unitId || null,
        priority: priority || "Medium",
      },
    });

    const populatedRequest = await Maintenance.findById(request._id)
      .populate("tenantId", "tenantId name email phone userId")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type status");

    res.status(201).json({
      success: true,
      message: "Maintenance request created successfully",
      data: populatedRequest,
    });
  } catch (error) {
    console.error("Create maintenance error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to create maintenance request",
    });
  }
};

const updateMaintenance = async (req, res) => {
  try {
    const request = await Maintenance.findById(req.params.id)
      .populate("tenantId", "tenantId name email phone userId");

    if (!request) {
      return res.status(404).json({
        success: false,
        message: "Maintenance request not found",
      });
    }

    const oldStatus = request.status;

    const allowedFields = [
      "issue",
      "description",
      "priority",
      "status",
      "unitId",
      "propertyId",
    ];

    allowedFields.forEach((field) => {
      if (req.body[field] !== undefined) {
        request[field] = req.body[field];
      }
    });

    if (request.propertyId) {
      const property = await Property.findById(
        request.propertyId
      );

      if (!property) {
        return res.status(404).json({
          success: false,
          message: "Property not found",
        });
      }
    }

    if (request.unitId) {
      const unit = await Unit.findById(request.unitId);

      if (!unit) {
        return res.status(404).json({
          success: false,
          message: "Unit not found",
        });
      }

      if (
        request.propertyId &&
        unit.propertyId.toString() !==
          request.propertyId.toString()
      ) {
        return res.status(400).json({
          success: false,
          message: "Unit does not belong to this property",
        });
      }
    }

    await request.save();

    // Notify the customer when status changes.
    if (
      oldStatus !== request.status &&
      request.tenantId &&
      request.tenantId.userId
    ) {
      await Notification.create({
        recipientRole: "Customer",
        recipientId: request.tenantId.userId,
        type: "maintenance_status_changed",
        title: "Maintenance Request Updated",
        message: `Your maintenance request "${request.issue}" is now ${request.status}.`,
        data: {
          maintenanceId: request._id,
          oldStatus,
          newStatus: request.status,
        },
      });
    }

    const updatedRequest =
      await Maintenance.findById(request._id)
        .populate(
          "tenantId",
          "tenantId name email phone userId"
        )
        .populate(
          "propertyId",
          "propertyId name location"
        )
        .populate(
          "unitId",
          "unitId unitNumber type status"
        );

    res.json({
      success: true,
      message: "Maintenance request updated successfully",
      data: updatedRequest,
    });
  } catch (error) {
    console.error("Update maintenance error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to update maintenance request",
    });
  }
};

const deleteMaintenance = async (req, res) => {
  try {
    const request = await Maintenance.findById(req.params.id);

    if (!request) {
      return res.status(404).json({
        success: false,
        message: "Maintenance request not found",
      });
    }

    await Maintenance.findByIdAndDelete(req.params.id);

    res.json({
      success: true,
      message: "Maintenance request deleted successfully",
    });
  } catch (error) {
    console.error("Delete maintenance error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to delete maintenance request",
    });
  }
};

module.exports = {
  getMaintenance,
  getMaintenanceById,
  createMaintenance,
  updateMaintenance,
  deleteMaintenance,
};
