Got it! You want to generate multiple graphs from your `my_sheet.xlsx` data in a Laravel application. We can use **Laravel Excel** to read the Excel file and **Chart.js** to render the graphs in the frontend. I’ll guide you **step by step** with full code.

---

## **Step 1: Install Required Packages**

1. **Laravel Excel** to read Excel data:

```bash
composer require maatwebsite/excel
```

2. **Chart.js** for frontend charts (we can include via CDN in Blade templates):

```html
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

---

## **Step 2: Prepare Excel Upload & Read Logic**

Assume you have uploaded `my_sheet.xlsx` in `storage/app/public/`.

Create a **controller**:

```bash
php artisan make:controller CapacityController
```

**app/Http/Controllers/CapacityController.php**:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class CapacityController extends Controller
{
    public function index()
    {
        return view('capacity.index');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('excel_file');
        $path = $file->store('public');

        // Read Excel data
        $data = Excel::toArray([], $file);

        // Assume the first sheet
        $rows = $data[0];

        // Process rows: adjust according to your Excel columns
        $resources = [];
        foreach ($rows as $key => $row) {
            if ($key == 0) continue; // Skip header
            $resources[] = [
                'name' => $row[0],
                'department' => $row[1],
                'available_hours' => (float)$row[2],
                'allocated_hours' => (float)$row[3],
            ];
        }

        // Pass to view
        return view('capacity.charts', compact('resources'));
    }
}
```

---

## **Step 3: Create Routes**

**routes/web.php**:

```php
use App\Http\Controllers\CapacityController;

Route::get('/capacity', [CapacityController::class, 'index']);
Route::post('/capacity/upload', [CapacityController::class, 'upload'])->name('capacity.upload');
```

---

## **Step 4: Create Blade Views**

### **capacity/index.blade.php**

```html
<!DOCTYPE html>
<html>
<head>
    <title>Capacity Planning Upload</title>
</head>
<body>
    <h2>Upload Excel File</h2>
    <form action="{{ route('capacity.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="excel_file" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
```

---

### **capacity/charts.blade.php**

```html
<!DOCTYPE html>
<html>
<head>
    <title>Capacity Charts</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>Capacity Charts</h2>

    <canvas id="capacityChart" width="400" height="200"></canvas>
    <canvas id="deptChart" width="400" height="200"></canvas>
    <canvas id="underUtilizedChart" width="400" height="200"></canvas>
    <canvas id="allocationChart" width="400" height="200"></canvas>

    <script>
        const resources = @json($resources);

        // 1) Capacity (Total Available vs Allocated)
        const totalAvailable = resources.reduce((sum, r) => sum + r.available_hours, 0);
        const totalAllocated = resources.reduce((sum, r) => sum + r.allocated_hours, 0);
        const utilization = totalAllocated;
        const utilizationPercent = ((totalAllocated / totalAvailable) * 100).toFixed(2);

        const ctx1 = document.getElementById('capacityChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: ['Available Hours', 'Allocated Hours', 'Utilization %'],
                datasets: [{
                    label: 'Hours',
                    data: [totalAvailable, totalAllocated, utilizationPercent],
                    backgroundColor: ['#4CAF50', '#2196F3', '#FFC107']
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 2) Dept-wise Available Hours
        const deptMap = {};
        resources.forEach(r => {
            deptMap[r.department] = (deptMap[r.department] || 0) + r.available_hours;
        });

        const ctx2 = document.getElementById('deptChart').getContext('2d');
        new Chart(ctx2, {
            type: 'pie',
            data: {
                labels: Object.keys(deptMap),
                datasets: [{
                    label: 'Available Hours by Department',
                    data: Object.values(deptMap),
                    backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF']
                }]
            }
        });

        // 3) Under-utilized Resources (<40 hrs)
        const underUtilized = resources.filter(r => r.allocated_hours < 40);
        const ctx3 = document.getElementById('underUtilizedChart').getContext('2d');
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: underUtilized.map(r => r.name),
                datasets: [{
                    label: 'Allocated Hours (<40)',
                    data: underUtilized.map(r => r.allocated_hours),
                    backgroundColor: '#FF5722'
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // 4) Allocation & Utilization per Resource
        const ctx4 = document.getElementById('allocationChart').getContext('2d');
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: resources.map(r => r.name),
                datasets: [
                    {
                        label: 'Available Hours',
                        data: resources.map(r => r.available_hours),
                        backgroundColor: '#4CAF50'
                    },
                    {
                        label: 'Allocated Hours',
                        data: resources.map(r => r.allocated_hours),
                        backgroundColor: '#2196F3'
                    }
                ]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

    </script>
</body>
</html>
```

---

✅ **Step 5: Test**

1. Visit `/capacity` in your browser.
2. Upload `my_sheet.xlsx`.
3. You should see 4 charts visualizing:

* Total capacity vs allocated vs utilization.
* Department-wise available hours.
* Under-utilized resources (<40 hours/week).
* Allocation vs utilization per resource.

---

If you want, I can **enhance this further** to generate **stacked bar charts**, **dynamic colors for departments**, and **show utilization % per resource** like professional dashboards.

Do you want me to do that next?
