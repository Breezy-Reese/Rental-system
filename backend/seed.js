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

        // Keep users so existing accounts are not unnecessarily deleted.
        // Remove demo customer accounts if they exist.
        await User.deleteMany({
            email: {
                $in: [
                    "customer@example.com",
                    "john@example.com",
                    "mary@example.com",
                    "peter@example.com",
                ],
            },
        });

        console.log("Existing seed records cleared.");

        // ============================================================
        // USERS
        // ============================================================

        const adminPassword = await bcrypt.hash(
            "admin123",
            12
        );

        const customerPassword = await bcrypt.hash(
            "customer123",
            12
        );

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
        }

        const johnUser = await User.create({
            name: "John Mwangi",
            email: "john@example.com",
            password: customerPassword,
            phone: "0712345678",
            role: "Customer",
            status: "Active",
        });

        const maryUser = await User.create({
            name: "Mary Wanjiku",
            email: "mary@example.com",
            password: customerPassword,
            phone: "0723456789",
            role: "Customer",
            status: "Active",
        });

        const peterUser = await User.create({
            name: "Peter Kamau",
            email: "peter@example.com",
            password: customerPassword,
            phone: "0734567890",
            role: "Customer",
            status: "Active",
        });

        // ============================================================
        // PROPERTIES
        // ============================================================

        const greenview = await Property.create({
            propertyId: "PROP-001",
            name: "Greenview Apartments",
            location: "Nairobi, Kilimani",
            address: "Kilimani, Nairobi",
            description: "Modern residential apartments.",
            totalUnits: 2,
            status: "Active",
        });

        const sunrise = await Property.create({
            propertyId: "PROP-002",
            name: "Sunrise Estate",
            location: "Mombasa, Nyali",
            address: "Nyali, Mombasa",
            description: "Residential estate near the coast.",
            totalUnits: 1,
            status: "Active",
        });

        const palmHeights = await Property.create({
            propertyId: "PROP-003",
            name: "Palm Heights",
            location: "Kilifi, Kilifi Town",
            address: "Kilifi Town",
            description: "Affordable residential apartments.",
            totalUnits: 1,
            status: "Active",
        });

        // ============================================================
        // TENANTS
        // ============================================================

        const john = await Tenant.create({
            tenantId: "TEN-00124",
            userId: johnUser._id,
            name: "John Mwangi",
            email: "john@example.com",
            phone: "0712345678",
            propertyId: greenview._id,
            status: "Active",
        });

        const mary = await Tenant.create({
            tenantId: "TEN-00125",
            userId: maryUser._id,
            name: "Mary Wanjiku",
            email: "mary@example.com",
            phone: "0723456789",
            propertyId: sunrise._id,
            status: "Active",
        });

        const peter = await Tenant.create({
            tenantId: "TEN-00126",
            userId: peterUser._id,
            name: "Peter Kamau",
            email: "peter@example.com",
            phone: "0734567890",
            propertyId: palmHeights._id,
            status: "Active",
        });

        // ============================================================
        // UNITS
        // ============================================================

        const unit101 = await Unit.create({
            unitId: "UNIT-00101",
            propertyId: greenview._id,
            unitNumber: "A-101",
            type: "2 Bedroom",
            rent: 25000,
            status: "Occupied",
            tenantId: john._id,
        });

        const unit102 = await Unit.create({
            unitId: "UNIT-00102",
            propertyId: greenview._id,
            unitNumber: "A-102",
            type: "1 Bedroom",
            rent: 20000,
            status: "Vacant",
            tenantId: null,
        });

        const unit204 = await Unit.create({
            unitId: "UNIT-00204",
            propertyId: sunrise._id,
            unitNumber: "B-204",
            type: "2 Bedroom",
            rent: 35000,
            status: "Occupied",
            tenantId: mary._id,
        });

        const unit302 = await Unit.create({
            unitId: "UNIT-00302",
            propertyId: palmHeights._id,
            unitNumber: "C-302",
            type: "2 Bedroom",
            rent: 30000,
            status: "Occupied",
            tenantId: peter._id,
        });

        // Update tenants with their units
        john.unitId = unit101._id;
        john.propertyId = greenview._id;

        mary.unitId = unit204._id;
        mary.propertyId = sunrise._id;

        peter.unitId = unit302._id;
        peter.propertyId = palmHeights._id;

        await john.save();
        await mary.save();
        await peter.save();

        // ============================================================
        // LEASES
        // ============================================================

        const johnLease = await Lease.create({
            leaseId: "LS-00124",
            tenantId: john._id,
            propertyId: greenview._id,
            unitId: unit101._id,
            startDate: new Date("2026-01-01"),
            endDate: new Date("2026-12-31"),
            rent: 25000,
            deposit: 50000,
            status: "Active",
        });

        const maryLease = await Lease.create({
            leaseId: "LS-00125",
            tenantId: mary._id,
            propertyId: sunrise._id,
            unitId: unit204._id,
            startDate: new Date("2026-02-01"),
            endDate: new Date("2027-01-31"),
            rent: 35000,
            deposit: 70000,
            status: "Active",
        });

        const peterLease = await Lease.create({
            leaseId: "LS-00126",
            tenantId: peter._id,
            propertyId: palmHeights._id,
            unitId: unit302._id,
            startDate: new Date("2026-03-01"),
            endDate: new Date("2027-02-28"),
            rent: 30000,
            deposit: 60000,
            status: "Active",
        });

        // ============================================================
        // PAYMENTS
        // ============================================================

        await Payment.create({
            paymentId: "PAY-10045",
            tenantId: john._id,
            leaseId: johnLease._id,
            amount: 25000,
            paymentMethod: "M-Pesa",
            reference: "MPESA-JOHN-10045",
            status: "Paid",
            paymentDate: new Date("2026-09-01"),
        });

        await Payment.create({
            paymentId: "PAY-10044",
            tenantId: mary._id,
            leaseId: maryLease._id,
            amount: 35000,
            paymentMethod: "Bank Transfer",
            reference: "BANK-MARY-10044",
            status: "Paid",
            paymentDate: new Date("2026-09-02"),
        });

        await Payment.create({
            paymentId: "PAY-10043",
            tenantId: peter._id,
            leaseId: peterLease._id,
            amount: 30000,
            paymentMethod: "M-Pesa",
            reference: "MPESA-PETER-10043",
            status: "Pending",
            paymentDate: new Date("2026-09-03"),
        });

        // ============================================================
        // MAINTENANCE
        // ============================================================

        await Maintenance.create({
            maintenanceId: "MNT-001",
            tenantId: john._id,
            propertyId: greenview._id,
            unitId: unit101._id,
            issue: "Broken water pipe",
            description: "Water pipe requires repair.",
            priority: "Urgent",
            status: "Pending",
        });

        await Maintenance.create({
            maintenanceId: "MNT-002",
            tenantId: mary._id,
            propertyId: sunrise._id,
            unitId: unit204._id,
            issue: "Faulty electricity",
            description: "Electrical fault reported in the unit.",
            priority: "High",
            status: "Pending",
        });

        await Maintenance.create({
            maintenanceId: "MNT-003",
            tenantId: peter._id,
            propertyId: palmHeights._id,
            unitId: unit302._id,
            issue: "Leaking shower",
            description: "Shower requires plumbing repair.",
            priority: "Medium",
            status: "Assigned",
        });

        // ============================================================
        // EXPENSES
        // ============================================================

        await Expense.create({
            expenseId: "EXP-001",
            propertyId: greenview._id,
            category: "Plumbing",
            description: "Plumbing repairs",
            amount: 35000,
            expenseDate: new Date("2026-09-02"),
            status: "Paid",
        });

        await Expense.create({
            expenseId: "EXP-002",
            propertyId: sunrise._id,
            category: "Security",
            description: "Security services",
            amount: 50000,
            expenseDate: new Date("2026-09-03"),
            status: "Paid",
        });

        await Expense.create({
            expenseId: "EXP-003",
            propertyId: palmHeights._id,
            category: "Electrical",
            description: "Electrical repairs",
            amount: 15000,
            expenseDate: new Date("2026-09-04"),
            status: "Pending",
        });

        // ============================================================
        // NOTIFICATIONS
        // ============================================================

        await Notification.create({
            recipientRole: "Administrator",
            recipientId: null,
            type: "maintenance_created",
            title: "New Maintenance Request",
            message: "John Mwangi submitted a maintenance request.",
            data: {
                maintenanceId: "MNT-001",
            },
            read: false,
        });

        await Notification.create({
            recipientRole: "Customer",
            recipientId: johnUser._id,
            type: "payment_recorded",
            title: "Payment Recorded",
            message: "Your payment of KES 25,000 has been recorded.",
            data: {
                paymentId: "PAY-10045",
            },
            read: false,
        });

        await Notification.create({
            recipientRole: "Customer",
            recipientId: maryUser._id,
            type: "payment_recorded",
            title: "Payment Recorded",
            message: "Your payment of KES 35,000 has been recorded.",
            data: {
                paymentId: "PAY-10044",
            },
            read: false,
        });

        console.log("");
        console.log("=================================");
        console.log("DATABASE SEEDED SUCCESSFULLY");
        console.log("=================================");
        console.log("");
        console.log("ADMIN");
        console.log("Email: admin@example.com");
        console.log("Password: admin123");
        console.log("");
        console.log("CUSTOMER");
        console.log("Email: john@example.com");
        console.log("Password: customer123");
        console.log("");
        console.log("CUSTOMER");
        console.log("Email: mary@example.com");
        console.log("Password: customer123");
        console.log("");
        console.log("CUSTOMER");
        console.log("Email: peter@example.com");
        console.log("Password: customer123");
        console.log("");
        console.log("Properties: 3");
        console.log("Units: 4");
        console.log("Tenants: 3");
        console.log("Leases: 3");
        console.log("Payments: 3");
        console.log("Maintenance: 3");
        console.log("Expenses: 3");
        console.log("Notifications: 3");
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
