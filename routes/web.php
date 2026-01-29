<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CapacityPlanningController;
use App\Http\Controllers\CapacityDashboardController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\LeaveController;
use App\Models\Holiday;
use Illuminate\Support\Facades\Auth;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard'); // Already logged in → go to dashboard
    }
    return redirect()->route('login'); // Not logged in → go to login page
});


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    Route::get('/capacity_planning', function () {
        return view('capacity_planning');
    });


    // ---------------    Capacity Planning Page APIs      ----------------------------- 
    Route::get('/capacity-planning', [CapacityPlanningController::class, 'index'])->name('capacity-planning.index');
    Route::get('/capacity-planning/resources', [CapacityPlanningController::class, 'getResources'])->name('capacity-planning.resources');
    Route::get('/capacity-planning/assignments/{resourceId}/{month}', [CapacityPlanningController::class, 'getAssignments'])->name('capacity-planning.assignments');

    Route::get('/capacity-planning/leaves/{resource}/{month}', [CapacityPlanningController::class, 'getLeavesByMonth']);

    Route::post('/capacity-planning/allocations/save', [CapacityPlanningController::class, 'saveAllocations'])
        ->name('capacity-planning.allocations.save');

    Route::delete('/capacity-planning/allocations/{id}', [CapacityPlanningController::class, 'deleteAllocation'])
        ->name('capacity-planning.allocations.delete');

    Route::post('/capacity-planning/allocations/save-bulk', [CapacityPlanningController::class, 'saveBulkAllocations'])
        ->name('capacity-planning.allocations.save.bulk');

    Route::post('/capacity-planning/allocations/save/mega-bulk', 
        [CapacityPlanningController::class, 'saveMegaBulkAllocations'])
        ->name('capacity-planning.allocations.save.mega-bulk');



    // ------------------    Manage Project APIs      ----------------------------- 
    Route::post('/projects', [CapacityPlanningController::class, 'store'])->name('projects.store');
    Route::get('/projects/{id}/edit', [CapacityPlanningController::class, 'edit'])->name('projects.edit');
    Route::post('/projects/{id}', [CapacityPlanningController::class, 'update'])->name('projects.update');


    // ------------------    Holiday APIs      ----------------------------- 
    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::post('/holidays', [HolidayController::class, 'store']);
    Route::post('/holidays/{id}', [HolidayController::class, 'update']);
    Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']);


    // ------------------    Capacity Dashboard Page APIs      ----------------------------- 
    Route::get('/capacity', [CapacityDashboardController::class, 'dashboard'])->name('capacity.dashboard');
    Route::get('/resource-capacity', [CapacityDashboardController::class, 'getResourceCapacity']);

    Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
    Route::get('/resources/{id}', [ResourceController::class, 'show']);
    Route::post('/resources', [ResourceController::class, 'store']);
    Route::put('/resources/{id}', [ResourceController::class, 'update']);
    Route::delete('/resources/{id}', [ResourceController::class, 'destroy'])->name('resources.destroy');

        
    Route::prefix('leaves')->group(function () {
        Route::get('/', [LeaveController::class, 'index']);       // List
        Route::post('/', [LeaveController::class, 'store']);      // Create
        Route::get('{id}', [LeaveController::class, 'show']);     // Single
        Route::put('{id}', [LeaveController::class, 'update']);   // Update
        Route::delete('{id}', [LeaveController::class, 'destroy']);// Delete
    });

    Route::get('/api/company-holidays', function () {
        return Holiday::pluck('date');
    });

    Route::post('/leaves/check-overlap', [LeaveController::class, 'checkOverlap']);


});

require __DIR__.'/auth.php';


