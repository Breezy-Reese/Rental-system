const Payment = require("../models/Payment");
const Tenant = require("../models/Tenant");
const Lease = require("../models/Lease");
const Notification = require("../models/Notification");

const { initiateStkPush } = require("../services/mpesa");

/**
 * Get the authenticated user's ID.
 */
function getUserId(req) {
  return req.user?.id || req.user?._id || null;
}

/**
 * Normalize Kenyan mobile numbers.
 *
 * Accepted:
 * 0712345678
 * 0112345678
 * 254712345678
 * 254112345678
 * +254712345678
 * +254 112 345 678
 *
 * Returned format: 254XXXXXXXXX
 */
function normalizeKenyanPhone(phone) {
  let number = String(phone || "").replace(/\D/g, "");

  if (number.startsWith("0")) {
    number = "254" + number.slice(1);
  } else if (
    number.startsWith("7") ||
    number.startsWith("1")
  ) {
    number = "254" + number;
  }

  if (!/^254[17]\d{8}$/.test(number)) {
    throw new Error(
      "Enter a valid Kenyan M-Pesa number, such as 0712345678 or +254712345678."
    );
  }

  return number;
}

/**
 * Customer initiates an M-Pesa STK Push.
 *
 * POST /api/mpesa/stk-push
 *
 * Body:
 * {
 *   "phoneNumber": "0712345678",
 *   "amount": 25000,
 *   "leaseId": "..."
 * }
 */
