-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2026 at 11:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edusync`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendanceID` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `attendDate` date NOT NULL DEFAULT curdate(),
  `isPresent` tinyint(1) NOT NULL,
  `status` varchar(20) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `staffID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendanceID`, `studentID`, `attendDate`, `isPresent`, `status`, `remarks`, `staffID`) VALUES
(1, 1, '2025-04-14', 1, 'Present', NULL, 1),
(2, 2, '2025-04-14', 0, 'Absent', 'Reported sick', 1),
(3, 3, '2025-04-14', 0, 'Late', 'Arrived 15 mins late', 1),
(4, 4, '2025-04-15', 1, 'Present', NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `classID` int(11) NOT NULL,
  `className` varchar(50) NOT NULL,
  `classCode` varchar(10) NOT NULL,
  `capacity` int(11) NOT NULL CHECK (`capacity` > 0),
  `classTeacherID` int(11) NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`classID`, `className`, `classCode`, `capacity`, `classTeacherID`, `isActive`) VALUES
(1, 'Year 1 - A', 'Y1A', 30, 2, 1),
(2, 'Year 2 - A', 'Y2A', 28, 4, 1),
(3, 'Year 3 - A', 'Y3A', 32, 2, 1),
(4, 'Year 4 - A', 'Y4A', 25, 4, 1),
(5, 'Year 5 - A', 'Y5A', 20, 2, 0);

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--

CREATE TABLE `grade` (
  `gradeID` int(11) NOT NULL,
  `gradeName` varchar(50) NOT NULL,
  `gradeCode` char(3) NOT NULL,
  `minAge` int(11) NOT NULL CHECK (`minAge` >= 4),
  `maxAge` int(11) NOT NULL CHECK (`maxAge` <= 18),
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `createdAt` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade`
--

INSERT INTO `grade` (`gradeID`, `gradeName`, `gradeCode`, `minAge`, `maxAge`, `isActive`, `createdAt`) VALUES
(1, 'Year 1', 'Y01', 5, 6, 1, '2026-04-29'),
(2, 'Year 2', 'Y02', 6, 7, 1, '2026-04-29'),
(3, 'Year 3', 'Y03', 7, 8, 1, '2026-04-29'),
(4, 'Year 4', 'Y04', 8, 9, 1, '2026-04-29'),
(5, 'Year 5', 'Y05', 9, 10, 1, '2026-04-29');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staffId` int(11) NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `passwordHash` varchar(255) NOT NULL,
  `role` enum('Administrator','Teacher','Headteacher') NOT NULL,
  `createdAt` date NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staffId`, `fullName`, `email`, `passwordHash`, `role`, `createdAt`, `isActive`) VALUES
(1, 'Sujan Ghimire', 'sujan@school.com', '0b14d501a594442a01c6859541bcb3e8164d183d32937b851835442f69d5c94e', 'Administrator', '2024-09-01', 1),
(2, 'Susma Pandey', 'susma@school.com', '6cf615d5bcaac778352a8f1f3360d23f02f34ec182e259897fd6ce485d7870d4', 'Teacher', '2024-09-01', 1),
(3, 'Laxman Giri', 'laxman@school.com', '5906ac361a137e2d286465cd6588ebb5ac3f5ae955001100bc41577c3d751764', 'Headteacher', '2024-09-01', 1),
(4, 'Saimon Hasan', 'saimon@school.com', 'b97873a40f73abedd8d685a7cd5e5f85e4a9cfb83eac26886640a0813850122b', 'Teacher', '2024-09-02', 1),
(5, 'Dibya Roshni Shau', 'dibya@school.com', '8b2c86ea9cf2ea4eb517fd1e06b74f399e7fec0fef92e3b482a6cf2e2b092023', 'Teacher', '2024-09-02', 0);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentId` int(11) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `Age` int(11) NOT NULL CHECK (`Age` >= 4 and `Age` <= 18),
  `IsEnrolled` tinyint(1) NOT NULL DEFAULT 1,
  `GradeID` int(11) DEFAULT NULL,
  `ClassID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentId`, `FullName`, `DateOfBirth`, `Age`, `IsEnrolled`, `GradeID`, `ClassID`) VALUES
(1, 'Rajan Acharya', '2010-05-15', 14, 1, 1, 1),
(2, 'Sita Thapa', '2012-08-20', 12, 1, 2, 2),
(3, 'Hari Sharma', '2009-11-30', 15, 1, 3, 3),
(4, 'Gita Rai', '2011-03-10', 13, 1, 4, 4),
(5, 'Bikash Karki', '2013-01-25', 11, 1, 5, 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendanceID`),
  ADD UNIQUE KEY `uq_student_date` (`studentID`,`attendDate`),
  ADD KEY `fk_attendance_staff` (`staffID`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`classID`),
  ADD UNIQUE KEY `classCode` (`classCode`),
  ADD KEY `fk_class_teacher` (`classTeacherID`);

--
-- Indexes for table `grade`
--
ALTER TABLE `grade`
  ADD PRIMARY KEY (`gradeID`),
  ADD UNIQUE KEY `gradeName` (`gradeName`),
  ADD UNIQUE KEY `gradeCode` (`gradeCode`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staffId`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentId`),
  ADD KEY `FK_Student_Grade` (`GradeID`),
  ADD KEY `FK_Student_Class` (`ClassID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendanceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `classID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grade`
--
ALTER TABLE `grade`
  MODIFY `gradeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staffId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `StudentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_staff` FOREIGN KEY (`staffID`) REFERENCES `staff` (`staffId`),
  ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`studentID`) REFERENCES `student` (`StudentId`);

--
-- Constraints for table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `fk_class_teacher` FOREIGN KEY (`classTeacherID`) REFERENCES `staff` (`staffId`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `FK_Student_Class` FOREIGN KEY (`ClassID`) REFERENCES `class` (`classID`),
  ADD CONSTRAINT `FK_Student_Grade` FOREIGN KEY (`GradeID`) REFERENCES `grade` (`gradeID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
