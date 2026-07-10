AMT Transport Management System - Proposed
Reports Catalog
Based on Master Files and Operational Modules (excluding existing SRD report analysis).

--- Executive Reports ---

1. Business Performance Summary
Revenue - Fuel - Expenses - Salaries = Estimated Profit. Executive summary for management.

  Files: reports/business_performance_summary/ → .php, .js, _data.php
  
  Filters:
    - Date From (date input, default: first day of current month)
    - Date To (date input, default: today)
  
  API ?action=summary:
    Run these queries in parallel and return one JSON object:
    - revenue:          SELECT COALESCE(SUM(amount), 0) FROM trips WHERE date BETWEEN :from AND :to
    - fuel_cost:        SELECT COALESCE(SUM(total), 0) FROM fuel_expenses WHERE date BETWEEN :from AND :to
    - vehicle_expenses: SELECT COALESCE(SUM(amount), 0) FROM vehicle_expenses WHERE date BETWEEN :from AND :to
    - general_expenses: SELECT COALESCE(SUM(amount), 0) FROM general_expenses WHERE date BETWEEN :from AND :to
    - driver_payouts:   SELECT COALESCE(SUM(damount) + SUM(COALESCE(day_pay, 0)), 0) FROM trips WHERE date BETWEEN :from AND :to
    - cleaner_payouts:  SELECT COALESCE(SUM(camount), 0) FROM trips WHERE date BETWEEN :from AND :to
    - advance_payments: SELECT COALESCE(SUM(amount), 0) FROM advance_payments WHERE date BETWEEN :from AND :to
    - Compute in PHP: estimated_profit = revenue - fuel_cost - vehicle_expenses - general_expenses - driver_payouts - cleaner_payouts
  
  Output UI:
    Row 1 — 4 small-box KPI cards: Total Revenue | Total Fuel Cost | Total Vehicle Expenses | Total General Expenses
    Row 2 — 3 small-box KPI cards: Driver Payouts | Cleaner Payouts | Estimated Profit (green if positive, red if negative)
    Below: simple HTML table with two columns (Category | Amount) listing all 7 lines plus the profit row at the bottom in bold
  
  Tables: trips, fuel_expenses, vehicle_expenses, general_expenses, advance_payments

2. Daily Business Snapshot
Shows trips, revenue, fuel cost, expenses and net position for the day.

  Files: reports/daily_business_snapshot/ → .php, .js, _data.php
  
  Filters:
    - Date (single date input, default: today)
  
  API ?action=snapshot&date=:
    - kpis: 
        SELECT COUNT(*) AS trip_count, COALESCE(SUM(amount), 0) AS revenue FROM trips WHERE date = :date
        SELECT COALESCE(SUM(total), 0) AS fuel FROM fuel_expenses WHERE date = :date
        SELECT COALESCE(SUM(amount), 0) AS vehicle_exp FROM vehicle_expenses WHERE date = :date
        SELECT COALESCE(SUM(amount), 0) AS general_exp FROM general_expenses WHERE date = :date
        Compute: net_position = revenue - fuel - vehicle_exp - general_exp
    - trip_list:
        SELECT t.ref, t.from_loc, t.to_loc, t.run_no, t.amount, t.damount, t.camount,
               v.plate_number, d.name AS driver_name
        FROM trips t
        JOIN vehicles v ON v.id = t.vehicle_id
        JOIN drivers d ON d.id = t.driver_id
        WHERE t.date = :date
        ORDER BY t.id ASC
  
  Output UI:
    Row — 5 KPI cards: Trips Today | Revenue | Fuel Spend | Expenses | Net Position
    Below: DataTable — Ref | Vehicle | Driver | From | To | Run No | Amount | Driver Amt | Cleaner Amt
    Export buttons: Excel, PDF, Print
  
  Tables: trips, vehicles, drivers, fuel_expenses, vehicle_expenses, general_expenses

--- Fleet Reports ---

3. Vehicle Performance Report
Per vehicle revenue, fuel cost, expenses and profit.

  Files: reports/vehicle_performance/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Vehicle (optional dropdown populated from vehicles table, default: All)
  
  API ?action=report&from=&to=&vehicle_id=:
    SELECT
      v.id, v.ref, v.plate_number, v.make, v.model,
      COALESCE(t.revenue, 0)       AS revenue,
      COALESCE(fe.fuel_cost, 0)    AS fuel_cost,
      COALESCE(ve.veh_expense, 0)  AS vehicle_expense,
      COALESCE(t.revenue, 0) - COALESCE(fe.fuel_cost, 0) - COALESCE(ve.veh_expense, 0) AS profit
    FROM vehicles v
    LEFT JOIN (
      SELECT vehicle_id, SUM(amount) AS revenue
      FROM trips WHERE date BETWEEN :from AND :to
      GROUP BY vehicle_id
    ) t ON t.vehicle_id = v.id
    LEFT JOIN (
      SELECT vehicle_id, SUM(total) AS fuel_cost
      FROM fuel_expenses WHERE date BETWEEN :from AND :to
      GROUP BY vehicle_id
    ) fe ON fe.vehicle_id = v.id
    LEFT JOIN (
      SELECT vehicle_id, SUM(amount) AS veh_expense
      FROM vehicle_expenses WHERE date BETWEEN :from AND :to
      GROUP BY vehicle_id
    ) ve ON ve.vehicle_id = v.id
    WHERE (:vehicle_id IS NULL OR v.id = :vehicle_id)
    ORDER BY profit DESC
  
  Output columns: Ref | Plate | Make | Model | Revenue (LKR) | Fuel Cost (LKR) | Veh. Expense (LKR) | Profit (LKR)
  Totals row: sum all numeric columns at the bottom
  
  UI: DataTable with totals row. Profit column colored green (positive) / red (negative). Export: Excel, PDF, Print
  
  Tables: vehicles, trips, fuel_expenses, vehicle_expenses

4. Vehicle Utilization Report
Counts trips per vehicle to identify underutilized assets.

  Files: reports/vehicle_utilization/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    SELECT
      v.ref, v.plate_number, v.make, v.model, v.status,
      COALESCE(t.trip_count, 0)   AS trip_count,
      COALESCE(t.total_mileage, 0) AS total_mileage,
      COALESCE(t.days_active, 0)  AS days_active
    FROM vehicles v
    LEFT JOIN (
      SELECT vehicle_id,
             COUNT(*) AS trip_count,
             SUM(mileage) AS total_mileage,
             COUNT(DISTINCT date) AS days_active
      FROM trips WHERE date BETWEEN :from AND :to
      GROUP BY vehicle_id
    ) t ON t.vehicle_id = v.id
    ORDER BY trip_count DESC
  
  Output columns: Ref | Plate | Make | Model | Status | Trip Count | Total Mileage (km) | Days Active
  Highlight rows in light red where trip_count = 0
  
  UI: DataTable with conditional row coloring. Export: Excel, PDF, Print
  
  Tables: vehicles, trips

