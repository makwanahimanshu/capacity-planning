
Perfect 👍 thanks for sharing your migrations.
Let’s prepare **dummy insert queries** for all 5 tables (`resources`, `projects`, `resource_project_allocations`, `holidays`, `timesheets`) with some realistic month JSON for allocations (skipping weekends).

---

## ✅ Dummy Data Insert Queries

```sql
-- Resources
INSERT INTO resources (id, name, email, role, daily_capacity, created_at, updated_at) VALUES
(1, 'Alice Johnson', 'alice@example.com', 'Backend Developer', 8.00, NOW(), NOW()),
(2, 'Bob Smith', 'bob@example.com', 'Frontend Developer', 8.00, NOW(), NOW()),
(3, 'Carol White', 'carol@example.com', 'QA Engineer', 7.50, NOW(), NOW()),
(4, 'David Brown', 'david@example.com', 'Project Manager', 6.00, NOW(), NOW()),
(5, 'Emma Wilson', 'emma@example.com', 'UI/UX Designer', 8.00, NOW(), NOW());

-- Projects
INSERT INTO projects (id, name, description, start_date, end_date, total_hours, status, priority, owner_id, is_billable, created_at, updated_at) VALUES
(1, 'Website Revamp', 'Corporate website redesign project', '2025-09-01', '2025-12-15', 400, 'active', 2, NULL, 1, NOW(), NOW()),
(2, 'Mobile App Development', 'Cross-platform mobile app', '2025-08-01', '2025-11-30', 600, 'active', 1, NULL, 1, NOW(), NOW()),
(3, 'Cloud Migration', 'Move services to AWS', '2025-09-15', '2026-01-31', 300, 'planned', 3, NULL, 1, NOW(), NOW()),
(4, 'Data Analytics POC', 'Build proof of concept for analytics platform', '2025-09-10', '2025-10-31', 120, 'active', 2, NULL, 1, NOW(), NOW()),
(5, 'Security Audit', 'Full infra security audit', '2025-09-05', '2025-09-30', 80, 'completed', 1, NULL, 1, NOW(), NOW());

-- Holidays (skipping weekends already, so only public holidays here)
INSERT INTO holidays (id, date, description, created_at, updated_at) VALUES
(1, '2025-09-02', 'Independence Day (Example)', NOW(), NOW()),
(2, '2025-09-10', 'Ganesh Chaturthi', NOW(), NOW()),
(3, '2025-09-25', 'Company Foundation Day', NOW(), NOW());

-- Resource Allocations
-- Resource 1 (Alice) assigned to Project 1 and Project 2
INSERT INTO resource_project_allocations (resource_id, project_id, month, available_hours, allocated_hours, daily_hours, created_at, updated_at) VALUES
(1, 1, '2025-09', 35, 30,
    '[
        {"date":"2025-09-01","hours":6},
        {"date":"2025-09-03","hours":6},
        {"date":"2025-09-04","hours":6},
        {"date":"2025-09-05","hours":6},
        {"date":"2025-09-08","hours":6}
    ]', NOW(), NOW()
),
(1, 2, '2025-09', 35, 24,
    '[
        {"date":"2025-09-10","hours":6},
        {"date":"2025-09-11","hours":6},
        {"date":"2025-09-12","hours":6},
        {"date":"2025-09-15","hours":6}
    ]', NOW(), NOW()
);

-- Resource 2 (Bob) assigned to Project 2 and Project 3
INSERT INTO resource_project_allocations (resource_id, project_id, month, available_hours, allocated_hours, daily_hours, created_at, updated_at) VALUES
(2, 2, '2025-09', 35, 20,
    '[
        {"date":"2025-09-01","hours":5},
        {"date":"2025-09-03","hours":5},
        {"date":"2025-09-04","hours":5},
        {"date":"2025-09-05","hours":5}
    ]', NOW(), NOW()
),
(2, 1, '2025-09', 25, 18,
    '[
        {"date":"2025-09-08","hours":6},
        {"date":"2025-09-09","hours":6},
        {"date":"2025-09-10","hours":6}
    ]', NOW(), NOW()
);


-- Resource Project Allocations (with daily_hours JSON, skipping weekends)
-- -- Resource 1 (Alice) assigned to Project 1 and Project 2
-- INSERT INTO resource_project_allocations (resource_id, project_id, month, available_hours, allocated_hours, daily_hours, created_at, updated_at) VALUES
-- (1, 1, '2025-09', 35, 30,
--     '[
--         {"date":"2025-09-01","hours":6},
--         {"date":"2025-09-03","hours":6},
--         {"date":"2025-09-04","hours":6},
--         {"date":"2025-09-05","hours":6},
--         {"date":"2025-09-08","hours":6}
--     ]', NOW(), NOW()
-- ),
-- (1, 2, '2025-09', 35, 24,
--     '[
--         {"date":"2025-09-10","hours":6},
--         {"date":"2025-09-11","hours":6},
--         {"date":"2025-09-12","hours":6},
--         {"date":"2025-09-15","hours":6}
--     ]', NOW(), NOW()
-- );

-- -- Resource 2 (Bob) assigned to Project 2 and Project 3
-- INSERT INTO resource_project_allocations (resource_id, project_id, month, available_hours, allocated_hours, daily_hours, created_at, updated_at) VALUES
-- (2, 2, '2025-09', 35, 20,
--     '[
--         {"date":"2025-09-01","hours":5},
--         {"date":"2025-09-03","hours":5},
--         {"date":"2025-09-04","hours":5},
--         {"date":"2025-09-05","hours":5}
--     ]', NOW(), NOW()
-- ),
-- (2, 1, '2025-09', 25, 18,
--     '[
--         {"date":"2025-09-08","hours":6},
--         {"date":"2025-09-09","hours":6},
--         {"date":"2025-09-10","hours":6}
--     ]', NOW(), NOW()
-- );

-- -- Resource 3 (Carol) assigned to Project 3 and Project 4
-- INSERT INTO resource_project_allocations (resource_id, project_id, month, available_hours, allocated_hours, daily_hours, created_at, updated_at) VALUES
-- (3, 3, '2025-09', 35, 15,
--     '[
--         {"date":"2025-09-01","hours":5},
--         {"date":"2025-09-02","hours":5},
--         {"date":"2025-09-03","hours":5}
--     ]', NOW(), NOW()
-- ),
-- (3, 4, '2025-09', 35, 12,
--     '[
--         {"date":"2025-09-08","hours":4},
--         {"date":"2025-09-09","hours":4},
--         {"date":"2025-09-10","hours":4}
--     ]', NOW(), NOW()
-- );

-- -- Resource 4 (David) assigned to Project 4 and Project 5
-- INSERT INTO resource_project_allocations (resource_id, project_id, month, available_hours, allocated_hours, daily_hours, created_at, updated_at) VALUES
-- (4, 4, '2025-09',35, 18,
--     '[
--         {"date":"2025-09-01","hours":6},
--         {"date":"2025-09-03","hours":6},
--         {"date":"2025-09-04","hours":6}
--     ]', NOW(), NOW()
-- ),
-- (4, 5, '2025-09',35, 12,
--     '[
--         {"date":"2025-09-08","hours":4},
--         {"date":"2025-09-09","hours":4},
--         {"date":"2025-09-10","hours":4}
--     ]', NOW(), NOW()
-- );

-- -- Resource 5 (Emma) assigned to Project 1, 3, 5
-- INSERT INTO resource_project_allocations (resource_id, project_id, month, available_hours, allocated_hours, daily_hours, created_at, updated_at) VALUES
-- (5, 1, '2025-09',35, 16,
--     '[
--         {"date":"2025-09-01","hours":4},
--         {"date":"2025-09-03","hours":4},
--         {"date":"2025-09-04","hours":4},
--         {"date":"2025-09-05","hours":4}
--     ]', NOW(), NOW()
-- ),
-- (5, 3, '2025-09',35, 12,
--     '[
--         {"date":"2025-09-08","hours":4},
--         {"date":"2025-09-09","hours":4},
--         {"date":"2025-09-10","hours":4}
--     ]', NOW(), NOW()
-- ),
-- (5, 5, '2025-09',35, 8,
--     '[
--         {"date":"2025-09-15","hours":4},
--         {"date":"2025-09-16","hours":4}
--     ]', NOW(), NOW()
-- );


-- Timesheets (actual hours logged)
INSERT INTO timesheets (id, resource_id, project_id, date, actual_hours, created_at, updated_at) VALUES
(1, 1, 1, '2025-09-24', 6, NOW(), NOW()),
(2, 1, 1, '2025-09-25', 5.5, NOW(), NOW()),
(3, 2, 2, '2025-09-01', 6, NOW(), NOW()),
(4, 3, 3, '2025-09-16', 5.5, NOW(), NOW()),
(5, 5, 5, '2025-09-09', 4.5, NOW(), NOW());
```

---

### 🔑 Notes

* `daily_hours` JSON follows the format you shared.
* Weekends (`Saturday`, `Sunday`) are **skipped** in allocations.
* `allocated_hours` = sum of JSON `hours` for that record.
* Holidays table only includes special days; weekends are naturally excluded.

---

👉 Do you want me to also prepare a **PHP seeder file** (so you can `php artisan db:seed` to load this data), or will you directly run these SQL inserts?




ALTER TABLE `projects` CHANGE `priority` `priority` TINYINT NULL DEFAULT NULL COMMENT '1 = Low, 2 = Medium, 3 = High';