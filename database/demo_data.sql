-- Import cms.sql first, then import this file to add local demo accounts and complaints.
-- Re-running this file replaces only complaints with the CMP-DEMO- prefix.

INSERT INTO users (full_name, email, password, role, is_active) VALUES
    ('Maya Chen', 'maya.chen.demo@hvu.example', '$2y$10$l0VVflE5LpiEXLaoBAG73u/kutksDM7DGkxibqXdss7Cmbm/NXCVq', 'user', 1),
    ('Jordan Rivera', 'jordan.rivera.demo@hvu.example', '$2y$10$l0VVflE5LpiEXLaoBAG73u/kutksDM7DGkxibqXdss7Cmbm/NXCVq', 'user', 1),
    ('Samira Okafor', 'samira.okafor.demo@hvu.example', '$2y$10$l0VVflE5LpiEXLaoBAG73u/kutksDM7DGkxibqXdss7Cmbm/NXCVq', 'user', 1),
    ('Leo Martinez', 'leo.martinez.demo@hvu.example', '$2y$10$l0VVflE5LpiEXLaoBAG73u/kutksDM7DGkxibqXdss7Cmbm/NXCVq', 'user', 1),
    ('Amara Mensah', 'amara.mensah.demo@hvu.example', '$2y$10$l0VVflE5LpiEXLaoBAG73u/kutksDM7DGkxibqXdss7Cmbm/NXCVq', 'user', 1),
    ('Ethan Brooks', 'ethan.brooks.demo@hvu.example', '$2y$10$l0VVflE5LpiEXLaoBAG73u/kutksDM7DGkxibqXdss7Cmbm/NXCVq', 'user', 1)
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password = VALUES(password),
    role = 'user',
    is_active = 1;

DELETE FROM complaints WHERE reference_no LIKE 'CMP-DEMO-%';

INSERT INTO complaints
    (reference_no, user_id, category_id, title, description, is_anonymous, priority, status, rating, feedback_comment, created_at, updated_at, resolved_at)
SELECT d.reference_no, u.id, cat.id, d.title, d.description, d.is_anonymous, d.priority, d.status, d.rating, d.feedback_comment,
       TIMESTAMPADD(MONTH, -d.months_ago, CURRENT_TIMESTAMP),
       TIMESTAMPADD(DAY, -d.days_ago, CURRENT_TIMESTAMP),
       CASE WHEN d.status = 'resolved' THEN TIMESTAMPADD(DAY, -d.days_ago, CURRENT_TIMESTAMP) ELSE NULL END
