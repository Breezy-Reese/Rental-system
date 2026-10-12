
const Payment = require("../models/Payment");
const Tenant = require("../models/Tenant");
const Lease = require("../models/Lease");
const Notification = require("../models/Notification");

const { initiateStkPush } = require("../services/mpesa");

/*
|--------------------------------------------------------------------------
| Normalize Kenyan mobile numbers
|--------------------------------------------------------------------------
| Accepts:
| 0712345678
| 0112345678
| 254712345678
| 254112345678
| +254712345678
| +254 112 345 678
|
| Sends to Safaricom as: 254XXXXXXXXX
|--------------------------------------------------------------------------
*/

function normalizeKenyanPhone(phone) {
  let number = String(phone || "").replace(/\D/g, "");

  if (number.startsWith("0")) {
    number = "254" + number.slice(1);
  }

  if (!/^254[17]\d{8}$/.test(number)) {
    throw new Error(
      "Enter a valid Kenyan mobile number, such as 0712345678 or 0112345678."
    );
  }

  return number;
}

/*
|--------------------------------------------------------------------------
| Customer initiates STK Push
|--------------------------------------------------------------------------
*/

const stkPush = async (req, res) => {
  try {
    const { phoneNumber, amount, leaseId } = req.body;

    const userId = req.user?.id || req.user?._id;

    if (!userId) {
      return res.status(401).json({
        success: false,
        message: "Please log in again to make a payment.",
      });
    }

    if (!phoneNumber || amount === undefined || amount === null || amount === "") {
      return res.status(400).json({
        success: false,
        message: "Phone number and amount are required.",
      });
    }

    const paymentAmount = Number(amount);

    if (!Number.isFinite(paymentAmount) || paymentAmount <= 0) {
      return res.status(400).json({
        success: false,
        message: "Enter a valid amount greater than zero.",
      });
    }

    let normalizedPhone;

    try {
      normalizedPhone = normalizeKenyanPhone(phoneNumber);
    } catch (error) {
      return res.status(400).json({
        success: false,
        message: error.message,
      });
    }

    const tenant = await Tenant.findOne({ userId });

    if (!tenant) {
      return res.status(404).json({
        success: false,
        message: "Customer tenant record not found.",
      });
    }

    let lease = null;

    if (leaseId) {
      lease = await Lease.findById(leaseId);

      if (
        !lease ||
        !lease.tenantId ||
        String(lease.tenantId) !== String(tenant._id)
      ) {
        return res.status(403).json({
          success: false,
          message: "The selected lease does not belong to your account.",
        });
      }
    } else {
      lease = await Lease.findOne({
        tenantId: tenant._id,
        status: "Active",
      }).sort({ createdAt: -1 });
    }

    // Send the normalized customer number to the M-Pesa service.
    console.log(
      `[MPESA STK] Initiating request for customer ${userId}; phone ending ${normalizedPhone.slice(-4)}`
    );

    const stkResponse = await initiateStkPush({
      phoneNumber: normalizedPhone,
      amount: Math.round(paymentAmount),
      accountReference: tenant.tenantId || "PropertyPro",
      transactionDesc: "Rent payment",
    });

    if (String(stkResponse?.ResponseCode) !== "0") {
      console.error("[MPESA STK] Safaricom rejected the request:", {
        responseCode: stkResponse?.ResponseCode,
        responseDescription: stkResponse?.ResponseDescription,
        customerMessage: stkResponse?.CustomerMessage,
      });

      return res.status(400).json({
        success: false,
        message:
          stkResponse?.CustomerMessage ||
          stkResponse?.ResponseDescription ||
          "Safaricom could not initiate the payment. Please try again.",
      });
    }

    if (!stkResponse.CheckoutRequestID) {
      console.error(
        "[MPESA STK] Missing CheckoutRequestID in Safaricom response."
      );

      return res.status(502).json({
        success: false,
        message:
          "Safaricom did not return a checkout reference. Please try again.",
      });
    }

    // Create a pending payment; the Safaricom callback updates its status.
    const paymentId =
      "MP-" +
      Date.now() +
      "-" +
      Math.floor(1000 + Math.random() * 9000);

    const payment = await Payment.create({
      paymentId,
      tenantId: tenant._id,
      customerId: userId,
      leaseId: lease ? lease._id : null,
      amount: Math.round(paymentAmount),
      paymentMethod: "M-Pesa",
      status: "Pending",
      paymentDate: new Date(),
      submittedBy: userId,
      mpesaPhoneNumber: normalizedPhone,
      merchantRequestId: stkResponse.MerchantRequestID,
      checkoutRequestId: stkResponse.CheckoutRequestID,
    });

    return res.status(200).json({
      success: true,
      message:
        "STK Push requested. Check the phone number entered and follow the M-Pesa prompt.",
      data: {
        paymentId: payment.paymentId,
        checkoutRequestId: stkResponse.CheckoutRequestID,
        phoneNumber: normalizedPhone,
        status: payment.status,
      },
    });
  } catch (error) {
    console.error(
      "[MPESA STK] Error:",
      error.response?.data || error.message
    );

    return res.status(500).json({
      success: false,
      message:
        error.message?.includes("timeout")
          ? "The M-Pesa service timed out. Please try again."
          : "Failed to initiate M-Pesa payment. Please try again.",
    });
  }
};

