import sqlite3

db_path = "C:/Users/ahopa/.local/share/opencode/opencode.db"
conn = sqlite3.connect(db_path)
c = conn.cursor()
c.execute("SELECT name FROM sqlite_master WHERE type='table'")
for row in c.fetchall():
    print(row[0])
