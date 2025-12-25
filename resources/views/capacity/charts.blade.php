{{-- @extends('layouts.app') --}}
@extends('layouts.header-style')
{{-- @section('header-style')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
@endsection --}}

@section('styles')
<link href="{{ asset('css/charts-styles.css') }}?v={{ config('constants.cache_ver') }}" rel="stylesheet">
@endsection

@section('content')
<div class="dashboard-container">
    <!-- Global Charts Loader -->
    <div id="chartsLoaderOverlay" aria-hidden="true" style="display:none;">
        <div class="loader-backdrop"></div>

        <div id="chartsLoader" class="loader-spinner">
            <div class="loader" role="status" aria-hidden="true"></div>
            <p class="visually-hidden" aria-live="polite">
                Loading, please wait…
            </p>
        </div>
    </div>

    <!-- Header with Month Selector -->
    {{-- <div class="dashboard-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <!-- Left: Back + Title -->
        <div class="d-flex align-items-center gap-3">
            <!-- Back Button -->
            <a href="{{ route('dashboard') }}" class="back-btn-style light" title="Go back to Dashboard">
                <i class="fas fa-arrow-left"></i>
            </a>


            <!-- Title -->
            <h1 class="main-title mb-0">Capacity Dashboard</h1>
        </div>

        <!-- Right: Month + Generate + Range Picker -->
        <div class="month-selector d-flex align-items-center gap-2 flex-wrap">
            <!-- Quick Range -->
            <select id="quickRangeSelect" class="form-select quick-range-select">
                <option value="">Quick Range</option>
                <option value="1w">Last 1 Week</option>
                <option value="2w">Last 2 Weeks</option>
                <option value="3w">Last 3 Weeks</option>
                <option value="1m">Monthly</option>
                <option value="1q">Quarterly</option>
                <option value="1y">Yearly</option>
            </select>

            <input type="month" id="monthSelectForCharts" class="form-control month-input-style">
            <button class="btn-generate" onclick="generateReport()">Generate Report</button>
            <input type="text" id="dateRangePicker" class="form-control" placeholder="Select date range" readonly style="max-width: 250px;">
        </div>
    </div> --}}
    <div class="dashboard-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="back-btn-style light">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="main-title">Capacity Dashboard</h1>
        </div>

        <div class="header-right">
            <div class="filter-group">
                <select id="quickRangeSelect" class="filter-control">
                    <option value="">Quick Range</option>
                    <option value="tw">This Week</option>
                    <option value="1w">Last 1 Week</option>
                    <option value="2w">Last 2 Weeks</option>
                    <option value="3w">Last 3 Weeks</option>
                    <option value="1m">Monthly</option>
                    <option value="1q">Quarterly</option>
                    <option value="1y">Yearly</option>
                </select>

                <input type="month"
                    id="monthSelectForCharts"
                    class="filter-control">

                <input type="text"
                    id="dateRangePicker"
                    class="filter-control range-input"
                    placeholder="Select date range"
                    readonly>
            </div>

            <button class="btn-generate" id="generateBtn" onclick="generateReport()">
                Generate Report
            </button>
        </div>
    </div>


    <!-- Charts Section -->
    <div id="chartsSection">
        <!-- Top 3 Charts -->
        <div class="row">
            <div class="col-lg-6 col-md-12">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Capacity</h3>
                        <p class="chart-subtitle">Total Available vs Allocated</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="capacityChart"></canvas>
                    </div>
                    <div class="stats-summary">
                        <div class="stats-item">
                            <span class="stats-value" id="totalHours">770</span>
                            <span class="stats-label">Total</span>
                        </div>

                        <div class="stats-item">
                            <span class="stats-value" id="availableHours">770</span>
                            <span class="stats-label">Available</span>
                        </div>
                        <div class="stats-item">
                            <span class="stats-value" id="allocatedHours">1396</span>
                            <span class="stats-label">Allocated</span>
                        </div>
                        <div class="stats-item">
                            <span class="stats-value" id="utilizationPercent">181.3%</span>
                            <span class="stats-label">Utilization</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Dept-wise Available Hours</h3>
                        <p class="chart-subtitle">Department-wise Available Hours</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Under-utilized Resources</h3>
                        {{-- <p class="chart-subtitle">Resources with &lt; 160 Allocated Hours/Month</p> --}}
                        <p class="chart-subtitle" id="underUtilizedSubtitle">
                            Resources with &lt; 160 Allocated Hours/Month
                        </p>
                    </div>
                    <div class="chart-container">
                        <canvas id="underUtilizedChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Width Chart -->
        <div class="row g-3">
            <div class="col-12">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Allocation & Utilization</h3>
                        <p class="chart-subtitle">Available vs Allocated by Resource</p>
                    </div>
                    <div class="chart-container full-width-chart">
                        <canvas id="allocationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resource List Table -->
        <div class="table-section">
            <h3 class="table-title">Resource List</h3>

            <!-- Search Box -->
            <div class="table-search mb-2">
                <input type="text" id="resourceSearchInput" placeholder="Search resources..." class="form-control">
            </div>

            <div class="table-responsive scrollable-table" style="max-height: 400px; overflow-y: auto;">
                <table class="resource-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Total Hours</th>
                            <th>Holiday Hrs</th>
                            <th>Leave Hrs</th>
                            <th>Allocated Hrs</th>
                            <th>Net Avail Hrs</th>
                            <th>Utilization</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="resourceTableBody">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Project-wise Allocated Resources List Table -->
        <div class="table-section">
            <h3 class="table-title">Project-wise Allocated Resources</h3>

            <!-- Search Box -->
            <div class="table-search mb-2">
                <input type="text" id="projectWiseSearchInput" placeholder="Search projects..." class="form-control">
            </div>

            <div class="table-responsive scrollable-table" style="max-height: 400px; overflow-y: auto;">
                <table class="resource-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Project</th>
                            <th>Project Manager</th>
                            <th>Resource Count</th>
                            <th>Allocated Hours/Month</th>
                    </thead>
                    <tbody id="projectWiseAllocatedResourceTableBody">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Project Summary (Project, Resource Count, Total Hours) List Table -->
        <div class="table-section">
            <h3 class="table-title">Project Summary (Project, Resource Count, Total Hours)</h3>

            <div class="table-search mb-2">
                <input type="text" id="projectSummarySearchInput" placeholder="Search project summary..." class="form-control">
            </div>

            <div class="table-responsive scrollable-table" style="max-height: 400px; overflow-y: auto;">
                <table class="resource-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Resource Count</th>
                            <th>Total Allocated Hours/Month</th>
                    </thead>
                    <tbody id="projectSummaryTable">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leave Report List Table -->
        <div class="table-section">
            <h3 class="table-title">Leave Report</h3>

            <div class="table-search mb-2">
                <input type="text" id="leaveReportSearchInput" placeholder="Search leave records..." class="form-control">
            </div>

            <div class="table-responsive scrollable-table" style="max-height: 400px; overflow-y: auto;">
                <table class="resource-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Hours Impacted</th>
                            <th>Remarks</th>
                    </thead>
                    <tbody id="leaveReportTable">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Holiday Report List Table -->
        <div class="table-section">
            <h3 class="table-title">Holiday Report</h3>

            <div class="table-search mb-2">
                <input type="text" id="holidayReportSearchInput" placeholder="Search holiday records..." class="form-control">
            </div>

            <div class="table-responsive scrollable-table" style="max-height: 400px; overflow-y: auto;">
                <table class="resource-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                    </thead>
                    <tbody id="holidayReportTable">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dashboard Footer Info -->
        <div class="cd-footer-info">
            <div class="cd-footer-content">
                <span class="cd-footer-text">
                    Developed by <strong>Himanshu Makwana</strong>
                </span>

                <span class="cd-footer-divider">•</span>

                <span class="cd-footer-text">
                    Last updated: {{ now()->format('d M Y') }}
                </span>

                <span class="cd-footer-divider">•</span>

                <span class="cd-footer-version">
                    v1.0
                </span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')

