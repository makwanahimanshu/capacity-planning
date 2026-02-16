<!-- Holiday Management Modal -->
<div class="modal fade" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content holidays-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="holidayModalLabel">Holiday Management</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="holidayForm">
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="dateRange" class="holidays-label">Select Date Range <span class="text-danger">*</span></label>
                            <input type="text" id="dateRange" name="date_range" class="holidays-form-control w-100" placeholder="Select start and end date" readonly>
                            <div class="holidays-error" id="dateError">Please select a date range</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="description" class="holidays-label">Description <span class="text-danger">*</span></label>
                            <textarea id="description" name="description" class="holidays-form-control w-100" rows="3" placeholder="Enter holiday description"></textarea>
                            <div class="holidays-error" id="descError">Description is required</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100" id="addHolidayBtn">
                                <span id="btnText">Add Holiday</span>
                            </button>
                        </div>
                    </div>
                </form>

                <hr class="holidays-divider">

                <!-- Holiday list month filter -->
                <div class="leave-list-filters row g-3 align-items-end mb-3">
                    <div class="col-auto col-md">
                        <label class="holidays-label d-block mb-1">Month</label>
                        <input type="month" id="holidayFilterMonth" class="holidays-form-control leave-filter-input">
                    </div>
                    <div class="col-auto">
                        <label class="holidays-label d-block mb-1 invisible">Apply</label>
                        <button type="button" id="holidayFilterApply" class="btn btn-primary leave-filter-apply-btn px-4">Apply</button>
                    </div>
                </div>

                <div>
                    <label class="holidays-label">Existing Holidays</label>
                    <div class="holidays-list-container" id="holidaysList">
                        <div class="holidays-empty-state">
                            No holidays added yet
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>