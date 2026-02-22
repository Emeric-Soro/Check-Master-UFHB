import sqlite3
import json
import os

db_path = "C:/Users/ahopa/.local/share/opencode/opencode.db"

conn = sqlite3.connect(db_path)
c = conn.cursor()

# Get schema of part table
c.execute("PRAGMA table_info(part)")
print("Columns in part:")
for row in c.fetchall():
    print(row)

# Let's search for ComponentHelper.php
# In the OpenCode database, parts are often JSON or have a text column or json column.
c.execute("SELECT * FROM part WHERE tool_call_id IS NOT NULL OR text LIKE '%ComponentHelper.php%' OR tool_name = 'write'")
with open("recovered_parts.txt", "w", encoding="utf-8") as f:
    for row in c.fetchall():
        f.write(str(row) + "\n\n===ROW===\n\n")

print("Done writing recovered_parts.txt")
