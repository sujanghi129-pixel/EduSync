-- ============================================================
--  EduSync — Stored Procedures
--  Run this file AFTER edusync.sql
--  Contains all stored procedures for all 5 components.
-- ============================================================

USE edusync;

-- ============================================================
--  STORED PROCEDURES
--  Staff Management Component — Sujan Ghimire
-- ============================================================

DELIMITER $$

-- ── sp_GetAllStaff ───────────────────────────────────────
-- Retrieves all staff records ordered by staffId ascending.
-- Used by the staff list page (index.php).
CREATE PROCEDURE sp_GetAllStaff()
BEGIN
    SELECT staffId, fullName, username, role, isStaffActive, staffCreatedAt
    FROM tblStaff
    ORDER BY staffId ASC;
END$$

-- ── sp_GetStaffById ──────────────────────────────────────
-- Retrieves a single staff record by their unique ID.
-- Used by edit.php and delete.php.
--
-- @param p_staffId INT - The unique staff identifier.
CREATE PROCEDURE sp_GetStaffById(IN p_staffId INT)
BEGIN
    SELECT staffId, fullName, username, role, isStaffActive, staffCreatedAt
    FROM tblStaff
    WHERE staffId = p_staffId;
END$$

-- ── sp_GetStaffByUsername ────────────────────────────────
-- Retrieves a staff record by username.
-- Used by the login page to authenticate the user.
-- Returns all fields including passwordHash for verification.
--
-- @param p_username VARCHAR(50) - The username to look up.
CREATE PROCEDURE sp_GetStaffByUsername(IN p_username VARCHAR(50))
BEGIN
    SELECT staffId, fullName, username, passwordHash, role, isStaffActive
    FROM tblStaff
    WHERE username = p_username
      AND isStaffActive = TRUE;
END$$

-- ── sp_AddStaff ──────────────────────────────────────────
-- Inserts a new staff record into tblStaff.
-- Password must already be hashed before calling this procedure.
-- Account is set to active by default.
--
-- @param p_fullName     VARCHAR(100) - Staff full name.
-- @param p_username     VARCHAR(50)  - Unique login username.
-- @param p_passwordHash VARCHAR(255) - Bcrypt hashed password.
-- @param p_role         ENUM         - Administrator, Teacher or Headteacher.
CREATE PROCEDURE sp_AddStaff(
    IN p_fullName     VARCHAR(100),
    IN p_username     VARCHAR(50),
    IN p_passwordHash VARCHAR(255),
    IN p_role         ENUM('Administrator','Teacher','Headteacher')
)
BEGIN
    INSERT INTO tblStaff (fullName, username, passwordHash, role, isStaffActive, staffCreatedAt)
    VALUES (p_fullName, p_username, p_passwordHash, p_role, TRUE, NOW());
END$$

-- ── sp_UpdateStaff ───────────────────────────────────────
-- Updates a staff record's name, username and role.
-- Does NOT update the password — use sp_UpdateStaffWithPassword for that.
--
-- @param p_staffId  INT          - The ID of the record to update.
-- @param p_fullName VARCHAR(100) - Updated full name.
-- @param p_username VARCHAR(50)  - Updated username.
-- @param p_role     ENUM         - Updated role.
CREATE PROCEDURE sp_UpdateStaff(
    IN p_staffId  INT,
    IN p_fullName VARCHAR(100),
    IN p_username VARCHAR(50),
    IN p_role     ENUM('Administrator','Teacher','Headteacher')
)
BEGIN
    UPDATE tblStaff
    SET fullName = p_fullName,
        username = p_username,
        role     = p_role
    WHERE staffId = p_staffId;
END$$

