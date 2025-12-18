<!-- Main Add/Edit Project Modal -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">

            <!-- Modal Header -->
            <div class="modal-header border-0">
                <h5 class="modal-title" id="projectModalLabel">
                    <i class="fas fa-briefcase me-2 text-primary"></i><span id="modalTitle">Add/Edit Project</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Choose Action Step -->
                <div id="chooseActionStep" class="d-flex justify-content-center gap-3">
                    <button id="chooseAddBtn" class="btn btn-primary">Add Project</button>
                    <button id="chooseEditBtn" class="btn btn-primary">Edit Project</button>
                </div>

                <!-- Add Project Form -->
                <form id="addProjectForm" class="d-none mt-3">
                    @csrf
                    <div class="form-scrollable">
                        <div class="row g-3  custom-modal">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter project name" id="projectName">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Total Hours <span class="text-danger">*</span></label>
                                <input type="number" name="total_hours" id="totalHours" class="form-control" min="0" placeholder="e.g. 120">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" id="descriptionPro" class="form-control" rows="3" placeholder="Enter project details..."></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" id="addStartDate">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" id="addEndDate">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select data-placeholder="Select status" data-allow-clear="true"
                                    class="form-select me-2 select-search" name="status" id="statusofProject">
                                    <option></option>
                                    <option value="planned">Planned</option>
                                    <option value="active">Active</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Priority</label>
                                <select data-placeholder="Select priority" data-allow-clear="true"
                                    class="form-select me-2 select-search" name="priority" id="priorityOfProject">
                                    <option></option>
                                    <option value="1">Low</option>
                                    <option value="2">Medium</option>
                                    <option value="3">High</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project Manager <span class="text-danger">*</span></label>
                                <select data-placeholder="Select project manager" data-allow-clear="true"
                                    class="form-select me-2 select-search" name="project_manager_id" id="addManagerSelect">
                                    <option></option>
                                    @foreach (App\Models\Resource::getProjectManagers() as $proManager)
                                        <option value="{{ $proManager->id }}" {{ old('proManager_id') == $proManager->id ? 'selected' : '' }}>
                                        {{ $proManager->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Assigned Resources <span class="text-danger">*</span></label>
                                <select data-placeholder="Select resources" name="resource_ids[]" class="form-select select2-multiple select-search" id="addResourceSelect" multiple>
                                    
                                    @foreach (App\Models\Resource::getResources() as $resource)
                                        <option value="{{ $resource->id }}" {{ old('resource_ids') == $resource->id ? 'selected' : '' }}>
                                        {{ $resource->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_billable" id="isBillableSwitch">
                                    <label class="form-check-label" for="isBillableSwitch">Billable Project</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-end p-0 pt-2" id="footerDiv">
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="projectFormSubmit">
                            <i class="fas fa-save me-2"></i> Save
                        </button>
                    </div>
                </form>

                <!-- Edit Project Form -->
                <form id="editProjectForm" class="d-none mt-3">
                    @csrf
                    <div class="form-scrollable">
                        <div class="row g-3 custom-modal">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Project Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter project name" id="editProjetName">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Total Hours <span class="text-danger">*</span></label>
                                <input type="number" name="total_hours" class="form-control" min="0" placeholder="e.g. 120" id="editTotalHours">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter project details..." id="editDescription"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" id="editStartDate">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" id="editEndDate">
                            </div>


                                <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select data-placeholder="Select status" data-allow-clear="true"
                                    class="form-select me-2 select-search" name="status" id="editStatusofProject">
                                    <option></option>
                                    <option value="planned">Planned</option>
                                    <option value="active">Active</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Priority</label>
                                <select data-placeholder="Select priority" data-allow-clear="true"
                                    class="form-select me-2 select-search" name="priority" id="editPriorityOfProject">
                                    <option></option>
                                    <option value="1">Low</option>
                                    <option value="2">Medium</option>
                                    <option value="3">High</option>
                                </select>
                            </div>

                                <div class="col-md-6">
                                <label class="form-label fw-semibold">Project Manager <span class="text-danger">*</span></label>
                                <select data-placeholder="Select project manager" data-allow-clear="true"
                                    class="form-select me-2 select-search" name="project_manager_id" id="editManagerSelect">
                                    <option></option>
                                    @foreach (App\Models\Resource::getProjectManagers() as $proManager)
                                        <option value="{{ $proManager->id }}" {{ old('proManager_id') == $proManager->id ? 'selected' : '' }}>
                                        {{ $proManager->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                                <div class="col-md-6">
                                <label class="form-label fw-semibold">Assigned Resources <span class="text-danger">*</span></label>
                                <select data-placeholder="Select resources" name="resource_ids[]" class="form-select select2-multiple select-search" id="editResourceSelect" multiple>
                                    @foreach (App\Models\Resource::getResources() as $resource)
                                        <option value="{{ $resource->id }}" {{ old('resource_ids') == $resource->id ? 'selected' : '' }}>
                                        {{ $resource->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_billable" id="isBillableSwitch">
                                    <label class="form-check-label" for="isBillableSwitch">Billable Project</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-end p-0 pt-2" id="editFooterDiv">
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="projectFormSubmit">
                            <i class="fas fa-save me-2"></i> Save
                        </button>
                    </div>
                </form>

                <!-- Edit Project Dropdown -->
                <div class="col-12 d-none" id="editProjectDropdownContainer">
                    <label class="form-label fw-semibold">Select Project to Edit</label>
                    <select data-placeholder="Select project" data-allow-clear="true"
                        class="form-select me-2 select-search select2-single" name="project_id" id="editProjectSelect">
                        <option></option>
                        @foreach (App\Models\Project::getProjectList() as $project)
                            <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}</option>
                        @endforeach
                    </select>

                </div>

            </div>

            {{-- <div class="modal-footer d-flex justify-content-end" id="footerDiv">
                <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="projectFormSubmit">
                    <i class="fas fa-save me-2"></i> Save
                </button>
            </div> --}}
        </div>
    </div>
</div>