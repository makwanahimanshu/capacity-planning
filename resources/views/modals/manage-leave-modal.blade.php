<div class="modal fade" id="manageLeaveModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content holidays-modal">

            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title">Leave Management</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">

                <!-- ADD / EDIT FORM -->
                <form id="leaveForm">
                    @csrf
                    <input type="hidden" id="leaveEditingId">

                    <div class="row g-3">

                        <!-- Resource -->
                        <div class="col-md-6">
                            <label class="holidays-label">Resource <span class="text-danger">*</span></label>
                            <select name="resource_id" id="leaveResource"
                                    class="holidays-form-control select-search">
                                <option value="">Select resource</option>
                                @foreach(App\Models\Resource::getResources() as $res)
                                    <option value="{{ $res->id }}">{{ $res->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Leave Type -->
                        <div class="col-md-6">
                            <label class="holidays-label">Leave Type <span class="text-danger">*</span></label>
                            <select name="type" id="leaveType"
                                    class="holidays-form-control">
                                <option value="">Select type</option>
                                <option value="sick">Sick</option>
                                <option value="paid">Paid</option>
                                <option value="probation">Probation</option>
                            </select>
                        </div>

                        <!-- Start Date -->
                        <div class="col-md-6">
                            <label class="holidays-label">Start Date <span class="text-danger">*</span></label>
                            {{-- <input type="date" name="start_date"
                                   id="leaveStart"
                                   class="holidays-form-control"> --}}
                            <input type="text" name="start_date" id="leaveStart" placeholder="Select start date" class="holidays-form-control" readonly>
                        </div>

                        <!-- End Date -->
                        <div class="col-md-6">
                            <label class="holidays-label">End Date <span class="text-danger">*</span></label>
                            {{-- <input type="date" name="end_date"
                                   id="leaveEnd"
                                   class="holidays-form-control"> --}}
                            <input type="text" name="end_date" id="leaveEnd" class="holidays-form-control" placeholder="Select end date" readonly>
                        </div>

                        <div id="leaveConflictWarning" class="alert alert-warning d-none mt-2">
                            You're already applied for leave on selected dates.
                        </div>

                        <div class="col-md-6">
                            <label class="holidays-label">Leave Duration <span class="text-danger">*</span></label>
                            <select name="duration" id="leaveDuration" class="holidays-form-control" >
                                <option value="">Select duration</option>
                                <option value="full">Full Day</option>
                                <option value="half">Half Day</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="holidays-label">Total Working Days:</label>
                            <input type="text" id="leaveDaysText" name="total_days" class="holidays-form-control" value="0" readonly>
                        </div>

                        <!-- Remark -->
                        <div class="col-12">
                            <label class="holidays-label">Remark</label>
                            <textarea name="remark"
                                      id="leaveRemark"
                                      class="holidays-form-control"
                                      rows="3"
                                      placeholder="Optional note"></textarea>
                        </div>

                    </div>

                    <!-- Submit -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <span id="leaveBtnText">Add Leave</span>
                        </button>
                    </div>
                </form>

                <hr class="holidays-divider">

                <!-- LEAVE LIST -->
                <label class="holidays-label">Existing Leaves</label>
                <div id="leaveList" class="holidays-list-container">
                    <div class="holidays-empty-state">No leaves added yet</div>
                </div>

            </div>
        </div>
    </div>
</div>