-- ── sp_UpdateStaffWithPassword ───────────────────────────
-- Updates a staff record including a new password hash.
-- Used when the Administrator changes a staff member's password.
--
-- @param p_staffId      INT          - The ID of the record to update.
-- @param p_fullName     VARCHAR(100) - Updated full name.
-- @param p_username     VARCHAR(50)  - Updated username.
-- @param p_role         ENUM         - Updated role.
-- @param p_passwordHash VARCHAR(255) - New bcrypt hashed password.
CREATE PROCEDURE sp_UpdateStaffWithPassword(
    IN p_staffId      INT,
    IN p_fullName     VARCHAR(100),
    IN p_username     VARCHAR(50),
    IN p_role         ENUM('Administrator','Teacher','Headteacher'),
    IN p_passwordHash VARCHAR(255)
)
BEGIN
    UPDATE tblStaff
    SET fullName     = p_fullName,
        username     = p_username,
        role         = p_role,
        passwordHash = p_passwordHash
    WHERE staffId = p_staffId;
END$$

-- ── sp_DeleteStaff ───────────────────────────────────────
-- Permanently deletes a staff record from tblStaff.
-- Should only be called after confirming the staff member
-- is not assigned to any active class (see sp_IsStaffAssignedToClass).
--
-- @param p_staffId INT - The ID of the staff record to delete.
CREATE PROCEDURE sp_DeleteStaff(IN p_staffId INT)
BEGIN
    DELETE FROM tblStaff
    WHERE staffId = p_staffId;
END$$

-- ── sp_ToggleStaffStatus ─────────────────────────────────
-- Flips a staff member's isStaffActive field between TRUE and FALSE.
-- Used by the Activate / Deactivate button on the staff list.
--
-- @param p_staffId INT - The ID of the staff record to toggle.
CREATE PROCEDURE sp_ToggleStaffStatus(IN p_staffId INT)
BEGIN
    UPDATE tblStaff
    SET isStaffActive = NOT isStaffActive
    WHERE staffId = p_staffId;
END$$

-- ── sp_CheckUsernameExists ───────────────────────────────
-- Returns 1 if the given username is already taken by another
-- staff member, or 0 if it is available.
-- The excludeId parameter allows the current record to be
-- excluded when checking on edit forms.
--
-- @param p_username  VARCHAR(50) - The username to check.
-- @param p_excludeId INT         - StaffId to exclude (0 for add forms).
CREATE PROCEDURE sp_CheckUsernameExists(
    IN p_username  VARCHAR(50),
    IN p_excludeId INT
)
BEGIN
    SELECT COUNT(*) AS taken
    FROM tblStaff
    WHERE username = p_username
      AND staffId != p_excludeId;
END$$

-- ── sp_IsStaffAssignedToClass ────────────────────────────
-- Returns 1 if the staff member is currently assigned as
-- the class teacher of at least one active class, or 0 if not.
-- Used before allowing deletion or deactivation.
--
-- @param p_staffId INT - The ID of the staff member to check.
CREATE PROCEDURE sp_IsStaffAssignedToClass(IN p_staffId INT)
BEGIN
    SELECT COUNT(*) AS assigned
    FROM tblClass
    WHERE classTeacherID = p_staffId
      AND isClassActive  = TRUE;
END$$

DELIMITER ;

-- ============================================================
--  STORED PROCEDURES — Grade Management (Roshni Karki)
-- ============================================================

DELIMITER $$

CREATE PROCEDURE sp_GetAllGrades()
BEGIN
    SELECT g.gradeId, g.gradeName, g.description, g.isGradeActive,
           g.displayOrder, g.gradeCreatedAt,
           COUNT(c.classId) AS classCount
    FROM tblGrade g
    LEFT JOIN tblClass c ON c.gradeId = g.gradeId AND c.isClassActive = TRUE
    GROUP BY g.gradeId
    ORDER BY g.displayOrder ASC;
END$$

CREATE PROCEDURE sp_GetGradeById(IN p_gradeId INT)
BEGIN
    SELECT gradeId, gradeName, description, isGradeActive, displayOrder, gradeCreatedAt
    FROM tblGrade
    WHERE gradeId = p_gradeId;
END$$