5. Mileage Report
Total kilometers traveled based on trip opening and closing KM.

  Files: reports/mileage_report/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Vehicle (optional dropdown, default: All)
  
  API ?action=report&from=&to=&vehicle_id=:
    SELECT
      v.ref, v.plate_number, v.make, v.model,
      MIN(t.opening_km) AS period_opening_km,
      MAX(t.closing_km) AS period_closing_km,
      SUM(t.mileage)    AS total_mileage,
      COUNT(t.id)       AS trip_count
    FROM vehicles v
    JOIN trips t ON t.vehicle_id = v.id
    WHERE t.date BETWEEN :from AND :to
      AND (:vehicle_id IS NULL OR v.id = :vehicle_id)
    GROUP BY v.id, v.ref, v.plate_number, v.make, v.model
    ORDER BY total_mileage DESC
  
  Output columns: Ref | Plate | Make | Model | Opening KM | Closing KM | Total Mileage (km) | Trips
  Totals row: sum Total Mileage and Trips
  
  UI: DataTable with totals. Export: Excel, PDF, Print
  
  Tables: vehicles, trips

6. Fuel Efficiency Report
Total KM / Total Litres to calculate KM per litre.

  Files: reports/fuel_efficiency/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    SELECT
      v.ref, v.plate_number, v.make, v.model, v.fuel_type,
      COALESCE(t.total_km, 0)     AS total_km,
      COALESCE(fe.total_litres, 0) AS total_litres,
      CASE WHEN COALESCE(fe.total_litres, 0) > 0
           THEN ROUND(COALESCE(t.total_km, 0) / fe.total_litres, 2)
           ELSE NULL END AS km_per_litre
    FROM vehicles v
    LEFT JOIN (
      SELECT vehicle_id, SUM(mileage) AS total_km
      FROM trips WHERE date BETWEEN :from AND :to
      GROUP BY vehicle_id
    ) t ON t.vehicle_id = v.id
    LEFT JOIN (
      SELECT vehicle_id, SUM(liters) AS total_litres
      FROM fuel_expenses WHERE date BETWEEN :from AND :to
      GROUP BY vehicle_id
    ) fe ON fe.vehicle_id = v.id
    WHERE COALESCE(t.total_km, 0) > 0 OR COALESCE(fe.total_litres, 0) > 0
    ORDER BY km_per_litre ASC NULLS LAST
  
  Output columns: Ref | Plate | Make | Model | Fuel Type | Total KM | Total Litres | KM per Litre
  Flag rows in red where km_per_litre < 5
  
  UI: DataTable. Color km_per_litre column: green >= 8, orange 5–7.9, red < 5. Export: Excel, PDF, Print
  
  Tables: vehicles, trips, fuel_expenses

7. Fuel Cost Trend Report
Monthly fuel expenditure trend analysis.

  Files: reports/fuel_cost_trend/ → .php, .js, _data.php
  
  Filters:
    - Year (dropdown, default: current year)
    - Vehicle (optional dropdown, default: All)
  
  API ?action=report&year=&vehicle_id=:
    SELECT
      TO_CHAR(date, 'YYYY-MM') AS month,
      TO_CHAR(date, 'Mon YYYY') AS month_label,
      SUM(liters) AS total_litres,
      ROUND(AVG(rate), 2) AS avg_rate,
      SUM(total) AS total_cost
    FROM fuel_expenses
    WHERE EXTRACT(YEAR FROM date) = :year
      AND (:vehicle_id IS NULL OR vehicle_id = :vehicle_id)
    GROUP BY TO_CHAR(date, 'YYYY-MM'), TO_CHAR(date, 'Mon YYYY')
    ORDER BY month ASC
  
  Output columns: Month | Total Litres | Avg Rate (LKR/L) | Total Cost (LKR)
  Totals row: sum Litres and Total Cost
  
  UI: Line chart (ApexCharts) showing Total Cost per month at top, DataTable with export below
  
  Tables: fuel_expenses, vehicles (for filter dropdown only)

8. Vehicle Expense Analysis
Breakdown by repair, service, tyre, battery, insurance and other categories.

  Files: reports/vehicle_expense_analysis/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Vehicle (optional dropdown, default: All)
  
  API ?action=report&from=&to=&vehicle_id=:
    SELECT
      v.ref, v.plate_number,
      COALESCE(SUM(CASE WHEN ve.category = 'repair'     THEN ve.amount END), 0) AS repair,
      COALESCE(SUM(CASE WHEN ve.category = 'service'    THEN ve.amount END), 0) AS service,
      COALESCE(SUM(CASE WHEN ve.category = 'tyre'       THEN ve.amount END), 0) AS tyre,
      COALESCE(SUM(CASE WHEN ve.category = 'battery'    THEN ve.amount END), 0) AS battery,
      COALESCE(SUM(CASE WHEN ve.category = 'insurance'  THEN ve.amount END), 0) AS insurance,
      COALESCE(SUM(CASE WHEN ve.category = 'other'      THEN ve.amount END), 0) AS other,
      COALESCE(SUM(ve.amount), 0) AS total
    FROM vehicles v
    JOIN vehicle_expenses ve ON ve.vehicle_id = v.id
    WHERE ve.date BETWEEN :from AND :to
      AND (:vehicle_id IS NULL OR v.id = :vehicle_id)
    GROUP BY v.id, v.ref, v.plate_number
    ORDER BY total DESC
  
  Output columns: Ref | Plate | Repair | Service | Tyre | Battery | Insurance | Other | Total
  Totals row: sum all category columns
  NOTE: Confirm actual enum values in vehicle_expenses.category before coding
  
  UI: DataTable with totals row. Export: Excel, PDF, Print
  
  Tables: vehicles, vehicle_expenses

