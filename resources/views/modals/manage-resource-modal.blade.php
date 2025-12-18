<!-- Manage Resource Modal -->
<div class="modal fade" id="manageResourceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <div class="modal-content manage-resource-modal">

            <!-- Header -->
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-user-cog me-2 text-primary"></i>
                    <span id="manageResourceModalTitle">Add / Edit Resource</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">

                <!-- Choose Add/Edit -->
                <div id="manage-resource-chooseAction" class="d-flex justify-content-center gap-3">
                    <button id="manageResourceAddBtn" class="btn btn-primary">Add Resource</button>
                    <button id="manageResourceEditBtn" class="btn btn-primary">Edit Resource</button>
                </div>

                <!-- EDIT DROPDOWN -->
                <div class="col-12 mt-3 d-none" id="manage-resource-editDropdownContainer">
                    <label class="form-label fw-semibold">Select Resource</label>
                    <select class="form-select select-search" id="manage-resource-editSelect" data-placeholder="Select resource" data-allow-clear="true">
                        <option></option>
                        @foreach(App\Models\Resource::getResources() as $res)
                            <option value="{{ $res->id }}">{{ $res->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- ADD RESOURCE FORM -->
                <form id="manage-resource-addForm" class="d-none mt-3">
                    @csrf
                    <div class="manage-resource-scroll">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter full name" id="manage-resource-nameInput">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="Enter email" id="manage-resource-emailInput">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                <select name="dept_id" id="manage-resource-deptSelect" class="form-select select-search" data-placeholder="Select department">
                                    <option></option>
                                    @foreach(App\Models\Department::all() as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Role</label>
                                <input type="text" name="role" class="form-control" placeholder="e.g. Developer, Manager" id="manage-resource-role">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Daily Capacity (Hours)</label>
                                <input type="number" name="daily_capacity" class="form-control" min="1" placeholder="e.g. 8" id="manage-resource-daily-capacity">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Total Hours</label>
                                <input type="number" name="total_hours" class="form-control" placeholder="0" id="manage-resource-total-hours">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Leave Hours</label>
                                <input type="number" name="leave_hours" class="form-control" placeholder="0" id="manage-resource-leave-hours">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select select-search" id="manage-resource-statusSelect">
                                    <option></option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_project_manager" id="manage-resource-isManagerAdd">
                                    <label class="form-check-label" for="manage-resource-isManagerAdd">Project Manager</label>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="modal-footer d-flex justify-content-end p-0 pt-2">
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save
                        </button>
                    </div>
                </form>

                <!-- EDIT RESOURCE FORM -->
                <form id="manage-resource-editForm" class="d-none mt-3">
                    @csrf
                    <div class="manage-resource-scroll">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter full name" id="manage-resource-editName">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="Enter email" id="manage-resource-editEmail">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                <select name="dept_id" class="form-select select-search" id="manage-resource-editDept" data-placeholder="Select department">
                                    <option></option>
                                    @foreach(App\Models\Department::all() as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Role</label>
                                <input type="text" name="role" class="form-control" placeholder="e.g. Developer, Manager" id="manage-resource-editRole">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Daily Capacity</label>
                                <input type="number" name="daily_capacity" class="form-control" id="manage-resource-editCapacity" placeholder="e.g. 8">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Total Hours</label>
                                <input type="number" name="total_hours" class="form-control" id="manage-resource-editTotalHours" placeholder="0">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Leave Hours</label>
                                <input type="number" name="leave_hours" class="form-control" id="manage-resource-editLeaveHours" placeholder="0">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select select-search" id="manage-resource-editStatus">
                                    <option></option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_project_manager" id="manage-resource-isManagerEdit">
                                    <label class="form-check-label" for="manage-resource-isManagerEdit">Project Manager</label>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="modal-footer d-flex justify-content-end p-0 pt-2">
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Update
                        </button>
                    </div>
                </form>

            </div>

        </div>
    </div>
</div>