CREATE PROCEDURE sp_GetActiveGrades()
BEGIN
    SELECT gradeId, gradeName
    FROM tblGrade
    WHERE isGradeActive = TRUE
    ORDER BY displayOrder ASC;
END$$

CREATE PROCEDURE sp_AddGrade(
    IN p_gradeName   VARCHAR(50),
    IN p_description VARCHAR(255),
    IN p_displayOrder INT
)
BEGIN
    INSERT INTO tblGrade (gradeName, description, isGradeActive, displayOrder, gradeCreatedAt)
    VALUES (p_gradeName, p_description, TRUE, p_displayOrder, NOW());
END$$

CREATE PROCEDURE sp_UpdateGrade(
    IN p_gradeId      INT,
    IN p_gradeName    VARCHAR(50),
    IN p_description  VARCHAR(255),
    IN p_displayOrder INT
)
BEGIN
    UPDATE tblGrade
    SET gradeName    = p_gradeName,
        description  = p_description,
        displayOrder = p_displayOrder,
        updatedAt    = NOW()
    WHERE gradeId = p_gradeId;
END$$

CREATE PROCEDURE sp_DeleteGrade(IN p_gradeId INT)
BEGIN
    DELETE FROM tblGrade WHERE gradeId = p_gradeId;
END$$

CREATE PROCEDURE sp_CheckGradeNameExists(
    IN p_gradeName VARCHAR(50),
    IN p_excludeId INT
)
BEGIN
    SELECT COUNT(*) AS taken
    FROM tblGrade
    WHERE gradeName = p_gradeName
      AND gradeId  != p_excludeId;
END$$

CREATE PROCEDURE sp_CountClassesInGrade(IN p_gradeId INT)
BEGIN
    SELECT COUNT(*) AS classCount
    FROM tblClass
    WHERE gradeId = p_gradeId AND isClassActive = TRUE;
END$$

CREATE PROCEDURE sp_CountStudentsInGrade(IN p_gradeId INT)
BEGIN
    SELECT COUNT(*) AS studentCount
    FROM tblStudent
    WHERE gradeId = p_gradeId AND isStudentActive = TRUE;
END$$

DELIMITER ;

-- ============================================================
--  STORED PROCEDURES — Class Management (Saimon Shrestha)
-- ============================================================

DELIMITER $$

CREATE PROCEDURE sp_GetAllClasses()
BEGIN
    SELECT c.classId, c.className, c.isClassActive, c.classCreatedAt,
           g.gradeName, g.gradeId,
           s.fullName AS teacherName, s.staffId
    FROM tblClass c
    LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
    LEFT JOIN tblStaff s ON s.staffId = c.classTeacherID
    ORDER BY g.displayOrder ASC, c.className ASC;
END$$

CREATE PROCEDURE sp_GetClassById(IN p_classId INT)
BEGIN
    SELECT c.*, g.gradeName, s.fullName AS teacherName
    FROM tblClass c
    LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
    LEFT JOIN tblStaff s ON s.staffId = c.classTeacherID
    WHERE c.classId = p_classId;
END$$

CREATE PROCEDURE sp_GetClassesByGrade(IN p_gradeId INT)
BEGIN
    SELECT classId, className
    FROM tblClass
    WHERE gradeId = p_gradeId AND isClassActive = TRUE
    ORDER BY className ASC;
END$$

CREATE PROCEDURE sp_GetClassByTeacher(IN p_staffId INT)
BEGIN
    SELECT c.classId, c.className, g.gradeName, g.gradeId
    FROM tblClass c
    LEFT JOIN tblGrade g ON g.gradeId = c.gradeId
    WHERE c.classTeacherID = p_staffId AND c.isClassActive = TRUE
    LIMIT 1;
END$$

CREATE PROCEDURE sp_AddClass(
    IN p_className      VARCHAR(50),
    IN p_gradeId        INT,
    IN p_classTeacherID INT
)
BEGIN
    INSERT INTO tblClass (className, gradeId, classTeacherID, isClassActive, classCreatedAt)
    VALUES (p_className, p_gradeId, p_classTeacherID, TRUE, NOW());