9. Most Expensive Vehicle Report
Ranks vehicles by total operating cost.

  Files: reports/most_expensive_vehicle/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    SELECT
      v.ref, v.plate_number, v.make, v.model, v.year,
      COALESCE(fe.fuel_total, 0)   AS fuel_cost,
      COALESCE(ve.veh_total, 0)    AS maintenance_cost,
      COALESCE(fe.fuel_total, 0) + COALESCE(ve.veh_total, 0) AS total_cost,
      ROW_NUMBER() OVER (ORDER BY COALESCE(fe.fuel_total,0) + COALESCE(ve.veh_total,0) DESC) AS rank
    FROM vehicles v
    LEFT JOIN (
      SELECT vehicle_id, SUM(total) AS fuel_total
      FROM fuel_expenses WHERE date BETWEEN :from AND :to
      GROUP BY vehicle_id
    ) fe ON fe.vehicle_id = v.id
    LEFT JOIN (
      SELECT vehicle_id, SUM(amount) AS veh_total
      FROM vehicle_expenses WHERE date BETWEEN :from AND :to
      GROUP BY vehicle_id
    ) ve ON ve.vehicle_id = v.id
    ORDER BY total_cost DESC
  
  Output columns: Rank | Ref | Plate | Make | Model | Year | Fuel Cost (LKR) | Maintenance (LKR) | Total Cost (LKR)
  
  UI: DataTable. Top row (rank 1) highlighted. Export: Excel, PDF, Print
  
  Tables: vehicles, fuel_expenses, vehicle_expenses

--- Operations Reports ---

10. Trip Revenue Report
Total revenue generated from trips over a selected period.

  Files: reports/trip_revenue/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Vehicle (optional dropdown, default: All)
    - Driver (optional dropdown, default: All)
  
  API ?action=report&from=&to=&vehicle_id=&driver_id=:
    SELECT
      t.ref, t.date, t.run_no,
      v.plate_number,
      d.name AS driver_name,
      t.from_loc, t.to_loc,
      t.item_name,
      t.amount, t.damount, t.camount,
      COALESCE(t.day_pay, 0) AS day_pay
    FROM trips t
    JOIN vehicles v ON v.id = t.vehicle_id
    JOIN drivers d ON d.id = t.driver_id
    WHERE t.date BETWEEN :from AND :to
      AND (:vehicle_id IS NULL OR t.vehicle_id = :vehicle_id)
      AND (:driver_id IS NULL OR t.driver_id = :driver_id)
    ORDER BY t.date ASC, t.id ASC
  
  Output columns: Ref | Date | Vehicle | Driver | From | To | Item | Run No | Amount | Driver Amt | Cleaner Amt | Day Pay
  Totals row: sum Amount, Driver Amt, Cleaner Amt, Day Pay
  
  UI: DataTable with totals row. Export: Excel, PDF, Print
  
  Tables: trips, vehicles, drivers

11. Route Profitability Report
Revenue grouped by route (From-To locations).

  Files: reports/route_profitability/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    SELECT
      t.from_loc, t.to_loc,
      COUNT(*) AS trip_count,
      SUM(t.amount) AS total_revenue,
      ROUND(AVG(t.amount), 2) AS avg_revenue,
      MAX(t.amount) AS max_revenue,
      MIN(t.amount) AS min_revenue
    FROM trips t
    WHERE t.date BETWEEN :from AND :to
    GROUP BY t.from_loc, t.to_loc
    ORDER BY total_revenue DESC
  
  Output columns: From | To | Trip Count | Total Revenue (LKR) | Avg Revenue (LKR) | Max | Min
  Totals row: sum Trip Count and Total Revenue
  
  UI: DataTable. Export: Excel, PDF, Print
  
  Tables: trips

12. Trip Frequency Report
Most frequently serviced routes based on trip count.

  Files: reports/trip_frequency/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    SELECT
      t.from_loc, t.to_loc,
      COUNT(*) AS trip_count,
      MIN(t.date) AS first_trip,
      MAX(t.date) AS last_trip,
      SUM(t.amount) AS total_revenue
    FROM trips t
    WHERE t.date BETWEEN :from AND :to
    GROUP BY t.from_loc, t.to_loc
    ORDER BY trip_count DESC
  
  Output columns: From | To | Trip Count | First Trip | Last Trip | Total Revenue (LKR)
  Top 10 rows highlighted
  
  UI: DataTable. Show top 10 highlighted. Export: Excel, PDF, Print
  
  Tables: trips

13. Department-wise Revenue Report
Revenue generated by department.

  Files: reports/department_revenue/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    SELECT
      COALESCE(t.department, 'Unassigned') AS department,
      COUNT(*) AS trip_count,
      SUM(t.amount) AS total_revenue,
      ROUND(100.0 * SUM(t.amount) / SUM(SUM(t.amount)) OVER (), 2) AS pct_of_total
    FROM trips t
    WHERE t.date BETWEEN :from AND :to
    GROUP BY COALESCE(t.department, 'Unassigned')
    ORDER BY total_revenue DESC
  
  Output columns: Department | Trip Count | Total Revenue (LKR) | % of Total
  Totals row: sum Trip Count and Total Revenue
  
  UI: Donut/pie chart (ApexCharts) + DataTable below. Export: Excel, PDF, Print
  
  Tables: trips

14. Item Type Revenue Report
Revenue generated by cargo/job type.

  Files: reports/item_type_revenue/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    SELECT
      COALESCE(i.name, t.item_name, 'Unspecified') AS item_type,
      COUNT(*) AS trip_count,
      SUM(t.amount) AS total_revenue,
      ROUND(AVG(t.amount), 2) AS avg_revenue,
      ROUND(100.0 * SUM(t.amount) / SUM(SUM(t.amount)) OVER (), 2) AS pct_of_total
    FROM trips t
    LEFT JOIN items i ON i.id = t.item_id
    WHERE t.date BETWEEN :from AND :to
    GROUP BY COALESCE(i.name, t.item_name, 'Unspecified')
    ORDER BY total_revenue DESC
  
  Output columns: Item / Cargo Type | Trip Count | Total Revenue (LKR) | Avg Revenue (LKR) | % of Total
  Totals row: sum Trip Count and Total Revenue
  
  UI: DataTable + pie chart. Export: Excel, PDF, Print
  
  Tables: trips, items

--- Staff Reports ---

