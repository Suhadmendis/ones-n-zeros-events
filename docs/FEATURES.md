# Ones n Zeros ERP — Product Feature Document

> **Prepared for:** Customer Presentation
> **Version:** 1.0
> **Date:** June 2026
> **Platform:** Web-based ERP for Transport & Fleet Management

---

## Overview

**Ones n Zeros ERP** is a comprehensive, web-based Enterprise Resource Planning system purpose-built for transport and fleet management businesses. It covers the complete operational lifecycle — from recording daily trips and managing drivers and vehicles, to processing payroll, tracking cash flow, and generating executive-level business intelligence reports.

The system is built on a modern, responsive web architecture and is accessible from any device. It consists of **81 modules** organised across **11 functional sections**, backed by a cloud database (Supabase/PostgreSQL).

---

## System Highlights

| Feature | Detail |
|---|---|
| Total Modules | 81 |
| Functional Sections | 11 |
| Platform | Web (PHP + Vue 3 frontend) |
| Database | Supabase (PostgreSQL) |
| Access | Role-based (Admin, Manager, Operator, Viewer) |
| Vehicle Types Supported | Lorry, Bowser, Tipper, Truck, Van, Bus |
| Staff Types Managed | Drivers, Cleaners |
| Report Categories | 7 distinct reporting sections |

---

## Section 1 — Company Management

> Controls the foundational settings of the organisation: company identity, user accounts, access permissions, and security.

### 1.1 Company Profile `[COM]`
- Store and maintain core company information: name, address, phone, and email
- Central hub for all system-wide reference number sequences (trips, drivers, vehicles, cleaners, customers, fuel, loans, advances, payments, settlements, etc.)
- Ensures consistent auto-numbering across every module

### 1.2 Create User `[USR]`
- Register new system users with full name, email, gender, and role assignment
- Assign roles: **Admin**, **Manager**, **Operator**, or **Viewer**
- Auto-generates a unique user reference number
- Active/Inactive status control

### 1.3 User Permissions `[PRM]`
- Fine-grained, module-level access control
- Grant or restrict access to any of the 81 modules per user
- Granular permissions ensure staff only see and interact with what is relevant to their role

### 1.4 Password Change `[PWD]`
- Secure self-service password update for any user account
- Enforced through the system's authentication layer

### 1.5 Edit / Deactivate User `[EDU]`
- Update user profile details (name, email, gender, role)
- Activate or deactivate a user account without deleting their history
- Full audit trail maintained for all user changes

---

## Section 2 — Master Files

> The foundational data registry of the business — every person, vehicle, location, customer, and item type used across all operational modules is defined here.

### 2.1 Driver Master File `[DRV]`
- Register and manage all drivers in the fleet
- Captures: name, phone number, license number, date of birth, joining date
- Status tracking: **Active**, **Inactive**, **On Leave**
- Auto-generated driver reference number (e.g. `DRV-001`)
- Drivers are available for selection across trip, salary, advance, loan, and payroll modules

### 2.2 Vehicle Master File `[VEH]`
- Comprehensive vehicle registry
- Captures: plate number, vehicle type (Lorry / Bowser / Tipper / Truck / Van / Bus), fuel type (Petrol / Diesel)
- Status: **Active** / **Inactive**
- Auto-generated vehicle reference number (e.g. `VEH-001`)
- Used across trip, fuel, expense, and all fleet reports

### 2.3 Cleaner Master File `[CLN]`
- Register and manage cleaners (vehicle assistants/labourers)
- Captures: name, phone, date of birth, joining date
- Status: **Active** / **Inactive**
- Auto-generated reference number
- Linked to driver-cleaner pairings and payroll settlement modules

### 2.4 Customer Master File `[CUS]`
- Full customer database: name, phone, email, address
- Status control: Active / Inactive
- Auto-generated customer reference number
- Referenced when recording trips and revenue

### 2.5 Item Master File `[ITM]`
- Catalogue of cargo/item types transported or used
- Item categories: **Fuel**, **Equipment**, **Materials**, **Cargo**, **Other**
- Status: Active / Inactive
- Referenced in trip entries and item-type revenue reports

### 2.6 Location Master File `[LOC]`
- Register pickup and delivery locations used in trip routing
- Status control: Active / Inactive
- Referenced in trip running charts for route tracking and profitability

