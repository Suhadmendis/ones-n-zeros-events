# Graph Report - ./modules  (2026-07-11)

## Corpus Check
- Large corpus: 816 files · ~295,074 words. Semantic extraction will be expensive (many Claude tokens). Consider running on a subfolder.

## Summary
- 2829 nodes · 2978 edges · 816 communities (799 shown, 17 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 14 edges (avg confidence: 0.5)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Modules Mgmt / Module Generator
- Modules Mgmt / Module Generator
- Accounting / Journal Entries
- Accounting / General Ledger
- Work Orders / Work Order Entries
- Quotations / Quotation Entries
- Staff Payroll / Cleaner Salary Settlement
- Staff Payroll / Driver Salary Settlement
- Trip Management / Trip Running Chart
- Payroll Processing / Payroll Runs
- User Management / User Permissions
- Financial Settings / Stg Posting Rules
- Receivables Payables / Accounts Payable
- Receivables Payables / Accounts Receivable
- Salary Setup / Salary Assignments
- Expenses / Fuel Entry
- Staff Fleet / Driver Master File
- Expenses / Cash Flow
- Expenses / General Expense Entry
- Expenses / Vehicle Expense Entry
- Staff Payroll / Deduction
- Staff Payroll / Employee Advance
- Receivables Payables / Bank Reconciliation
- Receivables Payables / Cash Bank
- Event Planning / Event Calendar
- Staff Payroll / Loan
- Staff Payroll / Payment Salary Disburse
- Compliance / Period Closing
- Payroll Processing / Payroll Inputs
- People / Employee Assignment
- People / Employment Record
- Vehicle / Vehicle Model
- Fleet Maintenance / Vehicle Documents
- Modules Mgmt / Stg Enable Modules
- Assets Budgeting / Budgeting
- Assets Budgeting / Fixed Assets
- Hr Operations / Freelance Crew Payment
- Salary Setup / Employee Salary Structure
- Fleet Maintenance / Vehicle Gps
- Fleet Maintenance / Vehicle Maintenance
- Quotations / Quotation Resources
- User Management / Edit Deactivate User
- Assets Budgeting / Cost Centers
- Hr Operations / Attendance
- Hr Operations / Leave Management
- Payroll Processing / Payroll Periods
- Salary Setup / Payroll Profiles
- References / Customer Advance
- References / Customer Master File
- References / Supplier Master
- References / Venue Master
- Staff Fleet / Cleaner Master File
- Event Planning / Site Visit
- Fleet Maintenance / Vehicle Odometer Readings
- User Management / Create User
- Accounting / Chart Of Accounts
- Salary Setup / Salary Components
- Organisation / Department
- Organisation / Designations
- Organisation / Employment Types
- People / Employee Master
- Quotation / Quotation Delivery Periods
- Quotation / Quotation Notes Templates
- Quotation / Quotation Payment Terms
- Quotation / Quotation Statuses
- Quotation / Quotation Terms And Conditions
- References / Ceremony Type
- References / Customer Types
- References / Event Type Master
- References / General Expense Type
- References / Item Master File
- References / Location Master File
- References / Supplier Category
- References / Venue Category
- References / Work Order Statuses
- References / Work Order Types
- Staff Fleet / Freelance Crew Master
- Staff Fleet / Vehicle Master File
- Vehicle / Fleet Statuses
- Vehicle / Fuel Type
- Vehicle / Gps Device
- Vehicle / Maintenance Type
- Vehicle / Vehicle Brand
- Vehicle / Vehicle Document Types
- Vehicle / Vehicle Type
- Modules Mgmt / Module Documentation
- User Management / Password Change
- User Management / Role Management
- Assets Budgeting / Multi Currency
- Compliance / Approval Workflow
- Compliance / Tax Management
- Work Orders / Fleet Tracker
- Company Settings / Stg Branches
- Company Settings / Stg Currency
- Notifications / Stg Financial Alerts
- Notifications / Stg Vehicle Alerts
- Number Series / Series Overview
- Payroll Processing / Payroll Runs
- Administration / Company Profile
- Fleet Reports / Fuel Cost Trend
- General Reports / Fuel Usage Report
- General Reports / Running Chart Full Ledger
- General Reports / Vehicle Expenses Report
- Operations Reports / Department Revenue
- Administration / Company Profile
- User Management / Create User
- User Management / Edit Deactivate User
- Compliance / Approval Workflow
- Receivables Payables / Bank Reconciliation
- Quotations / Quotation Entries
- Work Orders / Work Order Entries
- Financial Reports / Expense Summary
- Financial Reports / Expense Trend
- Financial Reports / Income Vs Expense
- Financial Reports / Monthly Profit
- Fleet Reports / Fuel Efficiency
- Fleet Reports / Mileage Report
- Fleet Reports / Most Expensive Vehicle
- Fleet Reports / Vehicle Expense Analysis
- Fleet Reports / Vehicle Performance
- General Reports / All Fuel List
- General Reports / Driver Income Report
- General Reports / Driver Salary List
- General Reports / Lorry Vs Trips Report
- General Reports / Vehicle Income Report
- General Reports / Vehicle Profitability Report
- Operations Reports / Item Type Revenue
- Operations Reports / Trip Revenue
- Staff Reports / Driver Earnings
- Staff Reports / Driver Loan Report
- Staff Reports / Driver Performance
- Notifications / Stg Financial Alerts
- Notifications / Stg Vehicle Alerts
- Executive Reports / Business Performance Summary
- Financial Reports / Cash Flow Statement
- Financial Reports / Revenue Trend
- Fleet Reports / Vehicle Utilization
- General Reports / Business Summary Dashboard
- General Reports / Cash Flow Print
- General Reports / Cleaner Salary List
- General Reports / Driver Salary Summary
- General Reports / General Expenses Report
- General Reports / Monthly Income Summary
- General Reports / Trip Expense Report
- Operations Reports / Route Profitability
- Operations Reports / Trip Frequency
- Staff Reports / Cleaner Advance Report
- Staff Reports / Cleaner Performance
- Staff Reports / Cleaner Salary Report
- Staff Reports / Driver Deduction Report
- Staff Reports / Driver Salary Sheet
- Staff Reports / Employee Advance Report
- User Management / Password Change
- Executive Reports / Daily Business Snapshot
- Staff Reports / Cleaner Loan Report
- Accounting / General Ledger

## God Nodes (most connected - your core abstractions)
1. `generateModule()` - 26 edges
2. `renderTemplate()` - 13 edges
3. `buildHeaderDetailPhp()` - 12 edges
4. `buildHeaderDetailJs()` - 12 edges
5. `buildModulePhp()` - 10 edges
6. `buildModuleJs()` - 9 edges
7. `buildHeaderDetailDataPhp()` - 9 edges
8. `emptyLine()` - 8 edges
9. `ruleKey()` - 8 edges
10. `toPascalCase()` - 8 edges

## Surprising Connections (you probably didn't know these)
- None detected - all connections are within the same source files.

## Import Cycles
- None detected.

## Communities (816 total, 17 thin omitted)

### Community 0 - "Modules Mgmt / Module Generator"
Cohesion: 0.09
Nodes (58): buildHeaderDetailDataPhp(), buildHeaderDetailJs(), buildHeaderDetailPhp(), buildModuleDataPhp(), buildModuleJs(), buildModulePhp(), buildPrintPhp(), buildReportDataPhp() (+50 more)

### Community 1 - "Modules Mgmt / Module Generator"
Cohesion: 0.08
Nodes (23): addField(), addLineField(), checkSlug(), checkTable(), derivePrefix(), fetchTitle(), fieldErrors(), fieldErrorsFor() (+15 more)

### Community 2 - "Accounting / Journal Entries"
Cohesion: 0.08
Nodes (18): addLine(), emptyLine(), fetchRefNumber(), fmtNum(), data(), loadCoaAccounts(), loadEntry(), mounted() (+10 more)

### Community 3 - "Accounting / General Ledger"
Cohesion: 0.07
Nodes (10): acctEnter(), applyFilters(), applyQuick(), buildQuickPeriods(), fetchEntries(), fetchTitle(), goPage(), loadCoa() (+2 more)

### Community 4 - "Work Orders / Work Order Entries"
Cohesion: 0.10
Nodes (16): addLine(), emptyLine(), fetchRefNumber(), isDirty(), data(), lineHasData(), loadRecord(), mounted() (+8 more)

### Community 5 - "Quotations / Quotation Entries"
Cohesion: 0.14
Nodes (18): addLine(), emptyLine(), fetchRefNumber(), isDirty(), data(), lineAmount(), lineHasData(), loadRecord() (+10 more)

### Community 6 - "Staff Payroll / Cleaner Salary Settlement"
Cohesion: 0.12
Nodes (14): autoCalculate(), calculate(), cancelEdit(), closeRecord(), deleteRecord(), loadRecord(), mounted(), newRecord() (+6 more)

### Community 7 - "Staff Payroll / Driver Salary Settlement"
Cohesion: 0.12
Nodes (14): autoCalculate(), calculate(), cancelEdit(), closeRecord(), deleteRecord(), loadRecord(), mounted(), newRecord() (+6 more)

### Community 8 - "Trip Management / Trip Running Chart"
Cohesion: 0.11
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 9 - "Payroll Processing / Payroll Runs"
Cohesion: 0.13
Nodes (12): employeeHasData(), fetchRefNumber(), isDirty(), lineHasData(), loadRecord(), mounted(), onAdd(), onClose() (+4 more)

### Community 10 - "User Management / User Permissions"
Cohesion: 0.15
Nodes (15): fetchModules(), fetchRoles(), fetchTitle(), isChecked(), isColumnAllChecked(), isRowAllChecked(), isSectionAllChecked(), mounted() (+7 more)

### Community 11 - "Financial Settings / Stg Posting Rules"
Cohesion: 0.18
Nodes (17): addLine(), getLines(), getVariants(), loadAccounts(), loadModules(), loadRulesForModule(), MODULE_VARIANTS, mounted() (+9 more)

### Community 12 - "Receivables Payables / Accounts Payable"
Cohesion: 0.16
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 13 - "Receivables Payables / Accounts Receivable"
Cohesion: 0.16
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 14 - "Salary Setup / Salary Assignments"
Cohesion: 0.19
Nodes (11): addDetailLine(), emptyDetailLine(), fetchRefNumber(), data(), loadRecord(), mounted(), onAdd(), onCancel() (+3 more)

### Community 15 - "Expenses / Fuel Entry"
Cohesion: 0.16
Nodes (8): fetchRefNumber(), loadEntry(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 16 - "Staff Fleet / Driver Master File"
Cohesion: 0.18
Nodes (8): fetchRefNumber(), loadDriver(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 17 - "Expenses / Cash Flow"
Cohesion: 0.18
Nodes (8): fetchRefNumber(), loadEntry(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 18 - "Expenses / General Expense Entry"
Cohesion: 0.18
Nodes (8): fetchRefNumber(), loadEntry(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 19 - "Expenses / Vehicle Expense Entry"
Cohesion: 0.18
Nodes (8): fetchRefNumber(), loadEntry(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 20 - "Staff Payroll / Deduction"
Cohesion: 0.14
Nodes (4): fetchRefNumber(), loadRecord(), mounted(), newRecord()

### Community 21 - "Staff Payroll / Employee Advance"
Cohesion: 0.18
Nodes (8): fetchRefNumber(), loadEntry(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 22 - "Receivables Payables / Bank Reconciliation"
Cohesion: 0.14
Nodes (4): fetchNextRef(), onAdd(), onReconcile(), onSave()

### Community 23 - "Receivables Payables / Cash Bank"
Cohesion: 0.19
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 24 - "Event Planning / Event Calendar"
Cohesion: 0.17
Nodes (7): cells(), dateKey(), fetchEvents(), goToday(), mounted(), nextMonth(), prevMonth()

### Community 25 - "Staff Payroll / Loan"
Cohesion: 0.15
Nodes (4): fetchRefNumber(), loadRecord(), mounted(), newRecord()

### Community 26 - "Staff Payroll / Payment Salary Disburse"
Cohesion: 0.15
Nodes (4): fetchRefNumber(), loadRecord(), mounted(), newRecord()

### Community 27 - "Compliance / Period Closing"
Cohesion: 0.15
Nodes (4): fetchNextRef(), onAdd(), onClosePeriod(), onSave()

### Community 28 - "Payroll Processing / Payroll Inputs"
Cohesion: 0.21
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 29 - "People / Employee Assignment"
Cohesion: 0.21
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 30 - "People / Employment Record"
Cohesion: 0.21
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 31 - "Vehicle / Vehicle Model"
Cohesion: 0.21
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 32 - "Fleet Maintenance / Vehicle Documents"
Cohesion: 0.21
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 33 - "Modules Mgmt / Stg Enable Modules"
Cohesion: 0.18
Nodes (10): COLOR_CHOICES, confirmToggle(), DEFAULT_ICONS, fetchSections(), fetchTitle(), ICON_CHOICES, mounted(), onColorSelect() (+2 more)

### Community 34 - "Assets Budgeting / Budgeting"
Cohesion: 0.23
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 35 - "Assets Budgeting / Fixed Assets"
Cohesion: 0.23
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 36 - "Hr Operations / Freelance Crew Payment"
Cohesion: 0.23
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 37 - "Salary Setup / Employee Salary Structure"
Cohesion: 0.23
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 38 - "Fleet Maintenance / Vehicle Gps"
Cohesion: 0.23
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 39 - "Fleet Maintenance / Vehicle Maintenance"
Cohesion: 0.23
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 40 - "Quotations / Quotation Resources"
Cohesion: 0.23
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 41 - "User Management / Edit Deactivate User"
Cohesion: 0.23
Nodes (9): fetchRoles(), fetchTitle(), loadUser(), mounted(), onActivate(), onCancel(), onDeactivate(), onReset() (+1 more)

### Community 43 - "Hr Operations / Attendance"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 44 - "Hr Operations / Leave Management"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 45 - "Payroll Processing / Payroll Periods"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 46 - "Salary Setup / Payroll Profiles"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 47 - "References / Customer Advance"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 48 - "References / Customer Master File"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadCustomer(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 49 - "References / Supplier Master"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 50 - "References / Venue Master"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 51 - "Staff Fleet / Cleaner Master File"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadCleaner(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 52 - "Event Planning / Site Visit"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 53 - "Fleet Maintenance / Vehicle Odometer Readings"
Cohesion: 0.26
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 54 - "User Management / Create User"
Cohesion: 0.30
Nodes (9): fetchRefNumber(), fetchRoles(), loadUser(), mounted(), onAdd(), onCancel(), onClose(), onReset() (+1 more)

### Community 55 - "Accounting / Chart Of Accounts"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadAccount(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 56 - "Salary Setup / Salary Components"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 57 - "Organisation / Department"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 58 - "Organisation / Designations"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 59 - "Organisation / Employment Types"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 60 - "People / Employee Master"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 61 - "Quotation / Quotation Delivery Periods"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 62 - "Quotation / Quotation Notes Templates"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 63 - "Quotation / Quotation Payment Terms"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 64 - "Quotation / Quotation Statuses"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 65 - "Quotation / Quotation Terms And Conditions"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 66 - "References / Ceremony Type"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 67 - "References / Customer Types"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 68 - "References / Event Type Master"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 69 - "References / General Expense Type"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadType(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 70 - "References / Item Master File"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadItem(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 71 - "References / Location Master File"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadLocation(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 72 - "References / Supplier Category"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 73 - "References / Venue Category"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 74 - "References / Work Order Statuses"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 75 - "References / Work Order Types"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 76 - "Staff Fleet / Freelance Crew Master"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 77 - "Staff Fleet / Vehicle Master File"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadVehicle(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 78 - "Vehicle / Fleet Statuses"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 79 - "Vehicle / Fuel Type"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 80 - "Vehicle / Gps Device"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 81 - "Vehicle / Maintenance Type"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 82 - "Vehicle / Vehicle Brand"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 83 - "Vehicle / Vehicle Document Types"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 84 - "Vehicle / Vehicle Type"
Cohesion: 0.29
Nodes (8): fetchRefNumber(), loadRecord(), mounted(), onAdd(), onCancel(), onClose(), onReset(), onSave()

### Community 85 - "Modules Mgmt / Module Documentation"
Cohesion: 0.20
Nodes (4): BLANK_FORM, fetchTitle(), loadLookups(), mounted()

### Community 86 - "User Management / Password Change"
Cohesion: 0.29
Nodes (7): fetchTitle(), loadUser(), mounted(), onCancel(), onClose(), onReset(), onSave()

### Community 87 - "User Management / Role Management"
Cohesion: 0.29
Nodes (7): fetchRoles(), fetchTitle(), mounted(), onNew(), onReset(), onSave(), onToggleStatus()

### Community 91 - "Work Orders / Fleet Tracker"
Cohesion: 0.20
Nodes (3): fetchTitle(), FT_EMPTY_ORDER, mounted()

### Community 96 - "Number Series / Series Overview"
Cohesion: 0.27
Nodes (7): blankForm(), data(), loadRecord(), mounted(), onCancel(), onClose(), onReset()

### Community 97 - "Payroll Processing / Payroll Runs"
Cohesion: 0.29
Nodes (6): findEffectiveSalaryAssignment(), generatePayrollForPeriod(), saveEmployeePayrollLines(), saveEmployeePayrolls(), savePayrollRuns(), updatePayrollRuns()

### Community 99 - "Fleet Reports / Fuel Cost Trend"
Cohesion: 0.36
Nodes (4): load(), loadVehicles(), mounted(), renderChart()

### Community 100 - "General Reports / Fuel Usage Report"
Cohesion: 0.38
Nodes (3): load(), mounted(), selectMonth()

### Community 101 - "General Reports / Running Chart Full Ledger"
Cohesion: 0.38
Nodes (3): load(), mounted(), selectMonth()

### Community 102 - "General Reports / Vehicle Expenses Report"
Cohesion: 0.43
Nodes (4): load(), loadVehicles(), mounted(), selectMonth()

### Community 103 - "Operations Reports / Department Revenue"
Cohesion: 0.43
Nodes (4): load(), mounted(), renderChart(), selectMonth()

### Community 105 - "Administration / Company Profile"
Cohesion: 0.53
Nodes (4): patchCompanyInfo(), saveUploadedImage(), updateCompany(), upsertSettings()

### Community 106 - "User Management / Create User"
Cohesion: 0.47
Nodes (3): listUsers(), userRoleNames(), userRoleRefs()

### Community 107 - "User Management / Edit Deactivate User"
Cohesion: 0.47
Nodes (3): listUsers(), userRoleNames(), userRoleRefs()

### Community 108 - "Compliance / Approval Workflow"
Cohesion: 0.60
Nodes (4): buildPayload(), saveRecord(), updateRecord(), validatePayload()

### Community 110 - "Receivables Payables / Bank Reconciliation"
Cohesion: 0.60
Nodes (4): buildPayload(), saveRecord(), updateRecord(), validatePayload()

### Community 111 - "Quotations / Quotation Entries"
Cohesion: 0.47
Nodes (3): saveQuotationLines(), saveQuotations(), updateQuotations()

### Community 112 - "Work Orders / Work Order Entries"
Cohesion: 0.47
Nodes (3): saveWorkOrderLines(), saveWorkOrders(), updateWorkOrders()

### Community 113 - "Financial Reports / Expense Summary"
Cohesion: 0.53
Nodes (4): load(), mounted(), renderChart(), selectMonth()

### Community 114 - "Financial Reports / Expense Trend"
Cohesion: 0.47
Nodes (3): load(), mounted(), renderChart()

### Community 115 - "Financial Reports / Income Vs Expense"
Cohesion: 0.47
Nodes (3): load(), mounted(), renderChart()

### Community 116 - "Financial Reports / Monthly Profit"
Cohesion: 0.47
Nodes (3): load(), mounted(), renderChart()

### Community 117 - "Fleet Reports / Fuel Efficiency"
Cohesion: 0.47
Nodes (3): load(), mounted(), selectMonth()

### Community 118 - "Fleet Reports / Mileage Report"
Cohesion: 0.53
Nodes (4): load(), loadVehicles(), mounted(), selectMonth()

### Community 119 - "Fleet Reports / Most Expensive Vehicle"
Cohesion: 0.47
Nodes (3): load(), mounted(), selectMonth()

### Community 120 - "Fleet Reports / Vehicle Expense Analysis"
Cohesion: 0.53
Nodes (4): load(), loadVehicles(), mounted(), selectMonth()

### Community 121 - "Fleet Reports / Vehicle Performance"
Cohesion: 0.53
Nodes (4): load(), loadVehicles(), mounted(), selectMonth()

### Community 122 - "General Reports / All Fuel List"
Cohesion: 0.53
Nodes (4): load(), loadVehicles(), mounted(), selectMonth()

### Community 123 - "General Reports / Driver Income Report"
Cohesion: 0.47
Nodes (3): load(), mounted(), selectMonth()

### Community 124 - "General Reports / Driver Salary List"
Cohesion: 0.53
Nodes (4): load(), loadDrivers(), mounted(), selectMonth()

### Community 125 - "General Reports / Lorry Vs Trips Report"
Cohesion: 0.47
Nodes (3): load(), mounted(), selectMonth()

### Community 126 - "General Reports / Vehicle Income Report"
Cohesion: 0.47
Nodes (3): load(), mounted(), selectMonth()

### Community 127 - "General Reports / Vehicle Profitability Report"
Cohesion: 0.47
Nodes (3): load(), mounted(), selectMonth()

### Community 128 - "Operations Reports / Item Type Revenue"
Cohesion: 0.47
Nodes (3): load(), mounted(), selectMonth()

### Community 129 - "Operations Reports / Trip Revenue"
Cohesion: 0.53
Nodes (4): load(), loadMeta(), mounted(), selectMonth()

### Community 130 - "Staff Reports / Driver Earnings"
Cohesion: 0.53
Nodes (4): load(), loadDrivers(), mounted(), selectMonth()

### Community 132 - "Staff Reports / Driver Performance"
Cohesion: 0.53
Nodes (4): load(), loadDrivers(), mounted(), selectMonth()

### Community 142 - "Notifications / Stg Financial Alerts"
Cohesion: 0.60
Nodes (4): buildPayload(), saveRecord(), updateRecord(), validatePayload()

### Community 143 - "Notifications / Stg Vehicle Alerts"
Cohesion: 0.60
Nodes (4): buildPayload(), saveRecord(), updateRecord(), validatePayload()

### Community 146 - "Executive Reports / Business Performance Summary"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 147 - "Financial Reports / Cash Flow Statement"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 148 - "Financial Reports / Revenue Trend"
Cohesion: 0.60
Nodes (3): load(), mounted(), renderChart()

### Community 149 - "Fleet Reports / Vehicle Utilization"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 150 - "General Reports / Business Summary Dashboard"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 151 - "General Reports / Cash Flow Print"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 152 - "General Reports / Cleaner Salary List"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 153 - "General Reports / Driver Salary Summary"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 154 - "General Reports / General Expenses Report"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 156 - "General Reports / Trip Expense Report"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 157 - "Operations Reports / Route Profitability"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 158 - "Operations Reports / Trip Frequency"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 159 - "Staff Reports / Cleaner Advance Report"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 160 - "Staff Reports / Cleaner Performance"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 162 - "Staff Reports / Driver Deduction Report"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

### Community 164 - "Staff Reports / Employee Advance Report"
Cohesion: 0.60
Nodes (3): load(), mounted(), selectMonth()

## Knowledge Gaps
- **11 isolated node(s):** `RMP_ACTIONS`, `PickerField`, `FT_EMPTY_ORDER`, `PickerField`, `MODULE_VARIANTS` (+6 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **17 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What connects `RMP_ACTIONS`, `PickerField`, `FT_EMPTY_ORDER` to the rest of the system?**
  _11 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Modules Mgmt / Module Generator` be split into smaller, more focused modules?**
  _Cohesion score 0.0903954802259887 - nodes in this community are weakly interconnected._
- **Should `Modules Mgmt / Module Generator` be split into smaller, more focused modules?**
  _Cohesion score 0.07657657657657657 - nodes in this community are weakly interconnected._
- **Should `Accounting / Journal Entries` be split into smaller, more focused modules?**
  _Cohesion score 0.08412698412698413 - nodes in this community are weakly interconnected._
- **Should `Accounting / General Ledger` be split into smaller, more focused modules?**
  _Cohesion score 0.07130124777183601 - nodes in this community are weakly interconnected._
- **Should `Work Orders / Work Order Entries` be split into smaller, more focused modules?**
  _Cohesion score 0.10344827586206896 - nodes in this community are weakly interconnected._
- **Should `Quotations / Quotation Entries` be split into smaller, more focused modules?**
  _Cohesion score 0.14153846153846153 - nodes in this community are weakly interconnected._