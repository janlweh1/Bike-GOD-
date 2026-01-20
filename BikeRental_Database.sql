

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
    username NVARCHAR(50) UNIQUE,
    first_name NVARCHAR(50) NOT NULL,
    last_name NVARCHAR(50) NOT NULL,
    contact_number NVARCHAR(20),
    email NVARCHAR(100) UNIQUE NOT NULL,
    password NVARCHAR(255) NOT NULL,
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
    SELECT Admin_ID, username, full_name, role
    FROM Admin
    WHERE Admin_ID = @AdminID;
END;
GO

-- Update admin profile (username, full_name)
CREATE PROCEDURE sp_UpdateAdminProfile
    @AdminID INT,
    @Username NVARCHAR(50),
    @FullName NVARCHAR(100)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE Admin SET username = @Username, full_name = @FullName
    WHERE Admin_ID = @AdminID;
END;
GO

-- Get member profile by ID
CREATE PROCEDURE sp_GetMemberProfile
    @MemberID INT
AS
BEGIN
    SELECT Member_ID, username, first_name, last_name, email, contact_number, address, date_joined
    FROM Member
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

-- Utility: Count members
CREATE PROCEDURE sp_CountMembers
AS
BEGIN
    SET NOCOUNT ON;
    SELECT COUNT(*) AS count FROM Member;
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
        (SELECT COUNT(*) FROM Rentals r WHERE r.member_id = m.Member_ID) AS TotalRentals,
        (SELECT COUNT(*) FROM Rentals r WHERE r.member_id = m.Member_ID AND r.status = 'Active') AS ActiveRentals
    FROM Member m
    ORDER BY m.Member_ID;
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

-- Upsert Business Info
IF OBJECT_ID('dbo.sp_UpdateBusinessInfo', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_UpdateBusinessInfo;
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