15. Driver Performance Report
Trips, mileage and revenue generated by each driver.

  Files: reports/driver_performance/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Driver (optional dropdown, default: All)
  
  API ?action=report&from=&to=&driver_id=:
    SELECT
      d.ref, d.name, d.status,
      COUNT(t.id) AS trip_count,
      COALESCE(SUM(t.mileage), 0) AS total_mileage,
      COALESCE(SUM(t.amount), 0) AS total_revenue,
      COALESCE(SUM(t.damount), 0) AS total_driver_earning,
      COALESCE(SUM(COALESCE(t.day_pay, 0)), 0) AS total_day_pay,
      COALESCE(SUM(t.damount), 0) + COALESCE(SUM(COALESCE(t.day_pay, 0)), 0) AS gross_earning
    FROM drivers d
    LEFT JOIN trips t ON t.driver_id = d.id AND t.date BETWEEN :from AND :to
    WHERE (:driver_id IS NULL OR d.id = :driver_id)
    GROUP BY d.id, d.ref, d.name, d.status
    ORDER BY total_revenue DESC
  
  Output columns: Ref | Name | Status | Trip Count | Total Mileage (km) | Revenue Generated (LKR) | Driver Earning (LKR) | Day Pay (LKR) | Gross Earning (LKR)
  Totals row: sum all numeric columns
  
  UI: DataTable with totals. Export: Excel, PDF, Print
  
  Tables: drivers, trips

16. Driver Earnings Report
Total driver earnings from trip allocations.

  Files: reports/driver_earnings/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Driver (optional dropdown, default: All)
  
  API ?action=report&from=&to=&driver_id=:
    SELECT
      d.ref AS driver_ref, d.name AS driver_name,
      t.ref AS trip_ref, t.date,
      t.from_loc, t.to_loc,
      t.amount AS trip_amount,
      t.damount AS driver_amount,
      COALESCE(t.day_pay, 0) AS day_pay,
      t.damount + COALESCE(t.day_pay, 0) AS total_earning
    FROM trips t
    JOIN drivers d ON d.id = t.driver_id
    WHERE t.date BETWEEN :from AND :to
      AND (:driver_id IS NULL OR t.driver_id = :driver_id)
    ORDER BY d.name ASC, t.date ASC
  
  Output columns: Driver Ref | Driver Name | Trip Ref | Date | From | To | Trip Amount (LKR) | Driver Amount (LKR) | Day Pay (LKR) | Total Earning (LKR)
  Grouped by driver with subtotal per driver, grand total at bottom
  
  UI: DataTable grouped by driver. Export: Excel, PDF, Print
  
  Tables: trips, drivers

17. Driver Advance Report
Advance payments issued to drivers.

  Files: reports/driver_advance_report/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Driver (optional dropdown, default: All)
  
  API ?action=report&from=&to=&driver_id=:
    SELECT
      d.ref AS driver_ref, d.name AS driver_name,
      ap.ref AS advance_ref, ap.date, ap.amount
    FROM advance_payments ap
    JOIN drivers d ON d.id = ap.driver_id
    WHERE ap.recipient_type = 'driver'
      AND ap.date BETWEEN :from AND :to
      AND (:driver_id IS NULL OR ap.driver_id = :driver_id)
    ORDER BY d.name ASC, ap.date ASC
  
  Output columns: Driver Ref | Driver Name | Advance Ref | Date | Amount (LKR)
  Grouped by driver with subtotal per driver, grand total at bottom
  
  UI: DataTable grouped by driver. Export: Excel, PDF, Print
  
  Tables: advance_payments, drivers

18. Driver Loan Report
Outstanding loan balances per driver.
  *** BLOCKED: loans table does not exist. Must create table first. ***
  
  Required new table: loans
    id SERIAL PRIMARY KEY
    ref VARCHAR(20) NOT NULL UNIQUE
    recipient_type VARCHAR(10) NOT NULL  -- 'driver' or 'cleaner'
    driver_id INTEGER REFERENCES drivers(id)
    cleaner_id INTEGER REFERENCES cleaners(id)
    date DATE NOT NULL
    principal_amount NUMERIC(12,2) NOT NULL
    recovered_amount NUMERIC(12,2) NOT NULL DEFAULT 0
    status VARCHAR(10) NOT NULL DEFAULT 'active'  -- 'active' or 'settled'
    created_at TIMESTAMPTZ DEFAULT NOW()
    updated_at TIMESTAMPTZ DEFAULT NOW()
  
  Files (after table created): reports/driver_loan_report/ → .php, .js, _data.php
  
  Filters:
    - Driver (optional dropdown, default: All)
    - Status (All / Active / Settled)
  
  API ?action=report&driver_id=&status=:
    SELECT
      d.ref, d.name,
      l.ref AS loan_ref, l.date,
      l.principal_amount,
      l.recovered_amount,
      l.principal_amount - l.recovered_amount AS remaining_balance,
      l.status
    FROM loans l
    JOIN drivers d ON d.id = l.driver_id
    WHERE l.recipient_type = 'driver'
      AND (:driver_id IS NULL OR l.driver_id = :driver_id)
      AND (:status = 'all' OR l.status = :status)
    ORDER BY d.name ASC, l.date ASC
  
  Output columns: Driver Ref | Driver Name | Loan Ref | Date | Principal (LKR) | Recovered (LKR) | Remaining (LKR) | Status
  
  Tables: loans (not yet created), drivers

19. Driver Deduction Report
Salary deductions applied to drivers.
  *** BLOCKED: deductions table does not exist. Must create table first. ***
  
  Required new table: deductions
    id SERIAL PRIMARY KEY
    ref VARCHAR(20) NOT NULL UNIQUE
    recipient_type VARCHAR(10) NOT NULL  -- 'driver' or 'cleaner'
    driver_id INTEGER REFERENCES drivers(id)
    cleaner_id INTEGER REFERENCES cleaners(id)
    date DATE NOT NULL
    amount NUMERIC(12,2) NOT NULL
    reason VARCHAR(255) NOT NULL
    created_at TIMESTAMPTZ DEFAULT NOW()
    updated_at TIMESTAMPTZ DEFAULT NOW()
  
  Files (after table created): reports/driver_deduction_report/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Driver (optional dropdown, default: All)
  
  API ?action=report&from=&to=&driver_id=:
    SELECT
      d.ref, d.name,
      ded.ref AS ded_ref, ded.date,
      ded.amount, ded.reason
    FROM deductions ded
    JOIN drivers d ON d.id = ded.driver_id
    WHERE ded.recipient_type = 'driver'
      AND ded.date BETWEEN :from AND :to
      AND (:driver_id IS NULL OR ded.driver_id = :driver_id)
    ORDER BY d.name ASC, ded.date ASC
  
  Output columns: Driver Ref | Driver Name | Deduction Ref | Date | Amount (LKR) | Reason
  Subtotals per driver, grand total at bottom
  
  Tables: deductions (not yet created), drivers

