const axios = require("axios");

const BASE_URL =
  process.env.MPESA_ENV === "production"
    ? "https://api.safaricom.co.ke"
    : "https://sandbox.safaricom.co.ke";

/*
|--------------------------------------------------------------------------
| Get OAuth access token
|--------------------------------------------------------------------------
*/

const getAccessToken = async () => {
  const auth = Buffer.from(
    `${process.env.MPESA_CONSUMER_KEY}:${process.env.MPESA_CONSUMER_SECRET}`
  ).toString("base64");

  const response = await axios.get(
    `${BASE_URL}/oauth/v1/generate?grant_type=client_credentials`,
    {
      headers: {
        Authorization: `Basic ${auth}`,
      },
    }
  );

  return response.data.access_token;
};

/*
|--------------------------------------------------------------------------
| Build the STK Push password
|--------------------------------------------------------------------------
| Safaricom requires: base64(ShortCode + Passkey + Timestamp)
*/

const getTimestamp = () => {
  const date = new Date();

  const pad = (n) => String(n).padStart(2, "0");

  return (
    date.getFullYear().toString() +
    pad(date.getMonth() + 1) +
    pad(date.getDate()) +
    pad(date.getHours()) +
    pad(date.getMinutes()) +
    pad(date.getSeconds())
  );
};

const getPassword = (timestamp) => {
  const raw =
    process.env.MPESA_SHORT_CODE +
    process.env.MPESA_PASSKEY +
    timestamp;

  return Buffer.from(raw).toString("base64");
};

/*
|--------------------------------------------------------------------------
| Normalize phone number to 2547XXXXXXXX format
|--------------------------------------------------------------------------
*/

const normalizePhone = (phone) => {
  let cleaned = String(phone).trim().replace(/\s+/g, "");

  if (cleaned.startsWith("+")) {
    cleaned = cleaned.slice(1);
  }

  if (cleaned.startsWith("0")) {
    cleaned = "254" + cleaned.slice(1);
  }

  if (cleaned.startsWith("7") || cleaned.startsWith("1")) {
    cleaned = "254" + cleaned;
  }

  return cleaned;
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
  const accessToken = await getAccessToken();

  const timestamp = getTimestamp();
  const password = getPassword(timestamp);
  const normalizedPhone = normalizePhone(phoneNumber);

  const response = await axios.post(
    `${BASE_URL}/mpesa/stkpush/v1/processrequest`,
    {
      BusinessShortCode: process.env.MPESA_SHORT_CODE,
      Password: password,
      Timestamp: timestamp,
      TransactionType: "CustomerPayBillOnline",
      Amount: Math.round(Number(amount)),
      PartyA: normalizedPhone,
      PartyB: process.env.MPESA_SHORT_CODE,
      PhoneNumber: normalizedPhone,
      CallBackURL: process.env.MPESA_CALLBACK_URL,
      AccountReference: accountReference || "PropertyPro",
      TransactionDesc: transactionDesc || "Rent payment",
    },
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json",
      },
    }
  );

  return response.data;
};

/*
|--------------------------------------------------------------------------
| Query STK Push status (optional polling)
|--------------------------------------------------------------------------
*/

const queryStkStatus = async (checkoutRequestId) => {
  const accessToken = await getAccessToken();

  const timestamp = getTimestamp();
  const password = getPassword(timestamp);

  const response = await axios.post(
    `${BASE_URL}/mpesa/stkpushquery/v1/query`,
    {
      BusinessShortCode: process.env.MPESA_SHORT_CODE,
      Password: password,
      Timestamp: timestamp,
      CheckoutRequestID: checkoutRequestId,
    },
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json",
      },
    }
  );

  return response.data;
};

module.exports = {
  getAccessToken,
  initiateStkPush,
  queryStkStatus,
  normalizePhone,
};