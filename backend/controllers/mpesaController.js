const Payment = require("../models/Payment");
const Tenant = require("../models/Tenant");
const Lease = require("../models/Lease");
const Notification = require("../models/Notification");

const {
  initiateStkPush,
} = require("../services/mpesa");

/*
|--------------------------------------------------------------------------
| Customer initiates STK Push
|--------------------------------------------------------------------------
*/

const stkPush = async (req, res) => {
  try {
    const { phoneNumber, amount, leaseId } = req.body;

    if (!phoneNumber || !amount) {
      return res.status(400).json({
        success: false,
        message: "Phone number and amount are required",
      });
    }

    if (Number(amount) <= 0) {
      return res.status(400).json({
        success: false,
        message: "Amount must be greater than zero",
      });
    }

    const tenant = await Tenant.findOne({
      userId: req.user.id,
    });

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message: "Customer tenant record not found",
      });
    }

    let lease = null;

    if (leaseId) {
      lease = await Lease.findById(leaseId);

      if (!lease || lease.tenantId.toString() !== tenant._id.toString()) {
        return res.status(403).json({
          success: false,
          message: "Invalid lease for this account",
        });
      }
    } else {
      lease = await Lease.findOne({
        tenantId: tenant._id,
        status: "Active",
      }).sort({ createdAt: -1 });
    }

    const paymentId =
      "MP-" + Date.now() + "-" + Math.floor(Math.random() * 10000);

    const stkResponse = await initiateStkPush({
      phoneNumber,
      amount,
      accountReference: tenant.tenantId || "PropertyPro",
      transactionDesc: "Rent payment",
    });

    if (stkResponse.ResponseCode !== "0") {
      return res.status(400).json({
        success: false,
        message:
          stkResponse.ResponseDescription ||
          "Failed to initiate M-Pesa payment",
      });
    }

    // Create a Pending payment record now; the callback fills in the result.
    const payment = await Payment.create({
      paymentId,
      tenantId: tenant._id,
      customerId: req.user.id,
      leaseId: lease ? lease._id : null,
      amount: Number(amount),
      paymentMethod: "M-Pesa",
      status: "Pending",
      paymentDate: new Date(),
      submittedBy: req.user.id,
      mpesaPhoneNumber: phoneNumber,
      merchantRequestId: stkResponse.MerchantRequestID,
      checkoutRequestId: stkResponse.CheckoutRequestID,
    });

    res.status(200).json({
      success: true,
      message: "STK Push sent. Check your phone to complete payment.",
      data: {
        paymentId: payment.paymentId,
        checkoutRequestId: stkResponse.CheckoutRequestID,
      },
    });
  } catch (error) {
    console.error(
      "STK push error:",
      error.response ? error.response.data : error.message
    );

    res.status(500).json({
      success: false,
      message: "Failed to initiate M-Pesa payment",
    });
  }
};

/*
|--------------------------------------------------------------------------
| Safaricom callback (async result)
|--------------------------------------------------------------------------
*/

const stkCallback = async (req, res) => {
  try {
    console.log(
      "MPESA CALLBACK RAW:",
      JSON.stringify(req.body, null, 2)
    );

    const callback = req.body?.Body?.stkCallback;

    if (!callback) {
      return res.status(400).json({
        success: false,
        message: "Invalid callback payload",
      });
    }

    const {
      CheckoutRequestID,
      ResultCode,
      ResultDesc,
      CallbackMetadata,
    } = callback;

    const payment = await Payment.findOne({
      checkoutRequestId: CheckoutRequestID,
    });

    if (!payment) {
      console.error(
        "No matching payment for CheckoutRequestID:",
        CheckoutRequestID
      );

      return res.status(200).json({
        ResultCode: 0,
        ResultDesc: "Accepted",
      });
    }

    payment.mpesaResultCode = ResultCode;
    payment.mpesaResultDesc = ResultDesc;

    if (ResultCode === 0) {
      const items = CallbackMetadata?.Item || [];

      const getValue = (name) =>
        items.find((item) => item.Name === name)?.Value;

      payment.mpesaReceiptNumber = getValue("MpesaReceiptNumber") || "";
      payment.status = "Paid";
    } else {
      payment.status = "Failed";
    }

    await payment.save();

    await Notification.create({
      recipientRole: "Administrator",
      recipientId: null,
      type: "payment_created",
      title:
        payment.status === "Paid"
          ? "M-Pesa Payment Received"
          : "M-Pesa Payment Failed",
      message: `M-Pesa payment ${payment.paymentId} for KES ${payment.amount} is now ${payment.status}.`,
      data: {
        paymentId: payment._id,
        tenantId: payment.tenantId,
        status: payment.status,
      },
    });

    res.status(200).json({
      ResultCode: 0,
      ResultDesc: "Accepted",
    });
  } catch (error) {
    console.error("STK callback error:", error);

    res.status(200).json({
      ResultCode: 0,
      ResultDesc: "Accepted",
    });
  }
};

/*
|--------------------------------------------------------------------------
| Customer checks payment status (polling)
|--------------------------------------------------------------------------
*/

const checkStatus = async (req, res) => {
  try {
    const payment = await Payment.findOne({
      paymentId: req.params.paymentId,
    });

    if (!payment) {
      return res.status(404).json({
        success: false,
        message: "Payment not found",
      });
    }

    res.json({
      success: true,
      data: {
        status: payment.status,
        mpesaReceiptNumber: payment.mpesaReceiptNumber,
        resultDesc: payment.mpesaResultDesc,
      },
    });
  } catch (error) {
    console.error("Check status error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to check payment status",
    });
  }
};

module.exports = {
  stkPush,
  stkCallback,
  checkStatus,
};