20. Driver Salary Sheet
Net salary after advances, deductions and loan recovery.

  Files: reports/driver_salary_sheet/ → .php, .js, _data.php
  
  Filters:
    - Month (month/year picker, default: current month)
    - Driver (required — one driver at a time for payslip view)
  
  API ?action=report&month=YYYY-MM&driver_id=:
    Step 1 — Earnings:
      SELECT SUM(damount) AS trip_earnings, SUM(COALESCE(day_pay,0)) AS day_pay_total
      FROM trips
      WHERE driver_id = :driver_id AND TO_CHAR(date,'YYYY-MM') = :month
    
    Step 2 — Advances:
      SELECT COALESCE(SUM(amount), 0) AS total_advances
      FROM advance_payments
      WHERE driver_id = :driver_id AND recipient_type = 'driver'
        AND TO_CHAR(date,'YYYY-MM') = :month
    
    Step 3 — Deductions (when table exists):
      SELECT COALESCE(SUM(amount), 0) AS total_deductions
      FROM deductions
      WHERE driver_id = :driver_id AND recipient_type = 'driver'
        AND TO_CHAR(date,'YYYY-MM') = :month
    
    Step 4 — Loan recovery (when table exists):
      SELECT COALESCE(SUM(recovery_amount), 0) AS loan_recovery FROM loan_recoveries ...
    
    Compute: net_salary = trip_earnings + day_pay_total - total_advances - total_deductions - loan_recovery
  
  Output UI: Payslip-style card layout (not a DataTable):
    Header: Driver name, ref, month
    Earnings section: Trip Earnings | Day Pay | Gross Earnings
    Deductions section: Advances | Deductions | Loan Recovery | Total Deductions
    Net Payable (large, bold)
    Print button opens a clean print view
  
  NOTE: Deductions and loan recovery rows show 0 until those tables exist
  
  Tables: trips, advance_payments, drivers, [deductions — not yet created], [loans — not yet created]

21. Cleaner Performance Report
Trips and operational activity for cleaners.

  Files: reports/cleaner_performance/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Cleaner (optional dropdown, default: All)
  
  API ?action=report&from=&to=&cleaner_id=:
    SELECT
      c.ref, c.name, c.status,
      COUNT(t.id) AS trip_count,
      COALESCE(SUM(t.camount), 0) AS total_cleaner_earning
    FROM cleaners c
    LEFT JOIN trips t ON t.cleaner_id = c.id AND t.date BETWEEN :from AND :to
    WHERE (:cleaner_id IS NULL OR c.id = :cleaner_id)
    GROUP BY c.id, c.ref, c.name, c.status
    ORDER BY trip_count DESC
  
  Output columns: Ref | Name | Status | Trip Count | Total Cleaner Earning (LKR)
  Totals row: sum Trip Count and Total Cleaner Earning
  
  UI: DataTable with totals. Export: Excel, PDF, Print
  
  Tables: cleaners, trips

22. Cleaner Salary Report
Cleaner payroll calculation and payment summary.

  Files: reports/cleaner_salary_report/ → .php, .js, _data.php
  
  Filters:
    - Month (month/year picker, default: current month)
    - Cleaner (required — one cleaner at a time for payslip view)
  
  API ?action=report&month=YYYY-MM&cleaner_id=:
    Step 1 — Earnings:
      SELECT COALESCE(SUM(camount), 0) AS trip_earnings
      FROM trips
      WHERE cleaner_id = :cleaner_id AND TO_CHAR(date,'YYYY-MM') = :month
    
    Step 2 — Advances:
      SELECT COALESCE(SUM(amount), 0) AS total_advances
      FROM advance_payments
      WHERE cleaner_id = :cleaner_id AND recipient_type = 'cleaner'
        AND TO_CHAR(date,'YYYY-MM') = :month
    
    Compute: net_salary = trip_earnings - total_advances
  
  Output UI: Same payslip card layout as report 20
  NOTE: Deductions and loan recovery rows show 0 until those tables exist
  
  Tables: cleaners, trips, advance_payments

23. Cleaner Loan Report
Outstanding cleaner loans.
  *** BLOCKED: loans table does not exist (same as report 18). ***
  
  Files (after table created): reports/cleaner_loan_report/ → .php, .js, _data.php
  
  Filters:
    - Cleaner (optional dropdown, default: All)
    - Status (All / Active / Settled)
  
  API ?action=report&cleaner_id=&status=:
    SELECT
      c.ref, c.name,
      l.ref AS loan_ref, l.date,
      l.principal_amount, l.recovered_amount,
      l.principal_amount - l.recovered_amount AS remaining_balance,
      l.status
    FROM loans l
    JOIN cleaners c ON c.id = l.cleaner_id
    WHERE l.recipient_type = 'cleaner'
      AND (:cleaner_id IS NULL OR l.cleaner_id = :cleaner_id)
      AND (:status = 'all' OR l.status = :status)
    ORDER BY c.name ASC, l.date ASC
  
  Output columns: Cleaner Ref | Cleaner Name | Loan Ref | Date | Principal (LKR) | Recovered (LKR) | Remaining (LKR) | Status
  
  Tables: loans (not yet created), cleaners

24. Cleaner Advance Report
Advances issued to cleaners.

  Files: reports/cleaner_advance_report/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Cleaner (optional dropdown, default: All)
  
  API ?action=report&from=&to=&cleaner_id=:
    SELECT
      c.ref AS cleaner_ref, c.name AS cleaner_name,
      ap.ref AS advance_ref, ap.date, ap.amount
    FROM advance_payments ap
    JOIN cleaners c ON c.id = ap.cleaner_id
    WHERE ap.recipient_type = 'cleaner'
      AND ap.date BETWEEN :from AND :to
      AND (:cleaner_id IS NULL OR ap.cleaner_id = :cleaner_id)
    ORDER BY c.name ASC, ap.date ASC
  
  Output columns: Cleaner Ref | Cleaner Name | Advance Ref | Date | Amount (LKR)
  Subtotals per cleaner, grand total at bottom
  
  Tables: advance_payments, cleaners

--- Financial Reports ---

