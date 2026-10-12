
const axios = require("axios");

const BASE_URL =
  String(process.env.MPESA_ENV || "sandbox").toLowerCase() === "production"
    ? "https://api.safaricom.co.ke"
    : "https://sandbox.safaricom.co.ke";

/*
|--------------------------------------------------------------------------
| Validate required M-Pesa configuration
|--------------------------------------------------------------------------
*/

const requireMpesaConfig = () => {
  const required = [
    "MPESA_CONSUMER_KEY",
    "MPESA_CONSUMER_SECRET",
    "MPESA_SHORT_CODE",
    "MPESA_PASSKEY",
    "MPESA_CALLBACK_URL",
  ];

  const missing = required.filter(
    (key) => !String(process.env[key] || "").trim()
  );

  if (missing.length > 0) {
    throw new Error(
      `Missing M-Pesa environment variables: ${missing.join(", ")}`
    );
  }
};

/*
|--------------------------------------------------------------------------
| Normalize and validate Kenyan mobile numbers
|--------------------------------------------------------------------------
| Accepted:
| 0712345678
| 0112345678
| 254712345678
| 254112345678
| +254712345678
| +254 112 345 678
|
| Output: 254XXXXXXXXX
|--------------------------------------------------------------------------
*/

const normalizePhone = (phone) => {
  let number = String(phone || "").replace(/\D/g, "");

  if (number.startsWith("0")) {
    number = "254" + number.slice(1);
  } else if (number.startsWith("7") || number.startsWith("1")) {
    number = "254" + number;
  }

  if (!/^254[17]\d{8}$/.test(number)) {
    throw new Error(
      "Invalid Kenyan phone number. Enter 0712345678, 0112345678, or a number beginning with +254."
    );
  }

  return number;
};

/*
|--------------------------------------------------------------------------
| Get OAuth access token
|--------------------------------------------------------------------------
*/

const getAccessToken = async () => {
  requireMpesaConfig();

  const auth = Buffer.from(
    `${process.env.MPESA_CONSUMER_KEY}:${process.env.MPESA_CONSUMER_SECRET}`
  ).toString("base64");

  try {
    const response = await axios.get(
      `${BASE_URL}/oauth/v1/generate?grant_type=client_credentials`,
      {
        headers: {
          Authorization: `Basic ${auth}`,
        },
        timeout: 20000,
      }
    );

    if (!response.data?.access_token) {
      throw new Error("Safaricom did not return an access token.");
    }

    return response.data.access_token;
  } catch (error) {
    console.error(
      "[MPESA AUTH] Failed:",
      error.response?.data || error.message
    );

    throw new Error("Could not authenticate with Safaricom M-Pesa.");
  }
};

/*
|--------------------------------------------------------------------------
| Generate timestamp in Kenya time (EAT)
|--------------------------------------------------------------------------
*/

const getTimestamp = () => {
  const parts = new Intl.DateTimeFormat("en-GB", {
    timeZone: "Africa/Nairobi",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hourCycle: "h23",
  }).formatToParts(new Date());

  const values = {};

  for (const part of parts) {
    if (part.type !== "literal") {
      values[part.type] = part.value;
    }
  }

  return (
    values.year +
    values.month +
    values.day +
    values.hour +
    values.minute +
    values.second
  );
};

/*
|--------------------------------------------------------------------------
| Build the STK Push password
|--------------------------------------------------------------------------
| Base64(ShortCode + Passkey + Timestamp)
|--------------------------------------------------------------------------
*/

const getPassword = (timestamp) => {
  const shortcode = String(process.env.MPESA_SHORT_CODE || "").trim();
  const passkey = String(process.env.MPESA_PASSKEY || "").trim();

  if (!shortcode || !passkey || !timestamp) {
    throw new Error("Cannot generate the M-Pesa password. Check configuration.");
  }

  return Buffer.from(shortcode + passkey + timestamp).toString("base64");
};

