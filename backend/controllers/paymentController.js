const Payment = require("../models/Payment");
const Tenant = require("../models/Tenant");
const Lease = require("../models/Lease");
const Notification = require("../models/Notification");

const getPayments = async (req, res) => {
  try {
    const payments = await Payment.find()
      .populate("tenantId", "tenantId name email phone userId")
      .populate("leaseId", "leaseId startDate endDate rent status")
      .sort({ paymentDate: -1, createdAt: -1 });

    res.json({
      success: true,
      count: payments.length,
      data: payments,
    });
  } catch (error) {
    console.error("Get payments error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve payments",
    });
  }
};

const getPayment = async (req, res) => {
  try {
    const payment = await Payment.findById(req.params.id)
      .populate("tenantId", "tenantId name email phone userId")
      .populate("leaseId", "leaseId startDate endDate rent status");

    if (!payment) {
      return res.status(404).json({
        success: false,
        message: "Payment not found",
      });
    }

    res.json({
      success: true,
      data: payment,
    });
  } catch (error) {
    console.error("Get payment error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve payment",
    });
  }
};

const createPayment = async (req, res) => {
  try {
    const {
      paymentId,
      tenantId,
      leaseId,
      amount,
      paymentMethod,
      reference,
      status,
      paymentDate,
    } = req.body;

    if (
      !paymentId ||
      !tenantId ||
      amount === undefined ||
      !paymentMethod
    ) {
      return res.status(400).json({
        success: false,
        message:
          "Payment ID, tenant, amount and payment method are required",
      });
    }

    if (Number(amount) <= 0) {
      return res.status(400).json({
        success: false,
        message: "Payment amount must be greater than zero",
      });
    }

    const existingPayment = await Payment.findOne({ paymentId });

    if (existingPayment) {
      return res.status(409).json({
        success: false,
        message: "Payment ID already exists",
      });
    }

    const tenant = await Tenant.findById(tenantId);

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message: "Tenant not found",
      });
    }

    let lease = null;

    if (leaseId) {
      lease = await Lease.findById(leaseId);

      if (!lease) {
        return res.status(404).json({
          success: false,
          message: "Lease not found",
        });
      }

      if (lease.tenantId.toString() !== tenantId) {
        return res.status(400).json({
          success: false,
          message: "Lease does not belong to this tenant",
        });
      }
    }

    const payment = await Payment.create({
      paymentId,
      tenantId,
      leaseId: leaseId || null,
      amount: Number(amount),
      paymentMethod,
      reference,
      status: status || "Pending",
      paymentDate: paymentDate || new Date(),
    });

    // Notify administrators.
    await Notification.create({
      recipientRole: "Administrator",
      recipientId: null,
      type: "payment_created",
      title: "New Payment Recorded",
      message: `${tenant.name} has a new payment of KES ${Number(
        amount
      ).toLocaleString()}.`,
      data: {
        paymentId: payment._id,
        tenantId: tenant._id,
        leaseId: leaseId || null,
        amount: Number(amount),
        paymentMethod,
        status: status || "Pending",
      },
    });

    // Notify the customer account linked to the tenant.
    if (tenant.userId) {
      await Notification.create({
        recipientRole: "Customer",
        recipientId: tenant.userId,
        type: "payment_created",
        title: "Payment Recorded",
        message: `Your payment of KES ${Number(
          amount
        ).toLocaleString()} has been recorded.`,
        data: {
          paymentId: payment._id,
          amount: Number(amount),
          paymentMethod,
          status: status || "Pending",
        },
      });
    }

    const populatedPayment = await Payment.findById(payment._id)
      .populate("tenantId", "tenantId name email phone userId")
      .populate("leaseId", "leaseId startDate endDate rent status");

    res.status(201).json({
      success: true,
      message: "Payment recorded successfully",
      data: populatedPayment,
    });
  } catch (error) {
    console.error("Create payment error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to record payment",
    });
  }
};

const updatePayment = async (req, res) => {
  try {
    const payment = await Payment.findById(req.params.id)
      .populate("tenantId", "tenantId name email phone userId");

    if (!payment) {
      return res.status(404).json({
        success: false,
        message: "Payment not found",
      });
    }

    const oldStatus = payment.status;

    const allowedFields = [
      "amount",
      "paymentMethod",
      "reference",
      "status",
      "paymentDate",
      "leaseId",
    ];

    allowedFields.forEach((field) => {
      if (req.body[field] !== undefined) {
        payment[field] = req.body[field];
      }
    });

    if (Number(payment.amount) <= 0) {
      return res.status(400).json({
        success: false,
        message: "Payment amount must be greater than zero",
      });
    }

    if (payment.leaseId) {
      const lease = await Lease.findById(payment.leaseId);

      if (!lease) {
        return res.status(404).json({
          success: false,
          message: "Selected lease not found",
        });
      }

      if (
        lease.tenantId.toString() !==
        payment.tenantId._id.toString()
      ) {
        return res.status(400).json({
          success: false,
          message: "Lease does not belong to this tenant",
        });
      }
    }

    await payment.save();

    // Notify customer when payment status changes.
    if (
      oldStatus !== payment.status &&
      payment.tenantId.userId
    ) {
      await Notification.create({
        recipientRole: "Customer",
        recipientId: payment.tenantId.userId,
        type: "payment_status_changed",
        title: "Payment Status Updated",
        message: `Your payment of KES ${Number(
          payment.amount
        ).toLocaleString()} is now ${payment.status}.`,
        data: {
          paymentId: payment._id,
          oldStatus,
          newStatus: payment.status,
        },
      });
    }

    const updatedPayment = await Payment.findById(payment._id)
      .populate("tenantId", "tenantId name email phone userId")
      .populate("leaseId", "leaseId startDate endDate rent status");

    res.json({
      success: true,
      message: "Payment updated successfully",
      data: updatedPayment,
    });
  } catch (error) {
    console.error("Update payment error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to update payment",
    });
  }
};

const deletePayment = async (req, res) => {
  try {
    const payment = await Payment.findById(req.params.id);

    if (!payment) {
      return res.status(404).json({
        success: false,
        message: "Payment not found",
      });
    }

    await Payment.findByIdAndDelete(req.params.id);

    res.json({
      success: true,
      message: "Payment deleted successfully",
    });
  } catch (error) {
    console.error("Delete payment error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to delete payment",
    });
  }
};

module.exports = {
  getPayments,
  getPayment,
  createPayment,
  updatePayment,
  deletePayment,
};
