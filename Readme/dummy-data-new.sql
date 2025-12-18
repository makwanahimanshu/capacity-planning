-- ======================
-- DEPARTMENTS
-- ======================
INSERT INTO `departments` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Java Development', '2025-09-30 19:14:08', '2025-09-30 19:14:08'),
(2, 'Frontend', '2025-09-30 19:14:08', '2025-09-30 19:14:08'),
(3, 'QA', '2025-09-30 19:14:08', '2025-09-30 19:14:08'),
(4, 'MERN', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(5, 'MOBILE', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(6, 'Python', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(7, 'PHP', '2025-10-09 00:00:00', '2025-10-09 00:00:00');


-- ======================
-- RESOURCES
-- ======================
INSERT INTO `resources` (`id`, `name`, `email`, `dept_id`, `is_project_manager`,`total_hours`, `leave_hours`, `role`, `daily_capacity`, `created_at`, `updated_at`) VALUES
-- MERN Team
(1, 'Akshay Rajput', 'akshay.rajput@innvonix.com', 4, 1, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(2, 'Rishit Parikh', 'rishit.parikh@innvonix.com', 4, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(3, 'Devansh Jadav', 'devansh.jadav@innvonix.com', 4, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(4, 'Vikas Prajapati', 'vikas.prajapati@innvonix.com', 4, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(5, 'Biren Hirapara', 'biren.hirapara@innvonix.com', 4, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(6, 'Sahil Rathod', 'sahil.rathod@innvonix.com', 4, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- MOBILE Team
(7, 'Karan Parihar', 'karan.parihar@innvonix.com', 5, 1, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(8, 'Parth Raval', 'parth.raval@innvonix.com', 5, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(9, 'Bhaumik Gandhi', 'bhaumik.gandhi@innvonix.com', 5, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(10, 'Mehul Valand', 'mehul.valand@innvonix.com', 5, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(11, 'Tirth Patel', 'tirth.patel@innvonix.com', 5, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- QA Team
(12, 'Birva Solanki', 'birva.solanki@innvonix.com', 3, 0, 40, 0, 'QA Engineer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(13, 'Kedar Soneji', 'kedar.soneji@innvonix.com', 3, 0, 40, 0, 'QA Engineer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(14, 'Asutosh', 'asutosh@innvonix.com', 3, 0, 40, 0, 'QA Engineer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- Python Team
(15, 'Dhruv Patel', 'dhruv.patel@innvonix.com', 6, 1, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(16, 'Ayushi Dhamecha', 'ayushi.dhamecha@innvonix.com', 6, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(17, 'Jenil Prajapati', 'jenil.prajapati@innvonix.com', 6, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(18, 'Jay Purani', 'jay.purani@innvonix.com', 6, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- PHP Team
(19, 'Ketul Patel', 'ketul.patel@innvonix.com', 7, 1, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(20, 'Himanshu Makwana', 'himanshu.makwana@innvonix.com', 7, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(21, 'Adarsh Haldar', 'adarsh.haldar@innvonix.com', 7, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(22, 'Parth Lalcheta', 'parth.lalcheta@innvonix.com', 7, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(23, 'Dharmesh Kanetiya', 'dharmesh.kanetiya@innvonix.com', 7, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(24, 'Purvi Patel', 'purvi.patel@innvonix.com', 7, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(25, 'Tushar Viradiya', 'tushar.viradiya@innvonix.com', 7, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(26, 'Vandan Patel', 'vandan.patel@innvonix.com', 7, 0, 40, 0, 'Developer', 8, '2025-10-09 00:00:00', '2025-10-09 00:00:00');

-- ======================
-- PROJECT MANAGERS
-- ======================
-- INSERT INTO `project_managers` (`id`, `name`, `created_at`, `updated_at`) VALUES
-- (1, 'Karan Parihar', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- (2, 'Lalith Senguthar', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- (3, 'Purav sir', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- (4, 'Mehul Valand', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- (5, 'Pratik Amlani', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- (6, 'Ajay Masi', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- (7, 'Akshay Rajput', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- (8, 'Ketul', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
-- (9, 'Dhruv Patel', '2025-10-09 00:00:00', '2025-10-09 00:00:00');

-- ======================
-- PROJECTS
-- ======================
INSERT INTO `projects` 
(`id`, `name`, `description`, `start_date`, `end_date`, `project_manager_id`, `total_hours`, `status`, `priority`, `owner_id`, `is_billable`, `created_at`, `updated_at`) 
VALUES
(1, 'Doctory', 'Project Doctory', '2025-10-01', '2025-12-31', 1, 90, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(2, 'Monty', 'Project Monty', '2025-10-01', '2025-12-31', 1, 5, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(3, 'DobiValet', 'Project DobiValet', '2025-10-01', '2025-12-31', 2, 10, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(4, 'Racquet Heroes', 'Project Racquet Heroes', '2025-10-01', '2025-12-31', 3, 78, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(5, 'Contact Base', 'Project Contact Base', '2025-10-01', '2025-12-31', 4, 12, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(6, 'SDL', 'Project SDL', '2025-10-01', '2025-12-31', 5, 80, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(7, 'Monty Pro', 'Project Monty Pro', '2025-10-01', '2025-12-31', 2, 52, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(8, 'Masblue', 'Project Masblue', '2025-10-01', '2025-12-31', NULL, 210, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(9, 'Arkim', 'Project Arkim', '2025-10-01', '2025-12-31', 7, 40, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(10, 'Acline', 'Project Acline', '2025-10-01', '2025-12-31', 7, 130, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(11, 'MasBlue', 'Project MasBlue', '2025-10-01', '2025-12-31', 7, 45, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(12, 'JARI', 'Project JARI', '2025-10-01', '2025-12-31', 7, 0, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(13, 'RH', 'Project RH', '2025-10-01', '2025-12-31', NULL, 5, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(14, 'Unzeenu', 'Project Unzeenu', '2025-10-01', '2025-12-31', 7, 55, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(15, 'Doalog', 'Project Doalog', '2025-10-01', '2025-12-31', 8, 40, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(16, 'Lichic', 'Project Lichic', '2025-10-01', '2025-12-31', 9, 35, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(17, 'NIMS', 'Project NIMS', '2025-10-01', '2025-12-31', 3, 0, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(18, 'Monty Monitor', 'Project Monty Monitor', '2025-10-01', '2025-12-31', 6, 8, 'active', 1, NULL, 1, '2025-10-09 00:00:00', '2025-10-09 00:00:00');

-- ======================
-- RESOURCE PROJECT ALLOCATIONS
-- ======================
-- INSERT INTO `resource_project_allocations` 
-- (`id`, `resource_id`, `project_id`, `month`, `total_hours`, `available_hours`, `allocated_hours`, `daily_hours`, `created_at`, `updated_at`, `deleted_at`) 
-- VALUES
-- -- MERN Team allocations
-- (1, 1, 8, '2025-10', 40, 40, 60, '[{"date":"2025-10-01","hours":8},{"date":"2025-10-02","hours":8},{"date":"2025-10-03","hours":8},{"date":"2025-10-06","hours":8},{"date":"2025-10-07","hours":8},{"date":"2025-10-08","hours":8},{"date":"2025-10-09","hours":8},{"date":"2025-10-10","hours":4}]', '2025-10-13 00:00:00', '2025-10-14 00:00:00', NULL),
-- (2, 2, 8, '2025-10', 40, 40, 50, '[{"date":"2025-10-01","hours":8},{"date":"2025-10-02","hours":8},{"date":"2025-10-03","hours":8},{"date":"2025-10-06","hours":10}]', '2025-10-09 00:00:00', '2025-10-09 00:00:00', NULL),
-- (3, 3, 8, '2025-10', 40, 40, 40, '[{"date":"2025-10-01","hours":8},{"date":"2025-10-02","hours":8},{"date":"2025-10-03","hours":8},{"date":"2025-10-04","hours":8},{"date":"2025-10-05","hours":8}]', '2025-10-09 00:00:00', '2025-10-09 00:00:00', NULL),

-- -- MOBILE Team allocations
-- (4, 7, 1, '2025-10', 40, 40, 90, '[{"date":"2025-10-01","hours":8},{"date":"2025-10-02","hours":8},{"date":"2025-10-03","hours":8},{"date":"2025-10-06","hours":8},{"date":"2025-10-07","hours":8},{"date":"2025-10-08","hours":8},{"date":"2025-10-09","hours":8},{"date":"2025-10-10","hours":8}]', '2025-10-09 00:00:00', '2025-10-09 00:00:00', NULL),
-- (5, 10, 5, '2025-10', 40, 40, 12, '[{"date":"2025-10-01","hours":4},{"date":"2025-10-02","hours":4},{"date":"2025-10-03","hours":4}]', '2025-10-09 00:00:00', '2025-10-09 00:00:00', NULL),

-- -- Python Team allocations
-- (6, 18, 10, '2025-10', 40, 10, 30, '[{"date":"2025-10-01","hours":2},{"date":"2025-10-02","hours":2},{"date":"2025-10-03","hours":2},{"date":"2025-10-06","hours":2},{"date":"2025-10-07","hours":2},{"date":"2025-10-08","hours":2},{"date":"2025-10-09","hours":2},{"date":"2025-10-10","hours":2},{"date":"2025-10-13","hours":2},{"date":"2025-10-14","hours":2},{"date":"2025-10-15","hours":2}]', '2025-10-09 00:00:00', '2025-10-09 00:00:00', NULL);

-- ======================
-- LEAVES
-- ======================
INSERT INTO `leaves` 
(`id`, `resource_id`, `type`, `start_date`, `end_date`, `number_of_days`, `hours_impacted`, `remark`, `created_at`, `updated_at`) 
VALUES
(1, 20, 'sick', '2025-10-10', '2025-10-11', 2, 16, 'Fever', '2025-10-09 19:18:22', '2025-10-09 19:18:22'),
(2, 14, 'probation', '2025-10-15', '2025-10-15', 1, 8, 'Personal work', '2025-10-09 19:18:22', '2025-10-09 19:18:22');