/*
|--------------------------------------------------------------------------
| Safaricom STK callback
|--------------------------------------------------------------------------
*/

const stkCallback = async (req, res) => {
  try {
    const callback = req.body?.Body?.stkCallback;

    if (!callback) {
      console.error("[MPESA CALLBACK] Invalid callback payload.");

      return res.status(400).json({
        success: false,
        message: "Invalid callback payload.",
      });
    }

    const {
      CheckoutRequestID,
      ResultCode,
      ResultDesc,
      CallbackMetadata,
    } = callback;

    console.log("[MPESA CALLBACK] Received result:", {
      checkoutRequestId: CheckoutRequestID,
      resultCode: ResultCode,
      resultDescription: ResultDesc,
    });

    const payment = await Payment.findOne({
      checkoutRequestId: CheckoutRequestID,
    });

    if (!payment) {
      console.error(
        "[MPESA CALLBACK] No payment found for checkout:",
        CheckoutRequestID
      );

      // Acknowledge the callback to Safaricom.
      return res.status(200).json({
        ResultCode: 0,
        ResultDesc: "Accepted",
      });
    }

    const wasPending = payment.status === "Pending";

    payment.mpesaResultCode = ResultCode;
    payment.mpesaResultDesc = ResultDesc || "";

    if (Number(ResultCode) === 0) {
      const items = CallbackMetadata?.Item || [];

      const getValue = (name) =>
        items.find((item) => item.Name === name)?.Value;

      payment.mpesaReceiptNumber =
        getValue("MpesaReceiptNumber") || payment.mpesaReceiptNumber || "";

      payment.status = "Paid";

      const callbackPhone = getValue("PhoneNumber");

      if (callbackPhone) {
        payment.mpesaPhoneNumber = String(callbackPhone);
      }

      const callbackAmount = getValue("Amount");

      if (callbackAmount !== undefined && callbackAmount !== null) {
        payment.mpesaPaidAmount = Number(callbackAmount);
      }
    } else {
      payment.status = "Failed";
    }

    await payment.save();

    // Avoid sending duplicate notifications for repeated callbacks.
    if (wasPending) {
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
          mpesaReceiptNumber: payment.mpesaReceiptNumber || "",
        },
      });
    }

    console.log("[MPESA CALLBACK] Payment updated:", {
      paymentId: payment.paymentId,
      status: payment.status,
      receipt: payment.mpesaReceiptNumber || null,
    });

    return res.status(200).json({
      ResultCode: 0,
      ResultDesc: "Accepted",
    });
  } catch (error) {
    console.error("[MPESA CALLBACK] Error:", error.message);

    // Acknowledge the callback so Safaricom receives an HTTP response.
    return res.status(200).json({
      ResultCode: 0,
      ResultDesc: "Accepted",
    });
  }
};

/*
|--------------------------------------------------------------------------
| Customer checks payment status
|--------------------------------------------------------------------------
*/

const checkStatus = async (req, res) => {
  try {
    const userId = req.user?.id || req.user?._id;

    if (!userId) {
      return res.status(401).json({
        success: false,
        message: "Please log in again.",
      });
    }

    // Only allow the customer who initiated this payment to view its status.
    const payment = await Payment.findOne({
      paymentId: req.params.paymentId,
      customerId: userId,
    });

    if (!payment) {
      return res.status(404).json({
        success: false,
        message: "Payment not found for this account.",
      });
    }

    return res.json({
      success: true,
      data: {
        paymentId: payment.paymentId,
        status: payment.status,
        amount: payment.amount,
        mpesaReceiptNumber: payment.mpesaReceiptNumber || "",
        resultDesc: payment.mpesaResultDesc || "",
      },
    });
  } catch (error) {
    console.error("[MPESA STATUS] Error:", error.message);

    return res.status(500).json({
      success: false,
      message: "Failed to check payment status.",
    });
  }
};

module.exports = {
  stkPush,
  stkCallback,
  checkStatus,
};
