

-- Create Database
USE master;
GO

IF EXISTS (SELECT name FROM sys.databases WHERE name = N'BikeRental')
BEGIN
    ALTER DATABASE BikeRental SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE BikeRental;
END
GO

CREATE DATABASE BikeRental;
GO

USE BikeRental;
GO

-- =============================================
-- Table: Admin
-- Description: Stores administrator information
-- =============================================
CREATE TABLE Admin (
    Admin_ID INT PRIMARY KEY IDENTITY(1,1),
    username NVARCHAR(50) UNIQUE NOT NULL,
    password NVARCHAR(255) NOT NULL,
    full_name NVARCHAR(100) NOT NULL,
    role NVARCHAR(50)
);
GO

-- =============================================
-- Table: Member
-- Description: Stores member/customer information
-- =============================================
CREATE TABLE Member (
    Member_ID INT PRIMARY KEY IDENTITY(1,1),
    first_name NVARCHAR(50) NOT NULL,
    last_name NVARCHAR(50) NOT NULL,
    contact_number NVARCHAR(20),
    email NVARCHAR(100) UNIQUE NOT NULL,
    address NVARCHAR(255),
    date_joined DATETIME DEFAULT GETDATE()
);
GO

-- =============================================
-- Table: Bike
-- Description: Stores bike inventory information
-- =============================================
CREATE TABLE Bike (
    Bike_ID INT PRIMARY KEY IDENTITY(1,1),
    admin_id INT FOREIGN KEY REFERENCES Admin(Admin_ID),
    bike_name_model NVARCHAR(100) NOT NULL,
    bike_type NVARCHAR(50),
    availability_status NVARCHAR(20),
    hourly_rate DECIMAL(10,2),
    date_added DATETIME DEFAULT GETDATE()
);
GO

-- =============================================
-- Table: Rentals
-- Description: Stores rental transaction information
-- =============================================
CREATE TABLE Rentals (
    Rental_ID INT PRIMARY KEY IDENTITY(1,1),
    member_id INT FOREIGN KEY REFERENCES Member(Member_ID),
    bike_id INT FOREIGN KEY REFERENCES Bike(Bike_ID),
    admin_id INT FOREIGN KEY REFERENCES Admin(Admin_ID),
    rental_date DATE,
    rental_time TIME,
    return_date DATE,
    status NVARCHAR(20)
);
GO

-- =============================================
-- Table: Returns
-- Description: Stores bike return information
-- =============================================
CREATE TABLE Returns (
    Return_ID INT PRIMARY KEY IDENTITY(1,1),
    rental_id INT FOREIGN KEY REFERENCES Rentals(Rental_ID),
    admin_id INT FOREIGN KEY REFERENCES Admin(Admin_ID),
    return_date DATE,
    return_time TIME,
    condition NVARCHAR(50),
    remarks NVARCHAR(500)
);
GO

-- =============================================
-- Insert Sample Data
-- =============================================

-- Insert Sample Admin Users
INSERT INTO Admin (username, password, full_name, role) VALUES
('admin', 'admin123', 'System Administrator', 'Super Admin'),
('manager', 'manager123', 'John Manager', 'Manager');
GO

-- Insert Sample Members
INSERT INTO Member (first_name, last_name, contact_number, email, address) VALUES
('John', 'Anderson', '555-0001', 'john.anderson@email.com', '123 Main St, City'),
('Sarah', 'Johnson', '555-0002', 'sarah.j@email.com', '456 Oak Ave, Town'),
('Mike', 'Davis', '555-0003', 'mike.d@email.com', '789 Pine Rd, Village'),
('Emily', 'Wilson', '555-0004', 'emily.w@email.com', '321 Elm St, City');
GO

-- Insert Sample Bikes
INSERT INTO Bike (admin_id, bike_name_model, bike_type, availability_status, hourly_rate) VALUES
(1, 'Trek Mountain Pro', 'Mountain Bike', 'Available', 8.00),
(1, 'Giant City Cruiser', 'City Bike', 'Available', 5.00),
(1, 'Specialized Road Racer', 'Road Bike', 'Rented', 10.00),
(1, 'Canyon Trail Blazer', 'Mountain Bike', 'Available', 8.00),
(2, 'Schwinn Urban Commuter', 'City Bike', 'Available', 6.00),
(2, 'Cannondale Speed Demon', 'Road Bike', 'Available', 12.00),
(2, 'Trek City Explorer', 'City Bike', 'Maintenance', 5.00),
(1, 'Giant Mountain King', 'Mountain Bike', 'Available', 9.00);
GO

-- Insert Sample Rentals
INSERT INTO Rentals (member_id, bike_id, admin_id, rental_date, rental_time, return_date, status) VALUES
(1, 3, 1, '2026-01-15', '09:00:00', '2026-01-17', 'Active'),
(2, 1, 1, '2026-01-10', '10:00:00', '2026-01-10', 'Completed'),
(3, 2, 2, '2026-01-12', '14:00:00', '2026-01-15', 'Completed');
GO

-- Insert Sample Returns
INSERT INTO Returns (rental_id, admin_id, return_date, return_time, condition, remarks) VALUES
(2, 1, '2026-01-10', '18:00:00', 'Good', 'Bike returned in excellent condition'),
(3, 2, '2026-01-15', '16:30:00', 'Good', 'Minor scratches on handlebar');
GO

-- =============================================
-- Create Indexes for Better Performance
-- =============================================
CREATE INDEX IX_Member_Email ON Member(email);
CREATE INDEX IX_Bike_AvailabilityStatus ON Bike(availability_status);
CREATE INDEX IX_Bike_AdminID ON Bike(admin_id);
CREATE INDEX IX_Rentals_MemberID ON Rentals(member_id);
CREATE INDEX IX_Rentals_BikeID ON Rentals(bike_id);
CREATE INDEX IX_Rentals_Status ON Rentals(status);
CREATE INDEX IX_Returns_RentalID ON Returns(rental_id);
GO

-- =============================================
-- Script Complete
-- =============================================
PRINT 'BikeRental database created successfully!';
PRINT 'Total Tables Created: 5 (Admin, Member, Bike, Rentals, Returns)';
PRINT 'Sample data has been inserted.';
PRINT '';
PRINT 'Connection Details:';
PRINT 'Server: localhost';
PRINT 'Database: BikeRental';
PRINT 'Authentication: Windows Authentication';
GO