END$$

CREATE PROCEDURE sp_UpdateClass(
    IN p_classId        INT,
    IN p_className      VARCHAR(50),
    IN p_gradeId        INT,
    IN p_classTeacherID INT
)
BEGIN
    UPDATE tblClass
    SET className      = p_className,
        gradeId        = p_gradeId,
        classTeacherID = p_classTeacherID
    WHERE classId = p_classId;
END$$

CREATE PROCEDURE sp_DeleteClass(IN p_classId INT)
BEGIN
    DELETE FROM tblClass WHERE classId = p_classId;
END$$

CREATE PROCEDURE sp_ToggleClassStatus(IN p_classId INT)
BEGIN
    UPDATE tblClass
    SET isClassActive = NOT isClassActive
    WHERE classId = p_classId;
END$$

CREATE PROCEDURE sp_CheckClassNameExists(
    IN p_className VARCHAR(50),
    IN p_gradeId   INT,
    IN p_excludeId INT
)
BEGIN
    SELECT COUNT(*) AS taken
    FROM tblClass
    WHERE className = p_className
      AND gradeId   = p_gradeId
      AND classId  != p_excludeId;
END$$

CREATE PROCEDURE sp_CountStudentsInClass(IN p_classId INT)
BEGIN
    SELECT COUNT(*) AS studentCount
    FROM tblStudent
    WHERE classId = p_classId AND isStudentActive = TRUE;
END$$

DELIMITER ;

-- ============================================================
--  STORED PROCEDURES — Student Management (Susma Thapa)
-- ============================================================

DELIMITER $$

CREATE PROCEDURE sp_GetAllStudents()
BEGIN
    SELECT s.studentId, s.fullName, s.dateOfBirth, s.isStudentActive, s.studentCreatedAt,
           g.gradeName, c.className
    FROM tblStudent s
    LEFT JOIN tblGrade g ON g.gradeId = s.gradeId
    LEFT JOIN tblClass c ON c.classId = s.classId
    ORDER BY g.displayOrder ASC, c.className ASC, s.fullName ASC;
END$$

CREATE PROCEDURE sp_GetStudentById(IN p_studentId INT)
BEGIN
    SELECT s.*, g.gradeName, c.className
    FROM tblStudent s
    LEFT JOIN tblGrade g ON g.gradeId = s.gradeId
    LEFT JOIN tblClass c ON c.classId = s.classId
    WHERE s.studentId = p_studentId;
END$$

CREATE PROCEDURE sp_GetStudentsByClass(IN p_classId INT)
BEGIN
    SELECT studentId, fullName, dateOfBirth, studentCreatedAt
    FROM tblStudent
    WHERE classId = p_classId AND isStudentActive = TRUE
    ORDER BY fullName ASC;
END$$

CREATE PROCEDURE sp_AddStudent(
    IN p_fullName    VARCHAR(100),
    IN p_dateOfBirth DATE,
    IN p_gradeId     INT,
    IN p_classId     INT
)
BEGIN
    INSERT INTO tblStudent (fullName, dateOfBirth, gradeId, classId, isStudentActive, studentCreatedAt)
    VALUES (p_fullName, p_dateOfBirth, p_gradeId, p_classId, TRUE, NOW());
END$$

CREATE PROCEDURE sp_UpdateStudent(
    IN p_studentId   INT,
    IN p_fullName    VARCHAR(100),
    IN p_dateOfBirth DATE,
    IN p_gradeId     INT,
    IN p_classId     INT
)
BEGIN
    UPDATE tblStudent
    SET fullName    = p_fullName,
        dateOfBirth = p_dateOfBirth,
        gradeId     = p_gradeId,
        classId     = p_classId
    WHERE studentId = p_studentId;
END$$