{{-- Chart JS --}}
<script type="text/javascript" src="{{ asset('js/chart.js') }}?v={{ config('constants.cache_ver') }}"></script>

<script>
let charts = {};

let isLoading = false;

// Initialize month input to current month
function initMonth() {
    let today = new Date();
    document.getElementById('monthSelectForCharts').value = today.toISOString().slice(0, 7);
}

// Utility: safe date formatter -> "10 Sep 2025"
function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        // Some DB strings might include time, so new Date() handles both
        let d = new Date(dateStr);
        if (isNaN(d)) return dateStr;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateStr;
    }
}

function formatISO(date) {
    return date.toISOString().split('T')[0];
}

document.getElementById('quickRangeSelect').addEventListener('change', function () {
    let value = this.value;
    if (!value) return;

    let today = new Date();
    let start = new Date(today);

    switch (value) {
        case 'tw': // This Week
            let day = today.getDay(); // 0 (Sun) - 6 (Sat)
            let diff = today.getDate() - day + (day === 0 ? -6 : 1); // Monday start
            // start = new Date(today.setDate(diff));
            start = new Date(today.getFullYear(), today.getMonth(), diff);
            start.setDate(start.getDate() + 1);
            break;
        case '1w':
            start.setDate(today.getDate() - 7);
            break;
        case '2w':
            start.setDate(today.getDate() - 14);
            break;
        case '3w':
            start.setDate(today.getDate() - 21);
            break;
        case '1m':
            start.setMonth(today.getMonth() - 1);
            break;
        case '1q':
            start.setMonth(today.getMonth() - 3);
            break;
        case '1y':
            start.setFullYear(today.getFullYear() - 1);
            break;
    }

    // Fill date range picker
    document.getElementById('dateRangePicker').value =
        `${formatISO(start)} to ${formatISO(today)}`;

    // Clear month
    document.getElementById('monthSelectForCharts').value = '';

    setTimeout(generateReport, 100);
});


