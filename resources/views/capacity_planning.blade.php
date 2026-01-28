{{-- 
    Capacity Planning view (Blade template)
    - Extends header-style layout
    - Renders resource / month / project filters
    - Builds per-resource allocation panels with daily inputs
    - Includes modals for managing projects, holidays, resources, and leaves
    - Inline JS handles UI logic, AJAX calls and validation
--}}
@extends('layouts.header-style')

@section('title', 'Capacity Planning – Resource Allocation')

@section('styles')
    <link href="{{ asset('css/main-styles.css') }}?v={{ config('constants.cache_ver') }}" rel="stylesheet">
    <style>
        /* SweetAlert2 Dark Theme Refinements */
        .swal2-popup {
            border: 1px solid var(--card-border) !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5) !important;
            border-radius: 16px !important;
        }

        .swal2-title {
            font-size: 1rem !important;
            font-weight: 600 !important;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple)) !important;
            border-radius: 12px !important;
            font-weight: 500 !important;
        }

        .swal2-cancel {
            border-radius: 12px !important;
            font-weight: 500 !important;
        }
    </style>
@endsection

@section('content')

    <!-- Loader -->
    <div id="chartsLoaderOverlay" aria-hidden="true" style="display:none;">
        <div class="loader-backdrop"></div>

        <div id="chartsLoader" class="loader-spinner">
            <div class="loader" role="status" aria-hidden="true"></div>
            <p class="visually-hidden" aria-live="polite">
                Loading, please wait…
            </p>
        </div>
    </div>

    <!-- Main Container -->
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

                {{-- Action buttons: open modals and trigger saves --}}
                <div>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#manageResourceModal">
                        <i class="fas fa-people-group me-2"></i>Resource
                    </button>
                    <button id="addEditProjectBtn" class="btn btn-primary me-2">
                        <i class="fas fa-briefcase me-2"></i>Project
                    </button>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#manageLeaveModal">
                        <i class="fas fa-user-clock me-2"></i>Leave
                    </button>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#holidayModal">
                        <i class="fas fa-calendar-day me-2"></i>Holiday
                    </button>
                    <button id="saveAllBtn" class="btn btn-primary" disabled>
                        <i class="fas fa-save me-2"></i>Save All Allocations
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <div class="row g-3">
                {{-- Resource multi-select --}}
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

                {{-- Month picker --}}
                <div class="col-lg-2 col-md-4">
                    <label for="monthSelect" class="form-label fw-semibold">
                        <i class="fas fa-calendar me-2"></i>Month
                    </label>
                    <input type="month" id="monthSelect" class="form-control">
                </div>

                {{-- Project filter (select2) --}}
                <div class="col-lg-2 col-md-4">
                    <label for="projectFilter" class="form-label fw-semibold">
                        <i class="fas fa-store me-2"></i>Project
                    </label>
                    <select data-placeholder="Select project" data-allow-clear="true" class="form-select me-2 select-search"
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

                {{-- Monthly capacity summary (calculated client-side) --}}
                <div class="col-lg-3 col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-clock me-2"></i>Monthly Capacity
                    </label>
                    <div class="form-control-plaintext text-info form-control" id="monthlyCapacityHours">160 hours</div>
                </div>
            </div>
        </div>

        <!-- Panels Container: per-resource allocation panels are inserted here -->
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
        /*
                                                                                                                Client-side script for Capacity Planning view.
                                                                                                                - Sets up AJAX CSRF header
                                                                                                                - Initializes UI (select2, date inputs)
                                                                                                                - Loads resources, holidays, leaves and assignments via AJAX
                                                                                                                - Builds allocation grids per resource & project
                                                                                                                - Validates inputs and sends save/delete requests
                                                                                                            */

        /* ---------------------------------------------------------------------
           Global AJAX setup for CSRF (Laravel)
           --------------------------------------------------------------------- */
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(function() {
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

            /* -------------------------
            Configuration constants
            ------------------------- */
            const HOURS_PER_DAY = {{ config('constants.daily_working_hours') }};
            const MAX_HOURS_PER_DAY = {{ config('constants.max_hours_per_day') }};
            const HALF_DAY_HOURS = {{ config('constants.half_day_hours') }};

            function getMaxAllowedHours($input) {
                return $input.hasClass('half-leave-day') ?
                    HALF_DAY_HOURS :
                    MAX_HOURS_PER_DAY;
            }

            /* -------------------------
            State variables / cached jQuery objects
            ------------------------- */
            let holidays = []; //
            let $resourceSelect = $('#resourceSelect');
            let $monthSelect = $('#monthSelect');
            let $panelsContainer = $('#panelsContainer');
            let $saveAllBtn = $('#saveAllBtn');
            let $monthlyCapacityHours = $('#monthlyCapacityHours');
            let MAX_HOURS_MONTH = getMaxHoursMonth($('#monthSelect').val() || new Date().toISOString().slice(0, 7));
            let $projectFilter = $('#projectFilter');

            // resourceMeta stores computed metadata for each resource (leaves, working days, etc.)
            let resourceMeta = {}; // { resourceId: { leaveHours, leaveDays, workingDays, leaveDates, ... } }

            /* -------------------------
            Initialize UI widgets
            ------------------------- */
            // Initialize select2 for project filter
            $projectFilter.select2({
                placeholder: "Select project",
                allowClear: true,
                width: '100%'
            });

            // Project filter change: load project-specific resources or all resources
            $projectFilter.on('change', debounce(function() {
                let projectId = $projectFilter.val() || '';

                if (projectId) {
                    $.get("{{ route('capacity-planning.resources') }}", {
                        project_id: projectId
                    }, function(resources) {
                        // Clear and deduplicate
                        let seen = {};
                        $resourceSelect.empty();
                        resources.forEach(r => {
                            if (!seen[r.id]) {
                                $resourceSelect.append(
                                    `<option value="${r.id}" selected>${r.name}</option>`
                                );
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
            $('#projectFilter').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Select project');
            });

            $monthSelect.on('change', function() {
                // MAX_HOURS_MONTH = getMaxHoursMonth($(this).val());
                MAX_HOURS_MONTH = workingDaysInMonth($(this).val()) * HOURS_PER_DAY;
                renderPanels();
            });

            /* -------------------------
            Helper functions: dates & working days
            ------------------------- */
            // Return number of weekdays in the given YYYY-MM string
            function workingDaysInMonth(yyyyMM) {
                let [year, month] = yyyyMM.split('-').map(Number);
                let totalDays = new Date(year, month, 0).getDate();
                let workingDays = 0;
                for (let day = 1; day <= totalDays; day++) {
                    let dayOfWeek = new Date(year, month - 1, day).getDay();
                    if (dayOfWeek !== 0 && dayOfWeek !== 6) workingDays++;
                }
                return workingDays;
            }

            // Compute maximum available working hours in month (weekdays * hours/day)
            function getMaxHoursMonth(yyyyMM) {
                return workingDaysInMonth(yyyyMM) * HOURS_PER_DAY;
            }

            // Number of days in a month (calendar days)
            function daysInMonth(yyyyMM) {
                let [y, m] = yyyyMM.split('-').map(Number);
                return new Date(y, m, 0).getDate();
            }

            // Helper: is weekend for a given date parts
            function isWeekend(y, m, d) {
                let dow = new Date(y, m - 1, d).getDay();
                return dow === 0 || dow === 6;
            }

            /* -------------------------
            AJAX loaders
            ------------------------- */
            // Load resources (optionally filtered by project)
            // function loadResources(callback) {
            //     let projectId = $projectFilter.val() || '';
            //     $.get("{{ route('capacity-planning.resources') }}", { project_id: projectId }, function(data) {
            //         $resourceSelect.empty();
            //         data.forEach(r => $resourceSelect.append(`<option value="${r.id}">${r.name}</option>`));
            //         initSelect2();
            //         if(callback) callback();
            //     });
            // }

            // Load resources (optionally filtered by project)
            function loadResources(callback) {
                console.log("loadResouces called");
                let projectId = $projectFilter.val() || '';
                console.log("projectId", projectId);

                showLoader();
                $.get("{{ route('capacity-planning.resources') }}", {
                        project_id: projectId
                    })
                    .done(function(data) {
                        $resourceSelect.empty();
                        data.forEach(r => $resourceSelect.append(`<option value="${r.id}">${r.name}</option>`));
                        initSelect2();
                        if (callback) callback();
                    })
                    .fail(function() {
                        showNotification('Failed to load resources', 'danger');
                    })
                    .always(function() {
                        hideLoader();
                    });
            }

            function loadAssignments(resourceId, month, callback) {
                let projectId = $projectFilter.val() || '';
                $.get(`/capacity-planning/assignments/${resourceId}/${month}`, {
                    project_id: projectId
                }, function(assignmentsData) {
                    $.get(`/capacity-planning/leaves/${resourceId}/${month}`, function(leavesData) {
                        callback(assignmentsData,
                            leavesData); // leavesData is array of "YYYY-MM-DD"
                    });
                });
            }

            /* -------------------------
            Initialization helpers
            ------------------------- */
            function initMonth() {
                let d = new Date();
                let m = String(d.getMonth() + 1).padStart(2, '0');
                $monthSelect.val(`${d.getFullYear()}-${m}`);
            }

            // Initialize or re-init Select2 on resource select
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

            /* -------------------------
            Input validation helpers
            ------------------------- */
            // Check if any day-input in a table exceeds MAX_HOURS_PER_DAY
            function hasInvalidInputs($table) {
                let invalid = false;
                $table.find('.day-input').each(function() {
                    let num = parseFloat($(this).val());
                    let maxAllowed = $(this).hasClass('half-leave-day') ?
                        HALF_DAY_HOURS :
                        MAX_HOURS_PER_DAY;

                    if (num > maxAllowed) {
                        invalid = true;
                        return false;
                    }
                });
                return invalid;
            }

            /* -------------------------
            Event bindings
            ------------------------- */
            function bindEvents() {
                // Re-render panels when resources or month changes
                $resourceSelect.on('change', debounce(renderPanels, 300));
                $monthSelect.on('change', debounce(renderPanels, 300));

                // Save all allocations for selected resources
                $saveAllBtn.on('click', function() {
                    let selectedResources = $resourceSelect.val() || [];
                    if (selectedResources.length === 0) return;

                    // Validate per-resource tables first
                    for (let resourceId of selectedResources) {
                        let $table = $(`.allocation-table[data-resource="${resourceId}"]`);
                        if (hasInvalidInputs($table)) {
                            showNotification(
                                `Please enter a valid number between 0 and ${MAX_HOURS_PER_DAY} hours`,
                                'danger');
                            return false;
                        }
                    }

                    // Build mega payload for server
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
                                let dateStr =
                                    `${month}-${String(day).padStart(2,'0')}`;
                                daily_hours.push({
                                    date: dateStr,
                                    hours
                                });
                            });

                            allocations.push({
                                project_id: projectId,
                                daily_hours
                            });
                        });

                        megaData.push({
                            resource_id: resourceId,
                            month: month,
                            allocations
                        });
                    });

                    if (megaData.length === 0) {
                        showNotification('No allocations to save.', 'info');
                        return;
                    }

                    $(this).addClass('loading');

                    // Send mega-bulk save request
                    $.ajax({
                        url: "{{ route('capacity-planning.allocations.save.mega-bulk') }}",
                        method: "POST",
                        data: {
                            data: JSON.stringify(megaData),
                            _token: "{{ csrf_token() }}"
                        },
                        beforeSend: function() {
                            showLoader();
                        },
                        success: function(res) {
                            $saveAllBtn.removeClass('loading');
                            if (res.success) {
                                showNotification(res.message, 'success');
                                selectedResources.forEach(rid => updateResourceAllocation(rid));
                            } else {
                                showNotification(res.message || 'Error saving allocations',
                                    'danger');
                            }
                        },
                        error: function(xhr) {
                            $saveAllBtn.removeClass('loading');
                            let msg = 'Error saving allocations';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            showNotification(msg, 'danger');
                        },
                        complete: function() {
                            hideLoader();
                        }
                    });
                });
            }

            // Basic debounce utility to reduce event spam
            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    let later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            // Premium notification using SweetAlert2 (Dark Theme)
            function showNotification(message, type = 'info') {
                const toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: '#1e293b',
                    color: '#e5e7eb',
                    iconColor: type === 'success' ? '#10b981' : (type === 'danger' ? '#ef4444' : '#3b82f6'),
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });

                toast.fire({
                    icon: type === 'danger' ? 'error' : type,
                    title: message
                });
            }

            /* -------------------------
            Rendering: panels and grids
            ------------------------- */
            function renderPanels() {
                console.log("renderPanels", renderPanels);
                let selected = $resourceSelect.val() || [];
                let month = $monthSelect.val();

                let totalWorkingDays = workingDaysInMonth(month); // weekdays only
                let totalLeaves = 0;

                // Sum leave days across selected resources (resourceMeta contains per-resource leaveDays)
                selected.forEach(resourceId => {
                    let meta = resourceMeta[resourceId] || {};
                    totalLeaves += meta.leaveDays || 0;
                });

                // Count month-specific holidays (global list)
                let totalHolidays = holidays.filter(h =>
                    h.date.substring(0, 7) === month
                ).length;

                let adjustedDays = totalWorkingDays - totalHolidays;
                let adjustedHours = adjustedDays * HOURS_PER_DAY;

                $monthlyCapacityHours.text(
                    `${adjustedHours} hrs (${HOURS_PER_DAY} hrs/day, ${adjustedDays} working days)`);

                $panelsContainer.empty();
                $saveAllBtn.prop('disabled', selected.length === 0);

                console.log("objectselected.length", selected.length);
                if (!selected.length) {
                    $panelsContainer.html(
                        `<div class="text-center text-muted py-5"><i class="fas fa-users fa-3x mb-3 opacity-25"></i><h5>No Resources Selected</h5><p>Please select one or more resources to view their capacity planning.</p></div>`
                    );
                    return;
                }
                if (!month) {
                    $panelsContainer.html(
                        `<div class="text-center text-muted py-5"><i class="fas fa-calendar fa-3x mb-3 opacity-25"></i><h5>No Month Selected</h5><p>Please select a month to view capacity planning.</p></div>`
                    );
                    return;
                }

                selected.forEach((resourceId, index) => {
                    setTimeout(() => createResourcePanel(resourceId, month), index * 100);
                });
            }

            // Create panel for a single resource: loads assignments/leaves then builds grid
            function createResourcePanel(resourceId, month) {
                // Remove existing panel for this resource if present (refresh)
                $panelsContainer.find('section.panel[data-resource="' + resourceId + '"]').remove();

                let selectedProject = $projectFilter.val(); // get selected project
                let resourceName = $resourceSelect.find(`option[value="${resourceId}"]`).text();

                // Load assignments and leaves, then build UI
                loadAssignments(resourceId, month, function(assignmentsData, leavesData) {
                    // Normalize assignments: server may send object or array
                    assignmentsData = assignmentsData ?
                        Object.values(assignmentsData) : [];

                    // If a project is selected in filter, display only that project's row
                    if (selectedProject) {
                        assignmentsData = assignmentsData.filter(p => p.project_id == selectedProject);
                    }

                    // Map server object into frontend project rows expected structure
                    let projects = assignmentsData.map(p => ({
                        id: p.project_id,
                        name: p.name,
                        allocation_id: p.allocation_id,
                        allocations: p.allocations || {},
                        total_hours: p.total_hours || 0,
                        allocated_hours: p.allocated_hours || 0,
                        available_hours: p.available_hours || 0,
                        start_date: p.start_date,
                        end_date: p.end_date,
                        status: p.status || '',
                        priority: p.priority || ''
                    }));

                    // Leaves may come as array or structured object; keep both possibilities
                    let leaveDates = leavesData || [];
                    let leaveDays = leavesData.length;
                    let leaveHours = leaveDays * HOURS_PER_DAY;
                    let workingDays = workingDaysInMonth(month);

                    // Store metadata for this resource
                    resourceMeta[resourceId] = {
                        leaveDates,
                        leaveDays,
                        leaveHours,
                        workingDays
                    };

                    let resourceName = $resourceSelect.find(`option[value="${resourceId}"]`).text();

                    // Build panel HTML (summary + save button + allocation table)
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

                    // Per-resource save button handler: collects rows and daily inputs, posts to server
                    $panel.find('.save-resource-btn').on('click', function() {
                        let resourceId = $(this).data('resource');
                        let month = $monthSelect.val();
                        let $table = $(`.allocation-table[data-resource="${resourceId}"]`);

                        let allocations = []; // <-- Declare it here

                        // Collect all project rows and day inputs
                        $table.find('tr').each(function() {
                            let projectId = $(this).data('project-id');
                            if (!projectId) return;

                            let daily_hours = [];
                            $(this).find('.day-input').each(function() {
                                let day = $(this).data('day');
                                let val = parseFloat($(this).val()) || 0;
                                let dateStr =
                                    `${month}-${String(day).padStart(2,'0')}`;
                                daily_hours.push({
                                    date: dateStr,
                                    hours: val
                                });
                            });

                            allocations.push({
                                project_id: projectId,
                                daily_hours
                            });
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
                            beforeSend: function() {
                                showLoader();
                            },
                            success: function(res) {
                                if (res.success) {
                                    showNotification(
                                        `Allocations saved for ${resourceName}`,
                                        'success');
                                    updateResourceAllocation(
                                        resourceId); // refresh allocation info
                                } else {
                                    showNotification(res.message ||
                                        'Error saving allocations', 'danger');
                                }
                            },
                            error: function(xhr) {
                                let msg = 'Error saving allocations';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }
                                showNotification(msg, 'danger');
                            },
                            complete: function() {
                                hideLoader();
                            }
                        });
                    });

                    // Build grid rows and header based on projects and month days
                    buildAllocationGrid(projects, month, $panel.find('.allocation-table'), resourceId,
                        leavesData);
                    updateResourceAllocation(resourceId);
                });
            }

            // Build table header and rows for allocation grid
            function buildAllocationGrid(projects, yyyyMM, $table, resourceId, leavesData) {
                let days = daysInMonth(yyyyMM);
                let [year, month] = yyyyMM.split('-').map(Number);

                // Table header: Project + one column per day with weekday label
                let $thead = $('<thead></thead>');
                let $headRow = $('<tr></tr>');
                $headRow.append('<th>Project / Tools</th>');
                for (let day = 1; day <= days; day++) {
                    let isWeekendDay = isWeekend(year, month, day);
                    let dayName = new Date(year, month - 1, day).toLocaleDateString('en', {
                        weekday: 'short'
                    });
                    $headRow.append(
                        `<th class="${isWeekendDay?'weekend-column':''}" title="${dayName}">${day}<div style="font-size:0.7rem;opacity:0.7">${dayName}</div></th>`
                    );
                }
                $thead.append($headRow);

                // Body: one row per project
                let $tbody = $('<tbody></tbody>');
                projects.forEach(project => $tbody.append(createProjectRow(project, year, month, days, resourceId,
                    leavesData)));
                $table.empty().append($thead, $tbody);
                updateResourceAllocation(resourceId);
            }

            // Create a single project row with tools (apply-all, clear, delete) and day-inputs
            function createProjectRow(project, year, month, days, resourceId, leavesData) {
                let leaveInfo = leavesData?.leaves || [];

                function isFullDayLeave(dateStr, leaveInfo) {
                    return leaveInfo.some(l =>
                        l.number_of_days >= 1 &&
                        l.dates.includes(dateStr)
                    );
                }

                // Build row root and header cell (project title + tools)
                // Store project start/end dates
                let $row = $('<tr></tr>')
                    .data('project-id', project.id)
                    .data('allocation-id', project.allocation_id || null)
                    .data('start-date', project.start_date)
                    .data('end-date', project.end_date)
                    .data('total-allowed', project.total_hours);

                let $headerCell = $(`
                                <td class="project-cell">
                                    <div class="project-header">
                                        <div class="project-title" title="Limit: ${project.total_hours} hrs | ${project.start_date} to ${project.end_date}">
                                            ${project.name}
                                        </div>
                                        <div class="project-tools">
                                            <input type="number" class="form-control tool-input apply-all-input" placeholder="hrs" min="0" max="${MAX_HOURS_PER_DAY}" step="0.5">

                                            <select class="form-select tool-week-select">
                                                <option value="all">All</option>
                                                <option value="1">Week1</option>
                                                <option value="2">Week2</option>
                                                <option value="3">Week3</option>
                                                <option value="4">Week4</option>
                                                <option value="5">Week5</option>
                                            </select>
                                            <div class="tool-actions">
                                                <button class="btn btn-outline-light btn-sm apply-all-btn" title="Apply hours">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm clear-row-btn" title="Clear row">
                                                    <i class="fas fa-eraser"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm delete-row-btn" title="Delete project">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            `);

                $row.append($headerCell);

                let dayInputs = [];
                // leaveDates may be a simple array of date strings
                let leaveDates = leavesData?.dates || [];

                // Calculate first day of the month (Mon=1, Tue=2, ..., Sun=7)
                let firstDayDate = new Date(year, month - 1, 1).getDay();
                let adjFirstDay = firstDayDate === 0 ? 7 : firstDayDate;

                // Create day cells with inputs and adjust disabled states (weekend/leave/holiday)
                for (let day = 1; day <= days; day++) {
                    let $cell = $(`<td></td>`);
                    let dateStr = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                    let existingVal = project.allocations && project.allocations[dateStr] != null ? project
                        .allocations[dateStr] : '';

                    // Calendar-based week calculation (starting on Monday)
                    let weekNo = Math.ceil((day + adjFirstDay - 1) / 7);

                    let $input = $(`<input type="number" class="form-control day-input"
                                min="0" max="${MAX_HOURS_PER_DAY}" step="0.5"
                                data-day="${day}"
                                data-week="${weekNo}"
                                data-resource="${resourceId}"
                                value="${existingVal}">`);

                    // Check project date range (project.start_date, project.end_date)
                    let isOutOfRange = false;
                    if (project.start_date && project.end_date) {
                        // dateStr is 'YYYY-MM-DD'
                        if (dateStr < project.start_date || dateStr > project.end_date) {
                            isOutOfRange = true;
                        }
                    }

                    // Weekend -> disabled
                    if (isWeekend(year, month, day)) {
                        $input.prop('disabled', true)
                            .addClass('day-off')
                            .attr('title', 'Weekend - Not available');

                    } else if (isOutOfRange) {
                        // Out of project scope -> disabled
                        $input.prop('disabled', true)
                            .addClass('out-of-range')
                            .attr('title',
                                `Date outside project duration (${project.start_date} to ${project.end_date})`);

                    } else if (isFullDayLeave(dateStr, leaveInfo)) {
                        // Full-day leave -> disabled
                        $input.prop('disabled', true)
                            .addClass('leave-day')
                            .attr('title', 'Full Day Leave - Not available');

                    } else if (leaveDates.includes(dateStr)) {
                        // Half-day leave -> enabled but marked
                        $input.prop('disabled', false)
                            .addClass('half-leave-day')
                            .attr('max', HALF_DAY_HOURS)
                            .attr('title', 'Half Day Leave');

                    } else if (holidays.some(h => h.date === dateStr)) {
                        // Company holiday -> disabled
                        $input.prop('disabled', true)
                            .addClass('holiday-day')
                            .attr('title', 'Holiday - Not available');

                    } else {
                        $input.attr('title', `Hours for day ${day}`);
                    }

                    $cell.append($input);
                    $row.append($cell);
                    dayInputs.push($input);
                }

                // Attach events to tools and inputs for this row
                bindProjectRowEvents($row, dayInputs, resourceId);
                return $row;
            }

            /* -------------------------
            Row-level event bindings
            ------------------------- */
            function bindProjectRowEvents($row, dayInputs, resourceId) {
                let $applyAllInput = $row.find('.apply-all-input');
                let $applyAllBtn = $row.find('.apply-all-btn');
                let $clearBtn = $row.find('.clear-row-btn');
                let $deleteBtn = $row.find('.delete-row-btn');

                // Apply value to all enabled inputs in row and save to server
                $applyAllBtn.on('click', function() {
                    let value = parseFloat($applyAllInput.val());
                    let selectedWeek = $row.find('.tool-week-select').val();

                    if (isNaN(value) || value < 0 || value > MAX_HOURS_PER_DAY) {
                        showNotification(
                            `Please enter a valid number between 0 and ${MAX_HOURS_PER_DAY} hours`,
                            'danger'
                        );
                        return;
                    }

                    // --- Max Hours Cap Logic & Date Range/Week Logic ---
                    let totalAllowed = parseFloat($row.data('total-allowed')) || 0;
                    // (Optional: check total allocated vs allowed here if we had full data)

                    let modifiedCount = 0;
                    let daily_hours = [];
                    let month = $('#monthSelect').val();
                    let projectId = $row.data('project-id');
                    let halfDaySkipped = false;
                    let halfDayApplied = false;

                    dayInputs.forEach($input => {
                        // Skip if disabled (weekend, holiday, full leave, out-of-range)
                        if ($input.is(':disabled')) return;

                        // Check Week Filter
                        let weekNo = $input.data('week'); // 1..5
                        if (selectedWeek !== 'all' && String(weekNo) !== String(
                                selectedWeek)) {
                            return; // Skip if day is not in selected week
                        }

                        // Handle Max per day (e.g. half-day leave)
                        let maxForDay = parseFloat($input.attr('max')) ||
                            MAX_HOURS_PER_DAY;
                        let finalVal = value;

                        if (value > maxForDay) {
                            // Skip setting if value exceeds max for that specific day (e.g. half-day leave)
                            halfDaySkipped = true;
                            return;
                        }

                        if ($input.hasClass('half-leave-day')) halfDayApplied = true;

                        $input.val(finalVal);
                        let day = $input.data('day');
                        let dateStr = `${month}-${String(day).padStart(2,'0')}`;
                        daily_hours.push({
                            date: dateStr,
                            hours: finalVal
                        });
                        modifiedCount++;
                    });

                    if (modifiedCount === 0) {
                        let msg =
                            'No applicable days found. Check date range, weekends, or week filter.';
                        if (halfDaySkipped) msg +=
                            ' (Some days skipped due to half-day leave limits)';
                        showNotification(msg, 'warning');
                        return;
                    }

                    // Save to server
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
                        beforeSend: showLoader,
                        success: function(res) {
                            if (res.success) {
                                let msg =
                                    `Applied ${value} hours to ${modifiedCount} days.`;
                                if (halfDaySkipped && !halfDayApplied) {
                                    msg += ' Half-day leave dates were excluded.';
                                } else if (halfDaySkipped && halfDayApplied) {
                                    msg += ' Some half-day dates were excluded.';
                                }
                                showNotification(msg, 'success');
                                updateResourceAllocation(resourceId);
                            } else {
                                showNotification(res.message ||
                                    'Error saving allocations',
                                    'danger');
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Error saving allocations';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            showNotification(msg, 'danger');
                        },
                        complete: hideLoader
                    });
                });

                // Validate per-input numeric entry (0..MAX_HOURS_PER_DAY)
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

                        // Accept numeric with optional decimal
                        if (/^\d+(\.\d+)?$/.test(val)) {
                            let num = parseFloat(val);
                            // let num = parseInt(val, 10);
                            // if (num >= 0 && num <= MAX_HOURS_PER_DAY) {
                            if (num >= 0 && num <= getMaxAllowedHours($this)) {
                                oldVal = val;
                                $this.removeClass('invalid-input');
                                return;
                            }
                        }

                        // Invalid → show notification and revert after delay
                        $this.addClass('invalid-input');
                        // showNotification(`Please enter a valid number between 0 and ${MAX_HOURS_PER_DAY} hours`, 'danger');
                        let maxAllowed = getMaxAllowedHours($this);
                        showNotification(
                            `Please enter a valid number between 0 and ${maxAllowed} hours`,
                            'danger'
                        );

                        clearTimeout($this.data('timeoutId'));
                        let timeoutId = setTimeout(() => {
                            $this.val(oldVal);
                            $this.removeClass('invalid-input');
                        }, 2000);

                        $this.data('timeoutId', timeoutId);
                    });
                });

                // Clear row inputs
                $clearBtn.on('click', function() {
                    dayInputs.forEach($input => $input.val(''));
                    $applyAllInput.val('');
                    updateResourceAllocation(resourceId);
                    showNotification('Row cleared', 'success');
                });

                // Delete allocation row (server call)
                $deleteBtn.on('click', function() {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you want to delete this project allocation?",
                        icon: 'warning',
                        background: '#1e293b',
                        color: '#e5e7eb',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#334155',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let allocationId = $row.data(
                                'allocation-id'); // make sure you set this in HTML
                            let url = "{{ route('capacity-planning.allocations.delete', ':id') }}"
                                .replace(
                                    ':id', allocationId);

                            $.ajax({
                                url: url,
                                method: "DELETE",
                                data: {
                                    _token: "{{ csrf_token() }}"
                                },
                                beforeSend: function() {
                                    showLoader();
                                },
                                success: function(res) {
                                    if (res.success) {
                                        $row.fadeOut(300, function() {
                                            $row.remove();
                                            updateResourceAllocation(
                                                resourceId);
                                        });
                                        showNotification(res.message, 'success');
                                    } else {
                                        showNotification(res.message || 'Delete failed',
                                            'danger');
                                    }
                                },
                                error: function() {
                                    showNotification('Error deleting allocation',
                                        'danger');
                                },
                                complete: function() {
                                    hideLoader();
                                }
                            });
                        }
                    });
                });

                // Pressing Enter in the apply-all input triggers apply action
                $applyAllInput.on('keypress', function(e) {
                    if (e.which === 13) { // Enter key
                        let value = parseFloat($applyAllInput.val());

                        // Check if value is a number and within 0-8
                        if (isNaN(value) || value < 0 || value > MAX_HOURS_PER_DAY) {
                            showNotification(
                                `Please enter a valid number between 0 and ${MAX_HOURS_PER_DAY} hours`,
                                'danger');
                            $applyAllInput.focus().select();
                            return;
                        }

                        // EXTRA half-day check
                        let hasHalfDay = dayInputs.some($input =>
                            !$input.prop('disabled') && $input.hasClass('half-leave-day')
                        );

                        if (hasHalfDay && value > HALF_DAY_HOURS) {
                            showNotification(
                                `Half-day leave detected. Max allowed is ${HALF_DAY_HOURS} hours`,
                                'danger'
                            );
                            return;
                        }

                        $applyAllBtn.click(); // valid, trigger apply
                    }
                });
            }

            /* -------------------------
            Summary calculation: updates allocation info area for a resource
            ------------------------- */
            function updateResourceAllocation(resourceId) {
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

                let $table = $(`.allocation-table[data-resource="${resourceId}"]`);
                let $allocationInfo = $(`.allocation-info[data-resource="${resourceId}"]`);

                let month = $('#monthSelect').val();
                let totalAllocated = 0;
                let leaveHoursCount = 0;
                let leaveDaysCount = leaveDates.length; // unique leave days

                // Iterate all day inputs and sum valid values, clearing disabled days
                $table.find('input.day-input').each(function() {
                    let $input = $(this);
                    let day = $input.data('day');
                    let dateStr = `${month}-${String(day).padStart(2,'0')}`;

                    // If holiday -> clear and skip
                    if (holidays.some(h => h.date === dateStr)) {
                        $input.val('');
                        return;
                    }

                    // Full day leave -> clear input
                    if (isFullDayLeave(dateStr, leaveInfo)) {
                        $input.val('');
                        return;
                    }

                    // Parse numeric value, guard range
                    let val = parseFloat($input.val());
                    let maxAllowed = getMaxAllowedHours($input);
                    if (isNaN(val) || val < 0 || val > maxAllowed) val = 0;
                    $input.val(val); // force valid value in UI
                    totalAllocated += val;
                });

                // Compute holiday and leave hours for effective capacity
                let resourceHolidays = holidays.filter(h => {
                    let d = h.date.substring(0, 7);
                    return d === month;
                });

                let holidayDays = resourceHolidays.length;
                let holidayHours = holidayDays * HOURS_PER_DAY;

                // leaveDays supplied via meta, if available
                let leaveDays = meta.leaveDates?.total_leave_days || 0;
                let leaveHours = leaveDays * HOURS_PER_DAY;

                // Base monthly capacity (precomputed MAX_HOURS_MONTH)
                let baseCapacity = MAX_HOURS_MONTH;

                // Effective capacity after subtracting leave and holiday hours
                let effectiveCapacity = Math.max(
                    0,
                    baseCapacity - leaveHours - holidayHours
                );

                // Compute available hours and utilization %
                let available = Math.max(0, effectiveCapacity - totalAllocated);
                let utilizationPercent = effectiveCapacity > 0 ?
                    Math.round((totalAllocated / effectiveCapacity) * 100) :
                    0;

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

            // Returns CSS class for status indicator based on allocated hours
            function getStatusClass(allocated) {
                if (allocated > MAX_HOURS_MONTH) return 'status-overallocated';
                if (allocated > MAX_HOURS_MONTH * 0.8) return 'status-warning';
                return 'status-available';
            }

            /* -------------------------
            Initialization calls
            ------------------------- */
            initMonth();
            loadResources();
            bindEvents();
            loadHolidays();
            renderPanels();

            /* ---------------------------------------------------------------------
            Project modal: Add / Edit project logic and validation
            --------------------------------------------------------------------- */

            // Elements for Add Project date logic
            let startDateInput = document.getElementById('addStartDate');
            let endDateInput = document.getElementById('addEndDate');

            // Today's date string (used for min restrictions)
            let today = new Date();
            let yyyy = today.getFullYear();
            let mm = String(today.getMonth() + 1).padStart(2, '0');
            let dd = String(today.getDate()).padStart(2, '0');
            let todayStr = `${yyyy}-${mm}-${dd}`;

            // Allow past dates for start date; enforce min on end date (cannot be earlier than today)
            endDateInput.min = todayStr; // End date still cannot be before today

            // When start date changes, update end date min and reset end date if invalid
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

            // Edit project date inputs (similar logic)
            let editStartDateInput = document.getElementById('editStartDate');
            let editEndDateInput = document.getElementById('editEndDate');

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

            // Initialize select2 for modal fields (project manager, resources, status/priority)
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

            // Fix select2 dropdown positioning when resource selects change (close & reopen trick)
            $('#addResourceSelect, #editResourceSelect, #resourceSelect').on(
                'select2:select select2:unselect',
                function(e) {
                    setTimeout(function() {
                        $(e.target).select2('close');
                        $(e.target).select2('open');
                    }, 10);
                });

            // Trigger validation on keyup/change for project modal inputs (jQuery Validate used below)
            $('#projectName, #totalHours, #descriptionPro').on('keyup', function() {
                $(this).valid();
            });
            $(
                    '#addStartDate, #addEndDate, #addManagerSelect, #addResourceSelect, #statusofProject, #priorityOfProject'
                )
                .on('change', function() {
                    $(this).valid();
                });

            // Edit Project Validation on keyup ----
            $('#editProjetName, #editTotalHours, #editDescription').on('keyup', function() {
                $(this).valid();
            });
            $(
                    '#editStartDate, #editEndDate, #editManagerSelect, #editResourceSelect, #editStatusofProject, #editPriorityOfProject'
                )
                .on('change', function() {
                    $(this).valid();
                });

            // This closes & reopens the dropdown so it recalculates position based on new height.
            $('#resourceSelect').on('select2:select select2:unselect', function(e) {
                // Delay a bit so Select2 can finish DOM changes
                setTimeout(function() {
                    $('#resourceSelect').select2(
                        'close'); // Close and re-open to fix position
                    $('#resourceSelect').select2('open');
                }, 10);
            });

            /* Inside set placeholder in search dropdown select2 */
            $('#addManagerSelect').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Select project manager');
            });

            /* Inside set placeholder in search dropdown select2 */
            $('#editManagerSelect').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Select project manager');
            });

            /* Inside set placeholder in search dropdown select2 */
            $('#statusofProject').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Select status');
            });

            /* Inside set placeholder in search dropdown select2 */
            $('#priorityOfProject').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Select priority');
            });

            /* Inside set placeholder in search dropdown select2 */
            $('#editStatusofProject').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Select status');
            });

            /* Inside set placeholder in search dropdown select2 */
            $('#editPriorityOfProject').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Select priority');
            });

            // Add/Edit Project modal open: reset forms and show initial step
            $('#addEditProjectBtn').on('click', function() {
                $('#addManagerSelect, #addResourceSelect, #statusofProject, #priorityOfProject, #editProjectSelect, #editManagerSelect, #editResourceSelect, #editStatusofProject, #editPriorityOfProject')
                    .val(null).trigger('change');

                $('#chooseActionStep').removeClass('d-none');
                $('#addProjectForm').addClass('d-none');
                $('#editProjectForm').addClass('d-none');
                $('#editProjectDropdownContainer').addClass('d-none');
                $('#modalTitle').text('Add/Edit Project');
                $('#projectModal').modal('show');
                $('#footerDiv').addClass('d-none');
            });

            // 'Choose Add' -> show add form
            $('#chooseAddBtn').on('click', function() {
                $('#chooseActionStep').addClass('d-none');
                $('#addProjectForm').removeClass('d-none');
                $('#editProjectDropdownContainer').addClass('d-none');
                $('#modalTitle').text('Add Project');
                $('#addProjectForm')[0].reset();
                $('#managerSelect, #resourceSelect').val(null).trigger('change');

                $('#addProjectForm .form-scrollable').animate({
                    scrollTop: 0
                }, 'slow');
                $('#footerDiv').removeClass('d-none');
            });

            // 'Choose Edit' -> show edit dropdown first
            $('#chooseEditBtn').on('click', function() {
                $('#chooseActionStep').addClass('d-none');
                $('#editProjectForm').addClass('d-none');
                $('#editProjectDropdownContainer').removeClass('d-none');
                $('#modalTitle').text('Edit Project');
                $('#editProjectForm')[0].reset();
                $('#managerSelect, #resourceSelect').val(null).trigger('change');
                $('#footerDiv').addClass('d-none');
            });

            // When a project is selected to edit, fetch its details and populate form
            $('#editProjectSelect').on('change', function() {
                $('#editProjectForm').removeClass('d-none');
                $('#editFooterDiv').removeClass('d-none');
                $('#editProjectDropdownContainer').addClass('d-none');

                $('#editProjectForm .form-scrollable').animate({
                    scrollTop: 0
                }, 'slow');

                let projectId = $(this).val();
                if (!projectId) return;

                $.ajax({
                    url: `/projects/${projectId}/edit`,
                    method: 'GET',
                    beforeSend: function() {
                        showLoader();
                    },
                    success: function(data) {
                        let form = $('#editProjectForm');
                        form.attr('data-project-id', data.id);
                        form.find('input[name="name"]').val(data.name);
                        form.find('input[name="total_hours"]').val(data.total_hours);
                        form.find('textarea[name="description"]').val(data.description);
                        form.find('input[name="start_date"]').val(data.start_date);
                        form.find('input[name="end_date"]').val(data.end_date);
                        form.find('select[name="project_manager_id"]').val(data
                            .project_manager_id).trigger('change');
                        form.find('select[name="resource_ids[]"]').val(data
                                .resource_ids)
                            .trigger('change');
                        form.find('select[name="status"]').val(data.status).trigger(
                            'change');
                        form.find('select[name="priority"]').val(data.priority).trigger(
                            'change');
                        form.find('input[name="is_billable"]').prop('checked', data
                            .is_billable);
                    },
                    error: function() {
                        showNotification('Error fetching project details', 'danger');
                    },
                    complete: function() {
                        hideLoader();
                    }
                });
            });

            // jQuery Validation rules for Add Project form
            $("#addProjectForm").validate({
                errorElement: "span",
                errorClass: "error",
                highlight: function(element) {
                    $(element).closest(".form-group").addClass("has-error");
                },
                unhighlight: function(element) {
                    $(element).closest(".form-group").removeClass("has-error");
                },
                errorPlacement: function(error, element) {
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

                    let formData = new FormData(form);

                    // normalize checkbox
                    formData.set('is_billable', $('#addProjectForm input[name="is_billable"]')
                        .is(
                            ':checked') ? 1 : 0);

                    // Submit AJAX
                    $.ajax({
                        url: "{{ route('projects.store') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function() {
                            showLoader();
                        },
                        success: function(response) {
                            if (response.success) {
                                // swal("Success", response.message, "success");
                                showNotification(response.message, 'success');

                                $('#projectModal').modal('hide');
                                form.reset();
                                $('#addManagerSelect, #addResourceSelect').val(null)
                                    .trigger('change');
                                // optionally reload or refresh project list
                            } else {
                                // swal("Error", response.message || "Something went wrong", "error");
                                showNotification(response.message ||
                                    "Something went wrong",
                                    'danger');
                            }
                        },
                        error: function(xhr) {
                            let errorMsg = "Something went wrong!";
                            if (xhr.responseJSON?.errors) {
                                errorMsg = Object.values(xhr.responseJSON.errors)
                                    .flat()
                                    .join("\n");
                            }
                            // swal("Error", errorMsg, "error");
                            showNotification(errorMsg || "Something went wrong",
                                'danger');
                        },
                        complete: function() {
                            hideLoader();
                        }
                    });
                }
            });

            // jQuery Validation for Edit Project form (similar rules)
            $("#editProjectForm").validate({
                errorElement: "span",
                errorClass: "error",
                highlight: function(element) {
                    $(element).closest(".form-group").addClass("has-error");
                },
                unhighlight: function(element) {
                    $(element).closest(".form-group").removeClass("has-error");
                },
                errorPlacement: function(error, element) {
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
                    formData.set('is_billable', $('#editProjectForm input[name="is_billable"]')
                        .is(
                            ':checked') ? 1 : 0);

                    $.ajax({
                        url: `/projects/${projectId}`,
                        method: 'POST', // or 'PUT' depending on your route
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function() {
                            showLoader();
                        },
                        success: function(response) {
                            if (response.success) {
                                showNotification(response.message, 'success');
                                $('#editProjectForm')[0].reset();
                                $('#editProjectForm').addClass('d-none');
                                $('#editProjectDropdownContainer').removeClass(
                                    'd-none');
                                $('#projectModal').modal('hide');
                            }
                        },
                        error: function(xhr) {
                            let msg = "Something went wrong while updating the project";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join(
                                    "\n");
                            }
                            showNotification(msg, 'danger');
                        },
                        complete: function() {
                            hideLoader();
                        }
                    });
                }
            });

            // Reset forms and select2 fields when project modal closes
            $('#projectModal').on('hidden.bs.modal', function() {
                // Reset the form
                $('#addProjectForm')[0].reset();
                $('#editProjectForm')[0].reset();

                $("#addProjectForm").data('validator').resetForm();
                $("#editProjectForm").data('validator').resetForm();

                // Reset Select2 fields
                $('#addManagerSelect, #addResourceSelect, #statusofProject, #priorityOfProject, #editProjectSelect, #editManagerSelect, #editResourceSelect, #editStatusofProject, #editPriorityOfProject')
                    .val(null).trigger('change');

            });

            /* ---------------------------------------------------------------------
            Holiday management: load, render, add/edit/delete with smooth UI
            --------------------------------------------------------------------- */
            // Flatpickr instance for holiday date range
            const flatpickrInstance = flatpickr("#dateRange", {
                mode: "range",
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function(selectedDates, dateStr) {
                    $('#dateError').hide();
                    $('input[name="date_range"]').valid();
                }
            });

            // Load holidays from server and render the list
            function loadHolidays() {
                $.get('/holidays', res => {
                    holidays = res || [];
                    renderHolidays();

                    $('#holidayForm').validate().resetForm();
                }).fail(() => {
                    showNotification("Failed to load holidays", "danger");
                });
            }

            // Render holidays list in the holiday modal
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
                return d.toLocaleDateString('en-US', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }

            // Holiday form validation using jQuery Validate
            $("#holidayForm").validate({
                errorElement: "span",
                errorClass: "error",
                // validate on input/keyup/change
                onkeyup: function(element) {
                    $(element).valid();
                },
                onfocusout: function(element) {
                    $(element).valid();
                },
                highlight: function(element) {
                    $(element).addClass("is-invalid");
                    $(element).closest(".form-group").addClass("has-error");
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid");
                    $(element).closest(".form-group").removeClass("has-error");
                },
                errorPlacement: function(error, element) {
                    // For select2 or custom inputs you might want to place differently
                    if (element.hasClass('select-search')) {
                        error.insertAfter(element.next('.select2-container'));
                    } else {
                        error.insertAfter(element);
                    }
                },
                rules: {
                    date_range: {
                        required: true
                    },
                    description: {
                        required: true,
                        minlength: 2,
                        maxlength: 500
                    }
                },
                messages: {
                    date_range: {
                        required: "Please select a date range"
                    },
                    description: {
                        required: "Please enter a description",
                        minlength: "At least 2 characters required",
                        maxlength: "Maximum 500 characters allowed"
                    }
                },
                submitHandler: function(form, event) {
                    event.preventDefault(); // keep default prevented
                    saveHoliday(); // call your save function
                }
            });

            // Save or update holiday; editingId used to determine update vs create
            let editingId = null;

            function saveHoliday() {
                const dateRange = $('#dateRange').val();
                const desc = $('#description').val().trim();

                let data = {
                    description: desc
                };

                if (editingId) {
                    data.date = dateRange; // send as 'date', not 'date_range'
                    $.ajax({
                        url: `/holidays/${editingId}`,
                        type: 'POST',
                        data: data,
                        beforeSend: () => {
                            // Optionally show loader
                            showLoader();
                        },
                        success: (response) => {
                            editingId = null;
                            $('#btnText').text('Add Holiday');
                            $('#holidayForm')[0].reset();
                            $('#holidayForm').validate().resetForm();
                            flatpickrInstance.clear();
                            loadHolidays();
                            // $('#holidayModal').modal('hide');
                            showNotification(response.message ||
                                "Holiday updated successfully",
                                'success');
                        },
                        error: (err) => {
                            showNotification(err.responseJSON?.message ||
                                "Failed to update holiday",
                                'danger');
                        },
                        complete: () => {
                            hideLoader();
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
                            showNotification(response.message || "Holiday added successfully",
                                'success');
                        })
                        .fail(err => {
                            showNotification(err.responseJSON?.message || "Failed to save holiday",
                                'danger');
                        });
                }
            }

            // Edit holiday button opens modal populated with existing data
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

            // Delete holiday with smooth fade out UI
            $('#holidaysList').on('click', '.holidays-btn-delete', function() {
                const id = $(this).data('id');
                const item = $(this).closest('.holidays-item');

                $.ajax({
                    url: `/holidays/${id}`,
                    type: 'DELETE',
                    beforeSend: () => {
                        // Optionally show loader
                        showLoader();
                    },
                    success: (response) => {
                        // Smooth fade-out removal
                        item.fadeOut(400, function() {
                            $(this).remove();
                            if (!$('#holidaysList').children().length) {
                                $('#holidaysList').html(
                                    '<div class="holidays-empty-state">No holidays added yet</div>'
                                );
                            }
                        });
                        showNotification(response.message ||
                            "Holiday deleted successfully",
                            'success');
                    },
                    error: (err) => {
                        showNotification(err.responseJSON?.message ||
                            "Failed to delete holiday", 'danger');
                    },
                    complete: () => {
                        hideLoader();
                    }
                });
            });

            // Reset holiday form when modal hides
            $('#holidayModal').on('hidden.bs.modal', function() {
                $('#holidayForm')[0].reset();
                $('#btnText').text('Add Holiday');
                $("#holidayForm").data('validator').resetForm();
            });

            // Initial load of holidays
            loadHolidays();


            /* ---------------------------------------------------------------------
            Resource modal: init select2, add/edit logic and validation
            --------------------------------------------------------------------- */
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

            // Placeholders for select2 on open
            $(
                    '#manage-resource-editSelect, #manage-resource-deptSelect, #manage-resource-statusSelect, #manage-resource-editDept, #manage-resource-editStatus'
                )
                .on('select2:open', function() {
                    let searchBox = $('.select2-container--open .select2-search__field');
                    searchBox.attr('placeholder', $(this).data('placeholder') || 'Search');
                });

            // Simple input validation triggers for resource forms
            $(
                    '#manage-resource-editName, #manage-resource-nameInput, #manage-resource-editEmail, #manage-resource-emailInput, #manage-resource-role, #manage-resource-editRole, #manage-resource-editCapacity, #manage-resource-daily-capacity, #manage-resource-total-hours, #manage-resource-editTotalHours, #manage-resource-leave-hours, #manage-resource-editLeaveHours'
                )
                .on('keyup', function() {
                    $(this).valid();
                });

            /* Show add resource form in modal */
            $('#manageResourceAddBtn').on('click', function() {

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
                $('.manage-resource-scroll').animate({
                    scrollTop: 0
                }, 'slow');
            });

            /* Show edit resource dropdown in modal */
            $('#manageResourceEditBtn').on('click', function() {

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

            /* On selecting a resource to edit, fetch details and populate edit form */
            $('#manage-resource-editSelect').on('change', function() {

                let id = $(this).val();

                if (!id) {
                    $('#manage-resource-editForm').addClass('d-none');
                    return;
                }

                // Show Edit Form
                $('#manage-resource-editForm').removeClass('d-none');

                // AJAX fetch resource details
                $.ajax({
                    url: "/resources/" +
                        id, // <-- Your GET route must return resource details
                    method: "GET",
                    beforeSend: function() {
                        showLoader();
                    },
                    success: function(response) {

                        let data = response.data;

                        // Show dropdown
                        $('#manage-resource-editDropdownContainer').addClass('d-none');

                        // Fill values
                        $('#manage-resource-editName').val(data.name);
                        $('#manage-resource-editEmail').val(data.email);
                        $('#manage-resource-editDept').val(data.dept_id).trigger(
                            'change');
                        $('#manage-resource-editRole').val(data.role);
                        $('#manage-resource-editCapacity').val(data.daily_capacity);
                        $('#manage-resource-editTotalHours').val(data.total_hours);
                        $('#manage-resource-editLeaveHours').val(data.leave_hours);
                        $('#manage-resource-editStatus').val(data.status).trigger(
                            'change');

                        $('#manage-resource-isManagerEdit').prop('checked', data
                            .is_project_manager == 1);

                        // Scroll top
                        $('#manage-resource-editForm .manage-resource-scroll').animate({
                            scrollTop: 0
                        }, 'slow');
                    },
                    error: function() {
                        showNotification("Failed to fetch resource details", "danger");
                    },
                    complete: function() {
                        hideLoader();
                    }
                });
            });

            // Reset resource modal on close
            $('#manageResourceModal').on('hidden.bs.modal', function() {
                // Reset everything back
                $('#manage-resource-chooseAction').removeClass('d-none');

                $('#manage-resource-addForm').addClass('d-none');
                $('#manage-resource-editForm').addClass('d-none');
                $('#manage-resource-editDropdownContainer').addClass('d-none');

                // Reset title
                $('#manageResourceModalTitle').text('Add / Edit Resource');

                // Reset all forms and selects
                $('form').each(function() {
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

            // Reset on show as well (ensures clean state)
            $('#manageResourceModal').on('show.bs.modal', function() {
                // Reset all forms and selects
                $('form').each(function() {
                    this.reset();
                })

                $('.select-search').val(null).trigger('change');

                // Reset form validation errors
                setTimeout(() => {
                    $('#manage-resource-addForm').validate().resetForm();
                    $('#manage-resource-editForm').validate().resetForm();
                }, 1500);
            });

            /* Custom jQuery Validate methods for resources */
            // Letters & spaces only (no special characters, no numbers)
            $.validator.addMethod("lettersOnly", function(value, element) {
                return this.optional(element) || /^[a-zA-Z\s]+$/.test(value);
            }, "Only letters and spaces are allowed");

            // Integer only
            $.validator.addMethod("integerOnly", function(value, element) {
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

            $('#manage-resource-deptSelect').on('change', function() {
                $('#manage-resource-deptSelect').valid(); // trigger validation on select change
            });

            $('#manage-resource-editDept').on('change', function() {
                $('#manage-resource-editDept').valid(); // trigger validation on select change
            });

            // Add resource form validation & AJAX submit
            // Initialize jQuery Validation for Add resource
            $("#manage-resource-addForm").validate({
                errorElement: "span",
                errorClass: "error",

                highlight: function(element) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid");
                },

                errorPlacement: function(error, element) {
                    if (element.hasClass('select-search')) {
                        error.insertAfter(element.next('.select2'));
                    } else {
                        error.insertAfter(element);
                    }
                },

                rules: resourceValidationRules,
                messages: resourceValidationMessages,

                submitHandler: function(form) {
                    let formData = new FormData(form);

                    $.ajax({
                        url: "/resources",
                        method: "POST",
                        data: formData,
                        contentType: false,
                        processData: false,

                        beforeSend: function() {
                            $('#manage-resource-addForm button[type="submit"]')
                                .prop(
                                    'disabled', true).text("Saving...");
                            $('.error-msg').remove(); // remove old errors
                            showLoader();
                        },
                        success: function(response) {
                            showNotification("Resource added successfully!",
                                'success');

                            $('#manageResourceModal').modal('hide');

                            // Optional: Refresh table or page
                            if (typeof refreshResourceTable === "function") {
                                refreshResourceTable();
                            }
                        },
                        error: function(xhr) {
                            let msg = "Failed to add resource";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join(
                                    "\n");
                            }
                            showNotification(msg, "danger");
                        },
                        complete: function() {
                            $('#manage-resource-addForm button[type="submit"]')
                                .prop(
                                    'disabled', false).html(
                                    '<i class="fas fa-save me-2"></i> Save');
                            hideLoader();
                        }
                    });
                }
            });

            // Update resource form validation & AJAX submit
            $("#manage-resource-editForm").validate({
                errorElement: "span",
                errorClass: "error",

                highlight: function(element) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid");
                },

                errorPlacement: function(error, element) {
                    if (element.hasClass('select-search')) {
                        error.insertAfter(element.next('.select2'));
                    } else {
                        error.insertAfter(element);
                    }
                },

                rules: resourceValidationRules,
                messages: resourceValidationMessages,

                submitHandler: function(form) {
                    let id = $('#manage-resource-editSelect').val();
                    let formData = new FormData(form);
                    formData.append('_method', 'PUT');

                    $.ajax({
                        url: "/resources/" + id,
                        method: "POST", // Laravel PUT via method spoofing
                        data: formData,
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            $('#manage-resource-editForm button[type="submit"]')
                                .prop(
                                    'disabled', true).text("Updating...");
                            $('.error-msg').remove();
                            showLoader();
                        },
                        success: function(response) {
                            showNotification("Resource updated successfully!",
                                'success');

                            $('#manageResourceModal').modal('hide');

                            if (typeof refreshResourceTable === "function") {
                                refreshResourceTable();
                            }
                        },
                        error: function(xhr) {
                            let msg = "Failed to update resource";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join(
                                    "\n");
                            }
                            showNotification(msg, "danger");
                        },
                        complete: function() {
                            $('#manage-resource-editForm button[type="submit"]')
                                .prop(
                                    'disabled', false).html(
                                    '<i class="fas fa-save me-2"></i> Update');
                            hideLoader();
                        },
                    });
                }
            });

            // Helper to refresh resource table fragment after add/update
            function refreshResourceTable() {
                $('#resourceTable').load(location.href + " #resourceTable");
            }

            /* ---------------------------------------------------------------------
            Leave management: flatpickr init, validation, add/edit/delete leaves
            --------------------------------------------------------------------- */
            let companyHolidays = []; // list of company holidays used to disable dates in leave pickers

            // Fetch company holidays from API (used by leave datepicker)
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

            // Leaves use number_of_days on server; for frontend just parse float
            function getLeaveDayCount(leave) {
                return parseFloat(leave.number_of_days);
            }

            // Calculate working days between two dates, excluding Sundays and company holidays
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

            // Flatpickr instances used in leave modal
            let leaveStartPicker, leaveEndPicker;

            // Initialize leave flatpickr controls; destroy existing before re-init
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

            // Open leave modal: fetch holidays then initialize pickers & load leaves
            function openLeaveModal() {
                fetchCompanyHolidays().then(() => {
                    initLeaveFlatpickr();
                    loadLeaves();
                });
            }

            $('#manageLeaveModal').on('shown.bs.modal', openLeaveModal);

            // Update leaveDaysText based on selected start/end and duration
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

            // When duration changes (half/full) apply UI rules
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


            $('#leaveDuration').on('change', function() {
                handleLeaveDurationChange();
                checkLeaveOverlap();
            });

            // Leaves state and editing id
            let leaves = [];
            let leaveEditingId = null;

            // Initialize select2 for leave modal selects
            $('#leaveResource').select2({
                dropdownParent: $('#manageLeaveModal'),
                width: '100%',
                placeholder: 'Select resource',
                allowClear: true
            });
            $('#leaveType').select2({
                dropdownParent: $('#manageLeaveModal'),
                width: '100%',
                placeholder: 'Select leave type',
                allowClear: true
            });
            $('#leaveDuration').select2({
                dropdownParent: $('#manageLeaveModal'),
                width: '100%',
                placeholder: 'Select duration',
                allowClear: true
            });

            /* Inside set placeholder in search dropdown select2 */
            $('#leaveResource').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Search resource');
            });

            /* Inside set placeholder in search dropdown select2 */
            $('#leaveType').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Search leave type');
            });

            /* Inside set placeholder in search dropdown select2 */
            $('#leaveDuration').on('select2:open', function() {
                // Find the search input inside the dropdown
                let searchBox = $('.select2-container--open .select2-search__field');
                searchBox.attr('placeholder', 'Search duration');
            });

            // Validate leave form with jQuery Validate
            $("#leaveForm").validate({
                errorElement: "span",
                errorClass: "error",
                ignore: [],
                highlight: function(element) {
                    $(element).addClass("is-invalid");

                    if ($(element).hasClass('select2-hidden-accessible')) {
                        $(element).next('.select2')
                            .find('.select2-selection')
                            .addClass('is-invalid');
                    }
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid");

                    if ($(element).hasClass('select2-hidden-accessible')) {
                        $(element).next('.select2')
                            .find('.select2-selection')
                            .removeClass('is-invalid');
                    }
                },
                errorPlacement: function(error, element) {
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
                submitHandler: function() {
                    saveLeave();
                }
            });

            // Add leaveRemark Validation on keyup ----
            $('#leaveRemark').on('keyup', function() {
                $('#leaveRemark').valid();
            });

            // Load leaves list for leave modal
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

            // Render leaves list in leave modal
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

            // Save or update leave
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
                        beforeSend: () => {
                            // Optionally show loader
                            showLoader();
                        },
                        success: res => {
                            resetLeaveForm();
                            loadLeaves();
                            showNotification(res.message || "Leave updated", "success");
                        },
                        error: function(xhr) {
                            let msg = "Failed to update leave";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            showNotification(msg, "danger");
                        },
                        complete: () => {
                            hideLoader();
                        }
                    });
                } else {
                    $.post('/leaves', data)
                        .done(res => {
                            resetLeaveForm();
                            loadLeaves();
                            showNotification(res.message || "Leave added", "success");
                        })
                        .fail(xhr => {
                            let msg = "Failed to add leave";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            showNotification(msg, "danger");
                        });
                }
            }

            // Edit leave button: populate form for editing
            $('#leaveList').on('click', '.leave-edit', function() {

                const id = $(this).data('id');
                const l = leaves.find(x => x.id === id);
                if (!l) return;

                leaveEditingId = id;
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

            // Delete leave with fade out
            $('#leaveList').on('click', '.leave-delete', function() {
                const id = $(this).data('id');
                const item = $(this).closest('.holidays-item');

                $.ajax({
                    url: `/leaves/${id}`,
                    type: 'DELETE',
                    beforeSend: () => {
                        // Optionally show loader
                        showLoader();
                    },
                    success: res => {
                        item.fadeOut(300, function() {
                            $(this).remove();
                            if (!$('#leaveList').children().length) {
                                $('#leaveList').html(
                                    '<div class="holidays-empty-state">No leaves added yet</div>'
                                );
                            }
                        });
                        showNotification(res.message || "Leave deleted", "success");
                    },
                    error: (xhr) => {
                        let msg = "Failed to delete leave";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        showNotification(msg, "danger");
                    },
                    complete: () => {
                        hideLoader();
                    }
                });
            });

            // Reset leave form UI and state
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

            // Ensure select2 triggers validation on change
            $('#leaveResource, #leaveType, #leaveDuration').on('change', function() {
                $(this).valid();
            });

            // Reset leave modal on hide
            $('#manageLeaveModal').on('hidden.bs.modal', function() {
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

            // On show: re-init pickers and load leaves
            $('#manageLeaveModal').on('shown.bs.modal', function() {
                initLeaveFlatpickr();
                loadLeaves();
            });

            // Check if a new leave overlaps existing ones for the same resource
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
