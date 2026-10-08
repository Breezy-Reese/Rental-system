require("dotenv").config();

const bcrypt = require("bcryptjs");

const connectDB = require("./config/db");

const User = require("./models/User");
const Property = require("./models/Property");
const Unit = require("./models/Unit");
const Tenant = require("./models/Tenant");
const Lease = require("./models/Lease");
const Payment = require("./models/Payment");
const Maintenance = require("./models/Maintenance");
const Expense = require("./models/Expense");
const Notification = require("./models/Notification");

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const rand = (min, max) =>
  Math.floor(Math.random() * (max - min + 1)) + min;

const pick = (arr) => arr[rand(0, arr.length - 1)];

const daysAgo = (n) => {
  const d = new Date();
  d.setDate(d.getDate() - n);
  return d;
};

const monthsAgo = (n, day = 5) => {
  const d = new Date();
  d.setMonth(d.getMonth() - n);
  d.setDate(day);
  return d;
};

const pad = (n, len = 3) => String(n).padStart(len, "0");

/*
|--------------------------------------------------------------------------
| Enum-safe value sets
|--------------------------------------------------------------------------
*/

const PAYMENT_METHODS = ["M-Pesa", "Bank Transfer", "Cash", "Other"];

const MAINTENANCE_STATUSES = ["Pending", "Assigned"];
const MAINTENANCE_PRIORITIES = ["Urgent", "High", "Medium"];

const EXPENSE_STATUSES = ["Paid", "Pending"];
const EXPENSE_CATEGORIES = ["Plumbing", "Security", "Electrical"];

const UNIT_TYPES = ["1 Bedroom", "2 Bedroom"];

/*
|--------------------------------------------------------------------------
| Seed
|--------------------------------------------------------------------------
*/