// Initialize flatpickr as range picker
// flatpickr("#dateRangePicker", {
//     mode: "range",
//     dateFormat: "Y-m-d",
//     onChange: function(selectedDates, dateStr, instance) {
//         // Automatically clear month if date range selected
//         if (selectedDates.length === 2) {
//             document.getElementById('monthSelectForCharts').value = '';
//         }
//     }
// });
flatpickr("#dateRangePicker", {
    mode: "range",
    dateFormat: "Y-m-d",
    minDate: "2000-01-01",   // Earliest selectable date
    maxDate: "2050-12-31",   // Latest selectable date
    allowInput: false,       // Prevent typing manually
    onReady: function(selectedDates, dateStr, instance) {
        // Disable year input typing
        instance.yearElements.forEach(el => el.setAttribute("readonly", true));
    },
    onChange: function(selectedDates, dateStr, instance) {
        // Automatically clear month input if a date range is selected
        if (selectedDates.length === 2) {
            document.getElementById('monthSelectForCharts').value = '';
            document.getElementById('quickRangeSelect').value = '';
            setTimeout(generateReport, 100);
        }
    }
});


// If user selects month, clear date range
document.getElementById('monthSelectForCharts').addEventListener('change', function() {
    document.getElementById('quickRangeSelect').value = '';
    document.getElementById('dateRangePicker').value = '';
    setTimeout(generateReport, 100);
});

// async function generateReport() {
//     let month = document.getElementById('monthSelectForCharts').value;
//     if (!month) return alert('Please select a month');

//     try {
//         let res = await fetch(`/resource-capacity?month=${month}`);
//         let data = await res.json();

//         renderDashboard(data);
//     } catch (err) {
//         console.error("Error fetching data:", err);
//         alert("Failed to load capacity data");
//     }
// }

// Single loader pair for all AJAX/fetch usage in this file.
function showLoader() {
    $('#chartsLoaderOverlay')
        .stop(true, true)
        .fadeIn(150)
        .attr('aria-hidden', 'false');
}

function hideLoader() {
    $('#chartsLoaderOverlay')
        .stop(true, true)
        .fadeOut(150)
        .attr('aria-hidden', 'true');
}