const stkPush = async (req, res) => {
  try {
    const userId = getUserId(req);
    const { phoneNumber, amount, leaseId } = req.body || {};

    if (!userId) {
      return res.status(401).json({
        success: false,
        message: "Your session has expired. Please log in again.",
      });
    }

    if (
      phoneNumber === undefined ||
      phoneNumber === null ||
      String(phoneNumber).trim() === "" ||
      amount === undefined ||
      amount === null ||
      String(amount).trim() === ""
    ) {
      return res.status(400).json({
        success: false,
        message: "M-Pesa phone number and payment amount are required.",
      });
    }

    const paymentAmount = Number(amount);

    if (
      !Number.isFinite(paymentAmount) ||
      paymentAmount <= 0 ||
      !Number.isSafeInteger(Math.round(paymentAmount))
    ) {
      return res.status(400).json({
        success: false,
        message: "Enter a valid payment amount greater than zero.",
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
        message: "Your customer tenant record could not be found.",
      });
    }

    let lease = null;

    if (leaseId) {
      if (!/^[a-f\d]{24}$/i.test(String(leaseId))) {
        return res.status(400).json({
          success: false,
          message: "The selected lease ID is invalid.",
        });
      }

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

    if (!lease) {
      return res.status(400).json({
        success: false,
        message:
          "No active lease was found for your account. Please contact the administrator.",
      });
    }

    const finalAmount = Math.round(paymentAmount);

    console.log("[MPESA STK] Request started:", {
      customerId: String(userId),
      leaseId: String(lease._id),
      phoneEnding: normalizedPhone.slice(-4),
      amount: finalAmount,
    });

    const stkResponse = await initiateStkPush({
      phoneNumber: normalizedPhone,
      amount: finalAmount,
      accountReference: tenant.tenantId || "PropertyPro",
      transactionDesc: "Rent payment",
    });

    if (String(stkResponse?.ResponseCode) !== "0") {
      console.error("[MPESA STK] Safaricom rejected request:", {
        responseCode: stkResponse?.ResponseCode,
        responseDescription: stkResponse?.ResponseDescription,
      });

      return res.status(400).json({
        success: false,
        message:
          stkResponse?.CustomerMessage ||
          stkResponse?.ResponseDescription ||
          "Safaricom could not initiate the payment. Please try again.",
      });
    }

    if (
      !stkResponse.CheckoutRequestID ||
      !stkResponse.MerchantRequestID
    ) {
      console.error(
        "[MPESA STK] Safaricom response is missing checkout identifiers."
      );

      return res.status(502).json({
        success: false,
        message:
          "Safaricom did not return the payment reference. Please try again.",
      });
    }

    // Avoid creating another pending payment for the same checkout request.
    const existingPayment = await Payment.findOne({
      checkoutRequestId: stkResponse.CheckoutRequestID,
    });

    if (existingPayment) {
      return res.status(200).json({
        success: true,
        message:
          "This M-Pesa payment request has already been recorded. Check your phone for the prompt.",
        data: {
          paymentId: existingPayment.paymentId,
          checkoutRequestId: existingPayment.checkoutRequestId,
          phoneNumber: existingPayment.mpesaPhoneNumber,
          status: existingPayment.status,
        },
      });
    }

    const paymentId =
      "MP-" +
      Date.now() +
      "-" +
      Math.floor(1000 + Math.random() * 9000);

    const payment = await Payment.create({
      paymentId,
      tenantId: tenant._id,
      customerId: userId,
      leaseId: lease._id,
      amount: finalAmount,
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
        "M-Pesa prompt requested. Check your phone and follow the instructions to complete the payment.",
      data: {
        paymentId: payment.paymentId,
        checkoutRequestId: payment.checkoutRequestId,
        phoneNumber: payment.mpesaPhoneNumber,
        status: payment.status,
      },
    });
  } catch (error) {
    console.error(
      "[MPESA STK] Error:",
      error.response?.data || error.message
    );

    const status = error.response?.status;

    if (status === 401 || status === 403) {
      return res.status(502).json({
        success: false,
        message:
          "Safaricom rejected the API credentials or permissions. Check the M-Pesa configuration in your server environment.",
      });
    }

    return res.status(500).json({
      success: false,
      message: String(error.message || "").toLowerCase().includes("timeout")
        ? "The M-Pesa service timed out. Please try again."
        : "Failed to initiate M-Pesa payment. Please check the server logs and try again.",
    });
  }
};

/**
 * Safaricom sends the result to this public callback.
 *
 * POST /api/mpesa/callback
 *
 * This endpoint must not require customer JWT authentication.
 */
const stkCallback = async (req, res) => {
  try {
    const callback = req.body?.Body?.stkCallback;

    if (!callback || !callback.CheckoutRequestID) {
      console.error("[MPESA CALLBACK] Invalid callback payload.");

      return res.status(400).json({
        ResultCode: 1,
        ResultDesc: "Invalid callback payload.",
      });
    }

    const {
      CheckoutRequestID,
      ResultCode,
      ResultDesc,
      CallbackMetadata,
    } = callback;

    console.log("[MPESA CALLBACK] Received:", {
      checkoutRequestId: CheckoutRequestID,
      resultCode: ResultCode,
      resultDescription: ResultDesc,
    });

    const payment = await Payment.findOne({
      checkoutRequestId: CheckoutRequestID,
    });

    if (!payment) {
      console.error(
        "[MPESA CALLBACK] Payment not found for checkout:",
        CheckoutRequestID
      );

      // Acknowledge Safaricom's callback. Investigate the unmatched
      // checkout reference in the server logs.
      return res.status(200).json({
        ResultCode: 0,
        ResultDesc: "Accepted",
      });
    }

    // Do not downgrade a payment that has already been marked Paid.
    if (payment.status === "Paid") {
      return res.status(200).json({
        ResultCode: 0,
        ResultDesc: "Accepted",
      });
    }

    const previousStatus = payment.status;
    const successful = Number(ResultCode) === 0;

    payment.mpesaResultCode = Number(ResultCode);
    payment.mpesaResultDesc = String(ResultDesc || "");

    if (successful) {
      const items = CallbackMetadata?.Item || [];

      const getValue = (name) =>
        items.find((item) => item.Name === name)?.Value;

      const receipt = getValue("MpesaReceiptNumber");
      const callbackPhone = getValue("PhoneNumber");
      const callbackAmount = getValue("Amount");

      if (receipt) {
        payment.mpesaReceiptNumber = String(receipt);
      }

      if (callbackPhone !== undefined && callbackPhone !== null) {
        payment.mpesaPhoneNumber = String(callbackPhone);
      }

      if (callbackAmount !== undefined && callbackAmount !== null) {
        payment.mpesaPaidAmount = Number(callbackAmount);
      }

      // Mark as Paid only when Safaricom reports success.
      payment.status = "Paid";
    } else {
      payment.status = "Failed";
    }

    await payment.save();

    // Notify administrators only when a pending payment changes status.
    if (previousStatus === "Pending") {
      try {
        await Notification.create({
          recipientRole: "Administrator",
          recipientId: null,
          type: "payment_created",
          title:
            payment.status === "Paid"
              ? "M-Pesa Payment Received"
              : "M-Pesa Payment Failed",
          message:
            `M-Pesa payment ${payment.paymentId} for KES ${payment.amount} ` +
            `is now ${payment.status}.`,
          data: {
            paymentId: payment._id,
            tenantId: payment.tenantId,
            customerId: payment.customerId,
            status: payment.status,
            mpesaReceiptNumber: payment.mpesaReceiptNumber || "",
          },
        });
      } catch (notificationError) {
        // A notification failure should not undo a confirmed payment.
        console.error(
          "[MPESA CALLBACK] Notification failed:",
          notificationError.message
        );
      }
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

    // Log the error for investigation. Safaricom callback retries depend
    // on its own callback policy and configuration.
    return res.status(500).json({
      ResultCode: 1,
      ResultDesc: "Callback processing failed.",
    });
  }
};

/**
 * Customer checks the status of their own payment.
 *
 * GET /api/mpesa/status/:paymentId
 */
const checkStatus = async (req, res) => {
  try {
    const userId = getUserId(req);

    if (!userId) {
      return res.status(401).json({
        success: false,
        message: "Your session has expired. Please log in again.",
      });
    }

    const payment = await Payment.findOne({
      paymentId: req.params.paymentId,
      customerId: userId,
    });

    if (!payment) {
      return res.status(404).json({
        success: false,
        message: "Payment not found for your account.",
      });
    }

    return res.status(200).json({
      success: true,
      data: {
        paymentId: payment.paymentId,
        status: payment.status,
        amount: payment.amount,
        paymentMethod: payment.paymentMethod,
        mpesaPhoneNumber: payment.mpesaPhoneNumber || "",
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