### 2.7 General Expense Type `[GET]`
- Define and manage categories of general (overhead) business expenses
- Status: Active / Inactive
- Referenced in General Expense Entry and General Expenses Report

---

## Section 3 — Operations

> The core day-to-day transaction recording engine of the ERP. Every trip, fuel fill, vehicle repair, staff payment, and cash movement is recorded here.

### 3.1 Trip / Running Chart `[TRP]`
- The primary operational record — logs every trip made by a vehicle
- Captures: vehicle, driver, date, route (from/to locations), customer, cargo item, trip revenue
- Auto-generated trip reference number
- Full New / Edit / Save / Delete / Print / Search workflow
- Linked to trip expense, fuel, and all operations and fleet reports
- Print-ready trip record output

### 3.2 Trip Expense Entry `[TEX]`
- Record expenses directly associated with a specific trip (e.g. toll, loading charges, unloading charges)
- Links to a trip reference for cost-to-revenue matching
- Supports multiple expense line items per trip

### 3.3 Fuel Entry `[FUL]`
- Log fuel fills for any vehicle
- Captures: vehicle, driver, date, fuel type, quantity (litres), unit price, total cost, odometer reading
- Supports petrol and diesel vehicles
- Feeds into fuel usage, fuel efficiency, and fuel cost trend reports

### 3.4 Vehicle Expense Entry `[VEX]`
- Record vehicle maintenance and repair costs
- Expense categories: **Repair**, **Maintenance**, **Body Work**, **Tyres**
- Links to a specific vehicle record
- Feeds into vehicle expense analysis and fleet cost reports

### 3.5 Driver Advance `[ADV]`
- Record cash advances given to drivers before salary settlement
- Captures: driver, date, amount, notes
- Auto-generated advance reference number
- Advances are deducted automatically during driver salary settlement

### 3.6 Deduction `[DED]`
- Record deductions from driver or cleaner pay
- Captures: staff member, deduction type, date, amount
- Linked to salary settlement to calculate net pay

### 3.7 Loan `[LON]`
- Manage staff loans issued to drivers and cleaners
- Captures: staff member, loan amount, date, repayment schedule
- Loan balance tracks outstanding amounts and repayments
- Feeds into driver and cleaner loan reports

### 3.8 Payment / Salary Disburse `[PAY]`
- Record salary payments made to drivers and cleaners
- Captures: staff member, payment month, amount disbursed, payment method
- Links to salary settlement records for reconciliation

### 3.9 Driver Salary Settlement `[DSS]`
- Monthly salary settlement processor for drivers
- Automatically pulls: total trip earnings, advances, deductions, and loans for the selected month and driver
- Calculates gross earnings, total deductions, and **net payable**
- Status: **Pending** / **Paid**
- Print-ready settlement slip

### 3.10 Cleaner Salary Settlement `[CSS]`
- Monthly salary settlement processor for cleaners
- Same structured workflow as driver settlement
- Pulls cleaner-specific earnings, advances, deductions, and loans
- Net pay calculation with printable settlement slip

### 3.11 Cash Flow `[CSF]`
- Record all business cash movements — inflows and outflows
- Flow types: **Inflow** (receipts, income) / **Outflow** (expenses, payments)
- Date-based records with description and amount
- Feeds into the Cash Flow Statement financial report

### 3.12 Driver-Cleaner Pairing `[DCP]`
- Formally assign a cleaner to a driver for a defined period
- Captures: driver, cleaner, start date, end date
- Used in pairing-based trip and salary allocation

### 3.13 General Expense Entry `[GEX]`
- Record general overhead business expenses not tied to a specific trip or vehicle
- Links to a General Expense Type category
- Feeds into General Expenses Report and financial summaries

---

## Section 4 — Reports

> Core operational and financial reports for day-to-day business review.

### 4.1 Monthly Income Summary `[MIS]`
Monthly breakdown of total income across all trips and revenue sources.

### 4.2 Vehicle Income Report `[VIR]`
Income generated per vehicle over a selected period — identify top-earning vehicles.

### 4.3 Driver Income Report `[DIR]`
Revenue attributed to each driver — useful for performance-linked pay structures.

### 4.4 Vehicle Profitability Report `[VPR]`
Compares vehicle revenue against vehicle-specific costs to show profit per vehicle.

### 4.5 Trip Expense Report `[TER]`
Breakdown of all trip-level expenses across a date range.

### 4.6 Running Chart Full Ledger `[RCL]`
Complete trip-by-trip ledger showing full operational history for any vehicle or driver.

