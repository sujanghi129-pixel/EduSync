CREATE TABLE Attendance (
    attendanceID  INT        PRIMARY KEY AUTO_INCREMENT,
    studentID     INT          NOT NULL,
    attendDate    DATE         NOT NULL DEFAULT (CURRENT_DATE),
    isPresent     BOOLEAN      NOT NULL,
    status        VARCHAR(20)  NOT NULL,
    remarks       VARCHAR(255) NULL,
    staffID       INT          NOT NULL, 
    -- Foreign key: must link to a real student
    CONSTRAINT fk_attendance_student
        FOREIGN KEY (studentID) REFERENCES Student(studentID),
 
    -- Foreign key: must link to a real staff member
    CONSTRAINT fk_attendance_staff
        FOREIGN KEY (staffID) REFERENCES Staff(staffID),
 
    -- Prevent duplicate attendance for the same student on the same day
    CONSTRAINT uq_student_date
        UNIQUE (studentID, attendDate)
);
INSERT INTO Attendance (studentID, attendDate, isPresent, status, remarks, staffID) VALUES
(1, '2025-04-14', TRUE,  'Present', NULL,               1),
(2, '2025-04-14', FALSE, 'Absent',  'Reported sick',    1),
(3, '2025-04-14', FALSE, 'Late',    'Arrived 15 mins late', 1),
(4, '2025-04-15', TRUE,  'Present', NULL,               2);