# Optional navigation seed (local only)

Creates a **separate** MariaDB database `SQL_Database_edoc_ai_nav_test` from the original dump plus one synthetic dental doctor (`specialty_id = 11`).

This is for testing “recommendation → doctors list” when the original dump has no doctors in the 30 supported specialties.

- Does not modify `SQL_Database_edoc` if you follow the commands below.
- Uses fake local emails only.
- Remove with `DROP DATABASE SQL_Database_edoc_ai_nav_test;`