25. Cash Flow Statement
All cash inflows and outflows with running balance.

  Files: reports/cash_flow_statement/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    Build a UNION of all transactions, sorted by date:
    
    SELECT date, 'Trip Revenue' AS type, ref AS reference, amount AS inflow, 0 AS outflow FROM trips WHERE date BETWEEN :from AND :to
    UNION ALL
    SELECT date, 'Fuel Expense', ref, 0, total FROM fuel_expenses WHERE date BETWEEN :from AND :to
    UNION ALL
    SELECT date, 'Vehicle Expense', ref, 0, amount FROM vehicle_expenses WHERE date BETWEEN :from AND :to
    UNION ALL
    SELECT date, 'General Expense', ref, 0, amount FROM general_expenses WHERE date BETWEEN :from AND :to
    UNION ALL
    SELECT date, 'Advance Payment', ref, 0, amount FROM advance_payments WHERE date BETWEEN :from AND :to
    ORDER BY date ASC, type ASC
    
    Compute running balance in PHP after fetching rows:
      running_balance += inflow - outflow for each row
  
  Output columns: Date | Type | Reference | Inflow (LKR) | Outflow (LKR) | Running Balance (LKR)
  Color inflow rows green, outflow rows light red. Bold running balance column
  
  UI: DataTable (no pagination — show all rows for the period). Totals row: total inflow, total outflow, final balance. Export: Excel, PDF, Print
  
  Tables: trips, fuel_expenses, vehicle_expenses, general_expenses, advance_payments

26. Income vs Expense Report
Compares trip income against operating expenses.

  Files: reports/income_vs_expense/ → .php, .js, _data.php
  
  Filters:
    - Year (dropdown, default: current year)
  
  API ?action=report&year=:
    SELECT
      TO_CHAR(m.month_date, 'Mon YYYY') AS month_label,
      TO_CHAR(m.month_date, 'YYYY-MM') AS month_key,
      COALESCE(inc.income, 0) AS income,
      COALESCE(exp.expenses, 0) AS expenses,
      COALESCE(inc.income, 0) - COALESCE(exp.expenses, 0) AS net
    FROM generate_series(
      DATE_TRUNC('year', MAKE_DATE(:year,1,1)),
      DATE_TRUNC('year', MAKE_DATE(:year,1,1)) + INTERVAL '11 months',
      INTERVAL '1 month'
    ) AS m(month_date)
    LEFT JOIN (
      SELECT DATE_TRUNC('month', date) AS m, SUM(amount) AS income FROM trips WHERE EXTRACT(YEAR FROM date) = :year GROUP BY 1
    ) inc ON inc.m = m.month_date
    LEFT JOIN (
      SELECT DATE_TRUNC('month', date) AS m,
        SUM(fe.total + ve.amt + ge.amt + ap.amt) AS expenses
      FROM ... -- combine all expense tables grouped by month
    ) exp ON exp.m = m.month_date
    ORDER BY month_key ASC
    
    NOTE: Expense sub-query requires UNION across fuel_expenses, vehicle_expenses, general_expenses, advance_payments grouped by month then summed
  
  Output columns: Month | Income (LKR) | Expenses (LKR) | Net (LKR)
  Net column: green if positive, red if negative
  
  UI: Grouped bar chart (ApexCharts, income vs expense per month) + DataTable below. Export: Excel, PDF, Print
  
  Tables: trips, fuel_expenses, vehicle_expenses, general_expenses, advance_payments

27. Expense Summary Report
Expense totals grouped by category.

  Files: reports/expense_summary/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    Return an array of category objects:
    1. Fuel:             SELECT SUM(total) FROM fuel_expenses WHERE date BETWEEN :from AND :to
    2. Vehicle Repair:   SELECT SUM(amount) FROM vehicle_expenses WHERE category='repair' AND date BETWEEN ...
    3. Vehicle Service:  ... category='service'
    4. Vehicle Tyre:     ... category='tyre'
    5. Vehicle Battery:  ... category='battery'
    6. Vehicle Insurance: ... category='insurance'
    7. Vehicle Other:    ... category='other'
    8. General Expenses per type:
       SELECT get.name, SUM(ge.amount) FROM general_expenses ge JOIN general_expense_types get ON get.id=ge.expense_type_id WHERE ge.date BETWEEN :from AND :to GROUP BY get.name
    9. Advance Payments: SELECT SUM(amount) FROM advance_payments WHERE date BETWEEN :from AND :to
  
  Output columns: Category | Amount (LKR) | % of Total
  Grand total row at bottom
  
  UI: Pie/donut chart (ApexCharts) + DataTable. Export: Excel, PDF, Print
  
  Tables: fuel_expenses, vehicle_expenses, general_expenses, general_expense_types, advance_payments

28. Monthly Profit Report
Monthly revenue minus expenses.

  Files: reports/monthly_profit/ → .php, .js, _data.php
  
  Filters:
    - Year (dropdown, default: current year)
  
  API ?action=report&year=:
    Generate all 12 months, left join income and expenses per month:
    - Income: SUM(trips.amount) per month
    - Expenses: SUM across fuel_expenses + vehicle_expenses + general_expenses + advance_payments per month
    - Profit: income - expenses
    - Margin %: ROUND(100.0 * profit / NULLIF(income, 0), 1)
  
  Output columns: Month | Revenue (LKR) | Expenses (LKR) | Profit (LKR) | Margin %
  Highlight red rows where profit < 0. Totals row: sum Revenue, Expenses, Profit
  
  UI: Line chart (profit trend over months, ApexCharts) + DataTable below. Export: Excel, PDF, Print
  
  Tables: trips, fuel_expenses, vehicle_expenses, general_expenses, advance_payments

29. Revenue Trend Report
Monthly revenue growth analysis.

  Files: reports/revenue_trend/ → .php, .js, _data.php
  
  Filters:
    - Year (dropdown, default: current year)
  
  API ?action=report&year=:
    Generate all 12 months:
    SELECT
      month_label,
      COALESCE(revenue, 0) AS revenue,
      COALESCE(trip_count, 0) AS trip_count,
      ROUND(COALESCE(revenue,0) / NULLIF(COALESCE(trip_count,0),0), 2) AS avg_per_trip,
      -- month over month change computed in PHP
    FROM generate_series(...) LEFT JOIN (
      SELECT DATE_TRUNC('month', date) AS m, SUM(amount) AS revenue, COUNT(*) AS trip_count
      FROM trips WHERE EXTRACT(YEAR FROM date) = :year GROUP BY 1
    ) t ON ...
  
  Compute in PHP: mom_change = ((this_month - prev_month) / prev_month) * 100
  
  Output columns: Month | Revenue (LKR) | Trip Count | Avg per Trip (LKR) | MoM Change %
  MoM column: green arrow up, red arrow down
  
  UI: Line chart (ApexCharts) + DataTable. Export: Excel, PDF, Print
  
  Tables: trips