FROM (
    SELECT 'CMP-DEMO-001' AS reference_no, 'maya.chen.demo@hvu.example' AS email, 'Academic Matters' AS category, 'Lab access after hours' AS title, 'My project team needs access to the materials lab after our scheduled class period. Could we learn the process for booking supervised evening time?' AS description, 0 AS is_anonymous, 'medium' AS priority, 'resolved' AS status, 5 AS months_ago, 130 AS days_ago, 5 AS rating, 'The booking steps were explained clearly and evening access was arranged.' AS feedback_comment
    UNION ALL SELECT 'CMP-DEMO-002', 'maya.chen.demo@hvu.example', 'Fees and Payments', 'Duplicate library charge', 'A library replacement fee appears twice on my student account, although I returned the book last week.', 0, 'low', 'resolved', 4, 95, 4, 'The duplicate charge was removed after staff checked the return record.'
    UNION ALL SELECT 'CMP-DEMO-003', 'maya.chen.demo@hvu.example', 'Facilities and Hostel', 'Project room ventilation', 'The ventilation in project room B-14 becomes very warm during afternoon sessions. The group has to leave before finishing our work.', 0, 'high', 'in_progress', 3, 50, NULL, NULL
    UNION ALL SELECT 'CMP-DEMO-004', 'jordan.rivera.demo@hvu.example', 'Student Services', 'Transfer credit review', 'I submitted my previous course outlines with my transfer application but have not received an update on the credit review.', 0, 'medium', 'resolved', 2, 28, 5, 'The registrar reviewed the documents and updated my degree plan.'
    UNION ALL SELECT 'CMP-DEMO-005', 'jordan.rivera.demo@hvu.example', 'Academic Matters', 'Seminar room conflict', 'Two seminar groups appear to have been assigned the same room on Thursday afternoon. Please confirm the correct location.', 0, 'low', 'pending', 1, 3, NULL, NULL
    UNION ALL SELECT 'CMP-DEMO-006', 'jordan.rivera.demo@hvu.example', 'Other', 'Lost property follow-up', 'I left a blue notebook in the east lecture hall after the afternoon lecture. I checked the reception desk but have not found it yet.', 1, 'low', 'resolved', 5, 128, 3, 'Campus services located the notebook and let me collect it from reception.'
    UNION ALL SELECT 'CMP-DEMO-007', 'samira.okafor.demo@hvu.example', 'Staff Conduct', 'Request for private advising time', 'I would like to discuss a classroom interaction privately with an adviser who is not directly involved in the course.', 1, 'high', 'in_progress', 4, 80, NULL, NULL
    UNION ALL SELECT 'CMP-DEMO-008', 'samira.okafor.demo@hvu.example', 'Fees and Payments', 'Scholarship award not reflected', 'My scholarship renewal was confirmed, but the latest tuition statement still shows the full balance.', 0, 'urgent', 'resolved', 3, 43, 5, 'Financial aid confirmed the award and sent me an updated statement.'
    UNION ALL SELECT 'CMP-DEMO-009', 'samira.okafor.demo@hvu.example', 'Facilities and Hostel', 'Accessible entrance door', 'The automatic door at the south entrance of the science building has not been opening consistently this week.', 0, 'high', 'resolved', 2, 25, 4, 'Facilities repaired the door sensor and checked the entrance later that day.'
    UNION ALL SELECT 'CMP-DEMO-010', 'samira.okafor.demo@hvu.example', 'Student Services', 'Name pronunciation in class list', 'Could the class roster include a pronunciation note for my name? I have shared the pronunciation with the registrar.', 0, 'low', 'pending', 1, 2, NULL, NULL
    UNION ALL SELECT 'CMP-DEMO-011', 'leo.martinez.demo@hvu.example', 'Academic Matters', 'Capstone equipment checkout', 'Our capstone team has approval to borrow two field sensors but cannot see the approved checkout in the equipment portal.', 0, 'medium', 'resolved', 5, 120, 4, 'The equipment desk added the approval to the portal and reserved the sensors.'
    UNION ALL SELECT 'CMP-DEMO-012', 'leo.martinez.demo@hvu.example', 'Facilities and Hostel', 'Workshop extractor maintenance', 'The extractor fan in the engineering workshop is making a loud intermittent sound. We stopped using the nearest bench and reported it to the lab assistant.', 0, 'urgent', 'in_progress', 4, 72, NULL, NULL
    UNION ALL SELECT 'CMP-DEMO-013', 'leo.martinez.demo@hvu.example', 'Academic Matters', 'Exam accommodation confirmation', 'I submitted my approved exam accommodation letter but have not received confirmation for the upcoming midterm.', 0, 'high', 'resolved', 3, 36, 3, 'The access office confirmed the arrangements with the course team.'
    UNION ALL SELECT 'CMP-DEMO-014', 'leo.martinez.demo@hvu.example', 'Other', 'Student society room booking', 'Our robotics society booking was approved, but the room calendar still shows the space as unavailable.', 0, 'low', 'resolved', 2, 20, 5, 'The calendar was corrected and the booking is now visible to the group.'
    UNION ALL SELECT 'CMP-DEMO-015', 'leo.martinez.demo@hvu.example', 'Fees and Payments', 'Payment receipt request', 'I paid the remaining balance at the cashier desk and would like a receipt for my records.', 0, 'low', 'resolved', 1, 16, 4, 'The cashier team emailed the receipt the same afternoon.'
    UNION ALL SELECT 'CMP-DEMO-016', 'amara.mensah.demo@hvu.example', 'Staff Conduct', 'Group project contribution concern', 'I would appreciate guidance on how to document a contribution concern in a group project before the final assessment.', 1, 'medium', 'in_progress', 5, 22, NULL, NULL
    UNION ALL SELECT 'CMP-DEMO-017', 'amara.mensah.demo@hvu.example', 'Student Services', 'Placement paperwork status', 'My community placement agreement was submitted by the host organization, but the placement portal still marks it as missing.', 0, 'medium', 'resolved', 4, 65, 2, 'The placement office matched the document to my file and cleared the warning.'
    UNION ALL SELECT 'CMP-DEMO-018', 'amara.mensah.demo@hvu.example', 'Facilities and Hostel', 'Study lounge lighting', 'Two overhead lights in the west study lounge have been out for several days, making the tables difficult to use after dusk.', 0, 'low', 'resolved', 3, 18, 5, 'The lights were replaced before the end of the week.'
    UNION ALL SELECT 'CMP-DEMO-019', 'amara.mensah.demo@hvu.example', 'Other', 'Shuttle schedule information', 'The printed shuttle timetable at the student centre differs from the schedule on the university site.', 0, 'low', 'pending', 2, 1, NULL, NULL
    UNION ALL SELECT 'CMP-DEMO-020', 'ethan.brooks.demo@hvu.example', 'Academic Matters', 'Course registration prerequisite', 'The registration system blocks a course even though the department approved my prerequisite substitution last month.', 0, 'high', 'resolved', 1, 12, 5, 'The department sent the approval to the registrar and the course became available.'
    UNION ALL SELECT 'CMP-DEMO-021', 'ethan.brooks.demo@hvu.example', 'Fees and Payments', 'Unexpected lab materials fee', 'A lab materials fee appeared after I registered for the course. I would like to understand what it covers and whether it applies to my section.', 0, 'medium', 'resolved', 5, 120, 4, 'The department shared the fee breakdown and confirmed the charge was correct.'
    UNION ALL SELECT 'CMP-DEMO-022', 'ethan.brooks.demo@hvu.example', 'Staff Conduct', 'Request for facilitated conversation', 'I am looking for support arranging a constructive conversation about feedback I received during a studio review.', 1, 'medium', 'in_progress', 4, 50, NULL, NULL
    UNION ALL SELECT 'CMP-DEMO-023', 'maya.chen.demo@hvu.example', 'Facilities and Hostel', 'Quiet floor noise in evenings', 'Repeated noise near the quiet study floor has made it difficult to prepare for evening assessments. Could the floor guidelines be reviewed?', 0, 'medium', 'resolved', 3, 20, 3, 'The library team posted a reminder and added evening staff rounds.'
    UNION ALL SELECT 'CMP-DEMO-024', 'jordan.rivera.demo@hvu.example', 'Student Services', 'Graduation audit question', 'My degree audit lists one elective as incomplete even though the course grade is visible in the transcript.', 0, 'low', 'resolved', 2, 12, 4, 'The registrar refreshed the audit and confirmed the elective now counts.'
    UNION ALL SELECT 'CMP-DEMO-025', 'samira.okafor.demo@hvu.example', 'Academic Matters', 'Research ethics training record', 'I completed the required research ethics module, but the training record has not appeared in the application portal.', 0, 'medium', 'resolved', 1, 8, 5, 'The training team synced the completion record to the portal.'
) AS d
JOIN users u ON u.email = d.email
JOIN categories cat ON cat.name = d.category;

