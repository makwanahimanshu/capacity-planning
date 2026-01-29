<!-- Manage Resource Modal -->
<div class="modal fade" id="manageResourceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content manage-resource-modal"
            style="height: 85vh; max-height: 800px; display: flex; flex-direction: column; overflow: hidden;">

            <!-- Header (Always Sticky) -->
            <div class="modal-header border-0 pb-3 px-4 pt-4 flex-shrink-0">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3 bg-primary bg-opacity-10 text-primary rounded-3 p-2">
                        <i class="fas fa-users-cog fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold text-white" id="manageResourceModalTitle">Resource
                            Management</h5>
                        <p class="text-white-50 small mb-0">View, add, and manage your team resources</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body (Flex Container) -->
            <div class="modal-body p-0 d-flex flex-column h-100 overflow-hidden">

                <!-- RESOURCE LIST VIEW -->
                <div id="manage-resource-listView" class="fade-in d-flex flex-column h-100 overflow-hidden">

                    <!-- Search Sticky Zone (Fixed at top of body) -->
                    <div class="modal-sticky-zone px-4 py-3 border-bottom border-white border-opacity-10 flex-shrink-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="search-wrapper flex-grow-1" style="max-width: 450px;">
                                <div class="input-group-merge">
                                    <input type="text" id="resourceListSearch" class="form-control"
                                        placeholder="Search by name or email">
                                </div>
                            </div>
                            <button id="resourceListAddBtn" class="btn btn-primary px-4 bg-gradient-premium">
                                <i class="fas fa-plus-circle me-2"></i>Add New Resource
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Content Area -->
                    <div class="table-responsive flex-grow-1" id="resourceTableScroll" style="overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0 resource-management-table">
                            <thead class="sticky-top" style="z-index: 5;">
                                <tr class="text-uppercase small fw-bold">
                                    <th class="ps-4">Resource Info</th>
                                    <th>Department</th>
                                    <th>Functional Role</th>
                                    <th class="text-center" style="width: 130px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="resourceListBody" class="border-top-0">
                                <!-- Loaded via AJAX -->
                            </tbody>
                        </table>

                        <!-- Infinite Scroll Loading Indicator -->
                        <div id="infiniteLoadIndicator" class="text-center py-4 d-none">
                            <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                            <span class="text-white-50 small">Loading more resources...</span>
                        </div>
                    </div>

                    <!-- Footer Sticky Zone (Fixed at bottom of body) -->
                    <div
                        class="modal-sticky-zone px-4 py-3 border-top border-white border-opacity-10 mt-auto flex-shrink-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info text-white-50 small">
                                Showing <span id="showingResourceCount" class="fw-bold text-white">0</span> of <span
                                    id="totalResourceCount" class="fw-bold text-white">0</span> resources
                            </div>
                            <div class="text-white-50 small opacity-50">
                                <i class="fas fa-mouse me-1"></i> Scroll to load more
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FORMS AREA (Only scrolls if content overflows) -->
                <div id="manage-resource-formsArea" class="h-100 overflow-auto px-4 pb-4 d-none">
                    <!-- ADD RESOURCE FORM -->
                    <form id="manage-resource-addForm" class="d-none fade-in pt-4">
                        <div class="d-flex align-items-center mb-4 pb-2 border-bottom border-white border-opacity-10">
                            <button type="button"
                                class="btn btn-link text-white-50 p-0 me-3 back-to-resource-list text-decoration-none border-0 shadow-none">
                                <i class="fas fa-arrow-left me-1"></i> Back to List
                            </button>
                            <h6 class="mb-0 fw-bold text-white">Create New Resource</h6>
                        </div>

                        <div class="row g-4 pt-1">
                            <div class="col-12">
                                <span class="section-label">Basic Information</span>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Full Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control h-45"
                                            placeholder="e.g. Johnathan Doe">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Email Address <span
                                                class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control h-45"
                                            placeholder="john.doe@company.com">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <span class="section-label">Professional Details</span>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Assign Department <span
                                                class="text-danger">*</span></label>
                                        <select name="dept_id" id="manage-resource-deptSelect"
                                            class="form-select select-search">
                                            <option></option>
                                            @foreach (App\Models\Department::all() as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Functional Role</label>
                                        <input type="text" name="role" class="form-control h-45"
                                            placeholder="e.g. Senior Software Engineer">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <span class="section-label">Settings & Capacity</span>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small text-white-50 fw-bold">Daily Allocation
                                            (Hrs)</label>
                                        <input type="number" name="daily_capacity" class="form-control h-45"
                                            min="0.5" step="0.5" value="8.0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-white-50 fw-bold">Resource Status</label>
                                        <select name="status" id="manage-resource-statusAdd"
                                            class="form-select h-45">
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 pb-2">
                                        <div class="form-check form-switch ps-5">
                                            <input class="form-check-input ms-n5" type="checkbox"
                                                name="is_project_manager" id="manage-resource-isManagerAdd">
                                            <label class="form-check-label small fw-bold text-white-50"
                                                for="manage-resource-isManagerAdd">Project Manager Privileges</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer d-flex justify-content-end p-0 pt-4 border-0 mt-4">
                            <button type="button"
                                class="btn btn-outline-secondary px-4 me-2 back-to-resource-list text-white-50 border-secondary">
                                Discard
                            </button>
                            <button type="submit" class="btn btn-primary px-4 bg-gradient-premium">
                                <i class="fas fa-check-circle me-2"></i>Save Resource
                            </button>
                        </div>
                    </form>

                    <!-- EDIT RESOURCE FORM -->
                    <form id="manage-resource-editForm" class="d-none fade-in pt-4">
                        <div class="d-flex align-items-center mb-4 pb-2 border-bottom border-white border-opacity-10">
                            <button type="button"
                                class="btn btn-link text-white-50 p-0 me-3 back-to-resource-list text-decoration-none border-0 shadow-none">
                                <i class="fas fa-arrow-left me-1"></i> Back to List
                            </button>
                            <h6 class="mb-0 fw-bold text-white">Update Resource: <span id="editResourceNameHeader"
                                    class="text-info"></span></h6>
                        </div>

                        <div class="row g-4 pt-1">
                            <div class="col-12">
                                <span class="section-label">Basic Information</span>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Full Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control h-45"
                                            id="manage-resource-editName">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Email Address <span
                                                class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control h-45"
                                            id="manage-resource-editEmail">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <span class="section-label">Professional Details</span>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Assign Department <span
                                                class="text-danger">*</span></label>
                                        <select name="dept_id" class="form-select select-search"
                                            id="manage-resource-editDept">
                                            <option></option>
                                            @foreach (App\Models\Department::all() as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Functional Role</label>
                                        <input type="text" name="role" class="form-control h-45"
                                            id="manage-resource-editRole">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <span class="section-label">Settings & Capacity</span>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small text-white-50 fw-bold">Daily Allocation
                                            (Hrs)</label>
                                        <input type="number" name="daily_capacity" class="form-control h-45"
                                            id="manage-resource-editCapacity" min="0.5" step="0.5">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-white-50 fw-bold">Resource Status</label>
                                        <select name="status" class="form-select h-45"
                                            id="manage-resource-editStatus">
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 pb-2">
                                        <div class="form-check form-switch ps-5">
                                            <input class="form-check-input ms-n5" type="checkbox"
                                                name="is_project_manager" id="manage-resource-isManagerEdit">
                                            <label class="form-check-label small fw-bold text-white-50"
                                                for="manage-resource-isManagerEdit">Project Manager Privileges</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer d-flex justify-content-end p-0 pt-4 border-0 mt-4">
                            <button type="button"
                                class="btn btn-outline-secondary px-4 me-2 back-to-resource-list text-white-50 border-secondary">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary px-4 bg-gradient-premium">
                                <i class="fas fa-save me-2"></i>Update Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