CREATE PROCEDURE sp_DeleteStudent(IN p_studentId INT)
BEGIN
    -- Delete attendance records first to maintain referential integrity
    DELETE FROM tblAttendance WHERE studentId = p_studentId;
    DELETE FROM tblStudent    WHERE studentId = p_studentId;
END$$

CREATE PROCEDURE sp_ToggleStudentStatus(IN p_studentId INT)
BEGIN
    UPDATE tblStudent
    SET isStudentActive = NOT isStudentActive
    WHERE studentId = p_studentId;
END$$

CREATE PROCEDURE sp_CountStudentAttendance(IN p_studentId INT)
BEGIN
    SELECT COUNT(*) AS attendanceCount
    FROM tblAttendance
    WHERE studentId = p_studentId;
END$$

DELIMITER ;

-- ============================================================
--  STORED PROCEDURES — Attendance (Laxman Rai)
-- ============================================================

DELIMITER $$

CREATE PROCEDURE sp_GetAttendanceReport(
    IN p_classId  INT,
    IN p_dateFrom DATE,
    IN p_dateTo   DATE
)
BEGIN
    SELECT a.attendanceId, a.date, a.status, a.notes,
           s.fullName  AS studentName,
           c.className, g.gradeName,
           st.fullName AS markedByName
    FROM tblAttendance a
    LEFT JOIN tblStudent s  ON s.studentId  = a.studentId
    LEFT JOIN tblClass   c  ON c.classId    = a.classId
    LEFT JOIN tblGrade   g  ON g.gradeId    = c.gradeId
    LEFT JOIN tblStaff   st ON st.staffId   = a.markedById
    WHERE (p_classId IS NULL OR a.classId = p_classId)
      AND a.date BETWEEN p_dateFrom AND p_dateTo
    ORDER BY a.date DESC, g.displayOrder ASC, c.className ASC, s.fullName ASC;
END$$

CREATE PROCEDURE sp_GetAttendanceByClassDate(
    IN p_classId INT,
    IN p_date    DATE
)
BEGIN
    SELECT studentId, status, notes
    FROM tblAttendance
    WHERE classId = p_classId AND date = p_date;
END$$

CREATE PROCEDURE sp_GetAttendanceById(IN p_attendanceId INT)
BEGIN
    SELECT a.*, s.fullName AS studentName, c.className, g.gradeName
    FROM tblAttendance a
    LEFT JOIN tblStudent s ON s.studentId = a.studentId
    LEFT JOIN tblClass   c ON c.classId   = a.classId
    LEFT JOIN tblGrade   g ON g.gradeId   = c.gradeId
    WHERE a.attendanceId = p_attendanceId;
END$$

CREATE PROCEDURE sp_DeleteAttendanceByClassDate(
    IN p_classId INT,
    IN p_date    DATE
)
BEGIN
    DELETE FROM tblAttendance
    WHERE classId = p_classId AND date = p_date;
END$$

CREATE PROCEDURE sp_AddAttendance(
    IN p_studentId  INT,
    IN p_classId    INT,
    IN p_markedById INT,
    IN p_date       DATE,
    IN p_status     ENUM('present','absent','late'),
    IN p_notes      VARCHAR(255)
)
BEGIN
    INSERT INTO tblAttendance (studentId, classId, markedById, date, status, notes)
    VALUES (p_studentId, p_classId, p_markedById, p_date, p_status, p_notes);
END$$

CREATE PROCEDURE sp_UpdateAttendance(
    IN p_attendanceId INT,
    IN p_status       ENUM('present','absent','late'),
    IN p_notes        VARCHAR(255)
)
BEGIN
    UPDATE tblAttendance
    SET status = p_status,
        notes  = p_notes
    WHERE attendanceId = p_attendanceId;
END$$

CREATE PROCEDURE sp_GetTodayAttendanceSummary(
    IN p_classId INT,
    IN p_date    DATE
)
BEGIN
    SELECT status, COUNT(*) AS cnt
    FROM tblAttendance
    WHERE classId = p_classId AND date = p_date
    GROUP BY status;
END$$

DELIMITER ;