### 4.7 Lorry vs Trips Report `[LVT]`
Cross-reference of how many trips each lorry has completed — fleet utilisation at a glance.

### 4.8 Fuel Usage Report `[FUR]`
Summary of fuel consumption by vehicle and period.

### 4.9 All Fuel List `[AFL]`
Full itemised list of every fuel entry in the system, filterable by vehicle and date.

### 4.10 Vehicle Expenses Report `[VER]`
Consolidated view of all maintenance and repair costs per vehicle.

### 4.11 General Expenses Report `[GER]`
Summary of all general overhead expenses by category and period.

### 4.12 Driver Salary Summary `[DSR]`
High-level salary totals across all drivers for a selected month.

### 4.13 Driver Salary List `[DSL]`
Detailed salary breakdown list for all drivers in a given period.

### 4.14 Cleaner Salary List `[CSL]`
Detailed salary breakdown list for all cleaners in a given period.

### 4.15 Cash Flow Print `[CFP]`
Printable version of the cash flow record for filing and audit purposes.

### 4.16 Business Summary Dashboard `[BSD]`
At-a-glance overview of key business metrics — income, expenses, trips, and staff costs.

---

## Section 5 — Executive Reports

> High-level summaries designed for management decision-making and strategic review.

### 5.1 Business Performance Summary `[BPS]`
- Comprehensive view of business KPIs across a selected period
- Covers: total revenue, total expenses, net profit, trip counts, vehicle count, driver count
- Designed for management presentations and board review

### 5.2 Daily Business Snapshot `[DBS]`
- One-page daily summary of all business activity
- Shows trips completed, fuel consumed, expenses incurred, and revenue generated for the day
- Ideal for morning briefings and daily operations review

---

## Section 6 — Fleet Reports

> Deep-dive analytics on vehicle performance, utilisation, fuel, and maintenance costs.

### 6.1 Vehicle Performance Report `[VPF]`
Measures each vehicle's output — trips completed, revenue generated, and expenses incurred — across a period.

### 6.2 Vehicle Utilisation Report `[VUT]`
Shows how actively each vehicle is being used: days active, idle days, trip frequency.

### 6.3 Mileage Report `[MLR]`
Tracks total kilometres driven per vehicle based on odometer readings from fuel entries.

### 6.4 Fuel Efficiency Report `[FEF]`
Calculates kilometres per litre (KPL) for each vehicle — identify fuel-efficient and inefficient vehicles.

### 6.5 Fuel Cost Trend Report `[FCT]`
Charts fuel cost over time — track rising/falling fuel spend and forecast future costs.

### 6.6 Vehicle Expense Analysis `[VEA]`
Breaks down vehicle expenses by category (Repair / Maintenance / Body Work / Tyres) per vehicle.

### 6.7 Most Expensive Vehicle `[MEV]`
Ranks vehicles from highest to lowest total expense — quickly flag high-maintenance assets.

---

## Section 7 — Operations Reports

> Drill-down reporting on trip revenue, routes, frequency, and departmental performance.

### 7.1 Trip Revenue Report `[TRV]`
Revenue earned per trip, with filters by vehicle, driver, customer, and date range.

### 7.2 Route Profitability Report `[RPR]`
Analyses which routes (origin-to-destination) are most profitable after accounting for trip expenses.

### 7.3 Trip Frequency Report `[TFQ]`
Shows how often specific routes or vehicles are deployed — supports scheduling decisions.

### 7.4 Department-wise Revenue Report `[DPR]`
Breaks income down by business department or customer segment.

### 7.5 Item Type Revenue Report `[ITR]`
Revenue analysis by cargo/item type — understand which goods generate the most business value.

---

## Section 8 — Staff Reports

> Full reporting suite for driver and cleaner performance, pay, loans, and deductions.

### Driver Reports

| Module | Code | Description |
|---|---|---|
| Driver Performance Report | DPF | Trips completed, revenue attributed, efficiency metrics per driver |
| Driver Earnings Report | DER | Total earnings per driver before deductions |
| Driver Advance Report | DAR | Full history of advances given to each driver |
| Driver Loan Report | DLR | Loan disbursements and outstanding balances per driver |
| Driver Deduction Report | DDR | All deductions applied to a driver's pay in a period |
| Driver Salary Sheet | DSH | Individual detailed salary sheet for a driver including all components |

