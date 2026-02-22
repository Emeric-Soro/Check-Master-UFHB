import sqlite3
import json
import os

db_path = "C:/Users/ahopa/.local/share/opencode/opencode.db"

conn = sqlite3.connect(db_path)
c = conn.cursor()

c.execute("SELECT data FROM part WHERE data LIKE '%ComponentHelper.php%'")
with open("recovered_components.txt", "w", encoding="utf-8") as f:
    for row in c.fetchall():
        f.write(row[0] + "\n\n===ROW===\n\n")

print("Done writing recovered_components.txt")
