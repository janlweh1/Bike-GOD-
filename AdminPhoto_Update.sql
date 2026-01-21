-- Admin photo_url migration and procedures
USE BikeRental;
GO

-- Add photo_url column to Admin if missing
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE Name = N'photo_url' AND Object_ID = Object_ID(N'dbo.Admin')
)
BEGIN
    ALTER TABLE dbo.Admin ADD photo_url NVARCHAR(255) NULL;
END
GO

-- Ensure sp_GetAdminProfile returns photo_url
IF OBJECT_ID(N'dbo.sp_GetAdminProfile', N'P') IS NOT NULL
BEGIN
    -- Recreate with photo_url included
    DROP PROCEDURE dbo.sp_GetAdminProfile;
END
GO

CREATE PROCEDURE dbo.sp_GetAdminProfile
    @AdminID INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT Admin_ID, username, full_name, role, photo_url
    FROM dbo.Admin
    WHERE Admin_ID = @AdminID;
END;
GO

-- Create or alter procedure to update photo_url
IF OBJECT_ID(N'dbo.sp_UpdateAdminPhotoUrl', N'P') IS NULL
    EXEC('CREATE PROCEDURE dbo.sp_UpdateAdminPhotoUrl AS BEGIN SET NOCOUNT ON; END');
GO

ALTER PROCEDURE dbo.sp_UpdateAdminPhotoUrl
    @AdminID INT,
    @PhotoUrl NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.Admin SET photo_url = @PhotoUrl WHERE Admin_ID = @AdminID;
END;
GO