### Cleaner Reports

| Module | Code | Description |
|---|---|---|
| Cleaner Performance Report | CPF | Trips assigned, earnings, and efficiency per cleaner |
| Cleaner Salary Report | CSR | Detailed salary report for cleaners |
| Cleaner Loan Report | CLR | Loan history and outstanding balances for cleaners |
| Cleaner Advance Report | CAR | Advance payment history for cleaners |

---

## Section 9 — Financial Reports

> Accounting-grade financial statements for the business.

### 9.1 Cash Flow Statement `[CFS]`
- Full period cash flow statement showing all inflows and outflows
- Calculates net cash position
- Suitable for accountant review and tax filing preparation

### 9.2 Income vs Expense Report `[IVE]`
- Side-by-side comparison of total income against total expenses over time
- Visualises profit margins and financial trends

### 9.3 Expense Summary Report `[EXS]`
- Consolidated view of all expenses (trip, vehicle, fuel, general, staff) grouped by category
- Useful for budget review and cost control

### 9.4 Monthly Profit Report `[MNP]`
- Month-by-month profit and loss statement
- Revenue minus all operating costs = net profit per month

### 9.5 Revenue Trend Report `[RVT]`
- Tracks revenue growth or decline over selected months
- Supports forecasting and business planning

### 9.6 Expense Trend Report `[EXT]`
- Tracks expense patterns over time
- Highlights cost spikes and supports corrective action

---

## Section 10 — Payroll Reports (Self-Service)

> Personal reports accessible by individual drivers and cleaners to view their own financial records.

### 10.1 My Trips `[MYT]`
A staff member's personal list of all trips assigned to them.

### 10.2 My Earnings `[MYE]`
Total earnings summary for the logged-in driver or cleaner.

### 10.3 My Salary Statement `[MSS]`
Full salary statement showing gross pay, deductions, advances, loan repayments, and net pay.

### 10.4 My Advances `[MYA]`
History of all advance payments received by the logged-in staff member.

### 10.5 My Loan Status `[MLS]`
Current loan balance, repayment history, and outstanding amount for the logged-in staff member.

---

## Section 11 — Analytics Reports

> Performance rankings and intelligent comparisons to support strategic decisions.

### 11.1 Top Performing Vehicle `[TPV]`
Ranks vehicles by revenue generated or trips completed — recognise your best-performing assets.

### 11.2 Top Performing Driver `[TPD]`
Ranks drivers by earnings, trip count, or efficiency — supports incentive and appraisal programmes.

### 11.3 Lowest Fuel Efficiency `[LFE]`
Identifies the vehicles with the worst fuel economy (lowest KPL) — flag candidates for servicing or replacement.

### 11.4 Highest Expense Vehicle `[HEV]`
Highlights which vehicles are consuming the most in maintenance and repair costs.

### 11.5 Profit per KM Report `[PPK]`
Calculates net profit generated per kilometre driven per vehicle — the ultimate operational efficiency metric.

---

## Technical Architecture

| Component | Technology |
|---|---|
| Frontend | PHP (server-rendered) + Vue 3 (reactive UI) |
| UI Framework | AdminLTE 4 + Bootstrap 5.3 |
| Icons | Bootstrap Icons |
| Database | Supabase (PostgreSQL) |
| Data Tables | DataTables (with export: Excel, CSV, PDF, Print) |
| Charts | ApexCharts |
| Hosting | PHP built-in server / any PHP host |
| Authentication | Role-based (Admin / Manager / Operator / Viewer) |

---

## Data Flow Summary

```
Master Files → Operations → Reports
    ↓               ↓           ↓
Drivers         Trips       Financial Statements
Vehicles        Fuel        Fleet Analytics
Cleaners        Expenses    Staff Reports
Customers       Payroll     Executive Dashboards
Locations       Cash Flow   Self-Service Pay Slips
```

---

## Module Count Summary

| Section | Modules |
|---|---|
| Company Management | 5 |
| Master Files | 7 |
| Operations | 13 |
| Reports | 16 |
| Executive Reports | 2 |
| Fleet Reports | 7 |
| Operations Reports | 5 |
| Staff Reports | 10 |
| Financial Reports | 6 |
| Payroll Reports (Self-Service) | 5 |
| Analytics Reports | 5 |
| **Total** | **81** |

---

*Ones n Zeros ERP — Built for Transport & Fleet Businesses*