INSERT INTO responses (complaint_id, admin_id, message, created_at)
SELECT c.id, admin.id, r.message, TIMESTAMPADD(DAY, -r.days_ago, CURRENT_TIMESTAMP)
FROM (
    SELECT 'CMP-DEMO-001' AS reference_no, 'Thanks for raising this. The lab coordinator has confirmed supervised evening sessions and shared the booking form with your team.' AS message, 126 AS days_ago
    UNION ALL SELECT 'CMP-DEMO-001', 'Your evening booking is confirmed. Please check in with the lab assistant when you arrive.', 123
    UNION ALL SELECT 'CMP-DEMO-002', 'We found the return scan and have asked Student Accounts to remove the duplicate fee.', 92
    UNION ALL SELECT 'CMP-DEMO-003', 'Facilities has scheduled an inspection of room B-14. We will update you after the ventilation check.', 46
    UNION ALL SELECT 'CMP-DEMO-004', 'The transfer credit team is reviewing your outlines. We expect to update your degree audit this week.', 25
    UNION ALL SELECT 'CMP-DEMO-006', 'The notebook was turned in to the east hall desk. It is labelled and ready for pickup.', 125
    UNION ALL SELECT 'CMP-DEMO-007', 'A student support adviser can meet privately this week. Please reply with a time that works for you.', 76
    UNION ALL SELECT 'CMP-DEMO-008', 'Your renewal is confirmed in the aid system. We are reconciling the student account now.', 40
    UNION ALL SELECT 'CMP-DEMO-009', 'A technician has repaired the sensor. Please let us know if the door does not respond normally.', 23
    UNION ALL SELECT 'CMP-DEMO-011', 'We have matched the capstone approval to your team. The equipment desk will reserve the sensors.', 116
    UNION ALL SELECT 'CMP-DEMO-012', 'The workshop has been inspected and the extractor is temporarily isolated while a replacement part is ordered.', 68
    UNION ALL SELECT 'CMP-DEMO-013', 'Your accommodation letter is on file. The access office is confirming the room and timing with the instructor.', 32
    UNION ALL SELECT 'CMP-DEMO-014', 'The society calendar entry has been corrected. Your original room reservation remains confirmed.', 18
    UNION ALL SELECT 'CMP-DEMO-015', 'We located your payment and attached the receipt to your student account.', 14
    UNION ALL SELECT 'CMP-DEMO-016', 'An adviser can help you document contributions and discuss the options with your course team.', 17
    UNION ALL SELECT 'CMP-DEMO-017', 'The placement agreement has been matched to your file. Please refresh the portal to see the updated status.', 60
    UNION ALL SELECT 'CMP-DEMO-018', 'The replacement bulbs have been installed in the west lounge.', 16
    UNION ALL SELECT 'CMP-DEMO-020', 'The approved substitution is being added to your registration record. We will confirm once it is available.', 10
    UNION ALL SELECT 'CMP-DEMO-021', 'The course coordinator has shared the itemized materials list. The fee applies to all lab sections.', 115
    UNION ALL SELECT 'CMP-DEMO-022', 'We have contacted the studio lead and are arranging a facilitated conversation.', 45
    UNION ALL SELECT 'CMP-DEMO-023', 'Library staff have added evening rounds and posted a reminder about the quiet floor.', 17
    UNION ALL SELECT 'CMP-DEMO-024', 'The audit has been refreshed and now includes your completed elective.', 9
    UNION ALL SELECT 'CMP-DEMO-025', 'Your course completion is confirmed and the portal record has been updated.', 6
) AS r
JOIN complaints c ON c.reference_no = r.reference_no
JOIN users admin ON admin.email = 'admin@cms.com';