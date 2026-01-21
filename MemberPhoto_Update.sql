-- Member photo_url migration and procedures
USE BikeRental;
GO

-- Add photo_url column to Member if missing
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE Name = N'photo_url' AND Object_ID = Object_ID(N'dbo.Member')
)
BEGIN
    ALTER TABLE dbo.Member ADD photo_url NVARCHAR(255) NULL;
END
GO

-- Recreate sp_GetMemberProfile to include photo_url
IF OBJECT_ID(N'dbo.sp_GetMemberProfile', N'P') IS NOT NULL
BEGIN
    DROP PROCEDURE dbo.sp_GetMemberProfile;
END
GO

CREATE PROCEDURE dbo.sp_GetMemberProfile
    @MemberID INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT Member_ID, username, first_name, last_name, email, contact_number, address, photo_url, date_joined
    FROM dbo.Member
    WHERE Member_ID = @MemberID;
END;
GO

-- Create or alter procedure to update member photo_url
IF OBJECT_ID(N'dbo.sp_UpdateMemberPhotoUrl', N'P') IS NULL
    EXEC('CREATE PROCEDURE dbo.sp_UpdateMemberPhotoUrl AS BEGIN SET NOCOUNT ON; END');
GO

ALTER PROCEDURE dbo.sp_UpdateMemberPhotoUrl
    @MemberID INT,
    @PhotoUrl NVARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.Member SET photo_url = @PhotoUrl WHERE Member_ID = @MemberID;
END;
GO
