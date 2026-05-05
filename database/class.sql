CREATE TABLE Class (
    classID INT NOT NULL AUTO_INCREMENT,
    className VARCHAR(50) NOT NULL,
    classCode VARCHAR(10) NOT NULL UNIQUE,
    capacity INT NOT NULL CHECK (capacity > 0),
    classTeacherID INT NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT TRUE,

    PRIMARY KEY (classID),

    -- Foreign key: links to Staff table
    CONSTRAINT fk_class_teacher
        FOREIGN KEY (classTeacherID) REFERENCES Staff(staffId)
);

-- Sample Data
INSERT INTO Class (className, classCode, capacity, classTeacherID, isActive)
VALUES
('Year 1 - A', 'Y1A', 30, 2, TRUE),
('Year 2 - A', 'Y2A', 28, 4, TRUE),
('Year 3 - A', 'Y3A', 32, 2, TRUE),
('Year 4 - A', 'Y4A', 25, 4, TRUE),
('Year 5 - A', 'Y5A', 20, 2, FALSE);