const seedDatabase = async () => {
  try {
    await connectDB();

    console.log("=================================");
    console.log("CLEARING EXISTING SEED DATA");
    console.log("=================================");

    await Notification.deleteMany({});
    await Expense.deleteMany({});
    await Maintenance.deleteMany({});
    await Payment.deleteMany({});
    await Lease.deleteMany({});
    await Unit.deleteMany({});
    await Tenant.deleteMany({});
    await Property.deleteMany({});

    // Remove demo customer accounts only. Keep any other users.
    await User.deleteMany({
      email: {
        $in: [
          "customer@example.com",
          "john@example.com",
          "mary@example.com",
          "peter@example.com",
          "grace@example.com",
          "david@example.com",
          "aisha@example.com",
          "brian@example.com",
          "faith@example.com",
        ],
      },
    });

    console.log("Existing seed records cleared.");

    // ============================================================
    // USERS
    // ============================================================

    /*
     * Basil — special password + phone.
     */
    const basilPassword = await bcrypt.hash("#Basil123", 12);

    /*
     * Everyone else — standard passwords.
     */
    const adminPassword    = await bcrypt.hash("admin123", 12);
    const customerPassword = await bcrypt.hash("customer123", 12);

    // -----------------------------------------------------------
    // Primary admin (Basil) — special password + phone
    // -----------------------------------------------------------

    let basil = await User.findOne({
      email: "basil59mutuku@gmail.com",
    });

    if (!basil) {
      basil = await User.create({
        name: "Basil Mutuku",
        email: "basil59mutuku@gmail.com",
        password: basilPassword,
        phone: "0110665688",
        role: "Administrator",
        status: "Active",
      });

      console.log("✓ Created primary admin: basil59mutuku@gmail.com");
    } else {
      basil.name     = "Basil Mutuku";
      basil.password = basilPassword;
      basil.phone    = "0110665688";
      basil.role     = "Administrator";
      basil.status   = "Active";
      await basil.save();

      console.log("✓ Updated primary admin: basil59mutuku@gmail.com");
    }

    // -----------------------------------------------------------
    // Secondary admin — standard password
    // -----------------------------------------------------------

    let admin = await User.findOne({
      email: "admin@example.com",
    });

    if (!admin) {
      admin = await User.create({
        name: "Property Manager",
        email: "admin@example.com",
        password: adminPassword,
        phone: "0700000000",
        role: "Administrator",
        status: "Active",
      });

      console.log("✓ Created secondary admin: admin@example.com");
    }

    // -----------------------------------------------------------
    // Customers — standard password
    // -----------------------------------------------------------

    const customerDefs = [
      { name: "John Mwangi",   email: "john@example.com",   phone: "0712345678" },
      { name: "Mary Wanjiku",  email: "mary@example.com",   phone: "0723456789" },
      { name: "Peter Kamau",   email: "peter@example.com",  phone: "0734567890" },
      { name: "Grace Njeri",   email: "grace@example.com",  phone: "0745678901" },
      { name: "David Otieno",  email: "david@example.com",  phone: "0756789012" },
      { name: "Aisha Hassan",  email: "aisha@example.com",  phone: "0767890123" },
      { name: "Brian Kiprop",  email: "brian@example.com",  phone: "0778901234" },
      { name: "Faith Achieng", email: "faith@example.com",  phone: "0789012345" },
    ];

    const customers = [];

    for (const def of customerDefs) {
      const u = await User.create({
        name: def.name,
        email: def.email,
        password: customerPassword,
        phone: def.phone,
        role: "Customer",
        status: "Active",
      });
      customers.push(u);
    }

    console.log(`✓ Created ${customers.length} customers`);

    // ============================================================
    // PROPERTIES
    // ============================================================

    const propertiesData = [
      {
        propertyId: "PROP-001",
        name: "Greenview Apartments",
        location: "Nairobi, Kilimani",
        address: "Kilimani, Nairobi",
        description: "Modern residential apartments.",
        totalUnits: 5,
        status: "Active",
      },
      {
        propertyId: "PROP-002",
        name: "Sunrise Estate",
        location: "Mombasa, Nyali",
        address: "Nyali, Mombasa",
        description: "Residential estate near the coast.",
        totalUnits: 4,
        status: "Active",
      },
      {
        propertyId: "PROP-003",
        name: "Palm Heights",
        location: "Kilifi, Kilifi Town",
        address: "Kilifi Town",
        description: "Affordable residential apartments.",
        totalUnits: 4,
        status: "Active",
      },
      {
        propertyId: "PROP-004",
        name: "Karen Gardens",
        location: "Nairobi, Karen",
        address: "Karen, Nairobi",
        description: "Luxury villas in a gated community.",
        totalUnits: 4,
        status: "Active",
      },
      {
        propertyId: "PROP-005",
        name: "Westlands Plaza",
        location: "Nairobi, Westlands",
        address: "Westlands, Nairobi",
        description: "Mixed-use commercial and residential.",
        totalUnits: 4,
        status: "Active",
      },
    ];

    const properties = [];

    for (const p of propertiesData) {
      const doc = await Property.create(p);
      properties.push(doc);
    }

    console.log(`✓ Created ${properties.length} properties`);

    // ============================================================
    // UNITS
    // ============================================================

    const rentForType = (type) => {
      switch (type) {
        case "1 Bedroom": return 22000;
        case "2 Bedroom": return 35000;
        default:          return 25000;
      }
    };

    const units = [];
    let unitCounter = 100;

    for (const property of properties) {
      const count = property.totalUnits || 4;

      for (let i = 1; i <= count; i++) {
        unitCounter++;

        const letter = String.fromCharCode(64 + Math.min(i, 26));
        const unitNumber = `${letter}-${i}0${rand(1, 9)}`;

        const type = pick(UNIT_TYPES);
        const rent = rentForType(type);

        const u = await Unit.create({
          unitId: `UNIT-${pad(unitCounter, 5)}`,
          propertyId: property._id,
          unitNumber,
          type,
          rent,
          status: "Vacant",
          tenantId: null,
        });

        units.push(u);
      }
    }

    console.log(`✓ Created ${units.length} units`);

    // ============================================================
    // TENANTS  (assign customers to units)
    // ============================================================

    const tenants = [];

    for (let i = 0; i < customers.length; i++) {
      const customer = customers[i];
      const unit = units[i];

      const tenant = await Tenant.create({
        tenantId: `TEN-${pad(120 + i, 5)}`,
        userId: customer._id,
        name: customer.name,
        email: customer.email,
        phone: customer.phone,
        propertyId: unit.propertyId,
        unitId: unit._id,
        status: "Active",
      });

      unit.status = "Occupied";
      unit.tenantId = tenant._id;
      await unit.save();

      tenants.push(tenant);
    }

    console.log(`✓ Created ${tenants.length} tenants`);

    // ============================================================
    // LEASES
    // ============================================================

    const leases = [];

    for (let i = 0; i < tenants.length; i++) {
      const tenant = tenants[i];
      const unit = units[i];

      const start = monthsAgo(rand(3, 12));
      const end = new Date(start);
      end.setMonth(end.getMonth() + 12);

      const lease = await Lease.create({
        leaseId: `LS-${pad(120 + i, 5)}`,
        tenantId: tenant._id,
        propertyId: unit.propertyId,
        unitId: unit._id,
        startDate: start,
        endDate: end,
        rent: unit.rent,
        deposit: unit.rent * 2,
        status: "Active",
      });

      leases.push(lease);
    }

    console.log(`✓ Created ${leases.length} leases`);

    // ============================================================
    // PAYMENTS  (6 months per lease → feeds the revenue chart)
    // ============================================================

    let paymentCounter = 10000;
    let paymentCount = 0;

    for (const lease of leases) {
      for (let m = 5; m >= 0; m--) {
        paymentCounter++;
        paymentCount++;

        const paid = Math.random() > 0.15;

        await Payment.create({
          paymentId: `PAY-${paymentCounter}`,
          tenantId: lease.tenantId,
          customerId: null,
          leaseId: lease._id,
          amount: lease.rent,
          paymentMethod: pick(PAYMENT_METHODS),
          reference: `REF-${paymentCounter}`,
          status: paid ? "Paid" : "Pending",
          paymentDate: monthsAgo(m, rand(1, 28)),
          submittedBy: null,
        });
      }
    }

    console.log(`✓ Created ${paymentCount} payments`);

    // ============================================================
    // MAINTENANCE
    // ============================================================

    const maintenanceIssues = [
      { issue: "Broken water pipe",  priority: "Urgent" },
      { issue: "Faulty electricity", priority: "High" },
      { issue: "Leaking shower",     priority: "Medium" },
      { issue: "Broken door lock",   priority: "High" },
      { issue: "Clogged toilet",     priority: "Medium" },
      { issue: "Broken window",      priority: "Medium" },
      { issue: "Pest infestation",   priority: "High" },
    ];

    for (let i = 0; i < 15; i++) {
      const tenant = pick(tenants);
      const unit = units.find(
        (u) => String(u.tenantId) === String(tenant._id)
      ) || units[0];

      const def = pick(maintenanceIssues);
      const status = pick(MAINTENANCE_STATUSES);

      await Maintenance.create({
        maintenanceId: `MNT-${pad(i + 1, 3)}`,
        tenantId: tenant._id,
        propertyId: unit.propertyId,
        unitId: unit._id,
        issue: def.issue,
        description: `${def.issue}. Reported by ${tenant.name}.`,
        priority: def.priority,
        status,
        createdAt: daysAgo(rand(1, 90)),
      });
    }

    console.log("✓ Created 15 maintenance requests");

    // ============================================================
    // EXPENSES
    // ============================================================

    for (let i = 0; i < 20; i++) {
      const property = pick(properties);
      const category = pick(EXPENSE_CATEGORIES);
      const status = pick(EXPENSE_STATUSES);

      await Expense.create({
        expenseId: `EXP-${pad(i + 1, 3)}`,
        propertyId: property._id,
        category,
        description: `${category} — ${property.name}`,
        amount: rand(5, 60) * 1000,
        expenseDate: daysAgo(rand(1, 180)),
        status,
      });
    }

    console.log("✓ Created 20 expenses");

    // ============================================================
    // NOTIFICATIONS
    // ============================================================

    const adminNotifications = [
      {
        title: "New Maintenance Request",
        message: "A tenant submitted a maintenance request.",
        type: "maintenance_created",
      },
      {
        title: "Payment Received",
        message: "A rental payment has been recorded.",
        type: "payment_recorded",
      },
    ];

    const customerNotifications = [
      {
        title: "Payment Recorded",
        message: "Your payment has been recorded.",
        type: "payment_recorded",
      },
      {
        title: "Maintenance Request Updated",
        message: "Your maintenance request has been updated.",
        type: "maintenance_created",
      },
    ];

    for (let i = 0; i < 12; i++) {
      const def = pick(adminNotifications);

      await Notification.create({
        recipientRole: "Administrator",
        recipientId: null,
        type: def.type,
        title: def.title,
        message: def.message,
        data: {},
        read: Math.random() > 0.5,
        createdAt: daysAgo(rand(0, 30)),
      });
    }

    for (let i = 0; i < 24; i++) {
      const customer = pick(customers);
      const def = pick(customerNotifications);

      await Notification.create({
        recipientRole: "Customer",
        recipientId: customer._id,
        type: def.type,
        title: def.title,
        message: def.message,
        data: {},
        read: Math.random() > 0.4,
        createdAt: daysAgo(rand(0, 30)),
      });
    }

    console.log("✓ Created 36 notifications");

    // ============================================================
    // SUMMARY
    // ============================================================

    const counts = {
      Users:         await User.countDocuments(),
      Properties:    await Property.countDocuments(),
      Units:         await Unit.countDocuments(),
      Tenants:       await Tenant.countDocuments(),
      Leases:        await Lease.countDocuments(),
      Payments:      await Payment.countDocuments(),
      Maintenance:   await Maintenance.countDocuments(),
      Expenses:      await Expense.countDocuments(),
      Notifications: await Notification.countDocuments(),
    };

    console.log("");
    console.log("=================================");
    console.log("DATABASE SEEDED SUCCESSFULLY");
    console.log("=================================");
    console.log("");
    console.log("PRIMARY ADMIN");
    console.log("Email:    basil59mutuku@gmail.com");
    console.log("Password: #Basil123");
    console.log("Phone:    0110665688");
    console.log("");
    console.log("SECONDARY ADMIN");
    console.log("Email:    admin@example.com");
    console.log("Password: admin123");
    console.log("");
    console.log("CUSTOMERS");
    console.log("Emails:   john@ / mary@ / peter@ / grace@ /");
    console.log("          david@ / aisha@ / brian@ / faith@example.com");
    console.log("Password: customer123");
    console.log("");
    console.log("COUNTS");
    Object.entries(counts).forEach(([k, v]) => {
      console.log(`  ${k}: ${v}`);
    });
    console.log("=================================");

    process.exit(0);
  } catch (error) {
    console.error("");
    console.error("=================================");
    console.error("DATABASE SEED FAILED");
    console.error("=================================");
    console.error(error);
    process.exit(1);
  }
};

seedDatabase();