async function generateReport() {

    if (isLoading) return;

    let month = document.getElementById('monthSelectForCharts').value;
    let dateRange = document.getElementById('dateRangePicker').value;

    if (!month && !dateRange) return alert('Please select a month or date range');

    let url = '/resource-capacity';
    if (month) {
        url += `?month=${month}`;
    } else if (dateRange) {
        // dateRange example: "2025-10-01 to 2025-10-15"
        let dates = dateRange.split(' to ');
        if (dates.length !== 2) return alert('Please select a valid date range');
        url += `?start_date=${dates[0]}&end_date=${dates[1]}`;
    }

    const btn = document.getElementById('generateBtn');
    btn.disabled = true;
    btn.innerText = 'Loading...';
    isLoading = true;

    showLoader();

    try {
        let res = await fetch(url);
        let data = await res.json();
        renderDashboard(data);
    } catch (err) {
        console.error("Error fetching data:", err);
        alert("Failed to load capacity data");
     }finally {
        btn.disabled = false;
        btn.innerText = 'Generate Report';
        isLoading = false;
        hideLoader();
    }
}

function renderDashboard(data) {
    let { available, allocated, utilization_percent, departments, resources, under_utilized, project_wise, leaves, total_hours, holidays, total_hours_sum } = data;

    // // Update summary stats
    // document.getElementById('totalHours').textContent = total_hours_sum;
    // document.getElementById('availableHours').textContent = available;
    // document.getElementById('allocatedHours').textContent = allocated;
    // document.getElementById('utilizationPercent').textContent = utilization_percent;

    // Update summary stats
    document.getElementById('totalHours').textContent = total_hours_sum;
    document.getElementById('availableHours').textContent = available;

    // Calculate overage (if any)
    let overAllocated = 0;
    let allocatedDisplay = '';
    let overAllocatedHours = total_hours_sum - available;

    // Number(overAllocatedHours.toFixed(1));
    if (allocated > overAllocatedHours) {
        overAllocated = allocated - overAllocatedHours;
        allocatedDisplay = `${Number(overAllocatedHours.toFixed(1))} + ${Number(overAllocated.toFixed(1))} (Over)`;
    } else {
        allocatedDisplay = allocated;
    }

    document.getElementById('allocatedHours').textContent = allocatedDisplay;
    document.getElementById('utilizationPercent').textContent = utilization_percent;

    // Update dynamic subtitle for under-utilized chart
    document.getElementById('underUtilizedSubtitle').innerHTML =
        `Resources with &lt; ${total_hours} Allocated Hours/Month`;


    // Destroy old charts if they exist
    Object.values(charts).forEach(c => c?.destroy());

    // Chart 1: Capacity
    charts.capacityChart = new Chart(document.getElementById('capacityChart'), {
        type: 'bar',
        data: {
            labels: ['Available Hours', 'Allocated Hours'],
            datasets: [{
                data: [available, allocated],
                backgroundColor: ['#74b9ff', '#fd79a8'],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        generateLabels: () => [
                            { text: 'Available Hours', fillStyle: '#74b9ff' },
                            { text: 'Allocated Hours', fillStyle: '#fd79a8' }
                        ]
                    }
                }
            },
            scales: {
                y: { beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });

    // Chart 2: Department-wise
    charts.deptChart = new Chart(document.getElementById('deptChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(departments),
            datasets: [{
                label: 'Available Hours',
                data: Object.values(departments),
                backgroundColor: '#55efc4',
                borderRadius: 8
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Chart 3: Under-utilized
    charts.underUtilizedChart = new Chart(document.getElementById('underUtilizedChart'), {
        type: 'bar',
        data: {
            labels: under_utilized.map(r => r.name),
            datasets: [{
                label: `Allocated Hours (< ${total_hours})`,
                data: under_utilized.map(r => r.allocated_hours),
                backgroundColor: '#ffeaa7',
                borderRadius: 8
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Chart 4: Allocation & Utilization
    charts.allocationChart = new Chart(document.getElementById('allocationChart'), {
        type: 'bar',
        data: {
            labels: resources.map(r => r.name),
            datasets: [
                { label: 'Available', data: resources.map(r => r.available_hours), backgroundColor: '#74b9ff', borderRadius: 8 },
                { label: 'Allocated', data: resources.map(r => r.allocated_hours), backgroundColor: '#fd79a8', borderRadius: 8 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { x: { ticks: { autoSkip: false } } }
        }
    });

    // Render table
    renderTable(resources);

    renderProjectWiseTable(project_wise);

    projectSummaryTable(project_wise);

    leaveReportTable(leaves);

    holidayReportTable(holidays);
}

// Table 1: Resource List -----------------------------------------------------------------
function renderTable(resources) {
    // let renderTableBody = document.getElementById('resourceTableBody');
    // if (!renderTableBody) return;

    // if (!resources.length) {
    //     renderTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">No resource found for the selected month.</td></tr>`;
    //     return;
    // }

    // renderTableBody.innerHTML = resources.map(r => {
    //     // let util = ((r.allocated_hours / r.available_hours) * 100).toFixed(1);
    //     // Prevent division by zero
    //     let util = r.available_hours > 0 
    //         ? ((r.allocated_hours / r.available_hours) * 100).toFixed(1) 
    //         : ((r.allocated_hours / 100) * 100).toFixed(1); // or just show allocated_hours as percent

    //     // If you want to display the actual % even when available_hours is 0, you can just use:
    //     // let util = r.available_hours > 0 ? ((r.allocated_hours / r.available_hours) * 100).toFixed(1) : (r.allocated_hours).toFixed(1);
        
    //     let statusClass = 'status-ok', statusText = 'OK';
    //     if (util > 100) { statusClass = 'status-over'; statusText = 'Over'; }
    //     else if (r.allocated_hours < 40) { statusClass = 'status-under'; statusText = 'Under'; }

    //     let leaveHours = Math.round(r.leave_hours);

    //     return `
    //         <tr>
    //             <td>${r.name}</td>
    //             <td>${r.department}</td>
    //             <td>${r.total_hours}</td>
    //             <td>${leaveHours}</td>
    //             <td>${r.available_hours}</td>
    //             <td>${r.allocated_hours}</td>
    //             <td>${util}%</td>
    //             <td><span class="status-badge ${statusClass}">${statusText}</span></td>
    //         </tr>
    //     `;
    // }).join('');

    let renderTableBody = document.getElementById('resourceTableBody');
    if (!renderTableBody) return;

    if (!resources.length) {
        renderTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">No resource found for the selected month.</td></tr>`;
        return;
    }

    renderTableBody.innerHTML = resources.map(r => {
        let statusClass = 'status-ok', statusText = 'OK';
        if (r.utilization > 100) { statusClass = 'status-over'; statusText = 'Over'; }
        else if (r.utilization < 50) { statusClass = 'status-under'; statusText = 'Under'; } // optional threshold

        return `
            <tr>
                <td>${r.name}</td>
                <td>${r.department}</td>
                <td>${r.total_hours}</td>
                <td>${r.holiday_hours}</td>
                <td>${Math.round(r.leave_hours)}</td>
                <td>${r.allocated_hours}</td>
                <td>${r.available_hours}</td>
                <td>${Math.round(r.utilization)}%</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
            </tr>
        `;
    }).join('');

    // Initialize search functionality
    initResourceSearch();
}

let resourceSearchInitialized = false;

// Filter Resource List table on keyup
function initResourceSearch() {
    if (resourceSearchInitialized) return;
    resourceSearchInitialized = true;

    let searchInput = document.getElementById('resourceSearchInput');
    let tableBody = document.getElementById('resourceTableBody');
    if (!searchInput || !tableBody) return;

    // Remove old listener if exists to prevent duplicate triggers
    searchInput.onkeyup = function() {
        let searchValue = this.value.toLowerCase();
        let rows = tableBody.querySelectorAll('tr:not(.no-results)');
        
        rows.forEach(row => {
            let rowText = Array.from(row.cells)
                .map(cell => cell.textContent.toLowerCase())
                .join(' ');

            row.style.display = rowText.includes(searchValue) ? '' : 'none';
        });

        showNoResults(tableBody);
    };
}

// Show "No matching records" row if all rows are hidden
function showNoResults(tableBody) {
    let rows = [...tableBody.querySelectorAll('tr:not(.no-results)')];
    let allHidden = rows.every(r => r.style.display === 'none');

    let existingNoResults = tableBody.querySelector('.no-results');
    if (allHidden) {
        if (!existingNoResults) {
            let tr = document.createElement('tr');
            tr.className = 'no-results';
            tr.innerHTML = `<td colspan="8" class="text-center text-muted">No matching records found.</td>`;
            tableBody.appendChild(tr);
        }
    } else if (existingNoResults) {
        existingNoResults.remove();
    }
}

// Table 2: Project-wise Allocated Resources  ----------------------------------------------------
function renderProjectWiseTable(projectWiseData) {
    let renderProjectWiseTableBody = document.getElementById('projectWiseAllocatedResourceTableBody');
    if (!renderProjectWiseTableBody) return;

    if (!projectWiseData.length) {
        renderProjectWiseTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No active projects found for the selected month.</td></tr>`;
        return;
    }

    renderProjectWiseTableBody.innerHTML = projectWiseData.map(p => `
        <tr class="project-row" data-project-id="${p.project_id}">
            <td class="text-center">
                ${p.resources?.length ? `
                    <span class="toggle-icon"
                          onclick="toggleProjectResources(${p.project_id}, this)">
                        +
                    </span>` : ''}
            </td>
            <td>${p.project_name}</td>
            <td>${p.project_manager}</td>
            <td>${p.resource_count}</td>
            <td>${p.allocated_hours}</td>
        </tr>

        <!-- Child Row -->
        <tr class="resource-details-row d-none" id="resources-${p.project_id}">
            <td colspan="5">
                ${renderProjectResources(p.resources)}
            </td>
        </tr>
    `).join('');

    // Initialize search
    initProjectWiseSearch();
}

function renderProjectResources(resources = []) {
    if (!resources.length) {
        return `<div class="text-muted text-center">No resources allocated.</div>`;
    }

    return `
        <table class="table table-sm nested-table">
            <thead>
                <tr>
                    <th>Resource Name</th>
                    <th>Role</th>
                    <th>Allocated Hours</th>
                </tr>
            </thead>
            <tbody>
                ${resources.map(r => `
                    <tr>
                        <td>${r.name}</td>
                        <td>${r.role}</td>
                        <td>${r.hours}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

function toggleProjectResources(projectId, iconEl) {
    const row = document.getElementById(`resources-${projectId}`);
    if (!row) return;

    const isOpen = !row.classList.contains('d-none');

    // Close all others (optional)
    document.querySelectorAll('.resource-details-row').forEach(r => r.classList.add('d-none'));
    document.querySelectorAll('.toggle-icon').forEach(i => i.textContent = '+');

    if (!isOpen) {
        row.classList.remove('d-none');
        iconEl.textContent = '−';
    }
}


// Filter Project-wise table on keyup
function initProjectWiseSearch() {
    let searchInput = document.getElementById('projectWiseSearchInput');
    let tableBody = document.getElementById('projectWiseAllocatedResourceTableBody');
    if (!searchInput || !tableBody) return;

    // Remove old listener if exists
    searchInput.onkeyup = function() {
        let searchValue = this.value.toLowerCase();
        let rows = tableBody.querySelectorAll('tr:not(.no-results)');

        rows.forEach(row => {
            let rowText = Array.from(row.cells)
                .map(cell => cell.textContent.toLowerCase())
                .join(' ');

            row.style.display = rowText.includes(searchValue) ? '' : 'none';
        });

        showNoResultsProjectWise(tableBody);
    };
}

// Show "No matching records" for Project-wise table
function showNoResultsProjectWise(tableBody) {
    let rows = [...tableBody.querySelectorAll('tr:not(.no-results)')];
    let allHidden = rows.every(r => r.style.display === 'none');

    let existingNoResults = tableBody.querySelector('.no-results');
    if (allHidden) {
        if (!existingNoResults) {
            let tr = document.createElement('tr');
            tr.className = 'no-results';
            tr.innerHTML = `<td colspan="4" class="text-center text-muted">No matching records found.</td>`;
            tableBody.appendChild(tr);
        }
    } else if (existingNoResults) {
        existingNoResults.remove();
    }
}

// Table 3: Project Summary (Project, Resource Count, Total Hours) ---------------------------------------
function projectSummaryTable(projectWiseData) {
    let projectSummaryTableBody = document.getElementById('projectSummaryTable');
    if (!projectSummaryTableBody) return;

    if (!projectWiseData.length) {
        projectSummaryTableBody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">No active projects found for the selected month.</td></tr>`;
        return;
    }

    projectSummaryTableBody.innerHTML = projectWiseData.map(p => `
        <tr>
            <td>${p.project_name}</td>
            <td>${p.resource_count}</td>
            <td>${p.allocated_hours}</td>
        </tr>
    `).join('');

    initProjectSummarySearch();
}

function initProjectSummarySearch() {
    let searchInput = document.getElementById('projectSummarySearchInput');
    let tableBody = document.getElementById('projectSummaryTable');
    if (!searchInput || !tableBody) return;

    searchInput.onkeyup = function() {
        let searchValue = this.value.toLowerCase();
        let rows = tableBody.querySelectorAll('tr:not(.no-results)');
        
        rows.forEach(row => {
            let rowText = Array.from(row.cells)
                .map(cell => cell.textContent.toLowerCase())
                .join(' ');
            row.style.display = rowText.includes(searchValue) ? '' : 'none';
        });

        showNoResultsBoth(tableBody, 3); // 3 columns in Project Summary
    };
}

// Table 4: Leave Report  -------------------------------------------------
function leaveReportTable(leaves) {
    let leaveReportTableBody = document.getElementById('leaveReportTable');
    if (!leaveReportTableBody) return;

    if (!leaves.length) {
        leaveReportTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">No leave records found for this month.</td></tr>`;
        return;
    }

    leaveReportTableBody.innerHTML = leaves.map(p => `
        <tr>
            <td>${p.employee_name}</td>
            <td>${p.type}</td>
            <td>${formatDate(p.start_date)}</td>
            <td>${formatDate(p.end_date)}</td>
            <td>${Math.round(p.hours_impacted) ?? p.number_of_days ?? '-'}</td>
            <td>${p.remark ?? '-'}</td>
        </tr>
    `).join('');

    initLeaveReportSearch();
}

function initLeaveReportSearch() {
    let searchInput = document.getElementById('leaveReportSearchInput');
    let tableBody = document.getElementById('leaveReportTable');
    if (!searchInput || !tableBody) return;

    searchInput.onkeyup = function() {
        let searchValue = this.value.toLowerCase();
        let rows = tableBody.querySelectorAll('tr:not(.no-results)');
        
        rows.forEach(row => {
            let rowText = Array.from(row.cells)
                .map(cell => cell.textContent.toLowerCase())
                .join(' ');
            row.style.display = rowText.includes(searchValue) ? '' : 'none';
        });

        showNoResultsBoth(tableBody, 6); // 6 columns in Leave Report
    };
}

// Table 5: Holiday Report  -------------------------------------------------
function holidayReportTable(holidays) {
    let holidayReportTableBody = document.getElementById('holidayReportTable');
    if (!holidayReportTableBody) return;

    if (!holidays.length) {
        holidayReportTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">No holiday records found for this month.</td></tr>`;
        return;
    }

    holidayReportTableBody.innerHTML = holidays.map(p => `
        <tr>
            <td>${formatDate(p.date)}</td>
            <td>${p.description ?? '-'}</td>
        </tr>
    `).join('');

    initHolidayReportSearch();
}

function initHolidayReportSearch() {
    let searchInput = document.getElementById('holidayReportSearchInput');
    let tableBody = document.getElementById('holidayReportTable');
    if (!searchInput || !tableBody) return;

    searchInput.onkeyup = function() {
        let searchValue = this.value.toLowerCase();
        let rows = tableBody.querySelectorAll('tr:not(.no-results)');
        
        rows.forEach(row => {
            let rowText = Array.from(row.cells)
                .map(cell => cell.textContent.toLowerCase())
                .join(' ');
            row.style.display = rowText.includes(searchValue) ? '' : 'none';
        });

        showNoResultsBoth(tableBody, 6); // 6 columns in Leave Report
    };
}

// --- Generic no results handler ---
function showNoResultsBoth(tableBody, colspan) {
    let rows = [...tableBody.querySelectorAll('tr:not(.no-results)')];
    let allHidden = rows.every(r => r.style.display === 'none');

    let existingNoResults = tableBody.querySelector('.no-results');
    if (allHidden) {
        if (!existingNoResults) {
            let tr = document.createElement('tr');
            tr.className = 'no-results';
            tr.innerHTML = `<td colspan="${colspan}" class="text-center text-muted">No matching records found.</td>`;
            tableBody.appendChild(tr);
        }
    } else if (existingNoResults) {
        existingNoResults.remove();
    }
}

// Initialize
initMonth();
generateReport();
</script>
@endsection
