CREATE TABLE Student (
    StudentId     INT          NOT NULL AUTO_INCREMENT,
    FullName      VARCHAR(100) NOT NULL,
    DateOfBirth   DATE         NOT NULL,
    Age           INT          NOT NULL CHECK (Age >= 4 AND Age <= 18),
    IsEnrolled    BOOLEAN      NOT NULL DEFAULT TRUE,
    GradeID       INT,
    ClassID       INT,

    CONSTRAINT PK_Student     PRIMARY KEY (StudentId),
    CONSTRAINT FK_Student_Grade  FOREIGN KEY (GradeID) REFERENCES Grade(GradeID),
    CONSTRAINT FK_Student_Class  FOREIGN KEY (ClassID) REFERENCES Class(ClassID)
);

INSERT INTO Student 
(StudentId, FullName, DateOfBirth, Age, IsEnrolled, GradeID, ClassID)
VALUES
(1, 'Rajan Acharya', '2010-05-15', 14, TRUE, 1, 1),
(2, 'Sita Thapa', '2012-08-20', 12, TRUE, 2, 2),
(3, 'Hari Sharma', '2009-11-30', 15, TRUE, 3, 3),
(4, 'Gita Rai', '2011-03-10', 13, TRUE, 4, 4),
(5, 'Bikash Karki', '2013-01-25', 11, TRUE, 5, 5);