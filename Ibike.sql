-- ================================================
-- Database: Bike Rental Management System (Ibike)
-- Created: January 17, 2026
-- MSSQL Server Database Schema
-- ================================================

-- Create Database if it doesn't exist
IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = 'BikeRental')
BEGIN
    CREATE DATABASE BikeRental;
    PRINT 'Database BikeRental created successfully!';
END
ELSE
BEGIN
    PRINT 'Database BikeRental already exists.';
END
GO

-- Use the BikeRental Database
USE BikeRental;
GO

PRINT 'Using BikeRental database...';
GO

-- Drop tables if they exist (in reverse order of dependencies)
IF OBJECT_ID('dbo.Returns', 'U') IS NOT NULL DROP TABLE dbo.Returns;
IF OBJECT_ID('dbo.Rentals', 'U') IS NOT NULL DROP TABLE dbo.Rentals;
IF OBJECT_ID('dbo.Bike', 'U') IS NOT NULL DROP TABLE dbo.Bike;
IF OBJECT_ID('dbo.Member', 'U') IS NOT NULL DROP TABLE dbo.Member;
IF OBJECT_ID('dbo.Admin', 'U') IS NOT NULL DROP TABLE dbo.Admin;
GO

-- ================================================
-- Table: Member
-- Description: Stores customer/member information
-- ================================================
CREATE TABLE Member (
    Member_ID INT PRIMARY KEY IDENTITY(1,1),
    first_name NVARCHAR(50) NOT NULL,
    last_name NVARCHAR(50) NOT NULL,
    contact_number NVARCHAR(20) NOT NULL,
    email NVARCHAR(100) NOT NULL UNIQUE,
    address NVARCHAR(255),
    date_joined DATE NOT NULL DEFAULT GETDATE(),
    CONSTRAINT CHK_Member_Email CHECK (email LIKE '%@%')
);
GO

-- ================================================
-- Table: Admin
-- Description: Stores administrator accounts
-- ================================================
CREATE TABLE Admin (
    Admin_ID INT PRIMARY KEY IDENTITY(1,1),
    username NVARCHAR(50) NOT NULL UNIQUE,
    password NVARCHAR(255) NOT NULL,
    full_name NVARCHAR(100) NOT NULL,
    role NVARCHAR(50) NOT NULL DEFAULT 'Admin',
    CONSTRAINT CHK_Admin_Role CHECK (role IN ('Admin', 'Super Admin', 'Manager', 'Staff'))
);
GO

-- ================================================
-- Table: Bike
-- Description: Stores bike inventory information
-- ================================================
CREATE TABLE Bike (
    Bike_ID INT PRIMARY KEY IDENTITY(1,1),
    admin_id INT NOT NULL,
    bike_name_model NVARCHAR(100) NOT NULL,
    bike_type NVARCHAR(50) NOT NULL,
    availability_status NVARCHAR(20) NOT NULL DEFAULT 'Available',
    hourly_rate DECIMAL(10, 2) NOT NULL,
    date_added DATE NOT NULL DEFAULT GETDATE(),
    CONSTRAINT FK_Bike_Admin FOREIGN KEY (admin_id) REFERENCES Admin(Admin_ID),
    CONSTRAINT CHK_Bike_Status CHECK (availability_status IN ('Available', 'Rented', 'Maintenance', 'Retired')),
    CONSTRAINT CHK_Bike_Type CHECK (bike_type IN ('Mountain', 'Road', 'Hybrid', 'Electric', 'City', 'BMX', 'Cruiser')),
    CONSTRAINT CHK_Bike_Rate CHECK (hourly_rate > 0)
);
GO

-- ================================================
-- Table: Rentals
-- Description: Stores bike rental transactions
-- ================================================
CREATE TABLE Rentals (
    Rental_ID INT PRIMARY KEY IDENTITY(1,1),
    member_id INT NOT NULL,
    bike_id INT NOT NULL,
    admin_id INT NOT NULL,
    rental_date DATE NOT NULL DEFAULT GETDATE(),
    rental_time TIME NOT NULL DEFAULT CONVERT(TIME, GETDATE()),
    return_date DATE,
    status NVARCHAR(20) NOT NULL DEFAULT 'Active',
    CONSTRAINT FK_Rentals_Member FOREIGN KEY (member_id) REFERENCES Member(Member_ID),
    CONSTRAINT FK_Rentals_Bike FOREIGN KEY (bike_id) REFERENCES Bike(Bike_ID),
    CONSTRAINT FK_Rentals_Admin FOREIGN KEY (admin_id) REFERENCES Admin(Admin_ID),
    CONSTRAINT CHK_Rentals_Status CHECK (status IN ('Active', 'Completed', 'Cancelled', 'Overdue'))
);
GO

-- ================================================
-- Table: Returns
-- Description: Stores bike return information
-- ================================================
CREATE TABLE Returns (
    Return_ID INT PRIMARY KEY IDENTITY(1,1),
    rental_id INT NOT NULL UNIQUE,
    admin_id INT NOT NULL,
    return_date DATE NOT NULL DEFAULT GETDATE(),
    return_time TIME NOT NULL DEFAULT CONVERT(TIME, GETDATE()),
    condition NVARCHAR(50) NOT NULL,
    remarks NVARCHAR(500),
    CONSTRAINT FK_Returns_Rental FOREIGN KEY (rental_id) REFERENCES Rentals(Rental_ID),
    CONSTRAINT FK_Returns_Admin FOREIGN KEY (admin_id) REFERENCES Admin(Admin_ID),
    CONSTRAINT CHK_Returns_Condition CHECK (condition IN ('Excellent', 'Good', 'Fair', 'Poor', 'Damaged'))
);
GO

-- ================================================
-- Create Indexes for Performance
-- ================================================
CREATE INDEX IX_Member_Email ON Member(email);
CREATE INDEX IX_Bike_Status ON Bike(availability_status);
CREATE INDEX IX_Bike_Type ON Bike(bike_type);
CREATE INDEX IX_Rentals_Member ON Rentals(member_id);
CREATE INDEX IX_Rentals_Bike ON Rentals(bike_id);
CREATE INDEX IX_Rentals_Status ON Rentals(status);
CREATE INDEX IX_Rentals_Date ON Rentals(rental_date);
CREATE INDEX IX_Returns_Rental ON Returns(rental_id);
GO

