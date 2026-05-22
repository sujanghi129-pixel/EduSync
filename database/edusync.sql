-- ============================================================
--  EduSync — Full Database Schema
--  Niels Brock Copenhagen Business College
-- ============================================================

CREATE DATABASE IF NOT EXISTS edusync CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edusync;

-- ── tblStaff ─────────────────────────────────────────────
CREATE TABLE tblStaff (
  staffId        INT AUTO_INCREMENT PRIMARY KEY,                         -- int
  fullName       VARCHAR(100) NOT NULL,                                  -- string
  username       VARCHAR(50)  NOT NULL UNIQUE,                           -- string
  passwordHash   VARCHAR(255) NOT NULL,                                  -- string
  role           ENUM('Administrator','Teacher','Headteacher') NOT NULL, -- string
  isStaffActive  BOOLEAN      NOT NULL DEFAULT TRUE,                     -- boolean
  staffCreatedAt DATETIME     NOT NULL                                   -- date
);
-- 7 attributes | int ✅ | string ✅ | boolean ✅ | date ✅

-- ── tblGrade ─────────────────────────────────────────────
CREATE TABLE tblGrade (
  gradeId        INT AUTO_INCREMENT PRIMARY KEY,  -- int
  gradeName      VARCHAR(50)  NOT NULL UNIQUE,    -- string
  description    VARCHAR(255) DEFAULT NULL,       -- string
  isGradeActive  BOOLEAN      NOT NULL DEFAULT TRUE, -- boolean
  displayOrder   INT          NOT NULL DEFAULT 0, -- int
  gradeCreatedAt DATETIME     NOT NULL,           -- date
  updatedAt      DATETIME     DEFAULT NULL        -- date
);
-- 7 attributes | int ✅ | string ✅ | boolean ✅ | date ✅

-- ── tblClass ─────────────────────────────────────────────
CREATE TABLE tblClass (
  classId        INT AUTO_INCREMENT PRIMARY KEY,  -- int
  className      VARCHAR(50)  NOT NULL,            -- string
  gradeId        INT          NOT NULL,            -- int (FK)
  classTeacherID INT          NOT NULL,            -- int (FK)
  isClassActive  BOOLEAN      NOT NULL DEFAULT TRUE, -- boolean
  classCreatedAt DATETIME     NOT NULL,            -- date
  FOREIGN KEY (gradeId)        REFERENCES tblGrade(gradeId),
  FOREIGN KEY (classTeacherID) REFERENCES tblStaff(staffId)
);
-- 6 attributes | int ✅ | string ✅ | boolean ✅ | date ✅

-- ── tblStudent ───────────────────────────────────────────
CREATE TABLE tblStudent (
  studentId        INT AUTO_INCREMENT PRIMARY KEY,  -- int
  fullName         VARCHAR(100) NOT NULL,            -- string
  dateOfBirth      DATE         NOT NULL,            -- date
  gradeId          INT          NOT NULL,            -- int (FK)
  classId          INT          NOT NULL,            -- int (FK)
  isStudentActive  BOOLEAN      NOT NULL DEFAULT TRUE, -- boolean
  studentCreatedAt DATETIME     NOT NULL,            -- date
  FOREIGN KEY (gradeId) REFERENCES tblGrade(gradeId),
  FOREIGN KEY (classId) REFERENCES tblClass(classId)
);
-- 7 attributes | int ✅ | string ✅ | boolean ✅ | date ✅

-- ── tblAttendance ────────────────────────────────────────
CREATE TABLE tblAttendance (
  attendanceId INT AUTO_INCREMENT PRIMARY KEY,            -- int
  studentId    INT          NOT NULL,                     -- int (FK)
  classId      INT          NOT NULL,                     -- int (FK)
  markedById   INT          NOT NULL,                     -- int (FK)
  date         DATE         NOT NULL,                     -- date
  status       ENUM('present','absent','late') NOT NULL,  -- string
  notes        VARCHAR(255) DEFAULT NULL,                 -- string
  FOREIGN KEY (studentId)  REFERENCES tblStudent(studentId),
  FOREIGN KEY (classId)    REFERENCES tblClass(classId),
  FOREIGN KEY (markedById) REFERENCES tblStaff(staffId)
);
-- 7 attributes | int ✅ | string ✅ | boolean ✅ | date ✅

