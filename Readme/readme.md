Here’s my understanding of your **Capacity Planning • Resource → Projects → Daily Hours** project in Laravel:

* **Goal:**
  Manage employee (resource) availability, allocations, and utilization across projects.

* **Key Features from Screenshots:**

  1. **Daily Hours Allocation Screen**

     * Allocate hours per day for each employee on a project.
     * Shows allocated vs available hours.
     * Has controls for apply, clear, delete.
     * Aggregates per-resource hours (e.g., 44 hrs allocated out of 160 hrs for Alice Johnson).

  2. **Dashboard & Reports**

     * **Capacity vs Allocated (Bar Chart):**
       Total available vs allocated hours with utilization %.
     * **Dept-wise Available Hours:**
       Aggregated by department (MERN, Mobile, QA, Python, PHP).
     * **Under-utilized Resources (< 40 hrs/wk):**
       Highlights employees under threshold.

  3. **Allocation & Utilization Chart**

     * Shows resource-level available vs allocated hours (side-by-side bars).

  4. **Resource List Table**

     * Columns: Employee, Department, Total Hours/Wk, Leave Hrs, Net Available, Allocated Hrs, Utilization %, Status.
     * Status can be **OK, Over, Under** depending on utilization.

* **Excel Upload (IT\_Capacity\_Planning\_Report\_with\_Data.xlsx):**
  Likely source of bulk data import/export for the dashboard.

So essentially, this system helps **track, allocate, and analyze resource capacity** across projects, ensuring employees are neither over-allocated nor under-utilized.

=======================================================


=======================================================