/*
|--------------------------------------------------------------------------
| Initiate STK Push
|--------------------------------------------------------------------------
*/

const initiateStkPush = async ({
  phoneNumber,
  amount,
  accountReference,
  transactionDesc,
}) => {
  requireMpesaConfig();

  const normalizedPhone = normalizePhone(phoneNumber);
  const numericAmount = Number(amount);

  if (!Number.isFinite(numericAmount) || numericAmount < 1) {
    throw new Error("M-Pesa payment amount must be at least KES 1.");
  }

  const roundedAmount = Math.round(numericAmount);
  const accessToken = await getAccessToken();
  const timestamp = getTimestamp();
  const password = getPassword(timestamp);

  const payload = {
    BusinessShortCode: String(process.env.MPESA_SHORT_CODE).trim(),
    Password: password,
    Timestamp: timestamp,

    // This transaction type is for PayBill-based STK Push.
    TransactionType: "CustomerPayBillOnline",

    Amount: roundedAmount,
    PartyA: normalizedPhone,
    PartyB: String(process.env.MPESA_SHORT_CODE).trim(),
    PhoneNumber: normalizedPhone,
    CallBackURL: String(process.env.MPESA_CALLBACK_URL).trim(),
    AccountReference: String(accountReference || "PropertyPro").slice(0, 12),
    TransactionDesc: String(transactionDesc || "Rent payment").slice(0, 13),
  };

  console.log("[MPESA STK] Sending request:", {
    environment:
      String(process.env.MPESA_ENV || "sandbox").toLowerCase(),
    phoneNumber: `${normalizedPhone.slice(0, 5)}****${normalizedPhone.slice(-3)}`,
    amount: roundedAmount,
    callbackConfigured: Boolean(payload.CallBackURL),
  });

  try {
    const response = await axios.post(
      `${BASE_URL}/mpesa/stkpush/v1/processrequest`,
      payload,
      {
        headers: {
          Authorization: `Bearer ${accessToken}`,
          "Content-Type": "application/json",
        },
        timeout: 30000,
      }
    );

    console.log("[MPESA STK] Safaricom response:", {
      responseCode: response.data?.ResponseCode,
      responseDescription: response.data?.ResponseDescription,
      customerMessage: response.data?.CustomerMessage,
      checkoutRequestId: response.data?.CheckoutRequestID,
    });

    return response.data;
  } catch (error) {
    console.error(
      "[MPESA STK] Request failed:",
      error.response?.data || error.message
    );

    throw new Error(
      error.response?.data?.errorMessage ||
        error.response?.data?.ResponseDescription ||
        "Safaricom could not initiate the STK Push."
    );
  }
};

/*
|--------------------------------------------------------------------------
| Query STK Push status
|--------------------------------------------------------------------------
*/

const queryStkStatus = async (checkoutRequestId) => {
  requireMpesaConfig();

  if (!checkoutRequestId) {
    throw new Error("CheckoutRequestID is required.");
  }

  const accessToken = await getAccessToken();
  const timestamp = getTimestamp();
  const password = getPassword(timestamp);

  try {
    const response = await axios.post(
      `${BASE_URL}/mpesa/stkpushquery/v1/query`,
      {
        BusinessShortCode: String(process.env.MPESA_SHORT_CODE).trim(),
        Password: password,
        Timestamp: timestamp,
        CheckoutRequestID: checkoutRequestId,
      },
      {
        headers: {
          Authorization: `Bearer ${accessToken}`,
          "Content-Type": "application/json",
        },
        timeout: 20000,
      }
    );

    return response.data;
  } catch (error) {
    console.error(
      "[MPESA QUERY] Failed:",
      error.response?.data || error.message
    );

    throw new Error("Could not check the M-Pesa transaction status.");
  }
};

module.exports = {
  getAccessToken,
  initiateStkPush,
  queryStkStatus,
  normalizePhone,
  getTimestamp,
};
