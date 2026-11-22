-- OPTIONAL local-only seed for a SEPARATE database (SQL_Database_edoc_ai_nav_test).
-- Do NOT run against production or the original SQL_Database_edoc unless you intend to.
-- Synthetic names only. Remove with DROP DATABASE SQL_Database_edoc_ai_nav_test;

INSERT INTO `webuser` (`email`, `usertype`) VALUES
('navtest.dental@edoc.local', 'd')
ON DUPLICATE KEY UPDATE `usertype` = 'd';

INSERT INTO `doctor` (`docemail`, `docname`, `docpassword`, `docnic`, `doctel`, `specialties`) VALUES
('navtest.dental@edoc.local', 'Nav Test Dental', 'local-test-only', '000000001', '0110000001', 11);
