

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
    role NVARCHAR(50),
    email NVARCHAR(100) NULL,
    photo_url NVARCHAR(255) NULL
);
GO

-- =============================================
-- Table: Member
-- Description: Stores member/customer information
-- =============================================
CREATE TABLE Member (
    Member_ID INT PRIMARY KEY IDENTITY(1,1),
    username NVARCHAR(50) UNIQUE,
    first_name NVARCHAR(50) NOT NULL,
    last_name NVARCHAR(50) NOT NULL,
    contact_number NVARCHAR(20),
    email NVARCHAR(100) UNIQUE NOT NULL,
    password NVARCHAR(255) NOT NULL,
    address NVARCHAR(255),
    photo_url NVARCHAR(255) NULL,
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
    photo_url NVARCHAR(255) NULL,
    bike_condition NVARCHAR(20) NOT NULL CONSTRAINT DF_Bike_Condition DEFAULT ('Excellent'),
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
    return_time TIME NULL,
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
INSERT INTO Member (username, first_name, last_name, contact_number, email, password, address) VALUES
('john_a', 'John', 'Anderson', '555-0001', 'john.anderson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Main St, City'),
('sarah_j', 'Sarah', 'Johnson', '555-0002', 'sarah.j@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456 Oak Ave, Town'),
('mike_d', 'Mike', 'Davis', '555-0003', 'mike.d@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '789 Pine Rd, Village'),
('emily_w', 'Emily', 'Wilson', '555-0004', 'emily.w@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '321 Elm St, City');
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
-- Stored Procedures
-- =============================================

-- =============================================
-- Login Procedures
-- =============================================

-- Get admin by username for login
CREATE PROCEDURE sp_GetAdminByUsername
    @Username NVARCHAR(50)
AS
BEGIN
    SELECT Admin_ID, username, password, full_name, role
    FROM Admin
    WHERE username = @Username;
END;
GO

-- Get member by username for login
CREATE PROCEDURE sp_GetMemberByUsername
    @Username NVARCHAR(50)
AS
BEGIN
    SELECT Member_ID, username, first_name, last_name, email, password
    FROM Member
    WHERE username = @Username;
END;
GO

-- Get member by email for login
CREATE PROCEDURE sp_GetMemberByEmail
    @Email NVARCHAR(100)
AS
BEGIN
    SELECT Member_ID, username, first_name, last_name, email, password
    FROM Member
    WHERE email = @Email;
END;
GO

-- =============================================
-- Profile Procedures
-- =============================================

-- Get admin profile by ID
CREATE PROCEDURE sp_GetAdminProfile
    @AdminID INT
AS
BEGIN
    SELECT Admin_ID, username, full_name, role, email, photo_url
    FROM Admin
    WHERE Admin_ID = @AdminID;
END;
GO

-- Update admin profile (username, full_name)
CREATE PROCEDURE sp_UpdateAdminProfile
    @AdminID INT,
    @Username NVARCHAR(50),
    @FullName NVARCHAR(100),
    @Email NVARCHAR(100) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Admin SET username = @Username, full_name = @FullName, email = @Email
    WHERE Admin_ID = @AdminID;
END;
GO

-- Get member profile by ID
CREATE PROCEDURE sp_GetMemberProfile
    @MemberID INT
AS
BEGIN
    SELECT Member_ID, username, first_name, last_name, email, contact_number, address, photo_url, date_joined
    FROM Member
    WHERE Member_ID = @MemberID;
END;
GO

-- Get member auth info by ID (email + password hash)
IF OBJECT_ID('dbo.sp_GetMemberAuthById', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetMemberAuthById;
GO
CREATE PROCEDURE dbo.sp_GetMemberAuthById
    @MemberID INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT email, password
    FROM dbo.Member
    WHERE Member_ID = @MemberID;
END;
GO

-- =============================================
-- Statistics Procedures
-- =============================================

-- Get admin statistics
CREATE PROCEDURE sp_GetAdminStats
AS
BEGIN
    SELECT
        (SELECT COUNT(*) FROM Bike) as TotalBikes,
        (SELECT COUNT(*) FROM Rentals WHERE status = 'Active') as ActiveRentals,
        (SELECT COUNT(*) FROM Member) as TotalMembers;
END;
GO

-- Get member statistics
CREATE PROCEDURE sp_GetMemberStats
    @MemberID INT
AS
BEGIN
    SELECT
        (SELECT COUNT(*) FROM Rentals WHERE member_id = @MemberID) as TotalRentals,
        (SELECT COUNT(*) FROM Rentals WHERE member_id = @MemberID AND status = 'Active') as ActiveRentals,
        0 as FavoriteBikes; -- Placeholder for future implementation
END;
GO

-- Register new member
CREATE PROCEDURE sp_RegisterMember
    @Username NVARCHAR(50),
    @FirstName NVARCHAR(50),
    @LastName NVARCHAR(50),
    @ContactNumber NVARCHAR(20) = NULL,
    @Email NVARCHAR(100),
    @Password NVARCHAR(255),
    @Address NVARCHAR(255) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    -- Check if username or email already exists
    IF EXISTS (SELECT 1 FROM Member WHERE username = @Username)
    BEGIN
        RAISERROR('Username already exists', 16, 1);
        RETURN;
    END

    IF EXISTS (SELECT 1 FROM Member WHERE email = @Email)
    BEGIN
        RAISERROR('Email already exists', 16, 1);
        RETURN;
    END

    -- Insert new member
    INSERT INTO Member (username, first_name, last_name, contact_number, email, password, address)
    VALUES (@Username, @FirstName, @LastName, @ContactNumber, @Email, @Password, @Address);

    -- Return the new member ID
    SELECT SCOPE_IDENTITY() as MemberID;
END;
GO

-- Utility: List basic member info
CREATE PROCEDURE sp_ListMembersBasic
AS
BEGIN
    SET NOCOUNT ON;
    SELECT Member_ID, first_name, last_name, email, username
    FROM Member
    ORDER BY Member_ID;
END;
GO

-- Utility: Update a member's username
CREATE PROCEDURE sp_UpdateMemberUsername
    @MemberID INT,
    @Username NVARCHAR(50)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Member SET username = @Username WHERE Member_ID = @MemberID;
END;
GO

-- Admin: Update basic member fields
IF OBJECT_ID('dbo.sp_UpdateMemberBasic', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateMemberBasic;
GO
CREATE PROCEDURE dbo.sp_UpdateMemberBasic
    @MemberID INT,
    @FirstName NVARCHAR(100),
    @LastName NVARCHAR(100),
    @Email NVARCHAR(255),
    @Phone NVARCHAR(50)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.Member
    SET first_name = @FirstName,
        last_name = @LastName,
        email = @Email,
        contact_number = @Phone
    WHERE Member_ID = @MemberID;

    SELECT @@ROWCOUNT AS RowsAffected;
END;
GO

-- Member-side: List rentals for a given member with bike info
IF OBJECT_ID('dbo.sp_GetMemberRentals', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetMemberRentals;
GO
CREATE PROCEDURE dbo.sp_GetMemberRentals
    @MemberID INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        r.Rental_ID,
        r.rental_date,
        r.rental_time,
        r.return_date,
        r.status AS rental_status,
        b.Bike_ID,
        b.bike_name_model,
        b.bike_type,
        b.hourly_rate
    FROM dbo.Rentals r
    INNER JOIN dbo.Bike b ON b.Bike_ID = r.bike_id
    WHERE r.member_id = @MemberID
    ORDER BY r.Rental_ID DESC;
END;
GO

-- Self-service: Update full member profile
IF OBJECT_ID('dbo.sp_UpdateMemberProfile', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateMemberProfile;
GO
CREATE PROCEDURE dbo.sp_UpdateMemberProfile
    @MemberID INT,
    @Username NVARCHAR(50) = NULL,
    @FirstName NVARCHAR(100),
    @LastName NVARCHAR(100),
    @Email NVARCHAR(255) = NULL,
    @Phone NVARCHAR(50) = NULL,
    @Address NVARCHAR(255) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE dbo.Member
    SET username = COALESCE(@Username, username),
        first_name = @FirstName,
        last_name = @LastName,
        email = COALESCE(@Email, email),
        contact_number = COALESCE(@Phone, contact_number),
        address = COALESCE(@Address, address)
    WHERE Member_ID = @MemberID;

    SELECT @@ROWCOUNT AS RowsAffected;
END;
GO

-- Self-service: Update member email only
IF OBJECT_ID('dbo.sp_UpdateMemberEmail', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateMemberEmail;
GO
CREATE PROCEDURE dbo.sp_UpdateMemberEmail
    @MemberID INT,
    @Email NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.Member SET email = @Email WHERE Member_ID = @MemberID;
    SELECT @@ROWCOUNT AS RowsAffected;
END;
GO

-- Self-service: Update member password hash
IF OBJECT_ID('dbo.sp_UpdateMemberPassword', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateMemberPassword;
GO
CREATE PROCEDURE dbo.sp_UpdateMemberPassword
    @MemberID INT,
    @PasswordHash NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.Member SET password = @PasswordHash WHERE Member_ID = @MemberID;
    SELECT @@ROWCOUNT AS RowsAffected;
END;
GO

-- Self-service: Update member photo URL
IF OBJECT_ID(N'dbo.sp_UpdateMemberPhotoUrl', N'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateMemberPhotoUrl;
GO
CREATE PROCEDURE dbo.sp_UpdateMemberPhotoUrl
    @MemberID INT,
    @PhotoUrl NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.Member SET photo_url = @PhotoUrl WHERE Member_ID = @MemberID;
END;
GO

-- Utility: Count members
CREATE PROCEDURE sp_CountMembers
AS
BEGIN
    SET NOCOUNT ON;
    SELECT COUNT(*) AS count FROM Member;
END;
GO

-- Utility: Count admins
IF OBJECT_ID('dbo.sp_CountAdmins', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_CountAdmins;
GO
CREATE PROCEDURE dbo.sp_CountAdmins
AS
BEGIN
    SET NOCOUNT ON;
    SELECT COUNT(*) AS AdminCount FROM dbo.Admin;
END;
GO

-- Utility: Check if an email is already used by another member
IF OBJECT_ID('dbo.sp_CheckMemberEmailUnique', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_CheckMemberEmailUnique;
GO
CREATE PROCEDURE dbo.sp_CheckMemberEmailUnique
    @MemberID INT,
    @Email NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;

    SELECT COUNT(*) AS Cnt
    FROM dbo.Member
    WHERE email = @Email
      AND Member_ID <> @MemberID;
END;
GO

-- Utility: Check if a username is already used by another member
IF OBJECT_ID('dbo.sp_CheckMemberUsernameUnique', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_CheckMemberUsernameUnique;
GO
CREATE PROCEDURE dbo.sp_CheckMemberUsernameUnique
    @MemberID INT,
    @Username NVARCHAR(50)
AS
BEGIN
    SET NOCOUNT ON;

    SELECT COUNT(*) AS Cnt
    FROM dbo.Member
    WHERE username = @Username
      AND Member_ID <> @MemberID;
END;
GO

-- Utility: Get top 3 members (basic info)
CREATE PROCEDURE sp_GetTopMembersEmails
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 3 email, first_name, last_name FROM Member ORDER BY Member_ID;
END;
GO

-- Admin: Get members with stats
CREATE PROCEDURE sp_GetMembersWithStats
AS
BEGIN
    SET NOCOUNT ON;
    SELECT 
        m.Member_ID,
        m.first_name,
        m.last_name,
        m.email,
        m.contact_number,
        m.username,
        m.date_joined,
        COUNT(DISTINCT r.Rental_ID) AS TotalRentals,
        SUM(CASE WHEN r.status IN ('Active','Pending') THEN 1 ELSE 0 END) AS ActiveRentals,
        ISNULL(SUM(CASE WHEN p.status = 'completed' THEN CAST(p.amount AS FLOAT) ELSE 0 END), 0) AS TotalSpent
    FROM Member m
    LEFT JOIN Rentals r ON r.member_id = m.Member_ID
    LEFT JOIN Payments p ON p.rental_id = r.Rental_ID
    GROUP BY
        m.Member_ID,
        m.first_name,
        m.last_name,
        m.email,
        m.contact_number,
        m.username,
        m.date_joined
    ORDER BY m.Member_ID;
END;
GO

-- Admin: Delete member only if no rentals exist
IF OBJECT_ID('dbo.sp_DeleteMemberIfNoRentals', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_DeleteMemberIfNoRentals;
GO
CREATE PROCEDURE dbo.sp_DeleteMemberIfNoRentals
    @MemberID INT
AS
BEGIN
    SET NOCOUNT ON;

    IF EXISTS (SELECT 1 FROM dbo.Rentals WHERE member_id = @MemberID)
    BEGIN
        RAISERROR('Cannot delete member with existing rentals', 16, 1);
        RETURN;
    END

    DELETE FROM dbo.Member WHERE Member_ID = @MemberID;
    SELECT @@ROWCOUNT AS RowsAffected;
END;
GO

-- Admin: Count members joined in current month
CREATE PROCEDURE sp_CountMembersNewThisMonth
AS
BEGIN
    SET NOCOUNT ON;
    SELECT COUNT(*) AS NewThisMonth
    FROM Member
    WHERE YEAR(date_joined) = YEAR(GETDATE()) AND MONTH(date_joined) = MONTH(GETDATE());
END;
GO

-- Admin: Count members joined in current ISO week
CREATE PROCEDURE sp_CountMembersNewThisWeek
AS
BEGIN
    SET NOCOUNT ON;
    SELECT COUNT(*) AS NewThisWeek
    FROM Member
    WHERE DATEPART(ISO_WEEK, date_joined) = DATEPART(ISO_WEEK, GETDATE())
      AND DATEPART(YEAR, date_joined) = DATEPART(YEAR, GETDATE());
END;
GO

-- Admin: Count members joined in previous month
CREATE PROCEDURE sp_CountMembersPrevMonth
AS
BEGIN
    SET NOCOUNT ON;
    SELECT COUNT(*) AS PrevMonth
    FROM Member
    WHERE YEAR(date_joined) = YEAR(DATEADD(MONTH,-1,GETDATE()))
      AND MONTH(date_joined) = MONTH(DATEADD(MONTH,-1,GETDATE()));
END;
GO

-- Admin: Count members with at least one active/ongoing rental
CREATE PROCEDURE sp_CountActiveRentalMembers
AS
BEGIN
    SET NOCOUNT ON;
    SELECT COUNT(DISTINCT member_id) AS Cnt
    FROM Rentals
    WHERE status IN ('Pending','Active');
END;
GO

-- =============================================
-- Admin Security Procedures
-- =============================================

-- Get admin auth info by ID (returns password for verification)
CREATE PROCEDURE sp_GetAdminAuthById
    @AdminID INT
AS
BEGIN
    SELECT Admin_ID, username, password FROM Admin WHERE Admin_ID = @AdminID;
END;
GO

-- Update admin password
CREATE PROCEDURE sp_UpdateAdminPassword
    @AdminID INT,
    @Password NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Admin SET password = @Password WHERE Admin_ID = @AdminID;
END;
GO

-- =============================================
-- Pricing Procedures
-- =============================================

-- Get average rates grouped by bike type
CREATE PROCEDURE sp_GetRatesByType
AS
BEGIN
    SET NOCOUNT ON;
    SELECT bike_type, AVG(hourly_rate) AS rate
    FROM Bike
    GROUP BY bike_type;
END;
GO

-- Update rate by bike type
CREATE PROCEDURE sp_UpdateRateByType
    @BikeType NVARCHAR(50),
    @Rate DECIMAL(10,2)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Bike SET hourly_rate = @Rate WHERE bike_type = @BikeType;
END;
GO

-- =============================================
-- Pricing: Per-Bike Procedures
-- =============================================

-- List bikes with hourly rates
CREATE PROCEDURE sp_ListBikesRates
AS
BEGIN
    SET NOCOUNT ON;
    SELECT Bike_ID, bike_name_model, bike_type, hourly_rate
    FROM Bike
    ORDER BY Bike_ID;
END;
GO

-- Update a single bike's hourly rate
CREATE PROCEDURE sp_UpdateBikeRate
    @BikeID INT,
    @Rate DECIMAL(10,2)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Bike SET hourly_rate = @Rate WHERE Bike_ID = @BikeID;
END;
GO

-- =============================================
-- Pricing: Additional Charges Settings
-- =============================================

IF OBJECT_ID('dbo.PricingSettings', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.PricingSettings (
        Settings_ID INT PRIMARY KEY IDENTITY(1,1),
        LateFeePerDay DECIMAL(10,2) NOT NULL DEFAULT 0,
        DamageFeeMin DECIMAL(10,2) NOT NULL DEFAULT 0,
        SecurityDeposit DECIMAL(10,2) NOT NULL DEFAULT 0,
        TaxInclusive BIT NOT NULL DEFAULT 1,
        UpdatedAt DATETIME NOT NULL DEFAULT GETDATE()
    );
    INSERT INTO dbo.PricingSettings (LateFeePerDay, DamageFeeMin, SecurityDeposit, TaxInclusive)
    VALUES (200, 1000, 2000, 1);
END
GO

IF OBJECT_ID('dbo.sp_GetPricingSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetPricingSettings;
GO
CREATE PROCEDURE dbo.sp_GetPricingSettings
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 1 Settings_ID, LateFeePerDay, DamageFeeMin, SecurityDeposit, TaxInclusive, UpdatedAt
    FROM dbo.PricingSettings
    ORDER BY Settings_ID DESC;
END;
GO

IF OBJECT_ID('dbo.sp_UpdatePricingSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdatePricingSettings;
GO
CREATE PROCEDURE dbo.sp_UpdatePricingSettings
    @LateFeePerDay DECIMAL(10,2),
    @DamageFeeMin DECIMAL(10,2),
    @SecurityDeposit DECIMAL(10,2),
    @TaxInclusive BIT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @Id INT;
    SELECT TOP 1 @Id = Settings_ID FROM dbo.PricingSettings ORDER BY Settings_ID DESC;
    IF @Id IS NULL
    BEGIN
        INSERT INTO dbo.PricingSettings (LateFeePerDay, DamageFeeMin, SecurityDeposit, TaxInclusive)
        VALUES (@LateFeePerDay, @DamageFeeMin, @SecurityDeposit, @TaxInclusive);
    END
    ELSE
    BEGIN
        UPDATE dbo.PricingSettings
        SET LateFeePerDay = @LateFeePerDay,
            DamageFeeMin = @DamageFeeMin,
            SecurityDeposit = @SecurityDeposit,
            TaxInclusive = @TaxInclusive,
            UpdatedAt = GETDATE()
        WHERE Settings_ID = @Id;
    END
END;
GO

-- =============================================
-- Script Complete
-- =============================================

-- =============================================
-- Compiled Sections from pricing_procs.sql
-- =============================================
-- Apply these procedures to your BikeRental database without dropping it
USE BikeRental;
GO

-- Get average hourly rates by bike type
IF OBJECT_ID('dbo.sp_GetRatesByType', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetRatesByType;
GO
CREATE PROCEDURE dbo.sp_GetRatesByType
AS
BEGIN
    SET NOCOUNT ON;
    SELECT bike_type, AVG(hourly_rate) AS rate
    FROM Bike
    GROUP BY bike_type;
END;
GO

-- Update hourly rate for all bikes of a given type
IF OBJECT_ID('dbo.sp_UpdateRateByType', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateRateByType;
GO
CREATE PROCEDURE dbo.sp_UpdateRateByType
    @BikeType NVARCHAR(50),
    @Rate DECIMAL(10,2)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Bike
    SET hourly_rate = @Rate
    WHERE bike_type = @BikeType;
END;
GO

-- =============================================
-- Compiled Sections from PricingSettings_Update.sql
-- =============================================
USE BikeRental;
GO

IF OBJECT_ID('dbo.PricingSettings', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.PricingSettings (
        Settings_ID INT PRIMARY KEY IDENTITY(1,1),
        LateFeePerDay DECIMAL(10,2) NOT NULL DEFAULT 0,
        DamageFeeMin DECIMAL(10,2) NOT NULL DEFAULT 0,
        SecurityDeposit DECIMAL(10,2) NOT NULL DEFAULT 0,
        TaxInclusive BIT NOT NULL DEFAULT 1,
        UpdatedAt DATETIME NOT NULL DEFAULT GETDATE()
    );
    INSERT INTO dbo.PricingSettings (LateFeePerDay, DamageFeeMin, SecurityDeposit, TaxInclusive)
    VALUES (200, 1000, 2000, 1);
END
GO

IF OBJECT_ID('dbo.sp_GetPricingSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetPricingSettings;
GO
CREATE PROCEDURE dbo.sp_GetPricingSettings
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 1 Settings_ID, LateFeePerDay, DamageFeeMin, SecurityDeposit, TaxInclusive, UpdatedAt
    FROM dbo.PricingSettings
    ORDER BY Settings_ID DESC;
END;
GO

IF OBJECT_ID('dbo.sp_UpdatePricingSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdatePricingSettings;
GO
CREATE PROCEDURE dbo.sp_UpdatePricingSettings
    @LateFeePerDay DECIMAL(10,2),
    @DamageFeeMin DECIMAL(10,2),
    @SecurityDeposit DECIMAL(10,2),
    @TaxInclusive BIT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @Id INT;
    SELECT TOP 1 @Id = Settings_ID FROM dbo.PricingSettings ORDER BY Settings_ID DESC;
    IF @Id IS NULL
    BEGIN
        INSERT INTO dbo.PricingSettings (LateFeePerDay, DamageFeeMin, SecurityDeposit, TaxInclusive)
        VALUES (@LateFeePerDay, @DamageFeeMin, @SecurityDeposit, @TaxInclusive);
    END
    ELSE
    BEGIN
        UPDATE dbo.PricingSettings
        SET LateFeePerDay = @LateFeePerDay,
            DamageFeeMin = @DamageFeeMin,
            SecurityDeposit = @SecurityDeposit,
            TaxInclusive = @TaxInclusive,
            UpdatedAt = GETDATE()
        WHERE Settings_ID = @Id;
    END
END;
GO

-- =============================================
-- Compiled Sections from BusinessInfo_Update.sql
-- =============================================
USE BikeRental;
GO

-- =============================================
-- Compiled Sections from GeneralSettings_Update.sql
-- =============================================
USE BikeRental;
GO

-- Create GeneralSettings table if it doesn't exist
IF OBJECT_ID('dbo.GeneralSettings', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.GeneralSettings (
        Settings_ID INT IDENTITY(1,1) PRIMARY KEY,
        SystemName NVARCHAR(200) NOT NULL DEFAULT N'BikeRental Management System',
        Language NVARCHAR(20) NOT NULL DEFAULT N'en',
        Timezone NVARCHAR(50) NOT NULL DEFAULT N'asia/manila',
        DateFormat NVARCHAR(20) NOT NULL DEFAULT N'dd/mm/yyyy',
        Currency NVARCHAR(10) NOT NULL DEFAULT N'php',
        RentalMinPeriod INT NOT NULL DEFAULT 1,
        RentalMaxDays INT NOT NULL DEFAULT 30,
        AutoLate BIT NOT NULL DEFAULT 1,
        RequireDeposit BIT NOT NULL DEFAULT 1,
        UpdatedAt DATETIME NOT NULL DEFAULT GETDATE(),
        UpdatedBy INT NULL
    );
    INSERT INTO dbo.GeneralSettings (SystemName, Language, Timezone, DateFormat, Currency)
    VALUES (N'BikeRental Management System', N'en', N'asia/manila', N'dd/mm/yyyy', N'php');
END
GO

-- Get General Settings
IF OBJECT_ID('dbo.sp_GetGeneralSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetGeneralSettings;
GO
CREATE PROCEDURE dbo.sp_GetGeneralSettings
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 1 
        Settings_ID,
        SystemName,
        Language,
        Timezone,
        DateFormat,
        Currency,
        RentalMinPeriod,
        RentalMaxDays,
        AutoLate,
        RequireDeposit,
        UpdatedAt,
        UpdatedBy
    FROM dbo.GeneralSettings
    ORDER BY Settings_ID DESC;
END;
GO

-- Update General Settings
IF OBJECT_ID('dbo.sp_UpdateGeneralSettings', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateGeneralSettings;
GO
CREATE PROCEDURE dbo.sp_UpdateGeneralSettings
    @SystemName NVARCHAR(200),
    @Language NVARCHAR(20),
    @Timezone NVARCHAR(50),
    @DateFormat NVARCHAR(20),
    @Currency NVARCHAR(10),
    @RentalMinPeriod INT,
    @RentalMaxDays INT,
    @AutoLate BIT,
    @RequireDeposit BIT,
    @UpdatedBy INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @Id INT;
    SELECT TOP 1 @Id = Settings_ID FROM dbo.GeneralSettings ORDER BY Settings_ID DESC;
    IF @Id IS NULL
    BEGIN
        INSERT INTO dbo.GeneralSettings (
            SystemName, Language, Timezone, DateFormat, Currency,
            RentalMinPeriod, RentalMaxDays, AutoLate, RequireDeposit,
            UpdatedAt, UpdatedBy
        )
        VALUES (
            @SystemName, @Language, @Timezone, @DateFormat, @Currency,
            @RentalMinPeriod, @RentalMaxDays, @AutoLate, @RequireDeposit,
            GETDATE(), @UpdatedBy
        );
    END
    ELSE
    BEGIN
        UPDATE dbo.GeneralSettings
        SET SystemName = @SystemName,
            Language = @Language,
            Timezone = @Timezone,
            DateFormat = @DateFormat,
            Currency = @Currency,
            RentalMinPeriod = @RentalMinPeriod,
            RentalMaxDays = @RentalMaxDays,
            AutoLate = @AutoLate,
            RequireDeposit = @RequireDeposit,
            UpdatedAt = GETDATE(),
            UpdatedBy = @UpdatedBy
        WHERE Settings_ID = @Id;
    END
END;
GO

-- Create BusinessInfo table if it doesn't exist
IF OBJECT_ID('dbo.BusinessInfo', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.BusinessInfo (
        BusinessInfo_ID INT IDENTITY(1,1) PRIMARY KEY,
        BusinessName NVARCHAR(200) NOT NULL DEFAULT '',
        Address NVARCHAR(500) NOT NULL DEFAULT '',
        Phone NVARCHAR(50) NOT NULL DEFAULT '',
        Email NVARCHAR(150) NOT NULL DEFAULT '',
        Website NVARCHAR(200) NOT NULL DEFAULT '',
        TIN NVARCHAR(50) NOT NULL DEFAULT '',
        WeekdaysOpen TIME NULL,
        WeekdaysClose TIME NULL,
        SaturdayOpen TIME NULL,
        SaturdayClose TIME NULL,
        SundayOpen TIME NULL,
        SundayClose TIME NULL,
        UpdatedAt DATETIME NOT NULL DEFAULT GETDATE(),
        UpdatedBy INT NULL
    );
    INSERT INTO dbo.BusinessInfo (BusinessName, Address, Phone, Email, Website, TIN)
    VALUES (N'BikeRental Inc.', N'123 Bike Street, Manila, Metro Manila, Philippines 1000', N'+63 912 345 6789', N'info@bikerental.com', N'www.bikerental.com', N'');
END
GO

-- Get Business Info
IF OBJECT_ID('dbo.sp_GetBusinessInfo', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetBusinessInfo;
GO
CREATE PROCEDURE dbo.sp_GetBusinessInfo
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 1 
        BusinessInfo_ID,
        BusinessName,
        Address,
        Phone,
        Email,
        Website,
        TIN,
        WeekdaysOpen,
        WeekdaysClose,
        SaturdayOpen,
        SaturdayClose,
        SundayOpen,
        SundayClose,
        UpdatedAt,
        UpdatedBy
    FROM dbo.BusinessInfo
    ORDER BY BusinessInfo_ID DESC;
END;
GO


-- =============================================
-- Compiled Sections from Bike_Update_AddCondition.sql
-- =============================================
-- Adds bike_condition to Bike table and updates relevant stored procedures
-- Safe to run multiple times

USE BikeRental;
GO

-- 1) Add column if not exists
IF COL_LENGTH('dbo.Bike','bike_condition') IS NULL
BEGIN
    ALTER TABLE dbo.Bike ADD bike_condition NVARCHAR(20) NULL CONSTRAINT DF_Bike_Condition DEFAULT ('Excellent');
    -- Backfill existing rows
    UPDATE dbo.Bike SET bike_condition = 'Excellent' WHERE bike_condition IS NULL;
END
GO

-- Add photo_url to Bike if it doesn't exist
IF COL_LENGTH('dbo.Bike','photo_url') IS NULL
BEGIN
    ALTER TABLE dbo.Bike ADD photo_url NVARCHAR(255) NULL;
END
GO

-- 2) Update sp_ListBikes to include condition
IF OBJECT_ID('dbo.sp_ListBikes', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_ListBikes;
GO
CREATE PROCEDURE dbo.sp_ListBikes
AS
BEGIN
    SET NOCOUNT ON;
    SELECT Bike_ID,
           bike_name_model,
           bike_type,
           availability_status,
           hourly_rate,
           bike_condition,
           photo_url
    FROM dbo.Bike
    ORDER BY Bike_ID;
END;
GO

-- 3) Update sp_AddBike to accept optional @Condition
IF OBJECT_ID('dbo.sp_AddBike', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_AddBike;
GO
CREATE PROCEDURE dbo.sp_AddBike
    @AdminID INT,
    @Model NVARCHAR(100),
    @Type NVARCHAR(50),
    @Status NVARCHAR(20),
    @Rate DECIMAL(10,2),
    @Condition NVARCHAR(20) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO dbo.Bike (admin_id, bike_name_model, bike_type, availability_status, hourly_rate, bike_condition)
    VALUES (@AdminID, @Model, @Type, @Status, @Rate, COALESCE(@Condition, 'Excellent'));

    SELECT CAST(SCOPE_IDENTITY() AS INT) AS Bike_ID;
END;
GO

-- 4) Update sp_UpdateBike to allow updating condition
IF OBJECT_ID('dbo.sp_UpdateBike', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateBike;
GO
CREATE PROCEDURE dbo.sp_UpdateBike
    @BikeID INT,
    @Model NVARCHAR(100) = NULL,
    @Type NVARCHAR(50) = NULL,
    @Status NVARCHAR(20) = NULL,
    @Rate DECIMAL(10,2) = NULL,
    @Condition NVARCHAR(20) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE dbo.Bike
    SET bike_name_model = COALESCE(@Model, bike_name_model),
        bike_type = COALESCE(@Type, bike_type),
        availability_status = COALESCE(@Status, availability_status),
        hourly_rate = COALESCE(@Rate, hourly_rate),
        bike_condition = COALESCE(@Condition, bike_condition)
    WHERE Bike_ID = @BikeID;
END;
GO

-- =============================================
-- Bikes Procedures (List/Add/Update/Delete)
-- Safe (no DB drop), re-creatable
-- =============================================
USE BikeRental;
GO

-- List bikes with availability and hourly rate
IF OBJECT_ID('dbo.sp_ListBikes', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_ListBikes;
GO
CREATE PROCEDURE dbo.sp_ListBikes
AS
BEGIN
    SET NOCOUNT ON;
    SELECT Bike_ID,
           bike_name_model,
           bike_type,
           availability_status,
           hourly_rate,
           photo_url
    FROM dbo.Bike
    ORDER BY Bike_ID;
END;
GO

-- Add a bike and return new ID
IF OBJECT_ID('dbo.sp_AddBike', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_AddBike;
GO
CREATE PROCEDURE dbo.sp_AddBike
    @AdminID INT,
    @Model NVARCHAR(100),
    @Type NVARCHAR(50),
    @Status NVARCHAR(20),
    @Rate DECIMAL(10,2)
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO dbo.Bike (admin_id, bike_name_model, bike_type, availability_status, hourly_rate)
    VALUES (@AdminID, @Model, @Type, @Status, @Rate);

    SELECT CAST(SCOPE_IDENTITY() AS INT) AS Bike_ID;
END;
GO

-- Update a bike; only fields provided (non-NULL) will be updated
IF OBJECT_ID('dbo.sp_UpdateBike', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateBike;
GO
CREATE PROCEDURE dbo.sp_UpdateBike
    @BikeID INT,
    @Model NVARCHAR(100) = NULL,
    @Type NVARCHAR(50) = NULL,
    @Status NVARCHAR(20) = NULL,
    @Rate DECIMAL(10,2) = NULL,
    @Condition NVARCHAR(20) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE dbo.Bike
    SET bike_name_model = COALESCE(@Model, bike_name_model),
        bike_type = COALESCE(@Type, bike_type),
        availability_status = COALESCE(@Status, availability_status),
        hourly_rate = COALESCE(@Rate, hourly_rate),
        bike_condition = COALESCE(@Condition, bike_condition)
    WHERE Bike_ID = @BikeID;

    SELECT @@ROWCOUNT AS RowsAffected;
END;
GO

-- Delete a bike (will fail if referenced by Rentals)
IF OBJECT_ID('dbo.sp_DeleteBike', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_DeleteBike;
GO
CREATE PROCEDURE dbo.sp_DeleteBike
    @BikeID INT
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM dbo.Bike WHERE Bike_ID = @BikeID;
    SELECT @@ROWCOUNT AS RowsAffected;
END;
GO

-- Cascade delete: Returns -> Rentals -> Bike
IF OBJECT_ID('dbo.sp_DeleteBikeCascade', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_DeleteBikeCascade;
GO
CREATE PROCEDURE dbo.sp_DeleteBikeCascade
    @BikeID INT
AS
BEGIN
    SET NOCOUNT ON;

    -- Delete returns associated to rentals of this bike
    DELETE r
    FROM dbo.Returns r
    INNER JOIN dbo.Rentals t ON t.Rental_ID = r.rental_id
    WHERE t.bike_id = @BikeID;

    -- Delete rentals for this bike
    DELETE FROM dbo.Rentals WHERE bike_id = @BikeID;

    -- Finally delete the bike
    DELETE FROM dbo.Bike WHERE Bike_ID = @BikeID;

    SELECT @@ROWCOUNT AS RowsAffected;
END;
GO

-- Update bike photo URL separately
IF OBJECT_ID('dbo.sp_UpdateBikePhoto', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateBikePhoto;
GO
CREATE PROCEDURE dbo.sp_UpdateBikePhoto
    @BikeID INT,
    @PhotoUrl NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;

    IF COL_LENGTH('dbo.Bike','photo_url') IS NULL
        RETURN;

    UPDATE dbo.Bike
    SET photo_url = @PhotoUrl
    WHERE Bike_ID = @BikeID;
END;
GO

-- Upsert Business Info
IF OBJECT_ID('dbo.sp_UpdateBusinessInfo', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateBusinessInfo;
GO

-- =============================================
-- Compiled Sections from Payments_Update.sql
-- Adds Payments table if missing; safe to run multiple times
-- =============================================
USE BikeRental;
GO

IF OBJECT_ID('dbo.Payments', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.Payments (
        Payment_ID INT IDENTITY(1,1) PRIMARY KEY,
        transaction_id NVARCHAR(50) NOT NULL,
        rental_id INT NOT NULL,
        member_id INT NULL,
        payment_method NVARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status NVARCHAR(20) NOT NULL,
        payment_date DATETIME NOT NULL,
        notes NVARCHAR(500) NULL,
        CreatedAt DATETIME NOT NULL DEFAULT GETDATE()
    );
    -- Optional foreign keys; enable if your schema uses these relationships
    -- ALTER TABLE dbo.Payments ADD CONSTRAINT FK_Payments_Rentals FOREIGN KEY (rental_id) REFERENCES dbo.Rentals(Rental_ID);
    -- ALTER TABLE dbo.Payments ADD CONSTRAINT FK_Payments_Member FOREIGN KEY (member_id) REFERENCES dbo.Member(Member_ID);

    -- Indexes for performance and integrity
    CREATE UNIQUE INDEX IX_Payments_TransactionId ON dbo.Payments(transaction_id);
    CREATE INDEX IX_Payments_RentalId ON dbo.Payments(rental_id);
    CREATE INDEX IX_Payments_Status ON dbo.Payments(status);
    CREATE INDEX IX_Payments_PaymentDate ON dbo.Payments(payment_date);
END
GO

-- Add foreign keys if not already present
IF OBJECT_ID('dbo.Payments', 'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_Payments_Rentals')
    BEGIN
        ALTER TABLE dbo.Payments ADD CONSTRAINT FK_Payments_Rentals FOREIGN KEY (rental_id)
        REFERENCES dbo.Rentals(Rental_ID);
    END
    IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_Payments_Member')
    BEGIN
        ALTER TABLE dbo.Payments ADD CONSTRAINT FK_Payments_Member FOREIGN KEY (member_id)
        REFERENCES dbo.Member(Member_ID);
    END
END
GO

-- Add integrity constraints and filtered unique index
IF OBJECT_ID('dbo.Payments', 'U') IS NOT NULL
BEGIN
    -- Ensure positive amount
    IF NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_Payments_Amount_Positive')
    BEGIN
        ALTER TABLE dbo.Payments ADD CONSTRAINT CK_Payments_Amount_Positive CHECK (amount > 0);
    END
    -- Only one completed payment per rental
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_Payments_Rental_Completed' AND object_id = OBJECT_ID('dbo.Payments'))
    BEGIN
        CREATE UNIQUE INDEX UX_Payments_Rental_Completed ON dbo.Payments(rental_id) WHERE status = 'completed';
    END
END
GO

-- Stored Procedure: Record Payment
IF OBJECT_ID('dbo.sp_RecordPayment', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_RecordPayment;
GO
CREATE PROCEDURE dbo.sp_RecordPayment
    @TransactionId NVARCHAR(50),
    @RentalId INT,
    @Amount DECIMAL(10,2),
    @PaymentMethod NVARCHAR(20),
    @Status NVARCHAR(20),
    @PaymentDate NVARCHAR(30), -- 'YYYY-MM-DD HH:MM'
    @Notes NVARCHAR(500) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    -- Validate rental and ensure it is not cancelled
    DECLARE @RentalStatus NVARCHAR(20);

    SELECT @RentalStatus = status
    FROM dbo.Rentals
    WHERE Rental_ID = @RentalId;

    IF @RentalStatus IS NULL
    BEGIN
        RAISERROR('Rental not found', 16, 1);
        RETURN;
    END

    IF LOWER(LTRIM(RTRIM(ISNULL(@RentalStatus, '')))) = 'cancelled'
    BEGIN
        RAISERROR('Cannot record payment for a cancelled rental', 16, 1);
        RETURN;
    END

    -- Validate datetime
    DECLARE @PaymentDt datetime = TRY_CONVERT(datetime, @PaymentDate);
    IF @PaymentDt IS NULL
    BEGIN
        RAISERROR('Invalid payment date/time', 16, 1);
        RETURN;
    END

    -- Enforce unique transaction id
    IF EXISTS (SELECT 1 FROM dbo.Payments WHERE transaction_id = @TransactionId)
    BEGIN
        RAISERROR('Duplicate transaction ID', 16, 1);
        RETURN;
    END

    -- Only one completed payment per rental
    IF LOWER(@Status) = 'completed' AND EXISTS (
        SELECT 1 FROM dbo.Payments WHERE rental_id = @RentalId AND status = 'completed'
    )
    BEGIN
        RAISERROR('Payment already completed for this rental', 16, 1);
        RETURN;
    END

    -- Compute expected amount when completing payment
    IF LOWER(@Status) = 'completed'
    BEGIN
        DECLARE @StartDt datetime, @EndDt datetime, @PlannedEnd datetime, @Rate DECIMAL(10,2), @DurationHours INT, @Expected DECIMAL(18,2);

        -- Start time + hourly rate
        SELECT 
            @StartDt = CONVERT(datetime, CONCAT(CONVERT(varchar(10), r.rental_date, 120), ' ', CONVERT(varchar(8), r.rental_time, 108))),
            @Rate = CAST(b.hourly_rate AS DECIMAL(10,2))
        FROM dbo.Rentals r
        INNER JOIN dbo.Bike b ON b.Bike_ID = r.bike_id
        WHERE r.Rental_ID = @RentalId;

        -- Planned end from Rentals (reflects any extensions)
        IF COL_LENGTH('dbo.Rentals','return_time') IS NOT NULL
        BEGIN
            SELECT @PlannedEnd = CONVERT(datetime, CONCAT(CONVERT(varchar(10), r.return_date, 120), ' ', CONVERT(varchar(8), r.return_time, 108)))
            FROM dbo.Rentals r
            WHERE r.Rental_ID = @RentalId;
        END
        ELSE
        BEGIN
            SELECT @PlannedEnd = CONVERT(datetime, CONCAT(CONVERT(varchar(10), r.return_date, 120), ' ', CONVERT(varchar(8), r.rental_time, 108)))
            FROM dbo.Rentals r
            WHERE r.Rental_ID = @RentalId;
        END

        -- Prefer actual return; if not returned yet, bill at least up to
        -- the planned end (including any extensions). If paying after the
        -- planned end, fall back to the actual payment datetime.
        SELECT TOP 1 @EndDt = CONVERT(datetime, CONCAT(CONVERT(varchar(10), x.return_date, 120), ' ', CONVERT(varchar(8), x.return_time, 108)))
        FROM dbo.Returns x
        WHERE x.rental_id = @RentalId
        ORDER BY x.Return_ID DESC;

        IF @EndDt IS NULL
        BEGIN
            IF @PlannedEnd IS NOT NULL AND @PaymentDt < @PlannedEnd
                SET @EndDt = @PlannedEnd;
            ELSE
                SET @EndDt = @PaymentDt;
        END

        SET @DurationHours = NULLIF(DATEDIFF(HOUR, @StartDt, @EndDt), 0);
        IF @DurationHours IS NULL OR @DurationHours < 1 SET @DurationHours = 1;
        SET @Expected = ROUND(@Rate * @DurationHours, 2);

        IF ABS(@Amount - @Expected) > 0.01
        BEGIN
            RAISERROR('Amount does not match expected rental cost', 16, 1);
            RETURN;
        END
    END

    DECLARE @MemberId INT = NULL;
    SELECT @MemberId = r.member_id FROM dbo.Rentals r WHERE r.Rental_ID = @RentalId;

    INSERT INTO dbo.Payments (
        transaction_id, rental_id, member_id, payment_method, amount, status, payment_date, notes, CreatedAt
    ) VALUES (
        @TransactionId,
        @RentalId,
        @MemberId,
        @PaymentMethod,
        @Amount,
        LOWER(@Status),
        @PaymentDt,
        NULLIF(@Notes, ''),
        GETDATE()
    );
END;
GO

-- Stored Procedure: Create a pending payment entry specifically for rental extensions
IF OBJECT_ID('dbo.sp_CreateExtensionPendingPayment', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_CreateExtensionPendingPayment;
GO
CREATE PROCEDURE dbo.sp_CreateExtensionPendingPayment
    @RentalId INT,
    @MemberId INT,
    @AdditionalHours INT,
    @Amount DECIMAL(10,2),
    @PaymentDateTime NVARCHAR(30) = NULL -- optional, defaults to now
AS
BEGIN
    SET NOCOUNT ON;

    -- Basic guards
    IF @AdditionalHours <= 0 OR @Amount <= 0
        RETURN;

    -- Ensure the rental exists and belongs to the member
    IF NOT EXISTS (
        SELECT 1 FROM dbo.Rentals WHERE Rental_ID = @RentalId AND member_id = @MemberId
    )
    BEGIN
        RAISERROR('Rental not found for member', 16, 1);
        RETURN;
    END

    -- Only create an extension payment if there is already at least one
    -- completed payment for this rental (i.e., the original booking was paid)
    IF NOT EXISTS (
        SELECT 1 FROM dbo.Payments WHERE rental_id = @RentalId AND status = 'completed'
    )
    BEGIN
        RAISERROR('No completed payment exists for this rental to extend from', 16, 1);
        RETURN;
    END

    DECLARE @Dt DATETIME = COALESCE(TRY_CONVERT(DATETIME, @PaymentDateTime), GETDATE());

    -- Generate a unique transaction id for the extension payment
    DECLARE @TxnId NVARCHAR(50);
    SET @TxnId = CONCAT('EXT-', @RentalId, '-', REPLACE(CONVERT(VARCHAR(19), @Dt, 120), ':', ''));

    WHILE EXISTS (SELECT 1 FROM dbo.Payments WHERE transaction_id = @TxnId)
    BEGIN
        SET @Dt = DATEADD(SECOND, 1, @Dt);
        SET @TxnId = CONCAT('EXT-', @RentalId, '-', REPLACE(CONVERT(VARCHAR(19), @Dt, 120), ':', ''));
    END

    DECLARE @MemberFromRental INT;
    SELECT @MemberFromRental = member_id
    FROM dbo.Rentals
    WHERE Rental_ID = @RentalId;

    INSERT INTO dbo.Payments (
        transaction_id,
        rental_id,
        member_id,
        payment_method,
        amount,
        status,
        payment_date,
        notes,
        CreatedAt
    )
    VALUES (
        @TxnId,
        @RentalId,
        @MemberFromRental,
        'cash',
        @Amount,
        'pending',
        @Dt,
        CONCAT('Extension of ', @AdditionalHours, ' hour(s)'),
        GETDATE()
    );
END;
GO

-- Stored Procedure: List Rentals with summary for admin
IF OBJECT_ID('dbo.sp_ListRentalsWithSummary', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_ListRentalsWithSummary;
GO

-- Admin: Create rental and mark bike as rented
IF OBJECT_ID('dbo.sp_CreateRental', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_CreateRental;
GO
CREATE PROCEDURE dbo.sp_CreateRental
    @MemberID INT,
    @BikeID INT,
    @AdminID INT,
    @RentalDate DATE,
    @RentalTime TIME,
    @PlannedReturnDate DATE,
    @PlannedReturnTime TIME = NULL,
    @Status NVARCHAR(20)
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @NewID INT;

    IF COL_LENGTH('dbo.Rentals','return_time') IS NOT NULL
    BEGIN
        INSERT INTO dbo.Rentals (member_id, bike_id, admin_id, rental_date, rental_time, return_date, return_time, status)
        VALUES (@MemberID, @BikeID, @AdminID, @RentalDate, @RentalTime, @PlannedReturnDate, @PlannedReturnTime, @Status);
    END
    ELSE
    BEGIN
        INSERT INTO dbo.Rentals (member_id, bike_id, admin_id, rental_date, rental_time, return_date, status)
        VALUES (@MemberID, @BikeID, @AdminID, @RentalDate, @RentalTime, @PlannedReturnDate, @Status);
    END

    SET @NewID = CAST(SCOPE_IDENTITY() AS INT);

    -- Mark bike as rented
    UPDATE dbo.Bike SET availability_status = 'Rented' WHERE Bike_ID = @BikeID;

    SELECT @NewID AS Rental_ID;
END;
GO

-- Member-side: Get basic bike info for rental creation
IF OBJECT_ID('dbo.sp_GetBikeForRental', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetBikeForRental;
GO
CREATE PROCEDURE dbo.sp_GetBikeForRental
    @BikeID INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        Bike_ID,
        admin_id,
        availability_status,
        hourly_rate
    FROM dbo.Bike
    WHERE Bike_ID = @BikeID;
END;
GO

-- Member-side: Get a single rental with bike/admin info for extension
IF OBJECT_ID('dbo.sp_GetRentalForExtend', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetRentalForExtend;
GO
CREATE PROCEDURE dbo.sp_GetRentalForExtend
    @RentalID INT,
    @MemberID INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        r.Rental_ID,
        r.rental_date,
        r.rental_time,
        r.return_date,
        r.return_time,
        r.status,
        r.bike_id,
        b.admin_id,
        b.hourly_rate
    FROM dbo.Rentals r
    INNER JOIN dbo.Bike b ON b.Bike_ID = r.bike_id
    WHERE r.Rental_ID = @RentalID
      AND r.member_id = @MemberID;
END;
GO

-- Helper: Get rental header + rate for expected amount calculation
IF OBJECT_ID('dbo.sp_GetRentalForExpectedAmount', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetRentalForExpectedAmount;
GO
CREATE PROCEDURE dbo.sp_GetRentalForExpectedAmount
    @RentalID INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        r.rental_date,
        r.rental_time,
        r.return_date,
        r.return_time,
        r.status,
        b.hourly_rate,
        r.Rental_ID
    FROM dbo.Rentals r
    INNER JOIN dbo.Bike b ON b.Bike_ID = r.bike_id
    WHERE r.Rental_ID = @RentalID;
END;
GO

-- Helper: Get latest actual return row for a rental
IF OBJECT_ID('dbo.sp_GetLatestReturnForRental', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetLatestReturnForRental;
GO
CREATE PROCEDURE dbo.sp_GetLatestReturnForRental
    @RentalID INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT TOP 1
        return_date,
        return_time
    FROM dbo.Returns
    WHERE rental_id = @RentalID
    ORDER BY Return_ID DESC;
END;
GO

-- Admin-side: Get basic rental info with member and bike details
IF OBJECT_ID('dbo.sp_GetRentalInfoAdmin', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetRentalInfoAdmin;
GO
CREATE PROCEDURE dbo.sp_GetRentalInfoAdmin
    @RentalID INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        r.Rental_ID,
        m.first_name,
        m.last_name,
        m.email,
        m.contact_number,
        b.bike_name_model
    FROM dbo.Rentals r
    INNER JOIN dbo.Member m ON m.Member_ID = r.member_id
    INNER JOIN dbo.Bike b ON b.Bike_ID = r.bike_id
    WHERE r.Rental_ID = @RentalID;
END;
GO

-- Self-service: Extend rental planned return
IF OBJECT_ID('dbo.sp_ExtendRental', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_ExtendRental;
GO
CREATE PROCEDURE dbo.sp_ExtendRental
    @RentalID INT,
    @MemberID INT,
    @NewReturnDate DATE,
    @NewReturnTime TIME = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF COL_LENGTH('dbo.Rentals','return_time') IS NOT NULL
    BEGIN
        UPDATE dbo.Rentals
        SET return_date = @NewReturnDate,
            return_time = @NewReturnTime
        WHERE Rental_ID = @RentalID AND member_id = @MemberID;
    END
    ELSE
    BEGIN
        UPDATE dbo.Rentals
        SET return_date = @NewReturnDate
        WHERE Rental_ID = @RentalID AND member_id = @MemberID;
    END

    SELECT @@ROWCOUNT AS RowsAffected;
END;
GO

-- Helper: Check if a rental already has a completed payment
IF OBJECT_ID('dbo.sp_CheckRentalHasCompletedPayment', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_CheckRentalHasCompletedPayment;
GO
CREATE PROCEDURE dbo.sp_CheckRentalHasCompletedPayment
    @RentalID INT
AS
BEGIN
    SET NOCOUNT ON;

    IF EXISTS (
        SELECT 1
        FROM dbo.Payments
        WHERE rental_id = @RentalID
          AND status = 'completed'
    )
    BEGIN
        SELECT CAST(1 AS BIT) AS HasCompletedPayment;
    END
    ELSE
    BEGIN
        SELECT CAST(0 AS BIT) AS HasCompletedPayment;
    END
END;
GO

-- Member-side: End (complete/cancel) rental with 5-minute cancellation window
IF OBJECT_ID('dbo.sp_MemberEndRental', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_MemberEndRental;
GO
CREATE PROCEDURE dbo.sp_MemberEndRental
    @RentalID INT,
    @MemberID INT,
    @RequestedAction NVARCHAR(20) = NULL -- 'complete' or 'cancel'; NULL = legacy behaviour
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE
        @DbMemberID INT,
        @BikeID INT,
        @AdminID INT,
        @RentalDate DATE,
        @RentalTime TIME,
        @Status NVARCHAR(20),
        @StatusLower NVARCHAR(20),
        @Now DATETIME,
        @StartDt DATETIME,
        @HasStarted BIT = 0,
        @CanCancelWindow BIT = 1,
        @ElapsedSeconds INT,
        @Action NVARCHAR(20);

    SELECT
        @DbMemberID = r.member_id,
        @BikeID = r.bike_id,
        @AdminID = r.admin_id,
        @RentalDate = r.rental_date,
        @RentalTime = r.rental_time,
        @Status = r.status
    FROM dbo.Rentals r
    WHERE r.Rental_ID = @RentalID;

    IF @DbMemberID IS NULL
    BEGIN
        RAISERROR('Rental not found', 16, 1);
        RETURN;
    END

    IF @DbMemberID <> @MemberID
    BEGIN
        RAISERROR('You do not own this rental', 16, 1);
        RETURN;
    END

    SET @StatusLower = LOWER(ISNULL(LTRIM(RTRIM(@Status)), ''));
    IF @StatusLower IN ('completed', 'cancelled')
    BEGIN
        SELECT
            CAST(1 AS BIT) AS Success,
            @StatusLower AS NewStatus,
            N'Rental already ended' AS Message;
        RETURN;
    END

    SET @Now = GETDATE();

    IF @RentalDate IS NOT NULL
    BEGIN
        SET @StartDt = TRY_CONVERT(DATETIME,
            CONVERT(VARCHAR(10), @RentalDate, 120) + ' ' +
            ISNULL(CONVERT(VARCHAR(8), @RentalTime, 108), '00:00:00'));
    END

    IF @StartDt IS NOT NULL AND @Now >= @StartDt
        SET @HasStarted = 1;

    -- Cancellation window:
    -- - Before start: allowed
    -- - After start: allowed only within first 5 minutes
    IF @StartDt IS NOT NULL
    BEGIN
        IF @Now < @StartDt
            SET @CanCancelWindow = 1;
        ELSE
        BEGIN
            SET @ElapsedSeconds = DATEDIFF(SECOND, @StartDt, @Now);
            SET @CanCancelWindow = CASE WHEN @ElapsedSeconds <= 300 THEN 1 ELSE 0 END;
        END
    END

    -- Normalise requested action
    IF @RequestedAction IS NOT NULL
    BEGIN
        SET @Action = LOWER(LTRIM(RTRIM(@RequestedAction)));
        IF @Action NOT IN ('complete', 'cancel') SET @Action = NULL;
    END
    ELSE
        SET @Action = NULL;

    BEGIN TRY
        BEGIN TRAN;

        -- If explicit cancel outside window, treat as complete
        IF @Action = 'cancel' AND @CanCancelWindow = 0
            SET @Action = 'complete';

        IF @Action = 'cancel'
            OR (@Action IS NULL AND (@HasStarted = 0 OR @CanCancelWindow = 1))
        BEGIN
            -- Cancel the rental
            UPDATE dbo.Rentals
            SET status = 'Cancelled'
            WHERE Rental_ID = @RentalID;

            -- Free up the bike
            IF @BikeID IS NOT NULL AND @BikeID > 0
            BEGIN
                UPDATE dbo.Bike
                SET availability_status = 'Available'
                WHERE Bike_ID = @BikeID;
            END

            -- If the customer has already paid and this cancellation
            -- is happening within the allowed 5-minute window from the
            -- start time, mark any completed payment for this rental as
            -- refundable by changing its status to 'refunded'.
            --
            -- Revenue-related procedures only sum rows with
            -- status = 'completed', so marking a payment as 'refunded'
            -- removes it from revenue while still keeping the record.
            IF @CanCancelWindow = 1
            BEGIN
                UPDATE dbo.Payments
                SET status = 'refunded'
                WHERE rental_id = @RentalID
                  AND status = 'completed';
            END

            COMMIT TRAN;
            SELECT CAST(1 AS BIT) AS Success,
                   N'cancelled' AS NewStatus,
                   N'Rental cancelled' AS Message;
            RETURN;
        END

        -- Otherwise complete
        UPDATE dbo.Rentals
        SET status = 'Completed',
            return_date = ISNULL(return_date, CONVERT(DATE, @Now))
        WHERE Rental_ID = @RentalID;

        IF NOT EXISTS (SELECT 1 FROM dbo.Returns WHERE rental_id = @RentalID)
        BEGIN
            INSERT INTO dbo.Returns (rental_id, admin_id, return_date, return_time, condition, remarks)
            VALUES (@RentalID, @AdminID, CONVERT(DATE, @Now), CONVERT(TIME, @Now), 'Good', 'Returned by member via portal');
        END

        IF @BikeID IS NOT NULL AND @BikeID > 0
        BEGIN
            UPDATE dbo.Bike
            SET availability_status = 'Available'
            WHERE Bike_ID = @BikeID;
        END

        COMMIT TRAN;
        SELECT CAST(1 AS BIT) AS Success,
               N'completed' AS NewStatus,
               N'Rental completed' AS Message;
    END TRY
    BEGIN CATCH
        IF XACT_STATE() <> 0 ROLLBACK TRAN;
        DECLARE @ErrMsg NVARCHAR(4000) = ERROR_MESSAGE();
        RAISERROR(@ErrMsg, 16, 1);
    END CATCH
END;
GO

-- Admin: Complete rental, create return if missing, free bike
IF OBJECT_ID('dbo.sp_CompleteRentalAdmin', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_CompleteRentalAdmin;
GO
CREATE PROCEDURE dbo.sp_CompleteRentalAdmin
    @RentalID INT,
    @AdminID INT
AS
BEGIN
    SET NOCOUNT ON;

    -- If already completed, just exit with success flag
    IF EXISTS (SELECT 1 FROM dbo.Rentals WHERE Rental_ID = @RentalID AND LOWER(status) = 'completed')
    BEGIN
        SELECT 0 AS AlreadyCompleted, 0 AS RowsAffected;
        RETURN;
    END

    BEGIN TRY
        BEGIN TRAN;

        -- Mark rental completed and ensure return_date
        UPDATE dbo.Rentals
        SET status = 'Completed',
            return_date = ISNULL(return_date, CONVERT(date, GETDATE()))
        WHERE Rental_ID = @RentalID;

        -- Insert Returns row if none exists
        IF NOT EXISTS (SELECT 1 FROM dbo.Returns WHERE rental_id = @RentalID)
        BEGIN
            INSERT INTO dbo.Returns (rental_id, admin_id, return_date, return_time, condition, remarks)
            VALUES (@RentalID, @AdminID, CONVERT(date, GETDATE()), CONVERT(time, GETDATE()), 'Good', '');
        END

        -- Free the bike
        UPDATE dbo.Bike
        SET availability_status = 'Available'
        WHERE Bike_ID = (SELECT bike_id FROM dbo.Rentals WHERE Rental_ID = @RentalID);

        COMMIT TRAN;
        SELECT 0 AS AlreadyCompleted, 1 AS RowsAffected;
    END TRY
    BEGIN CATCH
        IF XACT_STATE() <> 0 ROLLBACK TRAN;
        DECLARE @ErrMsg NVARCHAR(4000) = ERROR_MESSAGE();
        RAISERROR(@ErrMsg, 16, 1);
    END CATCH
END;
GO

-- Helper: Get rental status and member for simple checks
IF OBJECT_ID('dbo.sp_GetRentalStatus', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetRentalStatus;
GO
CREATE PROCEDURE dbo.sp_GetRentalStatus
    @RentalID INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        Rental_ID,
        status,
        member_id
    FROM dbo.Rentals
    WHERE Rental_ID = @RentalID;
END;
GO
CREATE PROCEDURE dbo.sp_ListRentalsWithSummary
AS
BEGIN
    SET NOCOUNT ON;

    -- Main rentals listing with latest return info
    SELECT r.Rental_ID,
           r.rental_date,
           r.rental_time,
           r.return_date AS planned_return_date,
           r.return_time AS planned_return_time,
           r.status,
           m.first_name, m.last_name, m.email, m.contact_number,
           b.bike_name_model, b.bike_type, b.hourly_rate,
           rr.return_date AS actual_return_date,
           rr.return_time AS actual_return_time
    FROM dbo.Rentals r
    INNER JOIN dbo.Member m ON m.Member_ID = r.member_id
    INNER JOIN dbo.Bike b ON b.Bike_ID = r.bike_id
    OUTER APPLY (
        SELECT TOP 1 return_date, return_time
        FROM dbo.Returns x
        WHERE x.rental_id = r.Rental_ID
        ORDER BY x.Return_ID DESC
    ) rr
    ORDER BY r.Rental_ID DESC;

    -- Summary metrics for admin overview
    SELECT
        ActiveCount = (SELECT COUNT(*) FROM dbo.Rentals WHERE status IN ('Pending','Active')),
        OverdueCount = (SELECT COUNT(*) FROM dbo.Rentals WHERE status IN ('Pending','Active') AND return_date < CONVERT(date, GETDATE())),
        CompletedToday = (SELECT COUNT(*) FROM dbo.Returns WHERE return_date = CONVERT(date, GETDATE())),
        TodayRevenue = (
            SELECT SUM(
                CAST(b.hourly_rate AS FLOAT) * NULLIF(DATEDIFF(HOUR,
                    CONVERT(datetime, CONCAT(CONVERT(varchar(10), r.rental_date, 120), ' ', CONVERT(varchar(8), r.rental_time, 108))),
                    CONVERT(datetime, CONCAT(CONVERT(varchar(10), x.return_date, 120), ' ', CONVERT(varchar(8), x.return_time, 108)))
                ), 0)
            )
            FROM dbo.Returns x
            INNER JOIN dbo.Rentals r ON r.Rental_ID = x.rental_id
            INNER JOIN dbo.Bike b ON b.Bike_ID = r.bike_id
            WHERE x.return_date = CONVERT(date, GETDATE())
        );
END;
GO

-- Stored Procedure: List Payments (latest first)
IF OBJECT_ID('dbo.sp_ListPayments', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_ListPayments;
GO
CREATE PROCEDURE dbo.sp_ListPayments
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 200 p.Payment_ID, p.transaction_id, p.payment_date, p.rental_id,
           p.payment_method, p.amount, p.status,
           m.first_name, m.last_name
    FROM dbo.Payments p
    LEFT JOIN dbo.Rentals r ON r.Rental_ID = p.rental_id
    LEFT JOIN dbo.Member m ON m.Member_ID = r.member_id
    ORDER BY p.Payment_ID DESC;
END;
GO

-- Confirm a pending payment and mark related rental as completed when applicable
IF OBJECT_ID('dbo.sp_ConfirmPayment', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_ConfirmPayment;
GO
CREATE PROCEDURE dbo.sp_ConfirmPayment
    @PaymentID INT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @RentalID INT;

    SELECT @RentalID = rental_id
    FROM dbo.Payments
    WHERE Payment_ID = @PaymentID;

    IF @RentalID IS NULL
    BEGIN
        RAISERROR('Payment not found', 16, 1);
        RETURN;
    END

    -- Ensure we do not create a second completed payment for the same rental
    IF EXISTS (
        SELECT 1
        FROM dbo.Payments
        WHERE rental_id = @RentalID
          AND status = 'completed'
          AND Payment_ID <> @PaymentID
    )
    BEGIN
        RAISERROR('A completed payment already exists for this rental.', 16, 1);
        RETURN;
    END

    UPDATE dbo.Payments
    SET status = 'completed',
        payment_date = GETDATE()
    WHERE Payment_ID = @PaymentID
      AND status = 'pending';

    IF @@ROWCOUNT = 0
    BEGIN
        RAISERROR('Payment not pending or not found', 16, 1);
        RETURN;
    END

    -- Best-effort: mark related rental as completed
    IF @RentalID IS NOT NULL
    BEGIN
        UPDATE dbo.Rentals
        SET status = 'Completed'
        WHERE Rental_ID = @RentalID
          AND (status IS NULL OR status <> 'Completed');
    END

    SELECT CAST(1 AS BIT) AS Success;
END;
GO

-- Payments listing with filters (status, method, range, sort)
IF OBJECT_ID('dbo.sp_GetPaymentsFiltered', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetPaymentsFiltered;
GO
CREATE PROCEDURE dbo.sp_GetPaymentsFiltered
    @Status NVARCHAR(20) = NULL,
    @Method NVARCHAR(20) = NULL,
    @Range NVARCHAR(10) = NULL, -- 'today','week','month' or NULL
    @Sort  NVARCHAR(20) = NULL  -- 'recent','oldest','amount-high','amount-low'
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @Sql NVARCHAR(MAX) = N'
        SELECT p.Payment_ID, p.transaction_id, p.payment_date, p.rental_id,
               p.payment_method, p.amount, p.status,
               m.first_name, m.last_name
        FROM dbo.Payments p
        LEFT JOIN dbo.Rentals r ON r.Rental_ID = p.rental_id
        LEFT JOIN dbo.Member m ON m.Member_ID = r.member_id
        WHERE 1 = 1';

    DECLARE @Params NVARCHAR(200) = N'@Status NVARCHAR(20), @Method NVARCHAR(20), @Range NVARCHAR(10)';

    IF @Status IS NOT NULL AND LTRIM(RTRIM(@Status)) <> ''
        SET @Sql += N' AND p.status = @Status';

    IF @Method IS NOT NULL AND LTRIM(RTRIM(@Method)) <> ''
        SET @Sql += N' AND p.payment_method = @Method';

    IF @Range IS NOT NULL
    BEGIN
        DECLARE @RangeLower NVARCHAR(10) = LOWER(LTRIM(RTRIM(@Range)));
        IF @RangeLower = 'today'
            SET @Sql += N' AND CAST(p.payment_date AS date) = CAST(GETDATE() AS date)';
        ELSE IF @RangeLower = 'week'
            SET @Sql += N' AND CAST(p.payment_date AS date) >= DATEADD(day, -6, CAST(GETDATE() AS date))';
        ELSE IF @RangeLower = 'month'
            SET @Sql += N' AND YEAR(p.payment_date) = YEAR(GETDATE()) AND MONTH(p.payment_date) = MONTH(GETDATE())';
    END

    DECLARE @SortLower NVARCHAR(20) = LOWER(ISNULL(LTRIM(RTRIM(@Sort)), 'recent'));
    IF @SortLower = 'oldest'
        SET @Sql += N' ORDER BY p.Payment_ID ASC';
    ELSE IF @SortLower = 'amount-high'
        SET @Sql += N' ORDER BY p.amount DESC';
    ELSE IF @SortLower = 'amount-low'
        SET @Sql += N' ORDER BY p.amount ASC';
    ELSE
        SET @Sql += N' ORDER BY p.Payment_ID DESC';

    EXEC sp_executesql @Sql, @Params,
        @Status = @Status,
        @Method = @Method,
        @Range = @Range;
END;
GO

-- Payments summary + pending overview (used by dashboard)
IF OBJECT_ID('dbo.sp_GetPaymentSummaryAndPending', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetPaymentSummaryAndPending;
GO
CREATE PROCEDURE dbo.sp_GetPaymentSummaryAndPending
    @Range NVARCHAR(10) = NULL -- for pending filters: today/week/month
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE
        @SumToday FLOAT = 0,
        @SumWeek FLOAT = 0,
        @SumMonth FLOAT = 0,
        @SumYesterday FLOAT = 0,
        @SumPrevWeek FLOAT = 0,
        @SumPrevMonth FLOAT = 0,
        @PendingCount INT = 0,
        @PendingTotal FLOAT = 0,
        @TodayChangePct FLOAT = NULL,
        @WeekChangePct FLOAT = NULL,
        @MonthChangePct FLOAT = NULL;

    SELECT @SumToday = ISNULL(SUM(CAST(amount AS FLOAT)), 0)
    FROM dbo.Payments
    WHERE status = 'completed'
      AND CAST(payment_date AS date) = CAST(GETDATE() AS date);

    SELECT @SumWeek = ISNULL(SUM(CAST(amount AS FLOAT)), 0)
    FROM dbo.Payments
    WHERE status = 'completed'
      AND CAST(payment_date AS date) >= DATEADD(day, -6, CAST(GETDATE() AS date));

    SELECT @SumMonth = ISNULL(SUM(CAST(amount AS FLOAT)), 0)
    FROM dbo.Payments
    WHERE status = 'completed'
      AND YEAR(payment_date) = YEAR(GETDATE())
      AND MONTH(payment_date) = MONTH(GETDATE());

    SELECT @SumYesterday = ISNULL(SUM(CAST(amount AS FLOAT)), 0)
    FROM dbo.Payments
    WHERE status = 'completed'
      AND CAST(payment_date AS date) = DATEADD(day, -1, CAST(GETDATE() AS date));

    SELECT @SumPrevWeek = ISNULL(SUM(CAST(amount AS FLOAT)), 0)
    FROM dbo.Payments
    WHERE status = 'completed'
      AND CAST(payment_date AS date) >= DATEADD(day, -13, CAST(GETDATE() AS date))
      AND CAST(payment_date AS date) <  DATEADD(day, -6, CAST(GETDATE() AS date));

    SELECT @SumPrevMonth = ISNULL(SUM(CAST(amount AS FLOAT)), 0)
    FROM dbo.Payments
    WHERE status = 'completed'
      AND YEAR(payment_date) = YEAR(DATEADD(month,-1,GETDATE()))
      AND MONTH(payment_date) = MONTH(DATEADD(month,-1,GETDATE()));

    IF @SumYesterday > 0 SET @TodayChangePct = ((@SumToday - @SumYesterday) / @SumYesterday) * 100.0;
    IF @SumPrevWeek > 0 SET @WeekChangePct = ((@SumWeek - @SumPrevWeek) / @SumPrevWeek) * 100.0;
    IF @SumPrevMonth > 0 SET @MonthChangePct = ((@SumMonth - @SumPrevMonth) / @SumPrevMonth) * 100.0;

    DECLARE @RangeLower NVARCHAR(10) = LOWER(LTRIM(RTRIM(ISNULL(@Range, ''))));

    -- Pending payments (optionally filtered by range)
    SELECT
        @PendingCount = ISNULL(COUNT(*), 0),
        @PendingTotal = ISNULL(SUM(CAST(amount AS FLOAT)), 0)
    FROM dbo.Payments
    WHERE status = 'pending'
      AND (
            @RangeLower = ''
         OR (@RangeLower = 'today'  AND CAST(payment_date AS date) = CAST(GETDATE() AS date))
         OR (@RangeLower = 'week'   AND CAST(payment_date AS date) >= DATEADD(day, -6, CAST(GETDATE() AS date)))
         OR (@RangeLower = 'month'  AND YEAR(payment_date) = YEAR(GETDATE()) AND MONTH(payment_date) = MONTH(GETDATE()))
      );

    SELECT
        @SumToday       AS TodayRevenue,
        @SumWeek        AS WeekRevenue,
        @SumMonth       AS MonthRevenue,
        @PendingCount   AS PendingCount,
        @PendingTotal   AS PendingTotal,
        @TodayChangePct AS TodayChangePct,
        @WeekChangePct  AS WeekChangePct,
        @MonthChangePct AS MonthChangePct;
END;
GO

-- Method sums (completed payments by method)
IF OBJECT_ID('dbo.sp_GetPaymentMethodSums', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetPaymentMethodSums;
GO
CREATE PROCEDURE dbo.sp_GetPaymentMethodSums
AS
BEGIN
    SET NOCOUNT ON;
    SELECT
        LOWER(ISNULL(payment_method, '')) AS payment_method,
        SUM(CAST(amount AS FLOAT)) AS TotalAmount
    FROM dbo.Payments
    WHERE status = 'completed'
    GROUP BY payment_method;
END;
GO

-- Recent payment activity (latest 8 rows)
IF OBJECT_ID('dbo.sp_GetRecentPaymentActivity', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetRecentPaymentActivity;
GO
CREATE PROCEDURE dbo.sp_GetRecentPaymentActivity
AS
BEGIN
    SET NOCOUNT ON;
    SELECT TOP 8
        p.payment_date,
        p.amount,
        p.status,
        m.first_name,
        m.last_name
    FROM dbo.Payments p
    LEFT JOIN dbo.Rentals r ON r.Rental_ID = p.rental_id
    LEFT JOIN dbo.Member m ON m.Member_ID = r.member_id
    ORDER BY p.payment_date DESC, p.Payment_ID DESC;
END;
GO

-- Rentals without a completed payment (unpaid)
IF OBJECT_ID('dbo.sp_GetUnpaidRentals', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_GetUnpaidRentals;
GO
CREATE PROCEDURE dbo.sp_GetUnpaidRentals
AS
BEGIN
        SET NOCOUNT ON;

        SELECT
                r.Rental_ID,
                r.rental_date,
                r.rental_time,
                r.status AS rental_status,
                m.first_name,
                m.last_name,
                b.bike_name_model
        FROM dbo.Rentals r
        INNER JOIN dbo.Member m ON m.Member_ID = r.member_id
        INNER JOIN dbo.Bike b   ON b.Bike_ID   = r.bike_id
        WHERE r.status <> 'Cancelled'
            AND (
                        -- Original behaviour: rentals with no completed payment at all
                        NOT EXISTS (
                                SELECT 1 FROM dbo.Payments pc
                                WHERE pc.rental_id = r.Rental_ID
                                    AND pc.status = 'completed'
                        )
                        OR
                        -- New behaviour: rentals that have a pending payment (for example,
                        -- an extension amount after the original booking was already paid)
                        EXISTS (
                                SELECT 1 FROM dbo.Payments pp
                                WHERE pp.rental_id = r.Rental_ID
                                    AND pp.status = 'pending'
                        )
                    )
        ORDER BY r.Rental_ID DESC;
END;
GO
CREATE PROCEDURE dbo.sp_UpdateBusinessInfo
    @BusinessName NVARCHAR(200),
    @Address NVARCHAR(500),
    @Phone NVARCHAR(50),
    @Email NVARCHAR(150),
    @Website NVARCHAR(200),
    @TIN NVARCHAR(50),
    @WeekdaysOpen NVARCHAR(10) = NULL, -- 'HH:MM'
    @WeekdaysClose NVARCHAR(10) = NULL,
    @SaturdayOpen NVARCHAR(10) = NULL,
    @SaturdayClose NVARCHAR(10) = NULL,
    @SundayOpen NVARCHAR(10) = NULL,
    @SundayClose NVARCHAR(10) = NULL,
    @UpdatedBy INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @Id INT;
    SELECT TOP 1 @Id = BusinessInfo_ID FROM dbo.BusinessInfo ORDER BY BusinessInfo_ID DESC;

    DECLARE 
        @wOpen TIME = TRY_CONVERT(TIME, @WeekdaysOpen),
        @wClose TIME = TRY_CONVERT(TIME, @WeekdaysClose),
        @sOpen TIME = TRY_CONVERT(TIME, @SaturdayOpen),
        @sClose TIME = TRY_CONVERT(TIME, @SaturdayClose),
        @suOpen TIME = TRY_CONVERT(TIME, @SundayOpen),
        @suClose TIME = TRY_CONVERT(TIME, @SundayClose);

    IF @Id IS NULL
    BEGIN
        INSERT INTO dbo.BusinessInfo (
            BusinessName, Address, Phone, Email, Website, TIN,
            WeekdaysOpen, WeekdaysClose, SaturdayOpen, SaturdayClose,
            SundayOpen, SundayClose, UpdatedAt, UpdatedBy
        )
        VALUES (
            @BusinessName, @Address, @Phone, @Email, @Website, @TIN,
            @wOpen, @wClose, @sOpen, @sClose,
            @suOpen, @suClose, GETDATE(), @UpdatedBy
        );
    END
    ELSE
    BEGIN
        UPDATE dbo.BusinessInfo
        SET BusinessName = @BusinessName,
            Address = @Address,
            Phone = @Phone,
            Email = @Email,
            Website = @Website,
            TIN = @TIN,
            WeekdaysOpen = @wOpen,
            WeekdaysClose = @wClose,
            SaturdayOpen = @sOpen,
            SaturdayClose = @sClose,
            SundayOpen = @suOpen,
            SundayClose = @suClose,
            UpdatedAt = GETDATE(),
            UpdatedBy = @UpdatedBy
        WHERE BusinessInfo_ID = @Id;
    END
END;
GO