30. Expense Trend Report
Monthly expense growth analysis.

  Files: reports/expense_trend/ → .php, .js, _data.php
  
  Filters:
    - Year (dropdown, default: current year)
  
  API ?action=report&year=:
    Generate all 12 months, join each expense table:
    - Fuel per month: SELECT DATE_TRUNC('month',date) AS m, SUM(total) FROM fuel_expenses GROUP BY 1
    - Vehicle expenses per month: same pattern
    - General expenses per month: same pattern
    - Advances per month: same pattern
    - Total = sum of all four per month
    Compute MoM change in PHP
  
  Output columns: Month | Fuel (LKR) | Vehicle Exp (LKR) | General Exp (LKR) | Advances (LKR) | Total (LKR) | MoM Change %
  Totals row: sum all numeric columns (except MoM %)
  
  UI: Stacked bar chart (ApexCharts, one stack per expense type) + DataTable. Export: Excel, PDF, Print
  
  Tables: fuel_expenses, vehicle_expenses, general_expenses, advance_payments

--- Payroll Reports ---

31. My Trips
Employee self-service trip history.
  *** REQUIRES: session/login system + user-to-driver/cleaner mapping ***

  Files: reports/my_trips/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Reads logged-in user ID from session
  
  API ?action=report&from=&to= (user_id from session):
    SELECT
      t.ref, t.date, v.plate_number,
      t.from_loc, t.to_loc, t.run_no, t.item_name,
      t.amount, t.damount, COALESCE(t.day_pay,0) AS day_pay
    FROM trips t
    JOIN vehicles v ON v.id = t.vehicle_id
    WHERE t.user_id = :session_user_id
      AND t.date BETWEEN :from AND :to
    ORDER BY t.date DESC
  
  Output columns: Ref | Date | Vehicle | From | To | Run No | Item | Amount (LKR) | My Earning (LKR) | Day Pay (LKR)
  
  Tables: trips, vehicles, app_users

32. My Earnings
Employee self-service earnings statement.
  *** REQUIRES: session/login system + user-to-driver/cleaner mapping ***

  Files: reports/my_earnings/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to= (user_id from session):
    Determine role from session (driver or cleaner)
    If driver:
      SELECT SUM(damount) AS earnings, SUM(COALESCE(day_pay,0)) AS day_pay, COUNT(*) AS trips
      FROM trips WHERE driver_id = :linked_driver_id AND date BETWEEN :from AND :to
    If cleaner:
      SELECT SUM(camount) AS earnings, COUNT(*) AS trips
      FROM trips WHERE cleaner_id = :linked_cleaner_id AND date BETWEEN :from AND :to
  
  Output UI: Summary KPI cards: Trip Count | Total Earnings | Day Pay | Gross Total
  Below: trip-by-trip breakdown list (same columns as report 31)
  
  Tables: trips, app_users, drivers, cleaners

33. My Salary Statement
Employee salary breakdown.
  *** REQUIRES: session/login system + user-to-driver/cleaner mapping ***

  Files: reports/my_salary_statement/ → .php, .js, _data.php
  
  Filters:
    - Month (month/year picker, default: current month)
  
  API ?action=report&month=YYYY-MM (user_id from session):
    Same calculation as reports 20 / 22 but scoped to the logged-in user's driver or cleaner record
  
  Output UI: Same payslip card layout (earnings, deductions, net payable). Print button
  
  Tables: trips, advance_payments, app_users, drivers, cleaners

34. My Advances
Employee advance history.
  *** REQUIRES: session/login system + user-to-driver/cleaner mapping ***

  Files: reports/my_advances/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current year)
  
  API ?action=report&from=&to= (user_id from session):
    SELECT ap.ref, ap.date, ap.amount
    FROM advance_payments ap
    WHERE (ap.driver_id = :linked_driver_id OR ap.cleaner_id = :linked_cleaner_id)
      AND ap.date BETWEEN :from AND :to
    ORDER BY ap.date DESC
    
    Return total at the end
  
  Output columns: Ref | Date | Amount (LKR)
  Total row at bottom
  
  Tables: advance_payments, app_users

35. My Loan Status
Loan amount, recovered amount and remaining balance.
  *** BLOCKED: loans table does not exist ***
  *** REQUIRES: session/login system + user-to-driver/cleaner mapping ***

  Files (after table created): reports/my_loan_status/ → .php, .js, _data.php
  
  Output UI: Card per loan showing: Principal | Recovered | Remaining Balance | Status badge
  Progress bar showing recovery percentage
  
  Tables: loans (not yet created), app_users

36. Top Performing Vehicle
Vehicle with highest profit contribution.

  Files: reports/top_performing_vehicle/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Top N (dropdown: Top 5 / Top 10 / All, default: Top 10)
  
  API ?action=report&from=&to=&limit=:
    SELECT
      v.ref, v.plate_number, v.make, v.model,
      COALESCE(t.revenue, 0) AS revenue,
      COALESCE(fe.fuel_cost, 0) + COALESCE(ve.veh_cost, 0) AS total_cost,
      COALESCE(t.revenue, 0) - COALESCE(fe.fuel_cost, 0) - COALESCE(ve.veh_cost, 0) AS profit,
      COALESCE(t.trip_count, 0) AS trip_count
    FROM vehicles v
    LEFT JOIN (SELECT vehicle_id, SUM(amount) AS revenue, COUNT(*) AS trip_count FROM trips WHERE date BETWEEN :from AND :to GROUP BY vehicle_id) t ON t.vehicle_id = v.id
    LEFT JOIN (SELECT vehicle_id, SUM(total) AS fuel_cost FROM fuel_expenses WHERE date BETWEEN :from AND :to GROUP BY vehicle_id) fe ON fe.vehicle_id = v.id
    LEFT JOIN (SELECT vehicle_id, SUM(amount) AS veh_cost FROM vehicle_expenses WHERE date BETWEEN :from AND :to GROUP BY vehicle_id) ve ON ve.vehicle_id = v.id
    ORDER BY profit DESC
    LIMIT :limit
  
  Output columns: Rank | Ref | Plate | Make | Model | Revenue (LKR) | Cost (LKR) | Profit (LKR) | Trips
  Rank 1 row highlighted in gold
  
  UI: Horizontal bar chart (profit per vehicle, ApexCharts) + DataTable. Export: Excel, PDF, Print
  
  Tables: vehicles, trips, fuel_expenses, vehicle_expenses

