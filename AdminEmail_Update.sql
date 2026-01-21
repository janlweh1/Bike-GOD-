-- Add Admin.email column and update profile procedures
USE BikeRental;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns WHERE Name = N'email' AND Object_ID = Object_ID(N'dbo.Admin')
)
BEGIN
    ALTER TABLE dbo.Admin ADD email NVARCHAR(100) NULL;
END
GO

IF OBJECT_ID(N'dbo.sp_UpdateAdminProfile', N'P') IS NOT NULL
BEGIN
    DROP PROCEDURE dbo.sp_UpdateAdminProfile;
END
GO

CREATE PROCEDURE dbo.sp_UpdateAdminProfile
    @AdminID INT,
    @Username NVARCHAR(50),
    @FullName NVARCHAR(100),
    @Email NVARCHAR(100) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.Admin SET username = @Username, full_name = @FullName, email = @Email
    WHERE Admin_ID = @AdminID;
END;
GO

IF OBJECT_ID(N'dbo.sp_GetAdminProfile', N'P') IS NOT NULL
BEGIN
    DROP PROCEDURE dbo.sp_GetAdminProfile;
END
GO

CREATE PROCEDURE dbo.sp_GetAdminProfile
    @AdminID INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT Admin_ID, username, full_name, role, email, photo_url
    FROM dbo.Admin
    WHERE Admin_ID = @AdminID;
END;
GO
