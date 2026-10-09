
const Lease = require("../models/Lease");
const Tenant = require("../models/Tenant");
const Unit = require("../models/Unit");
const Property = require("../models/Property");
const User = require("../models/User");
const Notification = require("../models/Notification");

// ============================================================
// GET ALL LEASES
// ============================================================

const getLeases = async (req, res) => {
  try {
    const leases = await Lease.find()
      .populate("tenantId", "tenantId name email phone userId")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status")
      .sort({ createdAt: -1 });

    return res.json({
      success: true,
      count: leases.length,
      data: leases,
    });
  } catch (error) {
    console.error("Get leases error:", error);

    return res.status(500).json({
      success: false,
      message: "Failed to retrieve leases",
    });
  }
};

// ============================================================
// GET SINGLE LEASE
// ============================================================

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

    return res.json({
      success: true,
      data: lease,
    });
  } catch (error) {
    console.error("Get lease error:", error);

    return res.status(500).json({
      success: false,
      message: "Failed to retrieve lease",
    });
  }
};

// ============================================================
// CREATE LEASE FOR EXISTING TENANT
// ============================================================

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
    } = req.body || {};

    if (
      !leaseId ||
      !tenantId ||
      !propertyId ||
      !unitId ||
      !startDate ||
      !endDate ||
      rent === undefined ||
      rent === null ||
      rent === ""
    ) {
      return res.status(400).json({
        success: false,
        message:
          "Lease ID, tenant, property, unit, dates and rent are required",
      });
    }

    const start = new Date(startDate);
    const end = new Date(endDate);
    const rentAmount = Number(rent);

    if (
      Number.isNaN(start.getTime()) ||
      Number.isNaN(end.getTime())
    ) {
      return res.status(400).json({
        success: false,
        message: "Invalid lease dates",
      });
    }

    if (end <= start) {
      return res.status(400).json({
        success: false,
        message: "End date must be after start date",
      });
    }

    if (!Number.isFinite(rentAmount) || rentAmount < 0) {
      return res.status(400).json({
        success: false,
        message: "Rent must be a valid non-negative number",
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

    if (String(unit.propertyId) !== String(propertyId)) {
      return res.status(400).json({
        success: false,
        message: "Unit does not belong to the selected property",
      });
    }

    if (
      unit.status === "Occupied" &&
      String(unit.tenantId || "") !== String(tenantId)
    ) {
      return res.status(409).json({
        success: false,
        message: "Unit is already occupied by another tenant",
      });
    }

    if (unit.status === "Maintenance") {
      return res.status(409).json({
        success: false,
        message: "This unit is currently under maintenance",
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
      startDate: start,
      endDate: end,
      rent: rentAmount,
      deposit: Number(deposit) || 0,
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

    await Notification.create({
      recipientRole: "Administrator",
      recipientId: null,
      senderId: req.user?.id || req.user?._id,
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
      .populate("tenantId", "tenantId name email phone userId")
      .populate("propertyId", "propertyId name location")
      .populate("unitId", "unitId unitNumber type rent status");

    return res.status(201).json({
      success: true,
      message: "Lease created successfully",
      data: populatedLease,
    });
  } catch (error) {
    console.error("Create lease error:", error);

    return res.status(500).json({
      success: false,
      message: "Failed to create lease",
    });
  }
};

// ============================================================
// ASSIGN CUSTOMER TO VACANT UNIT
// ============================================================

const assignCustomerToUnit = async (req, res) => {
  try {
    const body = req.body || {};

    /*
     * The frontend may send customerId or userId.
     * Accept either name, but both must contain the
     * MongoDB User document ID.
     */
    const userId = body.userId || body.customerId;
    const propertyId = body.propertyId;
    const unitId = body.unitId;
    const leaseId = body.leaseId;
    const startDate = body.startDate;
    const endDate = body.endDate;
    const deposit = body.deposit;

    const missing = {
      userId: !userId,
      propertyId: !propertyId,
      unitId: !unitId,
      startDate: !startDate,
      endDate: !endDate,
    };

    if (Object.values(missing).some(Boolean)) {
      return res.status(400).json({
        success: false,
        message:
          "Customer, property, unit, start date and end date are required",
        missing,
      });
    }

    // ----------------------------------------------------------
    // Validate dates and deposit
    // ----------------------------------------------------------

    const start = new Date(startDate);
    const end = new Date(endDate);

    if (
      Number.isNaN(start.getTime()) ||
      Number.isNaN(end.getTime())
    ) {
      return res.status(400).json({
        success: false,
        message: "Invalid lease dates",
      });
    }

    if (end <= start) {
      return res.status(400).json({
        success: false,
        message: "End date must be after start date",
      });
    }

    const depositAmount =
      deposit === undefined || deposit === null || deposit === ""
        ? 0
        : Number(deposit);

    if (
      !Number.isFinite(depositAmount) ||
      depositAmount < 0
    ) {
      return res.status(400).json({
        success: false,
        message: "Deposit must be a valid non-negative number",
      });
    }

    // ----------------------------------------------------------
    // Find active customer account
    // ----------------------------------------------------------

    if (!/^[a-f\d]{24}$/i.test(String(userId))) {
      return res.status(400).json({
        success: false,
        message:
          "Invalid customer account ID. Select the customer again.",
      });
    }

    const customer = await User.findOne({
      _id: userId,
      role: "Customer",
      status: "Active",
    });

    if (!customer) {
      return res.status(404).json({
        success: false,
        message:
          "Active customer account not found. Confirm that the selected customer is an active account.",
      });
    }

    // ----------------------------------------------------------
    // Validate property
    // ----------------------------------------------------------

    if (!/^[a-f\d]{24}$/i.test(String(propertyId))) {
      return res.status(400).json({
        success: false,
        message: "Invalid property ID. Select the property again.",
      });
    }

    const property = await Property.findById(propertyId);

    if (!property) {
      return res.status(404).json({
        success: false,
        message: "Property not found",
      });
    }

    if (property.status !== "Active") {
      return res.status(409).json({
        success: false,
        message: "This property is not active",
      });
    }

    // ----------------------------------------------------------
    // Validate unit
    // ----------------------------------------------------------

    if (!/^[a-f\d]{24}$/i.test(String(unitId))) {
      return res.status(400).json({
        success: false,
        message: "Invalid unit ID. Select the unit again.",
      });
    }

    const unit = await Unit.findById(unitId);

    if (!unit) {
      return res.status(404).json({
        success: false,
        message: "Unit not found",
      });
    }

    if (String(unit.propertyId) !== String(property._id)) {
      return res.status(400).json({
        success: false,
        message:
          "Selected unit does not belong to the selected property",
      });
    }

    if (unit.status !== "Vacant") {
      return res.status(409).json({
        success: false,
        message: "The selected unit is not vacant",
      });
    }

    if (unit.tenantId) {
      return res.status(409).json({
        success: false,
        message: "The selected unit is already assigned to a tenant",
      });
    }

    // ----------------------------------------------------------
    // Check for existing tenant record
    // ----------------------------------------------------------

    let tenant = await Tenant.findOne({
      userId: customer._id,
    });

    if (tenant) {
      const hasExistingAssignment = Boolean(
        tenant.propertyId || tenant.unitId
      );

      const existingActiveLease = await Lease.findOne({
        tenantId: tenant._id,
        status: "Active",
      });

      if (hasExistingAssignment || existingActiveLease) {
        return res.status(409).json({
          success: false,
          message:
            "This customer already has a rental assignment",
        });
      }

      tenant.name = customer.name;
      tenant.email = customer.email;
      tenant.phone = customer.phone || "";
      tenant.propertyId = property._id;
      tenant.unitId = unit._id;
      tenant.status = "Active";

      await tenant.save();
    } else {
      const generatedTenantId =
        `TEN-${Date.now()}-${Math.floor(
          1000 + Math.random() * 9000
        )}`;

      tenant = await Tenant.create({
        tenantId: generatedTenantId,
        userId: customer._id,
        name: customer.name,
        email: customer.email,
        phone: customer.phone || "",
        propertyId: property._id,
        unitId: unit._id,
        status: "Active",
      });
    }

    // ----------------------------------------------------------
    // Generate and validate lease ID
    // ----------------------------------------------------------

    const generatedLeaseId =
      typeof leaseId === "string" && leaseId.trim()
        ? leaseId.trim()
        : `LEASE-${Date.now()}-${Math.floor(
            1000 + Math.random() * 9000
          )}`;

    const existingLease = await Lease.findOne({
      leaseId: generatedLeaseId,
    });

    if (existingLease) {
      return res.status(409).json({
        success: false,
        message: "Lease ID already exists",
      });
    }

    // ----------------------------------------------------------
    // Create lease
    // ----------------------------------------------------------

    const lease = await Lease.create({
      leaseId: generatedLeaseId,
      tenantId: tenant._id,
      propertyId: property._id,
      unitId: unit._id,
      startDate: start,
      endDate: end,
      rent: Number(unit.rent),
      deposit: depositAmount,
      status: "Active",
    });

    // ----------------------------------------------------------
    // Mark unit occupied
    // ----------------------------------------------------------

    await Unit.findByIdAndUpdate(unit._id, {
      tenantId: tenant._id,
      status: "Occupied",
    });

    // ----------------------------------------------------------
    // Synchronize tenant assignment
    // ----------------------------------------------------------

    await Tenant.findByIdAndUpdate(tenant._id, {
      propertyId: property._id,
      unitId: unit._id,
      status: "Active",
    });

    // ----------------------------------------------------------
    // Notify customer
    // ----------------------------------------------------------

    await Notification.create({
      recipientRole: "Customer",
      recipientId: customer._id,
      senderId: req.user?.id || req.user?._id,
      type: "lease_assigned",
      title: "Rental Unit Assigned",
      message: `You have been assigned ${unit.unitNumber} at ${property.name}.`,
      data: {
        leaseId: lease._id,
        tenantId: tenant._id,
        customerId: customer._id,
        propertyId: property._id,
        unitId: unit._id,
      },
    });

    // ----------------------------------------------------------
    // Notify administrator
    // ----------------------------------------------------------

    await Notification.create({
      recipientRole: "Administrator",
      recipientId: null,
      senderId: req.user?.id || req.user?._id,
      type: "lease_assigned",
      title: "Customer Assigned to Unit",
      message: `${customer.name} was assigned ${unit.unitNumber} at ${property.name}.`,
      data: {
        leaseId: lease._id,
        tenantId: tenant._id,
        customerId: customer._id,
        propertyId: property._id,
        unitId: unit._id,
      },
    });

    // ----------------------------------------------------------
    // Return populated lease
    // ----------------------------------------------------------

    const populatedLease = await Lease.findById(lease._id)
      .populate("tenantId", "tenantId name email phone userId")
      .populate("propertyId", "propertyId name location address")
      .populate("unitId", "unitId unitNumber type rent status");

    return res.status(201).json({
      success: true,
      message: "Customer assigned to rental unit successfully",
      data: populatedLease,
    });
  } catch (error) {
    console.error("Assign customer to unit error:", error);

    return res.status(500).json({
      success: false,
      message: "Failed to assign customer to rental unit",
    });
  }
};

// ============================================================
// UPDATE LEASE
// ============================================================

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

    for (const field of allowedFields) {
      if (req.body?.[field] !== undefined) {
        lease[field] = req.body[field];
      }
    }

    const start = new Date(lease.startDate);
    const end = new Date(lease.endDate);

    if (
      Number.isNaN(start.getTime()) ||
      Number.isNaN(end.getTime())
    ) {
      return res.status(400).json({
        success: false,
        message: "Invalid lease dates",
      });
    }

    if (end <= start) {
      return res.status(400).json({
        success: false,
        message: "End date must be after start date",
      });
    }

    await lease.save();

    // Release unit when lease ends.
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

    // Restore occupancy if a lease becomes active again.
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

    return res.json({
      success: true,
      message: "Lease updated successfully",
      data: updatedLease,
    });
  } catch (error) {
    console.error("Update lease error:", error);

    return res.status(500).json({
      success: false,
      message: "Failed to update lease",
    });
  }
};

// ============================================================
// DELETE LEASE
// ============================================================

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
        propertyId: null,
      });
    }

    await Lease.findByIdAndDelete(req.params.id);

    return res.json({
      success: true,
      message: "Lease deleted successfully",
    });
  } catch (error) {
    console.error("Delete lease error:", error);

    return res.status(500).json({
      success: false,
      message: "Failed to delete lease",
    });
  }
};

// ============================================================
// EXPORT CONTROLLERS
// ============================================================

module.exports = {
  getLeases,
  getLease,
  createLease,
  assignCustomerToUnit,
  updateLease,
  deleteLease,
};