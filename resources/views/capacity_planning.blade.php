{{-- @extends('layouts.app') --}}
@extends('layouts.header-style')

@section('title', 'Capacity Planning – Resource Allocation')

@section('styles')
<link href="{{ asset('css/main-styles.css') }}?v={{ config('constants.cache_ver') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="main-container">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center">

                    <!-- Back Button -->
                    <a href="{{ route('dashboard') }}" class="back-btn-style light" title="Go back to Dashboard">
                        <i class="fas fa-arrow-left"></i>
                    </a>

                    
                    <div>
                        <h1 class="page-title">Capacity Planning</h1>
                        <div class="breadcrumb-nav">
                            Resource <span class="separator">→</span> Projects <span class="separator">→</span> Daily Hours
                        </div>
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#manageResourceModal">
                        {{-- <i class="fas fa-people-group me-2"></i>Manage Resource --}}
                        <i class="fas fa-people-group me-2"></i>Resource
                    </button>
                    <button id="addEditProjectBtn" class="btn btn-primary me-2">
                        {{-- <i class="fas fa-briefcase me-2"></i>Add/Edit Project --}}
                        <i class="fas fa-briefcase me-2"></i>Project
                    </button>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#manageLeaveModal">
                        <i class="fas fa-user-clock me-2"></i>Leave
                    </button>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#holidayModal">
                        {{-- <i class="fas fa-calendar-day me-2"></i>Manage Holiday --}}
                        <i class="fas fa-calendar-day me-2"></i>Holiday
                    </button>
                    <button id="saveAllBtn" class="btn btn-primary" disabled>
                        {{-- <i class="fas fa-save me-2"></i>Save All Allocations --}}
                        <i class="fas fa-save me-2"></i>Save All Allocations
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <div class="row g-3">
                <div class="col-lg-5 col-md-8">
                    <label for="resourceSelect" class="form-label fw-semibold">
                        <i class="fas fa-users me-2"></i>Select Resources
                    </label>
                    <select id="resourceSelect" class="form-select" multiple="multiple">
                        <option value="alice">Alice Johnson</option>
                        <option value="bob">Bob Kumar</option>
                        <option value="carol">Carol Singh</option>
                        <option value="david">David Chen</option>
                        <option value="emma">Emma Williams</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="monthSelect" class="form-label fw-semibold">
                        <i class="fas fa-calendar me-2"></i>Month
                    </label>
                    <input type="month" id="monthSelect" class="form-control">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="projectFilter" class="form-label fw-semibold">
                        <i class="fas fa-store me-2"></i>Project
                    </label>
                    <select data-placeholder="Select project" data-allow-clear="true"
                        class="form-select me-2 select-search"
                        name="filter_project_id" id="projectFilter">
                        <option></option>
                        @foreach (App\Models\Project::getProjectList() as $filter_project)
                            <option value="{{ $filter_project->id }}"
                                {{ old('filter_project_id') == $filter_project->id ? 'selected' : '' }}>
                                {{ $filter_project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-clock me-2"></i>Monthly Capacity
                    </label>
                    <div class="form-control-plaintext text-info form-control" id="monthlyCapacityHours">160 hours</div>
                </div>
            </div>
        </div>

        <!-- Panels Container -->
        <div id="panelsContainer"></div>
    </div>

    <!-- Pop up Modal for Add/Edit Project Modal -->
    @include('modals.manage-project-modal')
    <!-- Pop up Modal for Add/Edit Project Modal -->

    <!-- Pop up Modal for Holiday Modal -->
    @include('modals.manage-holiday-modal')
    <!-- Pop up Modal for Holiday Modal -->
    
    <!-- Pop up Modal for adding resource items -->
    @include('modals.manage-resource-modal')
    <!-- Pop up Modal for adding resource items -->

    <!-- Pop up Modal for Leave Modal -->
    @include('modals.manage-leave-modal')
    <!-- Pop up Modal for Leave Modal -->

@endsection

@section('scripts')
<script>

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(function () {

    const HOURS_PER_DAY = {{ config('constants.daily_working_hours') }};
    const MAX_HOURS_PER_DAY = {{ config('constants.max_hours_per_day') }};

    let holidays = []; //
    let $resourceSelect = $('#resourceSelect');
    let $monthSelect = $('#monthSelect');
    let $panelsContainer = $('#panelsContainer');
    let $saveAllBtn = $('#saveAllBtn');
    let $monthlyCapacityHours = $('#monthlyCapacityHours');
    let MAX_HOURS_MONTH = getMaxHoursMonth($('#monthSelect').val() || new Date().toISOString().slice(0,7));

    let $projectFilter = $('#projectFilter');

    // Initialize Select2 for project filter
    $projectFilter.select2({
        placeholder: "Select project",
        allowClear: true,
        width: '100%'
    });

    // Trigger renderPanels when project changes
    // $projectFilter.on('change', debounce(renderPanels, 300));

    $projectFilter.on('change', debounce(function() {
        let projectId = $projectFilter.val() || '';

        if(projectId) {
            $.get("{{ route('capacity-planning.resources') }}", { project_id: projectId }, function(resources) {
                // Clear and deduplicate
                let seen = {};
                $resourceSelect.empty();
                resources.forEach(r => {
                    if(!seen[r.id]){
                        $resourceSelect.append(`<option value="${r.id}" selected>${r.name}</option>`);
                        seen[r.id] = true;
                    }
                });

                // Re-init Select2 safely
                initSelect2();

                // Render panels
                renderPanels();
            });
        } else {
            // Project cleared → load all resources
            loadResources(renderPanels);
        }
    }, 300));



    /* Inside set placeholder in search dropdown select2 */
    $('#projectFilter').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Select project');
    });

    // Store resource-specific data
    let resourceMeta = {}; // { resourceId: { leaveHours, leaveDays, workingDays } }

    $monthSelect.on('change', function() {
        // MAX_HOURS_MONTH = getMaxHoursMonth($(this).val());
        MAX_HOURS_MONTH = workingDaysInMonth($(this).val()) * HOURS_PER_DAY;
        renderPanels();
    });

    function workingDaysInMonth(yyyyMM) {
        let [year, month] = yyyyMM.split('-').map(Number);
        let totalDays = new Date(year, month, 0).getDate();
        let workingDays = 0;
        for (let day = 1; day <= totalDays; day++) {
            let dayOfWeek = new Date(year, month-1, day).getDay();
            if (dayOfWeek !== 0 && dayOfWeek !== 6) workingDays++;
        }
        return workingDays;
    }

    function getMaxHoursMonth(yyyyMM) {
        return workingDaysInMonth(yyyyMM) * HOURS_PER_DAY;
    }

    function loadResources(callback) {
        let projectId = $projectFilter.val() || '';
        $.get("{{ route('capacity-planning.resources') }}", { project_id: projectId }, function(data) {
            $resourceSelect.empty();
            data.forEach(r => $resourceSelect.append(`<option value="${r.id}">${r.name}</option>`));
            initSelect2();
            if(callback) callback();
        });
    }

    function loadAssignments(resourceId, month, callback) {
        let projectId = $projectFilter.val() || '';
        $.get(`/capacity-planning/assignments/${resourceId}/${month}`, { project_id: projectId }, function(assignmentsData) {
            $.get(`/capacity-planning/leaves/${resourceId}/${month}`, function(leavesData) {
                callback(assignmentsData, leavesData); // leavesData is array of "YYYY-MM-DD"
            });
        });
    }

    function initMonth() {
        let d = new Date();
        let m = String(d.getMonth()+1).padStart(2,'0');
        $monthSelect.val(`${d.getFullYear()}-${m}`);
    }

    function initSelect2() {
        if ($resourceSelect.hasClass("select2-hidden-accessible")) {
            $resourceSelect.select2('destroy'); // destroy previous instance
        }
        $resourceSelect.select2({
            placeholder: "Select resources",
            allowClear: true,
            closeOnSelect: false,
            width: '100%'
        });
    }


    function hasInvalidInputs($table) {
        let invalid = false;
        $table.find('.day-input').each(function() {
            let num = parseFloat($(this).val());
            console.log("MAX_HOURS_PER_DAY11", MAX_HOURS_PER_DAY);
            if (num > MAX_HOURS_PER_DAY) {
                invalid = true;
                return false;
            }
        });
        return invalid;
    }

    
    function bindEvents() {
        $resourceSelect.on('change', debounce(renderPanels, 300));
        $monthSelect.on('change', debounce(renderPanels, 300));

        $saveAllBtn.on('click', function() {
            let selectedResources = $resourceSelect.val() || [];
            if (selectedResources.length === 0) return;

            for (let resourceId of selectedResources) {
                let $table = $(`.allocation-table[data-resource="${resourceId}"]`);
                if (hasInvalidInputs($table)) {
                    showNotification(`Please enter a valid number between 0 and ${MAX_HOURS_PER_DAY} hours`, 'danger');
                    return false;
                }
            }

            let megaData = [];
            selectedResources.forEach(resourceId => {
                let $table = $(`.allocation-table[data-resource="${resourceId}"]`);
                let month = $monthSelect.val();
                let allocations = [];

                $table.find('tr').each(function() {
                    let projectId = $(this).data('project-id');
                    if (!projectId) return;

                    let daily_hours = [];
                    $(this).find('.day-input').each(function() {
                        let day = $(this).data('day');
                        let hours = parseFloat($(this).val()) || 0;
                        let dateStr = `${month}-${String(day).padStart(2,'0')}`;
                        daily_hours.push({ date: dateStr, hours });
                    });

                    allocations.push({ project_id: projectId, daily_hours });
                });

                megaData.push({ resource_id: resourceId, month: month, allocations });
            });

            if (megaData.length === 0) {
                showNotification('No allocations to save.', 'info');
                return;
            }

            $(this).addClass('loading');

            $.ajax({
                url: "{{ route('capacity-planning.allocations.save.mega-bulk') }}",
                method: "POST",
                data: { data: JSON.stringify(megaData), _token: "{{ csrf_token() }}" },
                success: function(res) {
                    $saveAllBtn.removeClass('loading');
                    if(res.success){
                        showNotification(res.message, 'success');
                        selectedResources.forEach(rid => updateResourceAllocation(rid));
                    } else {
                        showNotification(res.message || 'Error saving allocations', 'danger');
                    }
                },
                error: function() {
                    $saveAllBtn.removeClass('loading');
                    showNotification('Error saving allocations', 'danger');
                }
            });
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            let later = () => { clearTimeout(timeout); func(...args); };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function showNotification(message, type='info') {
        let alertClass = type==='success' ? 'alert-success' : (type==='danger' ? 'alert-danger':'alert-info');
        let notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                    style="top:20px; right:20px; z-index:9999; min-width:300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        $('body').append(notification);
        setTimeout(()=>notification.alert('close'), 2000);
    }

    function renderPanels() {
        let selected = $resourceSelect.val() || [];
        let month = $monthSelect.val();

        // let workingMonthDays = workingDaysInMonth(month);

        // $monthlyCapacityHours.text(MAX_HOURS_MONTH + ` hours (8 hrs/day, ${workingMonthDays} working days)`);

        let totalWorkingDays = workingDaysInMonth(month); // weekdays only
        let totalLeaves = 0;
        // let totalHolidays = 0;

        // Compute total leaves/holidays for selected resources
        // selected.forEach(resourceId => {
        //     let meta = resourceMeta[resourceId] || {};
        //     totalLeaves += meta.leaveDays || 0;
        //     totalHolidays += (holidays.filter(h => {
        //         let date = new Date(h.date);
        //         let hMonth = date.getFullYear() + '-' + String(date.getMonth()+1).padStart(2,'0');
        //         return hMonth === month;
        //     })).length;
        // });
        // Leaves → per resource
        selected.forEach(resourceId => {
            let meta = resourceMeta[resourceId] || {};
            totalLeaves += meta.leaveDays || 0;
        });

        // Holidays → ONCE per month
        let totalHolidays = holidays.filter(h =>
            h.date.substring(0, 7) === month
        ).length;

        console.log("totalWorkingDays", totalWorkingDays);
        console.log("totalLeaves", totalLeaves);
        console.log("totalHolidays", totalHolidays);

        let adjustedDays = totalWorkingDays - totalHolidays;
        let adjustedHours = adjustedDays * HOURS_PER_DAY;

        $monthlyCapacityHours.text(`${adjustedHours} hrs (${HOURS_PER_DAY} hrs/day, ${adjustedDays} working days)`);

        $panelsContainer.empty();
        $saveAllBtn.prop('disabled', selected.length===0);

        if (!selected.length) {
            $panelsContainer.html(`<div class="text-center text-muted py-5"><i class="fas fa-users fa-3x mb-3 opacity-25"></i><h5>No Resources Selected</h5><p>Please select one or more resources to view their capacity planning.</p></div>`);
            return;
        }
        if (!month) {
            $panelsContainer.html(`<div class="text-center text-muted py-5"><i class="fas fa-calendar fa-3x mb-3 opacity-25"></i><h5>No Month Selected</h5><p>Please select a month to view capacity planning.</p></div>`);
            return;
        }

        selected.forEach((resourceId, index) => {
            setTimeout(()=>createResourcePanel(resourceId, month), index*100);
        });
    }

    function createResourcePanel(resourceId, month) {
        // Remove existing panel for this resource before re-creating it
        // $panelsContainer.find(`section.panel[data-resource="${resourceId}"]`).remove();
        $panelsContainer.find('section.panel[data-resource="' + resourceId + '"]').remove();

        let selectedProject = $projectFilter.val(); // get selected project
        let resourceName = $resourceSelect.find(`option[value="${resourceId}"]`).text();
        loadAssignments(resourceId, month, function(assignmentsData, leavesData) {
            // Filter assignments if a project is selected
            assignmentsData = assignmentsData
                ? Object.values(assignmentsData)
                : [];

            if(selectedProject) {
                assignmentsData = assignmentsData.filter(p => p.project_id == selectedProject);
            }

            // Normalize structure for frontend usage
            let projects = assignmentsData.map(p => ({
                id: p.project_id,
                name: p.name,
                allocation_id: p.allocation_id,
                allocations: p.allocations || {},
                total_hours: p.total_hours || 0,
                allocated_hours: p.allocated_hours || 0,
                available_hours: p.available_hours || 0,
                status: p.status || '',
                priority: p.priority || ''
            }));

            // Continue existing logic
            let leaveDates = leavesData || [];
            let leaveDays = leavesData.length;
            let leaveHours = leaveDays * HOURS_PER_DAY;
            let workingDays = workingDaysInMonth(month);

            resourceMeta[resourceId] = { leaveDates, leaveDays, leaveHours, workingDays };

            let resourceName = $resourceSelect.find(`option[value="${resourceId}"]`).text();
            let $panel = $(`
                <section class="panel fade-in" data-resource="${resourceId}">
                    <div class="panel-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h2 class="panel-title">Plans for ${resourceName}</h2>
                                <div class="allocation-info" data-resource="${resourceId}">
                                    <span class="status-indicator status-available"></span>
                                    Allocated: 0 hrs / ${MAX_HOURS_MONTH} hrs | Available: ${MAX_HOURS_MONTH - leaveHours} hrs | Leave Hours: ${leaveHours} hrs
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm save-resource-btn" data-resource="${resourceId}">
                                <i class="fas fa-save me-1"></i>Save
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="allocation-table" data-resource="${resourceId}"></table>
                    </div>
                </section>
            `);
            $panelsContainer.append($panel);

            $panel.find('.save-resource-btn').on('click', function() {
                let resourceId = $(this).data('resource');
                let month = $monthSelect.val();
                let $table = $(`.allocation-table[data-resource="${resourceId}"]`);

                let allocations = []; // <-- Declare it here

                // Collect all day inputs
                $table.find('tr').each(function() {
                    let projectId = $(this).data('project-id');
                    if (!projectId) return;

                    let daily_hours = [];
                    $(this).find('.day-input').each(function() {
                        let day = $(this).data('day');
                        let val = parseFloat($(this).val()) || 0;
                        console.log("val +++",val);
                        let dateStr = `${month}-${String(day).padStart(2,'0')}`;
                        daily_hours.push({ date: dateStr, hours: val });
                    });

                    allocations.push({ project_id: projectId, daily_hours });
                });

                $.ajax({
                    url: '/capacity-planning/allocations/save-bulk', // your route
                    method: 'POST',
                    data: {
                        resource_id: resourceId,
                        month: month,
                        allocations: JSON.stringify(allocations),
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        if (res.success) {
                            showNotification(`Allocations saved for ${resourceName}`, 'success');
                            updateResourceAllocation(resourceId); // refresh allocation info
                        } else {
                            showNotification(res.message || 'Error saving allocations', 'danger');
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        showNotification('Error saving allocations', 'danger');
                    }
                });
            });



            buildAllocationGrid(projects, month, $panel.find('.allocation-table'), resourceId, leavesData);
            updateResourceAllocation(resourceId);
        });
    }

    function daysInMonth(yyyyMM) {
        let [y,m] = yyyyMM.split('-').map(Number);
        return new Date(y,m,0).getDate();
    }

    function isWeekend(y,m,d){ let dow = new Date(y,m-1,d).getDay(); return dow===0||dow===6; }

    function buildAllocationGrid(projects, yyyyMM, $table, resourceId, leavesData) {
        let days = daysInMonth(yyyyMM);
        let [year,month] = yyyyMM.split('-').map(Number);

        let $thead = $('<thead></thead>');
        let $headRow = $('<tr></tr>');
        $headRow.append('<th>Project / Tools</th>');
        for(let day=1; day<=days; day++){
            let isWeekendDay = isWeekend(year,month,day);
            let dayName = new Date(year,month-1,day).toLocaleDateString('en',{weekday:'short'});
            $headRow.append(`<th class="${isWeekendDay?'weekend-column':''}" title="${dayName}">${day}<div style="font-size:0.7rem;opacity:0.7">${dayName}</div></th>`);
        }
        $thead.append($headRow);

        let $tbody = $('<tbody></tbody>');
        projects.forEach(project => $tbody.append(createProjectRow(project, year, month, days, resourceId, leavesData)));
        $table.empty().append($thead,$tbody);
        updateResourceAllocation(resourceId);
    }

    function createProjectRow(project, year, month, days, resourceId, leavesData){
        let leaveInfo = leavesData?.leaves || [];

        function isFullDayLeave(dateStr, leaveInfo) {
            return leaveInfo.some(l =>
                l.number_of_days >= 1 &&
                l.dates.includes(dateStr)
            );
        }

        let $row = $('<tr></tr>').data('project-id',project.id).data('allocation-id',project.allocation_id||null);
        let $headerCell = $(`
            <td class="project-cell">
                <div class="project-header">
                    <div class="project-title">${project.name}</div>
                    <div class="project-tools">
                        <input type="number" class="form-control tool-input apply-all-input" placeholder="hrs" min="0" max="${MAX_HOURS_PER_DAY}" step="0.5">
                        <button class="btn btn-outline-light btn-sm apply-all-btn"><i class="fas fa-copy me-1"></i>Apply</button>
                        <button class="btn btn-warning btn-sm clear-row-btn"><i class="fas fa-eraser me-1"></i>Clear</button>
                        <button class="btn btn-danger btn-sm delete-row-btn"><i class="fas fa-trash me-1"></i>Delete</button>
                    </div>
                </div>
            </td>
        `);
        $row.append($headerCell);

        let dayInputs=[];
        // let leaveDates = leavesData;
        let leaveDates = leavesData?.dates || [];

        for(let day=1; day<=days; day++){
            let $cell=$(`<td></td>`);
            let dateStr=`${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            // let existingVal = project.allocations && project.allocations[dateStr]!=null ? Math.round(project.allocations[dateStr]):'';
            let existingVal = project.allocations && project.allocations[dateStr]!=null ? project.allocations[dateStr] : '';
            let $input=$(`<input type="number" class="form-control day-input" min="0" max="${MAX_HOURS_PER_DAY}" step="0.5" data-day="${day}" data-resource="${resourceId}" value="${existingVal}">`);

            // if(isWeekend(year,month,day)){
            //     $input.prop('disabled',true).addClass('day-off').attr('title','Weekend - Not available');
            // } else if(leaveDates.includes(dateStr)){
            //     $input.prop('disabled',true).addClass('leave-day').attr('title','Leave Day - Not available');
            // } else {
            //     $input.attr('title',`Hours for day ${day}`);
            // }

            if (isWeekend(year, month, day)) {
                $input.prop('disabled', true)
                    .addClass('day-off')
                    .attr('title','Weekend - Not available');

            } else if (isFullDayLeave(dateStr, leaveInfo)) {
                // FULL day leave → disable
                $input.prop('disabled', true)
                    .addClass('leave-day')
                    .attr('title','Full Day Leave - Not available');

            } else if (leaveDates.includes(dateStr)) {
                // HALF day leave → enabled, only tooltip
                $input.prop('disabled', false)
                    .addClass('half-leave-day')
                    .attr('title','Half Day Leave');

            } else if (holidays.some(h => h.date === dateStr)) {
                $input.prop('disabled', true)
                    .addClass('holiday-day')
                    .attr('title','Holiday - Not available');

            } else {
                $input.attr('title', `Hours for day ${day}`);
            }

            $cell.append($input);
            $row.append($cell);
            dayInputs.push($input);
        }

        bindProjectRowEvents($row, dayInputs, resourceId);
        return $row;
    }

    // Bind events for each row
    function bindProjectRowEvents($row, dayInputs, resourceId) {
        let $applyAllInput = $row.find('.apply-all-input');
        let $applyAllBtn   = $row.find('.apply-all-btn');
        let $clearBtn      = $row.find('.clear-row-btn');
        let $deleteBtn     = $row.find('.delete-row-btn');

        // Apply-all
        $applyAllBtn.on('click', function() {
            let value = parseFloat($applyAllInput.val());

            console.log("MAX_HOURS_PER_DAY 222", MAX_HOURS_PER_DAY);
            if (isNaN(value) || value < 0 || value > MAX_HOURS_PER_DAY) {
                showNotification(`Please enter a valid number between 0 and ${MAX_HOURS_PER_DAY} hours`, 'danger');
                $applyAllInput.focus().select();
                return;
            }

            let projectId = $row.data('project-id');
            let month     = $('#monthSelect').val();

            let projectStart = new Date($row.data('project-start'));
            let projectEnd   = new Date($row.data('project-end'));
            let totalHours   = parseFloat($row.data('project-total-hours')) || 0;

            let allocatedSoFar = 0;
            let daily_hours = [];
            dayInputs.forEach($input => {
                if (!$input.prop('disabled')) {
                    let day = $input.data('day');
                    let dateStr = `${month}-${String(day).padStart(2, '0')}`;
                    daily_hours.push({ date: dateStr, hours: value });
                    $input.val(value); // update UI
                }
            });

            updateResourceAllocation(resourceId);

            $.ajax({
                url: "{{ route('capacity-planning.allocations.save') }}",
                method: "POST",
                data: {
                    resource_id: resourceId,
                    project_id: projectId,
                    month: month,
                    daily_hours: JSON.stringify(daily_hours),
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    if (res.success) {
                        showNotification('Hours applied and saved successfully!', 'success');

                        // Reset UI to match backend’s final accepted allocations
                        let accepted = res.data.daily_hours ? JSON.parse(res.data.daily_hours) : [];
                        let acceptedMap = {};
                        accepted.forEach(d => { acceptedMap[d.date] = d.hours; });

                        dayInputs.forEach($input => {
                            let day = $input.data('day');
                            let dateStr = `${month}-${String(day).padStart(2, '0')}`;
                            if (acceptedMap[dateStr] !== undefined) {
                                $input.val(acceptedMap[dateStr]);
                            } else {
                                $input.val('');
                            }
                        });

                    } else {
                        showNotification(res.message || 'Error saving allocations', 'danger');
                    }
                },
                error: function() {
                    showNotification('Error saving allocations', 'danger');
                }
            });
        });

        dayInputs.forEach($input => {
            let oldVal = $input.val();

            $input.on('input', function() {
                let $this = $(this);
                let val = $this.val().trim();

                // Allow empty input
                if (val === '' || $this.prop('disabled')) {
                    $this.removeClass('invalid-input');
                    oldVal = val;
                    return;
                }

                // Check if it is a valid number (0-8) and no extra characters
                // if (/^\d+(\.\d+)?$/.test(val)) {
                if (/^\d+(\.\d+)?$/.test(val)) {
                    let num = parseFloat(val);
                    // let num = parseInt(val, 10);
                    console.log("num", num);

                    console.log("MAX_HOURS_PER_DAY 222", MAX_HOURS_PER_DAY);
                    if (num >= 0 && num <= MAX_HOURS_PER_DAY) {
                        oldVal = val;
                        $this.removeClass('invalid-input');
                        return;
                    }
                }

                // Invalid input
                $this.addClass('invalid-input');
                showNotification(`Please enter a valid number between 0 and ${MAX_HOURS_PER_DAY} hours`, 'danger');

                clearTimeout($this.data('timeoutId'));
                let timeoutId = setTimeout(() => {
                    $this.val(oldVal);
                    $this.removeClass('invalid-input');
                }, 2000);

                $this.data('timeoutId', timeoutId);
            });
        });

        // Clear row
        $clearBtn.on('click', function() {
            dayInputs.forEach($input => $input.val(''));
            $applyAllInput.val('');
            updateResourceAllocation(resourceId);
            showNotification('Row cleared', 'success');
        });

        // Delete row
        $deleteBtn.on('click', function () {
            if (confirm('Are you sure you want to delete this project allocation?')) {
                let allocationId = $row.data('allocation-id'); // make sure you set this in HTML
                let url = "{{ route('capacity-planning.allocations.delete', ':id') }}".replace(':id', allocationId);

                $.ajax({
                    url: url,
                    method: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (res) {
                        if (res.success) {
                            $row.fadeOut(300, function () {
                                $row.remove();
                                updateResourceAllocation(resourceId);
                            });
                            showNotification(res.message, 'success');
                        } else {
                            showNotification(res.message || 'Delete failed', 'danger');
                        }
                    },
                    error: function () {
                        showNotification('Error deleting allocation', 'danger');
                    }
                });
            }
        });

        // Enter key should trigger Apply
        $applyAllInput.on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                let value = parseFloat($applyAllInput.val());

                // Check if value is a number and within 0-8
                console.log("MAX_HOURS_PER_DAY33", MAX_HOURS_PER_DAY);
                if (isNaN(value) || value < 0 || value > MAX_HOURS_PER_DAY) {
                    showNotification(`Please enter a valid number between 0 and ${MAX_HOURS_PER_DAY} hours`, 'danger');
                    $applyAllInput.focus().select();
                    return;
                }

                $applyAllBtn.click(); // valid, trigger apply
            }
        });
    }

    // Update allocation summary
    function updateResourceAllocation(resourceId){
        let meta = resourceMeta[resourceId] || {};
        // let leaveDates = meta.leaveDates || [];
        let leaveInfo = meta.leaveDates?.leaves || [];
        let leaveDates = meta.leaveDates?.dates || [];

        function isFullDayLeave(dateStr, leaveInfo) {
            return leaveInfo.some(l =>
                l.number_of_days >= 1 &&
                l.dates.includes(dateStr)
            );
        }

        console.log("leaveDates", leaveDates);
        let $table = $(`.allocation-table[data-resource="${resourceId}"]`);
        let $allocationInfo = $(`.allocation-info[data-resource="${resourceId}"]`);
        
        let month = $('#monthSelect').val();
        let totalAllocated = 0;
        let leaveHoursCount = 0;
        let leaveDaysCount = leaveDates.length; // unique leave days

        $table.find('input.day-input').each(function(){
            let $input = $(this);
            console.log("$input", $input);
            let day = $input.data('day'); 
            let dateStr = `${month}-${String(day).padStart(2,'0')}`;

            // Holidays
            if (holidays.some(h => h.date === dateStr)) {
                $input.val('');
                return;
            }

            // Full day leave
            if (isFullDayLeave(dateStr, leaveInfo)) {
                // Only FULL day leave clears input
                $input.val('');
                return;
            }

            // Normal hours
            // let val = parseInt($input.val(), 10);
            let val = parseFloat($input.val());
            if (isNaN(val) || val < 0 || val > MAX_HOURS_PER_DAY) val = 0;
            $input.val(val); // force valid value in UI
            totalAllocated += val;
        });

        // let resourceHolidays = holidays.filter(h => {
        //     let date = new Date(h.date);
        //     let hMonth = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
        //     return hMonth === month;
        // });

        // let holidayHours = resourceHolidays.length * HOURS_PER_DAY;
        // let effectiveCapacity = MAX_HOURS_MONTH - leaveHoursCount - holidayHours;
        // let available = Math.max(0, effectiveCapacity - totalAllocated);
        // let utilizationPercent = effectiveCapacity > 0 ? Math.min(100, Math.round((totalAllocated / effectiveCapacity) * 100)) : 0;

        let resourceHolidays = holidays.filter(h => {
            let d = h.date.substring(0, 7);
            return d === month;
        });

        let holidayDays  = resourceHolidays.length;
        let holidayHours = holidayDays * HOURS_PER_DAY;

        // Leave (already unique)
        // let leaveDays  = leaveDates.length;
        let leaveDays = meta.leaveDates?.total_leave_days || 0;
        let leaveHours = leaveDays * HOURS_PER_DAY;

        // Base monthly capacity (weekdays only)
        let baseCapacity = MAX_HOURS_MONTH;

        // Final effective capacity
        let effectiveCapacity = Math.max(
            0,
            baseCapacity - leaveHours - holidayHours
        );

        // Available hours
        let available = Math.max(0, effectiveCapacity - totalAllocated);

        // Utilization %
        let utilizationPercent = effectiveCapacity > 0
            ? Math.round((totalAllocated / effectiveCapacity) * 100)
            : 0;

        $allocationInfo.html(`
            <span class="status-indicator ${getStatusClass(totalAllocated)}"></span>
            Allocated: ${totalAllocated} hrs / ${effectiveCapacity} hrs | 
            Available: ${available} hrs | 
            Utilization: ${utilizationPercent}% | 
            Leave Days: ${leaveDays} |
            Holidays: ${holidayDays}
        `);

        $allocationInfo.removeClass('overallocated');
        if (totalAllocated > effectiveCapacity) $allocationInfo.addClass('overallocated');
    }


    function getStatusClass(allocated){
        if(allocated>MAX_HOURS_MONTH) return 'status-overallocated';
        if(allocated>MAX_HOURS_MONTH*0.8) return 'status-warning';
        return 'status-available';
    }

    initMonth(); 
    loadResources(); 
    bindEvents(); 
    loadHolidays();
    // renderPanels();

    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ------------------------- Add / Edit Project Modal ------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 

  
    //  Add Project Modal (Start Date End Date Logic) ----------------------
    let startDateInput = document.getElementById('addStartDate');
    let endDateInput = document.getElementById('addEndDate');

    // Today's date in YYYY-MM-DD format
    let today = new Date();
    let yyyy = today.getFullYear();
    let mm = String(today.getMonth() + 1).padStart(2, '0');
    let dd = String(today.getDate()).padStart(2, '0');
    let todayStr = `${yyyy}-${mm}-${dd}`;

    // Remove min restriction for Start Date (allow past dates)
    // startDateInput.min = todayStr; // removed
    endDateInput.min = todayStr; // End date still cannot be before today

    // When Start Date changes
    startDateInput.addEventListener('change', function() {
        let startDate = this.value;
        if (startDate) {
            // End date cannot be before start date
            endDateInput.min = startDate;

            // Reset End Date if it is now invalid
            if (endDateInput.value && endDateInput.value < endDateInput.min) {
                endDateInput.value = '';
            }
        } else {
            endDateInput.min = todayStr;
        }
    });

    // When End Date changes
    endDateInput.addEventListener('change', function() {
        let endDate = this.value;
        if (endDate) {
            // Start date can be any date, so no max restriction needed
        }
    });

    //  Edit Project Modal (Start Date End Date Logic) ----------------------
    let editStartDateInput = document.getElementById('editStartDate');
    let editEndDateInput = document.getElementById('editEndDate');

    // Remove min restriction for Start Date (allow past dates)
    // editEndDateInput.min = todayStr;

    // When Start Date changes
    editStartDateInput.addEventListener('change', function() {
        let startDate = this.value;
        if (startDate) {
            // End date cannot be before start date
            editEndDateInput.min = startDate;

            // Reset End Date if it is now invalid
            if (editEndDateInput.value && editEndDateInput.value < editEndDateInput.min) {
                editEndDateInput.value = '';
            }
        } else {
            editEndDateInput.min = todayStr;
        }
    });

    // When End Date changes
    editEndDateInput.addEventListener('change', function() {
        let endDate = this.value;
        if (endDate) {
            // Start date can be any date, no max restriction needed
        }
    });

    // Initialize Select2
    $('#addManagerSelect').select2({ 
        dropdownParent: $('#projectModal'), 
        placeholder: "Select manager", 
        allowClear: true 
    });

    $('#editManagerSelect').select2({ 
        dropdownParent: $('#projectModal'), 
        placeholder: "Select manager",
        allowClear: true
    });

    $('#addResourceSelect').select2({ 
        dropdownParent: $('#projectModal'), 
        placeholder: "Select resources", 
        allowClear: true,
        width: '100%'
        // dropdownAutoWidth: true
    });

    $('#editResourceSelect').select2({ 
        dropdownParent: $('#projectModal'), 
        placeholder: "Select resources",
        allowClear: true
    });

    $('#priorityOfProject').select2({ 
        dropdownParent: $('#projectModal'), 
        placeholder: "Select priority",
        allowClear: true
    });

    $('#editPriorityOfProject').select2({ 
        dropdownParent: $('#projectModal'), 
        placeholder: "Select priority",
        allowClear: true
    });

    $('#statusofProject').select2({ 
        dropdownParent: $('#projectModal'), 
        placeholder: "Select status",
        allowClear: true
    });

    $('#editStatusofProject').select2({ 
        dropdownParent: $('#projectModal'), 
        placeholder: "Select status",
        allowClear: true
    });

    $('#editProjectSelect').select2({ 
        dropdownParent: $('#projectModal'), 
        placeholder: "Select project",
        allowClear: true
    });


    // This closes & reopens the dropdown so it recalculates position based on new height.
    $('#addResourceSelect').on('select2:select select2:unselect', function (e) {
        // Delay a bit so Select2 can finish DOM changes
        setTimeout(function () {
            $('#addResourceSelect').select2('close'); // Close and re-open to fix position
            $('#addResourceSelect').select2('open');
        }, 10);
    });

    // This closes & reopens the dropdown so it recalculates position based on new height.
    $('#editResourceSelect').on('select2:select select2:unselect', function (e) {
        // Delay a bit so Select2 can finish DOM changes
        setTimeout(function () {
            $('#editResourceSelect').select2('close'); // Close and re-open to fix position
            $('#editResourceSelect').select2('open');
        }, 10);
    });

    // Add Project Validation on keyup ----
    $('#projectName').on('keyup', function () {
        $('#projectName').valid();
    });

    $('#totalHours').on('keyup', function () {
        $('#totalHours').valid();
    });
    
    $('#descriptionPro').on('keyup', function () {
        $('#descriptionPro').valid();
    });
  
    $('#addStartDate').on('change', function () {
        $('#addStartDate').valid();
    });

    $('#addEndDate').on('change', function () {
        $('#addEndDate').valid();
    });

    $('#addManagerSelect').on('change', function () {
        $('#addManagerSelect').valid(); // trigger validation on select change
    });

    $('#addResourceSelect').on('change', function () {
        $('#addResourceSelect').valid(); // trigger validation on select change
    });

    $('#statusofProject').on('change', function () {
        $('#statusofProject').valid(); // trigger validation on select change
    });

    $('#priorityOfProject').on('change', function () {
        $('#priorityOfProject').valid(); // trigger validation on select change
    });

    // Edit Project Validation on keyup ----
    $('#editProjetName').on('keyup', function () {
        $('#editProjetName').valid();
    });

    $('#editTotalHours').on('keyup', function () {
        $('#editTotalHours').valid();
    });
    
    $('#editDescription').on('keyup', function () {
        $('#editDescription').valid();
    });
  
    $('#editStartDate').on('change', function () {
        $('#editStartDate').valid();
    });

    $('#editEndDate').on('change', function () {
        $('#editEndDate').valid();
    });

    $('#editManagerSelect').on('change', function () {
        $('#editManagerSelect').valid(); // trigger validation on select change
    });

    $('#editResourceSelect').on('change', function () {
        $('#editResourceSelect').valid(); // trigger validation on select change
    });

    $('#editStatusofProject').on('change', function () {
        $('#editStatusofProject').valid(); // trigger validation on select change
    });

    $('#editPriorityOfProject').on('change', function () {
        $('#editPriorityOfProject').valid(); // trigger validation on select change
    });

    // This closes & reopens the dropdown so it recalculates position based on new height.
    $('#resourceSelect').on('select2:select select2:unselect', function (e) {
        // Delay a bit so Select2 can finish DOM changes
        setTimeout(function () {
            $('#resourceSelect').select2('close'); // Close and re-open to fix position
            $('#resourceSelect').select2('open');
        }, 10);
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#addManagerSelect').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Select project manager');
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#editManagerSelect').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Select project manager');
    });

     /* Inside set placeholder in search dropdown select2 */
    $('#statusofProject').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Select status');
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#priorityOfProject').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Select priority');
    });

     /* Inside set placeholder in search dropdown select2 */
    $('#editStatusofProject').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Select status');
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#editPriorityOfProject').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Select priority');
    });

    // Open Add/Edit Project Modal
    $('#addEditProjectBtn').on('click', function() {
        $('#addManagerSelect, #addResourceSelect, #statusofProject, #priorityOfProject, #editProjectSelect, #editManagerSelect, #editResourceSelect, #editStatusofProject, #editPriorityOfProject').val(null).trigger('change');

        $('#chooseActionStep').removeClass('d-none');
        $('#addProjectForm').addClass('d-none');
        $('#editProjectForm').addClass('d-none');
        $('#editProjectDropdownContainer').addClass('d-none');
        $('#modalTitle').text('Add/Edit Project');
        $('#projectModal').modal('show');
        $('#footerDiv').addClass('d-none');
    });

    // Choose Add
    $('#chooseAddBtn').on('click', function() {
        $('#chooseActionStep').addClass('d-none');
        $('#addProjectForm').removeClass('d-none');
        $('#editProjectDropdownContainer').addClass('d-none');
        $('#modalTitle').text('Add Project');
        $('#addProjectForm')[0].reset();
        $('#managerSelect, #resourceSelect').val(null).trigger('change');

        $('#addProjectForm .form-scrollable').animate({ scrollTop: 0 }, 'slow');
        $('#footerDiv').removeClass('d-none');
    });

    // Choose Edit
    $('#chooseEditBtn').on('click', function() {
        $('#chooseActionStep').addClass('d-none');
        $('#editProjectForm').addClass('d-none');
        $('#editProjectDropdownContainer').removeClass('d-none');
        $('#modalTitle').text('Edit Project');
        $('#editProjectForm')[0].reset();
        $('#managerSelect, #resourceSelect').val(null).trigger('change');
         $('#footerDiv').addClass('d-none');
    });

    // Load selected project data via AJAX
    // Load selected project data (already given)
    $('#editProjectSelect').on('change', function() {
        $('#editProjectForm').removeClass('d-none');
        $('#editFooterDiv').removeClass('d-none');
        $('#editProjectDropdownContainer').addClass('d-none');

        $('#editProjectForm .form-scrollable').animate({ scrollTop: 0 }, 'slow');

        let projectId = $(this).val();
        if (!projectId) return;

        $.ajax({
            url: `/projects/${projectId}/edit`,
            method: 'GET',
            success: function(data) {
                console.log("data", data);
                let form = $('#editProjectForm');
                form.attr('data-project-id', data.id);
                form.find('input[name="name"]').val(data.name);
                form.find('input[name="total_hours"]').val(data.total_hours);
                form.find('textarea[name="description"]').val(data.description);
                form.find('input[name="start_date"]').val(data.start_date);
                form.find('input[name="end_date"]').val(data.end_date);
                form.find('select[name="project_manager_id"]').val(data.project_manager_id).trigger('change');
                form.find('select[name="resource_ids[]"]').val(data.resource_ids).trigger('change');
                form.find('select[name="status"]').val(data.status).trigger('change');
                form.find('select[name="priority"]').val(data.priority).trigger('change');
                form.find('input[name="is_billable"]').prop('checked', data.is_billable);
            }
        });
    });

    // Initialize jQuery Validation for Add Project
    $("#addProjectForm").validate({
        errorElement: "span",
        errorClass: "error",
        highlight: function (element) {
            $(element).closest(".form-group").addClass("has-error");
        },
        unhighlight: function (element) {
            $(element).closest(".form-group").removeClass("has-error");
        },
        errorPlacement: function (error, element) {
            if (element.hasClass('select-search')) {
                error.insertAfter(element.next('.select2')); // For select2 dropdowns
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            name: {
                required: true,
                minlength: 2,
                maxlength: 50
            },
            total_hours: {
                required: true,
                number: true,
                min: 1,
                max: 10000
            },
            description: {
                minlength: 2,
                maxlength: 5000
            },
            start_date: {
                required: true,
                date: true
            },
            end_date: {
                required: true,
                date: true
            },
            project_manager_id: {
                required: true
            },
            'resource_ids[]': {
                required: true
            }
        },
        messages: {
            name: {
                required: "Project Name is required",
                minlength: "Minimum 2 characters required",
                maxlength: "Maximum 50 characters allowed"
            },
            total_hours: {
                required: "Total hours are required",
                number: "Must be a valid number",
                min: "Minimum value is 1",
                max: "Maximum value is 10000"
            },
            description: {
                minlength: "Minimum 2 characters required",
                maxlength: "Maximum 5000 characters allowed"
            },
            start_date: {
                required: "Start date is required",
            },
            end_date: {
                required: "End date is required",
            },
            project_manager_id: {
                required: "Please select a project manager"
            },
            'resource_ids[]': {
                required: "At least one resource must be assigned"
            }
        },
        submitHandler: function(form, event) {
            event.preventDefault();

            // // Check date logic
            // let startDate = new Date($('#addProjectForm input[name="start_date"]').val());
            // let endDate = new Date($('#addProjectForm input[name="end_date"]').val());
            // if (endDate < startDate) {
            //     alert("End Date must be after Start Date.");
            //     return false;
            // }

            let formData = new FormData(form);

            // normalize checkbox
            formData.set('is_billable', $('#addProjectForm input[name="is_billable"]').is(':checked') ? 1 : 0);

            // Submit AJAX
            $.ajax({
                url: "{{ route('projects.store') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // swal("Success", response.message, "success");
                        showNotification(response.message, 'success');

                        $('#projectModal').modal('hide');
                        form.reset();
                        $('#addManagerSelect, #addResourceSelect').val(null).trigger('change');
                        // optionally reload or refresh project list
                    } else {
                        // swal("Error", response.message || "Something went wrong", "error");
                        showNotification(response.message || "Something went wrong", 'danger');
                    }
                },
                error: function(xhr) {
                    let errorMsg = "Something went wrong!";
                    if (xhr.responseJSON?.errors) {
                        errorMsg = Object.values(xhr.responseJSON.errors).flat().join("\n");
                    }
                    // swal("Error", errorMsg, "error");
                    showNotification(errorMsg || "Something went wrong", 'danger');
                }
            });
        }
    });

    // Submit Edit Form
    // Initialize jQuery Validation for Edit Project (Same rules/messages)
    $("#editProjectForm").validate({
        errorElement: "span",
        errorClass: "error",
        highlight: function (element) {
            $(element).closest(".form-group").addClass("has-error");
        },
        unhighlight: function (element) {
            $(element).closest(".form-group").removeClass("has-error");
        },
        errorPlacement: function (error, element) {
            if (element.hasClass('select-search')) {
                error.insertAfter(element.next('.select2'));
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            name: {
                required: true,
                minlength: 2,
                maxlength: 50
            },
            total_hours: {
                required: true,
                number: true,
                min: 1,
                max: 10000
            },
            description: {
                minlength: 2,
                maxlength: 5000
            },
            start_date: {
                required: true,
                date: true
            },
            end_date: {
                required: true,
                date: true
            },
            project_manager_id: {
                required: true
            },
            'resource_ids[]': {
                required: true
            }
        },
        messages: {
            name: {
                required: "Project Name is required",
                minlength: "Minimum 2 characters required",
                maxlength: "Maximum 50 characters allowed"
            },
            total_hours: {
                required: "Total hours are required",
                number: "Must be a valid number",
                min: "Minimum value is 1",
                max: "Maximum value is 10000"
            },
            description: {
                minlength: "Minimum 2 characters required",
                maxlength: "Maximum 5000 characters allowed"
            },
            start_date: {
                required: "Start date is required",
            },
            end_date: {
                required: "End date is required",
            },
            project_manager_id: {
                required: "Please select a project manager"
            },
            'resource_ids[]': {
                required: "At least one resource must be assigned"
            }
        },
        submitHandler: function(form, event) {
            event.preventDefault();

            let projectId = $(form).attr('data-project-id');
            let formData = new FormData(form);
            formData.set('is_billable', $('#editProjectForm input[name="is_billable"]').is(':checked') ? 1 : 0);

            $.ajax({
                url: `/projects/${projectId}`,
                method: 'POST', // or 'PUT' depending on your route
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message, 'success');
                        $('#editProjectForm')[0].reset();
                        $('#editProjectForm').addClass('d-none');
                        $('#editProjectDropdownContainer').removeClass('d-none');
                        $('#projectModal').modal('hide');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        Object.values(errors).forEach(err => showNotification(err[0], 'error'));
                    } else {
                        showNotification("Something went wrong while updating the project", 'danger');
                    }
                }
            });
        }
    });

    // reset the form every time the modal is closed
    $('#projectModal').on('hidden.bs.modal', function () {
        // Reset the form
        $('#addProjectForm')[0].reset();
        $('#editProjectForm')[0].reset();

        $("#addProjectForm").data('validator').resetForm();
        $("#editProjectForm").data('validator').resetForm();

        // Reset Select2 fields
        $('#addManagerSelect, #addResourceSelect, #statusofProject, #priorityOfProject, #editProjectSelect, #editManagerSelect, #editResourceSelect, #editStatusofProject, #editPriorityOfProject').val(null).trigger('change');

    });


    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ------------------------- Holiday Manage Modal ------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    let editingId = null;

    // Initialize Flatpickr
    const flatpickrInstance = flatpickr("#dateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function(selectedDates, dateStr) {
            // hide legacy error UI
            $('#dateError').hide();
            // trigger jQuery Validate for this field
            $('input[name="date_range"]').valid();
        }
    });

    // CSRF setup for all AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    /** ============================
     *  Load & Render Holidays
     * ============================ */
    function loadHolidays() {
        $.get('/holidays', res => {
            holidays = res || [];
            renderHolidays();

            $('#holidayForm').validate().resetForm();
        }).fail(() => {
            showNotification("Failed to load holidays", "danger");
        });
    }

    function renderHolidays() {
        const listContainer = $('#holidaysList');
        if (!holidays.length) {
            listContainer.html('<div class="holidays-empty-state">No holidays added yet</div>');
            return;
        }

        listContainer.html(holidays.map(h => `
            <div class="holidays-item fade-in" data-id="${h.id}">
                <div class="holidays-item-info">
                    <div class="holidays-item-date">${formatDate(h.date)}</div>
                    <div class="holidays-item-desc">${h.description}</div>
                </div>
                <div class="holidays-item-actions">
                    <button class="holidays-btn-edit btn btn-sm btn-outline-primary" data-id="${h.id}">Edit</button>
                    <button class="holidays-btn-delete btn btn-sm btn-outline-danger" data-id="${h.id}">Delete</button>
                </div>
            </div>
        `).join(''));
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
    }

    /** ============================
     *  Form Validation (jQuery Validate)
     * ============================ */
    $("#holidayForm").validate({
        errorElement: "span",
        errorClass: "error",
        // validate on input/keyup/change
        onkeyup: function(element) { $(element).valid(); },
        onfocusout: function(element) { $(element).valid(); },
        highlight: function (element) {
            $(element).addClass("is-invalid");
            $(element).closest(".form-group").addClass("has-error");
        },
        unhighlight: function (element) {
            $(element).removeClass("is-invalid");
            $(element).closest(".form-group").removeClass("has-error");
        },
        errorPlacement: function (error, element) {
            // For select2 or custom inputs you might want to place differently
            if (element.hasClass('select-search')) {
                error.insertAfter(element.next('.select2-container'));
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            date_range: { required: true },
            description: { required: true, minlength: 2, maxlength: 500 }
        },
        messages: {
            date_range: { required: "Please select a date range" },
            description: {
                required: "Please enter a description",
                minlength: "At least 2 characters required",
                maxlength: "Maximum 500 characters allowed"
            }
        },
        submitHandler: function(form, event) {
            event.preventDefault(); // keep default prevented
            saveHoliday();         // call your save function
        }
    });

    /** ============================
     *  Add / Update Holiday
     * ============================ */
    function saveHoliday() {
        const dateRange = $('#dateRange').val();
        const desc = $('#description').val().trim();

        let data = { description: desc };

        if (editingId) {
            data.date = dateRange; // send as 'date', not 'date_range'
            $.ajax({
                url: `/holidays/${editingId}`,
                type: 'POST',
                data: data,
                success: (response) => {
                    editingId = null;
                    $('#btnText').text('Add Holiday');
                    $('#holidayForm')[0].reset();
                    $('#holidayForm').validate().resetForm();
                    flatpickrInstance.clear();
                    loadHolidays();
                    // $('#holidayModal').modal('hide');
                    showNotification(response.message || "Holiday updated successfully", 'success');
                },
                error: (err) => {
                    showNotification(err.responseJSON?.message || "Failed to update holiday", 'danger');
                }
            });
        } else {
            data.date_range = dateRange; // keep date_range for adding multiple dates
            $.post('/holidays', data)
                .done(response => {
                    $('#holidayForm')[0].reset();
                    $('#holidayForm').validate().resetForm();
                    flatpickrInstance.clear();
                    loadHolidays();
                    // $('#holidayModal').modal('hide');
                    showNotification(response.message || "Holiday added successfully", 'success');
                })
                .fail(err => {
                    showNotification(err.responseJSON?.message || "Failed to save holiday", 'danger');
                });
        }
    }


    /** ============================
     *  Edit Holiday
     * ============================ */
    $('#holidaysList').on('click', '.holidays-btn-edit', function() {
        const id = $(this).data('id');
        const h = holidays.find(x => x.id === id);
        if (!h) return;

        editingId = id;
        flatpickrInstance.setDate(h.date);
        $('#dateRange').val(h.date);
        $('#description').val(h.description);
        $('#btnText').text('Update Holiday');
        $('#holidayModal').modal('show');
    });

    /** ============================
     *  Delete Holiday (Smooth UI)
     * ============================ */
    $('#holidaysList').on('click', '.holidays-btn-delete', function() {
        const id = $(this).data('id');
        const item = $(this).closest('.holidays-item');

        $.ajax({
            url: `/holidays/${id}`,
            type: 'DELETE',
            success: (response) => {
                // Smooth fade-out removal
                item.fadeOut(400, function() {
                    $(this).remove();
                    if (!$('#holidaysList').children().length) {
                        $('#holidaysList').html('<div class="holidays-empty-state">No holidays added yet</div>');
                    }
                });
                showNotification(response.message || "Holiday deleted successfully", 'success');
            },
            error: (err) => {
                showNotification(err.responseJSON?.message || "Failed to delete holiday", 'danger');
            }
        });
    });

    /** ============================
     *  Modal Reset
     * ============================ */
    $('#holidayModal').on('hidden.bs.modal', function() {
        $('#holidayForm')[0].reset();
        $('#btnText').text('Add Holiday');
        $("#holidayForm").data('validator').resetForm();
    });

    // Initial Load
    loadHolidays();


    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ------------------------- Add / Edit Resource Modal ------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 


    $('#manage-resource-editSelect').select2({
        width: '100%',
        dropdownParent: $('#manageResourceModal'), 
        placeholder: "Select resources",
        allowClear: true
    });
    
    $('#manage-resource-deptSelect').select2({
        width: '100%',
        dropdownParent: $('#manageResourceModal'), 
        placeholder: "Select department",
        allowClear: true
    });

    $('#manage-resource-editDept').select2({
        width: '100%',
        dropdownParent: $('#manageResourceModal'), 
        placeholder: "Select department",
        allowClear: true
    });

    $('#manage-resource-statusSelect').select2({
        width: '100%',
        dropdownParent: $('#manageResourceModal'), 
        placeholder: "Select status",
        allowClear: true
    });

    $('#manage-resource-editStatus').select2({
        width: '100%',
        dropdownParent: $('#manageResourceModal'), 
        placeholder: "Select status",
        allowClear: true
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#manage-resource-editSelect').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Search resource');
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#manage-resource-deptSelect').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Search department');
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#manage-resource-statusSelect').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Search status');
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#manage-resource-editDept').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Search department');
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#manage-resource-editStatus').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Search status');
    });

    // Add Project Validation on keyup ----
    $('#manage-resource-editName, #manage-resource-nameInput').on('keyup', function () {
        $('#manage-resource-editName').valid();
        $('#manage-resource-nameInput').valid();
    });

    $('#manage-resource-editEmail, #manage-resource-emailInput').on('keyup', function () {
        $('#manage-resource-editEmail').valid();
        $('#manage-resource-emailInput').valid();
    });

    $('#manage-resource-role, #manage-resource-editRole').on('keyup', function () {
        $('#manage-resource-role').valid();
        $('#manage-resource-editRole').valid();
    });

    $('#manage-resource-editCapacity, #manage-resource-daily-capacity').on('keyup', function () {
        $('#manage-resource-editCapacity').valid();
        $('#manage-resource-daily-capacity').valid();
    });

    $('#manage-resource-total-hours, #manage-resource-editTotalHours').on('keyup', function () {
        $('#manage-resource-total-hours').valid();
        $('#manage-resource-editTotalHours').valid();
    });

    $('#manage-resource-leave-hours, #manage-resource-editLeaveHours').on('keyup', function () {
        $('#manage-resource-leave-hours').valid();
        $('#manage-resource-editLeaveHours').valid();
    });

    /* ------------------------------------
         OPEN: ADD RESOURCE FORM
    ------------------------------------ */
    $('#manageResourceAddBtn').on('click', function () {

        // Hide Add/Edit buttons
        $('#manage-resource-chooseAction').addClass('d-none');

        // Hide edit stuff
        $('#manage-resource-editDropdownContainer').addClass('d-none');
        $('#manage-resource-editForm').addClass('d-none');

        // Show Add Form
        $('#manage-resource-addForm').removeClass('d-none');

        // Update modal title
        $('#manageResourceModalTitle').text('Add Resource');

        // Reset Add Form
        $('#manage-resource-addForm')[0].reset();

        // Reset Select2 fields
        $('#manage-resource-addForm .select-search').val(null).trigger('change');

        // Scroll to top
        $('.manage-resource-scroll').animate({ scrollTop: 0 }, 'slow');
    });

    /* ------------------------------------
         OPEN: EDIT RESOURCE DROPDOWN
    ------------------------------------ */
    $('#manageResourceEditBtn').on('click', function () {

        // Hide choose section
        $('#manage-resource-chooseAction').addClass('d-none');

        // Show dropdown
        $('#manage-resource-editDropdownContainer').removeClass('d-none');

        // Hide edit form initially
        $('#manage-resource-editForm').addClass('d-none');

        // Update title
        $('#manageResourceModalTitle').text('Edit Resource');

        // Reset dropdown
        $('#manage-resource-editSelect').val(null).trigger('change');
    });

    /* -----------------------------------------------------
         WHEN USER SELECTS A RESOURCE FROM DROPDOWN
    ------------------------------------------------------ */
    $('#manage-resource-editSelect').on('change', function () {

        let id = $(this).val();

        if (!id) {
            $('#manage-resource-editForm').addClass('d-none');
            return;
        }

        // Show Edit Form
        $('#manage-resource-editForm').removeClass('d-none');

        // AJAX fetch resource details
        $.ajax({
            url: "/resources/" + id,    // <-- Your GET route must return resource details
            method: "GET",
            success: function (response) {

                let data = response.data;

                // Show dropdown
                $('#manage-resource-editDropdownContainer').addClass('d-none');

                // Fill values
                $('#manage-resource-editName').val(data.name);
                $('#manage-resource-editEmail').val(data.email);
                $('#manage-resource-editDept').val(data.dept_id).trigger('change');
                $('#manage-resource-editRole').val(data.role);
                $('#manage-resource-editCapacity').val(data.daily_capacity);
                $('#manage-resource-editTotalHours').val(data.total_hours);
                $('#manage-resource-editLeaveHours').val(data.leave_hours);
                $('#manage-resource-editStatus').val(data.status).trigger('change');

                $('#manage-resource-isManagerEdit').prop('checked', data.is_project_manager == 1);

                // Scroll top
                $('#manage-resource-editForm .manage-resource-scroll').animate({ scrollTop: 0 }, 'slow');
            }
        });
    });

    /* ------------------------------------
         RESET MODAL ON CLOSE
    ------------------------------------ */
    $('#manageResourceModal').on('hidden.bs.modal', function () {
        console.log("here add or edit modal close");
        // Reset everything back
        $('#manage-resource-chooseAction').removeClass('d-none');

        $('#manage-resource-addForm').addClass('d-none');
        $('#manage-resource-editForm').addClass('d-none');
        $('#manage-resource-editDropdownContainer').addClass('d-none');

        // Reset title
        $('#manageResourceModalTitle').text('Add / Edit Resource');

        // Reset all forms and selects
        $('form').each(function () {
            this.reset();
        });

        $('.select-search').val(null).trigger('change');

        // Reset dropdown values
        $('#manage-resource-deptSelect').val(null).trigger('change');

        // Reset dropdown values
        $('#manage-resource-editDept').val(null).trigger('change');

        // Reset form validation errors
        setTimeout(() => {
            $('#manage-resource-addForm').validate().resetForm();
            $('#manage-resource-editForm').validate().resetForm();
        }, 1500);
    });

    $('#manageResourceModal').on('show.bs.modal', function () {
        console.log("here add or edit modal opend shown");
        // Reset all forms and selects
        $('form').each(function () {
            this.reset();
        })

         console.log("here add or edit modal opend1");

        $('.select-search').val(null).trigger('change');

        console.log("here add or edit modal opend 22");

        // Reset form validation errors
        setTimeout(() => {
            $('#manage-resource-addForm').validate().resetForm();
            $('#manage-resource-editForm').validate().resetForm();
        }, 1500);

        console.log("here add or edit modal opend 333");
    });

    /* -------------------------------------
    Custom Validation Methods
    ------------------------------------- */

    // Letters & spaces only (no special characters, no numbers)
    $.validator.addMethod("lettersOnly", function (value, element) {
        return this.optional(element) || /^[a-zA-Z\s]+$/.test(value);
    }, "Only letters and spaces are allowed");

    // Integer only
    $.validator.addMethod("integerOnly", function (value, element) {
        return this.optional(element) || /^[0-9]+$/.test(value);
    }, "Only whole numbers are allowed");

    const resourceValidationRules = {
        name: {
            required: true,
            minlength: 2,
            maxlength: 100,
            lettersOnly: true
        },
        email: {
            required: true,
            email: true
        },
        dept_id: {
            required: true
        },
        role: {
            minlength: 2,
            maxlength: 50,
            lettersOnly: true
        },
        daily_capacity: {
            integerOnly: true,
            min: 0,
            max: 10,
        },
        total_hours: {
            integerOnly: true,
            min: 0,
            max: 200
        },
        leave_hours: {
            integerOnly: true,
            min: 0,
            max: 200
        }
    };

    const resourceValidationMessages = {
        name: {
            required: "Full name is required",
            minlength: "Minimum 2 characters required",
            maxlength: "Maximum 100 characters allowed"
        },
        email: {
            required: "Email is required",
            email: "Enter a valid email address"
        },
        dept_id: {
            required: "Department is required"
        },
        role: {
            minlength: "Minimum 2 characters required",
            maxlength: "Maximum 50 characters allowed"
        },
        daily_capacity: {
            integerOnly: "Daily capacity must be a number",
            min: "Minimum value is 0",
            max: "Maximum value is 10"
        },
        total_hours: {
            integerOnly: "Total hours must be a number",
            min: "Minimum value is 0",
            max: "Maximum value is 200"
        },
        leave_hours: {
            integerOnly: "Leave hours must be a number",
            min: "Minimum value is 0",
            max: "Maximum value is 200"
        }
    };

    $('#manage-resource-deptSelect').on('change', function () {
        $('#manage-resource-deptSelect').valid(); // trigger validation on select change
    });

    $('#manage-resource-editDept').on('change', function () {
        $('#manage-resource-editDept').valid(); // trigger validation on select change
    });

    /* -------------------------------------------------
        ADD RESOURCE AJAX SUBMIT AND VALIDATION
    --------------------------------------------------*/
    // Initialize jQuery Validation for Add resource
    $("#manage-resource-addForm").validate({
        errorElement: "span",
        errorClass: "error",

        highlight: function (element) {
            $(element).addClass("is-invalid");
        },
        unhighlight: function (element) {
            $(element).removeClass("is-invalid");
        },

        errorPlacement: function (error, element) {
            if (element.hasClass('select-search')) {
                error.insertAfter(element.next('.select2'));
            } else {
                error.insertAfter(element);
            }
        },

        rules: resourceValidationRules,
        messages: resourceValidationMessages,

        submitHandler: function (form) {
            let formData = new FormData(form);

            $.ajax({
                url: "/resources",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,

                beforeSend: function () {
                    $('#manage-resource-addForm button[type="submit"]').prop('disabled', true).text("Saving...");
                    $('.error-msg').remove();  // remove old errors
                },

                success: function (response) {
                    showNotification("Resource added successfully!", 'success');

                    $('#manageResourceModal').modal('hide');

                    // Optional: Refresh table or page
                    if (typeof refreshResourceTable === "function") {
                        refreshResourceTable();
                    }
                },

                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;

                        $.each(errors, function (key, value) {
                            let input = $('#manage-resource-addForm').find('[name="' + key + '"]');
                            input.after('<span class="text-danger error-msg">' + value[0] + '</span>');
                        });
                    }
                    showNotification("Failed to add resource", "danger");
                },

                complete: function () {
                    $('#manage-resource-addForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save me-2"></i> Save');
                }
            });
        }
    });

    /* -------------------------------------------------
        UPDATE RESOURCE AJAX SUBMIT
    --------------------------------------------------*/
    $("#manage-resource-editForm").validate({
        errorElement: "span",
        errorClass: "error",

        highlight: function (element) {
            $(element).addClass("is-invalid");
        },
        unhighlight: function (element) {
            $(element).removeClass("is-invalid");
        },

        errorPlacement: function (error, element) {
            if (element.hasClass('select-search')) {
                error.insertAfter(element.next('.select2'));
            } else {
                error.insertAfter(element);
            }
        },

        rules: resourceValidationRules,
        messages: resourceValidationMessages,

        submitHandler: function (form) {
            let id = $('#manage-resource-editSelect').val();
            let formData = new FormData(form);
            formData.append('_method', 'PUT');

            $.ajax({
                url: "/resources/" + id,
                method: "POST",  // Laravel PUT via method spoofing
                data: formData,
                contentType: false,
                processData: false,

                beforeSend: function () {
                    $('#manage-resource-editForm button[type="submit"]').prop('disabled', true).text("Updating...");
                    $('.error-msg').remove();
                },

                success: function (response) {
                    showNotification("Resource updated successfully!", 'success');

                    $('#manageResourceModal').modal('hide');

                    if (typeof refreshResourceTable === "function") {
                        refreshResourceTable();
                    }
                },

                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;

                        $.each(errors, function (key, value) {
                            let input = $('#manage-resource-editForm').find('[name="' + key + '"]');
                            input.after('<span class="text-danger error-msg">' + value[0] + '</span>');
                        });
                    }
                    showNotification("Failed to update resource", "danger");
                },

                complete: function () {
                    $('#manage-resource-editForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save me-2"></i> Update');
                }
            });
        }
    });

    // Auto Refresh After Save
    function refreshResourceTable() {
        $('#resourceTable').load(location.href + " #resourceTable");
    }

    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ------------------------- Add / Edit Leave Modal ------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 
    //  ---------------------------------------------------------------------------------------------- 

    let companyHolidays = [];

    function fetchCompanyHolidays() {
        return $.get('/api/company-holidays')
            .done(res => {
                companyHolidays = res;
            })
            .fail(() => {
                companyHolidays = []; // fallback
            });
    }

    function isSunday(date) {
        return date.getDay() === 0; // Sunday only
    }

    // function getLeaveDayCount(leave) {
    //     // Half day → always 0.5
    //     if (leave.duration === 'half') {
    //         return 0.5;
    //     }

    //     // Full day → calculate working days
    //     return calculateWorkingDays(
    //         new Date(leave.start_date),
    //         new Date(leave.end_date)
    //     );
    // }

    function getLeaveDayCount(leave) {
        return parseFloat(leave.number_of_days);
    }

    function calculateWorkingDays(start, end) {
        let count = 0;
        let current = new Date(start);

        while (current <= end) {
            const day = current.getDay();
            const formatted = current.toISOString().split('T')[0];

            if (day !== 0 && !companyHolidays.includes(formatted)) {
                count++;
            }
            current.setDate(current.getDate() + 1);
        }
        return count;
    }

    let leaveStartPicker, leaveEndPicker;

    function initLeaveFlatpickr() {

        if (leaveStartPicker) {
            leaveStartPicker.destroy();
            leaveEndPicker.destroy();
        }

        leaveStartPicker = flatpickr("#leaveStart", {
            dateFormat: "Y-m-d",
            disableMobile: true,
            // minDate: new Date(),
            minDate: "2025-12",
            disable: [
                date => date.getDay() === 0, // Sunday
                ...companyHolidays
            ],
            onChange(selectedDates) {

                const startDate = selectedDates[0];
                const duration = $('#leaveDuration').val();

                // Always set min end date
                leaveEndPicker.set('minDate', startDate);

                if (duration === 'half') {
                    // Force end date = start date
                    leaveEndPicker.setDate(startDate, true);
                    leaveEndPicker.set('clickOpens', false);
                    $('#leaveEnd').prop('disabled', true);
                    $('#leaveDaysText').val(0.5);
                } else {
                    leaveEndPicker.set('clickOpens', true);
                    $('#leaveEnd').prop('disabled', false);
                }

                updateLeaveDays();
                checkLeaveOverlap();
            }
        });

        leaveEndPicker = flatpickr("#leaveEnd", {
            dateFormat: "Y-m-d",
            disableMobile: true,
            minDate: new Date(),
            minDate: "2025-12",
            clickOpens: false, // initially disabled
            disable: [
                date => date.getDay() === 0,
                ...companyHolidays
            ],
            onChange() {
                updateLeaveDays();
            }
        });
    }

    function openLeaveModal() {
        fetchCompanyHolidays().then(() => {
            initLeaveFlatpickr();
            loadLeaves();
        });
    }

    $('#manageLeaveModal').on('shown.bs.modal', openLeaveModal);

    function updateLeaveDays() {
        const start = $('#leaveStart').val();
        const end = $('#leaveEnd').val();
        const duration = $('#leaveDuration').val();

        if (!start || !end) {
            $('#leaveDaysText').val(0);
            return;
        }

        let days = calculateWorkingDays(new Date(start), new Date(end));

        // Half day logic
        if (duration === 'half') {
            $('#leaveDaysText').val(0.5);
            return;
        }

        $('#leaveDaysText').val(days);

        // trigger validation
        $('#leaveStart, #leaveEnd').valid();
    }

    function handleLeaveDurationChange() {
        const duration = $('#leaveDuration').val();
        const start = $('#leaveStart').val();

        if (duration === 'half') {

            if (start) {
                // Set end date same as start date
                leaveEndPicker.setDate(start, true);
            }

            // Disable end date selection
            leaveEndPicker.set('clickOpens', false);
            $('#leaveEnd').prop('disabled', true);

            // Half day = 0.5
            $('#leaveDaysText').val(0.5);

        } else {
            // Full day → enable end date
            $('#leaveEnd').prop('disabled', false);
            leaveEndPicker.set('clickOpens', true);

            updateLeaveDays();
        }
    }


    $('#leaveDuration').on('change', function () {
        handleLeaveDurationChange();
        checkLeaveOverlap();
    });

    let leaves = [];
    let leaveEditingId = null;

    $('#leaveResource').select2({
        dropdownParent: $('#manageLeaveModal'),
        width: '100%',
        placeholder: 'Select resource',
        allowClear: true
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#leaveResource').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Search resource');
    });

    $('#leaveType').select2({
        dropdownParent: $('#manageLeaveModal'),
        width: '100%',
        placeholder: 'Select leave type',
        allowClear: true
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#leaveType').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Search leave type');
    });

    $('#leaveDuration').select2({
        dropdownParent: $('#manageLeaveModal'),
        width: '100%',
        placeholder: 'Select duration',
        allowClear: true
    });

    /* Inside set placeholder in search dropdown select2 */
    $('#leaveDuration').on('select2:open', function () {
        // Find the search input inside the dropdown
        let searchBox = $('.select2-container--open .select2-search__field');
        searchBox.attr('placeholder', 'Search duration');
    });

    $("#leaveForm").validate({
        errorElement: "span",
        errorClass: "error",
        ignore: [],

        highlight: function (element) {
            $(element).addClass("is-invalid");

            if ($(element).hasClass('select2-hidden-accessible')) {
                $(element).next('.select2')
                    .find('.select2-selection')
                    .addClass('is-invalid');
            }
        },

        unhighlight: function (element) {
            $(element).removeClass("is-invalid");

            if ($(element).hasClass('select2-hidden-accessible')) {
                $(element).next('.select2')
                    .find('.select2-selection')
                    .removeClass('is-invalid');
            }
        },

        errorPlacement: function (error, element) {
            if ($(element).hasClass('select2-hidden-accessible')) {
                error.insertAfter(element.next('.select2'));
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            resource_id: {
                required: true
            },
            type: {
                required: true
            },
            start_date: {
                required: true,
                date: true
            },
            end_date: {
                required: true,
                date: true
            },
            duration: {
                required: true
            },
            remark: {
                minlength: 2,
                maxlength: 200
            }
        },

        messages: {
            resource_id: "Please select a resource",
            type: "Please select leave type",
            start_date: "Please select start date",
            end_date: "Please select end date",
            duration: "Please select leave duration",
            remark: {
                minlength: "Please enter at least 2 characters",
                maxlength: "Please enter no more than 200 characters"
            }
        },

        submitHandler: function () {
            saveLeave();
        }
    });

    // Add leaveRemark Validation on keyup ----
    $('#leaveRemark').on('keyup', function () {
        $('#leaveRemark').valid();
    });

    function loadLeaves() {
        $.get('/leaves', res => {
            leaves = res;
            renderLeaves();
            $('#leaveForm')[0].reset();
            $('#leaveForm').validate().resetForm();
        }).fail(() => {
            showNotification("Failed to load leaves", "danger");
        });
    }

    function renderLeaves() {
        const container = $('#leaveList');

        if (!leaves.length) {
            container.html('<div class="holidays-empty-state">No leaves added yet</div>');
            return;
        }

        container.html(leaves.map(l => {
            const dayCount = getLeaveDayCount(l);

            return `
                <div class="holidays-item" data-id="${l.id}">
                    <div class="holidays-item-info">
                        <div class="holidays-item-date">
                            ${l.resource.name}
                            • ${l.start_date}
                            ${l.start_date !== l.end_date ? `→ ${l.end_date}` : ''}
                            <span class="holidays-item-desc">
                                (${dayCount === 0.5 ? 'Half day' : `${dayCount} day${dayCount > 1 ? 's' : ''}`})
                            </span>
                        </div>
                        <div class="holidays-item-desc">
                            ${l.type.toUpperCase()}
                            ${l.remark ? ' – ' + l.remark : ''}
                        </div>
                    </div>
                    <div class="holidays-item-actions">
                        <button class="btn btn-sm btn-outline-primary leave-edit" data-id="${l.id}">Edit</button>
                        <button class="btn btn-sm btn-outline-danger leave-delete" data-id="${l.id}">Delete</button>
                    </div>
                </div>
            `;
        }).join(''));
    }

    function saveLeave() {

        let data = {
            resource_id: $('#leaveResource').val(),
            type: $('#leaveType').val(),
            start_date: $('#leaveStart').val(),
            end_date: $('#leaveEnd').val(),
            remark: $('#leaveRemark').val(),
            duration: $('#leaveDuration').val()
        };


        if (leaveEditingId) {
            $.ajax({
                url: `/leaves/${leaveEditingId}`,
                type: 'PUT',
                data,
                success: res => {
                    resetLeaveForm();
                    loadLeaves();
                    showNotification(res.message || "Leave updated", "success");
                }
            });
        } else {
            $.post('/leaves', data)
                .done(res => {
                    resetLeaveForm();
                    loadLeaves();
                    showNotification(res.message || "Leave added", "success");
                });
        }
    }

    $('#leaveList').on('click', '.leave-edit', function () {

        const id = $(this).data('id');
        const l = leaves.find(x => x.id === id);
        if (!l) return;

        leaveEditingId = id;

        // leaveStartPicker.set('minDate', l.start_date);
        // leaveEndPicker.set('minDate', l.start_date);

        leaveStartPicker.setDate(l.start_date, true);
        leaveEndPicker.setDate(l.end_date, true);

        $('#leaveResource').val(l.resource_id).trigger('change');
        $('#leaveType').val(l.type).trigger('change');
        $('#leaveDuration').val(l.duration ?? 'full').trigger('change');

        $('#leaveRemark').val(l.remark);

        // Apply duration rules
        handleLeaveDurationChange();

        // Hide conflict warning on edit
        $('#leaveConflictWarning').addClass('d-none');
        $('#leaveForm button[type=submit]').prop('disabled', false);

        $('#leaveBtnText').text('Update Leave');
    });

    $('#leaveList').on('click', '.leave-delete', function () {
        const id = $(this).data('id');
        const item = $(this).closest('.holidays-item');

        $.ajax({
            url: `/leaves/${id}`,
            type: 'DELETE',
            success: res => {
                item.fadeOut(300, function () {
                    $(this).remove();
                    if (!$('#leaveList').children().length) {
                        $('#leaveList').html('<div class="holidays-empty-state">No leaves added yet</div>');
                    }
                });
                showNotification(res.message || "Leave deleted", "success");
            }
        });
    });

    function resetLeaveForm() {
        leaveEditingId = null;
        $('#leaveForm')[0].reset();
        $('#leaveResource').val(null).trigger('change');

        leaveStartPicker.set('minDate', new Date());
        leaveEndPicker.set('minDate', new Date());

        $('#leaveBtnText').text('Add Leave');
        $('#leaveForm')[0].reset();
        $('#leaveForm').validate().resetForm();

        // Reset select2
        $('#leaveResource, #leaveType, #leaveDuration')
            .val(null).trigger('change');
    }

    $('#leaveResource, #leaveType, #leaveDuration').on('change', function () {
        $(this).valid();
    });

    $('#manageLeaveModal').on('hidden.bs.modal', function () {

        // Reset form
        $('#leaveForm')[0].reset();

        // Reset select2
        $('#leaveResource, #leaveType, #leaveDuration')
            .val(null).trigger('change');

        // Reset flatpickr
        if (leaveStartPicker && leaveEndPicker) {
            leaveStartPicker.clear();
            leaveEndPicker.clear();

            leaveStartPicker.set('minDate', new Date());
            leaveEndPicker.set('minDate', new Date());
        }

        // Reset UI counters
        $('#leaveDaysText').val(0);

        // Reset validation
        $("#leaveForm").validate().resetForm();
        $('#leaveForm').find('.is-invalid').removeClass('is-invalid');

        // Reset mode
        leaveEditingId = null;
        $('#leaveBtnText').text('Add Leave');

        $('#leaveConflictWarning').addClass('d-none');
        $('#leaveForm button[type=submit]').prop('disabled', false);
    });

    $('#manageLeaveModal').on('shown.bs.modal', function () {
        initLeaveFlatpickr();
        loadLeaves();
    });

    function checkLeaveOverlap() {
        const resource = $('#leaveResource').val();
        const start = $('#leaveStart').val();
        const end = $('#leaveEnd').val();

        if (!resource || !start || !end) return;

        $.post('/leaves/check-overlap', {
            resource_id: resource,
            start_date: start,
            end_date: end,
            leave_id: leaveEditingId,
            _token: $('input[name=_token]').val()
        }).done(res => {
            if (res.exists) {
                $('#leaveConflictWarning').removeClass('d-none');
                $('#leaveForm button[type=submit]').prop('disabled', true);
            } else {
                $('#leaveConflictWarning').addClass('d-none');
                $('#leaveForm button[type=submit]').prop('disabled', false);
            }
        });
    }

    $('#leaveResource, #leaveStart, #leaveEnd').on('change', checkLeaveOverlap);

});

</script>
@endsection