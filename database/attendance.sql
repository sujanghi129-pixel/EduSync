CREATE TABLE Attendance (
    attendanceID  INT          NOT NULL AUTO_INCREMENT,
    studentID     INT          NOT NULL,
    attendDate    DATE         NOT NULL DEFAULT (CURRENT_DATE),
    isPresent     BOOLEAN      NOT NULL,
    status        VARCHAR(20)  NOT NULL,
    remarks       VARCHAR(255) NULL,
    staffID       INT          NOT NULL,
 
    PRIMARY KEY (attendanceID),
 
    -- Foreign key: must link to a real student
    CONSTRAINT fk_attendance_student
        FOREIGN KEY (studentID) REFERENCES Student(studentID),
 
    -- Foreign key: must link to a real staff member
    CONSTRAINT fk_attendance_staff
        FOREIGN KEY (staffID) REFERENCES Staff(staffID),
 
    -- Prevent duplicate attendance for the same student on the same day
    CONSTRAINT uq_student_date
        UNIQUE (studentID, attendDate),
 
    -- Only allow valid status values
    CONSTRAINT chk_status
        CHECK (status IN ('Present', 'Absent', 'Late'))
);