-- ============================================================
--  SAMPLE DATA
-- ============================================================

-- Staff (password for all = "password123")
INSERT INTO tblStaff (fullName, username, passwordHash, role, isStaffActive, staffCreatedAt) VALUES
('Sujan Ghimire',   'sujan.ghimire',   '$2b$10$wH.XuR6mKqCaP1sXKykUUeI8TbDUpAhmglxCd9Zy6N3iALHU3GElm', 'Administrator', TRUE, NOW()),
('Susma Thapa',     'susma.thapa',     '$2b$10$Qdc4NKPalfEWPwuUae5r6eFWGVQnA17qWsNcovw8v/aaMXS5FYhba', 'Teacher',        TRUE, NOW()),
('Laxman Rai',      'laxman.rai',      '$2b$10$GDMbikPZQY/4/6P2CmK1lexnsBJ1fv.cGOH06xy83TjnebLM5E8Eq', 'Teacher',        TRUE, NOW()),
('Saimon Shrestha', 'saimon.shrestha', '$2b$10$3HpGAt92u7A42GB7f4FH2O86kgEjRa2m2GtWCCnlheXT67VDc.tUS', 'Headteacher',    TRUE, NOW()),
('Roshni Karki',    'roshni.karki',    '$2b$10$qM8O5910zKMdoJeP7595ReDbDQ19FxCZuBTifiHlr/qitLspgjtSq', 'Teacher',        TRUE, NOW());

-- Grades
INSERT INTO tblGrade (gradeName, description, isGradeActive, displayOrder, gradeCreatedAt) VALUES
('Year 1', 'First year of secondary school',  TRUE, 1, NOW()),
('Year 2', 'Second year of secondary school', TRUE, 2, NOW()),
('Year 3', 'Third year of secondary school',  TRUE, 3, NOW());

-- Classes
INSERT INTO tblClass (className, gradeId, classTeacherID, isClassActive, classCreatedAt) VALUES
('1A', 1, 2, TRUE, NOW()),
('1B', 1, 3, TRUE, NOW()),
('2A', 2, 5, TRUE, NOW()),
('2B', 2, 4, TRUE, NOW()),
('3A', 3, 2, TRUE, NOW());

-- Students
INSERT INTO tblStudent (fullName, dateOfBirth, gradeId, classId, isStudentActive, studentCreatedAt) VALUES
('Alice Jensen',      '2010-03-14', 1, 1, TRUE, NOW()),
('Bjorn Nielsen',     '2010-07-22', 1, 1, TRUE, NOW()),
('Clara Madsen',      '2010-11-05', 1, 1, TRUE, NOW()),
('David Christensen', '2010-01-30', 1, 2, TRUE, NOW()),
('Emma Andersen',     '2010-09-18', 1, 2, TRUE, NOW()),
('Fiona Hansen',      '2009-04-12', 2, 3, TRUE, NOW()),
('Gustav Larsen',     '2009-08-25', 2, 3, TRUE, NOW()),
('Hanna Pedersen',    '2009-12-07', 2, 4, TRUE, NOW()),
('Ivan Sorensen',     '2009-02-19', 2, 4, TRUE, NOW()),
('Julia Thomsen',     '2008-06-03', 3, 5, TRUE, NOW()),
('Karl Rasmussen',    '2008-10-16', 3, 5, TRUE, NOW()),
('Laura Kristensen',  '2008-05-28', 3, 5, TRUE, NOW());

-- Attendance
INSERT INTO tblAttendance (studentId, classId, markedById, date, status, notes) VALUES
(1, 1, 2, CURDATE(), 'present', NULL),
(2, 1, 2, CURDATE(), 'late',    'Bus was delayed'),
(3, 1, 2, CURDATE(), 'absent',  'Sick leave'),
(4, 2, 3, CURDATE(), 'present', NULL),
(5, 2, 3, CURDATE(), 'present', NULL);