37. Top Performing Driver
Driver generating highest revenue.

  Files: reports/top_performing_driver/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
    - Top N (dropdown: Top 5 / Top 10 / All, default: Top 10)
  
  API ?action=report&from=&to=&limit=:
    SELECT
      d.ref, d.name,
      COUNT(t.id) AS trip_count,
      COALESCE(SUM(t.amount), 0) AS total_revenue,
      COALESCE(SUM(t.damount), 0) + COALESCE(SUM(COALESCE(t.day_pay,0)),0) AS total_earning,
      ROUND(COALESCE(SUM(t.amount),0) / NULLIF(COUNT(t.id),0), 2) AS avg_per_trip
    FROM drivers d
    LEFT JOIN trips t ON t.driver_id = d.id AND t.date BETWEEN :from AND :to
    GROUP BY d.id, d.ref, d.name
    ORDER BY total_revenue DESC
    LIMIT :limit
  
  Output columns: Rank | Ref | Name | Trip Count | Total Revenue (LKR) | Total Earning (LKR) | Avg per Trip (LKR)
  Rank 1 row highlighted in gold
  
  UI: Horizontal bar chart (revenue per driver) + DataTable. Export: Excel, PDF, Print
  
  Tables: drivers, trips

38. Lowest Fuel Efficiency Vehicle
Vehicle with worst KM/L ratio.

  Files: reports/lowest_fuel_efficiency/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    Same query as report 6 but ORDER BY km_per_litre ASC (worst first).
    Only include vehicles that have both trip mileage AND fuel expense records in the period.
    
    SELECT v.ref, v.plate_number, v.make, v.model, v.year,
      SUM(t.mileage) AS total_km, SUM(fe.liters) AS total_litres,
      ROUND(SUM(t.mileage) / NULLIF(SUM(fe.liters), 0), 2) AS km_per_litre
    FROM vehicles v
    JOIN trips t ON t.vehicle_id = v.id AND t.date BETWEEN :from AND :to
    JOIN fuel_expenses fe ON fe.vehicle_id = v.id AND fe.date BETWEEN :from AND :to
    GROUP BY v.id, v.ref, v.plate_number, v.make, v.model, v.year
    ORDER BY km_per_litre ASC
  
  Output columns: Rank | Ref | Plate | Make | Model | Year | Total KM | Total Litres | KM per Litre
  Flag rows below 5 KM/L in red
  
  UI: DataTable with conditional coloring. Export: Excel, PDF, Print
  
  Tables: vehicles, trips, fuel_expenses

39. Highest Expense Vehicle
Vehicle with highest operating cost.

  Files: reports/highest_expense_vehicle/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    SELECT
      v.ref, v.plate_number, v.make, v.model,
      COALESCE(fe.fuel_total, 0) AS fuel_cost,
      COALESCE(ve.veh_total, 0) AS maintenance_cost,
      COALESCE(fe.fuel_total, 0) + COALESCE(ve.veh_total, 0) AS total_operating_cost,
      ROW_NUMBER() OVER (ORDER BY COALESCE(fe.fuel_total,0) + COALESCE(ve.veh_total,0) DESC) AS rank
    FROM vehicles v
    LEFT JOIN (SELECT vehicle_id, SUM(total) AS fuel_total FROM fuel_expenses WHERE date BETWEEN :from AND :to GROUP BY vehicle_id) fe ON fe.vehicle_id = v.id
    LEFT JOIN (SELECT vehicle_id, SUM(amount) AS veh_total FROM vehicle_expenses WHERE date BETWEEN :from AND :to GROUP BY vehicle_id) ve ON ve.vehicle_id = v.id
    ORDER BY total_operating_cost DESC
  
  Output columns: Rank | Ref | Plate | Make | Model | Fuel Cost (LKR) | Maintenance (LKR) | Total Operating Cost (LKR)
  Rank 1 highlighted in red (worst)
  
  UI: Stacked bar chart (fuel vs maintenance per vehicle, ApexCharts) + DataTable. Export: Excel, PDF, Print
  
  Tables: vehicles, fuel_expenses, vehicle_expenses

40. Profit Per KM Report
Profit divided by mileage for efficiency analysis.

  Files: reports/profit_per_km/ → .php, .js, _data.php
  
  Filters:
    - Date From / Date To (default: current month)
  
  API ?action=report&from=&to=:
    SELECT
      v.ref, v.plate_number, v.make, v.model,
      COALESCE(t.revenue, 0) AS revenue,
      COALESCE(fe.fuel_cost, 0) + COALESCE(ve.veh_cost, 0) AS total_cost,
      COALESCE(t.revenue, 0) - COALESCE(fe.fuel_cost, 0) - COALESCE(ve.veh_cost, 0) AS profit,
      COALESCE(t.total_km, 0) AS total_km,
      CASE WHEN COALESCE(t.total_km, 0) > 0
           THEN ROUND((COALESCE(t.revenue,0) - COALESCE(fe.fuel_cost,0) - COALESCE(ve.veh_cost,0)) / t.total_km, 2)
           ELSE NULL END AS profit_per_km
    FROM vehicles v
    LEFT JOIN (SELECT vehicle_id, SUM(amount) AS revenue, SUM(mileage) AS total_km FROM trips WHERE date BETWEEN :from AND :to GROUP BY vehicle_id) t ON t.vehicle_id = v.id
    LEFT JOIN (SELECT vehicle_id, SUM(total) AS fuel_cost FROM fuel_expenses WHERE date BETWEEN :from AND :to GROUP BY vehicle_id) fe ON fe.vehicle_id = v.id
    LEFT JOIN (SELECT vehicle_id, SUM(amount) AS veh_cost FROM vehicle_expenses WHERE date BETWEEN :from AND :to GROUP BY vehicle_id) ve ON ve.vehicle_id = v.id
    ORDER BY profit_per_km DESC NULLS LAST
  
  Output columns: Rank | Ref | Plate | Make | Model | Revenue (LKR) | Cost (LKR) | Profit (LKR) | Total KM | Profit per KM (LKR/km)
  
  UI: DataTable sorted by profit_per_km descending. Export: Excel, PDF, Print
  
  Tables: vehicles, trips, fuel_expenses, vehicle_expenses
