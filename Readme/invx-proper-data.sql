-- ======================
-- DEPARTMENTS
-- ======================
INSERT INTO `departments` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'PHP', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(2, 'MERN', '2025-09-30 19:14:08', '2025-09-30 19:14:08'),
(3, 'MOBILE', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(4, 'QA', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(5, 'Python', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(6, 'Graphics', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(7, 'Manager', '2025-10-09 00:00:00', '2025-10-09 00:00:00'),
(8, 'Executive', '2025-10-09 00:00:00', '2025-10-09 00:00:00');

-- ======================
-- RESOURCES
-- ======================
INSERT INTO `resources` (`id`, `name`, `email`, `dept_id`, `is_project_manager`, `total_hours`, `leave_hours`, `role`, `daily_capacity`, `created_at`, `updated_at`) VALUES
(1, 'Adarsh Haldar', 'adarsh.haldar@innvonix.com', 1, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(2, 'Akshay Rajput', 'akshay.rajput@innvonix.com', 2, 1, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(3, 'Anjali Sorathiya', 'anjali.sorathiya@innvonix.com', 6, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(4, 'Asutosh Sarangi', 'asutosh.sarangi@innvonix.com', 4, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(5, 'Ayushi Dhamecha', 'Ayushi.dhamecha@innvonix.com', 5, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(6, 'Bhanushankar Joshi', 'bhanu.joshi@innvonix.com', 7, 1, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(7, 'Bhaumik Gandhi', 'bhaumik.gandhi@innvonix.com', 3, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(8, 'Biren Hirapara', 'biren.hirapara@innvonix.com', 2, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(9, 'Birva Solanki', 'birva.solanki@innvonix.com', 4, 1, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(10, 'Devansh Jadhav', 'devansh.jadhav@innvonix.com', 2, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(11, 'Dharmesh Kanetiya', 'dharmesh.kanetiya@innvonix.com', 1, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(12, 'Dhruv Patel', 'dhruv.patel@innvonix.com', 5, 1, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(13, 'Himanshu Makwana', 'himanshu.makwana@innvonix.com', 1, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(14, 'Jay Purani', 'jay.purani@innvonix.com', 5, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(15, 'Jaydeep Patel', 'jaydeep.patel@innvonix.com', 2, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(16, 'Jenil Prajapati', 'Jenil.prajapati@innvonix.com', 5, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(17, 'Karan Parihar', 'karan.parihar@innvonix.com', 3, 1, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(18, 'Kedar Soneji', 'kedar.soneji@innvonix.com', 4, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(19, 'Ketul Patel', 'ketul.patel@innvonix.com', 1, 1, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(20, 'Mehul Valand', 'mehul.valand@innvonix.com', 3, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(21, 'Mehul Vishroliya', 'mehul.vishroliya@innvonix.com', 3, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(22, 'Parth Raval', 'parth.raval@innvonix.com', 3, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(23, 'Parth Lalcheta', 'Parth.lalcheta@innvonix.com', 1, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(24, 'Purav Thakkar', 'purav@innvonix.com', 8, 1, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(25, 'Purvi Patel', 'purvi.patel@innvonix.com', 1, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(26, 'Raj Patel', 'raj.patel@innvonix.com', 2, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(27, 'Ramkrishna Sharma', 'ramkrishna.sharma@innvonix.com', 3, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(28, 'Rishit Parikh', 'rishit.parikh@innvonix.com', 2, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(29, 'Sahil Rathod', 'sahil.rathod@innvonix.com', 2, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(30, 'Sanjay Gupta', 'sanjay.gupta@innvonix.com', 6, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(31, 'Satish Prajapati', 'satish.prajapati@innvonix.com', 6, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(32, 'Shrungi Sangani', 'shrungi.sangani@innvonix.com', 1, 1, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(33, 'Tushar Viradiya', 'Tushar.Viradiya@innvonix.com', 1, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(34, 'Vikas Prajapati', 'vikas.prajapati@innvonix.com', 2, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(35, 'Ronald Thayil', 'ronald.thayil@innvonix.com', 2, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW()),
(36, 'Mayur Patel', 'mayur.patel@innvonix.com', 4, 0, 0.00, 0.00, NULL, 8.00, NOW(), NOW());



-- =====================================
-- For Resource
-- =====================================

ALTER TABLE `resources` ADD `status` TINYINT NOT NULL DEFAULT '1' COMMENT '0 = inactive, 1 = active' AFTER `daily_capacity`;